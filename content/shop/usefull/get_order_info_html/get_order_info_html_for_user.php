<?php
//Скрипт формирует HTML представление данных о заказе для уведомления клиента
define('_ASTEXE_',1);// Нужно для подключаемых скриптов

//........................................................................................................
/*
//Для отладки и тестирования
if(empty($DP_Config)){
	//Конфигурация CMS
	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
	$DP_Config = new DP_Config;


	//Подключение к БД
	try
	{
		$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	}
	catch (PDOException $e) 
	{
		$result = array();
		$result["status"] = false;
		$result["message"] = "No DB connect";
		exit(json_encode($result));
	}
	$db_link->query("SET NAMES utf8;");
	
	//Для работы с пользователем
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
	
	if(empty($order_id)){
		$order_id = 1;
	}
}
*/
//........................................................................................................



//Вспомогательные данные необходимые для формирования уведомления:
//Статусы заказа
$orders_statuses = array();
$orders_statuses_query = $db_link->prepare('SELECT * FROM `shop_orders_statuses_ref` ORDER BY `order` ASC;');
$orders_statuses_query->execute();
while($status_record = $orders_statuses_query->fetch(PDO::FETCH_ASSOC) )
{
    $orders_statuses[$status_record["id"]] = $status_record;
}
//Статусы позиций
$orders_items_statuses = array();
$orders_items_statuses_not_count = array();
$orders_items_statuses_query = $db_link->prepare('SELECT * FROM `shop_orders_items_statuses_ref` ORDER BY `order` ASC;');
$orders_items_statuses_query->execute();
while($status_record = $orders_items_statuses_query->fetch(PDO::FETCH_ASSOC) )
{
    //Все статусы позиций
    $orders_items_statuses[$status_record["id"]] = $status_record;
    
    //Не учитываемые при ценовых расчетах
    if($status_record["count_flag"] == 0)
    {
        array_push($orders_items_statuses_not_count, $status_record["id"]);
    }
}
//Склады
$storages_list = array();
$storages_query = $db_link->prepare('SELECT `id`,`name` FROM `shop_storages`;');
$storages_query->execute();
while( $storage = $storages_query->fetch() )
{
    $storages_list[$storage["id"]] = $storage["name"];
}



//........................................................................................................


//Настройки шаблона
$templates = array();
$templates_query = $db_link->prepare('SELECT * FROM `templates` WHERE `is_frontend` = 1 AND `current` = 1 LIMIT 1;');
$templates_query->execute();
$templates = $templates_query->fetch();
$templates = json_decode($templates['data_value'], true);


//........................................................................................................



//Формируем информацию о заказе

ob_start();

//Подстрока с условиями фильтрования статусов позиций, которые не участвуют в ценовых расчетах
$WHERE_statuses_not_count = "";
for($i=0; $i<count($orders_items_statuses_not_count); $i++)
{
	$WHERE_statuses_not_count .= " AND `status` != ".(int)$orders_items_statuses_not_count[$i];
}

//Для подсчета суммы оплаты по заказу
$INCOME_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `order_id` = ?), 0)";
$ISSUE_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `order_id` = ?), 0)";

