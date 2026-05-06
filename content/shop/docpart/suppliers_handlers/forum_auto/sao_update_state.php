<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/***********************************************************************
* SAO
* Действие: Проверить состояние

* Данный скрипт выполняется в контексте:
	** либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
	** либо в контексте скрипта робота
	
*************************************************************************/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/forum_auto/UPD_STATE.log";

//Структура результата 
$sao_result = array();
$sao_state_object	= array(); //Запись тех параметров после выполнения действия

//*************************************************************************

$sao_result["status"]	= false;

$sao_data	= json_decode($order_item["t2_json_params"], true);
//Специальные параметры
$sao_state_object = json_decode($order_item["sao_state_object"], true);

$login		= $connection_options["login"];
$password	= $connection_options["password"];
$gid = !empty($sao_state_object['did']) ? $sao_state_object['did'] : null;


/************************************
	Разобраться с SAO - статусом
*************************************/
$current_sao_state = $order_item["sao_state"];

$sao_info = "";
try
{
    if(empty($gid)) {
	    throw new Exception("Отсутствует ИД заказа в системе поставщика.");
	}
	
	$params = array(
        'login' => $login,
        'pass' => $password,
        'gid' => $gid
    );
    
    echo "<pre>";
    echo date('Y-m-d H:i:s');
    echo "</pre>";
    echo "</br>";
    echo "Параметры";
    echo "</br>";
    echo "<pre>";
    print_r($params);
    echo "</pre>";
    echo "</br>";

    //Создание объекта клиента
	try
	{
		$client = new SoapClient("http://api.forum-auto.ru/wsdl", array('soap_version' => SOAP_1_2, 'exceptions' => true));
	}
	catch (SoapFault $e)//Не можем создать клиент SOAP
	{
		throw new Exception("Ошибка Soap: " . $e->getMessage());
	}
	
    $soap_result = $client->listOrders($login, $password, $gid);	
    
    echo "Ответ";
    echo "</br>";
    echo "<pre>";
    print_r($soap_result);
    echo "</pre>";
    echo "</br>";

	if(!empty($soap_result[0])) {
        $order = $soap_result[0];
        
        $status_order_name = $order['status'];
        $status_order_id = $order['statusId'];
        

		// if($new_status > 0 && $new_status != $order_item["status"])
		// {
		// 	//Отправляем запрос на изменение статуса позиции
		// 	$ch = curl_init();
		// 	curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
		// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 	curl_setopt($ch, CURLOPT_HEADER, 0);
		// 	$curl_result = curl_exec($ch);
		// 	curl_close($ch);
		// }
		
		$date = date("d-m-Y H:i:s");
        $sao_message = "Заказ: {$gid}<br />Статус: {$status_order_name}<br /> Код статуса: {$status_order_id}</br />Обновлено: {$date}";
		
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

	
	} else {
	    throw new Exception("Ошибка получения данных после запроса listOrders.");
	}

}
catch(Exception $e)
{
	echo date("d-m-Y H:i:s")."\n";
	echo "Ошибка: \n";
	echo $e->getMessage()."\n";
	echo "============================================================\n";
	
	$sao_result["status"] = false;
	$sao_result["message"] = "Ошибка подключения в сервису, обратитесь к инженеру Docpart";
}

//Лог
$dump = ob_get_contents();
file_put_contents($pathError, $dump, FILE_APPEND);
ob_end_clean();
?>