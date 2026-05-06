<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/mparts/errors_DO_ORDER.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 

**********************************************************/

$jsonParams = json_decode($order_item["t2_json_params"]);


$url = "http://v01.ru/api/devinsight/orders/instant";

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => 'userlogin='.$connection_options["login"].'&userpsw='.md5($connection_options["password"]).'&positions[0][number]='.$jsonParams->number .'&positions[0][brand]='.$jsonParams->brand .'&positions[0][supplierCode]='.$jsonParams->supplierCode .'&positions[0][itemKey]='.$jsonParams->itemKey .'&positions[0][quantity]=' . $order_item["count_need"] . '&paymentMethod=' . $connection_options["paymentMethod"] . '&shipmentMethod=' . $connection_options["shipmentMethod"]. '&shipmentAddress=' . $connection_options["shipmentAddress"]. '&shipmentOffice=' . $connection_options["shipmentOffice"],
));

$response = curl_exec($curl);

curl_close($curl);

$answer = json_decode( $response );


echo "<pre>";
echo date('Y-m-d H:i:s');
echo "</pre>";
echo "</br>";
echo "<pre>";
echo "Результат";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($answer);
echo "</pre>";
echo "</br>";


if( $answer->status === 1 ) 
{
	$orders = $answer->orders;
	
	//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
	$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
	$new_status_query->bindValue(':id', 2);
	
	if ( ! $new_status_query->execute() )
		throw new QueryException( "Товар заказан у поставщика, но произошёл сбой при смене статуса позиции!" );

	$new_status_record = $new_status_query->fetch();
	$new_status = $new_status_record["status_id"];

	if( $new_status > 0 ) 
	{
		//Отправляем запрос на изменение статуса позиции
		$ch = curl_init();

		$url = $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status={$new_status}&key=".urlencode($DP_Config->tech_key);
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		$execute = curl_exec( $ch );
		curl_close( $ch );
	}
	$orderId = [];
	$orderId["orderId"] = $orders[0]->number;
	$state_object = json_encode( $orderId );
	
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
	$update_query->bindValue(':sao_state', 2);
	$update_query->bindValue(':sao_message', 'Товар заказан у поставщика<br/>Статус: '.$orders[0]->status . '<br />Номер заказа: ' . $orders[0]->number);
	$update_query->bindValue(':sao_state_object', $state_object);
	$update_query->bindValue(':id', $order_item["id"]);
		
	if( ! $update_query->execute() ) 
	{
		throw new Exception( "Товар заказан у поставщика, но произошёл сбой при смене SAO-Состояния!" );
	}

	$sao_result["status"] = true;
	$sao_result["message"] = "";
}

else { throw new Exception( "Ошибка заказа: " . $answer->errorMessage . "!" ); }

$dump = ob_get_contents();
file_put_contents($pathError, $dump);
ob_end_clean();
?>