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

//0. Структура результата
$sao_result = array();
$sao_result["status"] = false;
$sao_result_message = "";


ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/berg/errors_DO_ORDER.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 

**********************************************************/

//$connection_options["is_test"] = 1;



$dispatch_at = $connection_options["dispatch_at"];

$items = array();

if(isset($multi_order) && $multi_order === true) {
    
    foreach($order_items as $order_item) {
        
        $json = json_decode($order_item["t2_json_params"], true);
	    
	    $order_item_id = $order_item["id"];

        $items[] = array(
    	
        	"resource_id" => $json['resource_id'],
        	"warehouse_id" => $json['warehouse_id'],
        	"quantity" => $order_item["count_need"],
        	"max_price" => $order_item["price"],
        	"comment" => $order_item_id
        );
        
    }
} else {
    
    $json = json_decode($order_item["t2_json_params"], true);
    
    $order_item_id = $order_item["id"];
    
    $items[] = array(
	
    	"resource_id" => $json['resource_id'],
    	"warehouse_id" => $json['warehouse_id'],
    	"quantity" => $order_item["count_need"],
    	"max_price" => $order_item["price"],
    	"comment" => $order_item_id
    );
}

$dispatch_at = date("Y-m-j",  time() + 86400 * $dispatch_at); 

$order = array(
    "order" => array(
    	"is_test" => $connection_options["is_test"],
    	"payment_type" => $connection_options["payment_type"],
    	"dispatch_type" => $connection_options["dispatch_type"],
    	"dispatch_at" => $dispatch_at,
    	"dispatch_time" => $connection_options["dispatch_time"],
    	"shipment_address_id" => $connection_options["shipment_address_id"],
    	"comment" => $connection_options["comment"],
    	"phone" => $connection_options["phone"],
    	"person" => $connection_options["person"],
    	"items" => $items
        )
    );

echo "<pre>";
echo date('Y-m-d H:i:s');
echo "</pre>";
echo "</br>";
echo "<pre>";
echo "Параметры заказа";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($order);
echo "</pre>";
echo "</br>";
echo "<pre>";


$fields_str = json_encode($order);

echo "<pre>";
echo "Обработанные параметры для запроса";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($fields_str);
echo "</pre>";
echo "</br>";
echo "<pre>";

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.berg.ru/v1.0/ordering/place_order.json?key=' . $connection_options["key"],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => 1,
  CURLOPT_POSTFIELDS => $fields_str,
  CURLOPT_HEADER => 0,
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
   )
));

$response = curl_exec($curl);

echo "<pre>";
echo "Ответ запроса";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($response);
echo "</pre>";
echo "</br>";
echo "<pre>";

curl_close($curl);

$query_result = json_decode($response, true);

echo "<pre>";
echo "Ответ запроса после обработки";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($query_result);
echo "</pre>";
echo "</br>";
echo "<pre>";

try {
	if(!empty($query_result['order']))
	{
	    $query_order = $query_result['order'];
		$sao_obj["orderId"] = $query_order['id'];
		$sao_obj = json_encode($sao_obj);
		
		if(!empty($query_order['items'])) {
		    
		    $new_sao_state = 2; //Состояние "Заказано"
		
    		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
    		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
    		$new_status_query->bindValue(':id', $new_sao_state);
    		$new_status_query->execute();
    		$new_status_record = $new_status_query->fetch();
    		$new_status = $new_status_record["status_id"];
    		
    		echo "<pre>";
            echo "Позиции оформленного заказа";
            echo "</pre>";
            echo "</br>";
            echo "<pre>";
            print_r($$query_order['items']);
            echo "</pre>";
            echo "</br>";
            echo "<pre>";
		    
		    foreach($query_order['items'] as $sao_order_item) {
		        
    	        $order_sao_id = isset($sao_order_item['comment']) ? $sao_order_item['comment'] : null;

	            if($new_status > 0)
    			{
    				//Отправляем запрос на изменение статуса позиции
    				$ch = curl_init();
    				curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_sao_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
    				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    				curl_setopt($ch, CURLOPT_HEADER, 0);
    				$curl_result = curl_exec($ch);
    				curl_close($ch);
    			}
    			
    			$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа:<br/>" . $query_result->order->id;
    			
    			//4.1. Состояние позиции, //4.1. Запись технических параметоров SAO, // 4.3. Отображаемые комментарий
    			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = 2, `sao_robot` = 0, `sao_state_object`= :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
    			$update_query->bindValue(':sao_state_object', $sao_obj);
    			$update_query->bindValue(':sao_message', $sao_message);
    			$update_query->bindValue(':id', $order_sao_id);
    			
    			if( !$update_query->execute() )
                {
            		echo date("d-m-Y H:i:s")."\n";
            		echo "Ошибка обновления SAO-статуса\n";
            		echo "=======================================================\n";
            		
            		$sao_result_message .= " Заказ для позиции id-$order_sao_id создан, но при обновлении SAO-состояния возникли ошибки, обратитесь к инженеру поддержки.";
            	}
            	else
            	{
                    $sao_result_message .= " Заказ для позиции id-$order_sao_id создан.";
            	}

		    }
		    
		    $sao_result["status"] = true;
		    $sao_result["message"] = $sao_result_message;
		}
		
	} // ~if($result->success)
	else
	{
		$error_message = isset($query_result->ERROR[0]->error) ? $query_result->ERROR[0]->error : "Ошибка при оформлении заказа";
		throw new Exception($error_message);
	}
}
catch (Exception $e)
{
    $error_message = $e->getMessage();
    $sao_result['message'] = $error_message;
}
	


$dump = ob_get_contents();
file_put_contents($pathError, $dump);
ob_end_clean();

?>