<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/***********************************************************************
* SAO
* Действие: Проверить состояние

* Данный скрипт выполняется в контексте:
	** либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
	** либо в контексте скрипта робота
	
*************************************************************************/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/rossko/error_sao/errors_UPD_STATE.log";

//Структура результата 
$sao_result = array();
$sao_result_message = '';

//*************************************************************************

$orderIds = array();

if(isset($multi_order) && $multi_order === true) {
    
    foreach($order_items as $order_item) {
        
        $state_object = json_decode($order_item["sao_state_object"], true);
        $orderId = isset($state_object["orderId"]) ? $state_object["orderId"] : null;
        $orderIds[] = $orderId;
    }
    
} else {
    
    $state_object = json_decode($order_item["sao_state_object"], true);
    $current_sao_state = $order_item["sao_state"];
    $orderId = isset($state_object["orderId"]) ? $state_object["orderId"] : null;
    
    $orderIds[] = $orderId;
    
}



$connect = array(
    'wsdl'    => 'http://api.rossko.ru/service/v2.1/GetOrders',
    'options' => array(
        'connection_timeout' => 1,
        'trace' => true
    )
);

/************************************
	Разобраться с SAO - статусом
*************************************/

/*****Учетные данные*****/
$KEY1    = isset($connection_options["key1"]) ? $connection_options["key1"] : null;
$KEY2    = isset($connection_options["key2"]) ? $connection_options["key2"] : null;

/*****Учетные данные*****/

