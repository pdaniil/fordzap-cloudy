<?php
/**
 * Страница для вывода всех позиций всех заказов
*/
defined('_ASTEXE_') or die('No access');


function str_replace_once($search, $replace, $text) 
{ 
   $pos = strpos($text, $search); 
   return $pos!==false ? substr_replace($text, $replace, $pos, strlen($search)) : $text; 
}

// формируем пагинацию
// $all 		= количество постов в категории (определяем количество постов в базе данных)
// $lim 		= количество постов, размещаемых на одной странице
// $prev 		= количество отображаемых ссылок до и после номера текущей страницы
// $curr_link 	= номер текущей страницы (получаем из URL)
// $curr_css 	= css-стиль для ссылки на "текущую (активную)" страницу
// $link 		= часть адреса, используемый для формирования линков на другие страницы
function pagination($all, $lim, $prev, $curr_link, $curr_css, $link)
{
    $html = '';
	// осуществляем проверку, чтобы выводимые первая и последняя страницы
    // не вышли за границы нумерации
    $first = $curr_link - $prev;
    if ($first < 1) $first = 0;
		$last = $curr_link + $prev;
		
		$count_pages = (int)($all / $lim);//Количество страниц
		if($all%$lim)//Если остались еще элементы
		{
			$count_pages++;
		}

		$count_pages = $count_pages - 1;

    if ($last > $count_pages) $last = $count_pages;
 
    // начало вывода нумерации
    // выводим первую страницу
    $y = 0;
    if ($first > 0) $html .= "<li class='paginate_button'><a onclick='goToPage({$y})'>0</a></li>";
    // Если текущая страница далеко от 1-й (>10), то часть предыдущих страниц
    // скрываем троеточием
    // Если текущая страница имеет номер до 10, то выводим все номера
    // перед заданным диапазоном без скрытия
	// $prev
    $y = $first - 1;
    if ($first > $prev) {
        $html .= "<li class='paginate_button'><a onclick='goToPage({$y})'>...</a></li>";
    } else {
        for($i = 2;$i < $first;$i++){
            $html .=  "<li class='paginate_button'><a onclick='goToPage({$y})'>$i</a></li>";
        }
    }
    // отображаем заданный диапазон: текущая страница +-$prev
    for($i = $first;$i < $last + 1;$i++){
        // если выводится текущая страница, то ей назначается особый стиль css
        if($i == $curr_link) {
			$html .= "<li class='paginate_button ".$curr_css."'><a>". $i ."</a></li>";
        } else {
            $alink = "<li class='paginate_button'><a onclick='goToPage(";
            if($i != 0) $alink .= "{$i}";
            $alink .= ")'>$i</a></li>";
            $html .= $alink;
        }
    }
    $y = $last + 1;
    // часть страниц скрываем троеточием
    if ($last < $count_pages && $count_pages - $last > 2) $html .=  "<li class='paginate_button'><a onclick='goToPage({$y})'>...</a></li>";
    // выводим последнюю страницу
    $e = $count_pages;
    if ($last < $count_pages) $html .=  "<li class='paginate_button'><a onclick='goToPage({$e})'>$e</a></li>";
	
	return $html;
}


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$manager_id = DP_User::getAdminId();//ID менежера, который отображает эту страницу



//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php")
?>

