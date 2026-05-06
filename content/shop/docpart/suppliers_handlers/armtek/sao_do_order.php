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

//0. Структура результата
$sao_result = array();
$sao_result["status"] = false;
$sao_result_message = "";

ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/armtek/errors_DO_ORDER.log";

// --------------------------------------------------------------------------------------
//1. Получаем логин и пароль

$login = $connection_options["login"];
$password = $connection_options["password"];
$vkorg = !empty($connection_options["vkorg"]) ? $connection_options["vkorg"] : null;
$kunrg = !empty($connection_options["kunrg"]) ? $connection_options["kunrg"] : null;

$sao_test_mode = $connection_options["sao_test_mode"];

if(empty($vkorg) || empty($kunrg)) {

	$sao_result["status"] = false;
	$sao_result["message"] = "Не заполнены поля Организация или Покупатель в настройках склада. ";

} else {


	//Дополнительные настройки 
	$kunwe = !empty($connection_options["kunwe"]) ? $connection_options["kunwe"] : null;
	$kunza_0 = !empty($connection_options["kunza_0"]) ? $connection_options["kunza_0"] : null;
	$kunza_1 = !empty($connection_options["kunza_1"]) ? $connection_options["kunza_1"] : null;
	$vbeln = !empty($connection_options["vbeln"]) ? $connection_options["vbeln"] : null;
	$parnr = !empty($connection_options["parnr"]) ? $connection_options["parnr"] : null;
	$incoterms = (isset($connection_options["incoterms"]) && $connection_options["incoterms"] == 1) ? true : false;

	//Параметры запроса на создание заказа
	$fields = array(
		'format' => "json",
		'VKORG' => $vkorg,
		'KUNRG' => $kunrg,
		'ITEMS' => array(),
		'DBTYP' => 3
	);

		/*
	1 - самовывоз. В поле KUNNR_ZA необходимо отправлять значения из таблицы RG_TAB->EXW_TAB-ID.
	0 - доставка. В поле KUNNR_ZA необходимо отправлять значения из таблицы
	*/
	if($incoterms === true) {
		$fields['INCOTERMS'] = 1;
		$fields['KUNZA'] = $kunza_1;
	} else {
		$fields['INCOTERMS'] = 0;
		$fields['KUNZA'] = $kunza_0;
	}

	//Грузополучатель
	if(!empty($kunwe)) {
		$fields['KUNWE'] = $kunwe;
	}

	//Договор
	if(!empty($vbeln)) {
		$fields['VBELN'] = $vbeln;
	}

	//Контактное лицо
	if(!empty($parnr)) {
		$fields['PARNR'] = $parnr;
	}


	if(isset($multi_order) && $multi_order === true) {
	        
	    foreach($order_items as $order_item) {
	        
	          $json_params = json_decode($order_item["t2_json_params"], true);
	    	    
	    	    $order_item_id = $order_item["id"];

	         	if(empty($json_params["KEYZAK"]) || empty($json_params["DLVDT"])) {
	        	    
	        	    $sao_result_message .= " Отсутствуют данные, позиция id$order_item_id не будет отправлена поставщику. \n";
	        	    
	        	} else {
	        	    
								$fields['ITEMS'][] = array(
									'PIN'=>$order_item["t2_article"],
									'BRAND'=>$order_item["t2_manufacturer"],
									'KWMENG'=>$order_item["count_need"],
									'KEYZAK'=>$json_params["KEYZAK"],
									'PRICEMAX'=>$order_item["price"],
									'DATEMAX'=>$json_params["DLVDT"],
									'COMMENT' => $order_item["id"]
									);
	        	}
	        
	    }
	    
	} else {
	    
	    //Параметры склада из позиции:
	    $t2_json_params = json_decode($order_item["t2_json_params"], true);
	    
	    $fields['ITEMS'][0] = array(
	    	'PIN'=>$order_item["t2_article"],
	    	'BRAND'=>$order_item["t2_manufacturer"],
	    	'KWMENG'=>$order_item["count_need"],
	    	'KEYZAK'=>$t2_json_params["KEYZAK"],
	    	'PRICEMAX'=>$order_item["price"],
	    	'DATEMAX'=>$t2_json_params["DLVDT"],
	    	'COMMENT' => $order_item["id"]
	    	);
	        
	}

	echo "<pre>";
	echo date('Y-m-d H:i:s');
	echo "</pre>";
	echo "</br>";
	echo "<pre>";
	echo "Параметры";
	echo "</pre>";
	echo "</br>";
	echo "<pre>";
	print_r($fields);
	echo "</pre>";
	    
	$field_string = http_build_query($fields);
	//Запрос на создание заказа
	$ch = curl_init();
	if($sao_test_mode)
	{
		curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_order/createTestOrder");//Тестовый заказ
	}
	else
	{
		curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_order/createOrder");//Боевой заказ
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
	$curl_result = curl_exec($ch);
	curl_close($ch);
	$curl_result_str = $curl_result;//Для записи строки ответа в БД
	$curl_result = json_decode($curl_result, true);

	echo "</br>";
	echo "<pre>";
	echo "Результат";
	echo "</pre>";
	echo "</br>";
	echo "<pre>";
	print_r($curl_result);
	echo "</pre>";


	//3. Анализируем результат

	if($curl_result["STATUS"] != 200)
	{
		//ОШИБКА КОМАНДЫ
		$sao_result["status"] = false;
		$error_message = "API поставщика вернул код ".$curl_result["STATUS"].". Сообщения Армтек: ";
		for($i=0; $i < count($curl_result["MESSAGES"]); $i++)
		{
			$error_message .= " ".$curl_result["MESSAGES"][$i]["TEXT"];
		}
		
	}
	else
	{
		$RESULT_ITEMS = isset($curl_result["RESP"]["ITEMS"]) ? $curl_result["RESP"]["ITEMS"] : array();
		
		if(!empty($RESULT_ITEMS)) {
		    
		    //Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 2;');
			$new_status_query->execute();
			$new_status_record = $new_status_query->fetch();
			$new_status = $new_status_record["status_id"];

			$error_check = false;
		    
		    foreach($RESULT_ITEMS as $RESULT_ITEM) {
		        
		        $order_sao_id = isset($RESULT_ITEM['COMMENT']) ? $RESULT_ITEM['COMMENT'] : null;
		        if(isset($RESULT_ITEM['ERROR_MESSAGE']) && !empty($RESULT_ITEM['ERROR_MESSAGE'])) {
		            
		            $sao_result_message .= " Позиция ID" . $order_sao_id . ": " . $RESULT_ITEM['ERROR_MESSAGE'];

								if($RESULT_ITEM['ERROR'] == 1) {
									$error_check = true;
								}
		            
		        } else {
		            
		            if($new_status > 0)
			    			{
			    				//Отправляем запрос на изменение статуса позиции
			    				$ch = curl_init();
			    				curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_sao_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
			    				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			    				curl_setopt($ch, CURLOPT_HEADER, 0);
			    				$curl_result = curl_exec($ch);
			    				curl_close($ch);
			    			}
	    			
			    			//4.1. Состояние позиции, //4.1. Запись технических параметоров SAO, // 4.3. Отображаемые комментарий
			    			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = 2, `sao_robot` = 2, `sao_state_object`= :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
			    			$update_query->bindValue(':sao_state_object', $curl_result_str);
			    			$update_query->bindValue(':sao_message', 'Заказано: '.date("d.m.Y H:i:s", time()));
			    			$update_query->bindValue(':id', $order_sao_id);
	    			
  							if( !$update_query->execute() )
                {
              		echo date("d-m-Y H:i:s")."\n";
              		echo "Ошибка обновления SAO-статуса\n";
              		echo "=======================================================\n";
              		
              		$sao_result_message .= " Заказ для позиции id-$order_sao_id создан, но при обновлении SAO-состояния возникли ошибки, обратитесь к инженеру поддержки.";
              	}
              	else
              	{
                  $sao_result_message .= " Заказ для позиции id-$order_sao_id создан.";
              	}
		            
		        }
		    }
		    
			if($error_check == true) {
				$sao_result["status"] = false;
			}
			else
			{
				$sao_result["status"] = true;
			}
		    
	        $sao_result["message"] = $sao_result_message;
		    
		}
		
	}

}

echo "============================================================\n";
echo "============================================================\n";

$dump = ob_get_contents();
file_put_contents($pathError, $dump, FILE_APPEND);
ob_end_clean();

?>