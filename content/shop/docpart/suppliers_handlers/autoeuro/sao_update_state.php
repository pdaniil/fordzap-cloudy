<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/**
*/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/autoeuro_v2/update_state.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 

**********************************************************/


$sao_state_object = json_decode($order_item["sao_state_object"], true);
$order_id = $sao_state_object['orderId'];


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api2.autoeuro.ru/api/v2/json/get_statuses',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_HTTPHEADER => array(
    'key: ' . $connection_options["api_key"],
    'Content-Type: application/x-www-form-urlencoded'
  ),
));

$response = curl_exec($curl);


curl_close($curl);
$query_result = json_decode($response);
$arr_statuses = $query_result->DATA;

$order = [];
$order[] = $order_id;

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api2.autoeuro.ru/api/v2/json/get_orders',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => 'orders=' . json_encode($order),
  CURLOPT_HTTPHEADER => array(
    'key: ' . $connection_options["api_key"],
    'Content-Type: application/x-www-form-urlencoded'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
$query_result = json_decode($response);
$item = $query_result->DATA[0];
$status_id = $item->status_id;

$message = "";

echo "<pre>";
echo date('Y-m-d H:i:s');
echo "</pre>";
echo "</br>";
echo "<pre>";
echo "Результат Заказ";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($item);
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($status_id);
echo "</pre>";
echo "</br>";


echo "<pre>";
echo date('Y-m-d H:i:s');
echo "</pre>";
echo "</br>";
echo "<pre>";
echo "Результат Статусы";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($arr_statuses);
echo "</pre>";
echo "</br>";

foreach ($arr_statuses as $status)
{
	if ($status->status_id == $status_id)
		$message  = $status->name;
}

try {
	if($query_result->DATA)
	{
		$current_date = date( "d-m-Y H:i:s" );
		$sao_message = "Номер заказа: <br />" . $item->order_number . "<br />Статус: " . $message . "<br />Обновлено: " . $current_date;
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
file_put_contents($pathError, $dump);
ob_end_clean();
?>