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

ob_start();

// --------------------------------------------------------------------------------------
//1. Получаем логин и пароль

$login = $connection_options["login"];
$password = $connection_options["password"];

// --------------------------------------------------------------------------------------
//2. Отправляем запрос поставщику


//Параметры склада из позиции:
$t2_json_params = json_decode($order_item["t2_json_params"], true);

//Специальные параметры
$sao_state_object = json_decode($order_item["sao_state_object"], true);

$orderNumber = isset($sao_state_object['RESP']['ITEMS'][0]['RESULT'][0]['VBELN']) ? $sao_state_object['RESP']['ITEMS'][0]['RESULT'][0]['VBELN'] : null;


if(empty($orderNumber)) {
    
    $sao_result["status"] = false;
	$sao_result["message"] = 'Отсутствует номер заказа в базе данных';
	
} else {
    
    //Параметры запроса на создание заказа
    $fields = array(
    	'format' => "json",
    	'VKORG' => $t2_json_params["VKORG"],
    	'KUNRG' => $t2_json_params["KUNNR_RG"],
        'ORDER' => $orderNumber
    );
    

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
    	//ОШИБКА КОМАНДЫ
    	$sao_result["status"] = false;
    	$sao_result["message"] = "API поставщика вернул код ".$curl_result_armtek["STATUS"].". Сообщения Армтек: ";
    	for($i=0; $i < count($curl_result_armtek["MESSAGES"]); $i++)
    	{
    		$sao_result["message"] .= " ".$curl_result_armtek["MESSAGES"][$i]["TEXT"];
    	}
    	
    	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
    	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
    	$update_query->bindValue(':sao_message', 'Ошибка проверки состояния: '.date("d.m.Y H:i:s", time()));
    	$update_query->bindValue(':id', $order_item_id);
    	$update_query->execute();
    }
    else
    {
    	$ERROR = isset($curl_result_armtek["RESP"]["ITEMS"][0]["ERROR"]) ? $curl_result_armtek["RESP"]["ITEMS"][0]["ERROR"] : null;
    	
    	if( empty($ERROR) )//ОШИБОК ЗАПРОСА НЕТ
    	{
    		if( count($curl_result_armtek["MESSAGES"]) != 0  )//НО ЗАКАЗ НЕ ОФОРМЛЕН
    		{
    			$sao_result["status"] = false;
    			$sao_result["message"] = "Запрос выполнен, однако API поставщика ответил: ";
    			for($i=0; $i < count($curl_result_armtek["MESSAGES"]); $i++)
    			{
    				if($i > 0)
    				{
    					$sao_result["message"] = $sao_result["message"].",";
    				}
    				$sao_result["message"] = $sao_result["message"]." ".$curl_result_armtek["MESSAGES"][$i]["TEXT"];
    			}
    			
    			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
    			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
    			$update_query->bindValue(':sao_message', 'Ошибка проверки состояния: '.date("d.m.Y H:i:s", time()));
    			$update_query->bindValue(':id', $order_item_id);
    			$update_query->execute();
    		}
    		else//УСПЕХ - ЗАКАЗ ОФОРМЛЕН В АРМТЕК
    		{
    			//Новое состояние будет 2 - Заказано
    		
    			//4. Вносим изменения в своей базе
    			
    			$supplier_statuses = array(
            	    0 => "Создан",
                    1 => "В работе",
                    2 => "Закрыт",
                    3 => "Отклонен"
                );
    			
    			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
    			$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 2;');
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
    			
    			$result_header = $curl_result_armtek["RESP"]["HEADER"];
    			
    			echo "Статус";
                echo "</br>";
                echo "<pre>";
                print_r($result_header);
                echo "</pre>";
                echo "</br>";
    			
    			$date = date("d-m-Y H:i:s");
		        $sao_message = "Состояние : {$result_header['ORDER_STATUS']}<br /> Обновлено: {$date}";
    			
    			//4.1. Состояние позиции, //4.1. Запись технических параметоров SAO, // 4.3. Отображаемые комментарий
    			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
         		$update_query->bindValue(':sao_message', $sao_message);
        		$update_query->bindValue(':id', $order_item["id"]);
    			
    			if( !$update_query->execute() )
        		{	
        			$sao_result["status"] = false;
        			$sao_result["message"] = "Не удалось обновить состояние, обратитесь к инженеру Docpart";
        		}
        		else
        		{
        			//6. Формируем ответ
    		    	$sao_result["status"] = true;
    		    	$sao_result["message"] = "Состояние получено.";
        		}
    			

    		}
    	}
    	else//ЗАКАЗ НЕ ОФОРМЛЕН - ВЫДАЕМ СООБЩЕНИЕ
    	{
    	    $ERROR_MESSAGE = $curl_result_armtek["RESP"]["ITEMS"][0]["ERROR_MESSAGE"];
    	    
    		$sao_result["status"] = false;
    		$sao_result["message"] = "Запрос выполнен, однако API поставщика ответил: ".$ERROR_MESSAGE;
    		
    		//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
    		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
    		$update_query->bindValue(':sao_message', 'Ошибка проверки состояния: '.date("d.m.Y H:i:s", time()));
    		$update_query->bindValue(':id', $order_item_id);
    		$update_query->execute();
    	}
    }
    
}





file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/armtek/sao.log", ob_get_clean());
?>