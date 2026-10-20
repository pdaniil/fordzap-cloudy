<?php
ob_start();
$supplier_dir = $_SERVER['DOCUMENT_ROOT'] . '/content/shop/docpart/suppliers_handlers/abcp';

//Структура результата 
$sao_result = array();
$sao_result_message = '';

$login = $connection_options["login"];
$password = md5( $connection_options["password"] );
$subdomain = $connection_options["subdomain"];

$auth_params = http_build_query( array( 'userlogin' => $login, 
											'userpsw' => $password ));
											
$url_request = "https://{$subdomain}.public.api.abcp.ru/orders/list?{$auth_params}";

$orderIds = array();

$ordersBase = array();

if(isset($multi_order) && $multi_order === true) {
    
    foreach($order_items as $order_item) {
        
        $state_object = json_decode($order_item["sao_state_object"], true);
        $abcp_order_id = $state_object['orderId'];
        $abcp_order_position_id = $state_object['positionId'];
        
        $ordersBase[$abcp_order_id][$abcp_order_position_id] = $state_object['itemId'];
        
        if( in_array($abcp_order_id, $orderIds) == false) {
            $orderIds[] = $abcp_order_id;
        }
    }
    
} else {
    
    $state_object = json_decode($order_item["sao_state_object"], true);
    $abcp_order_id = $state_object['orderId'];
    $abcp_order_position_id = $state_object['positionId'];
        
    $ordersBase[$abcp_order_id][$abcp_order_position_id] = $state_object['itemId'];
        
    if( in_array($abcp_order_id, $orderIds) == false) {
        $orderIds[] = $abcp_order_id;
    }
}

$param_order = http_build_query(array("orders" => $orderIds));

$url_request .= "&{$param_order}";

echo "<pre>";
print_r( $url_request );
echo "</pre>";
echo "\n";

$ch = curl_init();
curl_setopt( $ch, CURLOPT_HEADER, false );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
curl_setopt( $ch, CURLOPT_URL, $url_request );

try {
	
	$exec = curl_exec($ch);

	if (curl_errno($ch)) {
		throw new Exception ( "Ошибка curl: " . curl_errno( $ch ) );
	}

	$decode = json_decode($exec, true);

	echo "<pre>";
    print_r($decode);
    echo "</pre>";
    echo "\n";

	if (is_null($decode)) {
		throw new Exception ("Ошибка json: " . json_last_error());
	}

	if (isset($decode['errorCode']) ) {
		throw new Exception ("Ошибка от поставщика: " . $decode['errorMessage']);
	}
	
	if(!empty($decode)) {
	    
	    foreach($decode as $order_id => $order_data_positions) {
	        
	        $order_positions = $order_data_positions['positions'];
	        
	        if(!empty($order_positions)) {
	            
	            foreach($order_positions as $order_position) {
	                
	                
	                echo "<pre>";
                    print_r( $order_position );
                    echo "</pre>";
                    echo "\n";
	                
	                $order_position_id = $order_position['positionId'];
	                $order_position_status = $order_position['status'];
	                $order_position_status_id = $order_position['statusId'];
	                
	                $sao_message = "Обновление ". date("d-m-Y H:i:s") ."<br/>Номер заказа: {$order_id}<br />Статус позиции: {$order_position_status}<br />Код статуса: {$order_position_status_id}";
	                
	                $itemId = isset($ordersBase[$order_id][$order_position_id]) ? $ordersBase[$order_id][$order_position_id] : null;
	                
	                if(!empty($itemId)) {
	                    
	                    $status_sql = "SELECT `status` FROM `shop_orders_items` WHERE id = ?";		
                    	$status_query = $db_link->prepare($status_sql);
                    	$status_query->execute(array($itemId));
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
                          
                          
                        ////////////////////
                        $new_status = $status_order_id;
                        $relation_field = $order_position['statusId'];
                
                        $current_relation_field = "";
                        $caption_relation_field = trim($relation_field);
                
                        if (!empty($relations[$caption_relation_field])) {
                
                            $current_relation_field = $relations[$caption_relation_field];
                
                            if(!empty($current_relation_field['status_store'])) $new_status = $current_relation_field['status_store'];
                
                        }
                        ////////////////////
                        echo "<pre>";
                        print_r( "СТАТУСЫ" );
                        echo "</pre>";
    	                echo "<pre>";
                        print_r( $new_status );
                        echo "</pre>";
                         echo "<pre>";
                        print_r( $relation_field );
                        echo "</pre>";
                        echo "\n";

    	                if($new_status > 0 && $new_status != $status_order_id)
                        {
                            //Отправляем запрос на изменение статуса позиции
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$itemId."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_HEADER, 0);
                            $curl_result = curl_exec($ch);
                            curl_close($ch);
                        }
                        
	                   	//Обновляем SAO-статус
                    	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message  WHERE `id` = :id;');
                    	$update_query->bindValue(':sao_message', $sao_message);
                    	$update_query->bindValue(':id', $itemId);
                    	
                    	if( $update_query->execute() != true) {
                    			
                    		echo date("d-m-Y H:i:s")."\n";
                    		echo "Ошибка обновления SAO-статуса\n";
                    		echo "=======================================================\n";
                    		
                    		$sao_result_message .= "При обновлении SAO-состояния позиции ID-$itemId (заказ $order_id) возникли ошибки, обратитесь к инженеру поддержки. ";	
                    	}
                    	else
                    	{
                    	    $sao_result_message .= "Получено актуальное состояние для позиции ID-$itemId. ";
                    	}
	                }
	                else
	                {
	                    $sao_result_message .= "Ошибка ответа по заказу $order_id. Не нашли идентификатор позиции в Базе данных магазина. ";
	                }

	            }
	        }
	        else
	        {
	           $sao_result_message .= "Ошибка ответа по заказу $order_id. В ответе отсутствуют позиции. "; 
	        }
	    }
	    
	    $sao_result["status"] = true;
	    $sao_result["message"] = $sao_result_message;
	}
	else
	{
	    $sao_result_message .= "Ошибка ответа. Нет данных от поставщика. ";
	    $sao_result['status'] = false;
	}
	
} catch ( Exception $e ) {
	
	$sao_result['status'] = false;
	$sao_result['message'] = $e->getMessage();
	
}

file_put_contents( $supplier_dir . "/dump_update.log", ob_get_clean() );
?>