//Получаем данные заказа
$order_query = $db_link->prepare("SELECT *, CAST( (SELECT SUM(`price`*`count_need`-`price`*`count_need`*`discount`/100) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) AS DECIMAL(20,2)) AS `price_sum`, CAST( ($ISSUE_SQL - $INCOME_SQL) AS DECIMAL(8,2) ) AS `paid_sum`, CAST( ( (SELECT SUM(`price`*`count_need`-`price`*`count_need`*`discount`/100) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) - ($ISSUE_SQL - $INCOME_SQL) ) AS DECIMAL(8,2) )  AS `paid_left` FROM `shop_orders` WHERE `id` = ?;");
$order_query->execute( array($order_id, $order_id, $order_id, $order_id, $order_id) );
$order = $order_query->fetch();
if( !empty($order) )
{
	// Получаем список способов оплаты:
	$shop_orders_paid_type = array();
	$query = $db_link->prepare('SELECT * FROM `shop_orders_paid_type` WHERE `active` = 1 ORDER BY `order`;');
	$query->execute();
	while($rov = $query->fetch()){
		$shop_orders_paid_type[$rov['id']] = $rov['name'];
	}
	
	$time = $order["time"];
	$office_id = $order["office_id"];
	$status_id = $order["status"];
	$paid = $order["paid"];
	$paid_type = $order["paid_type"];
	$price_sum = $order["price_sum"];
	$paid_sum = $order["paid_sum"];
	$paid_left = $order["paid_left"];
	$customer_id = $order["user_id"];
	$how_get = $order["how_get"];
	$how_get_json = json_decode($order["how_get_json"], true);
?>

<div style="margin-top:10px;">

<?php
if($customer_id > 0){
?>
<a style="background: <?=(!empty($templates['main_color']))?$templates['main_color']:'#799658';?>; color: #fff; text-decoration: none; padding: 7px 13px; font-size: 16px; border-radius: 5px; display: inline-block;" target="_blank" href="<?php echo $DP_Config->domain_path; ?>shop/orders/order?order_id=<?php echo $order_id; ?>">Перейти на страницу заказа</a>
<?php
}else{
?>
<a style="background: <?=(!empty($templates['main_color']))?$templates['main_color']:'#799658';?>; color: #fff; text-decoration: none; padding: 7px 13px; font-size: 16px; border-radius: 5px; display: inline-block;" target="_blank" href="<?php echo $DP_Config->domain_path; ?>shop/orders/zakaz-bez-registracii?order_id=<?php echo $order_id; ?>">Перейти на страницу заказа</a>
<?php
}
?>

<h4>Информация о заказе</h4>
	
<table>
	<tr> <td>Номер заказа</td> <td><?php echo $order_id; ?></td> </tr>
	<tr> <td>Создан</td> <td><?php echo date("d.m.Y", $time)." ".date("G:i", $time); ?></td> </tr>
	<tr> <td>Статус</td> <td><?php echo $orders_statuses[$status_id]["name"]; ?></td> </tr>
	<tr> <td>Способ оплаты</td> <td><?=(!empty($shop_orders_paid_type[$paid_type]))?$shop_orders_paid_type[$paid_type]:'';?></td> </tr>
</table>

<h4>Платежи по заказу</h4>

<div style="overflow: hidden; overflow-x: auto;">
	<table>
		<tr>
			<td>Состояние оплаты</td>
			<td>Сумма заказа</td>
			<td>Оплачено</td>
			<td>Осталось оплатить</td>
		</tr>
		<tr>
			<td>
				<strong>
					<?php 
					switch( $paid )
					{
						case 0:
							echo '<div style="color:#FFF;background-color:#e74c3c;border-radius:3px;padding:6px 12px;font-weight:normal;">Не оплачен</div>';
							break;
						case 1:
							echo '<div style="color:#FFF;background-color:#62cb31;border-radius:3px;padding:6px 12px;font-weight:normal;">Оплачен полностью</div>';
							break;
						case 2:
							echo '<div style="color:#FFF;background-color:#3498db;border-radius:3px;padding:6px 12px;font-weight:normal;">Оплачен частично</div>';
							break;
					}
					?>
				</strong>
			</td>
			<td><?php echo $price_sum; ?></td>
			<td><?php echo $paid_sum; ?></td>
			<td><?php echo $paid_left; ?></td>
		</tr>
	</table>
</div>

<div style="overflow-x:auto; margin-top:0px;">
<?php
//Способ получения
//Получаем имя папки с обработчиком
$obtain_query = $db_link->prepare( 'SELECT * FROM `shop_obtaining_modes` WHERE `id` = ?;' );
$obtain_query->execute( array($how_get) );
$obtain_mode = $obtain_query->fetch();
include($_SERVER["DOCUMENT_ROOT"]."/content/shop/obtaining_modes/".$obtain_mode["handler"]."/show_actual_info.php");
?>
</div>
	
<?php
}

//........................................................................................................

//Позиции заказа

$items_counter = 0;
$count_need_total = 0;
$price_sum_total = 0;
$price_purchase_sum_total = 0;
$profit_total = 0;

//Запрос закупа
$SELECT_price_purchase_sum = "IFNULL((SELECT SUM(`price_purchase`*`count_reserved`) FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id`), CAST(`t2_price_purchase`*`count_need` AS DECIMAL(8,2)))";

