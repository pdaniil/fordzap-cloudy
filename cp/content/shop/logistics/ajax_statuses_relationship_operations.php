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

$answer = array('status'=>false, 'message' => 'Не удалось выполнить скрипт.');

$request_action = isset($_POST['action']) ? $_POST['action'] : null;
$request_storage_id = isset($_POST['storage_id']) ? $_POST['storage_id'] : null;
$request_relationship = isset($_POST['relationship']) ? $_POST['relationship'] : array();


if(!empty($request_action)) {
    
    switch($request_action)
    {
    	case 'save_relationship_data':
    	    
    	    if(!empty($request_storage_id)) {
    	        
    	        $relationship_data = json_encode($request_relationship);
    	        
        	    $check_data_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_storages_statuses_relationship` WHERE `storage_id` = ?;");
        	    $check_data_query->execute(array($request_storage_id));
        	    if ($check_data_query->fetchColumn() > 0) {
        	        //Обновляем
        	        $save_data_query = $db_link->prepare("UPDATE `shop_storages_statuses_relationship` SET `relationship` = ? WHERE `storage_id` = ?;");
            		if($save_data_query->execute( array($relationship_data,$request_storage_id)) != true ) {
            		    
            		    $error = 'Ошибка новой записи в БД. ' . $db_link->errorInfo();
            		    $answer = array('status'=>false, "message"=>$error);
            		    
            		} else {
            		    
            		    $answer = array('status'=>true);
            		}
        	    } else {
        	        //Создаем новые настройки
            	    $save_data_query = $db_link->prepare("INSERT INTO `shop_storages_statuses_relationship` (storage_id,relationship) VALUES (?,?);");
            		if($save_data_query->execute( array($request_storage_id,$relationship_data)) != true ) {
            		    
            		    $error = 'Ошибка новой записи в БД. ' . $db_link->errorInfo();
            		    $answer = array('status'=>false, "message"=>$error);
            		    
            		} else {
            		    
            		    $answer = array('status'=>true);
            		}
        	    }
    	    }
            break;
    }
    
}

exit(json_encode($answer));
?>