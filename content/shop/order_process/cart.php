<?php
/**
 * Страничный скрипт для корзины
*/
defined('_ASTEXE_') or die('No access');


//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getUserSession();


//Получаем данные по валюте магазина
$currency_query = $db_link->prepare('SELECT * FROM `shop_currencies` WHERE `iso_code` = ?;');
$currency_query->execute( array($DP_Config->shop_currency) );
$currency_record = $currency_query->fetch();
$currency_sign = $currency_record["sign"];
//Строка для обозначения валюты
if($DP_Config->currency_show_mode == "no")
{
	$currency_indicator = "";
}
else if($DP_Config->currency_show_mode == "sign_before" || $DP_Config->currency_show_mode == "sign_after")
{
	$currency_indicator = $currency_sign;
}
else
{
	$currency_indicator = $currency_record["caption_short"];
}




require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();
if($user_id > 0)
{
	//Поля для авторизованного пользователя
	$session_id = 0;
}
else
{
	//Поля для НЕ авторизованного пользователя
	$session_record = DP_User::getUserSession();
	if($session_record == false)
	{
		$result = array();
		$result["status"] = false;
		$result["code"] = "incorrect_session";
		$result["message"] = "Ошибка сессии";
		exit(json_encode($result));
	}
	
	$session_id = $session_record["id"];
}




$cart_records = array();//Список записей корзины для этого пользователя

//Получаем содержимое его корзины из базы данных
$cart_records_query = $db_link->prepare('SELECT `id` FROM `shop_carts` WHERE `user_id` = ? AND `session_id` = ?;');
$cart_records_query->execute( array($user_id, $session_id) );
while($cart_record = $cart_records_query->fetch() )
{
	array_push($cart_records, $cart_record["id"]);
}
?>




<div id="cart_area"></div>
<style>
#cart_area .table th,
#cart_area .table td{
	vertical-align: middle;
    white-space: nowrap;
}
#cart_area .table input[type="checkbox"]{
	width: 25px;
    height: 25px;
}
</style>



