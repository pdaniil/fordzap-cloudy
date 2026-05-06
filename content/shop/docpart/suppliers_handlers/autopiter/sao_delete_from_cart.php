<?php

ob_start();

$supplier_dir = $_SERVER['DOCUMENT_ROOT'] . '/content/shop/docpart/suppliers_handlers/autopiter_v2';

//Конфигурация
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Соединение с основной БД
$DP_Config = new DP_Config;//Конфигурация CMS

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

//Инициализируем переменные
$login			= $connection_options["user"];//Логин Emex
$password		= $connection_options["password"];//Пароль Emex
$addParams		= json_decode($order_item["sao_state_object"], true); //Данные для SAO

$sao_item = array();

$sao_item["DetailUid"]	= $addParams["DetailUid"];


$sao_result	= array();//Результат работы API
$sao_result["status"] = false;	

$sao_object = array(); //Тех параметры для SAO
$data = ""; //Время создания заказа

echo "<pre>";
echo date('Y-m-d H:i:s');
echo "</pre>";
echo "</br>";
echo "<pre>";
echo "Параметры";
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($sao_item);
echo "</pre>";
echo "</br>";

//******************************************************************************************************************************************************
try
{
	$wsdl_service	= "http://service.autopiter.ru/v2/price?WSDL"; //Адрес сервиса
	
	$soap_options = array(
		'soap_version' => SOAP_1_2,
        'encoding' => 'UTF-8',
		'exceptions' => true
	);
	
	$soap_client = new SoapClient( $wsdl_service, $soap_options );

    if(!$soap_client->IsAuthorization()->IsAuthorizationResult) 
	{
		$soap_client->Authorization(array(
            'UserID' => $login, 
            'Password' => $password, 
            'Save' => true 
        ));
	}

	// Удаляем позицию из корзины
	$deleteResult = $soap_client->DeleteItemCart($sao_item);

        echo "<pre>";
        echo "Результат";
        echo "</pre>";
        echo "</br>";
        echo "<pre>";
        print_r($deleteResult);
        echo "</pre>";
        echo "</br>";


	if($deleteResult == true)
	{

        $date 		= date("d-m-Y H:i:s");
		
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', 7);
		$update_query->bindValue(':sao_message', 'Удалена из корзины<br/>'.$date);
		$update_query->bindValue(':id', $order_item["id"]);

        if( !$update_query->execute() ) {
            $message = "Позиция удалена из корзины, но произошла ошбика обновления записи";
            throw new Exception( $message );
        }
		
		$sao_result["status"] = true;

		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_sao_state = 7;

		$new_status_query = $db_link->prepare("SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;");
		$new_status_query->execute( array($new_sao_state) );
		$new_status_record = $new_status_query->fetch();
		$new_status = $new_status_record["status_id"];
		
		//Отправляем запрос на изменение статуса позиции
		if ( $new_status > 0 ) {
						
			$orders_items_list = array( $order_item["id"] );
			$order_items_json = json_encode( $orders_items_list );
			
			$url_service = $DP_Config->domain_path;
			$action_service = "content/shop/protocol/set_order_item_status.php";
			
			$params_action = array(
				"initiator" => 2,
				"orders_items" => $order_items_json,
				"status" => $new_status,
				"key" => $DP_Config->tech_key
			);
			
			$url_request = $url_service . $action_service . "?" . http_build_query( $params_action );
			
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url_request );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
			
		}
	}
	else
	{
        $message = "Ошибка удаления позиции из корзины!";
        if(isset($supplier_code[$code])) {
            $message = $supplier_code[$code];
        }
		$sao_result["message"] = $message;
	}

} catch ( SoapFault $e ) {
	
	$sao_result["message"] = "Невозможно подключиться к сервису: " . $e->getMessage();
	
} catch ( Exception $e ) {
	
	$sao_result["message"] = $e->getMessage();
	
}

file_put_contents( $supplier_dir . "/dump_delete_from_cart.log", ob_get_clean() );

?>