<?php
if(!empty($_POST["action"]))
{
    
}
else//Действий нет - выводим страницу
{
	//Для работы с пользователем
	require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
	$user_session = DP_User::getAdminSession();
	
    ?>
	
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/content/users/statistics/assets/modal.css"><!--//Статистика-->
	
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
	
	
	
	
	
	
	
	
	
	<script>
    // ------------------------------------------------------------------------------------------------
    //Установка куки сортировки позиций заказов
    function sortOrdersItems(field)
    {
        var asc_desc = "asc";//Направление по умолчанию
        
        //Берем из куки текущий вариант сортировки
        var current_sort_cookie = getCookie("orders_items_sort");
        if(current_sort_cookie != undefined)
        {
            current_sort_cookie = JSON.parse(getCookie("orders_items_sort"));
            //Если поле это же - обращаем направление
            if(current_sort_cookie.field == field)
            {
                if(current_sort_cookie.asc_desc == "asc")
                {
                    asc_desc = "desc";
                }
                else
                {
                    asc_desc = "asc";
                }
            }
        }
        
        
        var orders_items_sort = new Object;
        orders_items_sort.field = field;//Поле, по которому сортировать
        orders_items_sort.asc_desc = asc_desc;//Направление сортировки
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_items_sort="+JSON.stringify(orders_items_sort)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        goToPage(0);
    }
    // ------------------------------------------------------------------------------------------------
    // возвращает cookie с именем name, если есть, если нет, то undefined
    function getCookie(name) 
    {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }
    // ------------------------------------------------------------------------------------------------
    //Переход на другую страницу заказа
    function goToPage(need_page)
    {
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_items_need_page="+need_page+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items';
    }
    // ------------------------------------------------------------------------------------------------
	//Выделение цветом выбранного фильтра с мультиселектом
	function changeColorFilter(filter)
	{
		$('.ms-choice', $(filter)).css('background-color', '#b9fcab');
	}
    // ------------------------------------------------------------------------------------------------
    </script>
	
	
	
	
	
	
	
	
	
    
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Фильтр позиций
			</div>
			<div class="panel-body filter_panel">
				<?php
				$time_from = "";//1. Время с
				$time_to = "";//2. Время по
				$order_id = "";//3. ID заказа
				$order_status = "0";//4. Статус заказа
				$paid = -1;//5. Флаг - Заказ оплачен
				$customer = "";//6. Покупатель
				$customer_id = "";//6. Покупатель ID
				$order_item_status = "0";//7. Статус позиции
				$office_id = "0";//8. Офис обслуживания
				$product_name = "";//Подстрока наименования товара
				$article = "";//Подстрока article
				$manufacturer = "";//Подстрока manufacturer
				$viewed = -1;//Флаг "Заказ просмотрен"
				$storage_id = -1;//ID склада
				$phone = "";//Телефон клиента
				
				//Получаем текущие значения фильтра:
				$orders_items_filter = NULL;
				if( isset($_COOKIE["orders_items_filter"]) )
				{
					$orders_items_filter = $_COOKIE["orders_items_filter"];
				}
				if($orders_items_filter != NULL)
				{
					$orders_items_filter = json_decode($orders_items_filter, true);
					$time_from = $orders_items_filter["time_from"];
					$time_to = $orders_items_filter["time_to"];
					$order_id = $orders_items_filter["order_id"];
					$order_status = $orders_items_filter["order_status"];
					$paid = $orders_items_filter["paid"];
					$customer = $orders_items_filter["customer"];
					$customer_id = $orders_items_filter["customer_id"];
					$order_item_status = $orders_items_filter["order_item_status"];
					$office_id = $orders_items_filter["office_id"];
					$product_name = $orders_items_filter["product_name"];
					$article = $orders_items_filter["article"];
					$manufacturer = $orders_items_filter["manufacturer"];
					$viewed = $orders_items_filter["viewed"];
					$storage_id = $orders_items_filter["storage_id"];
					$phone = $orders_items_filter["phone"];
				}
				?>
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Дата с
						</label>
						<div class="col-lg-6">
							<div style="position:relative;height:34px;">
								<input style="position:absolute; z-index:2; opacity:0;" type="text"  id="time_from" value="<?php echo $time_from; ?>" class="form-control" />
								<input style="position:absolute; z-index:1; <?=(!empty($time_from))?'background:#b9fcab;':'';?>" type="text" id="time_from_show" class="form-control" />
								<script>
								//Инициализируем datetimepicker
								jQuery("#time_from").datetimepicker({
									lang:"ru",
									closeOnDateSelect:true,
									closeOnTimeSelect:false,
									dayOfWeekStart:1,
									format:'unixtime',
									onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
									{
										var time_string = "";
										var date_ob = new Date(current_time);
										time_string += date_ob.getDate()+".";
										time_string += (date_ob.getMonth() + 1)+".";
										time_string += date_ob.getFullYear()+" ";
										time_string += date_ob.getHours()+":"+date_ob.getMinutes();
										document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
									}
									<?php
									if($time_from != "")
									{
										?>
										,
										onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
										{
											var time_string = "";
											var date_ob = new Date(current_time);
											time_string += date_ob.getDate()+".";
											time_string += (date_ob.getMonth() + 1)+".";
											time_string += date_ob.getFullYear()+" ";
											time_string += date_ob.getHours()+":"+date_ob.getMinutes();
											document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
										}
										<?php
									}
									?>
								});
								</script>
							</div>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Дата по
						</label>
						<div class="col-lg-6">
							<div style="position:relative;height:34px;">
								<input style="position:absolute; z-index:2; opacity:0;" type="text"  id="time_to" value="<?php echo $time_to; ?>" class="form-control" />
								<input style="position:absolute; z-index:1; <?=(!empty($time_to))?'background:#b9fcab;':'';?>" type="text" id="time_to_show" class="form-control" />
								<script>
								//Инициализируем datetimepicker
								jQuery("#time_to").datetimepicker({
									lang:"ru",
									closeOnDateSelect:true,
									closeOnTimeSelect:false,
									dayOfWeekStart:1,
									format:'unixtime',
									onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
									{
										var time_string = "";
										var date_ob = new Date(current_time);
										time_string += date_ob.getDate()+".";
										time_string += (date_ob.getMonth() + 1)+".";
										time_string += date_ob.getFullYear()+" ";
										time_string += date_ob.getHours()+":"+date_ob.getMinutes();
										document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
									}
									<?php
									if($time_to != "")
									{
										?>
										,
										onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
										{
											var time_string = "";
											var date_ob = new Date(current_time);
											time_string += date_ob.getDate()+".";
											time_string += (date_ob.getMonth() + 1)+".";
											time_string += date_ob.getFullYear()+" ";
											time_string += date_ob.getHours()+":"+date_ob.getMinutes();
											document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
										}
										<?php
									}
									?>
								});
								</script>
							</div>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер заказа <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('В одном заказе может быть несколько позиций. Номер позиции указан в таблице слева, а номер заказа справа. Этот фильтр Вы можете использовать, чтобы отобразить позиции нужного заказа.');"><i class="fa fa-info"></i></button>
						</label>
						<div class="col-lg-6">
							<input <?=(!empty($order_id))?'style="background:#b9fcab;"':'';?> type="text"  id="order_id" value="<?php echo $order_id; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Оплачен
						</label>
						<div class="col-lg-6" id="paid_div">
							<select class="form-control" multiple="multiple" id="paid">
								<option value="1">Оплачен полностью</option>
								<option value="2">Оплачен частично</option>
								<option value="0">Не оплачен</option>
							</select>
							<script>
								$('#paid').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_items_filter") != undefined && JSON.parse(getCookie("orders_items_filter")).paid != -1){
									$('#paid').multipleSelect('setSelects', JSON.parse(getCookie("orders_items_filter")).paid);
									changeColorFilter('#paid_div');
								}
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Статус заказа
						</label>
						<div class="col-lg-6" id="order_status_div">
							<select multiple="multiple" id="order_status">
								<?php
								foreach($orders_statuses as $status_id => $status_data)
								{
								?>
									<option value="<?php echo $status_id; ?>"><?php echo $status_data["name"]; ?></option>
								<?php
								}
								?>
							</select>
							<script>
								$('#order_status').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_items_filter") != undefined && JSON.parse(getCookie("orders_items_filter")).order_status != 0){
									$('#order_status').multipleSelect('setSelects', JSON.parse(getCookie("orders_items_filter")).order_status);
									changeColorFilter('#order_status_div');
								}
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Статус позиции
						</label>
						<div class="col-lg-6" id="order_item_status_div">
							<select multiple="multiple" id="order_item_status">
								<?php
								foreach($orders_items_statuses as $status_id => $status_data)
								{
								?>
									<option value="<?php echo $status_id; ?>"><?php echo $status_data["name"]; ?></option>
								<?php
								}
								?>
							</select>
							<script>
								$('#order_item_status').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_items_filter") != undefined && JSON.parse(getCookie("orders_items_filter")).order_item_status != 0){
									$('#order_item_status').multipleSelect('setSelects', JSON.parse(getCookie("orders_items_filter")).order_item_status);
									changeColorFilter('#order_item_status_div');
								}
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Клиент ID
						</label>
						<div class="col-lg-6">
							<input <?=(!empty($customer_id))?'style="background:#b9fcab;"':'';?> type="text"  id="customer_id" value="<?php echo $customer_id; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							
							<?php
							//Для подсказки для поля Клиент
							$fields_for_customer_search = "ID, E-mail, Телефон";
							//Дополнительно сюда выводим перечень полей профиля пользователя:
							$users_profile_fields_query = $db_link->prepare("SELECT `caption` FROM `reg_fields` WHERE `to_users_table` = 1;");
							$users_profile_fields_query->execute();
							while( $users_profile_field = $users_profile_fields_query->fetch() )
							{
								$fields_for_customer_search = $fields_for_customer_search.", ".$users_profile_field["caption"];
							}
							?>
						
							Клиент <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('В это поле можно ввести одно из полей данных покупателя: <?php echo $fields_for_customer_search; ?>. Допустим поиск по частичному совпадению');"><i class="fa fa-info"></i></button>
						</label>
						<div class="col-lg-6">
							<input <?=(!empty($customer))?'style="background:#b9fcab;"':'';?> type="text"  id="customer" value="<?php echo $customer; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Офис
						</label>
						<div class="col-lg-6" id="office_id_div">
							<select multiple="multiple" id="office_id">
								<?php
								foreach($offices_list as $office_id_key => $office_name)
								{
									?>
									<option value="<?php echo $office_id_key; ?>"><?php echo $office_name; ?></option>
									<?php
								}
								?>
							</select>
							<script>
								$('#office_id').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_items_filter") != undefined && JSON.parse(getCookie("orders_items_filter")).office_id != 0){
									$('#office_id').multipleSelect('setSelects', JSON.parse(getCookie("orders_items_filter")).office_id);
									changeColorFilter('#office_id_div');
								}
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Производитель
						</label>
						<div class="col-lg-6">
							<input <?=(!empty($manufacturer))?'style="background:#b9fcab;"':'';?> type="text" id="manufacturer" value="<?php echo $manufacturer; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Артикул
						</label>
						<div class="col-lg-6">
							<input <?=(!empty($article))?'style="background:#b9fcab;"':'';?> type="text" id="article" value="<?php echo $article; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Наименование
						</label>
						<div class="col-lg-6">
							<input <?=(!empty($product_name))?'style="background:#b9fcab;"':'';?> type="text" id="product_name" value="<?php echo $product_name; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Заказ просмотрен
						</label>
						<div class="col-lg-6" id="viewed_div">
							<select multiple="multiple" id="viewed">
								<option value="1">Просмотрен</option>
								<option value="0">Не просмотрен</option>
							</select>
							<script>
								$('#viewed').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_items_filter") != undefined && JSON.parse(getCookie("orders_items_filter")).viewed != -1){
									$('#viewed').multipleSelect('setSelects', JSON.parse(getCookie("orders_items_filter")).viewed);
									changeColorFilter('#viewed_div');
								}
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Поставщик <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Поиск позиций по складам (или поставщикам). Для выбора доступны только те склады (поставщики), которые не были удалены. Если поставщик какой-либо позиции был удален, то найти ее можно только с выставленным значением &#34;Все&#34;');"><i class="fa fa-info"></i></button>
						</label>
						<div class="col-lg-6" id="storage_id_div">
							<select multiple="multiple" id="storage_id">
								<?php
								$storages_query = $db_link->prepare("SELECT * FROM `shop_storages` ORDER BY `name`;");
								$storages_query->execute();
								while ($storage = $storages_query->fetch())
								{
									if(empty($storage["short_name"])){
										?>
										<option value="<?php echo $storage["id"]; ?>"><?php echo $storage["name"]; ?> - id <?php echo $storage["id"]; ?></option>
										<?php
									}else{
										?>
										<option value="<?php echo $storage["id"]; ?>"><?php echo $storage["name"]; ?> - id <?php echo $storage["id"]; ?> - <?php echo $storage["short_name"]; ?></option>
										<?php
									}
								}
								?>
							</select>
							<script>
								$('#storage_id').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_items_filter") != undefined && JSON.parse(getCookie("orders_items_filter")).storage_id != -1){
									$('#storage_id').multipleSelect('setSelects', JSON.parse(getCookie("orders_items_filter")).storage_id);
									changeColorFilter('#storage_id_div');
								}
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Телефон
						</label>
						<div class="col-lg-6">
							<input <?=(!empty($phone))?'style="background:#b9fcab;"':'';?> type="text" id="phone" value="<?php echo str_replace(array("+7","+375","+380"),"",$phone); ?>" class="form-control" />
						</div>
					</div>
				</div>
				
			</div>
			<script>
                // Функция для сохранения позиции курсора
                function saveCursorPosition(element) {
                    if (element) {
                        const cursorPosition = element.selectionStart;
                        localStorage.setItem('focusedElementId', element.id);
                        localStorage.setItem('cursorPosition', cursorPosition);
                    }
                }

                // Отслеживаем все элементы input и сохраняем позицию курсора перед обновлением страницы
                $('.filter_panel .form-control').on('focus', function() {
                    $(this).on('keyup', function() {
                        saveCursorPosition(this);
                    });
                });

                var typingTimer;                // Таймер для отслеживания завершения ввода
                var doneTypingInterval = 1000;   // Интервал ожидания в миллисекундах (1 секунда)
                var lastInputElement = null;     // Переменная для хранения последнего элемента ввода
                var lastCursorPosition = 0;      // Переменная для хранения позиции курсора

                // Функция для выполнения поиска
                function doneTyping(input, element) {
                    // Проверяем, введено ли минимум 3 символа
                    if (input.length >= 3) {
                        filterOrdersItems(); // Вызов функции фильтрации

                        // Восстанавливаем фокус и позицию курсора после фильтрации
                        if (element) {
                            element.focus();
                            setCursorPosition(element, lastCursorPosition);
                        }
                    }
                }
                // Функция для восстановления позиции курсора
                function restoreCursorPosition() {
                    const focusedElementId = localStorage.getItem('focusedElementId');
                    const cursorPosition = localStorage.getItem('cursorPosition');

                    if (focusedElementId && cursorPosition) {
                        const element = document.getElementById(focusedElementId);
                        if (element) {
                            element.focus();
                            element.setSelectionRange(cursorPosition, cursorPosition);
                        }

                        // Очищаем сохраненные данные
                        localStorage.removeItem('focusedElementId');
                        localStorage.removeItem('cursorPosition');
                    }
                }

                // Восстанавливаем фокус и курсор после перезагрузки страницы
                $(document).ready(function() {
                    restoreCursorPosition();
                });

                // Функция для установки позиции курсора
                function setCursorPosition(element, position) {
                    if (element.setSelectionRange) {
                        element.setSelectionRange(position, position);
                    } else if (element.createTextRange) {
                        var range = element.createTextRange();
                        range.move('character', position);
                        range.select();
                    }
                }

                // Отслеживание ввода в полях с классом .form-control внутри .filter_panel
                $(".filter_panel .form-control").on('keyup input', function() {
                    clearTimeout(typingTimer);  // Очищаем предыдущий таймер

                    var inputValue = $(this).val(); // Получаем введенное значение
                    lastInputElement = this;  // Сохраняем текущий элемент ввода
                    lastCursorPosition = this.selectionStart;  // Сохраняем текущую позицию курсора

                    // Запускаем новый таймер для отслеживания завершения ввода
                    typingTimer = setTimeout(function() {
                        doneTyping(inputValue, lastInputElement);
                    }, doneTypingInterval);
                });



            </script>
			<div class="panel-footer">
				<div class="row">
					<div class="col-lg-12 float-e-margins">
						<button class="btn btn-success" type="button" onclick="filterOrdersItems();"><i class="fa fa-filter"></i> Отфильтровать</button>
						<button class="btn btn-primary" type="button" onclick="unsetFilterOrdersItems();"><i class="fa fa-square"></i> Снять фильтры</button>
						<button class="btn btn-info" type="button" onclick="itemsInProcess();"><i class="fa fa-toolbox"></i> Позиции в работе</button>
						<button class="btn btn-success" type="button" onclick="printCheck('/content/shop/print_docs/service/print.php?doc_name=sales_items&order_items=');"><i class="fa fa-print"></i> <span class="bold">Товарный чек</span></button>
						<button class="btn btn-success" type="button" onclick="printCheck('/content/shop/print_docs/service/print.php?doc_name=torg_12_items&amp;order_items=');"><i class="fa fa-print"></i> <span class="bold">Накладная ТОРГ-12</span></button>
					</div>
				</div>
			</div>
		</div>
	</div>
    
	<script>
	
	
	
	function printCheck(url)
	{
		const arr_id = getCheckedElements();
		const str = arr_id.join(',', arr_id); 
		jQuery.ajax({
			type: "POST",
			async: true, //Запрос асинхронный
			url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_check_user_items.php",
			dataType: "json",//Тип возвращаемого значения
			data: "request_object="+JSON.stringify(arr_id),
			success: function(answer)
			{
				//console.log(answer);
				//return;
				if(answer.status == true)
				{
					//Обновляем страницу
					window.open(url + str + '&order_id=' + answer.data.order_id + "&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>", '_blank');
				}
				else
				{
					console.log(answer); 
					alert(answer.message);	
				}
			}
		});
	}
	</script>
    
	<!--//Статистика-->
    <script src="/<?php echo $DP_Config->backend_dir; ?>/content/users/statistics/assets/main.js"></script>
    <script>
        function closeCustomerModalInfo(user_id) {
            if (window.event.srcElement.id == 'customer-modal-info-' + user_id || window.event.srcElement.id == 'close-customer-modal-info-' + user_id)
                document.querySelector('#customer-modal-info-' + user_id).classList.remove('customer-modal-info-wrapper-show');
        }

        function showCustomerModalInfo(customer_id)
        {
            jQuery.ajax({
                type: "POST",
                async: false, //Запрос синхронный
                url: "/<?php echo $DP_Config->backend_dir; ?>/content/users/statistics/frontAjax/ajax_loadUserModal.php",
                dataType: "json",//Тип возвращаемого значения
                data: "customer_id="+customer_id+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer){
                    if(answer.status == true)
                    {
                        document.querySelector('#customer-modal-info-' + customer_id).classList.add('customer-modal-info-wrapper-show');
                        document.querySelector('#customer-modal-info-' + customer_id).innerHTML = answer.modal;
                    }
                    else
                    {
                        alert("Ошибка авторизации");
                    }
                }
            });
        }
    </script>
    <!--END //Статистика-->
    
    

    <script>
    // ------------------------------------------------------------------------------------------------
    //Устновка cookie в соответствии с фильтром
    function filterOrdersItems()
    {
        var orders_items_filter = new Object;
        
        //1. Время с
        orders_items_filter.time_from = document.getElementById("time_from").value;
		
        //2. Время по
        orders_items_filter.time_to = document.getElementById("time_to").value;
        
        //3. Номер заказа
        orders_items_filter.order_id = document.getElementById("order_id").value;
        
        //4. Статус заказа
		if ($('#order_status').multipleSelect('getSelects', "value").length == 0) { orders_items_filter.order_status = 0; }
		else orders_items_filter.order_status = $('#order_status').multipleSelect('getSelects', "value");
        
        //5. Оплачен
        if ($('#paid').multipleSelect('getSelects', "value").length == 0) { orders_items_filter.paid = -1; }
		else orders_items_filter.paid = $('#paid').multipleSelect('getSelects', "value");
        
        //6. Покупатель
        orders_items_filter.customer = document.getElementById("customer").value;
        orders_items_filter.customer_id = document.getElementById("customer_id").value;
        
        //7. Статус позиции
        if ($('#order_item_status').multipleSelect('getSelects', "value").length == 0) { orders_items_filter.order_item_status = 0; }
		else orders_items_filter.order_item_status = $('#order_item_status').multipleSelect('getSelects', "value");
        
        //8. Офис обслуживания
        if ($('#office_id').multipleSelect('getSelects', "value").length == 0) { orders_items_filter.office_id = 0; }
		else orders_items_filter.office_id = $('#office_id').multipleSelect('getSelects', "value");
        
		//9. Наименование товара
        orders_items_filter.product_name = encodeURIComponent(document.getElementById("product_name").value);
        orders_items_filter.article = encodeURIComponent(document.getElementById("article").value);
        orders_items_filter.manufacturer = encodeURIComponent(document.getElementById("manufacturer").value);
		
		//10. Заказ просмотрен
		if ($('#viewed').multipleSelect('getSelects', "value").length == 0) { orders_items_filter.viewed = -1; }
		else orders_items_filter.viewed = $('#viewed').multipleSelect('getSelects', "value");
		
		//11. ID склада
		if ($('#storage_id').multipleSelect('getSelects', "value").length == 0) { orders_items_filter.storage_id = -1; }
		else orders_items_filter.storage_id = $('#storage_id').multipleSelect('getSelects', "value");
		
		//12. Телефон клиента
		orders_items_filter.phone = encodeURIComponent(document.getElementById("phone").value);
		
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_items_filter="+JSON.stringify(orders_items_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        goToPage(0);
    }
    // ------------------------------------------------------------------------------------------------
    //Снять все фильтры
    function unsetFilterOrdersItems()
    {
        var orders_items_filter = new Object;
        
        //1. Время с
        orders_items_filter.time_from = "";
        //2. Время по
        orders_items_filter.time_to = "";
        
        //3. Номер заказа
        orders_items_filter.order_id = "";
        
        //4. Статус заказа
        orders_items_filter.order_status = 0;
        
        //5. Товар
        orders_items_filter.paid = -1;
        
        //6. Покупатель
        orders_items_filter.customer = "";
        orders_items_filter.customer_id = "";
        
        //7. Статус позиции
        orders_items_filter.order_item_status = 0;
        
        //8. Офис обслуживания
        orders_items_filter.office_id = 0;
		
		//9. Наименование
        orders_items_filter.product_name = "";
        orders_items_filter.article = "";
        orders_items_filter.manufacturer = "";
        
		//10. Заказ просмотрен
		orders_items_filter.viewed = -1;
		
		//11. ID склада
		orders_items_filter.storage_id = -1;
		
		//12. Телефон клиента
		orders_items_filter.phone = "";
		
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_items_filter="+JSON.stringify(orders_items_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
		goToPage(0);
    }
    // ------------------------------------------------------------------------------------------------
	//Позиции в работе - кнопка
	<?php
	//Получаем список статусов позиций
	$orders_items_statuses_InProcess = array();
	$query = $db_link->prepare("SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `count_flag` != 0 && `for_finish` != 1 && `for_created` != 1;");
	$query->execute();
	while($status_row = $query->fetch() )
	{
		$orders_items_statuses_InProcess[] = (string)$status_row["id"];
	}
	?>
	function itemsInProcess()
	{
		var orders_items_filter = new Object;

		//1. Время с
		orders_items_filter.time_from = "";
		
		//2. Время по
		orders_items_filter.time_to = "";

		//3. Номер заказа
		orders_items_filter.order_id = "";

		//4. Статус заказа
		orders_items_filter.order_status = 0;

		//5. Товар
		orders_items_filter.paid = -1;

		//6. Покупатель
		orders_items_filter.customer = "";
		orders_items_filter.customer_id = "";

		//7. Статус позиции
		orders_items_filter.order_item_status = <?php  echo '['.implode(',', $orders_items_statuses_InProcess).']'; ?>;

		//8. Офис обслуживания
		orders_items_filter.office_id = 0;

		//9. Наименование
		orders_items_filter.product_name = "";
		orders_items_filter.article = "";
		orders_items_filter.manufacturer = "";

		//10. Заказ просмотрен
		orders_items_filter.viewed = -1;

		//11. ID склада
		orders_items_filter.storage_id = -1;

		//12. Телефон клиента
		orders_items_filter.phone = "";

		//Устанавливаем cookie (на полгода)
		var date = new Date(new Date().getTime() + 15552000 * 1000);
		document.cookie = "orders_items_filter=" + JSON.stringify(orders_items_filter) + "; path=/; expires=" + date.toUTCString();

		//Обновляем страницу
		goToPage(0);
	}
    // ------------------------------------------------------------------------------------------------
    </script>
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
   
    
    

    
    
    
    
    
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Товарные позиции
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<?php
					// ---------- Start SAO ----------
					//Предварительно получаем список возможных SAO-действий:
					$sao_actions = array();
					
					$sao_actions_query = $db_link->prepare("SELECT * FROM `shop_sao_actions`");
					$sao_actions_query->execute();
					while( $sao_action = $sao_actions_query->fetch() )
					{
						$sao_actions[$sao_action["id"]] = array();
						$sao_actions[$sao_action["id"]]["name"] = $sao_action["name"];
						$sao_actions[$sao_action["id"]]["script"] = $sao_action["script"];
						$sao_actions[$sao_action["id"]]["fontawesome"] = $sao_action["fontawesome"];
						$sao_actions[$sao_action["id"]]["btn_class"] = $sao_action["btn_class"];
					}
					
					//Подключаем протокол выполнения действий
					$sao_propocol_mode = 2;//Режим работы протокола - страница "Позиции заказов"
					require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/sao/actions_exec_propocol.php");
					// ---------- End SAO ----------
					
					
					
					//Определяем текущую сортировку и обозначаем ее:
					$orders_items_sort = NULL;
					if( isset($_COOKIE["orders_items_sort"]) )
					{
						$orders_items_sort = $_COOKIE["orders_items_sort"];
					}
					$sort_field = "id";
					$sort_asc_desc = "desc";
					if($orders_items_sort != NULL)
					{
						$orders_items_sort = json_decode($orders_items_sort, true);
						$sort_field = $orders_items_sort["field"];
						$sort_asc_desc = $orders_items_sort["asc_desc"];
					}
					
					if( strtolower($sort_asc_desc) == "asc" )
					{
						$sort_asc_desc = "asc";
					}
					else
					{
						$sort_asc_desc = "desc";
					}
					
					
					if( array_search($sort_field, array('id', 'product_name', 'article', 'manufacturer', 'price', 'count_need', 'price_sum', 'price_purchase_sum', 'profit', 'status', 'time', 'order_id', 'office_id', 't2_time_to_exe', 'customer', 'customer_id', 'checks_count') ) === false )
					{
						$sort_field = "id";
					}
					
					
					//Формируем сложный SQL-запрос для получения всей информации по каждой позиции
					$binding_values = array();
					
					//Запрос времени оформления заказа
					$SELECT_time = "(SELECT `time` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					
					//Запрос суммы позиции
					$SELECT_price_sum = "CAST(`price`*`count_need` AS DECIMAL(20,2))";
					
					//Запрос офисов обслуживания
					$SELECT_offices = "(SELECT `office_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					
					
					//Запрос клиента
					//Формируем подзапрос для значений профиля пользователя (только для тех полей, которые выводятся в таблицу пользователей в менеджер пользователей колонками)
					$users_profile_SQL = "";
					$users_profile_fields_query = $db_link->prepare("SELECT `name` FROM `reg_fields` WHERE `to_users_table` = 1;");
					$users_profile_fields_query->execute();
					while( $users_profile_field = $users_profile_fields_query->fetch() )
					{
						if( $users_profile_SQL != "" )
						{
							$users_profile_SQL = $users_profile_SQL.",";
						}
						
						//Допустимы только буквы и знаки нижнего подчеркивания
						$field_name = str_replace( array(' ', '#', '-', "'", '"'), '', $users_profile_field["name"] );
						
						$users_profile_SQL = $users_profile_SQL." IF( IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = '".$field_name."' AND `user_id` = (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` LIMIT 1 )), '') != '' , CONCAT(', ', (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = '".$field_name."' AND `user_id` = (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` LIMIT 1 ))),'') ";
					}
					if( $users_profile_SQL != "" )
					{
						$users_profile_SQL = ",".$users_profile_SQL;
					}
					//SQL-подзапрос компонует строку с данными пользователя
					$SELECT_clients = " IF( (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` ) = 0, CONCAT('Незарегистрированный покупатель (ID 0)', IF( (SELECT `phone_not_auth` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)='', '', CONCAT(', Телефон: ', (SELECT `phone_not_auth` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`) )), IF( (SELECT `email_not_auth` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)='', '', CONCAT(', E-mail: ', (SELECT `email_not_auth` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`) ))), CONCAT( 'ID ', (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` ), ', E-mail: ', (SELECT IF(`email`!='', `email`, 'Не указан') FROM `users` WHERE `user_id` = (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` LIMIT 1 ) LiMIT 1 ), ', Телефон: ', (SELECT IF(`phone`!='', `phone`, 'Не указан') FROM `users` WHERE `user_id` = (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` LIMIT 1 ) LiMIT 1 ) ".$users_profile_SQL." ) )";
					
					
					
					//Запрос закупа
					$SELECT_price_purchase_sum = "IFNULL((SELECT SUM(`price_purchase`*(`count_reserved`+`count_issued`+`count_canceled`)) FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id`), CAST(`t2_price_purchase`*`count_need` AS DECIMAL(20,2)))";
					//Запрос маржы
					$SELECT_profit = "CAST(($SELECT_price_sum - $SELECT_price_purchase_sum) AS DECIMAL(20,2))";
					//Запрос статуса заказа
					$SELECT_order_status = "(SELECT `status` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					//Запрос флаг "Заказ оплачен"
					$SELECT_paid = "(SELECT `paid` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					
					//Запрос флага "Заказ просмотрен" viewed_flag
					$SELECT_viewed = " IFNULL( (SELECT `viewed_flag` FROM `shop_orders_viewed` WHERE `order_id` = `shop_orders_items`.`order_id` AND `user_id` = ? LIMIT 1), 1 ) ";
					
					array_push($binding_values, $manager_id);
					
					
					
					//SAO
					$SELECT_item_sao_state = "IFNULL( (SELECT `name` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
					$SELECT_item_sao_color_background = "IFNULL( (SELECT `color_background` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
					$SELECT_item_sao_color_text = "IFNULL( (SELECT `color_text` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
					//Получаем через запятую возможные дейстия для SAO для данного состояния и данного поставщика
					$SELECT_item_sao_actions = " IFNULL(( SELECT GROUP_CONCAT(`id` SEPARATOR ',') FROM `shop_sao_actions` WHERE id IN (SELECT `action_id` FROM `shop_sao_states_types_actions_link` WHERE `state_type_id` = (SELECT `id` FROM `shop_sao_states_types_link` WHERE `state_id` = `shop_orders_items`.`sao_state` AND `interface_type_id` =  (SELECT `interface_type` FROM `shop_storages` WHERE `id` = `shop_orders_items`.`t2_storage_id` ) )) ), '')";
					
					
					
					//Данные о способе получения:
					$SELECT_how_get_caption = "(SELECT `caption` FROM `shop_obtaining_modes` WHERE `id` = (SELECT `how_get` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)  )";
					$SELECT_how_get_handler = "(SELECT `handler` FROM `shop_obtaining_modes` WHERE `id` = (SELECT `how_get` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)  )";
					$SELECT_how_get_json = "(SELECT `how_get_json` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					
					
					
					
					//Фильтры
					$WHERE_CONDITIONS = " WHERE ";

					//По офисам обслуживания - только те, с которыми работает данный менеджер
					$sub_WHERE_offices = "";
					foreach($offices_list as $office_id => $office_caption)
					{
						if(isset($orders_items_filter["office_id"]) && $orders_items_filter["office_id"] != 0 && !in_array($office_id, $orders_items_filter["office_id"]))
						{
							continue;//Если выбран в фильтре офис
						}
						
						if($sub_WHERE_offices != "")$sub_WHERE_offices .= ",";
						$sub_WHERE_offices .= "?";
						
						array_push($binding_values, $office_id);
					}
					$WHERE_CONDITIONS .= "$SELECT_offices IN ($sub_WHERE_offices)";

					//Ставим ПОЛЬЗОВАТЕЛЬСКИЕ фильтры
					$orders_items_filter = NULL;
					if( isset($_COOKIE["orders_items_filter"]) )
					{
						$orders_items_filter = $_COOKIE["orders_items_filter"];
					}
					if($orders_items_filter != NULL)
					{
						$orders_items_filter = json_decode($orders_items_filter, true);

						//1. Время с
						if($orders_items_filter["time_from"] != "")
						{
							$WHERE_CONDITIONS .= " AND $SELECT_time > ?";
							
							array_push($binding_values, $orders_items_filter["time_from"]);
						}

						//2. Время по
						if($orders_items_filter["time_to"] != "")
						{
							$WHERE_CONDITIONS .= " AND $SELECT_time < ?";
							
							array_push($binding_values, $orders_items_filter["time_to"]);
						}

						//3. Номер заказа
						if($orders_items_filter["order_id"] != "")
						{
							$WHERE_CONDITIONS .= " AND `order_id` = ?";
							
							array_push($binding_values, $orders_items_filter["order_id"]);
						}
						
						//4. Статус заказа
						if($orders_items_filter["order_status"] != 0)
						{
							$WHERE_CONDITIONS .= " AND $SELECT_order_status IN (". str_repeat("?,", count($orders_items_filter["order_status"]) - 1) ."?)";

							$binding_values = array_merge($binding_values, $orders_items_filter["order_status"]);
						}
						
						//5. Оплата
						if($orders_items_filter["paid"] != -1)
						{
							$WHERE_CONDITIONS .= " AND $SELECT_paid IN (". str_repeat("?,", count($orders_items_filter["paid"]) - 1) ."?)";

							$binding_values = array_merge($binding_values, $orders_items_filter["paid"]);
						}
						
						//6. Покупатель
						if($orders_items_filter["customer"] != "" )
						{
							$WHERE_CONDITIONS .= " AND $SELECT_clients  LIKE ?";
							
							array_push($binding_values, "%".htmlentities($orders_items_filter["customer"])."%");
						}
						
						if($orders_items_filter["customer_id"] != "" )
						{
							$WHERE_CONDITIONS .= " AND (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`) = ?";
							
							array_push($binding_values, $orders_items_filter["customer_id"]);
						}
						
						//7. Статус позиции
						if($orders_items_filter["order_item_status"] != 0)
						{
							$WHERE_CONDITIONS .= " AND `status` IN (". str_repeat("?,", count($orders_items_filter["order_item_status"]) - 1) ."?)";

							$binding_values = array_merge($binding_values, $orders_items_filter["order_item_status"]);
						}

						//8. Офис обслуживания
						if($orders_items_filter["office_id"] != 0)
						{
							$WHERE_CONDITIONS .= " AND $SELECT_offices IN (". str_repeat("?,", count($orders_items_filter["office_id"]) - 1) ."?)";

							$binding_values = array_merge($binding_values, $orders_items_filter["office_id"]);
						}

						//9. Наименование
						if($orders_items_filter["product_name"] != "" )
						{
							$WHERE_CONDITIONS .= " AND `t2_name` LIKE ?";
							
							array_push($binding_values, '%'.$orders_items_filter["product_name"].'%');
						}
						
						if($orders_items_filter["article"] != "" )
						{
							$WHERE_CONDITIONS .= " AND `t2_article` LIKE ?";
							
							array_push($binding_values, '%'.$orders_items_filter["article"].'%');
						}
						
						if($orders_items_filter["manufacturer"] != "" )
						{
							$WHERE_CONDITIONS .= " AND `t2_manufacturer` LIKE ?";
							
							array_push($binding_values, '%'.$orders_items_filter["manufacturer"].'%');
						}
						
						//10. Заказ просмотрен
						if($orders_items_filter["viewed"] != -1 && count($orders_items_filter["viewed"]) == 1)
						{
							$orders_items_filter["viewed"] = $orders_items_filter["viewed"][0];
							$WHERE_CONDITIONS .= " AND IFNULL( (SELECT `viewed_flag` FROM `shop_orders_viewed` WHERE `order_id` = `shop_orders_items`.`order_id` AND `user_id` = ? LIMIT 1), 1 ) = ?";
							
							array_push($binding_values, $manager_id);
							array_push($binding_values, $orders_items_filter["viewed"]);
						}
						
						//11. ID Склада
						if($orders_items_filter["storage_id"] != -1)
						{
							$WHERE_CONDITIONS .= " AND IF( `t2_storage_id` = 0, IFNULL((SELECT `storage_id` FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id` AND `storage_id` = ? LIMIT 1 ), 0) , `t2_storage_id` ) IN (". str_repeat("?,", count($orders_items_filter["storage_id"]) - 1) ."?)";

							array_push($binding_values, $orders_items_filter["storage_id"]);
							$binding_values = array_merge($binding_values, $orders_items_filter["storage_id"]);
						}
						
						//12. Телефон клиента
						if($orders_items_filter["phone"] != "" )
						{
							$WHERE_CONDITIONS .= " AND $SELECT_clients  LIKE ?";
							
							array_push($binding_values, "%".htmlentities($orders_items_filter["phone"])."%");
						}
					}
			
					//ОБЕСПЕЧИВАЕМ ПОСТРАНИЧНЫЙ ВЫВОД:
					//---------------------------------------------------------------------------------------------->

					//Определяем, с какой страницы начать вывод:
					$s_page = 0;
					if( isset($_COOKIE['orders_items_need_page']) )
					{
						$s_page = (int) $_COOKIE['orders_items_need_page'];
					}


					//Определяем сколько пропустить записей для выборки
					$p = $DP_Config->list_page_limit;//Штук на страницу
					$start_elements_of_page = abs($s_page * $p);

					// print_r($SQL_SELECT_ITEMS_LIMIT);

					$elements_counter = 0;
					//----------------------------------------------------------------------------------------------|

					//ЗАПРОС 
					$SQL_SELECT_ITEMS = "SELECT SQL_CALC_FOUND_ROWS *, 
						`t2_name` AS `product_name`, 
						`t2_article` AS `article`, 
						`t2_manufacturer` AS `manufacturer`, 
						$SELECT_price_sum AS `price_sum`, 
						$SELECT_offices AS `office_id`, 
						$SELECT_clients AS `customer`, 
						(SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`) AS `customer_id`, 
						$SELECT_price_purchase_sum AS `price_purchase_sum`,
						$SELECT_profit AS `profit`,
						$SELECT_time AS `time`,
						$SELECT_order_status AS `order_status`,
						$SELECT_paid AS `paid`,
						$SELECT_viewed AS `viewed_flag`,
						$SELECT_item_sao_state AS `sao_state_name`,
						$SELECT_item_sao_color_background AS `sao_state_color_background`, 
						$SELECT_item_sao_color_text AS `sao_state_color_text`, 
						$SELECT_item_sao_actions AS `sao_actions`,
						$SELECT_how_get_caption AS `how_get_caption`,
						(SELECT COUNT(`id`) FROM `shop_kkt_checks_products_to_orders_items_map` WHERE `order_item_id` = `shop_orders_items`.`id`) AS `checks_count`
						FROM `shop_orders_items` $WHERE_CONDITIONS ORDER BY `$sort_field` $sort_asc_desc LIMIT $start_elements_of_page,$p";
					

					$elements_query = $db_link->prepare($SQL_SELECT_ITEMS);
					$elements_query->execute($binding_values);
					
					$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
					$elements_count_rows_query->execute();
					$elements_count_rows = $elements_count_rows_query->fetchColumn();
					
					//Определяем количество страниц для вывода:
					$count_pages = (int)($elements_count_rows / $p);//Количество страниц
					if($elements_count_rows%$p)//Если остались еще элементы
					{
						$count_pages++;
					}
					
					// -------------------------------------------------------------------
					//Получаем суммарные показатели: Сумма всех заказов (price_sum_total), Количество заказов (orders_count), Количество позиций (positions_count), Сумма маржи (profit_sum_total), Сумма закупа (price_purchase_sum_total)
					$SQL_SELECT_TOTAL_INDICATORS = "SELECT 
						SUM($SELECT_price_sum) AS `price_sum_total`,
						COUNT(*) AS `positions_count`,
						COUNT( DISTINCT(`order_id`) ) AS `orders_count`,
						SUM($SELECT_profit) AS `profit_sum_total`,
						SUM($SELECT_price_purchase_sum) AS `price_purchase_sum_total`
						FROM `shop_orders_items` $WHERE_CONDITIONS ";
					
					//Удаляем первый элемент массива связанных значений (это был manager_id для флага "Просмотрен")
					array_shift($binding_values);
					
					$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);
					$total_indicators_query->execute($binding_values);
					$total_indicators = $total_indicators_query->fetch();
					// -------------------------------------------------------------------
					
					//Массивы для JS с id элементов и с чекбоксами элементов
					$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
					$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
					
					$for_js = $for_js."var orders_items_to_orders_map = new Array();\n";//Для связи позиций заказов и номеров заказов
					
					$for_js = $for_js."var orders_items_ids_to_orders_items_objects = new Array();";//Для связи ID позиций заказов с их объектами (требуется для пробивки чеков)
					
					?>
					<table id="orders_items_table" class="footable table table-hover toggle-arrow " data-sort="false" data-page-size="<?php echo $elements_count_rows; ?>">
						<thead>
							<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();" /></th>
							<th data-toggle="true"></th>
							<th><a href="javascript:void(0);" onclick="sortOrdersItems('id');" id="id_sorter">ID</a></th>
							<th><a href="javascript:void(0);" onclick="sortOrdersItems('manufacturer');" id="manufacturer_sorter">Производитель</a></th>
							<th><a href="javascript:void(0);" onclick="sortOrdersItems('article');" id="article_sorter">Артикул</a></th>
							<th><a href="javascript:void(0);" onclick="sortOrdersItems('product_name');" id="product_name_sorter">Наименование</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('checks_count');" id="checks_count_sorter">Чеки</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('price');" id="price_sorter">Цена</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('count_need');" id="count_need_sorter">Кол-во</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('price_sum');" id="price_sum_sorter">Сумма</a></th>
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('price_purchase_sum');" id="price_purchase_sum_sorter">Закуп</a></th>
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('profit');" id="profit_sorter">Маржа</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('status');" id="status_sorter">Статус</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('time');" id="time_sorter">Дата</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('customer');" id="customer_sorter">Клиент</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('order_id');" id="order_id_sorter">Заказ</a></th>
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('office_id');" id="office_id_sorter">Офис</a></th>
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('t2_time_to_exe');" id="t2_time_to_exe_sorter">Срок</a></th>
							
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('how_get_caption');" id="how_get_caption_sorter">Способ доставки</a></th>
							
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);">Подробнее</a></th>
							
							<th data-hide="phone,tablet,default">Данные склада</th>
						</thead>
						<tbody>
						<?php
						$items_counter = 0;

						while( $item = $elements_query->fetch() )
						{
							if($elements_counter >= $s_page*$p+$p)
							{
								break;
							}
							$elements_counter++;
							//Для Javascript
							$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$item["id"]."\";\n";//Добавляем элемент для JS
							$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$item["id"].";\n";//Добавляем элемент для JS
							
							$for_js = $for_js."orders_items_to_orders_map[".$item["id"]."] = ".$item["order_id"].";\n";//Добавляем элемент для JS
							
							
							$for_js = $for_js."orders_items_ids_to_orders_items_objects[".$item["id"]."] = {\"product_name\":\"".$item["manufacturer"].' '.$item["article"].' '.$item["product_name"]."\",\"name\":\"".$item["product_name"]."\",\"article\":\"".$item["article"]."\",\"manufacturer\":\"".$item["manufacturer"]."\",\"price\":".$item["price"].",\"count_need\":".$item["count_need"]."};\n";//Добавляем элемент для JS
							
							
							$item_id = $item["id"];
							$item_product_type = $item["product_type"];
							$item_status = $item["status"];
							$item_order_id = $item["order_id"];
							$item_product_name = $item["product_name"];
							$item_article = $item["article"];
							$item_manufacturer = $item["manufacturer"];
							$item_price = $item["price"];
							$item_count_need = $item["count_need"];
							$item_price_sum = $item["price_sum"];
							$item_office_id = $item["office_id"];
							$item_customer = $item["customer"];
							
							$customer_id =  $item['customer_id'];//Статистика
							
							$item_how_get_caption = $item["how_get_caption"];
							
							$item_price_purchase_sum = $item["price_purchase_sum"];
							$item_profit = $item["profit"];
							$item_time = $item["time"];
							$item_t2_time_to_exe = $item["t2_time_to_exe"];
							$item_t2_time_to_exe_guaranteed = $item["t2_time_to_exe_guaranteed"];
							
							//Срок доставки для продуктов типа 2
							if($item_t2_time_to_exe < $item_t2_time_to_exe_guaranteed)
							{
								$item_t2_time_to_exe = $item_t2_time_to_exe." - ".$item_t2_time_to_exe_guaranteed;
							}
							$item_t2_time_to_exe = $item_t2_time_to_exe." дн.";
							
							//Флаг "Заказ просмотрен"
							$viewed_class = "";
							$viewed_flag = $item["viewed_flag"];
							if( $viewed_flag == 0)
							{
								$viewed_class = " not_viewed";
							}
							
							
							

							
							//SAO
							$item_sao_state_name = $item["sao_state_name"];
							$item_sao_state = $item["sao_state"];
							$item_sao_state_color_background = $item["sao_state_color_background"];
							$item_sao_state_color_text = $item["sao_state_color_text"];
							$item_sao_actions = $item["sao_actions"];
							$item_sao_message = $item["sao_message"];
							
							
							//Чеки
							$item_checks_count = $item["checks_count"];
							if( $item_checks_count == 0 )
							{
								$item_checks_count = "Нет";
							}
							else
							{
								$item_checks_count = "<span onclick=\"show_order_item_checks(".$item_id.");\">".$item_checks_count." <i class=\"fas fa-search\"></i></span>";
							}
							?>
							

							<tr class="<?php echo $viewed_class; ?>" id="order_item_record_<?php echo $item_id; ?>" style="background-color:<?php echo $orders_items_statuses[$item_status]["color"]; ?>">
								<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $item_id; ?>');" id="checked_<?php echo $item_id; ?>" name="checked_<?php echo $item_id; ?>" /></td>
								<td></td>
								<td><?php echo $item_id; ?></td>
								<td><?php echo $item_manufacturer; ?></td>
								<td><?php echo $item_article; ?></td>
								<td><?php echo $item_product_name; ?></td>
								<td><?php echo $item_checks_count; ?></td>
								<td><?php echo number_format($item_price, 2, '.', ''); ?></td>
								<td><?php echo $item_count_need; ?></td>
								<td><?php echo number_format($item_price_sum, 2, '.', ''); ?></td>
								<td><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></td>
								<td><?php echo number_format($item_profit, 2, '.', ''); ?></td>
								<td id="order_item_status_name_td_<?php echo $item_id; ?>"><?php echo $orders_items_statuses[$item_status]["name"]; ?></td>
								<td><?php echo date("d.m.Y", $item_time)." ".date("G:i", $item_time); ?></td>
								<td><?php include $_SERVER['DOCUMENT_ROOT'].'/'.$DP_Config->backend_dir.'/content/users/statistics/modal.php';//Статистика?><?php echo $item_customer; ?></td>
								<td>
									<a href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $item_order_id; ?>">
										Заказ ID <?php echo $item_order_id; ?><br>
										<font style="font-size:0.8em;">
										<?php
										if( $item["paid"] == 0 )
										{
											echo "Не оплачен";
										}
										else if( $item["paid"] == 1 )
										{
											echo "Оплачен полностью";
										}
										else
										{
											echo "Оплачен частично";
										}
										?>
										</font>
									</a>
								</td>
								<td><?php echo $offices_list[$item_office_id]; ?></td>
								<td><?php echo $item_t2_time_to_exe; ?></td>
								<td>
									<?php echo $item_how_get_caption; ?>
								</td>
								<td>
									<a class="btn btn-success " href="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $item_order_id; ?>" target="_blank"><i class="fa fa-search"></i> <span class="bold">Детальная информация заказа</span></a>
								</td>
								<td>
									<div class="row">
										<div class="col-lg-12">
										
											
											
											<?php
											if($item["paid"] == 0)
											{
											?>
												<div style="position:relative; left:-70px; top:60px;">
													<a href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items/edit?id=<?=$item_id;?>" title="Редактировать позицию"> <i style="font-size: 4em;" class="far fa-edit"></i></a>
												</div>
											<?php
											}
											?>
											
										
										
										
											<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped table-bordered">
												<thead>
													<tr>
														<th rowspan="2" style="vertical-align:middle;">Склад</th>
														<th rowspan="2" style="vertical-align:middle;">Поставка</th>
														<th rowspan="2" style="vertical-align:middle;">Цена закупа</th>
														<th rowspan="2" style="vertical-align:middle;">Количество</th>
														<th rowspan="2" style="vertical-align:middle;">Сумма закупа</th>
														<th colspan="3" style="text-align:center;">
															SAO
														</th>
													</tr>
													<tr>
														<th style="text-align:center;">Состояние</th>
														<th style="text-align:center;">Инфо</th>
														<th style="text-align:center;">Действия</th>
													</tr>
												</thead>
												<tbody>
												<?php
												//Выводим данные по поставкам. Логика зависит от типа продукта
												if($item_product_type == 1)
												{
													$details_query = $db_link->prepare("SELECT *, (`count_reserved`+`count_issued`+`count_canceled`)*`price_purchase` AS `price_purchase_sum`, `count_reserved`+`count_issued`+`count_canceled` AS `count_reserved_issued` FROM `shop_orders_items_details` WHERE `order_item_id` = ?;");
													$details_query->execute( array($item_id) );
													while( $detail = $details_query->fetch() )
													{
														?>
														<tr>
															<td><?php echo $storages_list[$detail["storage_id"]]; ?> (ID <?php echo $detail["storage_id"]; ?>)</td>
															<td><?php echo $detail["storage_record_id"]; ?></td>
															<td><?php echo number_format($detail["price_purchase"], 2, '.', ''); ?></td>
															<td><?php echo $detail["count_reserved_issued"]; ?></td>
															<td><?php echo number_format($detail["price_purchase_sum"], 2, '.', ''); ?></td>
															<td colspan="3">В каталоге SAO не поддерживается</td>
														</tr>
														<?php
													}
												}
												else if($item_product_type == 2)
												{
													?>
													<tr>
														<td><?php echo $storages_list[$item["t2_storage_id"]]; ?> (ID <?php echo $item["t2_storage_id"]; ?>)</td>
														<td><?php echo "-"; ?></td>
														<td><?php echo number_format($item["t2_price_purchase"], 2, '.', ''); ?></td>
														<td><?php echo $item["count_need"]; ?></td>
														<td><?php echo number_format($item["t2_price_purchase"]*$item["count_need"], 2, '.', ''); ?></td>
														<?php
														if( $item_sao_state > 0 )
														{
															?>
															<td id="order_item_sao_state_td_<?php echo $item_id; ?>" style="background-color:<?php echo $item_sao_state_color_background; ?>; color:<?php echo $item_sao_state_color_text; ?>;vertical-align:middle;">
																<?php echo $item_sao_state_name; ?>
															</td>
															<td id="order_item_sao_info_td_<?php echo $item_id; ?>">
																<?php
																if($item_sao_message != "")
																{
																	echo $item_sao_message;
																}
																else
																{
																	echo "-";
																}
																?>
															</td>
															<td id="order_item_sao_actions_td_<?php echo $item_id; ?>">
																<?php
																if($item_sao_actions != "")
																{
																	$item_sao_actions = explode(",", $item_sao_actions);
																	for($ac=0; $ac < count($item_sao_actions); $ac++)
																	{
																		?>
																		<button onclick="exec_action(<?php echo $item_id; ?>, <?php echo $item_sao_actions[$ac]; ?>);" class="btn <?php echo $sao_actions[$item_sao_actions[$ac]]["btn_class"]; ?> " type="button"><i class="fa <?php echo $sao_actions[$item_sao_actions[$ac]]["fontawesome"]; ?>"></i> <span class="bold"><?php echo $sao_actions[$item_sao_actions[$ac]]["name"]; ?></span></button>
																		<?php
																	}
																}
																else
																{
																	?>
																	Доступных действий нет
																	<?php
																}
																?>
															</td>
															<?php
														}
														else
														{
															?>
															<td colspan="3">Поставщик не поддерживает SAO</td>
															<?php
														}
														?>
													</tr>
													<?php
												}
												?>
													<tr>
														<td colspan="2"></td>
														<td><strong>Итого</strong></td>
														<td><strong><?php echo $item_count_need; ?></strong></td>
														<td><strong><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></strong></td>
														<td colspan="3"></td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</td>
							</tr>
							<?php
							$items_counter++;
						}//while() - по позициям
						?>
						</tbody>
						<tfoot style="display:none;"><tr><td><ul class="pagination"></ul></td></tr></tfoot>
					</table>
				</div>
				
				
				<?php
				//START ВЫВОД ПЕРЕКЛЮЧАТЕЛЕЙ СТРАНИЦ ТАБЛИЦЫ
				if( $count_pages > 1 )
				{
					// формируем пагинацию
					$pagination = pagination($elements_count_rows, $p, 3, $s_page, 'paginate_button active', '');
					if($pagination != '<a class="paginate_button active">1</a>'){
						$pagination = '<div class="pagination">'.$pagination.'</div>';
					}else{
						$pagination = '';
					}
					?>
					<div class="row">
						<div class="col-lg-12 text-center">
							<div class="dataTables_paginate paging_simple_numbers">
								<?php echo $pagination; ?>	
							
							</div>
						</div>
					</div>
				<?php
				}
				//END ВЫВОД ПЕРЕКЛЮЧАТЕЛЕЙ СТРАНИЦ ТАБЛИЦЫ
				?>
					
				
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-lg-2">
						<button class="btn btn-info " type="button" onclick="create_check_for_orders_items();"><i class="fas fa-receipt"></i> Оформить кассовый чек</button>
					</div>
				
					<div class="col-lg-6">	
						<div class="input-group">
							<select id="setOrderItemsStatusSelect" class="form-control">
								<?php
								foreach($orders_items_statuses as $status_id=>$status_data)
								{
									?>
									<option value="<?php echo $status_id; ?>"><?php echo $status_data["name"]; ?></option>
									<?php
								}
								?>
							</select>
							<span class="input-group-btn">
								<button onclick="setOrderItemsStatus();" class="btn btn-success " type="button"><i class="fa fa-check"></i> <span class="bold">Присвоить статус</span></button>
							</span>
						</div>
					</div>
					
					
					
					<div class="col-lg-4">	
						<div class="input-group">
							<select id="setOrderViewed" class="form-control">
								<option value="1">Пометить заказ, как просмотренный</option>
								<option value="0">Пометить заказ, как не просмотренный</option>
							</select>
							<span class="input-group-btn">
								<button onclick="setOrderViewed();" class="btn btn-success " type="button"><i class="fa fa-check"></i> <span class="bold">Ok</span></button>
							</span>
						</div>
					</div>
					
					
				</div>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Показатели по позициям с учетом фильтра <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('В этом блоке приведены показатели по всем позициям с учетом фильтра. Если позиции в таблице выше не помещаются на одну страницу (т.е. требуется переключать страницы), то суммарные показатели рассчитываются, не смотря на это - со всех страниц по всем позициям, которые соответствуют настройкам фильтра (фильтр расположен вверху на данной странице).');"><i class="fa fa-info"></i></button>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-lg-12 text-center">
						<div class="table-responsive">
							<table cellpadding="1" cellspacing="1" class="table">
								<thead>
									<tr>
										<th style="text-align:center;">Всего позиций</th>
										<th style="text-align:center;">Всего заказов</th>
										<th style="text-align:center;">Сумма по позициям</th>
										<th style="text-align:center;">Маржа по позициям</th>
										<th style="text-align:center;">Закуп по позициям</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td style="text-align:center;"><?php echo $total_indicators["positions_count"]; ?></td>
										<td style="text-align:center;"><?php echo $total_indicators["orders_count"]; ?></td>
										<td style="text-align:center;"><?php echo $total_indicators["price_sum_total"]; ?></td>
										<td style="text-align:center;"><?php echo $total_indicators["profit_sum_total"]; ?></td>
										<td style="text-align:center;"><?php echo $total_indicators["price_purchase_sum_total"]; ?></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	

	
	
	<script>
	// -----------------------------------------------------------------------------------------------------------
	//Установить статус просмотра для заказов
	function setOrderViewed()
	{
		var orders_items_checked = getCheckedElements();
		
		if(orders_items_checked.length == 0)
		{
			alert("Не отмечены позиции заказов");
			return;
		}
		
		//Далее получаем заказы
		var orders = new Array();
		for(var i=0; i < orders_items_checked.length; i++)
		{
			orders.push( orders_items_to_orders_map[orders_items_checked[i]] );
		}
		
		
		//Объект запроса
		var request_object = new Object;
		request_object.orders = orders;
		request_object.viewed_flag = document.getElementById("setOrderViewed").value;
		request_object.user_id = <?php echo $manager_id; ?>;

		//console.log(request_object);
		//return;
		
		jQuery.ajax({
			type: "POST",
			async: true, //Запрос асинхронный
			url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_set_orders_viewed.php",
			dataType: "json",//Тип возвращаемого значения
			data: "request_object="+JSON.stringify(request_object)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
			success: function(answer)
			{
				//console.log(answer);
				//return;
				
				if(answer.status == true)
				{
					//Обновляем страницу
					location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items';
				}
				else
				{
					console.log(answer);
					alert("Ошибка сервера");
				}
			}
		});
		
	}
	// -----------------------------------------------------------------------------------------------------------
	</script>
	
    
    
    
	
	
	<script>
		jQuery( window ).load(function() {
			$('#orders_items_table').footable();
			
			document.getElementById("<?php echo $sort_field; ?>_sorter").innerHTML += "<img src=\"/content/files/images/sort_<?php echo $sort_asc_desc; ?>.png\" style=\"width:15px\" />";
		});
	</script>
    
	
	
	
	<script>
        //Выставить статус для позиций заказа
        function setOrderItemsStatus()
        {
            var orders_items = getCheckedElements();//Список отмеченных заказов
            if(orders_items.length == 0)
            {
                alert("Выберите товарные позиции из списка");
                return;
            }
            
            var needStatus = document.getElementById("setOrderItemsStatusSelect").value;
            
            jQuery.ajax({
                    type: "GET",
                    async: false, //Запрос синхронный
                    url: "/content/shop/protocol/set_order_item_status.php",
                    dataType: "json",//Тип возвращаемого значения
                    data: "initiator=1&orders_items="+JSON.stringify(orders_items)+"&status="+needStatus+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                    success: function(answer)
                    {
                        //console.log(answer);
                        if(answer.status == true)
                        {
                            //Обновляем страницу
                            location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items?success_message='+encodeURI('Выполнено');
                        }
                        else
                        {
							if( typeof answer.message != undefined )
							{
								alert(answer.message);
							}
							else
							{
								alert("Ошибка изменения статуса позиций");
							}
                        }
                    }
            	});
        }
    </script>
    
    
    
    
    <script>
    // ----------------------------------------------------------------------------------------
    <?php
    echo $for_js;//Выводим массив с чекбоксами для элементов
    ?>
    //Обработка переключения Выделить все/Снять все
    function on_check_uncheck_all()
    {
        var state = document.getElementById("check_uncheck_all").checked;
        
        for(var i=0; i<elements_array.length;i++)
        {
            document.getElementById(elements_array[i]).checked = state;
        }
    }//~function on_check_uncheck_all()
    // ----------------------------------------------------------------------------------------
    //Обработка переключения одного чекбокса
    function on_one_check_changed(id)
    {
        //Если хотя бы один чекбокс снят - снимаем общий чекбокс
        for(var i=0; i<elements_array.length;i++)
        {
            if(document.getElementById(elements_array[i]).checked == false)
            {
                document.getElementById("check_uncheck_all").checked = false;
                break;
            }
        }
    }//~function on_one_check_changed(id)
    // ----------------------------------------------------------------------------------------
    //Получение массива id отмеченых элементов
    function getCheckedElements()
    {
        var checked_ids = new Array();
        //По массиву чекбоксов
        for(var i=0; i<elements_array.length;i++)
        {
            if(document.getElementById(elements_array[i]).checked == true)
            {
                checked_ids.push(elements_id_array[i]);
            }
        }
        
        return checked_ids;
    }
    // ----------------------------------------------------------------------------------------
    </script>
    
    
    <?php
}
?>