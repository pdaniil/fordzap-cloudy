<?php
/**
 * Модуль выбора своего географического узла
*/
/*
Логика работы:
- если гео-узел - единственный, то модуль скрывается и автоматически ставит этот населенный пункт
- если населенных пунктов несколько,
	- если свой гео-узел еще не выбран - выдается окно для выбора
	- если узел выбран, то просто показывается модуль
*/
defined('_ASTEXE_') or die('No access');

// -------------------------------------------
// 1. Проверяем куку – если есть, получаем данные узла
// -------------------------------------------
if( isset($_COOKIE["my_city"]) )
{
	$stmt = $db_link->prepare('SELECT * FROM `shop_geo` WHERE `id` = :id;');
	$stmt->bindValue(':id', $_COOKIE["my_city"]);
	$stmt->execute();
	$node = $stmt->fetch();
	if(empty($node)){
		$_COOKIE["my_city"] = null;
	}
}

// -------------------------------------------
// 2. Подключаем необходимые файлы и получаем дерево гео
// -------------------------------------------
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/geo/dp_geo_node_record.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/geo/get_geo_tree.php");
//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getUserSession();

// -------------------------------------------
// 3. Получаем первый город (для использования по умолчанию)
// -------------------------------------------
$stmt = $db_link->query('SELECT `id`, `value` FROM `shop_geo` WHERE  `count` = 0 ORDER BY `id` LIMIT 1');
$first_geo = $stmt->fetch(PDO::FETCH_ASSOC);
$first_geo_id   = $first_geo ? $first_geo['id'] : 0;
$first_geo_name = $first_geo ? $first_geo['value'] : '';

// -------------------------------------------
// 4. Получаем общее количество городов
// -------------------------------------------
$stmt = $db_link->query('SELECT COUNT(*) FROM `shop_geo`');
$total_geo = $stmt->fetchColumn();

// -------------------------------------------
// 5. Если городов больше одного – выводим модуль выбора
// -------------------------------------------
if( $total_geo > 1 )
{
	// Гео-узлов больше одного.
	// Выставляем текущий город (по умолчанию – первый)
	$city_name = $first_geo_name;
	?>
    <!-- Start Модальное окно -->
        <div style="display:none" id="modal_content_div">
        	<div class="popup_content">
        		<a href="javascript:void(0);" class="popup_window_close" style="text-decoration:none;"><span style="position: relative; top: -5px; left: -3px;">X</span></a>
				<font style="font-weight:bold;">Для получения актуальной информации по ценам и наличию, выберите свое местоположение</font>
        		<div class="geo_list">
        			<?php
        			$geo_tree_dump = json_decode($geo_tree_dump_JSON, true);
        			printGeoNodes($geo_tree_dump);
        			?>
        		</div>
        	</div>
        </div>
        <script>
        	//Создание модального окна
        	var div_modal = document.createElement('div');//Объект DIV
        	div_modal.setAttribute('class', 'popup_window');//Класс в соответствии со стилем
        	div_modal.innerHTML = document.getElementById("modal_content_div").innerHTML;//Содержимое окна берем из образца
        	document.body.insertBefore(div_modal, document.body.firstChild);//Добавляем окно в самое начало BODY. Т.о. окно будет выше всех
        </script>
        <script>
        	modal_geo_list = $('.popup_window');
        	modal_geo_list.click(function(event) {
        		e = event || window.event
        		if (e.target == this) {
        			$(modal_geo_list).css('display', 'none')
        		}
        	});
        	$('.popup_window_close').click(function() {
        		modal_geo_list.css('display', 'none');
        	});
        	// ----------------------------------------------------------------
        	function openPopupWindow_CityList()
        	{
        		modal_geo_list.css('display', 'block');
        	}
        	// ----------------------------------------------------------------
        </script>
    <!-- End Модальное окно -->
	<?php
	// -------------------------------------------
	// Если кука установлена – показываем её название
	// -------------------------------------------
	if( isset($_COOKIE["my_city"]) )
	{
		$stmt = $db_link->prepare('SELECT `value` FROM `shop_geo` WHERE `id` = :id;');
		$stmt->bindValue(':id', $_COOKIE["my_city"]);
		$stmt->execute();
		$city_name_record = $stmt->fetch(PDO::FETCH_ASSOC);
		if( $city_name_record != false )
		{
			$city_name = $city_name_record["value"];
		}
		?>
		<div class="customer_city" id="customer_city" onclick="openPopupWindow_CityList();">
			<i style="font-size: 1.2em;" class="fa fa-map-marker" aria-hidden="true"></i> <span><?php echo htmlspecialchars($city_name); ?></span>
		</div>
		<?php
	}
	else
	// -------------------------------------------
	// Куки не установлены – автоматически ставим первый город
	// -------------------------------------------
	{
		?>
		<div class="customer_city" id="customer_city" onclick="openPopupWindow_CityList();">
			<i style="font-size: 1.2em;" class="fa fa-map-marker" aria-hidden="true"></i>
			<span><?php echo htmlspecialchars($first_geo_name); ?></span>
		</div>
		<script>
			// Автоматически устанавливаем первый город (перезагрузит страницу)
			set_my_city(<?php echo (int)$first_geo_id; ?>);
		</script>
		<?php
	}
} //~if($total_geo > 1)
else
// -------------------------------------------
// Если единственный географический узел – ставим его автоматически
// -------------------------------------------
{
	if (empty($_COOKIE["my_city"]))
	{
		// Используем уже полученный первый (и единственный) город
		?>
		<script>
			set_my_city(<?php echo (int)$first_geo_id; ?>);
		</script>
		<?php
	}
}

// -------------------------------------------
// 6. JavaScript-функция для установки города (AJAX)
// -------------------------------------------
?>
<script>
//Устновка своего города
function set_my_city(id)
{
    //Увеличиваем наличие на сервере и только после этого отображаем
    jQuery.ajax({
        type: "POST",
        async: false, //Запрос синхронный
        url: "/modules/shop/geo/ajax_set_my_city.php",
        dataType: "json",//Тип возвращаемого значения
        data: "geo_id="+id+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
        success: function(answer)
        {
            if(answer == 1)
            {
                location.reload();
            }
        }
    });
}
</script>
<?php

// -------------------------------------------
// 7. Рекурсивная функция для вывода географических узлов
// -------------------------------------------
function printGeoNodes($geo_tree_dump)
{
    for($i=0; $i < count($geo_tree_dump); $i++)
    {
        switch((integer)$geo_tree_dump[$i]['level'])
    	{
    		case 1:
    			$geo_node_class = "geo_top_level";
    			break;
    		case 2:
    			$geo_node_class = "geo_second_level";
    			break;
    		default:
    			$geo_node_class = "geo_default_level";
    			break;
    	}
		if($geo_node_class == "geo_default_level"){
		?>
		<div class="<?php echo $geo_node_class; ?>" onclick="set_my_city(<?php echo $geo_tree_dump[$i]["id"]; ?>);" style="cursor:pointer"><?php echo $geo_tree_dump[$i]["value"]; ?></div>
		<?php
		}else{
		?>
		<div class="<?php echo $geo_node_class; ?>"><?php echo $geo_tree_dump[$i]["value"]; ?></div>
		<?php
		}
	    if(count($geo_tree_dump[$i]["data"]) > 0)
	    {
	        printGeoNodes($geo_tree_dump[$i]["data"]);
	    }
    }//~for($i=0; $i < count($geo_tree_dump); $i++)
}//~function printGeoNodes($geo_tree_dump)
?>