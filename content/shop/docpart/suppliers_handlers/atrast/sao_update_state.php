<?php 

    ob_start();
    
    $pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/atrast/errors_UPD_STATE.log";
	
	$sao_params = json_decode($order_item["sao_state_object"] ,true );
	$sao_order_id = isset($sao_params['id']) ? $sao_params['id'] : null;
	
	echo "<pre>";
    echo "Параметры";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($sao_params);
    echo "</pre>";
    echo "</br>";
	
	if(!empty($sao_order_id)) {
	    
	    if(!empty($sao_params['items'][0]['id'])) {
	        
	        $sao_order_item_id = $sao_params['items'][0]['id'];
	        
        	$fields = array(
                "item_ids" => array($sao_order_item_id)
        	);
        
        	$fields_str = json_encode($fields);
	        
	        //Запрос к API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://ws.a-trast.ru/v1/order/items-info");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
             curl_setopt($ch, CURLOPT_HEADER, 0);
             curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $connection_options["api_key"]
            ));
            
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_str);
            
            $response = curl_exec($ch);
    	
        	echo "<pre>";
            echo "Ответ";
            echo "</pre>";
            echo "</br>";
            echo "<pre>";
            print_r($response);
            echo "</pre>";
            echo "</br>";
            echo "<pre>";
            print_r(curl_getinfo($ch));
            echo "</pre>";
            echo "</br>";
            echo "<pre>";
            print_r(curl_error($ch));
            echo "</pre>";
            echo "</br>";
            
            curl_close($ch);
    	
        	$result = json_decode($response , true);
        	
        	echo "<pre>";
            echo "Ответ обработанный";
            echo "</pre>";
            echo "</br>";
            echo "<pre>";
            print_r($result);
            echo "</pre>";
            echo "</br>";
            
            if(!empty($result['items'])) {
            
                $sao_item = $result['items'][0];
                
                $sao_item_status = $sao_item['status']['title'];
                
                $date = date("d-m-Y H:i:s");
		
        		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
                $update_query->bindValue(':sao_message', 'Статус: '.$sao_item_status.'<br/>'.$date);
        		$update_query->bindValue(':id', $order_item["id"]);
        
                if( !$update_query->execute() ) {
                    $message = "Статус получен, но произошла ошибка обновления записи";
                    throw new Exception( $message );
                }
        		
        		$sao_result["status"] = true;
                    
            } else {
                
                $sao_result["status"] = false;
    	        $sao_result["message"] = "Ошибка. Не найдены позиции в заказе. ";
            }

        } else  {
            $sao_result["status"] = false;
            $sao_result["message"] = "Ошибка. Отсутствует ID позиции заказа. ";
        }
        
    } else {
        
        $sao_result["status"] = false;
        $sao_result["message"] = "Ошибка. Отсутствует ID заказа. ";
    }
	

	//Лог
    $dump = ob_get_contents();
    file_put_contents($pathError, $dump);
    ob_end_clean();
?>