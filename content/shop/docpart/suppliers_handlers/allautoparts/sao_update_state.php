<?php

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);


require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/allautoparts/soap_transport.php");
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

// --------------------------------------------------------------------------------------
//1. Авторизуемся

/*****Учетные данные*****/
$login = $connection_options["login"];
$passwd = $connection_options["password"];
$session_id = $connection_options["session_id"];
/*****Учетные данные*****/

// --------------------------------------------------------------------------------------
//2. Отправляем запрос поставщику

//Специальные параметры
$sao_state_object = json_decode($order_item["sao_state_object"], true);


$data['session_id'] = $session_id;
$data['session_guid']='';
$data['session_login']=$login;
$data['session_password']=$passwd;
$data['orderNum'] = isset($sao_state_object['Order']['@attributes']['number']) ? $sao_state_object['Order']['@attributes']['number'] : null;

if(empty($data['orderNum'])) {
    
    $sao_result["status"] = false;
	$sao_result["message"] = 'Отсутствует номер заказа в базе данных';
	
} else {
    
    $SOAP=new soap_transport("https://services.allautoparts.ru/WebService2/OrderService.svc/wsdl?wsdl");
    $requestXMLstring = createRequestXML($data);
    $errors=array();
    $Response_result_data = $SOAP->query('OrdersInWork', array('ParametersXml' => $requestXMLstring), $errors);
    
    $xml_result = xml2array($Response_result_data);
    
    $log = fopen($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/allautoparts/log.txt", "w");
    fwrite($log, json_encode($xml_result, true) );
    fclose($log);
    
    $xml_result_state = isset($xml_result['order']['orderIn']['details']['detail']['states']['state']['@attributes']) ? $xml_result['order']['orderIn']['details']['detail']['states']['state']['@attributes'] : null;
    
    if(!is_array($xml_result_state) || empty($xml_result_state))
    {
    	$sao_result["status"] = false;
    	$sao_result["message"] = "Техническая неполадка. Ошибка в структуре ответа от поставщика.";
    	
    	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
    	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
    	$update_query->bindValue(':sao_message', 'Ошибка создания заказа. Техническая неполадка. Ошибка в структуре ответа от поставщика: '.date("d.m.Y H:i:s", time()), $order_item_id);
    	$update_query->execute();
    }
    else
    {
        
	    $supplier_statuses = array(
    	    0 => "В корзине",
            30 => "В работе",
            50 => "Выкупается",
            60 => "Ждем на ЦС",
            70 => "Пришло на ЦС",
            80 => "Отгружено клиенту",
            90 => "Получено клиентом",
            99 => "Исключительная ситуация"
        );
            
		$docpartState = 2;

		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
		$new_status_query->bindValue(':id', $docpartState);
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
			$error = curl_error($ch);
			curl_close($ch);
		}
		
		$date = date("d-m-Y H:i:s");
		$sao_message = "ID состояния : {$xml_result_state['stateId']}<br />Состояние: {$xml_result_state['hint']} <br /> Комментарий: {$xml_result_state['note']}<br /> Обновлено: {$date}";
		
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
}




// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
//ОПРЕДЕЛЕНИЯ ФУНКЦИЙ
function createRequestXML($data) 
{
	
	$session_info = $data['session_guid'] ? 
		'SessionGUID="'.$data['session_guid'].'"' : 
		'UserLogin="'.base64_encode($data['session_login']).'" UserPass="'.base64_encode($data['session_password']).'"';
	
	$xml = '<root>
			  <SessionInfo ParentID="'.$data['session_id'].'" '.$session_info.'/>
                <parameters>
                    <states>
                    </states>
                    <orderNums>
                        <orderNum>'.$data['orderNum'].'</orderNum>
                    </orderNums>
                    <stocksonly>0</stocksonly>
                    <searchArticles>
                    </searchArticles>
                    <page>
                        <pageNumber>1</pageNumber>
                        <pageLength>100</pageLength>
                    </page>
                </parameters>
			</root>';
	return $xml;

}
// --------------------------------------------------------------------------------------

function xml2array($xmlObject, $out = array())
{
    foreach ((array) $xmlObject as $index => $node)
        $out[$index] = (is_object($node)) ? xml2array($node) : $node;

    return $out;
}
?>