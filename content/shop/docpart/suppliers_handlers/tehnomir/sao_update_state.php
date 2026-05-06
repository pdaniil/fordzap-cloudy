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

$state_object = json_decode( $order_item['sao_state_object'], true );

$order_reference = isset($state_object["reference"]) ? $state_object["reference"] : null;

if(empty($order_reference)) {
	throw new Exception("Отсутствует <reference> в Базе Данных для выбранной позиции.");
}

$url = "https://api.tehnomir.com.ua/order/getPositionInfo";

$postdata = array(
	"apiToken" => $token,
	"reference" => $order_reference,
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

		$result_order_item = $data['data'][0];

		$action_status_data = $result_order_item['states'][0];
		$action_status = $action_status_data['statusId'];
		$action_name = $action_status_data['status'];


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

		///////////////////
		$new_status = $order_item["status"];
		$relation_field = $action_status;

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

		$sao_message = 'Обновлено состояние: '.date("d.m.Y H:i:s", time());
		$sao_message .= '<br />Статус: ' . $action_name;
		$sao_message .= '<br />Код статуса: ' . $action_status;
		//Состояние позиции, Запись технических параметоров SAO, Отображаемые комментарий
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_robot` = :sao_robot WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', 2);
		$update_query->bindValue(':sao_robot', 2);
		$update_query->bindValue(':sao_message', $sao_message );
		$update_query->bindValue(':id', $order_item['id']);
		$update_query->execute();


		//---------Успешно---------//
		$sao_result["status"] = true;
		$sao_result["message"] = "";
		//---------Успешно---------//


	} else {
		throw new Exception("Ошибка обновления состояния.");
	}
	
} catch(Exception $e) {
	echo $e->getMessage();
	$sao_result["message"] = $e->getMessage();
}		
file_put_contents($path_script . "/update_state.log", ob_get_clean());
?>