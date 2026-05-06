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
$sao_result_message = '';

/*********************************************************
	* Выполнение действия 

**********************************************************/

$orderIds = array();

if(isset($multi_order) && $multi_order === true) {
    
    foreach($order_items as $order_item) {
        
        $state_object = json_decode($order_item["sao_state_object"], true);
        $orderId = isset($state_object["orderId"]) ? $state_object["orderId"] : null;
        if( in_array($orderId, $orderIds) == false) {
            $orderIds[] = $orderId;
        }
    }
    
} else {
    
    $state_object = json_decode($order_item["sao_state_object"], true);
    $orderId = isset($state_object["orderId"]) ? $state_object["orderId"] : null;
    $orderIds[] = $orderId;
    
}

echo "<pre>";
echo date('Y-m-d H:i:s');
echo "</pre>";
echo "</br>";
echo "<pre>";
echo "Список заказов";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($orderIds);
echo "</pre>";
echo "</br>";
echo "<pre>";

$param_order = http_build_query(array("orders" => $orderIds));

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.berg.ru/ordering/states.json?key=' . $connection_options["key"] . '&' . $param_order,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);
$query_result = json_decode($response, true);

echo "<pre>";
echo "Ответ после обработки";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($query_result);
echo "</pre>";
echo "</br>";
echo "<pre>";

foreach ($query_result['orders'] as $order)
{
    
    try {
    	if(!empty($order['items']))
    	{
    	    foreach ($order['items'] as $item)
        	{
        	    $order_item_id = (!empty($item['comment'])) ? $item['comment'] : $order_item['id'];
        	    $current_date = date( "d-m-Y H:i:s" );
        		$message = "ID-" . $order_item_id . " Статус позиции: " . $item['state']['name'];
        		
        		$sao_message = $message . "<br />Обновлено: " . $current_date;
        		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
        		$update_query->bindValue(':sao_message', $sao_message);
        		$update_query->bindValue(':id', $order_item_id);
        
        		if( !$update_query->execute() )
        		{	
        			echo date("d-m-Y H:i:s")."\n";
        			echo "Ошибка  mysql: \n";
        			echo $UPD_STATUS."\n";
        			echo "============================================================\n";
        			
        			$sao_result_message .= "Не удалось обновить состояние позиции {$order_item_id}. ";
        		}
        		else
        		{
        			$sao_result_message .= "Получено актуальное состояние для позиции {$order_item_id}. ";
        		}
        	}
        	
        	$sao_result["status"] = true;
        	$sao_result["message"] = $sao_result_message;
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
}



	


$dump = ob_get_contents();
file_put_contents($pathError, $dump);
ob_end_clean();
?>