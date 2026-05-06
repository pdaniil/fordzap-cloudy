<?php
// Скрипт добавляет позицию в заказ из модального окна проценки в панели управления

/*
$f = fopen('log_w.txt', 'w');
fwrite($f, $_POST['product_objects']);
*/

/*
$_POST['order_id'] = 3;
$_POST['product_objects'] = '[{"manufacturer":"DOLZ","article":"C110","article_show":"C110","name":"Водяная помпа","exist":3,"price":1941,"time_to_exe":1,"time_to_exe_guaranteed":1,"storage":"MSK","min_order":1,"probability":0,"office_id":"1","storage_id":"3","office_caption":"Основная точка обслуживания","color":"#ffffff","storage_caption":"Froza","price_purchase":"1941.00","markup":0,"product_type":2,"product_id":0,"storage_record_id":0,"product_url":null,"json_params":"{\"gid\":\"202502W20\"}","valid":true,"check_hash":"c15db76efaf03a3ac1cae01e39d16d7b","search_name":null,"url":"","groups_check_hash":{"1":"c15db76efaf03a3ac1cae01e39d16d7b","2":"c15db76efaf03a3ac1cae01e39d16d7b","3":"c15db76efaf03a3ac1cae01e39d16d7b"},"groups_price":{"1":1941,"2":1941,"3":1941},"groups_markup":{"1":0,"2":0,"3":0},"aid":0,"count_need":1}]';
*/



$order_id = (int) $_POST["order_id"];
$product_objects = json_decode($_POST['product_objects'], true);



if(empty($product_objects) || empty($order_id))
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "No Data";
	exit( json_encode($answer) );
}



require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");

// -------------------------------------------------------------------------------
//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
// -------------------------------------------------------------------------------

//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

//Проверяем право менеджера
if( ! DP_User::isAdmin())
{
	$result["status"] = false;
	$result["message"] = "Forbidden";
	$result["code"] = 501;
	exit(json_encode($result));//Вообще не является администратором бэкенда
}

//Проверяем существует ли заказ с переданным ID и он должен быть не оплачен что бы можно было добавить позиции в него
$sql = "SELECT * FROM `shop_orders` WHERE `id` = ?;";
$query = $db_link->prepare($sql);
$query->execute(array($order_id));
$record = $query->fetch();
if(empty($record) || (int)$record['paid'] !== 0)
{
	$result["status"] = false;
	$result["message"] = "Forbidden";
	$result["code"] = 404;
	exit(json_encode($result));
}

//Статус для создаваемых позиций заказа:
$for_created_status_query = $db_link->prepare('SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_created`=1;');
$for_created_status_query->execute();
$for_created_status_record = $for_created_status_query->fetch();
$for_created_status = $for_created_status_record["id"];



