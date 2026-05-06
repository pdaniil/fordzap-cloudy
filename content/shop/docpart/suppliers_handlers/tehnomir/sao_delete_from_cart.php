<?php
/**
	* Удаление позиции из корзины поставщика
*/

$path_script = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/tehnomir";

ob_start();

$sao_result = array(
	"status" => false,
	"message" => ""
);

/*****Учетные данные*****/
$token = $connection_options["token"];
/*****Учетные данные*****/

$url = "https://api.tehnomir.com.ua/basket/delete";

$sao_params = json_decode($order_item["sao_state_object"] ,true );
$basketId = isset($sao_params['basketId']) ? $sao_params['basketId'] : null;

$postdata = array(
	"apiToken" => $token,
	"basketId" => $basketId
);

$headers = array(
	"Content-Type: application/json",
	"accept: application/json"
);


// -------------------------------------------------------------------------------------------------

try {

	$postdata = json_encode($postdata);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_VERBOSE, 1 );
	$execute = curl_exec($ch);
	$data = json_decode($execute, true);

	echo "<pre>";
	echo date('Y-m-d H:i:s');
	echo "</pre>";
	echo "</br>";
	echo "<pre>";
	print_r($data);
	echo "</pre>";
	echo "</br>";

	if($data['success'] !== false) {
	    
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 7;');
		$new_status_query->execute();
		$new_status_record	= $new_status_query->fetch();
		$new_status 		= (int)$new_status_record["status_id"];
		
		if($new_status > 0) {
			//Отправляем запрос на изменение статуса позиции
			$ch = curl_init();
			$url = $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[{$order_item["id"]}]&status={$new_status}&key=".urlencode($DP_Config->tech_key);

			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_HEADER, 0 );
			$execute = curl_exec( $ch );
			
			curl_close( $ch );
		}

		$date_time = date("d-m-Y H:i:s");
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_robot` = :sao_robot, `sao_state_object` = :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', 1);
		$update_query->bindValue(':sao_robot', 3);
		$update_query->bindValue(':sao_state_object', '');
		$update_query->bindValue(':sao_message', 'Позиция удалена из корзины<br/> '.$date_time);
		$update_query->bindValue(':id', $order_item["id"]);
		
		
		if( ! $update_query->execute() ) {
			throw new QueryException( "Позиция добавлена в корзину поставщика, но произошёл сбой при смене SAO-Состояния!" );
		}
		
		//---------Успешно---------//
		$sao_result["status"] = true;
		$sao_result["message"] = "";
		//---------Успешно---------//

	} else {
		throw new Exception("Ошибка ответа поставщика. Требуется проверить параметры ответа.");
	}
	
} catch(Exception $e) {
	echo $e->getMessage();
	$sao_result["message"] = $e->getMessage();
}		
file_put_contents($path_script . "/delete_cart.log", ob_get_clean());
?>