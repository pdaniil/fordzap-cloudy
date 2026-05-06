<?php 

    ob_start();
    
    $pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/v_avto/errors_UPD_STATE.log";
	
	$sao_params = json_decode($order_item["sao_state_object"] ,true );
	$sao_order_id = isset($sao_params['uid']) ? $sao_params['uid'] : null;
	
	if(!empty($sao_order_id)) {
	    
	    //Запрос к API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.v-avto.ru/v1/orders/{$sao_order_id}.json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Voshod-API-KEY: ' . $connection_options["api_key"]));
        
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
        
        if(empty($result['errors'])) {
            
            $sao_order = $result['response']['order'];
            
            $sao_item = $sao_order['items'][0];
            
            if(!empty($sao_item)) {
                
                $sao_item_status = $sao_item['state_name'];
                
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
             
    	}
    	else
    	{
    	    $sao_result["status"] = false;
    	    $sao_result["message"] = "Ошибка. Обратитесь к специалисту для уточнения причины. ";
    	    
    	    //Отправляем запрос на изменение статуса позиции
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=6&key=".urlencode($DP_Config->tech_key) );
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    		curl_setopt($ch, CURLOPT_HEADER, 0);
    		$curl_result = curl_exec($ch);
    		curl_close($ch);
    	}

        
	} else {
	    
	    $sao_result["status"] = false;
	    $sao_result["message"] = "Ошибка. Отсутствует ID заказ. ";
	}
	

	//Лог
    $dump = ob_get_contents();
    file_put_contents($pathError, $dump);
    ob_end_clean();
?>