<?php
if(count($cart_records) == 0)
{
    ?>
    <script>
        document.getElementById("cart_area").innerHTML = "Корзина пуста";
    </script>
    <?php
}
else
{
    ?>
    <script>
    // --------------------------------------------------------------------------------------
    //Переотобразить корзину
    function refreshCartArea()
    {
		//Строки с суммой в корзине
		sum_total = Number(sum_total).toFixed(2);
		var sum_total_num = Number(sum_total);
		sum_total = digit(sum_total);

		let update_order_items_check = false;

		<?php
		//Индикатор валюты перед ценой
		if($DP_Config->currency_show_mode == "sign_before")
		{
			?>
			sum_total = '<font class=\"currency\"><?php echo $currency_indicator; ?></font> <font class=\"price\">' + sum_total + "</font>";
			<?php
		}
		//Индикатор валюты после цены
		else if($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after")
		{
			?>
			sum_total = '<font class=\"price\">'+sum_total+'</font> <font class=\"currency\"><?php echo $currency_indicator; ?></font>';
			<?php
		}
		?>
		// ----------------------------------------------
		
        //Обновление корзины снизу
        if(typeof updateCartInfo == 'function') {
            updateCartInfo();
        }
        
        // ----------------------------------------------
        
		//Обработка модуля корзины - в случае наличия: (Для старых шаблонов)
		var cart_module_positions = document.getElementById("cart_module_positions");
		if(cart_module_positions != undefined)
		{
			cart_module_positions.innerHTML = "<b>Товаров в корзине</b> " + cart_records.length;
		}
		var cart_module_sum = document.getElementById("cart_module_sum");
		if(cart_module_sum != undefined)
		{
			cart_module_sum.innerHTML = "<b>На сумму</b> " + sum_total;
		} 
		
		// ----------------------------------------------
		
		
        //Если последний товар был удален из корзины
        if(cart_records.length == 0)
        {
            document.getElementById("cart_area").innerHTML = "Корзина пуста";
            return;
        }
        
       
		// Определим есть ли в корзине товары с изображениями и только при их наличии будем отображать колонку с картинкой товара
		var there_is_images = false;
		for(var i=0; i < cart_records.length; i++)
        {
			if( cart_records[i].image_src != '' ){
				there_is_images = true;
				break;
			}
		}

		// Определим есть ли устаревшие товары в корзине
		var old_cart_items = false;
		for(var i=0; i < cart_records.length; i++)
        {
			if( cart_records[i].access == 0 ){
				old_cart_items = true;
				break;
			}
		}
		
		// Определим все ли позиции уорзины выбраны
		var check_all = 'checked';
		for(var i=0; i < cart_records.length; i++)
        {
			if( cart_records[i].checked_for_order == 0 ){
				check_all = '';
				break;
			}
		}
		
		var cart_html = "";//HTML корзины
		
		cart_html += '<div style="overflow: hidden; overflow-x: auto;">';
		cart_html += '<table class="table cart_table">';
		cart_html += 	'<tr>';
		cart_html += 		'<th><input type="checkbox" '+check_all+' id="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>';
		
		if(there_is_images === true){
		cart_html += 		'<th></th>';
		}
		
		cart_html += 		'<th>Производитель</th>';
		cart_html += 		'<th>Артикул</th>';
		cart_html += 		'<th>Наименование</th>';
		cart_html += 		'<th>Склад</th>';
		cart_html += 		'<th>Срок</th>';
		cart_html += 		'<th>Цена</th>';
		cart_html += 		'<th>Количество</th>';
		cart_html += 		'<th>Сумма</th>';
		cart_html += 		'<th></th>';
		cart_html += 	'</tr>';
		
        for(var i=0; i < cart_records.length; i++)
        {
            var checked_for_order = 'checked';
			if( cart_records[i].checked_for_order == 0 ){
				checked_for_order = '';
			}
			
			//Строки с ценами
			var price = digit(Number(cart_records[i].price).toFixed(2));//Цена позиции
			var price_sum = digit(Number(cart_records[i].price_sum).toFixed(2));//Сумма позиции
			<?php
			if($DP_Config->currency_show_mode == "sign_before")
			{
				?>
				price = '<?php echo $currency_indicator; ?> '+ price;
				price_sum = '<?php echo $currency_indicator; ?> '+ price_sum;
				<?php
			}
			else if($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after")
			{
				?>
				price = price +' <?php echo $currency_indicator; ?>';
				price_sum = price_sum +' <?php echo $currency_indicator; ?>';
				<?php
			}
			?>
			
			
			var style_tr = '';// Стили строки позиции корзины (используется при различных доработках)
			
			
			//////////////////////////////////////////////////////////////////////// - Доработка id 34
			if(cart_records[i].access == 0){
				style_tr = 'background:#f4f90e;';
				price = '<s>'+price+'</s>';
				price_sum = '<s>'+price_sum+'</s>';
			}//////////////////////////////////////////////////////////////////////// - END Доработка id 34
			//////////////////////////////////////////////////////////////////////// - Доработка id О.К.
			if(cart_records[i].is_update == 3) {
				let update_price = cart_records[i].update_price;
				let update_exist = cart_records[i].update_exist;
				console.log('update_price', update_price);
				console.log('update_exist', update_exist);
				console.log('count_need', cart_records[i].t2_exist);
				style_tr = 'background:linear-gradient(90deg, rgba(84,232,108,1) 40%, rgba(103,191,255,1) 60%);';
				price = '<span>' + price + '</span> <i data-toggle="tooltip" title="Старая цена - ' + update_price + ', Требовалось - ' + update_exist + '" style="position:relative;" class="fa fa-question-circle"></i>';
			} else {
				if(cart_records[i].is_update == 1){
					let update_price = cart_records[i].update_price;
					console.log('update_price', update_price);
					style_tr = 'background:#54e86c;';
					price = '<span>' + price + '</span> <i data-toggle="tooltip" title="Старая цена - ' + update_price + '" style="position:relative;" class="fa fa-question-circle"></i>';
				}
				if(cart_records[i].is_update == 2){
				let update_exist = cart_records[i].update_exist;
				// if(+cart_records[i].count_need > +cart_records[i].t2_exist) {
				// 	cart_records[i].count_need = cart_records[i].t2_exist;
				// }
				console.log('update_exist', update_exist);
				console.log('count_need', cart_records[i].t2_exist);
				style_tr = 'background:#67bfff;';
				price = '<span>' + price + '</span> <i data-toggle="tooltip" title="Требовалось - ' + update_exist + '" style="position:relative;" class="fa fa-question-circle"></i>';
				}
			}
			//////////////////////////////////////////////////////////////////////// - END Доработка id О.К.
			//////////////////////////////////////////////////////////////////////// - END Доработка id 34

			
			cart_html += '<tr style="'+style_tr+'">';
			
				cart_html += '<td>';
					cart_html += '<input type="checkbox" '+checked_for_order+' onclick="check_for_order('+cart_records[i].id+');">';
				cart_html += '</td>';
				
				
				if(there_is_images === true){
				if(cart_records[i].image_src !== ''){
				cart_html += '<td>';
                    cart_html += '<img style="max-width:50px; max-height:70px;" src="'+cart_records[i].image_src+'"/>';
                cart_html += '</td>';
				}else{
				cart_html += '<td>';
					cart_html += '';
				cart_html += '</td>';
				}
				}
			
			
                cart_html += '<td style="font-weight: bold;">';
                    cart_html += cart_records[i].manufacturer;
                cart_html += '</td>';
                
				
				cart_html += '<td style="font-weight: bold;">';
                    cart_html += cart_records[i].article;
                cart_html += '</td>';
				
				
				cart_html += '<td style="white-space: normal; line-height: 1em; width: 100%; min-width: 200px;">';
                    cart_html += cart_records[i].name;
                cart_html += '</td>';
				
				cart_html += '<td style="font-weight: bold;">';
                    cart_html += cart_records[i].storage_caption;
                cart_html += '</td>';
				
				console.log(cart_records[i]);
				
				cart_html += '<td>';

				// Проверяем есть ли данные о доставке
				if (cart_records[i].delivery_start || cart_records[i].delivery_end) {
					// Форматируем время доставки
					const deliveryHtml = formatDelivery(
						parseInt(cart_records[i].delivery_start),
						parseInt(cart_records[i].delivery_end)
					);
					
					if (deliveryHtml) {
						cart_html += deliveryHtml;
					} else {
						cart_html += cart_records[i].time_to_exe || '';
					}
				} else {
					// Если нет данных о доставке, показываем time_to_exe
					cart_html += cart_records[i].time_to_exe || '';
				}

				cart_html += '</td>';
				
				
				cart_html += '<td>';
                    cart_html += price;
                cart_html += '</td>';
				
				
				cart_html += '<td><table style="margin:auto;"><tr>';
                    cart_html += '<td><a style="display: inline-block; background: #f5f5f5; font-weight: bold; width: 22px; height: 25px; line-height: 24px; text-decoration: none; text-align: center; border-radius: 3px 0px 0px 3px; border: 1px solid #999; border-right: 0;" onclick="minusCountNeed('+cart_records[i].id+');" href="javascript:void(0);"><span style="position: relative; top: -2px;">-</span></a></td>';
                    cart_html += '<td><input style="width: 40px; height: 25px; line-height: 24px; text-align: center; border: 1px solid #999; border-radius: unset; box-shadow: none;" type="text" value="'+cart_records[i].count_need+'" onkeyup="onKeyUpCountNeed('+cart_records[i].id+');" id="count_need_'+cart_records[i].id+'"/></td>';
                    cart_html += '<td><a style="display: inline-block; background: #f5f5f5; font-weight: bold; width: 22px; height: 25px; line-height: 24px; text-decoration: none; text-align: center; border-radius: 0px 3px 3px 0px; border: 1px solid #999; border-left: 0;" onclick="plusCountNeed('+cart_records[i].id+');" href="javascript:void(0);"><span>+</span></a></td>';
                cart_html += '</tr></table></td>';
				
				
				cart_html += '<td style="font-weight: bold;">';
                    cart_html += price_sum;
                cart_html += '</td>';
				
				
				cart_html += '<td style="text-align: right;">';
					
					cart_html += '<a style="margin-right:5px;" href="javascript:void(0);" onclick="RefreschRecord('+cart_records[i].id+');" title="Перезаказать"><i style="font-size: 20px; position: relative; top: -1px;" class="fa fa-refresh" aria-hidden="true"></i></a>';
					
					cart_html += '<a style="margin-right:5px;" href="javascript:void(0);" title="Добавить в блокнот" onclick="show_add_bloknot('+cart_records[i].id+');"><i style="font-size: 18px; position: relative; top: -2px;" class="fa fa-car"></i></a>';
					
					cart_html += '<a href="javascript:void(0);" onclick="deleteRecord('+cart_records[i].id+');" title="Удалить"><i style="font-size: 25px;" class="fa fa-times" aria-hidden="true"></i></a>';
                
				cart_html += '</td>';
				
            cart_html += '</tr>';
        }
		
        cart_html += '</table>';
        cart_html += '</div>';
		
		
		//////////////////////////////////////////////////////////////////////// - Доработка id 34
			var flag_no_access_exist = false;
			for(var i=0; i < cart_records.length; i++)
			{
				if(cart_records[i].access == 0){
					flag_no_access_exist = true;
					break;
				}
			}
			if(flag_no_access_exist === true){
				cart_html += '<p style="background: #f4f90e; padding: 10px; margin: 10px 0px; border-radius: 5px; border: 1px solid #ddd;"><i class="fa fa-info-circle" aria-hidden="true"></i> Некоторые позиции корзины устарели. Перед оформлением заказа выделенные позиции необходимо перезаказать - нажмите <i class="fa fa-refresh" aria-hidden="true"></i></p>';
			}
		//////////////////////////////////////////////////////////////////////// - END Доработка id 34
		
		
					//////////////////////////////////////////////////////////////////////// - Доработка id 34
					var flag_no_price_update = false;
			for(var i=0; i < cart_records.length; i++)
			{
				if(cart_records[i].is_update == 1 || cart_records[i].is_update == 3){
					flag_no_price_update = true;
					break;
				}
			}
			if(flag_no_price_update === true){
				cart_html += '<p style="background: #54e86c; padding: 10px; margin: 10px 0px; border-radius: 5px; border: 1px solid #ddd;"><i class="fa fa-info-circle" aria-hidden="true"></i> Некоторые позиции корзины обновились. Чтобы узнать прежнюю стоимость кликните на вопрос рядом с ценой. </p>';
			}
			var flag_no_exist_update = false;
			for(var i=0; i < cart_records.length; i++)
			{
				if(cart_records[i].is_update == 2 || cart_records[i].is_update == 3){
					flag_no_exist_update = true;
					break;
				}
			}
			if(flag_no_exist_update === true){
				cart_html += '<p style="background: #67bfff; padding: 10px; margin: 10px 0px; border-radius: 5px; border: 1px solid #ddd;"><i class="fa fa-info-circle" aria-hidden="true"></i> Для некоторых позиций обновилось наличие товара у поставщика. </p>';
			}

			<?php
	      $order_update_html = '';
	      
	      if((int)$user_id > 0) {
	          $order_update_html = '<span style="display:none;" id="confirm_loader">Подождите... <img src="/content/files/images/ajax-loader-transparent.gif" /></span> <a id="update_items_btn" class="btn btn-ar btn-warning" onClick="update_order(cart_records, ' .$user_id. ');" href="javascript:void(0);" title="Обновить все устаревшие позиции в корзине">Обновить позиции</a>';
	      }
  		?>

		cart_html += '<p style="background: #eee; padding: 10px; margin: 10px 0px; border-radius: 5px; border: 1px solid #ddd;"><i class="fa fa-info-circle" aria-hidden="true"></i> Стоимость заказа в корзине указана на условиях самовывоза. Для выполнения заказа может потребоваться предоплата.</p>';
		
		
		<?php
		//Выводим кнопки для печати документов
		$print_docs_buttons = "";
		$print_docs_query = $db_link->prepare("SELECT * FROM `shop_print_docs` WHERE `name` IN('user_cart') ORDER BY `id` ASC;");
		$print_docs_query->execute();
		while( $print_doc = $print_docs_query->fetch() )
		{
			$print_doc["parameters_values"] = json_decode($print_doc["parameters_values"], true);
			
			
			if( isset( $DP_Config->wholesaler ) )
			{
				$doc_query_2 = $db_link->prepare("SELECT * FROM `shop_print_docs_wholesaler` WHERE `doc_name` = ? AND `office_id` = ( SELECT `office_id` FROM `shop_orders` WHERE `id` = ? ) ;");
				$doc_query_2->execute( array( $print_doc["name"] , $order_id ) );
				$doc_record_2 = $doc_query_2->fetch();
				if( $doc_record_2 != false )
				{
					$print_doc["parameters_values"] = json_decode($doc_record_2["parameters_values"], true);
				}
			}
			
			
			if( ! DP_User::isAdmin() )
			{
				if( (int)$print_doc["parameters_values"]["button_visible_for_user"] != 1 )
				{
					continue;
				}
			}
			
			if((int) $print_doc["parameters_values"]["button_visible_for_pages"] === 1)
			{
				if( $print_docs_buttons != "" )
				{
					$print_docs_buttons = $print_docs_buttons." ";
				}
				if($print_doc['name'] == 'user_cart'){
					$print_docs_buttons = $print_docs_buttons."<a class=\"btn btn-ar btn-primary\" href=\"/content/shop/print_docs/service/print.php?doc_name=".$print_doc["name"]."&new=1&csrf_guard_key=".$user_session["csrf_guard_key"]."\" target=\"_blank\"><i class=\"fa fa-print\"></i>Печать корзины</a> ";
					continue;
				}
				$print_docs_buttons = $print_docs_buttons."<a class=\"btn btn-ar btn-primary\" href=\"/content/shop/print_docs/service/print.php?doc_name=".$print_doc["name"]."&csrf_guard_key=".$user_session["csrf_guard_key"]."\" target=\"_blank\"><i class=\"fa fa-print\"></i> ".$print_doc["caption"]."</a>";
			}
		}
		if( $print_docs_buttons != "" )
		{
			if( 1 )
			{
			?>
			if( sum_total_num > 0 )
			{
				cart_html += '<div><?php echo $print_docs_buttons; ?></div>';
			}
			<?php
			}
		}
		?>
		
		
        cart_html += '<div style="padding-top: 10px; text-align: right; font-size: 18px; font-weight: bold;"><span style="font-size: 14px; font-weight: normal;">Итого:</span> '+sum_total+'</div>';
        
        
        <?php
        //В зависимости от того, зарегистрирован ли пользователь - указываем ссылку для кнопки "Оформить заказ"
        if($user_id > 0)
        {
            $order_link = "/shop/checkout/how_get";//Сразу на страницу выбора способа получения
        }
        else
        {
            $order_link = "/shop/checkout/login_offer";//Предложить авторизацию
        }
        ?>
        
				cart_html += '<div style="text-align: right;">';

				if(old_cart_items) {
						cart_html += '<?= $order_update_html ;?>';
				}	

		if( sum_total_num > 0 )
		{
			cart_html += '<a style="margin-left:20px;" class="btn btn-ar btn-primary" href="<?php echo $order_link; ?>">Оформить заказ</a>';
		}
		cart_html += '</div>';
		
        document.getElementById("cart_area").innerHTML = cart_html;
    }
    // --------------------------------------------------------------------------------------
	var ajax_flag = false;
    // --------------------------------------------------------------------------------------
    //Функция добавления требуемого количества
    function plusCountNeed(cart_record_id)
    {
		if(ajax_flag){
			return;
		}
		ajax_flag = true;
        //Текущее количество
        var current_count_need = parseInt(document.getElementById("count_need_"+cart_record_id).value);
		
		var min_order = 1;
		for(var i=0; i < cart_records.length; i++)
        {
			if(cart_records[i]['id'] == cart_record_id){
				min_order = cart_records[i]['min_order'];
			}
		}
        //Объект для запроса
        var request_object = new Object;
        request_object.id = cart_record_id;
        request_object.count_need = parseInt(current_count_need) + parseInt(min_order);
        
        
        //Увеличиваем наличие на сервере и только после этого отображаем
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос синхронный
            url: "/content/shop/order_process/ajax_change_count_need.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
				//console.log(answer);
				
                if(answer.status == true)
                {
                    cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                    calculateSums();//Пересчитываем суммы по товарам и итого
                    refreshCartArea();//Отображаем товары в корзине
                }
                else
                {
                    if(answer.code == "not_enough")
                    {
                        alert("Превышено наличие на складе");
                    }
                    else
                    {
                        alert("Ошибка добавления количества");
                    }
                }
				ajax_flag = false;
            }
        });
    }
    // --------------------------------------------------------------------------------------
    //Функция вычитания требуемого количества
    function minusCountNeed(cart_record_id)
    {
		if(ajax_flag){
			return;
		}
		ajax_flag = true;
        //Текущее количество
        var current_count_need = parseInt(document.getElementById("count_need_"+cart_record_id).value);
		
		var min_order = 1;
		for(var i=0; i < cart_records.length; i++)
        {
			if(cart_records[i]['id'] == cart_record_id){
				min_order = cart_records[i]['min_order'];
			}
		}
        //Проверка допустимости
        if( parseInt(current_count_need-1) > 0 )
        {
            //Объект для запроса
            var request_object = new Object;
            request_object.id = cart_record_id;
            request_object.count_need = parseInt(current_count_need) - parseInt(min_order);
            
            //Уменьшаем наличие на сервере и только после этого отображаем
            jQuery.ajax({
                type: "POST",
                async: true, //Запрос синхронный
                url: "/content/shop/order_process/ajax_change_count_need.php",
                dataType: "json",//Тип возвращаемого значения
                data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer)
                {
                    if(answer.status == true)
                    {
                        cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                        calculateSums();//Пересчитываем суммы по товарам и итого
                        refreshCartArea();//Отображаем товары в корзине
                    }
                    else
                    {
                        alert("Ошибка уменьшения количества");
                    }
					ajax_flag = false;
                }
            });
        }else{
			ajax_flag = false;
		}
    }
    // --------------------------------------------------------------------------------------
	
	// Функция для форматирования даты (аналог handleDate из PHP)
	function handleDate(timestamp) {
		if (!timestamp || timestamp == -1) return null;
		
		const currentDate = new Date();
		currentDate.setHours(0, 0, 0, 0);
		
		const dateTime = new Date(timestamp * 1000); // Умножаем на 1000, т.к. JS использует миллисекунды
		
		const timeDiff = Math.floor((dateTime - currentDate) / (1000 * 60 * 60 * 24));
		
		const result = {
			date: '',
			time: false
		};
		
		if (timeDiff > 1) {
			result.date = ('0' + dateTime.getDate()).slice(-2) + '.' + ('0' + (dateTime.getMonth() + 1)).slice(-2);
		} else if (timeDiff == 1) {
			result.date = 'Завтра';
		} else if (timeDiff == 0) {
			result.date = 'Сегодня';
		}
		
		const h = dateTime.getHours();
		const m = dateTime.getMinutes();
		
		if (h > 0 || m > 0) {
			result.time = {
				h: ('0' + h).slice(-2),
				m: ('0' + m).slice(-2)
			};
		}
		
		return result;
	}

	// Функция для формирования строки доставки
	// Измените функцию formatDelivery:
	function formatDelivery(deliveryStart, deliveryEnd) {
		let delivery = '';
		
		const start = deliveryStart ? handleDate(deliveryStart) : null;
		const end = deliveryEnd ? handleDate(deliveryEnd) : null;
		
		if (start && end) {
			if (start.date == end.date) {
				delivery = start.date;
				if (start.time) {
					delivery += ' с <span class="hh" style="display: inline-block;">' + start.time.h + '</span><sup class="mm" style="display: inline-block;">' + start.time.m + '</sup>';
				}
				if (end.time) {
					delivery += ' до <span class="hh" style="display: inline-block;">' + end.time.h + '</span><sup class="mm" style="display: inline-block;">' + end.time.m + '</sup>';
				}
			} else {
				delivery = ' с ' + start.date;
				if (start.time) {
					delivery += ' <span class="hh" style="display: inline-block;">' + start.time.h + '</span><sup class="mm" style="display: inline-block;">' + start.time.m + '</sup>';
				}
				delivery += ' до ' + end.date;
				if (end.time) {
					delivery += ' <span class="hh" style="display: inline-block;">' + end.time.h + '</span><sup class="mm" style="display: inline-block;">' + end.time.m + '</sup>';
				}
			}
		} else if (end) {
			delivery = end.date;
			if (end.time) {
				delivery += ' до <span class="hh" style="display: inline-block;">' + end.time.h + '</span><sup class="mm" style="display: inline-block;">' + end.time.m + '</sup>';
			}
		}
		
		return delivery;
	}
	
    //Функция изменения количества при ручном вводе в поле
    function onKeyUpCountNeed(cart_record_id)
    {
		if(ajax_flag){
			return;
		}
		ajax_flag = true;
		
        //Текущее количество
        var current_count_need = parseInt(document.getElementById("count_need_"+cart_record_id).value);
        
        //Если введено допустимое значение
        if(current_count_need > 0)
        {
            //Объект для запроса
            var request_object = new Object;
            request_object.id = cart_record_id;
            request_object.count_need = current_count_need;
            
            //Уменьшаем наличие на сервере и только после этого отображаем
            jQuery.ajax({
                type: "POST",
                async: true, //Запрос синхронный
                url: "/content/shop/order_process/ajax_change_count_need.php",
                dataType: "json",//Тип возвращаемого значения
                data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer)
                {
                    if(answer.status == true)
                    {
                        cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                        calculateSums();//Пересчитываем суммы по товарам и итого
                        refreshCartArea();//Отображаем товары в корзине
                    }
                    else
                    {
                        if(answer.code == "not_enough")//Превышено наличие на складе - но мы зарезервировали максимально-доступное количество
                        {
                            cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                            calculateSums();//Пересчитываем суммы по товарам и итого
                            refreshCartArea();//Отображаем товары в корзине
                            alert("Превышено наличие на складе");
                        }
                        else
                        {
                            cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                            calculateSums();//Пересчитываем суммы по товарам и итого
                            refreshCartArea();//Отображаем товары в корзине
							alert("Ошибка изменения количества");
                        }
                    }
					ajax_flag = false;
                }
            });
        }
        else//Просто исправляем обратно
        {
            alert("Введено недопустимое значение");
            document.getElementById("count_need_"+cart_record_id).value = cart_records[getElementIndex(cart_record_id)].count_need;
			ajax_flag = false;
        }
    }
    // --------------------------------------------------------------------------------------
    //Удаление из Корзины
    function deleteRecord(cart_record_id)
    {
        //Объект для запроса
        var request_object = new Object;
		request_object.records_to_del = new Array();
		request_object.records_to_del.push(cart_record_id)
    
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/content/shop/order_process/ajax_delete_cart_record.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                console.log(answer);
                if(answer.status == true)
                {
                    //Удаляем элемет из массива
                    cart_records.splice(getElementIndex(answer.records_to_del[0]), 1);
                    calculateSums();//Пересчитываем суммы по товарам и итого
                    refreshCartArea();//Отображаем товары в корзине
                }
                else
                {
                    alert("Ошибка удаления");
                }
            }
        });
    }
    // --------------------------------------------------------------------------------------
    //Получить индекс из списка по ID записи
    function getElementIndex(cart_record_id)
    {
        //Сначала определяем индекс объекта в списке javascript
        for(var i=0; i < cart_records.length; i++)
        {
            if(cart_record_id == cart_records[i].id)
            {
                return i;
            }
        }
    }
    // --------------------------------------------------------------------------------------
    //Пересчитать суммы по продуктам
    function calculateSums()
    {
        sum_total = 0;//Обнуляем общую сумму
        
        for(var i=0; i < cart_records.length; i++)
        {
			cart_records[i].price_sum = cart_records[i].price*cart_records[i].count_need;
            
			if( cart_records[i].checked_for_order == 0 )
			{
				continue;
			}
			
            sum_total = sum_total + cart_records[i].price_sum;
        }
    }
    // --------------------------------------------------------------------------------------
    //Снять / отметить для заказа
	function check_for_order(cart_record_id)
	{
		//////////////////////////////////////////////////////////////////////// - Доработка id 34
		if(cart_records[getElementIndex(cart_record_id)].access == 0 || cart_records[getElementIndex(cart_record_id)].count_need == 0){
			calculateSums();//Пересчитываем суммы по товарам и итого
			refreshCartArea();//Отображаем товары в корзине
			return;
		}
		//////////////////////////////////////////////////////////////////////// - END Доработка id 34
		
		//Объект для запроса
        var request_object = new Object;
		request_object.records = new Array();
		request_object.records.push(cart_record_id);
		
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/content/shop/order_process/ajax_check_for_order.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                console.log(answer);
                if(answer.status == true)
                {
                    cart_records[getElementIndex(answer.records[0].cart_record_id)].checked_for_order = answer.records[0].checked_for_order;
					
					
                    calculateSums();//Пересчитываем суммы по товарам и итого
                    refreshCartArea();//Отображаем товары в корзине
                }
                else
                {
                    alert("Серверная ошибка");
                }
            }
        });
	}
	// --------------------------------------------------------------------------------------
	//Обработка переключения Выделить все / Снять все
    function on_check_uncheck_all()
    {
        var state = document.getElementById("check_uncheck_all").checked;
        
		var request_object = new Object;
		request_object.records = new Array();
		
		for(var i=0; i < cart_records.length; i++)
        {
			if( cart_records[i].checked_for_order != state )
			{
				//////////////////////////////////////////////////////////////////////// - Доработка id 34
				if(cart_records[i].access == 0){
					continue;
				}
				if(cart_records[i].count_need == 0){
					continue;
				}
				//////////////////////////////////////////////////////////////////////// - END Доработка id 34
				
				request_object.records.push(cart_records[i].id);
			}
        }
		
        if(request_object.records.length > 0){
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/content/shop/order_process/ajax_check_for_order.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					if(answer.status == true)
					{
						for(var i=0; i < answer.records.length; i++)
						{
							cart_records[getElementIndex(answer.records[i].cart_record_id)].checked_for_order = answer.records[i].checked_for_order;
						}
						
						calculateSums();//Пересчитываем суммы по товарам и итого
						refreshCartArea();//Отображаем товары в корзине
					}
					else
					{
						alert("Серверная ошибка");
					}
				}
			});
		}else{
			calculateSums();//Пересчитываем суммы по товарам и итого
			refreshCartArea();//Отображаем товары в корзине
		}
    }//~function on_check_uncheck_all()
	// --------------------------------------------------------------------------------------
	// Функция отделяет тысячные знаки пробелом. Используется для отображения цены
	function digit(str){
		var parts = (str + '').split('.'),
			main = parts[0],
			len = main.length,
			output = '',
			i = len - 1;
		
		while(i >= 0) {
			output = main.charAt(i) + output;
			if ((len - i) % 3 === 0 && i > 0) {
				output = ' ' + output;
			}
			--i;
		}

		if (parts.length > 1) {
			output += '.' + parts[1];
		}
		return output;
	}
	// --------------------------------------------------------------------------------------
	// Функция удаляет из корзине товар и переносит пользователя в проценку
	function RefreschRecord(id){
		var url = cart_records[getElementIndex(id)].url_refresh;
		deleteRecord(id);
		window.location = url;
	}
	// --------------------------------------------------------------------------------------
    var cart_records = new Array();
    var sum_total = 0;//Сумма заказа
    <?php

	$cart = array();// Массив объектов содержимого корзины

	//Формируем объекты содержимого корзины
    for($i=0; $i < count($cart_records); $i++)
    {
        $cart_object = array();
    
		$cart_record_query = $db_link->prepare('SELECT * FROM `shop_carts` WHERE `id` = ?;');
        $cart_record_query->execute( array($cart_records[$i]) );
        $cart_record = $cart_record_query->fetch();
		
		$select_storage_caption = $db_link->prepare("SELECT `short_name` FROM `shop_storages` WHERE `id` = ?;");
		$select_storage_caption->execute([$cart_record["t2_storage_id"]]);
		
		$res_select_storage_caption = $select_storage_caption->fetch(PDO::FETCH_ASSOC);
		
        //Заполняем поля, которые не зависят от типа продукта:
        $cart_object["id"] = $cart_records[$i];
        $cart_object["price"] = $cart_record["price"];
        $cart_object["product_type"] = $cart_record["product_type"];
        $cart_object["count_need"] = $cart_record["count_need"];
		$cart_object["checked_for_order"] = $cart_record["checked_for_order"];
		$cart_object["is_update"] = $cart_record["is_update"];
		$cart_object["t2_exist"] = $cart_record["t2_exist"];
		// В коде, где формируется $cart_object
		$cart_object["delivery"] = ""; // По умолчанию пустая строка

		$cart_object["delivery_start"] = "";
		$cart_object["delivery_end"] = "";

		// Пробуем получить delivery из t2_product_json
		if (!empty($cart_record["t2_product_json"])) {
			$product_json = json_decode($cart_record["t2_product_json"], true);
			
			if ($product_json !== null && isset($product_json['rest_params'])) {
				// Передаем timestamp'ы
				$cart_object["delivery_start"] = $product_json['rest_params']['delivery_start'] ?? "";
				$cart_object["delivery_end"] = $product_json['rest_params']['delivery_end'] ?? "";
			}
		}
			
		$cart_object["update_price"] = $cart_record["update_price"];
		$cart_object["storage_caption"] = $res_select_storage_caption["short_name"];
		
		if($cart_object["is_update"] == 2 || $cart_object["is_update"] == 3) {
			if($cart_object["count_need"] > $cart_object["t2_exist"]) {
				$cart_object["count_need"] = $cart_object["t2_exist"];
				$cart_object["checked_for_order"] = 0;
				$cart_count_need_query = $db_link->prepare('UPDATE `shop_carts` SET `count_need` = ?, `checked_for_order` = 0 WHERE `id` = ?;');
				$cart_count_need_query->execute( array($cart_object["count_need"], $cart_record["id"]) );
				$cart_count_need_query->fetch();
			} else if($cart_object["count_need"] == 0 && $cart_object["t2_exist"] > 0) {
				$cart_object["count_need"] = 1;
				$cart_object["checked_for_order"] = 0;
				$cart_count_need_query = $db_link->prepare('UPDATE `shop_carts` SET `count_need` = ?, `checked_for_order` = 0 WHERE `id` = ?;');
				$cart_count_need_query->execute( array($cart_object["count_need"], $cart_record["id"]) );
				$cart_count_need_query->fetch();
			}
		}
		
		//Срок поставки
		if($cart_record["t2_time_to_exe"] < $cart_record["t2_time_to_exe_guaranteed"]){
			$cart_object["time_to_exe"] = $cart_record["t2_time_to_exe"] .' - '. $cart_record["t2_time_to_exe_guaranteed"];
		}else{
			$cart_object["time_to_exe"] = $cart_record["t2_time_to_exe"];
		}
		if($cart_object["time_to_exe"] == 0){
			$cart_object["time_to_exe"] = 'На складе';
		}else{
			$cart_object["time_to_exe"] = $cart_object["time_to_exe"] . ' дн.';
		}
		
		//Минимальная партия
		$cart_object["min_order"] = (int) $cart_record["t2_min_order"];
		if($cart_object["min_order"] <= 0){
			$cart_object["min_order"] = 1;
		}
        
		//Получаем Наименование
		$product_name = trim($cart_record["t2_name"]);
		$product_name = str_replace( array("'", '"', "\n", "\t", "\r", "\\"), "", $product_name);
		if(!empty($product_name)){
			$cart_object["name"] = $product_name;
		}
		
		$cart_object["manufacturer"] = $cart_record["t2_manufacturer"];
		$cart_object["article"] = $cart_record["t2_article"];
		
		//Поля зависящие от типа продукта
		$cart_object["image_src"] = '';
		$cart_object["product_id"] = '';
		$cart_object["url_refresh"] = '';
		
		//////////////////////////////////////////////////////////////////////// - Доработка id 34
		if(time() < ((int)$cart_record["time"] + (3600 * 12))){
			$cart_object["access"] = 1;
		}else{
			$cart_object["access"] = 0;
			//Снимаем выделение позиции
			$product_query = $db_link->prepare("UPDATE `shop_carts` SET `checked_for_order` = 0 WHERE `id` = ?;");
			$product_query->execute( array($cart_record["id"]) );
			$cart_object["checked_for_order"] = 0;
		}
		//////////////////////////////////////////////////////////////////////// - END Доработка id 34
		
        //Заполняем поля объекта корзины в зависимости от типа продукта (1 - каталожный, 2 - docpart)
        switch($cart_record["product_type"])
        {
            case 1:
                $product_id = (int) $cart_record["product_id"];
				
				$cart_object["product_id"] = $product_id;
				
				//////////////////////////////////////////////////////////////////////// - Доработка id 34
				
					$product_query = $db_link->prepare("SELECT *, CAST((SELECT `price` FROM `shop_carts_details` WHERE `cart_record_id` = ?) AS decimal(20,2)) AS 'price_not_markup' FROM `shop_storages_data` WHERE `id` = (SELECT `storage_record_id` FROM `shop_carts_details` WHERE `cart_record_id` = ?) AND `product_id` = ?;");
					$product_query->execute( array($cart_record["id"], $cart_record["id"], $product_id) );
					$record = $product_query->fetch();
					
					// Проверяем наличие складской записи
					if(empty($record) || ($record['price'] != $record["price_not_markup"])){
						$cart_object["access"] = 0;
						
						//Снимаем выделение позиции
						$product_query = $db_link->prepare("UPDATE `shop_carts` SET `checked_for_order` = 0 WHERE `id` = ?;");
						$product_query->execute( array($cart_record["id"]) );
						$cart_object["checked_for_order"] = 0;
					}
				
				//////////////////////////////////////////////////////////////////////// - END Доработка id 34
				
                //Получаем id категории и alias продукта
                $product_query = $db_link->prepare("SELECT `category_id`, `caption`, `alias` FROM `shop_catalogue_products` WHERE `id` = $product_id;");
				$product_query->execute();
                $product_record = $product_query->fetch();
				$category_id = (int) $product_record['category_id'];
				$product_alias = trim($product_record['alias']);
				
                //Получаем изображение
				$image_query = $db_link->prepare("SELECT `id`, `file_name` FROM `shop_products_images` WHERE `product_id` = ? ORDER BY `id` ASC LIMIT 1;");
				$image_query->execute( array($product_id) );
                $image_record = $image_query->fetch();
				$image_record["file_name"] = trim($image_record["file_name"]);
				if( !empty($image_record["file_name"]) )
                {
                    if( strpos($image_record["file_name"], "/") )
					{
						$cart_object["image_src"] = $image_record["file_name"];
					}else{
						if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/products_images/".$image_record["file_name"])){
							$cart_object["image_src"] = '/content/shop/catalogue/watermark.php?image_id='.$image_record["id"];
						}
					}
                }
            
				//Получаем ссылку на продукт для переоценки
				$product_category_query = $db_link->prepare("SELECT `url` FROM `shop_catalogue_categories` WHERE `id` = $category_id");
				$product_category_query->execute();
                $product_category_record = $product_category_query->fetch();
				$product_category_url = trim($product_category_record['url']);
				
				if($DP_Config->product_url == 'id'){
					$cart_object["url_refresh"] = '/'.$product_category_url.'/'.$product_id;
				}else{
					$cart_object["url_refresh"] = '/'.$product_category_url.'/'.$product_alias;
				}
				
                break;
            case 2:
				
                //Получаем изображение
				$product_image = NULL;
				if( isset($cart_record["image"]) )
				{
					$product_image = trim($cart_record["image"]);
				}
				if( !empty($product_image) )
                {
                    if( strpos($product_image, "/") )
					{
						$cart_object["image_src"] = $product_image;
					}else{
						if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/products_images/".$product_image)){
							$cart_object["image_src"] = "/content/files/images/products_images/".$product_image;
						}
					}
                }
				
				//Получаем ссылку на продукт для переоценки
				$cart_object["url_refresh"] = "/parts/".htmlentities($cart_object["manufacturer"])."/".$cart_object["article"];
				
                break;
        }
		
		$cart[] = $cart_object;
		
    }//for($i) - формируем объект корзины
    ?>
	cart_records = JSON.parse('<?php echo json_encode($cart); ?>');
    calculateSums();//Пересчитываем суммы по товарам и итого
    refreshCartArea();//Отображаем товары в корзине
		jQuery(function () {
			jQuery('[data-toggle="tooltip"]').tooltip({
					placement: 'top',
					html: true
			})
		})
    </script>
    <?php
}//~else - в корзине есть записи
?>














