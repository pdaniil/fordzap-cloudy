<?php

require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
//Проверяем право менеджера
if( ! DP_User::isAdmin() )
{
	$result["status"] = false;
	$result["message"] = "Forbidden";
	$result["code"] = 501;
	exit(json_encode($result));//Вообще не является администратором бэкенда
}
//Менеджер
$manager_id = DP_User::getAdminId();

//Получаем список магазинов в которых данный пользователь указан в качестве менеджера
$manager_id_offices_list = array();
$manager_id_offices_query = $db_link->prepare('SELECT `id` FROM `shop_offices` WHERE `users` LIKE ?;');
$manager_id_offices_query->execute( array('%"'.$manager_id.'"%') );
while($manager_id_offices_row = $manager_id_offices_query->fetch(PDO::FETCH_ASSOC)){
	$manager_id_offices_list[] = $manager_id_offices_row['id'];
}

$manager_id_offices_list_str = implode(",", $manager_id_offices_list);


$date_today = new DateTime('today midnight');
// $date_today = new DateTime('2024-10-04');
$timestamp_today = $date_today->getTimestamp();

$balance_today = 0;
$balance_before = 0;

//Сразу расчет из БД
$INCOME_today_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 1 AND `time` > ? AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";
$ISSUE_today_SQL  = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 0 AND `time` > ? AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";

$INCOME_before_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 1 AND `time` < ? AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";
$ISSUE_before_SQL  = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 0 AND `time` < ? AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";

$SQL_balance = "SELECT 
($INCOME_today_SQL - $ISSUE_today_SQL) AS `balance_today`,
($INCOME_before_SQL - $ISSUE_before_SQL) AS `balance_before`
;";

$query_balance = $db_link->prepare($SQL_balance);
$query_balance->execute( array(
	$timestamp_today, $timestamp_today,
	$timestamp_today, $timestamp_today
	));
$row_balance = $query_balance->fetch(PDO::FETCH_ASSOC);

$balance_today = $row_balance["balance_today"];
$balance_before = $row_balance["balance_before"];

$today_payment_class = '';
if($balance_today > 0) {
	$today_payment_class = 'text-success';
}
else
{
	$today_payment_class = 'text-danger';
}

$before_payment_class = '';
if($balance_before > 0) {
	$before_payment_class = 'text-success';
}
else
{
	$before_payment_class = 'text-danger';
}

?>

<a href="/<?= $DP_Config->backend_dir ;?>/shop/cash" style="color:#e1aa28;">Баланс средств</a> от <?= date("d.m.Y", $timestamp_today) ;?>
<?php if(isset($balance_today) && !empty($balance_today)) : ?>
- <span class="m-xs <?= $today_payment_class ;?>" style="font-weight: 600; font-size: 18px;line-height:1;vertical-align: text-top;" ><?= $balance_today; ?><span class="small" style="font-weight:300;">₽</span></span>
<?php endif; ?>, остаток на начало дня 
<?php if(isset($balance_before) && !empty($balance_before)) : ?>
- <span class="m-xs <?= $before_payment_class ;?>" style="font-weight: 600; font-size: 18px;line-height:1;vertical-align: text-top;" ><?= $balance_before; ?><span class="small" style="font-weight:300;">₽</span></span>
<?php endif; ?>