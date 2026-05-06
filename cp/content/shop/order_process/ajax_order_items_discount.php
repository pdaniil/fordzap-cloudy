<?php

/**

Серверный скрипт удаления заказов

*/
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json;charset=utf-8;');

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

	$result["message"] = "DB connect error";

	exit(json_encode($result));

}

$db_link->query("SET NAMES utf8;");





// -------------------------------------------------------------------------------

//Защита от CSRF-атак

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

// -------------------------------------------------------------------------------


if((isset($_POST['key']) ? $_POST['key'] : '') !== $DP_Config->tech_key)

{

	$result = array();

	$result["status"] = false;

	$result["message"] = "Forbidden";

	exit(json_encode($result));//Вообще не является администратором бэкенда

}


$answer = array('status' => false);

//Получаем список позиций

$items = json_decode($_POST["items"], true);

$orderId = isset($_POST['order_id']) ? $_POST['order_id'] : 0;

$discount = (float)isset($_POST['discount']) ? $_POST['discount'] : 0;

$orderQuery = $db_link->prepare("SELECT * FROM shop_orders WHERE id=?");
$order = $orderQuery->execute(array($orderId)) ? $orderQuery->fetch(PDO::FETCH_ASSOC) : false;

if($discount <= 0){
	$answer['message'] = 'Скидка должна быть выше нуля';
}else if(!$order){
	$answer['message'] = 'Неизвестный заказ';
}else{
	$itemsList = array();
	foreach($items as $item){
		$itemsList[] = (int) $item;
	}
	if(!count($itemsList)){
		$answer['message'] = 'Позиции не выбраны';
	}else{
		$query = $db_link->prepare("UPDATE shop_orders_items SET discount=? WHERE order_id=? AND id IN (".implode(",", $itemsList).")");
		if($query->execute(array($discount, $orderId))){
			$answer['status'] = true;
			$answer['message'] = "Скидка успешно применена";
		}else{
			$answer['message'] = 'Не удалось применить скидку';
		}
	}
}
exit( json_encode($answer) );