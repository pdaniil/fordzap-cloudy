<?php
/**
	* Добавление позиции в корзину поставщика
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

$url = "https://api.tehnomir.com.ua/order/create";

$postdata = array(
	"apiToken" => $token,
	"orderNumber" => '',
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
	    
	    $order_id = $data['data']['orderId'];

			//Теперь определяем все позиции, которые были заказаны из корзины
			$url = "https://api.tehnomir.com.ua/order/getOrderPositions";

			$postdata = array(
				"apiToken" => $token,
				"orderId" => $order_id,
			);
			
			$headers = array(
				"Content-Type: application/json",
				"accept: application/json"
			);

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

				$error_message = '';
				foreach($data['data'] as $data_item) {

					$order_item_id = $data_item['reference'];
					$order_item_info = $data_item['code'] . ' ' . $data_item['brand'] . ' ' . $data_item['descriptionRus'] . ' ' . $data_item['priceLogo'] . '.';

					if(!empty($order_item_id)) {

			  		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			  		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 2;');
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
			  		$update_query->bindValue(':sao_state', 2);
			  		$update_query->bindValue(':sao_robot', 2);
			  		$update_query->bindValue(':sao_state_object', json_encode($data_item));
			  		$update_query->bindValue(':sao_message', 'Позиция заказана<br/> '.$date_time);
			  		$update_query->bindValue(':id', $order_item_id);
			  		
			  		
			  		if( ! $update_query->execute() ) {
			  			$error_message .= "Позиция заказана, но произошёл сбой при смене SAO-Состояния! ";
			  		}
					}
					else
					{
						$error_message .= "У позиции $order_item_info не найден id в поле reference. ";
						if($data_item['comment'] !== 'Заказ с сайта honda-club.md') {
							$error_message .= "Позиция $order_item_info заказана вручную с сайта поставщика. ";
						}
					}

				}


	  		//---------Успешно---------//
	  		$sao_result["status"] = true;
	  		$sao_result["message"] = $error_message;
	  		//---------Успешно---------//
			}
			else {
				throw new Exception("Заказ у поставщика оформлен. Не удалось получить информацию о заказанных позициях. Обновите данные вручную.");
			}

	} else {
		throw new Exception("Ошибка ответа поставщика. Требуется проверить параметры ответа.");
	}
	
} catch(Exception $e) {
	echo $e->getMessage();
	$sao_result["message"] = $e->getMessage();
}		
file_put_contents($path_script . "/do_order.log", ob_get_clean());
?>