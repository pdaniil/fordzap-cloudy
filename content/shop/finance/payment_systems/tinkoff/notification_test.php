<?php
require_once("include.php");
$operation_id = $_POST["OrderId"];
//Общий скрипт получения настроек платежной системы.
//require_once( $_SERVER['DOCUMENT_ROOT'].'/content/shop/finance/get_pay_system_parameters.php' );
//$terminal_id = $paysystem_parameters["terminal_id"];
//$password = $paysystem_parameters["password"];
$data = json_decode('{"TerminalKey":"1736499616296","OrderId":"7745","Success":"true","Status":"CONFIRMED","PaymentId":"5782327772","ErrorCode":"0","Amount":"1000","RebillId":"","SpAccumulationId":"","Pan":"+7(910)***-**-50","Token":"f39b54094ba11e65e631ac16e3450453c36467a3f06f1b4b66fd7e424ab99472"}', true);

echo "<pre>";
print_r($data);
echo "</pre>";

$order_id	= $data["OrderId"];

print_r($order_id);




$operation_query = $db_link->prepare('SELECT * FROM `shop_users_accounting` WHERE `id` = ?;');
$operation_query->execute( array($order_id) );
$operation = $operation_query->fetch();

echo "<pre>";
print_r($operation);
echo "</pre>";

exit();
	 
//Получаем сумму
$amount_query = $db_link->prepare('SELECT `amount` FROM `shop_users_accounting` WHERE `id` = ?;');
$amount_query->execute( array($order_id) );
$amount_record = $amount_query->fetch();
// -----
//Уведомление менеджерам магазинов
$operation_id = $order_id;
$amount = $amount_record["amount"];
//require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/finance/pay_notify.php");
// -----
// -----
//Вызов протокола оплаты заказа, если в операцию был вписан номер заказа
$operation_id = $order_id;

//require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/finance/pay_for_order.php");

		


?>