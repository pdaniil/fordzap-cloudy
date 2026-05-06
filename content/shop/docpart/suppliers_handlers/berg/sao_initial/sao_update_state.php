<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/**
*/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/berg/UPDATE_STATE.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 

**********************************************************/

$json = json_decode($order_item["sao_state_object"], true);

$order_id = $json['orderId'];

echo "<pre>";
echo date('Y-m-d H:i:s');
echo "</pre>";
echo "</br>";
echo "<pre>";
echo "Заказа";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($order_id);
echo "</pre>";
echo "</br>";
echo "<pre>";

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.berg.ru/ordering/states.json?orders[]=' . $order_id . '&key=' . $connection_options["key"],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);
$query_result = json_decode($response);

echo "<pre>";
echo "Ответ после обработки";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($query_result);
echo "</pre>";
echo "</br>";
echo "<pre>";

$message = "";

foreach ($query_result->orders as $order)
{
	foreach ($order->items as $item)
	{
		$message = $item->state->name;
	}
}


try {
	if($message != '')
	{
		$current_date = date( "d-m-Y H:i:s" );
		$sao_message = "Номер заказа: " . $order_id . "<br />Статус: " . $message . "<br />Обновлено: " . $current_date;
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_message', $sao_message);
		$update_query->bindValue(':id', $order_item["id"]);

		
		if( !$update_query->execute() )
		{	
			echo date("d-m-Y H:i:s")."\n";
			echo "Ошибка  mysql: \n";
			echo mysqli_error($db_link)."\n";
			echo $UPD_STATUS."\n";
			echo "============================================================\n";
			
			$sao_result["status"] = false;
			$sao_result["message"] = "Не удалось обновить состояние, обратитесь к инженеру Docpart";
		}
		else
		{
			$sao_result["status"] = true;
		}
		
	} // ~if($result->success)
	else
	{
		$error_message = isset($query_result->ERROR[0]->error) ? $query_result->ERROR[0]->error : "Ошибка при обновлении состояния";
		throw new Exception($error_message);
	}
}
catch (Exception $e)
{
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
}
	


$dump = ob_get_contents();
file_put_contents($pathError, $dump, FILE_APPEND);
ob_end_clean();
?>