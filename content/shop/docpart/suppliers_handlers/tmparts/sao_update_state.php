<?php 

    ob_start();
    
    $pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/tmparts/errors_UPD_STATE.log";
	
	//Проверка состояния
	$curl = curl_init();
	$order_id = json_decode($order_item["sao_state_object"], true)["orderId"];
	$fields = [
	"JSONparameter" => "{ 'ID_List' : [ { 'ID' : '".$order_id."' } ] }"
	];
	curl_setopt($curl, CURLOPT_URL, "api.tmparts.ru/api/OrderStates?".http_build_query($fields));
	curl_setopt_array($curl, array(
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	  CURLOPT_HTTPHEADER => array(
		'Content-Type: application/json',
		'Authorization: Bearer ' . $connection_options["authorization"]
	  ),
	));
	
	$response = curl_exec($curl);
	
	
    echo "<pre>";
    echo date('Y-m-d H:i:s');
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

    curl_close($ch);
	
	
	$sao_message = "";
	$result = json_decode($response , true);
	
	curl_close($curl);
	$sao_message = "Обновлено ". date("d-m-Y H:i:s") ."<br/>Номер заказа в системе:<br/>{$order_id}<br />Состояние: {$result[0]["State"]}";
	//Обновляем SAO-статус
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message, `sao_robot` = :sao_robot WHERE `id` = :id;');
	$update_query->bindValue(':sao_message', $sao_message);
	$update_query->bindValue(':sao_robot', 2);
	$update_query->bindValue(':id', $order_item["id"]);
	$update_query->execute();
	
	$sao_result["status"] = true;
	
	//Лог
    $dump = ob_get_contents();
    file_put_contents($pathError, $dump);
    ob_end_clean();
?>