<!---------------------------------------------- ГАРАЖ ---------------------------------------------->
<style>
body {
   padding: 0 !important;
}
#my_modal_box_for_garage .modal {
	z-index:99999999;
	padding-right: 0px !important;
}
#my_modal_box_for_garage .modal-header {
  text-align: center;
  font-size: 14px;
  background: #fff;
  color:#000;
  border-bottom: 1px solid #999;
}
#my_modal_box_for_garage .close{
	color:#000;
}
#my_modal_box_for_garage .modal-footer {
	border-top: 1px solid #999;
	text-align: center;
}
#my_modal_box_for_garage .modal-dialog {
    max-width: 601px;
    margin: 30px auto;
    width: 100%;
}
</style>
<div id="my_modal_box_for_garage">
  <div class="modal fade" id="modal_garage" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="padding:10px 15px;">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <b>Выберите автомобиль, в блокнот которого будет добавлена позиция</b>
        </div>
        <div class="modal-body" style="color:#000; padding:40px 50px;">
			<div id="add_bloknot_content">
				<?php
				$query = $db_link->prepare('SELECT *, (SELECT `caption` FROM `shop_docpart_cars` WHERE `id` = `shop_docpart_garage`.`mark_id`) AS `mark` FROM `shop_docpart_garage` WHERE `user_id` = ?;');
				$query->execute( array($user_id) );
				echo '<select id="garage_auto" class="form-control">';
				echo '<option value="0">Общий блокнот</option>';
				while($car = $query->fetch())
				{
					echo '<option value="'.$car['id'].'">'. $car["mark"]." ".$car["model"]." ".$car["year"]." года - ". $car["caption"] .'</option>';
				}
				echo '</select>';
				?>
			</div>
			<div id="add_bloknot_msg"></div>
        </div>
        <div id="add_bloknot_btn" class="modal-footer">
			<a style="margin-bottom: 5px;" class="btn btn-ar btn-primary" onclick="add_bloknot();">Добавить в блокнот</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