$sao_info = "";
try
{
    if(empty($KEY1)) {
	    throw new Exception("Отсутствует key1 для подключения к Rossko.");
	}
	
	if(empty($KEY2)) {
	    throw new Exception("Отсутствует key2 для подключения к Rossko.");
	}
	
	if(empty($orderIds)) {
	    throw new Exception("Отсутствуют данные заказа ЛК Rossko.");
	}
	
	$param = array(
        'KEY1' => $KEY1,
        'KEY2' => $KEY2,
        'order_ids' => $orderIds
    );

    //Создание объекта клиента
	try
	{
		$objClient = new SoapClient($connect['wsdl'], $connect['options']);//Создаем SOAP-клиент
	}
	catch (SoapFault $e)//Не можем создать клиент SOAP
	{
		throw new Exception("Ошибка Soap: " . $e->getMessage());
	}
	
	
    $soap_result = $objClient->GetOrders($param);
    

	if(isset($soap_result->OrdersResult)) {
        $order = $soap_result->OrdersResult;
    
    	if($order->success)
    	{
    	    $supplier_statuses = array(
        	    0 => "ждёт подтверждения",
                1 => "комплектуется",
                2 => "отгружено",
                3 => "готово к отгрузке",
                5 => "ожидаем поступление",
                6 => "на складе филиала",
                7 => "нет в наличии",
                8 => "отменён клиентом",
                9 => "просрочен",
                31 => "ожидаем товар на складе",
                32 => "возврат на согласовании",
                33 => "товар на экспертизе",
                34 => "возврат отклонён",
                35 => "возврат частично отклонён",
                36 => "товар возвращён"
            );
            
            if(isset($order->OrdersList)) {
                
    		        $ordersInfo = $order->OrdersList->Order;
    		        
    		        $orderItemsSuccess = array();

    		        if(is_array($ordersInfo)) {
    		            
    		            foreach($ordersInfo as $orderInfo) {

    		                if(isset($orderInfo->parts)) {
    		                    
    		                    $partsInfo = $orderInfo->parts->part;
    		                    
    		                    if(is_array($partsInfo)) {
    		                        
    		                        foreach($partsInfo as $partInfo) {
    		                            
    		                            $orderItemsSuccess[] = $partInfo;
    		                            
    		                        }
    		                        
    		                    } else {
    		                        
    		                        $orderItemsSuccess[] = $partsInfo;
    		                    }
    		                    
    		                }

    		            }
    		            
    		        } else {
    		            
		                if(isset($ordersInfo->parts)) {
		                    
		                    $partsInfo = $ordersInfo->parts->part;
		                    
		                    if(is_array($partsInfo)) {
		                        
		                        foreach($partsInfo as $partInfo) {
		                            
		                            $orderItemsSuccess[] = $partInfo;
		                            
		                        }
		                        
		                    } else {
		                        
		                        $orderItemsSuccess[] = $partsInfo;
		                    }
		                    
		                }
    		        }
    		        
    		        if(!empty($orderItemsSuccess)) {
                	    foreach($orderItemsSuccess as $orderItemSuccess) {
                	        
                	        $order_sao_id = (int)$orderItemSuccess->comment;
                	        
                	        $status_sql = "SELECT `status` FROM `shop_orders_items` WHERE id = ?";		
                        	$status_query = $db_link->prepare($status_sql);
                        	$status_query->execute(array($order_sao_id));
                        	$status_arr = $status_query->fetchColumn();
                        	$status_order_id = (int) $status_arr;
                        	
    	                    ////////////////////
                            $relation_sql = "SELECT `relationship` FROM `shop_storages_statuses_relationship` WHERE storage_id = ?";		
                            $relations_query = $db_link->prepare($relation_sql);
                            $relations_query->execute(array($order_item["t2_storage_id"]));
                            $relations_arr = $relations_query->fetchColumn();
                            $relations_params = json_decode($relations_arr, true);
                            
                            $relations = array();
                            
                            foreach($relations_params as $relations_param) {
                            
                              $relation_info = array(
                                  "status_store" => $relations_param["status_store"],
                                  "description"  => $relations_param["description"]
                              );
                            
                              $caption = strip_tags(trim($relations_param["status_supplier"]));
                            
                              $relations[$caption] = $relation_info;
                            }
                            ////////////////////
                            
                	        $status_order_item = isset($orderItemSuccess->status) ? $orderItemSuccess->status : null;
                	        
                	        $status_order_item_text = isset($supplier_statuses[$status_order_item]) ? $supplier_statuses[$status_order_item] : 'Не определен';
                	        
                    		$sao_info = "Статус позиции: {$status_order_item_text}<br/>Код статуса: {$status_order_item}<br/>";
                    		
                    		////////////////////
                            $new_status = $status_order_id;
                            $relation_field = $status_order_item;
                    
                            $current_relation_field = "";
                            $caption_relation_field = trim($relation_field);
                    
                            if (!empty($relations[$caption_relation_field])) {
                    
                                $current_relation_field = $relations[$caption_relation_field];
                    
                                if(!empty($current_relation_field['status_store'])) $new_status = $current_relation_field['status_store'];
                    
                            }
                            
        	                if($new_status > 0 && $new_status != $status_order_id)
                            {
                                //Отправляем запрос на изменение статуса позиции
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_sao_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_HEADER, 0);
                                $curl_result = curl_exec($ch);
                                curl_close($ch);
                            }
                            
                    		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
                 			$update_query->bindValue(':sao_message', $sao_info);
                			$update_query->bindValue(':id', $order_sao_id);
                
                    		
                    		if( !$update_query->execute() )
                    		{	
                    			$sao_result_message .= " Не удалось обновить состояние в Базе данных позиции id-$order_sao_id, обратитесь к инженеру Docpart.";
                    		}
                    		else
                    		{
                    			$sao_result_message .= " Состояние для позиции id-$order_sao_id получено.";
                    		}
                	        
                	    }
                	    
                	    $sao_result["status"] = true;
                        $sao_result["message"] = $sao_result_message;
    		        }
    		    }

			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			/*
			$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
			$new_status_query->bindValue(':id', $docpartState);
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
				$error = curl_error($ch);
				curl_close($ch);
			}
			*/
    		
    	}
    	else
    	{
    		$error_message = isset($order->message) ? $order->message : "Ошибка при получении заказа";
		    throw new Exception($error_message);
    	}
	
	} else {
	    throw new Exception("Ошибка получения данных после запроса GetOrders. Проверьте корзину в личном кабинете Росско.");
	}

}
catch(SoapFault $e)
{
	echo date("d-m-Y H:i:s")."\n";
	echo "Ошибка SOAP: \n";
	echo $e->getMessage()."\n";
	echo "============================================================\n";
	
	$sao_result["status"] = false;
	$sao_result["message"] = "Ошибка подключения в сервису, обратитесь к инженеру Docpart";
}
catch(Exception $e)
{
	echo date("d-m-Y H:i:s")."\n";
	echo "Ошибка: \n";
	echo $e->getMessage()."\n";
	echo "============================================================\n";
	
	$sao_result["status"] = false;
	$sao_result["message"] = "Ошибка подключения в сервису, обратитесь к инженеру Docpart";
}

//Лог
$dump = ob_get_contents();
file_put_contents($pathError, $dump, FILE_APPEND);
ob_end_clean();
?>