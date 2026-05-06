<?php
/**
 * Страничный скрипт для отображения заказов
 * 
 * Заказы отображаются:
 * - от тех офисов, для которых данный пользователь назначен менеджером;
 * - в соответствии с фильтром;
 * - упорядоченные по определенному полю;
 * - ограниченный диапазон
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

	// print_r($all);
	// echo "</br>";
	// print_r($lim);
	// echo "</br>";
	// print_r($prev);
	// echo "</br>";
	// print_r($curr_link);
	// echo "</br>";

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
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");
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
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
    
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/content/users/statistics/assets/modal.css"><!--//Статистика-->
	
	<script>
    // ------------------------------------------------------------------------------------------------
    //Установка куки сортировки заказов
    function sortOrders(field)
    {
        var asc_desc = "asc";//Направление по умолчанию
        
        //Берем из куки текущий вариант сортировки
        var current_sort_cookie = getCookie("orders_sort");
        if(current_sort_cookie != undefined)
        {
            current_sort_cookie = JSON.parse(getCookie("orders_sort"));
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
        
        
        var orders_sort = new Object;
        orders_sort.field = field;//Поле, по которому сортировать
        orders_sort.asc_desc = asc_desc;//Направление сортировки
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_sort="+JSON.stringify(orders_sort)+"; path=/; expires=" + date.toUTCString();
        
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
        document.cookie = "orders_need_page="+need_page+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
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
				Фильтр заказов <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('На данной странице показаны учетные записи заказов. Для просмотра товарных позиций рекомендуется использовать более продвинутый инструмент &quot;Позиции заказов&quot;, доступный с главной страницы &quot;Панели управления&quot;. Вы также можете открыть страницу с детальной информацией по каждому заказу, нажав на строку нужного заказа в таблице ниже.');"><i class="fa fa-info"></i></button>
			</div>
			<div class="panel-body filter_panel">
				<?php
				
				//Способ оплаты - на странице созданного заказа
				$shop_orders_paid_type = array();
				$query = $db_link->prepare('SELECT * FROM `shop_orders_paid_type` WHERE `active` = 1 ORDER BY `order`;');
				$query->execute();
				while($rov = $query->fetch()){
					$shop_orders_paid_type[$rov['id']] = $rov['name'];
				}
				
				$time_from = "";
				$time_to = "";
				$order_id = "";
				$status = "0";
				$paid = -1;
				$customer = "";
				$customer_id = "";
				$viewed = -1;
				$paid_type = -1;
				$office = 0;
				$phone = "";
				$article = "";
				
				//Получаем текущие значения фильтра:
				$orders_filter = NULL;
				if( isset($_COOKIE["orders_filter"]) )
				{
					$orders_filter = $_COOKIE["orders_filter"];
				}
				if($orders_filter != NULL)
				{
					$orders_filter = json_decode($orders_filter, true);
					$time_from = $orders_filter["time_from"];
					$time_to = $orders_filter["time_to"];
					$order_id = $orders_filter["order_id"];
					$status = $orders_filter["status"];
					$paid = $orders_filter["paid"];
					$customer = $orders_filter["customer"];
					$customer_id = $orders_filter["customer_id"];
					$viewed = $orders_filter["viewed"];
					$paid_type = (int) $orders_filter["paid_type"];
					if( isset($orders_filter["office"]) ){
						$office = $orders_filter["office"];
					}
					if( isset($orders_filter["phone"]) ){
						$phone = $orders_filter["phone"];
					}
					if( isset($orders_filter["article"]) ){
						$article = trim($orders_filter["article"]);
					}
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
							Номер заказа
						</label>
						<div class="col-lg-6">
							<input <?=(!empty($order_id))?'style="background:#b9fcab;"':'';?> type="text" id="order_id" value="<?php echo $order_id; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Оплачен
						</label>
						<div class="col-lg-6" id="paid_div">
							<select multiple="multiple" id="paid">
								<option value="1">Оплачен полностью</option>
								<option value="2">Оплачен частично</option>
								<option value="0">Не оплачен</option>
							</select>
							<script>
								$('#paid').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_filter") != undefined && JSON.parse(getCookie("orders_filter")).paid != -1){
									$('#paid').multipleSelect('setSelects', JSON.parse(getCookie("orders_filter")).paid);
									changeColorFilter('#paid_div');
								}
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Способ оплаты
						</label>
						<div class="col-lg-6" id="paid_type_div">
							<select multiple="multiple" id="paid_type">
								<option value="0">Не определен</option>
								<?php
								foreach($shop_orders_paid_type as $paid_type_id => $paid_type_data)
								{
								?>
									<option value="<?php echo $paid_type_id; ?>"><?php echo $paid_type_data; ?></option>
								<?php
								}
								?>
							</select>
							<script>
								$('#paid_type').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_filter") != undefined && JSON.parse(getCookie("orders_filter")).paid_type != -1){
									$('#paid_type').multipleSelect('setSelects', JSON.parse(getCookie("orders_filter")).paid_type);
									changeColorFilter('#paid_type_div');
								}
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Статус
						</label>
						<div class="col-lg-6" id="status_div">
							<select multiple="multiple" id="status">
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
								$('#status').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_filter") != undefined && JSON.parse(getCookie("orders_filter")).status != 0){
									$('#status').multipleSelect('setSelects', JSON.parse(getCookie("orders_filter")).status);
									changeColorFilter('#status_div');
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
							<input <?=(!empty($customer_id))?'style="background:#b9fcab;"':'';?> type="text" id="customer_id" value="<?php echo $customer_id; ?>" class="form-control" />
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
							<input <?=(!empty($customer))?'style="background:#b9fcab;"':'';?> type="text" id="customer" value="<?php echo $customer; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Просмотрен
						</label>
						<div class="col-lg-6" id="viewed_div">
							<select multiple="multiple" id="viewed">
								<option value="1">Просмотрен</option>
								<option value="0">Не просмотрен</option>
							</select>
							<script>
								$('#viewed').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_filter") != undefined && JSON.parse(getCookie("orders_filter")).viewed != -1){
									$('#viewed').multipleSelect('setSelects', JSON.parse(getCookie("orders_filter")).viewed);
									changeColorFilter('#viewed_div');
								}
							</script>
						</div>
					</div>
				</div>
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Офис
						</label>
						<div class="col-lg-6" id="office_div">
							<select multiple="multiple" id="office">
								<?php
								foreach($offices_list as $office_id => $office_caption)
								{
									echo '<option value="'.$office_id.'">'.$office_caption.'</option>';
								}
								?>
							</select>
							<script>
								$('#office').multipleSelect({ placeholder: "Все", width: "100%" });
								//Инициализируем выбранные значения
								if(getCookie("orders_filter") != undefined && JSON.parse(getCookie("orders_filter")).office != 0){
									$('#office').multipleSelect('setSelects', JSON.parse(getCookie("orders_filter")).office);
									changeColorFilter('#office_div');
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
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Артикул
						</label>
						<div class="col-lg-6">
							<input <?=(!empty($article))?'style="background:#b9fcab;"':'';?> type="text" id="article" value="<?php echo trim($article); ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				
				
			</div>
			<script>
			$(".filter_panel .form-control").keyup(function(event){
				if(event.keyCode == 13){
					filterOrders();
				}
			});
			</script>
			<div class="panel-footer">
				<button class="btn btn-success" type="button" onclick="filterOrders();"><i class="fa fa-filter"></i> Отфильтровать</button>
				<button class="btn btn-primary" type="button" onclick="unsetFilterOrders();"><i class="fa fa-square"></i> Снять фильтры</button>
				<button class="btn btn-info" type="button" onclick="ordersInProcess();"><i class="fa fa-toolbox"></i> Заказы в работе</button>
				<button class="btn btn-ar btn-warning pull-right" onclick="show_orders_items();"><i class="fa fa-chevron-down"></i> Отобразить позиции</button>
			</div>
		</div>
	</div>
	
	
	
	<style>
	@media screen and (max-width: 991px) {
		.panel-footer .btn{
			margin-bottom:5px;
		}
		.btn[onclick="show_orders_items();"]{
			float:none !important;
		}
	}
	</style>
	<script>
	//Отобразить блок с позициями заказа
	function show_orders_items(){
		if($('.orders-items-tr').hasClass('hidden')){
			$('.orders-items-tr').removeClass('hidden');
		}else{
			$('.orders-items-tr').addClass('hidden');
		}
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
    function filterOrders()
    {
        var orders_filter = new Object;
        
        //1. Время с
        orders_filter.time_from = document.getElementById("time_from").value;
        //2. Время по
        orders_filter.time_to = document.getElementById("time_to").value;
        
        //3. Номер заказа
        orders_filter.order_id = document.getElementById("order_id").value;
        
        //4. Статус заказа
        if ($('#status').multipleSelect('getSelects', "value").length == 0) {orders_filter.status = 0;}
		else orders_filter.status = $('#status').multipleSelect('getSelects', "value");
        
        //5. Оплачен
        if ($('#paid').multipleSelect('getSelects', "value").length == 0) {orders_filter.paid = -1;}
		else orders_filter.paid = $('#paid').multipleSelect('getSelects', "value");
        
        //6. Покупатель
        orders_filter.customer = encodeURIComponent(document.getElementById("customer").value);
        orders_filter.customer_id = encodeURIComponent(document.getElementById("customer_id").value);
        
		//7. Просмотрен
		if ($('#viewed').multipleSelect('getSelects', "value").length == 0) {orders_filter.viewed = -1;}
		else orders_filter.viewed = $('#viewed').multipleSelect('getSelects', "value");
		
		//8. Способ оплаты
        if ($('#paid_type').multipleSelect('getSelects', "value").length == 0) {orders_filter.paid_type = -1;}
		else orders_filter.paid_type = $('#paid_type').multipleSelect('getSelects', "value"); 
		
		//9. Офис
        if ($('#office').multipleSelect('getSelects', "value").length == 0) {orders_filter.office = 0;}
		else orders_filter.office = $('#office').multipleSelect('getSelects', "value"); 
		
		//10. Телефон клиента
        orders_filter.phone = encodeURIComponent(document.getElementById("phone").value);
		
		//11. Артикул
        orders_filter.article = encodeURIComponent(document.getElementById("article").value);
		
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_filter="+JSON.stringify(orders_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        goToPage(0);
    }
    // ------------------------------------------------------------------------------------------------
    //Снять все фильтры
    function unsetFilterOrders()
    {
        var orders_filter = new Object;
        
        //1. Время с
        orders_filter.time_from = "";
        //2. Время по
        orders_filter.time_to = "";
        
        //3. Номер заказа
        orders_filter.order_id = "";
        
        //4. Статус заказа
        orders_filter.status = 0;
        
        //5. Товар
        orders_filter.paid = -1;
        
        //6. Покупатель
        orders_filter.customer = "";
        orders_filter.customer_id = "";
		
		//7. Просмотрен
		orders_filter.viewed = -1;
        
		//8. Способ оплаты
        orders_filter.paid_type = -1;
		
		//9. Офис
        orders_filter.office = 0;
		
		//10. Телефон клиента
        orders_filter.phone = "";
		
		//11. Артикул
        orders_filter.article = "";
		
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_filter="+JSON.stringify(orders_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        goToPage(0);
    }
    // ------------------------------------------------------------------------------------------------
	//Заказы в работе - кнопка
	<?php
	//Получаем список статусов заказа кроме тех что с флагом "for_inverse" - заказ отменен и "for_finish" - заказ выполнен
	$orders_statuses_InProcess = array();
	$query = $db_link->prepare("SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_inverse` != 1 && `for_finish` != 1 && `for_created` != 1;");
	$query->execute();
	while($status_row = $query->fetch() )
	{
		$orders_statuses_InProcess[] = (string)$status_row["id"];
	}
	?>
	function ordersInProcess(){
		var orders_filter = new Object;

		//1. Время с
		orders_filter.time_from = "";
		//2. Время по
		orders_filter.time_to = "";

		//3. Номер заказа
		orders_filter.order_id = "";
		
		<?php
		if(isset($_GET['status_id'])){
		?>
		//4. Статус заказа
		orders_filter.status = <?php  echo '['.(int)$_GET['status_id'].']'; ?>;
		<?php
		}else{
		?>
		//4. Статус заказа
		orders_filter.status = <?php  echo '['.implode(',', $orders_statuses_InProcess).']'; ?>;
		<?php
		}
		?>

		//5. Товар
		orders_filter.paid = -1;

		//6. Покупатель
		orders_filter.customer = "";
		orders_filter.customer_id = "";

		//7. Просмотрен
		orders_filter.viewed = -1;

		//8. Способ оплаты
		orders_filter.paid_type = -1;

		//9. Офис
		orders_filter.office = 0;

		//10. Телефон клиента
		orders_filter.phone = "";

		//11. Артикул
		orders_filter.article = "";

		//Устанавливаем cookie (на полгода)
		var date = new Date(new Date().getTime() + 15552000 * 1000);
		document.cookie = "orders_filter=" + JSON.stringify(orders_filter) + "; path=/; expires=" + date.toUTCString();

		//Обновляем страницу
		goToPage(0);
	}
	<?php
	if(isset($_GET['status_id'])){
	?>
	ordersInProcess();
	<?php
	}
	?>
    // ------------------------------------------------------------------------------------------------
    </script>

    
    
    
    
    
    
    
    
    
    
    
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Заказы
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<?php
					//Определяем текущую сортировку и обозначаем ее:
					$orders_sort = null;
					if( isset($_COOKIE["orders_sort"]) )
					{
						$orders_sort = $_COOKIE["orders_sort"];
					}
					$sort_field = "id";
					$sort_asc_desc = "desc";
					if($orders_sort != NULL)
					{
						$orders_sort = json_decode($orders_sort, true);
						$sort_field = $orders_sort["field"];
						$sort_asc_desc = $orders_sort["asc_desc"];
					}
					
					if( strtolower($sort_asc_desc) == "asc" )
					{
						$sort_asc_desc = "asc";
					}
					else
					{
						$sort_asc_desc = "desc";
					}
					
					if( array_search($sort_field, array('id', 'time', 'price_sum', 'price_purchase', 'profit', 'paid', 'paid_type', 'status', 'obtain_caption', 'customer', 'office_id', 'checks_count', 'count_items')) === false )
					{
						$sort_field = "id";
					}
					
					
					
					
					
					//Формируем часть SQL-запроса для покупателя (отдельно, чтобы можно было использовать и для WHERE)
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
						
						$users_profile_SQL = $users_profile_SQL." IF( IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = '".$field_name."' AND `user_id` = `shop_orders`.`user_id`), '') != '' , CONCAT(', ', (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = '".$field_name."' AND `user_id` = `shop_orders`.`user_id`)),'') ";
					}
					if( $users_profile_SQL != "" )
					{
						$users_profile_SQL = ",".$users_profile_SQL;
					}
					//SQL-подзапрос компонует строку с данными пользователя
					$SQL_SELECT_CUSTOMER = " IF( `user_id` = 0, CONCAT('Незарегистрированный (ID 0), ', 'Телефон: ' , IF((`phone_not_auth`='' OR `phone_not_auth` IS NULL), '', CONCAT(', Телефон: ', `phone_not_auth` )), IF( `email_not_auth`=''  OR `email_not_auth` IS NULL, '', CONCAT(', E-mail: ', `email_not_auth` )))  , CONCAT( 'ID ', `user_id`, ', E-mail: ', (SELECT IF(`email`!='', `email`, 'Не указан') FROM `users` WHERE `user_id` = `shop_orders`.`user_id` LiMIT 1 ), ', Телефон: ', (SELECT IF(`phone`!='', `phone`, 'Не указан') FROM `users` WHERE `user_id` = `shop_orders`.`user_id` LiMIT 1 ) ".$users_profile_SQL." ) )";
					
					
					
					
					
					
					
					//Подстрока с условиями фильтрования заказов
					$WHERE_CONDITIONS = " WHERE ";
					$binding_values = array();
					//По офисам обслуживания - только те, с котроми работает данный менеджер
					$sub_WHERE_offices = "";
					foreach($offices_list as $office_id => $office_caption)
					{
						if(isset($orders_filter["office"]) && $orders_filter["office"] != 0 && !in_array($office_id, $orders_filter["office"]))
						{
							continue;//Если выбран в фильтре офис
						}
						if($sub_WHERE_offices != "")$sub_WHERE_offices .= " OR ";
						$sub_WHERE_offices .= "`office_id`=?";
						
						
						array_push( $binding_values, $office_id );
					}
					$WHERE_CONDITIONS .= "(".$sub_WHERE_offices.")";
					
					//По куки фильтра:
					$orders_filter = NULL;
					if( isset($_COOKIE["orders_filter"]) )
					{
						$orders_filter = $_COOKIE["orders_filter"];
					}
					if($orders_filter != NULL)
					{
						$orders_filter = json_decode($orders_filter, true);
						
						//1. Время с
						if($orders_filter["time_from"] != "")
						{
							$WHERE_CONDITIONS .= " AND `time` > ?";
							
							array_push( $binding_values, $orders_filter["time_from"] );
						}
						
						//2. Время по
						if($orders_filter["time_to"] != "")
						{
							$WHERE_CONDITIONS .= " AND `time` < ?";
							
							array_push( $binding_values, $orders_filter["time_to"] );
						}
						
						//3. Номер заказа
						if($orders_filter["order_id"] != "")
						{
							$WHERE_CONDITIONS .= " AND `id` = ?";
							
							array_push( $binding_values, $orders_filter["order_id"] );
						}
						
						
						//4. Статус заказа
						if ($orders_filter["status"] != 0)
						{
							$WHERE_CONDITIONS .= " AND `status` IN (".str_repeat("?,", count($orders_filter["status"])-1)."?)";
							$binding_values = array_merge($binding_values, $orders_filter["status"]);
						}
						
						
						//5. Оплата
						if ($orders_filter["paid"] != -1)
						{
							$WHERE_CONDITIONS .= " AND `paid` IN (".str_repeat("?,", count($orders_filter["paid"]) - 1)."?)";
							$binding_values = array_merge($binding_values, $orders_filter["paid"]);

						}
						
						
						//6. Покупатель
						if($orders_filter["customer"] != "" )
						{
							$WHERE_CONDITIONS .= " AND $SQL_SELECT_CUSTOMER LIKE ?";
							
							array_push( $binding_values, "%".htmlentities($orders_filter["customer"])."%");
						}
						
						if($orders_filter["customer_id"] != "" )
						{
							$WHERE_CONDITIONS .= " AND `user_id` = ?";
							
							array_push( $binding_values, $orders_filter["customer_id"]);
						}
						
						
						//7. Просмотрен
						if($orders_filter["viewed"] != -1 && count($orders_filter["viewed"]) == 1)
						{
							$orders_filter["viewed"] = $orders_filter["viewed"][0];
							$WHERE_CONDITIONS .= " AND IFNULL( (SELECT `viewed_flag` FROM `shop_orders_viewed` WHERE `order_id` = `shop_orders`.`id` AND `user_id` = ? LIMIT 1), 1 ) = ?";
							
							array_push( $binding_values, $manager_id);
							array_push( $binding_values, $orders_filter["viewed"][0]);
						}
						
						//8. Способ оплаты
						if ($orders_filter["paid_type"] != -1)
						{
							$WHERE_CONDITIONS .= " AND `paid_type` IN (".str_repeat("?,", count($orders_filter["paid_type"])-1)."?)";
							$binding_values = array_merge($binding_values, $orders_filter["paid_type"]);

						}
						
						//10. Телефон клиента
						if($orders_filter["phone"] != "")
						{
							$WHERE_CONDITIONS .= " AND $SQL_SELECT_CUSTOMER LIKE ?";
							
							array_push($binding_values, "%".htmlentities($orders_filter["phone"])."%");
						}
						
						//11. Артикул
						if(trim($orders_filter["article"]) != "")
						{
							$WHERE_CONDITIONS .= " AND `id` IN(SELECT DISTINCT `order_id` FROM `shop_orders_items` WHERE LOWER(`t2_article`) LIKE ?)";
							
							array_push($binding_values, '%'.mb_strtolower(trim($orders_filter["article"])).'%');
						}
					}
					
					
					//Подстрока с условиями фильтрования статусов позиций, которые не участвуют в ценовых расчетах
					$WHERE_statuses_not_count = "";
					$WHERE_statuses_not_count_without_and = "";
					for($i=0; $i<count($orders_items_statuses_not_count); $i++)
					{
						$WHERE_statuses_not_count .= " AND `status` != ".(int)$orders_items_statuses_not_count[$i];
						
						if($i > 0)$WHERE_statuses_not_count_without_and .= " AND ";
						$WHERE_statuses_not_count_without_and .= " `status` != ".(int)$orders_items_statuses_not_count[$i];
					}

					
					//ОБЕСПЕЧИВАЕМ ПОСТРАНИЧНЫЙ ВЫВОД:
					//---------------------------------------------------------------------------------------------->

					//Определяем, с какой страницы начать вывод:
					$s_page = 0;
					if( isset($_COOKIE['orders_need_page']) )
					{
						$s_page = (int) $_COOKIE['orders_need_page'];
					}


					//Определяем сколько пропустить записей для выборки
					$p = $DP_Config->list_page_limit;//Штук на страницу
					$start_elements_of_page = abs($s_page * $p);

					// print_r($SQL_SELECT_ITEMS_LIMIT);

					$elements_counter = 0;
					//----------------------------------------------------------------------------------------------|
				
					
					
					$SQL_SELECT_ORDERS = "SELECT SQL_CALC_FOUND_ROWS `shop_orders`.`id` AS `id`, ";
					$SQL_SELECT_ORDERS .= "`shop_orders`.`time` AS `time`, ";
					$SQL_SELECT_ORDERS .= "`shop_orders`.`paid` AS `paid`, ";
					$SQL_SELECT_ORDERS .= "`shop_orders`.`paid_type` AS `paid_type`, ";
					$SQL_SELECT_ORDERS .= "`shop_orders`.`status` AS `status`, ";
					$SQL_SELECT_ORDERS .= " (SELECT `caption` FROM `shop_obtaining_modes` WHERE `id` = `shop_orders`.`how_get`) AS `obtain_caption`, ";
					$SQL_SELECT_ORDERS .= "`shop_orders`.`status` AS `status`, ";
					$SQL_SELECT_ORDERS .= "`shop_orders`.`user_id` AS `user_id`, ";//Статистика
					
					$SQL_SELECT_ORDERS .= " $SQL_SELECT_CUSTOMER AS `customer`,";
					
					
					
					
					$SQL_SELECT_ORDERS .= "`shop_orders`.`office_id` AS `office_id`, ";

					//Количество позиций в заказе
					$SQL_SELECT_ORDERS .= "(SELECT COUNT(*) FROM `shop_orders_items` WHERE `order_id` = `shop_orders`.`id` ) AS `count_items`,";
					
					//Сумма заказа
					$sql_select_order_sum = " CAST( (SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) AS DECIMAL(20,2)) ";
					$SQL_SELECT_ORDERS .= $sql_select_order_sum." AS `price_sum`,";
					
					$sql_select_order_purchase = "((CAST( IFNULL( (SELECT SUM(`price_purchase`*(`count_reserved`+`count_issued`)) FROM `shop_orders_items_details` WHERE `order_id`= `shop_orders`.`id` AND `order_item_id` IN (SELECT `id` FROM `shop_orders_items` WHERE $WHERE_statuses_not_count_without_and) ), 0 ) AS DECIMAL(20,2) ) ) + (CAST( IFNULL( (SELECT SUM(`t2_price_purchase`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` AND $WHERE_statuses_not_count_without_and),  0) AS DECIMAL(20,2) ) ))";
					
					
					
					
					$SQL_SELECT_ORDERS .= $sql_select_order_purchase." AS `price_purchase`,";//Сумма закупа
					
					//Прибыль
					$SQL_SELECT_ORDERS .= " $sql_select_order_sum - $sql_select_order_purchase  AS `profit`, ";
					
					
					//Флаг "Просмотрен"
					$SQL_SELECT_ORDERS .= " IFNULL( (SELECT `viewed_flag` FROM `shop_orders_viewed` WHERE `order_id` = `shop_orders`.`id` AND `user_id` = ? LIMIT 1), 1 ) AS `viewed_flag`, ";
					
					array_unshift( $binding_values, (int)$manager_id );
					
					
					//Количество непрочитанных сообщений
					$SQL_SELECT_ORDERS .= " (SELECT COUNT(*) FROM `shop_orders_messages` WHERE `order_id` = `shop_orders`.`id` AND `read` = 0 AND `is_customer` = 1) AS `count_not_viewed_msg`, ";
					
					if(isset($_GET["read"]) && (int)$_GET["read"] === 0)
					{
						$WHERE_CONDITIONS .= " AND `id` IN(SELECT DISTINCT `order_id` FROM `shop_orders_messages` WHERE `read` = 0 AND `is_customer` = 1) ";
					}
					
					if(isset($_GET["cancel_flag"]) && (int)$_GET["cancel_flag"] === 1)
					{
						$WHERE_CONDITIONS .= " AND `flag_canceled_items` = 1 ";
					}
					
					
					//Количество чеков, привязанных к позициям заказа
					//$SQL_SELECT_ORDERS .= "(SELECT `id` FROM `shop_kkt_checks_products_to_orders_items_map` WHERE `order_item_id` IN (SELECT `id` FROM `shop_orders_items` WHERE `order_id` = `shop_orders`.`id` ) ) AS `checks_count`";
					$SQL_SELECT_ORDERS .= "(SELECT COUNT(DISTINCT(`check_id`)) FROM `shop_kkt_checks_products` WHERE `id` IN (SELECT `check_product_id` FROM `shop_kkt_checks_products_to_orders_items_map` WHERE `order_item_id` IN (SELECT `id` FROM `shop_orders_items` WHERE `order_id` = `shop_orders`.`id` ) ) ) AS `checks_count`";
					
					$SQL_SELECT_ORDERS .= " FROM `shop_orders` $WHERE_CONDITIONS ORDER BY `$sort_field` $sort_asc_desc LIMIT $start_elements_of_page,$p";
					
					//echo $SQL_SELECT_ORDERS;
					
	
					$elements_query = $db_link->prepare($SQL_SELECT_ORDERS);
					$elements_query->execute($binding_values);
					//var_dump($elements_query->fetch());
					//var_dump($SQL_SELECT_ORDERS);
					//var_dump($binding_values);
					
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
					//Получаем суммарные показатели
					$SQL_SELECT_TOTAL_INDICATORS = "SELECT 
						COUNT(*) AS `orders_count`,
						SUM($sql_select_order_sum) AS `price_sum_total`,
						SUM($sql_select_order_sum - $sql_select_order_purchase) AS `profit_sum_total`,
						SUM($sql_select_order_purchase) AS `price_purchase_sum_total`
						FROM `shop_orders` $WHERE_CONDITIONS ";
					
					//Удаляем первый элемент массива связанных значений (это был manager_id для флага "Просмотрен")
					array_shift($binding_values);
					
					$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);
					$total_indicators_query->execute($binding_values);
					$total_indicators = $total_indicators_query->fetch();
					// -------------------------------------------------------------------
					
					//Массив заказов
					$orders = array();
					$orders_id_list = array();
					while($record = $elements_query->fetch())
					{
						$orders[] = $record;
						$orders_id_list[] = $record["id"];
					}
					
					//Формируем список позиций заказов
					$orders_items = array();
					$orders_items_query = $db_link->prepare("SELECT * FROM `shop_orders_items` WHERE `order_id` IN(".implode(',', $orders_id_list).");");
					$orders_items_query->execute();
					while($record = $orders_items_query->fetch())
					{
						$orders_items[$record['order_id']][] = $record;
					}
					
					//Массивы для JS с id элементов и с чекбоксами элементов
					$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
					$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
									
					//Далее идет вывод таблицы с плагином footable. При этом постраничный вывод обеспечивает PHP. Поэтому, в таблицу ставятся параметры data-sort="false", data-page-size = всему количеству записей, <tfoot style="display:none;"> - заглушка, чтобы JS не затрагивал свой переключатель станиц
					?>
					<table id="orders_table" class="footable table table-hover toggle-arrow" data-sort="false" data-page-size="<?php echo ($elements_count_rows * 2); ?>">
						<thead>
							<tr>
								<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();" /></th>
								<th data-toggle="true"></th>
								<th><a href="javascript:void(0);" onclick="sortOrders('id');" id="id_sorter">ID</a></th>
								<th><a href="javascript:void(0);" onclick="sortOrders('time');" id="time_sorter">Дата</a></th>
								<th><a href="javascript:void(0);" onclick="sortOrders('count_items');" id="count_items_sorter">Позиций</a></th>
								<th><a href="javascript:void(0);" onclick="sortOrders('price_sum');" id="price_sum_sorter">Сумма</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('price_purchase');" id="price_purchase_sorter">Закуп</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('profit');" id="profit_sorter">Маржа</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('paid');" id="paid_sorter">Оплачен</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('paid_type');" id="paid_type_sorter">Способ оплаты</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('status');" id="status_sorter">Статус</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('obtain_caption');" id="obtain_caption_sorter">Способ получения</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('customer');" id="customer_sorter">Покупатель</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('checks_count');" id="checks_count_sorter">Чеки</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('office_id');" id="office_id_sorter">Офис</a></th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach($orders as $element_record)
						{
							$customer_id =  $element_record['user_id'];//Статистика
							
							if($elements_counter >= $s_page*$p+$p)
							{
								break;
							}
							$elements_counter++;
							
							
							//Для Javascript
							$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$element_record["id"]."\";\n";//Добавляем элемент для JS
							$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$element_record["id"].";\n";//Добавляем элемент для JS
							
							
							$order_id = $element_record["id"];
							$time = $element_record["time"];
							$count_items = $element_record["count_items"];
							$price_sum = $element_record["price_sum"];
							$price_purchase = $element_record["price_purchase"];
							$profit = $element_record["profit"];
							$paid = $element_record["paid"];
							$paid_type = $element_record["paid_type"];
							$status = $element_record["status"];
							$obtain_caption = $element_record["obtain_caption"];
							$customer = $element_record["customer"];
							$office_id = $element_record["office_id"];
							
							
							//Флаг "Заказ просмотрен"
							$viewed_class = "";
							$viewed_flag = $element_record["viewed_flag"];
							if( $viewed_flag == 0)
							{
								$viewed_class = " not_viewed";
							}
							
							
							
							//Чеки
							$order_checks_count = $element_record["checks_count"];
							if( $order_checks_count == 0 )
							{
								$order_checks_count = "Нет";
							}
							else
							{
								$order_checks_count = "<span style=\"cursor:pointer;\" onclick=\"show_order_checks(".$order_id.");\">".$order_checks_count." <i class=\"fas fa-search\"></i></span>";
							}
							
							
							$a_item = "<a href=\"".$DP_Config->domain_path.$DP_Config->backend_dir."/shop/orders/order?order_id=".$order_id."\">";
							
							?>
							<tr class="<?php echo $viewed_class; ?>" style="background-color:<?php echo $orders_statuses[$status]["color"]; ?>">
								<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $element_record["id"]; ?>');" id="checked_<?php echo $element_record["id"]; ?>" name="checked_<?php echo $element_record["id"]; ?>" /></td>
								<td>
								<?php
								// Индикатор непрочитанных сообщений в заказах
								if($element_record["count_not_viewed_msg"] > 0){
								?>
								<a style="white-space: nowrap;" class="dropdown-toggle label-menu-corner" href="<?=$DP_Config->domain_path.$DP_Config->backend_dir;?>/shop/orders/order?order_id=<?=$order_id;?>">
									<i style="font-size: 25px;" class="pe-7s-mail"></i>
									<span style="font-size: 9px;"><?=$element_record["count_not_viewed_msg"];?></span>
								</a>
								<?php
								}
								?>
								</td>
								<td><?php echo $a_item.$order_id; ?></a></td>
								<td><?php echo $a_item.date("d.m.Y", $time)."<br>".date("G:i", $time); ?></a></td>
								<td><?php echo $a_item.$count_items; ?></a></td>
								<td><?php echo $a_item.number_format($price_sum, 2, '.', ''); ?></a></td>
								<td><?php echo $a_item.number_format($price_purchase, 2, '.', ''); ?></a></td>
								<td><?php echo $a_item.number_format($profit, 2, '.', ''); ?></a></td>
								<td>
									<?php
									if($paid == 1)
									{
										echo $a_item."Оплачен<br>полностью";
									}
									else if($paid == 0)
									{
										echo $a_item."Не оплачен";
									}
									else
									{
										echo $a_item."Оплачен<br>частично";
									}
									?></a>
								</td>
								<td><?=(!empty($shop_orders_paid_type[$paid_type]))?$a_item.$shop_orders_paid_type[$paid_type]:$a_item.'';?></a></td>
								<td><?php echo $a_item.$orders_statuses[$status]["name"]; ?></a></td>
								<td><?php echo $a_item.$obtain_caption; ?></a></td>
								<td><?php include $_SERVER['DOCUMENT_ROOT'].'/'.$DP_Config->backend_dir.'/content/users/statistics/modal.php';//Статистика?><?php echo $a_item.$customer; ?></a></td>
								<td><?php echo $order_checks_count; ?></td>
								<td><?php echo $a_item.$offices_list[$office_id]; ?></a></td>
							</tr>
							
							
							
							<?php
							//Отображаем позиции заказа
							if(!empty($orders_items[$element_record["id"]])){
								?>
								<tr class="orders-items-tr hidden">
									<td colspan="15" style="padding:0;">
										<div style="background-color:<?php echo $orders_statuses[$status]["color"]; ?>; margin-bottom: 50px; clear: both; display: inline-block; float: right; padding: 20px 30px; position: relative; top: -1px; width:100%; max-width:900px;">
										<table class="table" style="margin: 0; font-size: 12px;">
										<tr>
											<td style="padding: 5px; line-height: 1em; vertical-align: middle;" colspan="4"><b>Позиции заказа:</b></td>
										</tr>
										<?php
										foreach($orders_items[$element_record["id"]] as $order_item){
										?>
											<tr style="background:<?php echo $orders_items_statuses[$order_item['status']]["color"]; ?>">
												<td style="padding: 5px; line-height: 1em; vertical-align: middle; width:20%;"><?php echo $order_item['t2_manufacturer']; ?></td>
												<td style="padding: 5px; line-height: 1em; vertical-align: middle; width:20%;"><?php echo $order_item['t2_article']; ?></td>
												<td style="padding: 5px; line-height: 1em; vertical-align: middle; width:40%;"><?php echo $order_item['t2_name']; ?></td>
												<td style="padding: 5px; line-height: 1em; vertical-align: middle;"><?php echo $orders_items_statuses[$order_item['status']]["name"]; ?></td>
											</tr>
										<?php
										}
										?>
										</table>
										</div>
									</td>
								</tr>
								<?php
							}
							?>
							
							
							
							<?php
						}
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
		</div>
	</div>
	
	
	
	
	
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия с отмеченными
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="" class="col-lg-2 control-label">
						Присвоить статус
					</label>
					<div class="col-lg-8">
						<select id="setOrderStatusSelect" class="form-control">
							<?php
							foreach($orders_statuses as $status_id=>$status_data)
							{
								?>
								<option value="<?php echo $status_id; ?>" ><?php echo $status_data["name"]; ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="col-lg-2">
						<button class="btn w-xs btn-success" onclick="setOrdersStatus();">Выполнить</button>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-2 control-label">
						Установить статус просмотра
					</label>
					<div class="col-lg-8">
						<select id="setOrderViewed" class="form-control">
							<option value="1">Просмотрен</option>
							<option value="0">Не просмотрен</option>
						</select>
					</div>
					<div class="col-lg-2">
						<button class="btn w-xs btn-success" onclick="setOrderViewed();">Выполнить</button>
					</div>
				</div>
				
				
				
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<div class="col-lg-12">
						<button class="btn btn-danger" type="button" onclick="deleteSelectedeOrders();"><i class="fa fa-trash-o"></i> <span class="bold">Удалить отмеченные заказы</span></button>
					</div>
				</div>
				
				
				
				
			</div>
		</div>
	</div>
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Показатели по заказам с учетом фильтра <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('В этом блоке приведены показатели по всем заказам с учетом фильтра. Если заказы в таблице выше не помещаются на одну страницу (т.е. требуется переключать страницы), то суммарные показатели рассчитываются, не смотря на это - со всех страниц по всем заказам, которые соответствуют настройкам фильтра (фильтр расположен вверху на данной странице).');"><i class="fa fa-info"></i></button>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-lg-12 text-center">
						<div class="table-responsive">
							<table cellpadding="1" cellspacing="1" class="table">
								<thead>
									<tr>
										<th style="text-align:center;">Всего заказов</th>
										<th style="text-align:center;">Сумма по заказам</th>
										<th style="text-align:center;">Маржа по заказам</th>
										<th style="text-align:center;">Закуп по заказам</th>
									</tr>
								</thead>
								<tbody>
									<tr>
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
    
    
	
    <?php
	//Статусы заказов:

	//Получаем список статусов заказа с флагом "for_finish" - заказ выполнен
	$orders_statuses_for_finish = array();
	$query = $db_link->prepare("SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_finish` = 1;");
	$query->execute();
	while($status_row = $query->fetch() )
	{
		$orders_statuses_for_finish[] = $status_row["id"];
	}
	
	//Получаем список статусов заказа с флагом "for_inverse" - заказ отменен
	$orders_statuses_for_inverse = array();
	$query = $db_link->prepare("SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_inverse` = 1;");
	$query->execute();
	while($status_row = $query->fetch() )
	{
		$orders_statuses_for_inverse[] = $status_row["id"];
	}
	
	?>
    <script>
		// -----------------------------------------------------------------------------------------------------------
		
		var orders_statuses_for_finish = JSON.parse('<?php echo json_encode($orders_statuses_for_finish); ?>');
		var orders_statuses_for_inverse = JSON.parse('<?php echo json_encode($orders_statuses_for_inverse); ?>');
		
        //Выставить статус для заказов
        function setOrdersStatus()
        {
            var checkedOrders = getCheckedElements();//Список отмеченных заказов
            if(checkedOrders.length == 0)
            {
                alert("Выберите заказы из списка");
                return;
            }
            
            var needStatus = document.getElementById("setOrderStatusSelect").value;
            
			if(orders_statuses_for_finish.indexOf(needStatus) !== -1){
				if( !confirm("Помимо смены статуса заказа, всем его не отмененным позициям так же будет изменен статус автоматически. Продолжить?") )
				{
					return;
				}
			}
			
			if(orders_statuses_for_inverse.indexOf(needStatus) !== -1){
				if( !confirm("Помимо смены статуса заказа, всем его позициям так же будет изменен статус автоматически. Все платежи по заказу будут возвращены. Продолжить?") )
				{
					return;
				}
			}
			
            jQuery.ajax({
                    type: "GET",
                    async: false, //Запрос синхронный
                    url: "/content/shop/protocol/set_order_status.php",
                    dataType: "json",//Тип возвращаемого значения
                    data: "initiator=1&orders="+JSON.stringify(checkedOrders)+"&status="+needStatus+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                    success: function(answer)
                    {
                        if(answer.status == true)
                        {
                            //Обновляем страницу
                            location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
                        }
                        else
                        {
                            if(answer.message){
								alert("Ошибка изменения статуса. "+answer.message);
							}else{
								alert("Ошибка изменения статуса");
							}
                        }
                    }
            	});
        }
		// -----------------------------------------------------------------------------------------------------------
		//Установить статус просмотра для заказов
		function setOrderViewed()
		{
			var orders_checked = getCheckedElements();
			
			if(orders_checked.length == 0)
			{
				alert("Не отмечены заказы");
				return;
			}
			
			//Объект запроса
			var request_object = new Object;
			request_object.orders = orders_checked;
			request_object.viewed_flag = document.getElementById("setOrderViewed").value;
			request_object.user_id = <?php echo $manager_id; ?>;

			jQuery.ajax({
				type: "POST",
				async: true, //Запрос асинхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_set_orders_viewed.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+JSON.stringify(request_object)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					if(answer.status == true)
					{
						//Обновляем страницу
						location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
					}
					else
					{
						alert("Ошибка сервера");
					}
				}
			});
			
		}
		// -----------------------------------------------------------------------------------------------------------
		//Удалить отмеченные заказы
		function deleteSelectedeOrders()
		{
			var checkedOrders = getCheckedElements();//Список отмеченных заказов
            if(checkedOrders.length == 0)
            {
                alert("Выберите заказы из списка");
                return;
            }
			
			
			if( !confirm("Отмеченные заказы будут безвозвратно удалены. Продолжить?") )
			{
				return;
			}
			
			
			
			jQuery.ajax({
				type: "POST",
				async: true, //Запрос асинхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_delete_orders.php",
				dataType: "json",//Тип возвращаемого значения
				data: "orders_list="+JSON.stringify(checkedOrders)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{	
					if(answer.status == true)
					{
						//Обновляем страницу
						location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
					}
					else
					{
						alert(answer.message);
					}
				}
			});
		}
		// -----------------------------------------------------------------------------------------------------------
    </script>
	
	
	
	
	
	
	
	
	
	
	
	
	<script>
		jQuery( window ).load(function() {
			$('#orders_table').footable();
			
			document.getElementById("<?php echo $sort_field; ?>_sorter").innerHTML += "<img src=\"/content/files/images/sort_<?php echo $sort_asc_desc; ?>.png\" style=\"width:15px\" />";
		});
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
    
}//~else//Действий нет - выводим страницу
?>