<?php

defined('_ASTEXE_') or die('No access');







//Для работы с пользователями

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");



//Проверяем право менеджера

if( ! DP_User::isAdmin() )

{

	$result["status"] = false;

	$result["message"] = "Forbidden";

	$result["code"] = 501;

	exit(json_encode($result));//Вообще не является администратором бэкенда

}











$manager_id = DP_User::getAdminId();//ID менежера, который отображает эту страницу



// Получаем список магазинов в которых данный пользователь указан в качестве менеджера

$manager_offices_list = array();

$query = $db_link->prepare('SELECT `id`, `caption` FROM `shop_offices` WHERE `users` LIKE ?;');

$query->execute( array('%"'.$manager_id.'"%') );

while($row = $query->fetch(PDO::FETCH_ASSOC)){

	$manager_offices_list[] = $row;

}











$statistical_settings = array();// Настройки статистики



if( isset($_COOKIE["statistical"]) ){

	$statistical_settings = json_decode($_COOKIE["statistical"], true);

}



if(empty($statistical_settings)){

	$statistical_settings["office_id"] = '-1';

	$statistical_settings["period"] = 'week';

	$statistical_settings["previous_period"] = '0';

	$statistical_settings["type"] = 'orders';

	$statistical_settings["user_rating"] = 'order_sum';

	$statistical_settings["product_rating"] = 'products';

}else{

	$tmp = $statistical_settings["office_id"];

	foreach($manager_offices_list as $item){

		if($tmp == $item['id']){

			$statistical_settings["office_id"] = $item['id'];

			break;

		}else{

			$statistical_settings["office_id"] = '-1';// Если не является менеджером выбранного магазина, сбросим фильтр

		}

	}

}



if($statistical_settings["office_id"] != '-1'){

	$WHERE_CONDITIONS_office_id = " `office_id` = ". ((int) $statistical_settings["office_id"]) ." AND ";

}else{

	$tmp = '';

	foreach($manager_offices_list as $item){

		if($tmp != ''){

			$tmp .= ', ';

		}

		$tmp .= $item['id'];

	}

	$WHERE_CONDITIONS_office_id = " `office_id` IN(".$tmp.") AND ";

}









$class = '';

$time_start = 0;

$time_end = time();

$month_list = array(1=>'Январь', 2=>'Февраль', 3=>'Март', 4=>'Апрель', 5=>'Май', 6=>'Июнь', 7=>'Июль', 8=>'Август', 9=>'Сентябрь', 10=>'Октябрь', 11=>'Ноябрь', 12=>'Декабрь');

$week_list = array(1=>'Понедельник', 2=>'Вторник', 3=>'Среда', 4=>'Четверг', 5=>'Пятница', 6=>'Суббота', 7=>'Воскресенье');



// Статистический период

switch($statistical_settings["period"])

{

	case 'day' :

		// День

		$format = 'G';

		$arr_key = array();

		for($i=0; $i<24; $i++){

			$arr_key[$i] = $i;

		}

		$time_start = mktime(0, 0, 0, date('n', time()), date('j', time()), date('Y', time()));// Метка времени с начала текущего дня

		if($statistical_settings["previous_period"] == 1){

			$time_end = $time_start;

			$time_start = ($time_start - 86400);// Метка времени с начала предыдущего дня

		}

	break;

	case 'week' :

		// Неделя

		$format = 'N';

		$arr_key = $week_list;

		$time_start = strtotime("last Monday");// Метка времени с начала текущего понедельника

		if($statistical_settings["previous_period"] == 1){

			$time_end = $time_start;

			$time_start = strtotime("last Monday", strtotime("last Monday") - 1);// Метка времени с начала предыдущего понедельника

		}

	break;

	case 'month' :

		// Месяц

		$format = 'j';

		$arr_key = array();

		$time_start = mktime(0, 0, 0, date('n', time()), 1, date('Y', time()));// Метка времени с начала текущего месяца

		if($statistical_settings["previous_period"] == 1){

			$time_end = $time_start;

			$time = $time_start - 1;

			$time_start = mktime(0, 0, 0, date('n', $time), 1, date('Y', $time));// Метка времени с начала предыдущего месяца

		}

		for($i=1; $i<=date("t", $time_start); $i++){

			$arr_key[$i] = $i;

		}

	break;

	case 'quarter' :

		// Квартал

		$quarter = array();

		$quarter[1] = array(1,2,3);

		$quarter[2] = array(4,5,6);

		$quarter[3] = array(7,8,9);

		$quarter[4] = array(10,11,12);

		$format = 'n';

		$arr_key = array();

		$tmp = date('n', time());

		foreach($quarter as $k => $item){

			if(in_array($tmp, $item)){

				$tmp = $item[0];

				foreach($item as $val){

					$arr_key[$val] = $month_list[$val];// Временные отсечки на графике

				}

			}

		}

		$time_start = mktime(0, 0, 0, $tmp, 1, date('Y', time()));// Метка времени с начала текущего квартала

		if($statistical_settings["previous_period"] == 1){

			$time_end = $time_start;

			$arr_key = array();

			$time = $time_start - 1;// Уменьшаем число секунд что бы временная метка ссылалась на предыдущий период

			$tmp = date('n', $time);

			foreach($quarter as $k => $item){

				if(in_array($tmp, $item)){

					$tmp = $item[0];

					foreach($item as $val){

						$arr_key[$val] = $month_list[$val];// Временные отсечки на графике

					}

				}

			}

			$time_start = mktime(0, 0, 0, $tmp, 1, date('Y', $time));// Метка времени с начала предыдущего квартала

		}

	break;

	case 'year' :

		// Год

		$format = 'n';

		$arr_key = $month_list;

		$time_start = mktime(0, 0, 0, 1, 1, date('Y', time()));// Метка времени с начала текущего года

		if($statistical_settings["previous_period"] == 1){

			$time_end = $time_start;

			$time_start = mktime(0, 0, 0, 1, 1, ((int) date('Y', time()) - 1));// Метка времени с начала предыдущего года

		}

	break;

	default :

		// За всё время

		$statistical_settings["period"] = 'all';

		$format = 'Y';

		$arr_key = array();

	break;

}









/*

echo '<br>';

var_dump($statistical_settings);

echo '<br>';

echo date("d.m.Y H:i:s", time());

echo '<pre>';

echo '<br>';

echo date("d.m.Y H:i:s", $time_start);

echo '<br>';

echo date("d.m.Y H:i:s", $time_end);

echo '<br>';

echo '</pre>';

*/





























// Технические данные для работы с заказами

require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");



// Подстрока с условиями фильтрования статусов позиций, которые не участвуют в ценовых расчетах

$WHERE_statuses_not_count = "";

$WHERE_statuses_not_count_without_and = "";

for($i=0; $i<count($orders_items_statuses_not_count); $i++)

{

	$WHERE_statuses_not_count .= " AND `status` != ".(int)$orders_items_statuses_not_count[$i];

	

	if($i > 0)$WHERE_statuses_not_count_without_and .= " AND ";

	$WHERE_statuses_not_count_without_and .= " `status` != ".(int)$orders_items_statuses_not_count[$i];

}



// Количество позиций заказа всего

$sql_select_order_count_need_all = " CAST( (SELECT SUM(`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id`) AS DECIMAL(20,2)) ";



// Количество позиций заказа в не отмененном статусе

$sql_select_order_count_need = " CAST( (SELECT SUM(`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) AS DECIMAL(20,2)) ";



// Сумма заказа

$sql_select_order_sum = " CAST( (SELECT SUM(`price`*`count_need`-`price`*`count_need`*`discount`/100) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) AS DECIMAL(20,2)) ";



// Сумма закупа

$sql_select_order_purchase = "((CAST( IFNULL( (SELECT SUM(`price_purchase`*(`count_reserved`+`count_issued`+`count_canceled`)) FROM `shop_orders_items_details` WHERE `order_id`= `shop_orders`.`id` AND `order_item_id` IN (SELECT `id` FROM `shop_orders_items` WHERE $WHERE_statuses_not_count_without_and) ), 0 ) AS DECIMAL(20,2) ) ) + (CAST( IFNULL( (SELECT SUM(`t2_price_purchase`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` AND $WHERE_statuses_not_count_without_and),  0) AS DECIMAL(20,2) ) ))";



// Прибыль

$sql_select_order_profit = "($sql_select_order_sum - $sql_select_order_purchase)";





























//Статусы заказов:



//Получаем список статусов заказа с флагом "for_finish" - заказ выполнен

$orders_statuses_for_finish = array();

$query = $db_link->prepare("SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_finish` = 1;");

$query->execute();

while($status_row = $query->fetch() )

{

	$orders_statuses_for_finish[] = $status_row["id"];

}

if(empty($orders_statuses_for_finish))

{

	$orders_statuses_for_finish[] = 0;

}



//Получаем список статусов заказа с флагом "for_inverse" - заказ отменен

$orders_statuses_for_inverse = array();

$query = $db_link->prepare("SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_inverse` = 1;");

$query->execute();

while($status_row = $query->fetch() )

{

	$orders_statuses_for_inverse[] = $status_row["id"];

}

if(empty($orders_statuses_for_inverse))

{

	$orders_statuses_for_inverse[] = 0;

}



//Статусы позиций:



//Получаем список статусов позиций с флагом "for_inverse" - заказ отменен

$orders_items_statuses_for_inverse = array();

$query = $db_link->prepare("SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `count_flag` = 0 ORDER BY `order` ASC;");

$query->execute();

while($status_row = $query->fetch() )

{

	$orders_items_statuses_for_inverse[] = $status_row["id"];

}

if(empty($orders_items_statuses_for_inverse))

{

	$orders_items_statuses_for_inverse[] = 0;

}



//Получаем список статусов позиций с флагом "for_finish" - позиция выдана

$orders_items_statuses_for_finish = array();

$query = $db_link->prepare("SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `for_finish` = 1 ORDER BY `order` ASC;");

$query->execute();

while($status_row = $query->fetch() )

{

	$orders_items_statuses_for_finish[] = $status_row["id"];

}

if(empty($orders_items_statuses_for_finish))

{

	$orders_items_statuses_for_finish[] = 0;

}



?>





















<script src="vendor/chartjs/Chart.min_2.js"></script>





