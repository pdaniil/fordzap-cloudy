<?php

    ini_set('precision', 10);
    ini_set('serialize_precision', 10);

    ob_start();
    
    $pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/atrast/errors_DO_ORDER.log";

	$sao_result = array();
	$sao_params = json_decode($order_item["t2_json_params"] ,true );
	
	$curl = curl_init();
	
	$item_array = [];
	
	$item_array[] = [
		"product_id" => $sao_params["product_id"],
		"product_code" => $sao_params["product_code"],
		"count" => $order_item["count_need"],
		"supplier_id" => $sao_params["supplier_id"],
		"contract_guid" => $connection_options["contract_guid"],
		"delivery_address_guid" => $connection_options["address_guid"],
		"comment" => "Заказ API",
		"price" => $sao_params["product_price"]
		];
	
	$fields = array(
        "items" => $item_array
	);

	$fields_str = json_encode($fields);
	
	echo "<pre>";
    echo date('Y-m-d H:i:s');
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($fields);
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
	
    //Запрос к API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://ws.a-trast.ru/v1/order/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $connection_options["api_key"]
        
        ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_str);

	$response = curl_exec($ch);
	
	echo "<pre>";
    echo "Ответ";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r(curl_getinfo($ch));
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r(curl_error($ch));
    echo "</pre>";
    echo "</br>";
	
	curl_close($ch);
	
	$result = json_decode($response , true);
	
	echo "<pre>";
    echo "Ответ обработанный";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    echo "</br>";
    
    if(!empty($result['errors'])) {
        throw new Exception("Ошибка. Ответ поставщика содержит ошибки. Для уточнения ошибки обратитесь к специалисту.");
    }

	if(!empty($result[0]['orders'][0])) {
	    
	    $sao_order = $result[0]['orders'][0];
	    
    	$sao_obj = json_encode($sao_order);
    	
    	$sao_order_id = $sao_order['id'];
    	
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
    
    	$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа: {$sao_order_id}";
    	//Обновляем SAO-статус
    	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object, `sao_robot` = :sao_robot WHERE `id` = :id;');
    	$update_query->bindValue(':sao_state', $new_sao_state);
    	$update_query->bindValue(':sao_message', $sao_message);
    	$update_query->bindValue(':sao_robot', 2);
    	$update_query->bindValue(':sao_state_object', $sao_obj);
    	$update_query->bindValue(':id', $order_item["id"]);
    	$update_query->execute();
    	
    	$sao_result["status"] = true;
	}
	else
	{
	    $sao_result["status"] = false;
	    $sao_result["message"] = "Ошибка. Обратитесь к специалисту для уточнения причины. ";
	    
	    if(!empty($result["message"])) $sao_result["message"] .= $result["message"] . " ";
	    
	    //Отправляем запрос на изменение статуса позиции
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=6&key=".urlencode($DP_Config->tech_key) );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$curl_result = curl_exec($ch);
		curl_close($ch);
	}
	
		//Лог
    $dump = ob_get_contents();
    file_put_contents($pathError, $dump);
    ob_end_clean();
?>