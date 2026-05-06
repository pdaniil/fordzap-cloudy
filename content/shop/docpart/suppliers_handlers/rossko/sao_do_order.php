<?php

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

/**
SAO
Действие: Заказать

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота

ВЫВОД ДАМПА БУФФЕРИЗИРУЕТСЯ И ПИШЕТСЯ В ФАЙЛ $pathError
*/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/rossko/error_sao/errors_DO_ORDER.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();
$sao_result["status"] = false;
$sao_result_message = "";

/*********************************************************
	* Выполнение действия 
**********************************************************/

$connect = array(
    'wsdl'    => 'http://api.rossko.ru/service/v2.1/GetCheckout',
    'options' => array(
        'connection_timeout' => 1,
        'trace' => true
    )
);


/*****Учетные данные*****/
$KEY1           = isset($connection_options["key1"]) ? $connection_options["key1"] : null;
$KEY2           = isset($connection_options["key2"]) ? $connection_options["key2"] : null;
$delivery_id    = isset($connection_options["delivery_id"]) ? $connection_options["delivery_id"] : null;
$address_id     = isset($connection_options["address_id"]) ? $connection_options["address_id"] : null;
$payment_id     = isset($connection_options["payment_id"]) ? $connection_options["payment_id"] : null;
$requisite_id   = isset($connection_options["requisite_id"]) ? $connection_options["requisite_id"] : null;
$name           = isset($connection_options["delivery_name"]) ? $connection_options["delivery_name"] : '';
$phone          = isset($connection_options["delivery_phone"]) ? $connection_options["delivery_phone"] : '';
$comment        = isset($connection_options["delivery_comment"]) ? $connection_options["delivery_comment"] : '';
$delivery_parts = (isset($connection_options["delivery_parts"]) && $connection_options["delivery_parts"] == 1) ? true : false;
/*****Учетные данные*****/

