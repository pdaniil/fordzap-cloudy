<?php 

    ob_start();
    
    $pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/uniqom/errors_UPD_STATE.log";
	
	$sao_params = json_decode($order_item["sao_state_object"] ,true );
	$sao_order_id = isset($sao_params['order_id']) ? $sao_params['order_id'] : null;
	
	$item_status_default = $order_item['status'];
	
	if(!empty($sao_order_id)) {
	    
	   if(!(isset($connection_options["login"]) && !empty($connection_options["login"]))) {
        throw new Exception("Отсутствует login для подключения к Uniqom.");
        }
        if(!(isset($connection_options["password"]) && !empty($connection_options["password"]))) {
            throw new Exception("Отсутствует password для подключения к Uniqom.");
        }
        
        /*****Учетные данные*****/
        $login = $connection_options["login"];
        $password = $connection_options["password"];
        /*****Учетные данные*****/
	    
       $url_params = http_build_query(array(
            'login' => $login, 
			'password' => $password ));
			
			
    	$curl = curl_init();
    
    	curl_setopt_array($curl, array(
    	  CURLOPT_URL => "http://uniqom.ru/api/v2.1/order.json/{$sao_order_id}?{$url_params}",
    	  CURLOPT_RETURNTRANSFER => true,
    	  CURLOPT_FOLLOWLOCATION => true,
    	  CURLOPT_CUSTOMREQUEST => 'GET',
    	));
    
    	$response = curl_exec($curl);
    
    	curl_close($curl);
    	
    	$result = json_decode($response, true);
	
    	echo "<pre>";
        echo "Ответ";
        echo "</pre>";
        echo "</br>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        echo "</br>";
        echo "<pre>";
        print_r(curl_getinfo($curl));
        echo "</pre>";
        echo "</br>";
        echo "<pre>";
        print_r(curl_error($curl));
        echo "</pre>";
        echo "</br>";
        
        curl_close($curl);
	

        if(!empty($result['order'])) {
            
            $sao_order = $result['order'];
                
            $sao_order_status = $sao_order['status'];
            $sao_order_status_code = $sao_order['status_id'];
            
            $sao_message = 'Обновлено состояние: '.date("d.m.Y H:i:s", time());
        	$sao_message .= '<br />Статус: ' . $sao_order_status;
        	$sao_message .= '<br />Код статуса: ' . $sao_order_status_code;
	
    		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
            $update_query->bindValue(':sao_message', $sao_message);
    		$update_query->bindValue(':id', $order_item["id"]);
    
            if( !$update_query->execute() ) {
                
                $message = "Статус получен, но произошла ошибка обновления записи";
                throw new Exception( $message );
            }
    		
    		$sao_result["status"] = true;
                
    	}
    	else
    	{
    	    $sao_result["status"] = false;
    	    $sao_result["message"] = "Ошибка. Получен некорректный ответ от поставщика. ";
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