//СОЗДАНИЕ ЗАКАЗА ЧЕРЕЗ ТРАНЗАКЦИЮ
try
{
	//Меняем статус autocommit на FALSE. Т.е. старт транзакции
	if( ! $db_link->beginTransaction()  )
	{
		throw new Exception("Не удалось стартовать транзакцию");
	}
	
	// Цикл по позициям
	foreach($product_objects as $product_item)
	{
		$product_type = 2;
		$product_id = 0;
		$price = $product_item["price"];
		$count_need = $product_item["count_need"];

		$t2_manufacturer = $product_item["manufacturer"];
		$t2_article = $product_item["article"];
		$t2_article_show = $product_item["article_show"];
		$t2_name = $product_item["name"];
		$t2_exist = $product_item["exist"];
		$t2_time_to_exe = $product_item["time_to_exe"];
		$t2_time_to_exe_guaranteed = $product_item["time_to_exe_guaranteed"];
		$t2_storage = $product_item["storage"];
		$t2_min_order = $product_item["min_order"];
		$t2_probability = $product_item["probability"];
		$t2_markup = $product_item["markup"];
		$t2_price_purchase = $product_item["price_purchase"];
		$t2_office_id = $product_item["office_id"];
		$t2_office_id_first = $t2_office_id;
		$t2_storage_id = $product_item["storage_id"];
		$t2_json_params = $product_item["json_params"];
		$t2_product_json = '';
		
		
		//Проверяем хеш, защищающий от подмены данных злоумышлненниками через JavaScript
		$check_hash = md5($t2_manufacturer . $t2_article . $t2_article_show . $t2_name . $t2_exist . $price . $t2_time_to_exe . $t2_time_to_exe_guaranteed . $t2_storage . $t2_min_order . $t2_probability . $t2_office_id . $t2_storage_id . $t2_price_purchase . $t2_markup . $t2_json_params . "2" . $DP_Config->tech_key);
		if($check_hash != $product_item["check_hash"])
		{
			throw new Exception("Ошибка check_hash");
		}
		
		
		//Получаем значения sao_state и sao_robot для данного поставщика
		$sao_state = "( SELECT IFNULL( (SELECT `state_id` FROM `shop_sao_states_types_link` WHERE `is_start` = 1 AND `interface_type_id` = (SELECT `interface_type` FROM `shop_storages` WHERE `id` = ?)) ,0) AS `state_id` )";
		
		$sao_robot = "( SELECT IFNULL( (SELECT `action_id` FROM `shop_sao_states_types_actions_link` WHERE `is_start` = 1 AND `state_type_id` = (SELECT `id` FROM `shop_sao_states_types_link` WHERE `is_start` = 1 AND `interface_type_id` = (SELECT `interface_type` FROM `shop_storages` WHERE `id` = ?) )) ,0) AS `action_id` )";
	
		$SQL_INSERT_ORDER_ITEM = "INSERT INTO `shop_orders_items` 
		(`order_id`, 
		`product_type`, 
		`price`, 
		`count_need`, 
		`product_id`, 
		`status`,
		`t2_manufacturer`,
		`t2_article`,
		`t2_article_show`,
		`t2_name`,
		`t2_exist`,
		`t2_time_to_exe`,
		`t2_time_to_exe_guaranteed`,
		`t2_storage`,
		`t2_min_order`,
		`t2_probability`,
		`t2_markup`,
		`t2_price_purchase`,
		`t2_office_id`,
		`t2_storage_id`,
		`t2_product_json`,
		`sao_state`,
		`sao_robot`,
		`t2_json_params`
		) 
		VALUES 
		(";
		
		$binding_values = array($order_id,$product_type,$price,$count_need,$product_id,$for_created_status,$t2_manufacturer,$t2_article,$t2_article_show,$t2_name,$t2_exist,$t2_time_to_exe,$t2_time_to_exe_guaranteed,$t2_storage,$t2_min_order,$t2_probability,$t2_markup,$t2_price_purchase,$t2_office_id,$t2_storage_id,$t2_product_json);
		
		$SQL_INSERT_ORDER_ITEM = $SQL_INSERT_ORDER_ITEM.str_repeat('?,', count($binding_values) - 1) . '?,'.$sao_state.','.$sao_robot.',?);';
		
		//Добавим оставшиеся переменные
		array_push($binding_values, $t2_storage_id);//Из $sao_state
		array_push($binding_values, $t2_storage_id);//Из $sao_robot
		array_push($binding_values, $t2_json_params);

		if( $db_link->prepare($SQL_INSERT_ORDER_ITEM)->execute( $binding_values ) != true)
		{
			throw new Exception("SQL-ошибка добавления товароной позиции заказа.");
		}
		$order_item_id =$db_link->lastInsertId();//ID добавленной записи (позиция заказа)
		
		if( $order_item_id <= 0 )
		{
			throw new Exception("Ошибка определения ID товарной позиции заказа.");
		}
		
		//Пишем лог заказа
		$db_link->prepare('INSERT INTO `shop_orders_logs` (`order_id`,`time`,`user_id`,`is_manager`,`text`, `is_robot`) VALUES (?, ?, ?, ?, ?, ?);')->execute( array($order_id, time(), DP_User::getAdminId(), 1,'В заказ добавлена позиция из проценки id '.$order_item_id.' - <b>'.$t2_manufacturer.' - '.$t2_article.'</b> с ценой: '.$price, 0) );
	}
}
catch (Exception $e)
{
	$db_link->rollBack();//Откатываем все изменения и закрываем транзакцию
	
	
	$result = array();
	$result["status"] = false;
	$result["message"] = "Ошибка оформления заказа. ".$e->getMessage();
	exit(json_encode($result));
}


//Дошли сюда - значит все запросы выполнены без ошибок
$db_link->commit();//Коммитим все изменения и закрываем транзакцию


$result = array();
$result["status"] = true;
exit(json_encode($result));
?>