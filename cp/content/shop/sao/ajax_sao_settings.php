<?php
set_time_limit(600);

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $result["status"] = false;
	$result["message"] = "DB connect error";
	$result["code"] = 502;
	exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

$sql = "SET SESSION SQL_BIG_SELECTS = 1";
$query = $db_link->prepare($sql);
$query->execute();

//Проверяем право менеджера
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
// if( ! DP_User::isAdmin())
// {
// 	$answer = array('status'=>false);
// 	exit(json_encode($answer));
// }


$answer = array('status'=>false, 'message' => 'Не удалось выполнить скрипт.');
$request_object = json_decode($_POST['request_object'], true);

switch($request_object['action'])
{
	case 'save_settings_data':
	    
	    $request_object_json = json_encode($request_object);
	    
	    $ex_order_status = json_encode($request_object["statuses_order_data"]);
	    $ex_order_item_status = json_encode($request_object["statuses_order_item_data"]);
	    $ex_order_status_ids = json_encode($request_object["statuses_order_ids"]);
	    $ex_order_item_status_ids = json_encode($request_object["statuses_order_item_ids"]);
	    $actions_enable = json_encode($request_object["actions_enable"]);
	    $enable_service = json_encode($request_object["enable_service"]);
	    $write_log = json_encode($request_object["write_log"]);
	    $positive_balance = json_encode($request_object["positive_balance"]);
	    $storages_data = json_encode($request_object["storages_data"]);
			$storages_data_ids = json_encode($request_object["storages_data_ids"]);
	    $user_groups_data = json_encode($request_object["user_groups_data"]);
	    $user_groups_data_ids = json_encode($request_object["user_groups_data_ids"]);

	    $robot_id = $request_object["robot_id"];
	    
	    $check_robot_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_sao_settings` WHERE `id` = ?;");
	    $check_robot_query->execute(array($robot_id));
	    if ($check_robot_query->fetchColumn() > 0) {
	        //Обновляем
	        $save_robot_query = $db_link->prepare("UPDATE `shop_sao_settings` SET `ex_order_status` =?, `ex_order_item_status` = ?, `ex_order_status_ids` = ?, `ex_order_item_status_ids` = ?, `actions_enable` = ?, `positive_balance` = ?, `write_log` = ?, `enable` = ?, `ex_storages` = ?, `ex_user_groups` = ?, `ex_storages_ids` = ?, `ex_user_groups_ids` = ?;");
    		if($save_robot_query->execute( array($ex_order_status,$ex_order_item_status,$ex_order_status_ids,$ex_order_item_status_ids,$actions_enable,$positive_balance,$write_log,$enable_service,$storages_data,$user_groups_data,$storages_data_ids,$user_groups_data_ids)) != true ) {
    		    
    		    $error = 'Ошибка новой записи в БД. ' . json_encode($db_link->errorInfo());
    		    $answer = array('status'=>false, "message"=>$error);
    		    
    		} else {
    		    
    		    $answer = array('status'=>true);
    		}
	    } else {
	        //Создаем новые настройки
    	    $save_robot_query = $db_link->prepare("INSERT INTO `shop_sao_settings` (ex_order_status,ex_order_item_status,ex_order_status_ids,ex_order_item_status_ids,actions_enable,positive_balance,write_log,enable,ex_storages,ex_user_groups,ex_storages_ids,ex_user_groups_ids) VALUES (?,?,?,?,?,?,?,?,?,?,?,?);");
    		if($save_robot_query->execute( array($ex_order_status,$ex_order_item_status,$ex_order_status_ids,$ex_order_item_status_ids,$actions_enable,$positive_balance,$write_log,$enable_service,$storages_data,$user_groups_data,$storages_data_ids,$user_groups_data_ids)) != true ) {
    		    
    		    $error = 'Ошибка новой записи в БД. ' . json_encode($db_link->errorInfo());
    		    $answer = array('status'=>false, "message"=>$error);
    		    
    		} else {
    		    
    		    $answer = array('status'=>true);
    		}
	    }
	    
        break;
}

exit(json_encode($answer));
?>