try
{
	//Выполняем запрос на получение информации для создания заказа.
	
	if(empty($KEY1)) {
	    throw new Exception("Отсутствует key1 для подключения к Rossko.");
	}
	
	if(empty($KEY2)) {
	    throw new Exception("Отсутствует key2 для подключения к Rossko.");
	}
	
	if(empty($delivery_id) || empty($address_id) || empty($payment_id) || empty($requisite_id)) {
	    throw new Exception("Отсутствуют данные из ЛК Rossko.");
	}
	
	if(empty($name) || empty($phone)) {
	    throw new Exception("Отсутствуют контакнтные данные для оформления заказа.");
	}
	
	    //Создание объекта клиента
	try
	{
		$objClient = new SoapClient($connect['wsdl'], $connect['options']);//Создаем SOAP-клиент
	}
	catch (SoapFault $e)//Не можем создать клиент SOAP
	{
		throw new Exception("Ошибка Soap: " . $e->getMessage());
	}
	
    
    $items_to_order = array();
    //==========================================================
    // Определяем групповой заказ или одиночный
    
    if(isset($multi_order) && $multi_order === true) {
        
        foreach($order_items as $order_item) {
            
            $json_params = json_decode($order_item["t2_json_params"], true);
    	    
    	    $order_item_id = $order_item["id"];
        	$partnumber = $order_item["t2_article"];
        	$brand      = $order_item["t2_manufacturer"];
        	$stock      = $json_params["id"];
        	$count      = $order_item["count_need"];
            // $count = null;
        	
        	
        	if(empty($partnumber) || empty($brand) || empty($stock) || empty($count)) {
        	    
        	    $sao_result_message .= " Отсутствуют данные, позиция id$order_item_id не будет отправлена поставщику. \n";
        	    
        	} else {
        	    
        	    $items_to_order[] = array(
                    'partnumber' => $partnumber,
                    'brand'      => $brand,
                    'stock'      => $stock,
                    'count'      => $count,
                    'comment'    => $order_item_id
                );
        	}
            
        }
        
    } else {
        
        $json_params = json_decode($order_item["t2_json_params"], true);
    	
    	$order_item_id = $order_item["id"];
    	$partnumber = $order_item["t2_article"];
    	$brand      = $order_item["t2_manufacturer"];
    	$stock      = $json_params["id"];
    	$count      = $order_item["count_need"];
    	
    	
    	if(empty($partnumber) || empty($brand) || empty($stock) || empty($count)) {
    	    throw new Exception("Отсутствуют данные позиции при оформлении заказа.");
    	}
        
        $items_to_order[] = array(
                'partnumber' => $partnumber,
                'brand'      => $brand,
                'stock'      => $stock,
                'count'      => $count,
                'comment'    => $order_item_id
            );
        
    }
    
	//==========================================================
	
    $param = array(
        'KEY1' => $KEY1,
        'KEY2' => $KEY2,
        'delivery' => array(
            'delivery_id' => $delivery_id,
            'address_id'  => $address_id
        ),
        'payment' => array(
            'payment_id'        => $payment_id,
            'requisite_id'      => $requisite_id
        ),
        'contact' => array(
            'name'    => $name,
            'phone'   => $phone,
            'comment' => $comment
        ),
        'delivery_parts' => $delivery_parts,
        'PARTS' => $items_to_order
    );
    
    
    echo "<pre>";
    echo date('Y-m-d H:i:s');
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    echo "Параметры";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($param);
    echo "</pre>";
    
    
    //Обрабатываем результат
    $soap_result = $objClient->GetCheckout($param);
    // $soap_result = (object)array(
    //     "CheckoutResult" => (object)array(
    //         "success" => 1,
    //         "OrderIDS" => (object)array("id" => 50836776),
            
    //     )  
    // );

    echo "</br>";
    echo "<pre>";
    echo "Результат";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($soap_result);
    echo "</pre>";
    
    $sao_result["status"] = true;
    $sao_result["message"] = $sao_result_message;


    if(isset($soap_result->CheckoutResult)) {
        $query_result = $soap_result->CheckoutResult;
        
        /*Формат ответа
                [CheckoutResult] => stdClass Object
        (
            [success] => 1
            [OrderIDS] => stdClass Object
                (
                    [id] => 50836776
                )

            [DeliveryCost] => stdClass Object
                (
                    [cost] => 0
                )

            [comment] => 
            [ItemsList] => stdClass Object
                (
                    [Item] => Array
                        (
                            [0] => stdClass Object
                                (
                                    [partnumber] => WW0385
                                    [brand] => LEX
                                    [count] => 1
                                    [price] => 13.24
                                    [total_price] => 13.24
                                    [stock] => HST151562043
                                    [delivery] => 11
                                    [deliveryStart] => 2022-06-28T09:01:00
                                    [deliveryEnd] => 2022-06-28T10:30:00
                                    [comment] => 545
                                    [order_id] => 50836776
                                    [extra] => 0
                                    [description] => Партнерский склад
                                    [stock_address] => Пермь, Усольская ул, дом № 15Е
                                )

                            [1] => stdClass Object
                                (
                                    [partnumber] => FS205
                                    [brand] => Olymp
                                    [count] => 1
                                    [price] => 150.41
                                    [total_price] => 150.41
                                    [stock] => HST73508228
                                    [delivery] => 13
                                    [deliveryStart] => 2022-06-30T09:01:00
                                    [deliveryEnd] => 2022-06-30T10:30:00
                                    [comment] => 546
                                    [order_id] => 50836776
                                    [extra] => 0
                                    [description] => Партнерский склад
                                    [stock_address] => Пермь, Усольская ул, дом № 15Е
                                )

                        )

                )

        ) */
        
    	if($query_result->success)
    	{
    	    
    		if( isset($query_result->ItemsErrorList) && !empty($query_result->ItemsErrorList))
    		{
    		    if(isset($query_result->ItemsErrorList)) {
    		        $itemsError = $query_result->ItemsErrorList->ItemError;
    		        $error_message = "";
    		        
    		        if(is_array($itemsError)) {
    		            
    		            foreach($itemsError as $itemError) {

    		                $error_message .= $itemError->message;
    		                $error_message .= " \n";
    		            }
    		            
    		        } else {
    		            
    		            $error_message = $itemsError->message;
    		        }
    		    }

    		    $sao_result_message .= $error_message;
    		}
    		else //Если деталь заказана, то и заказ оформлен
    		{
    			//Заказ создан, получен его id в росско.
            	$orderRosskoId = isset($query_result->OrderIDS->id) ? $query_result->OrderIDS->id : null;
            	$sao_obj["orderId"] = $orderRosskoId;
            	$sao_obj = json_encode($sao_obj);
            	
            	//Меняем SAO-Состояние
            	$new_sao_state = 2; //Состояние "Заказано"
            	
            	//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
            	$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
            	$new_status_query->bindValue(':id', $new_sao_state);
            	$new_status_query->execute();
            	$new_status_record = $new_status_query->fetch();
            	$new_status = $new_status_record["status_id"];
            	
            	$orderItemsSuccess = array();
            	
            	// Делаем для всех заказанных позиций
            	if(isset($query_result->ItemsList)) {
            	    $itemsSuccess = $query_result->ItemsList->Item;
            	    
            	    if(is_array($itemsSuccess)) {
            	        
            	        foreach($itemsSuccess as $itemSuccess) {
            	            $orderItemsSuccess[] = $itemSuccess;
            	        }
            	        
            	    } else {
            	        
            	        $orderItemsSuccess[] = $itemsSuccess;
            	        
            	    }
            	}
            	
            	echo "</br>";
                echo "<pre>";
                echo "Все заказанные позиции";
                echo "</pre>";
                echo "</br>";
                echo "<pre>";
                print_r($orderItemsSuccess);
                echo "</pre>";
            	
            	if(!empty($orderItemsSuccess)) {
            	    foreach($orderItemsSuccess as $orderItemSuccess) {
            	        
            	        $order_sao_id = (int)$orderItemSuccess->comment;
            	        
            	        echo "</br>";
                        echo "<pre>";
                        echo "Позиция";
                        echo "</pre>";
                        echo "</br>";
                        echo "<pre>";
                        print_r($orderItemSuccess);
                        echo "</pre>";
            	        
            	       if((int)$new_status > 0)
                    	{
                    		//Отправляем запрос на изменение статуса позиции
                    		$ch = curl_init();
                    		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_sao_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
                    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    		curl_setopt($ch, CURLOPT_HEADER, 0);
                    		$curl_result = curl_exec($ch);
                    		curl_close($ch);
                    	}
                    
                    	$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа в Росско:<br/>{$orderRosskoId}";
                    	//Обновляем SAO-статус
                    	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object, `sao_robot` = :sao_robot WHERE `id` = :id;');
                    	$update_query->bindValue(':sao_state', $new_sao_state);
                    	$update_query->bindValue(':sao_message', $sao_message);
                    	$update_query->bindValue(':sao_state_object', $sao_obj);
                    	$update_query->bindValue(':sao_robot', 2);
                    	$update_query->bindValue(':id', $order_sao_id);
                    	
                    	
                    	if( ! $update_query->execute() )
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
            	}
            	
            	$sao_result["status"] = true;
                $sao_result["message"] = $sao_result_message;

    		}
    		
    		
    	} // ~if($result->success)
    	else
    	{
    	    $error_message = isset($query_result->message) ? $query_result->message : "Ошибка при оформлении заказа";
    		throw new Exception($error_message);
    	}
    	
    	

    } else {
        throw new Exception("Ошибка получения данных после запроса GetCheckout. Проверьте корзину в личном кабинете Росско.");
    }
	
}
catch(Exception $e)
{
	echo date("d-m-Y H:i:s")."\n";
	echo "Ошибка: \n";
	echo $e->getMessage()."\n";
	echo "============================================================\n";
	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
}


echo "============================================================\n";
echo "============================================================\n";

$dump = ob_get_contents();
file_put_contents($pathError, $dump, FILE_APPEND);
ob_end_clean();
?>