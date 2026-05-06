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

$sao_item["OrderNumber"]	= $addParams["OrderNumber"];

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


	// Кладём позицию в корзину
	$insertResult = $soap_client->GetFullInvoiceOrder($sao_item);

    echo "<pre>";
    echo "Результат";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($insertResult);
    echo "</pre>";
    echo "</br>";

    $supplier_status = array(
        11 => "Выдано",
        9 => "Отказ",
        149 => "Возврат невозможен",
        128 => "Зачтено клиенту", // Данный статус проставляется, после успешного возврата товара
        1858 => "Выдан получателю", // Данный статус проставляется, когда клиент получил в СДЭКе товар
        1859 => "Возврат в Autopiter.ru", // Данный статус проставляется, когда клиент не забрал товар из СДЭК
    );

    $item_result = isset($insertResult->GetFullInvoiceOrderResult->OrderInformationItemModel) ? $insertResult->GetFullInvoiceOrderResult->OrderInformationItemModel : null;

	if(!empty($item_result))
	{
		$status	= $item_result->Status->Name; //Получаем созданную позицию

		$date 		= date("d-m-Y H:i:s");
		
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
        $update_query->bindValue(':sao_message', 'Статус '.$status.'<br/>'.$date);
		$update_query->bindValue(':id', $order_item["id"]);

        if( !$update_query->execute() ) {
            $message = "Статус получен, но произошла ошибка обновления записи";
            throw new Exception( $message );
        }
		
		$sao_result["status"] = true;
	}
	else
	{
        $message = "Сервис временно не доступен!";
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

file_put_contents( $supplier_dir . "/dump_update_state.log", ob_get_clean(),  FILE_APPEND);

?>