var id_in_bloknot = -1;// id позиции корзины которую будем добавлять в блокнот
// Функция отображения блока добавления позиции в блокнот
function show_add_bloknot(id){
	<?php
	if(empty($user_id)){
	?>
	alert('Для добавления позиций в блокнот необходимо авторизоваться на сайте');
	return;
	<?php
	}
	?>
	id_in_bloknot = id;
	$("#modal_garage").modal();
}
// Функция добавления позиции в блокнот гаража
function add_bloknot(){
	if(id_in_bloknot >= 0){
		var n = document.getElementById("garage_auto").options.selectedIndex;
		var garage_id = document.getElementById("garage_auto").options[n].value;
		
		var Products = cart_records[getElementIndex(id_in_bloknot)];
		
		var request_object = new Object;
		request_object.manufacturer = encodeURIComponent(Products.manufacturer);
		request_object.article = encodeURIComponent(Products.article);
		request_object.name = encodeURIComponent(Products.name);
		request_object.exist = encodeURIComponent(Products.count_need);
		request_object.price = encodeURIComponent(Products.price);
		
		jQuery.ajax({
			type: "POST",
			async: true,
			url: "/content/shop/docpart/garage/ajax_add_to_notepad.php",
			dataType: "json",
			data: "garage="+garage_id+"&product="+JSON.stringify(request_object)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
			success: function(answer)
			{
				var icon = '<i style="font-size: 30px; color: green;" class="fa fa-check"></i> ';
				if(answer.status != true){
					icon = '<i style="font-size: 30px; color: red;" class="fa fa-times"></i> ';
				}
				
				document.getElementById('add_bloknot_content').style.display = "none";
				document.getElementById('add_bloknot_btn').style.display = "none";
				document.getElementById('add_bloknot_msg').innerHTML = '<table><tr><td style="padding-right:5px;">'+icon+'</td><td>'+answer.message+'</td></tr></table>';
				
				setTimeout(function(){
					$("#modal_garage").modal('hide');
					
				}, 2000);
				
				setTimeout(function(){
					document.getElementById('add_bloknot_content').style.display = "block";
					document.getElementById('add_bloknot_btn').style.display = "block";
					document.getElementById('add_bloknot_msg').innerHTML = '';
				}, 2300);
			},
			error: function (e, ajaxOptions, thrownError){
				alert('Ошибка');
			}
		});
	}
}

