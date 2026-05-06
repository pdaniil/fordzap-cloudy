<?php
// require_once( $_SERVER['DOCUMENT_ROOT'] . "/Logger.php" );
require_once( 'FrozaApi.php' );


$login 		= $connection_options['login'];
$password		= $connection_options['password'];
$wsdl 			= "http://www.froza.ru/webservice/basket.php?WSDL";

$api_options	= array (
	"login" => $login,
	"password" => $password,
	"wsdl" => $wsdl
);

$action 		= "changeStatusByGlobalID";
$item_params	= json_decode( $order_item["sao_state_object"], true );

$params_action = array (
	"global_id" => $item_params['global_id']
);

$api = new FrozaApi( $api_options );

$api->getAction( $action );
$api->getParamsAction( $params_action );

try {
	
	$res_action = $api->execAction();
	
	if ( is_object( $res_action ) ) {
		
		$StatusByGlobalID = $res_action->changeStatusByGlobalIDResult->StatusByGlobalID;
		
		if ( $StatusByGlobalID->ok == 1 ) {
			
			$new_state_id = 2;
			
			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
			$new_status_query->bindValue(':id', $new_state_id);
			$new_status_query->execute();
			$new_status_record = $new_status_query->fetch();
			$new_status = $new_status_record["status_id"];
			if($new_status > 0)
			{
				//Отправляем запрос на изменение статуса позиции
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$curl_result = curl_exec($ch);
				curl_close($ch);
			}
				
			$data = date("d-m-Y H:i:s");
			$sao_message = "Позиция в заказе <br/> {$data}<br/> GlobalID: {$item_params['global_id']}";
			//Обновляем статус позиции в docpart
			$UPDATE_ORDERITEM = "
				UPDATE 
					`shop_orders_items`
				SET
					`sao_state` = 2,
					`sao_message` = ?,
					`sao_robot` = 2
				WHERE
					`id` = ?
			;";
	
			$binds = array();
			$binds[] = $sao_message;
			$binds[] = $order_item["id"];
			
			$stmt = $db_link->prepare( $UPDATE_ORDERITEM );
			$res_exec = $stmt->execute( $binds );
			
			if ( ! $res_exec ) {
				
				$error_message = "Товар заказан, но произошла ошибка обновления позиции!";	
				throw new Exception( $error_message );
				
			}
				
			$sao_result["status"] = true;
			
		}
		else {
			
			throw new Exception( 'Неизвестный статус создания заказа' );
			
		}
		
	}
	else {
		
		throw new Exception( 'Ошибка отправки позиции в заказ' );
		
	}
	
} 
catch ( Exception $e ) {
	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
	
}

// Logger::writeLog( __DIR__, 'dump_sao_do_order.log' );

?>