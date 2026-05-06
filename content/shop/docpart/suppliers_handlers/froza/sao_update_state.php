<?php
// require_once( $_SERVER['DOCUMENT_ROOT'] . "/Logger.php" );

require_once( 'FrozaApi.php' );

ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/froza/UPD_STATE.log";


$login 		= $connection_options['login'];
$password		= $connection_options['password'];
$wsdl 			= "http://www.froza.ru/webservice/orders.php?WSDL";

$item_params = json_decode( $order_item['sao_state_object'], true );
$supp_order_id = $item_params['global_id'];

$order_data = array();

$sql_order = "
SELECT 
	`time` 
FROM 
	`shop_orders`
WHERE
	`id` = '{$order_item['order_id']}'
";

$mysqli_res = $db_link->prepare( $sql_order );
$mysqli_res->execute();
$time_for_order = $mysqli_res->fetchColumn();

$order_date 		= date( 'd.m.y', $time_for_order );
$now_date 			= date( 'd.m.y' );
$now_date_time	= date( 'd.m.y H:i:s' );

$api_options	= array (
	"login" => $login,
	"password" => $password,
	"wsdl" => $wsdl
);

$action = "getSubclientOrderDetails";

$params_action = array (
	'date_start' => $order_date,
	'date_end' => '',
	'status' => '',
	'archive' => '1',
	'search' => $order_item['t2_article'],
	'type_search' => '3',
	'date_st' => '0'
);


$api = new FrozaApi( $api_options );

$api->getAction( $action );
$api->getParamsAction( $params_action );

try {
	
	$res_action = $api->execAction();

	echo "<pre>";
	print_r($res_action);
	echo "</pre>";
	echo "</br>";
	
	if ( is_object( $res_action ) ) {
	    
	    
		
		// Logger::addLog( '$res_action', $res_action );
		// Logger::addLog( '$params_action', $params_action );
		
		$ClientOrderDetails = $res_action->getSubclientOrderDetailsResult->SubclientOrderDetails;
		
		//Позиций может быть несколько, нужно найти конкретную ( UPD: поиск по referense должен исправить эту проблему )
		if ( is_array( $ClientOrderDetails ) ) {
			
			$ClientOrderDetails = $api->searchOrderItemByOrderId( $ClientOrderDetails, $supp_order_id );
			
			if ( ! is_object( $ClientOrderDetails ) ) {
				
				$error_message = "Ошибка: в списке заказов не найден заказ с параметром GlobalID: {$supp_order_id}<br /> {$now_date_time}";
				
				$UPDATE_ORDERITEM = "UPDATE `shop_order_items` SET 'sao_message' = ? WHERE `id` = ?;";
				
				$binds = array();
				$binds[] = $error_message;
				$binds[] = $order_item["id"];
				
				$stmt = $db_link->prepare( $UPDATE_ORDERITEM );
				$res_exec = $stmt->execute( $binds );
				
				if ( ! $res_exec ) {
					
					$error_message = "Ошибка обновления состояния";	
					throw new Exception( $error_message );
					
				}
				
			}
		
		}
		
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
		
		$need_sao_state_id 		= 2;

		$status_id 				= $ClientOrderDetails->status_id;
		$state_name = $ClientOrderDetails->status_name;
		$price_client_primary 	= $ClientOrderDetails->price_client_primary;
		$price_client_final 		= $ClientOrderDetails->price_client_final;

		$need_sao_state_id 		= 2;
		
		$status_id 				= $ClientOrderDetails->status_id;
		$price_client_primary 	= $ClientOrderDetails->price_client_primary;
		$price_client_final 		= $ClientOrderDetails->price_client_final;
		
		$sao_message = "GlobalID: $supp_order_id<br/>";
		$sao_message .= "Статус заказа: $state_name<br/>";
		$sao_message .= "ID статуса: $status_id<br/>";
		$sao_message .= "Цена оптовика при заказе: $price_client_primary<br/>";
		$sao_message .= "Цена оптовика конечная: $price_client_final<br/>";
		$sao_message .= "{$now_date_time}";
		
        
        ////////////////////
    	$new_status = $order_item["status"];
    	$relation_field = $status_id;
    
        $current_relation_field = "";
        $caption_relation_field = trim($relation_field);
        
        if (!empty($relations[$caption_relation_field])) {
        			 
    		$current_relation_field = $relations[$caption_relation_field];
    		
    		if(!empty($current_relation_field['status_store'])) $new_status = $current_relation_field['status_store'];
    		
        }
        ////////////////////
    
    	if($new_status > 0 && $new_status != $order_item["status"])
    	{
    		//Отправляем запрос на изменение статуса позиции
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    		curl_setopt($ch, CURLOPT_HEADER, 0);
    		$curl_result = curl_exec($ch);
    		curl_close($ch);
    	}
	
		
		$UPDATE_ORDERITEM = "
		UPDATE 
			`shop_orders_items` 
		SET 
			`sao_state` = ?,
			`sao_message` = ?
		WHERE 
			`id` = ?;";
		
		$binds = array();
		$binds[] = $need_sao_state_id;
		$binds[] = $sao_message;
		$binds[] = $order_item["id"];
		
		$stmt = $db_link->prepare( $UPDATE_ORDERITEM );
		$res_exec = $stmt->execute( $binds );
		
		if ( ! $res_exec ) {
			
			$error_message = "Ошибка обновления состояния";
			throw new Exception( $error_message );
			
		}
		
		$sao_result["status"] = true; //Успех

	} // ~! if ( is_object( $res_action ) )
	else {
		
		$error_message = "Ошибка получения статуса заказа: {$res_action['error_message']}<br/>GlobalID: {$supp_order_id}<br /> {$now_date_time}";
		$UPDATE_ORDERITEM = "
		UPDATE 
			`shop_orders_items` 
		SET 
			`sao_message` = ?
		WHERE 
			`id` = ?;";
			
		$binds = array();
		$binds[] = $error_message;
		$binds[] = $order_item["id"];
		
		$stmt = $db_link->prepare( $UPDATE_ORDERITEM );
		$res_exec = $stmt->execute( $binds );
		
		if ( ! $res_exec ) {
			
			$error_message = "Ошибка обновления состояния";
			throw new Exception( $error_message );
			
		}
		
	}
	
} 
catch ( Exception $e ) {
	echo date("d-m-Y H:i:s")."\n";
	echo "Ошибка: \n";
	echo $e->getMessage()."\n";
	echo "============================================================\n";

	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
	
}

// Logger::writeLog( __DIR__, 'dump_sao_update.log' );
//Лог
$dump = ob_get_contents();
file_put_contents($pathError, $dump);
ob_end_clean();
?>