function update_order(cartIems = [], userId = 0) {
	    
	    let request_object = new Object;
	    
	    document.getElementById("confirm_loader").style.display = 'inline-block';
	    jQuery('#update_items_btn').addClass('disabled');

	    
	    setTimeout(function() {
               if(userId !== 0 && cartIems.length > 0) {
	        
        	        cart_items = cartIems.filter(item => item.access == 0);
        	        
									if(cart_items.length > 0) {

	                  jQuery.ajax({
	                		type: "POST",
	                		async: true, //Запрос синхронный
	                		url: "/content/cron/update_cart_price.php",
	                		dataType: "json",//Тип возвращаемого значения
	                		data: "cart_items="+encodeURIComponent(JSON.stringify(cart_items)),
	                        complete:function(){
	                            document.getElementById("confirm_loader").style.display = 'none';
	                            jQuery('#update_items_btn').removeClass('disabled');
	                        },
	                		success: function(answer)
	                		{
	                			if(answer.status == true)
	                			{
	            			        setTimeout(function() {
	                                    location.reload();
	                                }, 2000);
	                			}
	                			else
	                			{
	                			    console.log('answer', answer);
	                                alert("Серверная ошибка. Обратитесь к вашему менеджеру.");
	                			}
	                		}
	                	});

									}
									else
									{

										document.getElementById("confirm_loader").style.display = 'none';
	                  jQuery('#update_items_btn').removeClass('disabled');
									}
        	    
        	    }
        }, 2000)
        
    }
		
</script>
<!-------------------------------------------- End ГАРАЖ -------------------------------------------->