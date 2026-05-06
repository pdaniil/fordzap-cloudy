<?php
/**
SAO
Действие: Заказать

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота
*/

// --------------------------------------------------------------------------------------

//0. Структура результата
$sao_result = array();
$sao_result["status"] = false;
$sao_result_message = "";

ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/armtek/errors_UPDATE_STATE.log";

// --------------------------------------------------------------------------------------
//1. Получаем логин и пароль

$login = $connection_options["login"];
$password = $connection_options["password"];
$vkorg = !empty($connection_options["vkorg"]) ? $connection_options["vkorg"] : null;
$kunrg = !empty($connection_options["kunrg"]) ? $connection_options["kunrg"] : null;
// --------------------------------------------------------------------------------------
//2. Отправляем запрос поставщику

	//Параметры запроса на создание заказа
	$fields = array(
		'format' => "json",
		'VKORG' => $vkorg,
		'KUNRG' => $kunrg
	);

//Массив всех запрошенных заказов
$orderNumbers = array();

if(isset($multi_order) && $multi_order === true) {

	foreach($order_items as $order_item) {

		$t2_json_params = json_decode($order_item["t2_json_params"], true);
		$sao_state_object = json_decode($order_item["sao_state_object"], true);

		$orderNumber = isset($sao_state_object['RESP']['ITEMS'][0]['RESULT'][0]['VBELN']) ? $sao_state_object['RESP']['ITEMS'][0]['RESULT'][0]['VBELN'] : null;

		if(!empty($orderNumber)) {
			if(!in_array($orderNumber, $orderNumbers)) $orderNumbers[] = $orderNumber;
		}
		else {
			
			$sao_result_message .= "У позиции ID" . $order_item['id'] . " отсутствует номер заказа поставщика. ";
		}
	}
}
else
{
	
	$t2_json_params = json_decode($order_item["t2_json_params"], true);
	$sao_state_object = json_decode($order_item["sao_state_object"], true);
    
	$orderNumber = isset($sao_state_object['RESP']['ITEMS'][0]['RESULT'][0]['VBELN']) ? $sao_state_object['RESP']['ITEMS'][0]['RESULT'][0]['VBELN'] : null;

	if(!empty($orderNumber)) {
		$orderNumbers[] = $orderNumber;
	}
}


if(empty($orderNumbers)) {
		    
	$sao_result["status"] = false;
	$sao_result["message"] = 'Отсутствует номер заказа для отправки поставщику';

} else {
	
	foreach ($orderNumbers as $orderNumber) {
		
		$fields['ORDER'] = $orderNumber;

		echo "<pre>";
		echo date('Y-m-d H:i:s');
		echo "</pre>";
		echo "</br>";
		echo "Параметры";
		echo "</br>";
		echo "<pre>";
		print_r($fields);
		echo "</pre>";
		echo "</br>";

		$field_string = http_build_query($fields);
		//Запрос на создание заказа
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_order/getOrder?$field_string");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
		$curl_result_armtek = curl_exec($ch);
		$curl_result_str = $curl_result_armtek;//Для записи строки ответа в БД
		curl_close($ch);
	
		$curl_result_armtek = json_decode($curl_result_armtek, true);
		
		echo "Результат";
		echo "</br>";
		echo "<pre>";
		print_r($curl_result_armtek);
		echo "</pre>";
		echo "</br>";


		//3. Анализируем результат
		if($curl_result_armtek["STATUS"] != 200)
		{
			$sao_result_message .= "API поставщика вернул код ".$curl_result_armtek["STATUS"].". Сообщения Армтек: ";
			for($i=0; $i < count($curl_result_armtek["MESSAGES"]); $i++)
			{
				$sao_result_message .= $curl_result_armtek["MESSAGES"][$i]["TEXT"] . ". ";
			}
		}
		else
		{

			for($i=0; $i < count($curl_result_armtek["MESSAGES"]); $i++)
			{
				$sao_result_message .= $curl_result_armtek["MESSAGES"][$i]["TEXT"] . ". ";
			}

			foreach ($curl_result_armtek["RESP"]["ITEMS"] as $result_order_item) {

				if( isset($result_order_item['ERROR']) )
				{

					$ERROR_MESSAGE = $result_order_item["ERROR_MESSAGE"];
						
					$sao_result_message .= "Запрос выполнен для позиции ID" .$result_order_item["NOTE"]. ", однако API поставщика ответил: ".$ERROR_MESSAGE . ". ";

				}
				else//ЗАКАЗ НЕ ОФОРМЛЕН - ВЫДАЕМ СООБЩЕНИЕ
				{
				    

				// 	//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
				// 	$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 2;');
				// 	$new_status_query->execute();
				// 	$new_status_record = $new_status_query->fetch();
				// 	$new_status = $new_status_record["status_id"];
				// 	if($new_status > 0)
				// 	{
				// 		//Отправляем запрос на изменение статуса позиции
				// 		$ch = curl_init();
				// 		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
				// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				// 		curl_setopt($ch, CURLOPT_HEADER, 0);
				// 		$curl_result = curl_exec($ch);
				// 		curl_close($ch);
				// 	}
					
					$result_header = $curl_result_armtek["RESP"]["HEADER"];
					
					echo "Статус";
					echo "</br>";
					echo "<pre>";
					print_r($result_header);
					echo "</pre>";
					echo "</br>";
					
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
				
					//Новое состояние будет 2 - Заказано
				
					//4. Вносим изменения в своей базе
					
					$supplier_statuses = array(
									"Создан" => 1,
									"В работе" => 2,
									"Закрыт" => 3,
									"Отклонен" => 5
								);

					$order_item_id = $result_order_item["NOTE"];
                	        
        	        $status_sql = "SELECT `status` FROM `shop_orders_items` WHERE id = ?";		
                	$status_query = $db_link->prepare($status_sql);
                	$status_query->execute(array($order_item_id));
                	$status_arr = $status_query->fetchColumn();
                	$status_order_id = (int) $status_arr;
                	
                	////////////////////
    				$new_status = $status_order_id;
    				$relation_field = $supplier_statuses[trim($result_header['ORDER_STATUS'])];
                    
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
    				
					$date = date("d-m-Y H:i:s");
					$sao_message = "Состояние : {$result_header['ORDER_STATUS']}<br /> Обновлено: {$date}";
					
					//4.1. Состояние позиции, //4.1. Запись технических параметоров SAO, // 4.3. Отображаемые комментарий
					$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
					 $update_query->bindValue(':sao_message', $sao_message);
					$update_query->bindValue(':id', $order_item_id);
					
					if( !$update_query->execute() )
					{	
						$sao_result_message .= "Не удалось обновить состояние для позиции ID{$order_item_id}, обратитесь к инженеру Docpart. ";
					}
					
				}
			}

		}

	}

	//6. Формируем ответ
	$sao_result["status"] = true;
	$sao_result["message"] = $sao_result_message;
}

$dump = ob_get_contents();
file_put_contents($pathError, $dump);
ob_end_clean();

?>