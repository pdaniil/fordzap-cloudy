<?php
	$order_id = json_decode($order_item["sao_state_object"])->orderId;
	$url = 'https://beta.autoopt.ru/api/v2/order/';
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => $url,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_USERAGENT => '123',
	  CURLOPT_HTTPHEADER => array(
		'Authorization: Bearer ' . $connection_options['token'],
		'Content-Type: application/x-www-form-urlencoded',
	  ),
	));

	$response = curl_exec($curl);
	
	curl_close($curl);
	
	$orders = json_decode($response, true);
	
	$currentOrder = [];
	foreach ($orders as $order) 
		if ($order['id'] == $order_id)
			$currentOrder = $order;
	
	$status_string = 'Номер заказа на сайте: '. $currentOrder['site_order_id'] . '<br>';
	$status_string .= 'Заказ создан: '. $currentOrder['created_at'] . '<br>';
	$status_string .= 'Статус заказа: '. $currentOrder['site_status_name'] . '<br>';
	
	$current_date = date( "d-m-Y H:i:s" );
	$sao_message = $status_string;
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
	$update_query->bindValue(':sao_message', $sao_message);
	$update_query->bindValue(':id', $order_item["id"]);


	if( !$update_query->execute() )
	{	
		$sao_result["status"] = false;
		$sao_result["message"] = "Не удалось обновить состояние, обратитесь к инженеру Docpart";
	}
	else
	{
		$sao_result["status"] = true;
	}
?>