<div class="col-lg-12" id="statistical_div">

	<div class="row">

		<div class="col-lg-12">

			<div class="hpanel">

				<div class="panel-heading hbuilt">

					<div class="panel-tools">

						<?php

						if(isset($_COOKIE["statistical_mp_hidden"]) && ((int)$_COOKIE["statistical_mp_hidden"] === 1)){

							echo '<a title="Отображать на главной" onClick="statistical_mp_hidden(0);"><i class="fa fa-check"></i> </a>';

						}else{

							echo '<a title="Не отображать на главной" onClick="statistical_mp_hidden(1);"><i class="fa fa-chevron-up"></i></a>';

						}

						?>

						<script>

						function statistical_mp_hidden(flag){

							//Устанавливаем cookie (на полгода)

							var date = new Date(new Date().getTime() + 15552000 * 1000);

							document.cookie = "statistical_mp_hidden="+flag+"; path=/; expires=" + date.toUTCString();

							

							//Обновляем страницу

							location.reload(true);

						}

						</script>

					</div>

					<div class="pull-right">

						<small>

							<select style="border-color: #eeeeee; border-radius: 4px; padding: 0px 0px; line-height: 1em; margin-right: 5px; cursor: pointer; color: #9d9fa2;" id="select_statistical_office" onChange="statistical_settings();">

								<option value="-1">Все магазины</option>

								<?php

								if(is_array($manager_offices_list)){

									foreach($manager_offices_list as $item){

										echo '<option value="'.$item['id'].'">'.$item['id'].' - '.$item['caption'].'</option>';

									}

								}

								?>

							</select>

						</small>

						

						<small>

							<select style="border-color: #eeeeee; border-radius: 4px; padding: 0px 0px; line-height: 1em; margin-right: 5px; cursor: pointer; color: #9d9fa2;" id="select_statistical_period" onChange="statistical_settings();">

								<option value="day">День</option>

								<option value="week">Неделя</option>

								<option value="month">Месяц</option>

								<option value="quarter">Квартал</option>

								<option value="year">Год</option>

								<option value="all">За всё время</option>

							</select>

						</small>

						

						<small style="position: relative;">

							<input <?=($statistical_settings["previous_period"]==1)?'checked':'';?> style="border-color: #eeeeee; border-radius: 4px; line-height: 1em; cursor: pointer; color: #9d9fa2; margin: 0; padding: 0; margin-right: 5px; position: absolute; top: -1px; left: 0; height: 16px; width: 16px;" id="input_previous_period" type="checkbox" onChange="statistical_settings();"/><label style="margin: 0; padding: 0; line-height: 1em; color: #9d9fa2; margin-left: 19px; font-weight: 600; position: relative; top: -1px; margin-right: 5px; cursor: pointer;" for="input_previous_period">Предыдущий период</label>

						</small>

						<script>

							document.getElementById("select_statistical_office").value = '<?php echo $statistical_settings["office_id"]; ?>';

							document.getElementById("select_statistical_period").value = '<?php echo $statistical_settings["period"]; ?>';

							function statistical_settings(type = '', user_rating = '', product_rating = ''){

								let statistical_settings = new Object;

									statistical_settings.office_id = document.getElementById("select_statistical_office").value;

									statistical_settings.period = document.getElementById("select_statistical_period").value;

									statistical_settings.previous_period = (document.getElementById("input_previous_period").checked * 1);

									statistical_settings.type = type;

									

									if(type != ''){

										statistical_settings.type = type;

									}else{

										statistical_settings.type = '<?php echo $statistical_settings["type"]; ?>';

									}

									

									if(user_rating != ''){

										statistical_settings.user_rating = user_rating;

									}else{

										statistical_settings.user_rating = '<?php echo $statistical_settings["user_rating"]; ?>';

									}

									

									if(product_rating != ''){

										statistical_settings.product_rating = product_rating;

									}else{

										statistical_settings.product_rating = '<?php echo $statistical_settings["product_rating"]; ?>';

									}

								

								let date = new Date(new Date().getTime() + 15552000 * 1000);

								document.cookie = "statistical="+JSON.stringify(statistical_settings)+"; path=/; expires=" + date.toUTCString();

								location.reload(true);

							}

						</script>

					</div>

					<style>

					@media (max-width: 1200px) {

						#statistical_div .panel-heading > .pull-right {

							float: none !important;

						}

					}

					</style>

					Статистика основных показателей

				</div>

				<div class="panel-body">

					<div class="row">

						<?php

						

						

						

						

						

						

						

						

						

						

						// ЗАКАЗЫ :

						

						if($statistical_settings["type"] === 'orders')

						{

							//Получаем суммарные показатели

							$SQL_SELECT_TOTAL_INDICATORS = "SELECT 

								COUNT(*) AS `orders_count`, 

								SUM($sql_select_order_count_need) AS `count_need_total`, 

								SUM($sql_select_order_sum) AS `price_sum_total`, 

								SUM($sql_select_order_profit) AS `profit_sum_total` 

								FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?;";

							$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

							$total_indicators_query->execute(array($time_start, ($time_end - 1)));

							$total_indicators = $total_indicators_query->fetch();



							$SQL_SELECT_TOTAL_INDICATORS = "SELECT 

								COUNT(*) AS `orders_count`, 

								SUM($sql_select_order_sum) AS `price_sum_total`, 

								SUM($sql_select_order_profit) AS `profit_sum_total` 

								FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `time` BETWEEN ? AND ?;";

							$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

							$total_indicators_query->execute(array($time_start, ($time_end - 1)));

							$total_indicators_2 = $total_indicators_query->fetch();

							

							$array_all = array();// Всего

							$array_completed = array();// Завершенные

							$array_all_cnt = 0;

							$array_completed_cnt = 0;



							$SQL = "SELECT `time`, `status` FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `time` BETWEEN ? AND ?;";

							$query = $db_link->prepare($SQL);

							$query->execute(array($time_start, ($time_end - 1)));

							while($record = $query->fetch())

							{

								if( ! isset($array_all[date($format, $record['time'])]) ){

									$array_all[date($format, $record['time'])] = 0;

								}

								$array_all[date($format, $record['time'])] += 1;

								$array_all_cnt++;

								if( in_array($record['status'], $orders_statuses_for_finish) ){

									if( ! isset($array_completed[date($format, $record['time'])]) ){

										$array_completed[date($format, $record['time'])] = 0;

									}

									$array_completed[date($format, $record['time'])] += 1;

									$array_completed_cnt++;

								}

							}

							

							// Временные отсечки на графике

							if(empty($arr_key)){

								foreach($array_all as $key => $val){

									$arr_key[$key] = $key;

								}

							}

							$key_str = '';

							foreach($arr_key as $key => $val){

								if($key_str != ''){

									$key_str .= ', ';

								}

								$key_str .= '"'.$val.'"';

							}

							$val_1_str = '';

							foreach($arr_key as $key => $val){

								if($val_1_str != ''){

									$val_1_str .= ', ';

								}

								if( ! isset($array_all[$key]) ){

									$array_all[$key] = 0;

								}

								$val_1_str .= '"'. ((int) $array_all[$key]) .'"';

							}

							$val_2_str = '';

							foreach($arr_key as $key => $val){

								if($val_2_str != ''){

									$val_2_str .= ', ';

								}

								if( ! isset($array_completed[$key]) ){

									$array_completed[$key] = 0;

								}

								$val_2_str .= '"'. ((int) $array_completed[$key]) .'"';

							}

							

							?>

							

							<div class="col-md-12 text-center"><h4>Заказы</h4></div>

							

							<div class="col-md-3 text-center">

								<div class="small" style="margin-bottom:20px;">

									<i class="fa fa-check" ></i> Завершенные заказы

								</div>

								<div style="position: relative;">

									<h1 style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; margin: auto; display: inline-block; height: 26px; line-height: 1em;" class="font-extra-bold text-success">

										<?php echo (int) $array_completed_cnt; ?>

									</h1>

									<div>

										<iframe class="chartjs-hidden-iframe" tabindex="-1" style="display: block; overflow: hidden; border: 0px; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;"></iframe>

										<canvas id="doughnutChart" height="170" style="display: block; height: 170px;"></canvas>

									</div>

								</div>

								<div class="row m-t-md">

									<div class="col-lg-6">

										<h3 class="no-margins font-extra-bold text-success"><?php echo number_format((float)$total_indicators['price_sum_total'], 2, ',', ' '); ?></h3>



										<div class="font-bold">

										<?php

										if( (int) $total_indicators['price_sum_total'] !== 0 ){

											echo (int) ($total_indicators['price_sum_total'] / $total_indicators_2['price_sum_total'] * 100);

										}else{

											echo '0';

										}

										?>% <i class="fa fa-level-up text-success"></i>

										</div>

									</div>

									<div class="col-lg-6">

										<h3 class="no-margins font-extra-bold text-success"><?php echo number_format((float)$total_indicators['profit_sum_total'], 2, ',', ' '); ?></h3>



										<div class="font-bold">

										<?php

										if( (int) $total_indicators['profit_sum_total'] !== 0 ){

											echo (int) ($total_indicators['profit_sum_total'] / $total_indicators_2['profit_sum_total'] * 100);

										}else{

											echo '0';

										}

										?>% <i class="fa fa-level-up text-success"></i>

										</div>

									</div>

								</div>

								<div class="small m-t-xl">

									<i class="fa fa-exclamation-triangle"></i> Заказы в статусе "Завершен"

								</div>

							</div>

							

							<div class="col-lg-6">

								<div class="text-center" style="height:300px;">

									<iframe class="chartjs-hidden-iframe" tabindex="-1" style="display: block; overflow: hidden; border: 0px; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;"></iframe>

									<canvas id="lineOptions" style="display: block;" ></canvas>

								</div>

							</div>

							

							<div class="col-md-3 text-center">

								<div class="small" style="margin-bottom:20px;">

									<i class="fa fa-shopping-basket"></i> Всего заказов

								</div>

								<div style="position: relative;">

									<h1 style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; margin: auto; display: inline-block; height: 26px; line-height: 1em;" class="font-extra-bold m-t-xl m-b-xs">

										<?php echo (int) $array_all_cnt; ?>

									</h1>

									<div>

										<iframe class="chartjs-hidden-iframe" tabindex="-1" style="display: block; overflow: hidden; border: 0px; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;"></iframe>

										<canvas id="doughnutChart_2" height="170" style="display: block; height: 170px;"></canvas>

									</div>

								</div>

								<div class="row m-t-md">

									<div class="col-lg-6">

										<h3 class="no-margins font-extra-bold "><?php echo number_format((float)$total_indicators_2['price_sum_total'], 2, ',', ' '); ?></h3>



										<div class="font-bold">Сумма <i class="fa fa-level-down"></i></div>

									</div>

									<div class="col-lg-6">

										<h3 class="no-margins font-extra-bold "><?php echo number_format((float)$total_indicators_2['profit_sum_total'], 2, ',', ' '); ?></h3>



										<div class="font-bold">Прибыль <i class="fa fa-level-down"></i></div>

									</div>

								</div>

								<div class="small m-t-xl">

									<i class="fa fa-exclamation-triangle"></i> Все созданные заказы на сайте

								</div>

							</div>

							<script>

								jQuery(document).ready(function () {

								

								// .........................................................................................

									<?php

									if($array_all_cnt == 0){

										$array_all_cnt = 1;

									}

									?>

									// Завершенные заказы

									setTimeout(() => {

										

										var doughnutData = {

												datasets: [

													{

														data: [<?php echo (int) ($array_all_cnt - $array_completed_cnt); ?>, <?php echo (int) $array_completed_cnt; ?>],

														backgroundColor: [

															"#eeeeee",

															"#91dc6e"

														],

														hoverBackgroundColor: [

															"#dddddd",

															"#57b32c"

														]

													}]

											}



											var doughnutOptions = {

												responsive: true,

												maintainAspectRatio: false,

											};



											var ctx = document.getElementById("doughnutChart").getContext("2d");

											new Chart(ctx, {type: 'doughnut', data: doughnutData, options:doughnutOptions});

										

									}, "500");

									

								// .........................................................................................

									

									// Всего заказов

									setTimeout(() => {

										

										var doughnutChart_2 = {

												datasets: [

													{

														data: [<?php echo (int) $array_all_cnt; ?>],

														backgroundColor: [

															"#eeeeee"

														],

														hoverBackgroundColor: [

															"#dddddd"

														]

													}]

											}



											var doughnutOptions = {

												responsive: true,

												maintainAspectRatio: false,

											};



											var ctx = document.getElementById("doughnutChart_2").getContext("2d");

											new Chart(ctx, {type: 'doughnut', data: doughnutChart_2, options:doughnutOptions});

										

									}, "500");

									

								// .........................................................................................

									

									// График

									setTimeout(() => {

										

										var lineData = {

											labels: [<?php echo $key_str; ?>],

											datasets: [



												{

													label: "Завершенные",

													backgroundColor: 'rgba(98,203,49, 0.5)',

													pointBorderWidth: 1,

													pointBackgroundColor: "rgba(98,203,49,1)",

													pointRadius: 3,

													pointBorderColor: '#ffffff',

													borderWidth: 1,

													data: [<?php echo $val_2_str; ?>]

												},

												{

													label: "Всего",

													backgroundColor: 'rgba(220,220,220,0.5)',

													borderColor: "rgba(220,220,220,0.7)",

													pointBorderWidth: 1,

													pointBackgroundColor: "rgba(220,220,220,1)",

													pointRadius: 3,

													pointBorderColor: '#ffffff',

													borderWidth: 1,

													data: [<?php echo $val_1_str; ?>]

												}

											]

										};



										var lineOptions = {

											responsive: true,

											maintainAspectRatio: false,

										};

										

										

										

										var ctx = document.getElementById("lineOptions").getContext("2d");

										new Chart(ctx, {type: 'line', data: lineData, options:lineOptions});

										

									}, "800");

									

								// .........................................................................................

									

								});

							</script>

							<?php

						}// END - ЗАКАЗЫ

						

						

						

						

						

						

						

						

						

						

						// СУММА ЗАКАЗОВ

						

						if($statistical_settings["type"] === 'price')

						{

							$array_completed = array();// Завершенные

							$array_completed_sum = 0;

							

							$SQL = "SELECT `time`, ($sql_select_order_sum) AS `price_sum_total` FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?;";

							$query = $db_link->prepare($SQL);

							$query->execute(array($time_start, ($time_end - 1)));

							while($record = $query->fetch())

							{

								if( ! isset($array_completed[date($format, $record['time'])]) ){

									$array_completed[date($format, $record['time'])] = 0;

								}

								$array_completed[date($format, $record['time'])] += $record['price_sum_total'];

								$array_completed_sum += $record['price_sum_total'];

							}

							

							// Временные отсечки на графике

							if(empty($arr_key)){

								foreach($array_completed as $key => $val){

									$arr_key[$key] = $key;

								}

							}

							$key_str = '';

							foreach($arr_key as $key => $val){

								if($key_str != ''){

									$key_str .= ', ';

								}

								$key_str .= '"'.$val.'"';

							}

							$val_1_str = '';

							foreach($arr_key as $key => $val){

								if($val_1_str != ''){

									$val_1_str .= ', ';

								}

								if( ! isset($array_completed[$key]) ){

									$array_completed[$key] = 0;

								}

								$val_1_str .= '"'. ((int) $array_completed[$key]) .'"';

							}

							?>

							<div class="col-md-8 text-center"><h4>Сумма заказов</h4></div>

							

							<div class="col-md-8">

								<div class="text-center" style="height:300px;">

									<iframe class="chartjs-hidden-iframe" tabindex="-1" style="display: block; overflow: hidden; border: 0px; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;"></iframe>

									<canvas id="singleBarOptions" height="300" style="display: block; width: 644px; height: 300px;" width="644"></canvas>

								</div>

							</div>

							

							<div class="col-md-4 text-center">

								<br/>

								<br/>

								<h3 class="font-extra-bold no-margins text-success">

									Сумма за указанный период:

								</h3>

								<br/>

								<h1 class="m-xs"><?php echo number_format((float)$array_completed_sum, 2, ',', ' '); ?></h1>

								<br/>

								<i class="pe-7s-graph1 fa-4x"></i>

							</div>

							

							<script>

								jQuery(document).ready(function () {

								

								// .........................................................................................

								

									setTimeout(() => {

										

										var singleBarOptions = {

											responsive:true,

											maintainAspectRatio: false,

											legend: {

												display: false

											}

										};



										var singleBarData = {

											labels: [<?php echo $key_str; ?>],

											datasets: [

												{

													label: "Сумма",

													backgroundColor: "rgba(98,203,49,0.5)",

													borderColor: "rgba(98,203,49,0.8)",

													highlightFill: "rgba(98,203,49,0.75)",

													highlightStroke: "rgba(98,203,49,1)",

													borderWidth: 1,

													data: [<?php echo $val_1_str; ?>]

												}

											]

										};



										var ctx = document.getElementById("singleBarOptions").getContext("2d");

										new Chart(ctx, {type: 'bar', data: singleBarData, options:singleBarOptions});

										

									}, "500");

								

								// .........................................................................................

								

								});

							</script>

							<?php

						}

						

						

						

						

						

						

						

						

						

						

						// ПОЗИЦИИ ЗАКАЗОВ :

						

						if($statistical_settings["type"] === 'orders_items')

						{

							$array_all = array();// Всего

							$array_completed = array();// Завершенные

							$array_cancelled = array();// Отмененные

							

							$all_cnt = 0;

							$completed_cnt = 0;

							$cancelled_cnt = 0;

							

							$all_sum = 0;

							$completed_sum = 0;

							$cancelled_sum = 0;



							$all_profit = 0;

							$completed_profit = 0;

							$cancelled_profit = 0;

							

							$SQL = "

									SELECT  `time`, 

											`status`, 

											(CAST( (SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id`) AS DECIMAL(20,2))) AS `price_sum_total`, 

											(CAST( (SELECT SUM(`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id`) AS DECIMAL(20,2))) AS `count_need_total`, 

											

											((CAST( (SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id`) AS DECIMAL(20,2)) - ((CAST( IFNULL( (SELECT SUM(`price_purchase`*(`count_reserved`+`count_issued`+`count_canceled`)) FROM `shop_orders_items_details` WHERE `order_id`= `shop_orders`.`id`), 0) AS DECIMAL(20,2) ) ) + (CAST( IFNULL( (SELECT SUM(`t2_price_purchase`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id`), 0) AS DECIMAL(20,2) ) )))) AS `profit_sum_total`, 

											

											($sql_select_order_count_need) AS `count_need`, 

											($sql_select_order_sum) AS `price_sum`, 

											($sql_select_order_profit) AS `profit_sum` 

									FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `time` BETWEEN ? AND ?;

							";

							

							$query = $db_link->prepare($SQL);

							$query->execute(array($time_start, ($time_end - 1)));

							while($record = $query->fetch())

							{

								if( ! isset($array_all[date($format, $record['time'])]) ){

									$array_all[date($format, $record['time'])] = 0;

								}

								$array_all[date($format, $record['time'])] += $record['count_need_total'];

								$all_cnt += $record['count_need_total'];

								$all_sum += $record['price_sum_total'];

								$all_profit += $record['profit_sum_total'];

								

								if( in_array($record['status'], $orders_statuses_for_finish) ){

									if( ! isset($array_completed[date($format, $record['time'])]) ){

										$array_completed[date($format, $record['time'])] = 0;

									}

									$array_completed[date($format, $record['time'])] += $record['count_need'];

									$completed_cnt += $record['count_need'];

									$completed_sum += $record['price_sum'];

									$completed_profit += $record['profit_sum'];

								}

								

								if( ! isset($array_cancelled[date($format, $record['time'])]) ){

									$array_cancelled[date($format, $record['time'])] = 0;

								}

								$array_cancelled[date($format, $record['time'])] += $record['count_need_total'] - $record['count_need'];

								$cancelled_cnt += $record['count_need_total'] - $record['count_need'];

								$cancelled_sum += $record['price_sum_total'] - $record['price_sum'];

								$cancelled_profit += $record['profit_sum_total'] - $record['profit_sum'];

							}

							

							// Временные отсечки на графике

							if(empty($arr_key)){

								foreach($array_all as $key => $val){

									$arr_key[$key] = $key;

								}

							}

							$key_str = '';

							foreach($arr_key as $key => $val){

								if($key_str != ''){

									$key_str .= ', ';

								}

								$key_str .= '"'.$val.'"';

							}

							$val_1_str = '';

							foreach($arr_key as $key => $val){

								if($val_1_str != ''){

									$val_1_str .= ', ';

								}

								if( ! isset($array_all[$key]) ){

									$array_all[$key] = 0;

								}

								$val_1_str .= '"'. ((int) $array_all[$key]) .'"';

							}

							$val_2_str = '';

							foreach($arr_key as $key => $val){

								if($val_2_str != ''){

									$val_2_str .= ', ';

								}

								if( ! isset($array_completed[$key]) ){

									$array_completed[$key] = 0;

								}

								$val_2_str .= '"'. ((int) $array_completed[$key]) .'"';

							}

							$val_3_str = '';

							foreach($arr_key as $key => $val){

								if($val_3_str != ''){

									$val_3_str .= ', ';

								}

								if( ! isset($array_cancelled[$key]) ){

									$array_cancelled[$key] = 0;

								}

								$val_3_str .= '"'. ((int) $array_cancelled[$key]) .'"';

							}

							

							?>

							

							<div class="col-md-12 text-center"><h4>Позиции заказов</h4></div>

							

							<div class="col-md-3">

								

								<div>

									<h3 class="m-b-xs"><?php echo $completed_cnt; ?> <small>шт.</small></h3>

									

									<span class="font-bold no-margins">

										Позиции в завершенных заказах

									</span>



									<div class="progress m-t-xs full progress-small">

										<?php

										if( (int) $all_cnt !== 0 ){

										?>

										<div style="width: <?php echo (int) ($completed_cnt / $all_cnt * 100); ?>%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="10" role="progressbar" class=" progress-bar progress-bar-success">

										</div>

										<?php

										}else{

										?>

										<div style="width: 0%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="10" role="progressbar" class=" progress-bar progress-bar-success">

										</div>

										<?php

										}

										?>

									</div>



									<div class="row">

										<div class="col-xs-6">

											<small class="stats-label">Сумма / Прибыль</small>

											<h4><?php echo number_format((float)$completed_sum, 2, ',', ' '); ?> / <?php echo number_format((float)$completed_profit, 2, ',', ' '); ?></h4>

										</div>



										<div class="col-xs-6">

											<small class="stats-label">% соотношения</small>

											<h4>

												<?php

												if( (int) $all_cnt !== 0 ){

													echo (int) ($completed_cnt / $all_cnt * 100);

												}else{

													echo '0';

												}

												?>%

											</h4>

										</div>

									</div>

								</div>

								

								<div>

									<h3 class="m-b-xs"><?php echo $cancelled_cnt; ?> <small>шт.</small></h3>

									

									<span class="font-bold no-margins">

										Отмененные позиции

									</span>



									<div class="progress m-t-xs full progress-small">

										<?php

										if( (int) $all_cnt !== 0 ){

										?>

										<div style="width: <?php echo (int) ($cancelled_cnt / $all_cnt * 100); ?>%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="10" role="progressbar" class=" progress-bar progress-bar-danger">

										</div>

										<?php

										}else{

										?>

										<div style="width: 0%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="10" role="progressbar" class=" progress-bar progress-bar-danger">

										</div>

										<?php

										}

										?>

									</div>



									<div class="row">

										<div class="col-xs-6">

											<small class="stats-label">Сумма / Упущенная прибыль</small>

											<h4><?php echo number_format((float)$cancelled_sum, 2, ',', ' '); ?> / <?php echo number_format((float)$cancelled_profit, 2, ',', ' '); ?></h4>

										</div>



										<div class="col-xs-6">

											<small class="stats-label">% соотношения</small>

											<h4>

											<?php

											if( (int) $all_cnt !== 0 ){

												echo (int) ($cancelled_cnt / $all_cnt * 100); 

											}else{

												echo '0';

											}

											?>%

											</h4>

										</div>

									</div>

								</div>

								

							</div>

							

							<div class="col-lg-6">

								<div class="text-center" style="height:300px;">

									<iframe class="chartjs-hidden-iframe" tabindex="-1" style="display: block; overflow: hidden; border: 0px; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;"></iframe>

									<canvas id="lineOptions" style="display: block;" ></canvas>

								</div>

							</div>

							

							<div class="col-md-3 text-center">

								<div class="small" style="margin-bottom:20px;">

									<i class="fa fa-shopping-basket"></i> Всего позиций

								</div>

								<div style="position: relative;">

									<h1 style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; margin: auto; display: inline-block; height: 26px; line-height: 1em;" class="font-extra-bold m-t-xl m-b-xs">

										<?php echo (int) $all_cnt; ?>

									</h1>

									<div>

										<iframe class="chartjs-hidden-iframe" tabindex="-1" style="display: block; overflow: hidden; border: 0px; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;"></iframe>

										<canvas id="doughnutChart_2" height="170" style="display: block; height: 170px;"></canvas>

									</div>

								</div>

								<div class="row m-t-md">

									<div class="col-lg-6">

										<h3 title="Сумма всего, с учетом отмененных позиций" class="no-margins font-extra-bold "><?php echo number_format((float)$all_sum, 2, ',', ' '); ?></h3>



										<div title="Сумма за исключением отмененных позиций" class="font-bold">Сумма: <?php echo number_format((float)($all_sum-$cancelled_sum), 2, ',', ' '); ?></div>

									</div>

									<div class="col-lg-6">

										<h3 title="Всего, с учетом отмененных позиций" class="no-margins font-extra-bold "><?php echo number_format((float)$all_profit, 2, ',', ' '); ?></h3>

										

										<div title="За исключением отмененных позиций" class="font-bold">Прибыль: <?php echo number_format((float)($all_profit-$cancelled_profit), 2, ',', ' '); ?></div>

									</div>

								</div>

								<div class="small m-t-xl">

									

								</div>

							</div>

							<script>

								jQuery(document).ready(function () {

								

								// .........................................................................................

									<?php

									if($all_cnt == 0){

										$all_cnt = 1;

									}

									?>

									// Всего заказов

									setTimeout(() => {

										

										var doughnutChart_2 = {

												//labels: ["", "", ""],

												datasets: [

													{

														data: [<?php echo (int) ($all_cnt - $completed_cnt - $cancelled_cnt); ?>, <?php echo (int) $completed_cnt; ?>, <?php echo (int) $cancelled_cnt; ?>],

														backgroundColor: [

															"#eeeeee",

															"#91dc6e",

															"#E91E63"

														],

														hoverBackgroundColor: [

															"#dddddd",

															"#57b32c",

															"#E91E63"

														]

													}]

											}



											var doughnutOptions = {

												responsive: true,

												maintainAspectRatio: false,

											};



											var ctx = document.getElementById("doughnutChart_2").getContext("2d");

											new Chart(ctx, {type: 'doughnut', data: doughnutChart_2, options:doughnutOptions});

										

									}, "500");

									

								// .........................................................................................

									

									// График

									setTimeout(() => {

										

										var lineData = {

											labels: [<?php echo $key_str; ?>],

											datasets: [



												{

													label: "Отмена",

													backgroundColor: '#E91E63',

													pointBorderWidth: 1,

													pointBackgroundColor: "rgba(98,203,49,1)",

													pointRadius: 3,

													pointBorderColor: '#ffffff',

													borderWidth: 1,

													data: [<?php echo $val_3_str; ?>]

												},

												{

													label: "Завершенные",

													backgroundColor: 'rgba(98,203,49, 0.5)',

													pointBorderWidth: 1,

													pointBackgroundColor: "rgba(98,203,49,1)",

													pointRadius: 3,

													pointBorderColor: '#ffffff',

													borderWidth: 1,

													data: [<?php echo $val_2_str; ?>]

												},

												{

													label: "Всего",

													backgroundColor: 'rgba(220,220,220,0.5)',

													borderColor: "rgba(220,220,220,0.7)",

													pointBorderWidth: 1,

													pointBackgroundColor: "rgba(220,220,220,1)",

													pointRadius: 3,

													pointBorderColor: '#ffffff',

													borderWidth: 1,

													data: [<?php echo $val_1_str; ?>]

												}

											]

										};



										var lineOptions = {

											responsive: true,

											maintainAspectRatio: false,

										};

										

										

										

										var ctx = document.getElementById("lineOptions").getContext("2d");

										new Chart(ctx, {type: 'line', data: lineData, options:lineOptions});

										

									}, "800");

									

								// .........................................................................................

									

								});

							</script>

							<?php

						}// END - ЗАКАЗЫ

						

						

						

						

						

						

						

						

						

						

						// ПРИБЫЛЬ

						

						if($statistical_settings["type"] === 'profit')

						{

							$array_completed = array();// Завершенные

							$array_completed_sum = 0;

							

							$SQL = "SELECT `time`, ($sql_select_order_profit) AS `price_sum_total` FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?;";

							$query = $db_link->prepare($SQL);

							$query->execute(array($time_start, ($time_end - 1)));

							while($record = $query->fetch())

							{

								if( ! isset($array_completed[date($format, $record['time'])]) ){

									$array_completed[date($format, $record['time'])] = 0;

								}

								$array_completed[date($format, $record['time'])] += $record['price_sum_total'];

								$array_completed_sum += $record['price_sum_total'];

							}

							

							// Временные отсечки на графике

							if(empty($arr_key)){

								foreach($array_completed as $key => $val){

									$arr_key[$key] = $key;

								}

							}

							$key_str = '';

							foreach($arr_key as $key => $val){

								if($key_str != ''){

									$key_str .= ', ';

								}

								$key_str .= '"'.$val.'"';

							}

							$val_1_str = '';

							foreach($arr_key as $key => $val){

								if($val_1_str != ''){

									$val_1_str .= ', ';

								}

								if( ! isset($array_completed[$key]) ){

									$array_completed[$key] = 0;

								}

								$val_1_str .= '"'. ((int) $array_completed[$key]) .'"';

							}

							?>

							<div class="col-md-8 text-center"><h4>Прибыль</h4></div>

							

							<div class="col-md-8">

								<div class="text-center" style="height:300px;">

									<iframe class="chartjs-hidden-iframe" tabindex="-1" style="display: block; overflow: hidden; border: 0px; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;"></iframe>

									<canvas id="singleBarOptions" height="300" style="display: block; width: 644px; height: 300px;" width="644"></canvas>

								</div>

							</div>

							

							<div class="col-md-4 text-center">

								<br/>

								<br/>

								<h3 class="font-extra-bold no-margins text-success">

									Сумма за указанный период:

								</h3>

								<br/>

								<h1 class="m-xs"><?php echo number_format((float)$array_completed_sum, 2, ',', ' '); ?></h1>

								<br/>

								<i class="pe-7s-cash fa-4x"></i>

							</div>

							

							<script>

								jQuery(document).ready(function () {

								

								// .........................................................................................

								

									setTimeout(() => {

										

										var singleBarOptions = {

											responsive:true,

											maintainAspectRatio: false,

											legend: {

												display: false

											}

										};



										var singleBarData = {

											labels: [<?php echo $key_str; ?>],

											datasets: [

												{

													label: "Сумма",

													backgroundColor: "rgba(98,203,49,0.5)",

													borderColor: "rgba(98,203,49,0.8)",

													highlightFill: "rgba(98,203,49,0.75)",

													highlightStroke: "rgba(98,203,49,1)",

													borderWidth: 1,

													data: [<?php echo $val_1_str; ?>]

												}

											]

										};



										var ctx = document.getElementById("singleBarOptions").getContext("2d");

										new Chart(ctx, {type: 'bar', data: singleBarData, options:singleBarOptions});

										

									}, "500");

								

								// .........................................................................................

								

								});

							</script>

							<?php

						}	

						?>

						

						

						

						

						

						

						

						

						

						

						<?php

						if($statistical_settings["type"] === 'user_rating')

						{

						?>

								

							<div class="col-md-6">

								<div class="col-lg-12 text-center"><h4>Рейтинг по количеству заказов</h4></div>

								<div style="width: 100%; max-height: 300px; overflow: auto;">

									<div class="list">

										<div class="panel-title">Учитываются только Завершенные заказы</div>

										<div class="list-item-container" style="width:100%;">

											<?php

											$SQL_SELECT_TOTAL_INDICATORS = "SELECT 

												`user_id`, 

												

												(SELECT `data_value` FROM `users_profiles` WHERE `user_id` = `users`.`user_id` AND `data_key` = 'surname') AS 'surname', 

												(SELECT `data_value` FROM `users_profiles` WHERE `user_id` = `users`.`user_id` AND `data_key` = 'name') AS 'name', 

												

												( CAST( ( SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id` IN(SELECT `id` FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?) AND `status` NOT IN (".implode(',', $orders_statuses_for_inverse).") ) AS DECIMAL(20,2) ) ) AS `price_sum_total`, 

												

												( CAST( ( SELECT SUM(`count_need`) FROM `shop_orders_items` WHERE `order_id` IN(SELECT `id` FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?) AND `status` NOT IN (".implode(',', $orders_statuses_for_inverse).") ) AS DECIMAL(20,2) ) ) AS `count_need_total`, 

												

												( CAST( ( SELECT COUNT(*) FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ? ) AS DECIMAL(20,2) ) ) AS `count_total` 

												

											FROM `users` HAVING `count_total` > 0 ORDER BY `count_total` DESC LIMIT 50;

											";

											$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

											$total_indicators_query->execute(array($time_start, ($time_end - 1), $time_start, ($time_end - 1), $time_start, ($time_end - 1)));

											while($total_indicators = $total_indicators_query->fetch()){

												if($class != 'text-success'){

													$class = 'text-success';

												}else{

													$class = 'text-color3';

												}

											?>

											<div class="list-item" style="padding-right: 10px; padding-left: 10px;">

												<h3 class="no-margins font-extra-bold <?php echo $class; ?>"><?php echo (int) $total_indicators['count_total']; ?></h3>

												<small>ID <?php echo $total_indicators['user_id'] .' - '. $total_indicators['name'] .' '. $total_indicators['surname']; ?></small>

												<div class="pull-right font-bold"> <span title="Сумма по завершенным заказам"><?php echo number_format((float)$total_indicators['price_sum_total'], 2, ',', ' '); ?></span> / <span title="Число позиций в завершенных заказах"><?php echo (int) $total_indicators['count_need_total']; ?> <small>шт.</small></span> </div>

											</div>

											<?php

											}

											?>

										</div>

									</div>

								</div>

							</div>

							

							<div class="col-md-6">

								<div class="col-lg-12 text-center"><h4>Рейтинг по сумме заказов</h4></div>

								<div style="width: 100%; max-height: 300px; overflow: auto;">

									<div class="list">

										<div class="panel-title">Учитываются только Завершенные заказы</div>

										<div class="list-item-container" style="width:100%;">

											<?php

											$SQL_SELECT_TOTAL_INDICATORS = "SELECT 

												`user_id`, 

												

												(SELECT `data_value` FROM `users_profiles` WHERE `user_id` = `users`.`user_id` AND `data_key` = 'surname') AS 'surname', 

												(SELECT `data_value` FROM `users_profiles` WHERE `user_id` = `users`.`user_id` AND `data_key` = 'name') AS 'name', 

												

												( CAST( ( SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id` IN(SELECT `id` FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?) AND `status` NOT IN (".implode(',', $orders_statuses_for_inverse).") ) AS DECIMAL(20,2) ) ) AS `price_sum_total`, 

												

												( CAST( ( SELECT SUM(`count_need`) FROM `shop_orders_items` WHERE `order_id` IN(SELECT `id` FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?) AND `status` NOT IN (".implode(',', $orders_statuses_for_inverse).") ) AS DECIMAL(20,2) ) ) AS `count_need_total`, 

												

												( CAST( ( SELECT COUNT(*) FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ? ) AS DECIMAL(20,2) ) ) AS `count_total` 

												

											FROM `users` ORDER BY `price_sum_total` DESC LIMIT 50;

											";

											$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

											$total_indicators_query->execute(array($time_start, ($time_end - 1), $time_start, ($time_end - 1), $time_start, ($time_end - 1)));

											while($total_indicators = $total_indicators_query->fetch()){

												if($class != 'text-success'){

													$class = 'text-success';

												}else{

													$class = 'text-color3';

												}

											?>

											<div class="list-item" style="padding-right: 10px; padding-left: 10px;">

												<h3 class="no-margins font-extra-bold <?php echo $class; ?>"><?php echo number_format((float)$total_indicators['price_sum_total'], 2, ',', ' '); ?></h3>

												<small>ID <?php echo $total_indicators['user_id'] .' - '. $total_indicators['name'] .' '. $total_indicators['surname']; ?></small>

												<div class="pull-right font-bold"> <span title="Число завершенных заказов"><?php echo (int) $total_indicators['count_total']; ?></span> / <span title="Число позиций в завершенных заказах"><?php echo (int) $total_indicators['count_need_total']; ?> <small>шт.</small></span> </div>

											</div>

											<?php

											}

											?>

										</div>

									</div>

								</div>

							</div>

								

						<?php

						}

						?>

						

						

						

						

						

						

						

						

						

						

						<?php

						if($statistical_settings["type"] === 'product_rating')

						{

						?>

								

							<div class="col-md-6">

								<div class="col-lg-12 text-center"><h4>Рейтинг товаров</h4></div>

								<div style="width: 100%; max-height: 315px; overflow: auto;">

									<table class="table table-striped" style="margin-bottom: 0;">

										<thead>

										<tr>

											<th><small>Артикул</small></th>

											<th><small>Производитель</small></th>

											<th><small>Наименование</small></th>

											<th><small>Заказы</small></th>

											<th style="white-space: nowrap;"><small>Кол-во</small></th>

										</tr>

										</thead>

										<tbody>

										<?php

										$SQL_SELECT_TOTAL_INDICATORS = "

										SELECT 

										`tt`.`id`,

										`tt`.`t2_article`,

										`tt`.`t2_manufacturer`,

										`tt`.`t2_name`,

										`tt`.`cnt_id`,

										`tt`.`cnt`



										FROM(



										SELECT 

										MAX(`t`.`id`) AS `id`,

										`t`.`t2_article`,

										`t`.`t2_manufacturer`,

										`t`.`t2_name`,

										COUNT(`t`.`id`) AS 'cnt_id',

										SUM(`t`.`count_need`) AS `cnt`



										FROM(

										 

										SELECT `id`, `count_need`, `t2_article`, `t2_manufacturer`, `t2_name` FROM `shop_orders_items` WHERE `t2_article` != '' AND `order_id` IN(SELECT `id` FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `time` BETWEEN ? AND ?) 



										) AS `t` GROUP BY `t`.`t2_article`, `t`.`t2_manufacturer`, `t`.`t2_name` 

										) AS `tt` ORDER BY `cnt_id` DESC LIMIT 50

										";

										$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

										$total_indicators_query->execute(array($time_start, ($time_end - 1)));

										while($total_indicators = $total_indicators_query->fetch()){

											

											if( ! isset($total_indicators['t2_article']) ){

												$total_indicators['t2_article'] = '';

											}

											if( ! isset($total_indicators['t2_manufacturer']) ){

												$total_indicators['t2_manufacturer'] = '';

											}

											if( ! isset($total_indicators['t2_name']) ){

												$total_indicators['t2_name'] = '';

											}

											if( ! isset($total_indicators['cnt_id']) ){

												$total_indicators['cnt_id'] = '';

											}

											if( ! isset($total_indicators['cnt']) ){

												$total_indicators['cnt'] = '';

											}

											

										?>

										<tr>

											<td>

												<?php echo $total_indicators['t2_article']; ?>

											</td>

											<td>

												<?php echo $total_indicators['t2_manufacturer']; ?>

											</td>

											<td style="font-size: 11px;">

												<?php echo $total_indicators['t2_name']; ?>

											</td>

											<td>

												<?php echo $total_indicators['cnt_id']; ?>

											</td>

											<td>

												<?php echo $total_indicators['cnt']; ?>

											</td>

										</tr>

										<?php

										}

										?>

										</tbody>

									</table>

								</div>

							</div>

							

							<div class="col-md-6">

								<div class="col-lg-12 text-center"><h4>Рейтинг запросов</h4></div>

								<div style="width: 100%; max-height: 315px; overflow: auto;">

									<table class="table table-striped" style="margin-bottom: 0;">

										<thead>

										<tr>

											<th><small>Артикул</small></th>

											<th><small>Производитель</small></th>

											<th><small>Наименование</small></th>

											<th style="white-space: nowrap;"><small>Кол-во</small></th>

										</tr>

										</thead>

										<tbody>

										<?php

										$SQL_SELECT_TOTAL_INDICATORS = "

										SELECT DISTINCT(`article`), MAX(`manufacturer`) AS `manufacturer`, MAX(`name`) AS `name`, COUNT(`article`) AS `cnt` FROM `shop_stat_article_queries` WHERE `article` != '' AND `time` BETWEEN ? AND ? GROUP BY `article` ORDER BY `cnt` DESC LIMIT 50;

										";

										$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

										$total_indicators_query->execute(array($time_start, ($time_end - 1)));

										while($total_indicators = $total_indicators_query->fetch()){

											

											if( ! isset($total_indicators['article']) ){

												$total_indicators['article'] = '';

											}

											if( ! isset($total_indicators['manufacturer']) ){

												$total_indicators['manufacturer'] = '';

											}

											if( ! isset($total_indicators['name']) ){

												$total_indicators['name'] = '';

											}

											if( ! isset($total_indicators['cnt']) ){

												$total_indicators['cnt'] = '';

											}

											

										?>

										<tr>

											<td>

												<?php echo $total_indicators['article']; ?>

											</td>

											<td>

												<?php echo $total_indicators['manufacturer']; ?>

											</td>

											<td style="font-size: 11px;">

												<?php echo $total_indicators['name']; ?>

											</td>

											<td>

												<?php echo $total_indicators['cnt']; ?>

											</td>

										</tr>

										<?php

										}

										?>

										</tbody>

									</table>

								</div>

							</div>

								

						<?php

						}

						?>

						

						

						

						

						

						

						

						

						

						

						<?php

						

						// НОВЫЕ ПОЛЬЗОВАТЕЛИ

						

						if($statistical_settings["type"] === 'new_users')

						{

							$array_all = array();

							$array_all_sum = 0;

							

							$SQL = "SELECT `time_registered` AS `time` FROM `users` WHERE `time_registered` BETWEEN ? AND ?;";

							$query = $db_link->prepare($SQL);

							$query->execute(array($time_start, ($time_end - 1)));

							while($record = $query->fetch())

							{

								if( ! isset($array_all[date($format, $record['time'])]) ){

									$array_all[date($format, $record['time'])] = 0;

								}

								$array_all[date($format, $record['time'])] += 1;

								$array_all_sum += 1;

							}

							

							// Временные отсечки на графике

							if(empty($arr_key)){

								foreach($array_all as $key => $val){

									$arr_key[$key] = $key;

								}

							}

							$key_str = '';

							foreach($arr_key as $key => $val){

								if($key_str != ''){

									$key_str .= ', ';

								}

								$key_str .= '"'.$val.'"';

							}

							$val_1_str = '';

							foreach($arr_key as $key => $val){

								if($val_1_str != ''){

									$val_1_str .= ', ';

								}

								if( ! isset($array_all[$key]) ){

									$array_all[$key] = 0;

								}

								$val_1_str .= '"'. ((int) $array_all[$key]) .'"';

							}

							?>

							<div class="col-md-8 text-center"><h4>Новые пользователи</h4></div>

							

							<div class="col-md-8">

								<div class="text-center" style="height:300px;">

									<iframe class="chartjs-hidden-iframe" tabindex="-1" style="display: block; overflow: hidden; border: 0px; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;"></iframe>

									<canvas id="singleBarOptions" height="300" style="display: block; width: 644px; height: 300px;" width="644"></canvas>

								</div>

							</div>

							

							<div class="col-md-4 text-center">

								<br/>

								<br/>

								<h3 class="font-extra-bold no-margins text-success">

									Количество новых пользователей:

								</h3>

								<br/>

								<h1 class="m-xs">+<?php echo $array_all_sum; ?> <small>чел.</small></h1>

								<br/>

								<i class="pe-7s-add-user fa-4x"></i>

							</div>

							

							<script>

								jQuery(document).ready(function () {

								

								// .........................................................................................

								

									setTimeout(() => {

										

										var singleBarOptions = {

											responsive:true,

											maintainAspectRatio: false,

											legend: {

												display: false

											}

										};



										var singleBarData = {

											labels: [<?php echo $key_str; ?>],

											datasets: [

												{

													label: "Кол-во",

													backgroundColor: "rgba(98,203,49,0.5)",

													borderColor: "rgba(98,203,49,0.8)",

													highlightFill: "rgba(98,203,49,0.75)",

													highlightStroke: "rgba(98,203,49,1)",

													borderWidth: 1,

													data: [<?php echo $val_1_str; ?>]

												}

											]

										};



										var ctx = document.getElementById("singleBarOptions").getContext("2d");

										new Chart(ctx, {type: 'bar', data: singleBarData, options:singleBarOptions});

										

									}, "500");

								

								// .........................................................................................

								

								});

							</script>

						<?php

						}

						?>

						

												

						

						

						

						

						

						

						

						

						<?php

						

						// АКТИВНЫЕ ПОЛЬЗОВАТЕЛИ

						

						if($statistical_settings["type"] === 'active_users')

						{

							$array_all = array();

							$array_all_sum = 0;

							

							$SQL = "SELECT `time_last_visit` AS `time` FROM `users` WHERE `time_last_visit` BETWEEN ? AND ?;";

							$query = $db_link->prepare($SQL);

							$query->execute(array($time_start, ($time_end)));

							while($record = $query->fetch())

							{

								if( ! isset($array_all[date($format, $record['time'])]) ){

									$array_all[date($format, $record['time'])] = 0;

								}

								$array_all[date($format, $record['time'])] += 1;

								$array_all_sum += 1;

							}

							

							// Временные отсечки на графике

							if(empty($arr_key)){

								foreach($array_all as $key => $val){

									$arr_key[$key] = $key;

								}

							}

							$key_str = '';

							foreach($arr_key as $key => $val){

								if($key_str != ''){

									$key_str .= ', ';

								}

								$key_str .= '"'.$val.'"';

							}

							$val_1_str = '';

							foreach($arr_key as $key => $val){

								if($val_1_str != ''){

									$val_1_str .= ', ';

								}

								if( ! isset($array_all[$key]) ){

									$array_all[$key] = 0;

								}

								$val_1_str .= '"'. ((int) $array_all[$key]) .'"';

							}

							?>

							<div class="col-md-8 text-center"><h4>Активные пользователи</h4></div>

							

							<div class="col-md-8">

								<div class="text-center" style="height:300px;">

									<iframe class="chartjs-hidden-iframe" tabindex="-1" style="display: block; overflow: hidden; border: 0px; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;"></iframe>

									<canvas id="singleBarOptions" height="300" style="display: block; width: 644px; height: 300px;" width="644"></canvas>

								</div>

							</div>

							

							<div class="col-md-4 text-center">

								<h3 class="font-extra-bold no-margins text-success">

									Количество активных пользователей:

								</h3>

								<br/>

								<h1 class="m-xs"><?php echo $array_all_sum; ?> <small>чел.</small></h1>

								<br/>

								<i class="pe-7s-gleam fa-4x"></i>

								<br/>

								<br/>

								<small>Учитывается только дата последнего визита на сайт, поэтому актуальной будет только статистика за текущий период. Это означает что, например пользователи посещавшие сайт в текущем месяце, не учитываются в статисте за предыдущий месяц, так как дата их последнего визита была изменена на дату текущего месяца. Поэтому в статистике за предыдущий месяц будет отражено количество пользователей, которые посещали сайт в предыдущем месяце, но не заходили на сайт в текущем.</small>

							</div>

							

							<script>

								jQuery(document).ready(function () {

								

								// .........................................................................................

								

									setTimeout(() => {

										

										var singleBarOptions = {

											responsive:true,

											maintainAspectRatio: false,

											legend: {

												display: false

											}

										};



										var singleBarData = {

											labels: [<?php echo $key_str; ?>],

											datasets: [

												{

													label: "Кол-во",

													backgroundColor: "rgba(98,203,49,0.5)",

													borderColor: "rgba(98,203,49,0.8)",

													highlightFill: "rgba(98,203,49,0.75)",

													highlightStroke: "rgba(98,203,49,1)",

													borderWidth: 1,

													data: [<?php echo $val_1_str; ?>]

												}

											]

										};



										var ctx = document.getElementById("singleBarOptions").getContext("2d");

										new Chart(ctx, {type: 'bar', data: singleBarData, options:singleBarOptions});

										

									}, "500");

								

								// .........................................................................................

								

								});

							</script>

						<?php

						}

						?>

						

						

						

					</div>

				</div>

			</div>

		</div>

	</div>





















	<div class="row">



		<?php



		// -------------------------------------------------------------------

		

		//Получаем суммарные показатели

		$SQL_SELECT_TOTAL_INDICATORS = "SELECT 

			COUNT(*) AS `orders_count`, 

			SUM($sql_select_order_count_need) AS `count_need_total`, 

			SUM($sql_select_order_sum) AS `price_sum_total`, 

			SUM($sql_select_order_profit) AS `profit_sum_total` 

			FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?;";

		$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

		$total_indicators_query->execute(array($time_start, ($time_end - 1)));

		$total_indicators = $total_indicators_query->fetch();



		$SQL_SELECT_TOTAL_INDICATORS = "SELECT 

			COUNT(*) AS `orders_count`, 

			SUM($sql_select_order_sum) AS `price_sum_total`, 

			SUM($sql_select_order_profit) AS `profit_sum_total` 

			FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `time` BETWEEN ? AND ?;";

		$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

		$total_indicators_query->execute(array($time_start, ($time_end - 1)));

		$total_indicators_2 = $total_indicators_query->fetch();

		

		// -------------------------------------------------------------------



		?>

		

		

		

		<?php



		// -------------------------------------------------------------------



		?>







		<div class="col-lg-3">

			<div class="hpanel">

				<div class="panel-body text-center h-200" style="height: 210px;">

					<i class="pe-7s-graph1 fa-4x"></i>



					<h1 class="m-xs"><?php echo number_format((float)$total_indicators['price_sum_total'], 2, ',', ' '); ?></h1>



					<h3 class="font-extra-bold no-margins text-success">

						Сумма по заказам

					</h3>

					<small>Сумма завершенных заказов.</small>

				</div>

				<div class="panel-footer">

					Сумма заказов

					<a onClick="statistical_settings('price');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

		</div>







		<?php



		// -------------------------------------------------------------------



		?>







		<div class="col-lg-3">

			<div class="hpanel stats">

				<div class="panel-body h-200" style="height: 210px;">

					<div class="stats-title pull-left">

						<h4>Выполненные заказы</h4>

					</div>

					<div class="stats-icon pull-right">

						<i class="pe-7s-share fa-4x"></i>

					</div>

					<div class="m-t-xl">

						<h3 class="m-b-xs"><?php echo $total_indicators['orders_count']; ?> <small>шт.</small></h3>

				<span class="font-bold no-margins">

					Заказы пользователей

				</span>



						<div class="progress m-t-xs full progress-small">

							<?php

							if( (int) $total_indicators_2['orders_count'] !== 0 ){

							?>

							<div style="width: <?php echo (int) ($total_indicators['orders_count'] / $total_indicators_2['orders_count'] * 100); ?>%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="10" role="progressbar" class=" progress-bar progress-bar-success">

							</div>

							<?php

							}else{

							?>

							<div style="width: 0%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="10" role="progressbar" class=" progress-bar progress-bar-success">

							</div>

							<?php

							}

							?>

						</div>



						<div class="row">

							<div class="col-xs-6">

								<small class="stats-label">Всего / Выполнено</small>

								<h4><?php echo $total_indicators_2['orders_count']; ?> / <?php echo $total_indicators['orders_count']; ?></h4>

							</div>



							<div class="col-xs-6">

								<small class="stats-label">% соотношения</small>

								<h4><?php

								if( (int) $total_indicators_2['orders_count'] !== 0 ){

									echo (int) (($total_indicators['orders_count'] / $total_indicators_2['orders_count']) * 100);

								}else{

									echo '0';

								}

								?>%</h4>

							</div>

						</div>

					</div>

				</div>

				<div class="panel-footer">

					Заказы

					<a onClick="statistical_settings('orders');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

		</div>







		<?php



		// -------------------------------------------------------------------



		?>







		<div class="col-lg-3">

			<div class="hpanel stats">

				<div class="panel-body h-200" style="height: 210px;">

					<div class="stats-title pull-left">

						<h4>Позиции заказов</h4>

					</div>

					<div class="stats-icon pull-right">

						<i class="pe-7s-keypad fa-4x"></i>

					</div>

					<div class="m-t-xl">

						<h1 class="text-success"><?php echo (int) $total_indicators['count_need_total']; ?> <small class="text-success">шт.</small></h1>

				<span class="font-bold no-margins">

					Завершенные позиции

				</span>

						<br>

						<small>

							Все завершенные позиции за исключением отмененных.

						</small>

					</div>

				</div>

				<div class="panel-footer">

					Позиции заказов

					<a onClick="statistical_settings('orders_items');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

		</div>







		<?php



		// -------------------------------------------------------------------



		?>







		<div class="col-lg-3">

			<div class="hpanel stats">

				<div class="panel-body h-200" style="height: 210px;">

					<div class="stats-title pull-left">

						<h4>Прибыль</h4>

					</div>

					<div class="stats-icon pull-right">

						<i class="pe-7s-cash fa-4x"></i>

					</div>

					<div class="clearfix"></div>

					<div class="flot-chart">

						<div class="flot-chart-content" id="flot-income-chart" style="padding: 0px; position: relative;"><canvas class="flot-base" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 286px; height: 60px;" width="286" height="60"></canvas><canvas class="flot-overlay" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 286px; height: 60px;" width="286" height="60"></canvas></div>

					</div>

					<div class="m-t-xs">



						<div class="row">

							<div class="col-xs-5">

								<small class="stat-label">Всего</small>

								<h4><?php echo number_format((float)$total_indicators_2['profit_sum_total'], 2, ',', ' '); ?></h4>

							</div>

							<div class="col-xs-7">

								<small class="stat-label">Выполнен</small>

								<h4><?php echo number_format((float)$total_indicators['profit_sum_total'], 2, ',', ' '); ?></h4>

							</div>

						</div>

					</div>

				</div>

				<div class="panel-footer">

					Прибыль

					<a onClick="statistical_settings('profit');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

		</div>

		<script>

			jQuery(document).ready(function () {

				var chartIncomeData = [

					{

						label: "line",

						data: [ [1, 10], [2, 26], [3, 16], [4, 36], [5, 32], [6, 51] ]

					}

				];



				var chartIncomeOptions = {

					series: {

						lines: {

							show: true,

							lineWidth: 0,

							fill: true,

							fillColor: "#64cc34"



						}

					},

					colors: ["#62cb31"],

					grid: {

						show: false

					},

					legend: {

						show: false

					}

				};



				$.plot($("#flot-income-chart"), chartIncomeData, chartIncomeOptions);

			});

		</script>







		<?php



		// -------------------------------------------------------------------



		?>



	</div>

</div>

































<div class="col-lg-12">

	<div class="row">

		

		<div class="col-lg-3">

			<?php

			$SQL_SELECT_TOTAL_INDICATORS = "SELECT COUNT(*) AS 'count_need_total' FROM `users` WHERE `time_registered` BETWEEN ? AND ?;";

			$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

			$total_indicators_query->execute(array($time_start, ($time_end - 1)));

			$total_indicators = $total_indicators_query->fetch();

			?>

			<div class="hpanel stats">

				<div class="panel-body" style="height: 138px;">

					<div class="stats-title pull-left">

						<h4>Новые пользователи</h4>

					</div>

					<div class="stats-icon pull-right">

						<i class="pe-7s-add-user fa-4x"></i>

					</div>

					<div class="m-t-xl">

						<h1 class="text-success">+<?php echo number_format((float)$total_indicators['count_need_total'], 0, '.', ' '); ?> <small class="text-success">чел.</small></h1>

						<span class="font-bold no-margins">

							<?php

							$SQL_SELECT_TOTAL_INDICATORS = "SELECT COUNT(*) AS 'count_total' FROM `users`;";

							$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

							$total_indicators_query->execute();

							$total_indicators = $total_indicators_query->fetch();

							?>

							<small>Всего:</small> <?php echo number_format((float)$total_indicators['count_total'], 0, '.', ' '); ?> <small>чел.</small>

						</span>

					</div>

				</div>

				<div class="panel-footer">

					Новые пользователи

					<a onClick="statistical_settings('new_users');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

			

			<?php

			$SQL_SELECT_TOTAL_INDICATORS = "SELECT COUNT(*) AS 'count_need_total' FROM `users` WHERE `time_last_visit` BETWEEN ? AND ?;";

			$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

			$total_indicators_query->execute(array($time_start, ($time_end)));

			$total_indicators = $total_indicators_query->fetch();

			?>

			<div class="hpanel stats">

				<div class="panel-body" style="height: 139px;">

					<div class="stats-title pull-left">

						<h4>Активные пользователи</h4>

					</div>

					<div class="stats-icon pull-right">

						<i class="pe-7s-gleam fa-4x"></i>

					</div>

					<div class="m-t-xl">

						<h1 class="text-success"><?php echo number_format((float)$total_indicators['count_need_total'], 0, '.', ' '); ?> <small class="text-success">чел.</small></h1>

						<span class="font-bold no-margins">

							Проверяется дата последнего визита на сайт

						</span>

					</div>

				</div>

				<div class="panel-footer">

					Активные пользователи

					<a onClick="statistical_settings('active_users');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

		</div>

		

		

		

		

		

		

		

		

		

		

		<?php

		if($statistical_settings["user_rating"] == 'order_cnt'){

		?>

		<div class="col-lg-3">

			<div class="hpanel stats">

				<div class="panel-body list" style="height: 341px;">

					<div class="pull-right">

						<a onClick="statistical_settings('', 'order_sum');" class="btn btn-xs btn-default">Сумма</a>

						<a onClick="statistical_settings('', 'order_cnt');" class="btn btn-xs btn-default" style="background: #64cc34; color: #fff;">Количество</a>

					</div>

					<div class="panel-title">Рейтинг по количеству заказов</div>

					<small class="fo">Завершенные заказы</small>

					<div class="list-item-container" style="width:100%;">

						<?php

						$SQL_SELECT_TOTAL_INDICATORS = "SELECT 

							`user_id`, 

							

							(SELECT `data_value` FROM `users_profiles` WHERE `user_id` = `users`.`user_id` AND `data_key` = 'surname') AS 'surname', 

							(SELECT `data_value` FROM `users_profiles` WHERE `user_id` = `users`.`user_id` AND `data_key` = 'name') AS 'name', 

							

							( CAST( ( SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id` IN(SELECT `id` FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?) AND `status` NOT IN (".implode(',', $orders_statuses_for_inverse).") ) AS DECIMAL(20,2) ) ) AS `price_sum_total`, 

							

							( CAST( ( SELECT SUM(`count_need`) FROM `shop_orders_items` WHERE `order_id` IN(SELECT `id` FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?) AND `status` NOT IN (".implode(',', $orders_statuses_for_inverse).") ) AS DECIMAL(20,2) ) ) AS `count_need_total`, 

							

							( CAST( ( SELECT COUNT(*) FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ? ) AS DECIMAL(20,2) ) ) AS `count_total` 

							

						FROM `users` ORDER BY `count_total` DESC LIMIT 4;

						";

						$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

						$total_indicators_query->execute(array($time_start, ($time_end - 1), $time_start, ($time_end - 1), $time_start, ($time_end - 1)));

						while($total_indicators = $total_indicators_query->fetch()){

							if($class != 'text-success'){

								$class = 'text-success';

							}else{

								$class = 'text-color3';

							}

						?>

						<div class="list-item">

							<h3 class="no-margins font-extra-bold <?php echo $class; ?>"><?php echo (int) $total_indicators['count_total']; ?></h3>

							<small>ID <?php echo $total_indicators['user_id'] .' - '. $total_indicators['name'] .' '. $total_indicators['surname']; ?></small>

							<div class="pull-right font-bold"> <span title="Сумма по завершенным заказам"><?php echo number_format((float)$total_indicators['price_sum_total'], 2, ',', ' '); ?></span> / <span title="Число позиций в завершенных заказах"><?php echo (int) $total_indicators['count_need_total']; ?> <small>шт.</small></span> </div>

						</div>

						<?php

						}

						?>

					</div>

				</div>

				<div class="panel-footer">

					Рейтинг по количеству заказов

					<a onClick="statistical_settings('user_rating');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

		</div>

		<?php

		}

		else

		{

		?>

		<div class="col-lg-3">

			<div class="hpanel stats">

				<div class="panel-body list" style="height: 341px;">

					<div class="pull-right">

						<a onClick="statistical_settings('', 'order_sum');" class="btn btn-xs btn-default" style="background: #64cc34; color: #fff;">Сумма</a>

						<a onClick="statistical_settings('', 'order_cnt');" class="btn btn-xs btn-default">Количество</a>

					</div>

					<div class="panel-title">Рейтинг по сумме заказов</div>

					<small class="fo">Завершенные заказы</small>

					<div class="list-item-container" style="width:100%;">

						<?php

						$SQL_SELECT_TOTAL_INDICATORS = "SELECT 

							`user_id`, 

							

							(SELECT `data_value` FROM `users_profiles` WHERE `user_id` = `users`.`user_id` AND `data_key` = 'surname') AS 'surname', 

							(SELECT `data_value` FROM `users_profiles` WHERE `user_id` = `users`.`user_id` AND `data_key` = 'name') AS 'name', 

							

							( CAST( ( SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id` IN(SELECT `id` FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?) AND `status` NOT IN (".implode(',', $orders_statuses_for_inverse).") ) AS DECIMAL(20,2) ) ) AS `price_sum_total`, 

							

							( CAST( ( SELECT SUM(`count_need`) FROM `shop_orders_items` WHERE `order_id` IN(SELECT `id` FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ?) AND `status` NOT IN (".implode(',', $orders_statuses_for_inverse).") ) AS DECIMAL(20,2) ) ) AS `count_need_total`, 

							

							( CAST( ( SELECT COUNT(*) FROM `shop_orders` WHERE `user_id` = `users`.`user_id` AND $WHERE_CONDITIONS_office_id `status` IN (".implode(',', $orders_statuses_for_finish).") AND `time` BETWEEN ? AND ? ) AS DECIMAL(20,2) ) ) AS `count_total` 

							

						FROM `users` ORDER BY `price_sum_total` DESC LIMIT 4;

						";

						$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

						$total_indicators_query->execute(array($time_start, ($time_end - 1), $time_start, ($time_end - 1), $time_start, ($time_end - 1)));

						while($total_indicators = $total_indicators_query->fetch()){

							if($class != 'text-success'){

								$class = 'text-success';

							}else{

								$class = 'text-color3';

							}

						?>

						<div class="list-item">

							<h3 class="no-margins font-extra-bold <?php echo $class; ?>"><?php echo number_format((float)$total_indicators['price_sum_total'], 2, ',', ' '); ?></h3>

							<small>ID <?php echo $total_indicators['user_id'] .' - '. $total_indicators['name'] .' '. $total_indicators['surname']; ?></small>

							<div class="pull-right font-bold"> <span title="Число завершенных заказов"><?php echo (int) $total_indicators['count_total']; ?></span> / <span title="Число позиций в завершенных заказах"><?php echo (int) $total_indicators['count_need_total']; ?> <small>шт.</small></span> </div>

						</div>

						<?php

						}

						?>

					</div>

				</div>

				<div class="panel-footer">

					Рейтинг по сумме заказов

					<a onClick="statistical_settings('user_rating');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

		</div>

		<?php

		}

		?>

		

		

		

		

		

		

		

		

		

		

		<?php

		if($statistical_settings["product_rating"] == 'products'){

		?>

		<div class="col-lg-6">

			<div class="hpanel">

				<div class="panel-body list" style="height: 341px;">

					<div class="pull-right">

						<a onClick="statistical_settings('', '', 'products');" class="btn btn-xs btn-default" style="background: #64cc34; color: #fff;">Заказы</a>

						<a onClick="statistical_settings('', '', 'search');" class="btn btn-xs btn-default">Поиск</a>

					</div>

					<div class="panel-title">Рейтинг товаров</div>

					<small class="fo">Товары оформленные в заказ</small>

					<br/>

					<br/>

					<div class="table-responsive project-list">

						<table class="table table-striped" style="margin-bottom: 0;">

							<thead>

							<tr>

								<th><small>Артикул</small></th>

								<th><small>Производитель</small></th>

								<th><small>Наименование</small></th>

								<th><small>Заказы</small></th>

								<th style="white-space: nowrap;"><small>Кол-во</small></th>

							</tr>

							</thead>

							<tbody>

							<?php

							$SQL_SELECT_TOTAL_INDICATORS = "

							SELECT 

							`tt`.`id`,

							`tt`.`t2_article`,

							`tt`.`t2_manufacturer`,

							`tt`.`t2_name`,

							`tt`.`cnt_id`,

							`tt`.`cnt`



							FROM(



							SELECT 

							`t`.`id`,

							`t`.`t2_article`,

							`t`.`t2_manufacturer`,

							`t`.`t2_name`,

							COUNT(`t`.`id`) AS 'cnt_id',

							SUM(`t`.`count_need`) AS `cnt`



							FROM(

							 

							SELECT `id`, `count_need`, `t2_article`, `t2_manufacturer`, `t2_name` FROM `shop_orders_items` WHERE `t2_article` != '' AND `order_id` IN(SELECT `id` FROM `shop_orders` WHERE $WHERE_CONDITIONS_office_id `time` BETWEEN ? AND ?) 



							) AS `t` GROUP BY `t`.`t2_article`, `t`.`t2_manufacturer`, `t`.`t2_name`, `t`.`id` 

							) AS `tt` ORDER BY `cnt_id` DESC LIMIT 5

							";

							$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

							$total_indicators_query->execute(array($time_start, ($time_end - 1)));

							while($total_indicators = $total_indicators_query->fetch()){

								

								if( ! isset($total_indicators['t2_article']) ){

									$total_indicators['t2_article'] = '';

								}

								if( ! isset($total_indicators['t2_manufacturer']) ){

									$total_indicators['t2_manufacturer'] = '';

								}

								if( ! isset($total_indicators['t2_name']) ){

									$total_indicators['t2_name'] = '';

								}

								if( ! isset($total_indicators['cnt_id']) ){

									$total_indicators['cnt_id'] = '';

								}

								if( ! isset($total_indicators['cnt']) ){

									$total_indicators['cnt'] = '';

								}

								

							?>

							<tr>

								<td>

									<?php echo $total_indicators['t2_article']; ?>

								</td>

								<td>

									<?php echo $total_indicators['t2_manufacturer']; ?>

								</td>

								<td style="font-size: 11px;">

									<?php echo $total_indicators['t2_name']; ?>

								</td>

								<td>

									<?php echo $total_indicators['cnt_id']; ?>

								</td>

								<td>

									<?php echo $total_indicators['cnt']; ?>

								</td>

							</tr>

							<?php

							}

							?>

							</tbody>

						</table>

					</div>

				</div>

				<div class="panel-footer">

					Рейтинг товаров

					<a onClick="statistical_settings('product_rating');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

		</div>

		<?php

		}

		else

		{

		?>

		<div class="col-lg-6">

			<div class="hpanel">

				<div class="panel-body list" style="height: 341px;">

					<div class="pull-right">

						<a onClick="statistical_settings('', '', 'products');" class="btn btn-xs btn-default">Заказы</a>

						<a onClick="statistical_settings('', '', 'search');" class="btn btn-xs btn-default" style="background: #64cc34; color: #fff;">Поиск</a>

					</div>

					<div class="panel-title">Рейтинг запросов</div>

					<small class="fo">Запросы поиска по артикулу</small>

					<br/>

					<br/>

					<div class="table-responsive project-list">

						<table class="table table-striped" style="margin-bottom: 0;">

							<thead>

							<tr>

								<th><small>Артикул</small></th>

								<th><small>Производитель</small></th>

								<th><small>Наименование</small></th>

								<th style="white-space: nowrap;"><small>Кол-во</small></th>

							</tr>

							</thead>

							<tbody>

							<?php

							$SQL_SELECT_TOTAL_INDICATORS = "

							SELECT DISTINCT(`article`), MAX(`manufacturer`) AS `manufacturer`,  MAX(`name`) AS `name`, COUNT(`article`) AS `cnt` FROM `shop_stat_article_queries` WHERE `article` != '' AND `time` BETWEEN ? AND ? GROUP BY `article` ORDER BY `cnt` DESC LIMIT 5;

							";

							$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);

							$total_indicators_query->execute(array($time_start, ($time_end - 1)));

							while($total_indicators = $total_indicators_query->fetch()){

								

								if( ! isset($total_indicators['article']) ){

									$total_indicators['article'] = '';

								}

								if( ! isset($total_indicators['manufacturer']) ){

									$total_indicators['manufacturer'] = '';

								}

								if( ! isset($total_indicators['name']) ){

									$total_indicators['name'] = '';

								}

								if( ! isset($total_indicators['cnt']) ){

									$total_indicators['cnt'] = '';

								}

								

							?>

							<tr>

								<td>

									<?php echo $total_indicators['article']; ?>

								</td>

								<td>

									<?php echo $total_indicators['manufacturer']; ?>

								</td>

								<td style="font-size: 11px;">

									<?php echo $total_indicators['name']; ?>

								</td>

								<td>

									<?php echo $total_indicators['cnt']; ?>

								</td>

							</tr>

							<?php

							}

							?>

							</tbody>

						</table>

					</div>

				</div>

				<div class="panel-footer">

					Рейтинг запросов

					<a onClick="statistical_settings('product_rating');" class="btn btn-default btn-xs pull-right">Подробнее</a>

				</div>

			</div>

		</div>

		<?php

		}

		?>

		

	</div>

</div>





















