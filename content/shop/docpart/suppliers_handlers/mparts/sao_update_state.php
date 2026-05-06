<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/**
*/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/mparts/update_state.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 

**********************************************************/


$order_id = json_decode($order_item["sao_state_object"])->orderId;

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://v01.ru/api/devinsight/orders/list?userlogin='.$connection_options["login"].'&userpsw='.md5($connection_options["password"]).'&orders[0]=' . $order_id,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);

$query_result = json_decode($response);


echo "<pre>";
echo date('Y-m-d H:i:s');
echo "</pre>";
echo "</br>";
echo "<pre>";
echo "Результат";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($query_result);
echo "</pre>";
echo "</br>";

$status = $query_result->{$order_id}->status;

$message = $status;



try {
	
		$current_date = date( "d-m-Y H:i:s" );
		$sao_message = "Номер заказа: " . $order_id . "<br />Статус: " . $message . "<br />Обновлено: " . $current_date;
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_message', $sao_message);
		$update_query->bindValue(':id', $order_item["id"]);

		
		if( !$update_query->execute() )
		{	
			echo date("d-m-Y H:i:s")."\n";
			echo "============================================================\n";
			
			$sao_result["status"] = false;
			$sao_result["message"] = "Не удалось обновить состояние, обратитесь к инженеру Docpart";
		}
		else
		{
			$sao_result["status"] = true;
		}
		
	} // ~if($result->success)
	

catch (Exception $e)
{
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
}
	


$dump = ob_get_contents();
file_put_contents($pathError, $dump);
ob_end_clean();
?>