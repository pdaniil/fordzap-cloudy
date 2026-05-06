<?php

    ini_set('precision', 10);
    ini_set('serialize_precision', 10);

    function ResultCodeToStr($code) {
		$result="";
		if (substr($code,-2,1)=='1') {$result.="Отсутствуют в наличии позиции. ";} 
		if (substr($code,-3,1)=='1') {$result.="Уменьшилось количество в позициЯх. ";} 
		if (substr($code,-4,1)=='1') {$result.="Изменился срок доставки. ";} 
		if (substr($code,-5,1)=='1') {$result.="Изменилась цена. ";} 
		return	$result;
	}

    ob_start();
    
    $pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/tmparts/errors_DO_ORDER.log";

	$sao_result = array();
	$sao_params = json_decode($order_item["t2_json_params"] ,true );
	
	$curl = curl_init();
	
	$item_array = [];
	
	$item_array[] = [
		"ID" => $sao_params["ID"],
		"Price" => $sao_params["Price"],
		"Delivery_Period" => $sao_params["Delivery_Period"],
		"Quantity" => $order_item["count_need"],
	];
	
	$fields = 
	[
		"PriceCheck" => '1',
		"DeliveryCheck" => '0',
		"QuantityCheck" => '1',
		"Mode" => '1',
		"ContractCode" => $connection_options["ContractCode"],
		"ShipCode" => $connection_options["ShipCode"],
		"Comment" => "Заказ API. ",
		"ID_List" => $item_array
	];
	
	$fields_str = json_encode(json_encode($fields));
	
	echo "<pre>";
    echo date('Y-m-d H:i:s');
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($fields_str);
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
	
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'api.tmparts.ru/api/NewOrder', 
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS => $fields_str,
	  CURLOPT_HTTPHEADER => array(
		'Content-Type: application/json',
		'Authorization: Bearer ' . $connection_options["authorization"]
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
	
	
	$result = json_decode($response , true);
	
	echo "<pre>";
    echo "Ответ обработанный";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    echo "</br>";

	//Заказ создан, получен его id 
	$order_id = !empty($result["Orders"][0]["ID"]) ? $result["Orders"][0]["ID"] : null;
	
	
	if(!empty($order_id)) {
    	$sao_obj["orderId"] = $order_id ;
    	$sao_obj = json_encode($sao_obj);
    	
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
    
    	$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа:<br/>{$order_id}";
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
	    $sao_result["message"] = !empty($result['Comment']) ? $result['Comment'] : "Ошибка. Обратитесь к специалисту для уточнения причины. ";
	    
	    $sao_result["message"] .= " " . ResultCodeToStr($result['Code']);
	    
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