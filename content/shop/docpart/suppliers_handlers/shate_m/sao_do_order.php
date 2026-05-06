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

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/shate_m/DO_ORDER.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 

**********************************************************/

//Все строки заказа должны быть из одного locationCode, иначе заказ не будет оформлен.
//Если deliveryInfo (информация о доставке) == null или не передан, то заказ будет оформлен на самовывоз.
//Если deliveryDateTime == null, то заказ будет оформлен на ближайшую возможную доставку (в зависимости от deliveryType).

//expectedDateTimeOfArrivalAllItems - Дата и время (UTC) прибытия всех строк заказа к клиенту,к которой все строки должны быть отгружены/доставлены.Все строки заказа будут валидироваться на возможность отгрузки/доставки(в зависимости от параметров заказа) к указанной дате и времени.И если хоть одна строка не будет отгружена/доставлена к данному времени(дата и время отгрузки/доставки по строке больше переданного значения),то заказ не будет создан. Не обязательно к заполнению. Если не заполнено, то дата и время доставки/отгрузки не будут валидироваться.


$t2_json_params = json_decode($order_item["t2_json_params"], true);

$order_item_id = $order_item['id'];

$PriceId = $t2_json_params['PriceId'];

$AgreementCode = !empty($connection_options["AgreementCode"]) ? $connection_options["AgreementCode"] : null;
$AgreementCodeGroup = !empty($connection_options["AgreementCodeGroup"]) ? $connection_options["AgreementCodeGroup"] : null;
$fio = !empty($connection_options["fio"]) ? $connection_options["fio"] : null;
$phone = !empty($connection_options["phone"]) ? $connection_options["phone"] : null;
$delivery = !empty($connection_options["delivery"]) ? $connection_options["delivery"] : null;

$item = array(
    "priceId" => $PriceId,
    "quantity" => $order_item["count_need"],
    "comment" => $order_item["id"]
);

$priceItems[] = $item;

$params = array(
    "agreeWithTermsOfDelivery" => true,
    "agreeWithPersonalDataProcessingPolicyAndUserAgreement" => true,
    "priceItems" => $priceItems
);

$codeGroup = array('НАЛ', 'ЭКВАИР', 'МАГ'); //Если код группы договора == "НАЛ", "ЭКВАИР" или "МАГ", то поля fio и phone обязательны к заполнению
try {
    
    if(empty($AgreementCode)) {
        throw new Exception("Отсутствует Код Договора. Выберите значение в настройках склада.");
    }
    
    if(in_array($AgreementCodeGroup, $codeGroup)) {
        if(empty($fio) || empty($phone)) {
            throw new Exception("Телефон и ФИО обязательны если выбран договор группы НАЛ. Выберите значение в настройках склада.");
        }
        
        $params['fio'] = $fio;
        $params['phone'] = $phone;
    }
    
    $params['agreementCode'] = $AgreementCode;
    
    if(!empty($delivery) && $delivery == 'courier') {
        
        $delivery_params = array();
        if(empty($connection_options["DeliveryType"])) {
            throw new Exception("При выборе Доставки по адресу поле Тип доставки обязательно.");
        }
        if(empty($connection_options["DeliveryAddressCode"])) {
            throw new Exception("При выборе Доставки по адресу поле Код адреса доставки обязательно.");
        }
        $delivery_params['DeliveryType'] = $connection_options["DeliveryType"];
        $delivery_params['DeliveryAddressCode'] = $connection_options["DeliveryAddressCode"];
        
        $params['DeliveryInfo'] = $delivery_params;
        
    }
    
    
    $api_path = "";
	switch($connection_options["country"])
	{
		case "ru":
			$api_path = "https://api.shate-m.ru/api/v1";
			break;
		case "by":
			$api_path = "https://api.shate-m.by/api/v1";
			break;
		case "kz":
			$api_path = "https://api.shate-m.kz/api/v1";
			break;
		default: $api_path = "https://api.shate-m.ru/api/v1";
	}
	
	if(!empty($connection_options['login']) && !empty($connection_options['password'])) {
		$url = $api_path . '/auth/login';
		$auth_fields = array(
			'Login' => $connection_options['login'],
			'Password' => $connection_options['password']
		);
	} else if(!empty($connection_options["api_key"])) {
		$url = $api_path . '/auth/loginbyapikey';
		$auth_fields = array(
			'ApiKey' => $connection_options['api_key']
		);
	} else {
	    throw new Exception("Отсутствует Логин и Пароль или Api-Key для авторизации в Шате-М.");
	}
	
	$auth_field_string = http_build_query($auth_fields);
	
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $auth_field_string);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	
	$curl_json = curl_exec($ch);

	$curl_result = json_decode($curl_json, true);
	
	curl_close($ch);
	
	$token = "";

	if(!empty($curl_result['access_token'])) {
	    
		$token = $curl_result['access_token'];
		
	} else {
		throw new Exception("Ошибка получения токена. " . $url);
	}
	
	echo "<pre>";
    echo "Данные";
    echo "</pre>";
    echo "<br>";
    echo "<pre>";
    print_r($params);
    echo "</pre>";
    echo "<br>";

    
	$post_fields_string = json_encode($params);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_path . "/orders/bypriceitems"); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields_string);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"Content-Type: application/json",
		"Authorization: Bearer $token"
	));

	$result = curl_exec($ch);
	$order_result = json_decode($result, true);
    
    
    echo "<pre>";
    echo "Ответ на запрос";
    echo "</pre>";
    echo "<br>";
    echo "<pre>";
    print_r(curl_getinfo($ch));
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r(curl_error($ch));
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    echo "<br>";
    echo "<pre>";
    print_r($order_result);
    echo "</pre>";
    echo "<br>";
    
    curl_close($ch);
    
    
    if(isset($order_result['id'])) {
        
        $sao_obj = json_encode($order_result);
        
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
		
		$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа: " . $order_result['id'];
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = 2, `sao_robot` = 2, `sao_state_object`= :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state_object', $sao_obj);
		$update_query->bindValue(':sao_message', $sao_message);
		$update_query->bindValue(':id', $order_item_id);
		$update_query->execute();
		
		//5. Указываем задание роботу (при необходимости)
		//... У Армтек нет заданий роботу
		
		//6. Формируем ответ
		$sao_result["status"] = true;
		$sao_result["message"] = "Позиция заказана.";
    }
    else
    {
        throw new Exception("Произошла ошибка при запросе. Проверьте настройки или повторите позже.");
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