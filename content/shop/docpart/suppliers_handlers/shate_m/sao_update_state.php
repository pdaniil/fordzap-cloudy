<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/**
*/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/shate_m/update_state.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 

**********************************************************/


$sao_state_object = json_decode($order_item["sao_state_object"], true);
$order_id = $sao_state_object['id'];

$order_item_id = $order_item['id'];

try {
    
    if(empty($order_id)) {
        throw new Exception("Ошибка получения номера заказа. Запись не найдена.");
    }
        
    $api_path = "";
	switch($connection_options["country"])
	{
		case "ru":
			$api_path = "https://api.shate-m.ru/api/v1";
			break;
		case "by":
			$api_path = "https://api.shate-m.by/api/v1";
			break;
		case "kz":
			$api_path = "https://api.shate-m.kz/api/v1";
			break;
		default: $api_path = "https://api.shate-m.ru/api/v1";
	}
	
	if(!empty($connection_options['login']) && !empty($connection_options['password'])) {
		$url = $api_path . '/auth/login';
		$auth_fields = array(
			'Login' => $connection_options['login'],
			'Password' => $connection_options['password']
		);
	} else if(!empty($connection_options["api_key"])) {
		$url = $api_path . '/auth/loginbyapikey';
		$auth_fields = array(
			'ApiKey' => $connection_options['api_key']
		);
	} else {
	    throw new Exception("Отсутствует Логин и Пароль или Api-Key для авторизации в Шате-М.");
	}
	
	$auth_field_string = http_build_query($auth_fields);
	
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $auth_field_string);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	
	$curl_json = curl_exec($ch);

	$curl_result = json_decode($curl_json, true);
	
	curl_close($ch);
	
	$token = "";

	if(!empty($curl_result['access_token'])) {
	    
		$token = $curl_result['access_token'];
		
	} else {
		throw new Exception("Ошибка получения токена. " . $url);
	}
	

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_path . "/orders?Ids={$order_id}"); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата
	curl_setopt($ch, CURLOPT_POST, false);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"Content-Type: application/json",
		"Authorization: Bearer $token"
	));

	$result = curl_exec($ch);
	$order_result = json_decode($result, true);
	
	echo "<pre>";
    echo "Ответ на запрос";
    echo "</pre>";
    echo "<br>";
    echo "<pre>";
    print_r(curl_getinfo($ch));
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r(curl_error($ch));
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    echo "<br>";
    echo "<pre>";
    print_r($order_result);
    echo "</pre>";
    echo "<br>";

    curl_close($ch);
    
    if(isset($order_result[0]) && !empty($order_result[0]['id'])) {
        
        $order = $order_result[0];
        
        $orderItem = $order['orderItems'][0];
        
        $status_order_id = $orderItem['statusCode'];
        
        ////////////////////
    	$relation_sql = "SELECT `relationship` FROM `shop_storages_statuses_relationship` WHERE storage_id = ?";		
    	$relations_query = $db_link->prepare($relation_sql);
    	$relations_query->execute(array($order_item["t2_storage_id"]));
    	$relations_arr = $relations_query->fetchColumn();
    	$relations_params = json_decode($relations_arr, true);
    	
    	$relations = array();
    	
    	foreach($relations_params as $relations_param) {
    							
    		$relation_info = array(
    			"status_store" => $relations_param["status_store"],
    			"description"  => $relations_param["description"]
    		);
    		
    		$caption = strip_tags(trim($relations_param["status_supplier"]));
    	
    		$relations[$caption] = $relation_info;
    	}
    	////////////////////
    	
    	
    	////////////////////
    	$new_status = $order_item["status"];
    	$relation_field = $status_order_id;
    
        $current_relation_field = "";
        $caption_relation_field = trim($relation_field);
        
        if (!empty($relations[$caption_relation_field])) {
        			 
    		$current_relation_field = $relations[$caption_relation_field];
    		
    		if(!empty($current_relation_field['status_store'])) $new_status = $current_relation_field['status_store'];
    		
        }
        ////////////////////

		if($new_status > 0 && $new_status != $order_item["status"])
		{
			//Отправляем запрос на изменение статуса позиции
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
		}
		
		$date = date("d-m-Y H:i:s");
        $sao_message = "Заказ: {$order['id']}<br />Код статуса: {$status_order_id}</br />Обновлено: {$date}";
		
		//4.1. Состояние позиции, //4.1. Запись технических параметоров SAO, // 4.3. Отображаемые комментарий
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
 		$update_query->bindValue(':sao_message', $sao_message);
		$update_query->bindValue(':id', $order_item_id);
		
		if( !$update_query->execute() )
		{	
			$sao_result["status"] = false;
			$sao_result["message"] = "Не удалось обновить состояние, обратитесь к инженеру Docpart";
		}
		else
		{
			//6. Формируем ответ
	    	$sao_result["status"] = true;
	    	$sao_result["message"] = "Состояние получено.";
		}

    }
    else
    {
        throw new Exception("Произошла ошибка при запросе. Проверьте настройки или повторите позже.");
    }
}
catch (Exception $e)
{
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
}
	


$dump = ob_get_contents();
file_put_contents($pathError, $dump);
ob_end_clean();
?>