//Сумма позиции
$SELECT_item_price_sum = "CAST(`price`*`count_need`-`price`*`count_need`*`discount`/100 AS DECIMAL(8,2))";

//Маржа позиции
$SELECT_item_profit = "CAST($SELECT_item_price_sum - $SELECT_price_purchase_sum AS DECIMAL(8,2))";

//СЛОЖНЫЙ ВЛОЖЕННЫЙ ЗАПРОС
$SELECT_ORDER_ITEMS = "SELECT *, $SELECT_price_purchase_sum AS `price_purchase_sum`, $SELECT_item_price_sum AS `price_sum`, $SELECT_item_profit AS `profit` FROM `shop_orders_items` WHERE `order_id` = ?;";

$order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
$order_items_query->execute( array($order_id) );
?>

<h4>Позиции заказа</h4>

<table>
	<tr>
		<td>ID</td>
		<td>Производитель</td>
		<td>Артикул</td>
		<td>Наименование</td>
		<td>Цена</td>
		<td>Количество</td>
		<td>Сумма</td>
		<td>Статус</td>
		<td>Срок</td>
	</tr>

	<?php
	while( $order_item = $order_items_query->fetch() )
	{
		$item_id            = $order_item["id"];
		$item_status        = $order_item["status"];
		$item_count_need    = $order_item["count_need"];
		$item_price         = $order_item["price"];
		$item_price_sum     = $order_item["price_sum"];
		$item_product_type  = $order_item["product_type"];
		$item_product_id    = $order_item["product_id"];
		$item_price_purchase_sum = $order_item["price_purchase_sum"];
		$item_product_name  = $order_item["t2_name"];
		$item_article 		= $order_item["t2_article"];
		$item_manufacturer 	= $order_item["t2_manufacturer"];
		$item_profit        = $order_item["profit"];
		
		$item_t2_time_to_exe = $order_item["t2_time_to_exe"];
		$item_t2_time_to_exe_guaranteed = $order_item["t2_time_to_exe_guaranteed"];
		
		//Срок доставки для продуктов типа 2
		if($item_t2_time_to_exe < $item_t2_time_to_exe_guaranteed)
		{
			$item_t2_time_to_exe = $item_t2_time_to_exe." - ".$item_t2_time_to_exe_guaranteed;
		}
		$item_t2_time_to_exe = $item_t2_time_to_exe." дн.";
		
		//Считаем поля ИТОГО ПО ЗАКАЗУ (если статус позиции позволяет)
		if( array_search($item_status, $orders_items_statuses_not_count) === false)
		{
			$count_need_total += $item_count_need;
			$price_sum_total += $item_price_sum;
			$price_purchase_sum_total += $item_price_purchase_sum;
			$profit_total += $item_profit;
		}
	?>
	
	<tr style="background: <?php echo $orders_items_statuses[$item_status]["color"]; ?>;">
		<td><?php echo $item_id; ?></td>
		<td><?php echo $item_manufacturer; ?></td>
		<td><?php echo $item_article; ?></td>
		<td><?php echo $item_product_name; ?></td>
		<td><?php echo number_format($item_price, 2, '.', ''); ?></td>
		<td><?php echo $item_count_need; ?></td>
		<td><?php echo number_format($item_price_sum, 2, '.', ''); ?></td>
		<td><?php echo $orders_items_statuses[$item_status]["name"]; ?></td>
		<td><?php echo $item_t2_time_to_exe; ?></td>
	</tr>
	
	<?php
		$items_counter++;
	}//while - по позициям заказа
	?>

	<tr>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td>Итого</td>
		<td><?php echo $count_need_total; ?></td>
		<td><?php echo number_format($price_sum_total, 2, '.', ''); ?></td>
		<td></td>
		<td></td>
	</tr>

</table>

</div>

<?php
$order_text = ob_get_clean();

//Для отладки и тестирования
/*
$email_subject = 'Создан новый заказ №2';
$email_body = 'Создан новый заказ №2'.$order_text;
require_once($_SERVER["DOCUMENT_ROOT"]."/content/notifications/template.php");
echo $email_body;
*/
?>