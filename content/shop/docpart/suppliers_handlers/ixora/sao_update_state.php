<?php

ob_start();

//Структура результата 
$sao_result = array();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/ixora/update_state.log";

//*************************************************************************

$state_object = json_decode($order_item["sao_state_object"], true);

$client = new SoapClient("http://ws.ixora-auto.ru/soap/ApiService.asmx?wsdl", array('soap_version'   => SOAP_1_2));	

$AuthCode = $connection_options["authcode"];

$checkResult = $client->OrderStatusGetByOrderId(array("AuthCode" => $AuthCode, "OrderId" => $state_object["order_id"]));

echo "<pre>";
echo date('Y-m-d H:i:s');
echo "</pre>";
echo "</br>";
echo "<pre>";
print_r($checkResult);
echo "</pre>";
echo "</br>";

/*
           [Data] => stdClass Object
                (
                    [OrderStatus] => stdClass Object
                        (
                            [Id] => 2dcf811b-d895-4812-8fa6-ede1b4f6bec9
                            [Date] => 2022-08-23T14:23:00+03:00
                            [Number] => PODGORB-54
                            [DateArrivalOrient] => 2022-08-27T21:00:00+03:00
                            [DateArrivalWarranty] => 2022-08-27T21:00:00+03:00
                            [Region] => МОСКВА СКЛАД - 585
                            [Reference] => 
                            [Comment] => 
                            [Storage] => 
                            [Status] => Ordered
                            [Items] => stdClass Object
                                (
                                    [OrderItem] => stdClass Object
                                        (
                                            [DetailNumber] => TRK0557
                                            [DetailMaker] => TORK
                                            [DetailName] => ЛЮЧОК БЕНЗОБАКА RENAULT CLIO II
                                            [Ordered] => 2
                                            [Purchased] => 0
                                            [OnTheWay] => 0
                                            [HasCome] => 0
                                            [Issued] => 0
                                            [Price] => 654.53
                                            [IsReplacement] => 
                                        )

                                )

                            [HasReplacement] => 
                            [ReorderSourceId] => 
                            [ReorderResult] => None
                            [IsComplete] => 
                            [ReturnPeriod] => 14
                            [ReturnPeriodExpired] => 
                            [ReturnConditions] => Гарантированный возврат с сохранением товарного вида в установленный срок (14 дней)
                        )

                )
*/


$sao_info = "";
try
{	
    
    $orderStatus = $checkResult->OrderStatusGetResult->Data->OrderStatus->Status;
    
	$supplier_statuses = array(
	    'All' => "Все",
        'InWork' => "Все заказы, за исключением имеющих статус Выдано и Нет в наличии",
        'InOrder' => "В заказе",
        'Ordered' => "Заказано",
        'Purchased' => "Выкуплено",
        'OnTheWay' => "В пути",
        'ToIssue' => "К выдаче",
        'Issued' => "Выдано ",
        'NotAvailable' => "Нет в наличии",
        'Reserve' => "Резерв на складе",
        'Acceptance' => "Приемка",
        'Moving' => "Перемещение"
    );
    
    $status_message = $orderStatus;
    if(isset($supplier_statuses[$orderStatus])) {
        
        $status_message = $supplier_statuses[$orderStatus];
    }
    
    $current_date = date( "d-m-Y H:i:s" );
    
    $sao_message = "Состояние: {$status_message} <br /> Обновлено: {$current_date}";
    
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
		$sao_result["status"] = true;
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
file_put_contents($pathError, $dump);
ob_end_clean();
?>