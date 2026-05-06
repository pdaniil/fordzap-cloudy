<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/**
SAO
Действие: Заказать

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота

ВЫВОД ДАМПА БУФФЕРИЗИРУЕТСЯ И ПИШЕТСЯ В ФАЙЛ $pathError
*/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/uniqom/DO_ORDER.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 

**********************************************************/

$t2_json_params = json_decode($order_item["t2_json_params"], true);

$good_id = $t2_json_params['good_id'];
$storage_id = $t2_json_params['storage_id'];

$stock_item = [
	"good_id" => $good_id,
	"storage_id" => $storage_id,
	"count" => $order_item["count_need"]
];

$stock_items[] = $stock_item;

$company_id = $connection_options["company_id"];
$store_id = $connection_options["store_id"];
$delivery_id = $connection_options["delivery_id"];
$is_cash = $connection_options["is_cash"];

$order_params = array(
    "company_id" => $company_id,
    "store_id" => $store_id,
    "delivery_id" => $delivery_id,
    "is_cash" => $is_cash,
    "goods" => $stock_items,
    "comment" => "1"
);


try {
    
    if(!(isset($connection_options["login"]) && !empty($connection_options["login"]))) {
        throw new Exception("Отсутствует login для подключения к Uniqom.");
    }
    if(!(isset($connection_options["password"]) && !empty($connection_options["password"]))) {
        throw new Exception("Отсутствует password для подключения к Uniqom.");
    }
    
    /*****Учетные данные*****/
    $login = $connection_options["login"];
    $password = $connection_options["password"];
    /*****Учетные данные*****/
    
    $url_params = http_build_query(array(
    'login' => $login, 
	'password' => $password ));
	
	
    echo "<pre>";
    echo "Параметры";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($order_params);
    echo "</pre>";
    echo "</br>";
	
	$fields_str = json_encode($order_params);
    
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://uniqom.ru/api/v2.1/sendorder.json?{$url_params}", 
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS => $fields_str,
	  CURLOPT_HTTPHEADER => array(
		'Content-Type: application/json'
	  ),
	));

	$response = curl_exec($curl);
	
	echo "<pre>";
    echo "Ответ";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r(curl_getinfo($curl));
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r(curl_error($curl));
    echo "</pre>";
    echo "</br>";

	curl_close($curl);
	
	$result = json_decode($response, true);
	
	if(!empty($result['status']) && $result['status'] == "OK") {
	    
    	$order = isset($result['ordersData'][0]) ? $result['ordersData'][0] : null;

        if(!empty($order)) {
            
        	$sao_obj = json_encode($order);
        	$order_id = $order['order_id'];
        	
        	//Меняем SAO-Состояние
        	$new_sao_state = 2; //Состояние "Заказано"
        	
    	    //Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
        	$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
        	$new_status_query->bindValue(':id', $new_sao_state);
        	$new_status_query->execute();
        	$new_status_record = $new_status_query->fetch();
        	$new_status = $new_status_record["status_id"];
        	if((int)$new_status > 0)
        	{
        		//Отправляем запрос на изменение статуса позиции
        		$ch = curl_init();
        		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
        		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        		curl_setopt($ch, CURLOPT_HEADER, 0);
        		$curl_result = curl_exec($ch);
        		curl_close($ch);
        	}
    
        	$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа: {$order_id}";
        	//Обновляем SAO-статус
        	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object, `sao_robot` = :sao_robot WHERE `id` = :id;');
        	$update_query->bindValue(':sao_state', $new_sao_state);
        	$update_query->bindValue(':sao_message', $sao_message);
        	$update_query->bindValue(':sao_robot', 2);
        	$update_query->bindValue(':sao_state_object', $sao_obj);
        	$update_query->bindValue(':id', $order_item["id"]);
        	
        	
        	if( ! $update_query->execute() )
        	{
        		throw new Exception("Заказ создан, но при обновлении SAO-состояния возникли ошибки, обратитесь к инженеру поддрежки.");
        	}
        	else
        	{
        	    $sao_result["status"] = true;
        	}
        }
        else
        {
            $error_message = "Данные от поставщика получены. Ошибка получения ID заказа. Обратитесь к специалисту для уточнения причины. ";
	        throw new Exception($error_message);
        }
	}
    else
	{
	    $error_message = !empty($result['error']['message']) ? $result['error']['message'] : "Ошибка. Обратитесь к специалисту для уточнения причины. ";
	    throw new Exception($error_message);
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