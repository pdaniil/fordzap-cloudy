<?php

ob_start();

$sao_result = array();

//Параметры склада из позиции:
$t2_json_params = json_decode($order_item["t2_json_params"], true);
$sao_state_object = json_decode($order_item["sao_state_object"], true);

$order_item_id = $order_item['id'];

$sao_state_result = isset($sao_state_object['result']) ? $sao_state_object['result'] : array();

$sao_state_productId = null;

if(!empty($sao_state_result)) {
    $sao_state_productId = $sao_state_result['id'];
}

$login 				= $connection_options["login"];
$key				= $connection_options["key"];
$delivery_option	= $connection_options["delivery_option"]; //Способ достаки

$url_request = "http://portal.moskvorechie.ru/portal.api?l={$login}&p={$key}&act=make_order_v2&on={$order_item_id}&dl={$delivery_option}&cs=utf8";//Запрос на добавление в коризну

if(!empty($sao_state_productId)) {
    $url_request .= "&gids={$sao_state_productId}";
}

echo "</br>";
echo "<pre>";
echo "Адрес запроса";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($url_request);
echo "</pre>";

try {
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url_request);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);

	$exec = curl_exec($ch);//Выполняем
	
	echo "</br>";
    echo "<pre>";
    echo "Результат";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($exec);
    echo "</pre>";
	
	if ( ! $exec ) {
		
		var_dump($url_request);
		throw new Exception("Ошибка culr: " . curl_errno($ch));
		
	}
	
	curl_close($ch);
	
	
	
	$decode = json_decode($exec, true);
	
	echo "</br>";
    echo "<pre>";
    echo "Результат обработанный";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($decode);
    echo "</pre>";
	
	if ( !$decode ) {
		throw new Exception("Ошибка json: " . json_last_error());
	}
	
	$action_result	= $decode['result'];
	
	if(isset($action_result['msg']) && preg_match("/таймаут/i", $action_result['msg'])) {
	    throw new Exception($action_result['msg']);
	}
	
	$new_sao_state = 2;
	
	//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
	$new_status_query = $db_link->prepare("SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;");
	$new_status_query->execute( array($new_sao_state) );
	$new_status_record = $new_status_query->fetch();
	$new_status = $new_status_record["status_id"];
	
	echo "</br>";
    echo "<pre>";
    echo "Новый статус";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($new_status);
    echo "</pre>";
    
    if((int)$new_status > 0)
	{
	    
	    echo "</br>";
        echo "<pre>";
        echo "Попали в блок";
        echo "</pre>";
        echo "</br>";
        echo "<pre>";
        print_r($new_status);
        echo "</pre>";
    
		//Отправляем запрос на изменение статуса позиции
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$curl_result = curl_exec($ch);
		

    echo "<pre>";
    print_r($curl_result);
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r(curl_getinfo($ch));
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r(curl_error($ch));
    echo "</pre>";
    echo "</br>";
        
        
		curl_close($ch);
	}
	
	$SQL = "
	UPDATE 
		`shop_orders_items` 
	SET 
		`sao_state_object` = ?,
		`sao_message` = ?, 
		`sao_state` = ?, 
		`sao_robot` = '0' 
	WHERE 
		`id` = ?;
	";
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	if ( ! $db_link->prepare($SQL)->execute( array($exec, "Заказано у поставщика: ".date("d.m.Y H:i:s", time())." <br/> ID : {$action_result[0]['id']}", $new_sao_state, $order_item_id) ) )
	{	
		$error = "Позиция заказана, но произошёл сбой при обновлении sao-статуса!";
		throw new Exception($error);	
	}
	
	$sao_result["status"] = true;
	
} catch (Exception $e) {
	
	echo  "\n" . $e->getMessage() . "\n";
	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
	
}

$bufer = ob_get_contents();
ob_end_clean();

$f = fopen($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/moskvorechie/sao_do_order.log", 'w');
fwrite($f, $bufer."\n");
fclose($f);
?>