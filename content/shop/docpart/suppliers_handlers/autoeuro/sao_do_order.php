<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/**
SAO
Действие: Заказать

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота

ВЫВОД ДАМПА БУФФЕРИЗИРУЕТСЯ И ПИШЕТСЯ В ФАЙЛ $pathError
*/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/autoeuro_v2/DO_ORDER.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 

**********************************************************/

$t2_json_params = json_decode($order_item["t2_json_params"], true);

$offer_key = $t2_json_params['offer_key'];

$stock_item = [
	"offer_key" => $offer_key,
	"quantity" => $order_item["count_need"],
	"price" => $order_item["price"]
];

$stock_items[] = $stock_item;


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api2.autoeuro.ru/api/v2/json/create_order',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => 'delivery_key='.$connection_options["delivery_key"].'&payer_key='.$connection_options["payer_key"].'&stock_items=' . json_encode($stock_items),
  CURLOPT_HTTPHEADER => array(
    'key: ' . $connection_options["api_key"],
    'Content-Type: application/x-www-form-urlencoded'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
$query_result = json_decode($response);

try {
	if($query_result->DATA[0]->result)
	{
		$sao_obj["orderId"] = $query_result->DATA[0]->order_id;
		$sao_obj = json_encode($sao_obj);
		
		//Меняем SAO-Состояние
		$new_sao_state = 2; //Состояние "Заказано"
		
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
		$new_status_query->bindValue(':id', $new_sao_state);
		$new_status_query->execute();
		$new_status_record = $new_status_query->fetch();
		$new_status = $new_status_record["status_id"];
		if((int)$new_status > 0)
		{
			//Отправляем запрос на изменение статуса позиции
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
		}

		$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа:<br/>" . $query_result->DATA[0]->order_id;
		//Обновляем SAO-статус
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object, `sao_robot` = :sao_robot WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', $new_sao_state);
		$update_query->bindValue(':sao_message', $sao_message);
		$update_query->bindValue(':sao_robot', 0);
		$update_query->bindValue(':sao_state_object', $sao_obj);
		$update_query->bindValue(':id', $order_item["id"]);
		
		
		if( ! $update_query->execute() )
		{
			echo date("d-m-Y H:i:s")."\n";
			echo "Ошибка обновления SAO-статуса\n";
			echo mysqli_error($db_link)."\n";
			echo $UPDATE_SAO_STATUS."\n";
			echo "=======================================================\n";
			
			$sao_result["status"] = false;
			$sao_result["message"] = "Заказ создан, но при обновлении SAO-состояния возникли ошибки, обратитесь к инженеру поддрежки";
		}
		else
		{
			$sao_result["status"] = true;
		}
		
	} // ~if($result->success)
	else
	{
		$error_message = isset($query_result->ERROR[0]->error) ? $query_result->ERROR[0]->error : "Ошибка при оформлении заказа";
		throw new Exception($error_message);
	}
}
catch (Exception $e)
{
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
}
	


$dump = ob_get_contents();
file_put_contents($pathError, $dump);
ob_end_clean();
?>