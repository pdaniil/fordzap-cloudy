<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class mparts_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//----------------Учётные данные для API поставщика---------------//
		$login 				= $storage_options["login"];
		$password			= md5($storage_options["password"]);
		$useOnlineStocks	= $storage_options["useOnlineStocks"];

		
		
		//-------------------------По массиву производителей------------------//
		foreach ($manufacturers as $m_data) {
			
			$brand_name = urlencode($m_data["manufacturer"]);
			
			//---------------Формируем запрос к поставщику-----------------//
			
			$params_query = http_build_query( array( 'userlogin' => $login, 
										'userpsw' => $password, 
										'number'  => $article, 
										'brand' => $brand_name,
										'useOnlineStocks' => $useOnlineStocks ) );
			
			$url = "http://v01.ru/api/devinsight/search/articles/?" . $params_query;
			
			//--------------------- Запрос в API Поставщика---------------//
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$execute = curl_exec($ch);
		
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$brand_name, $url, $execute, print_r(json_decode($execute, true), true) );
			}
			
			if(curl_errno($ch))
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
				}
			}
			
		
			curl_close($ch);
		
			$array_data = json_decode($execute, true);
			
			
			//------------------По данным массива ответа от поставщика---------------//
			foreach ($array_data as $good) {
				

				//-------------------Обработка полей--------------------------------//
				$price = $good["price"];
				
				//Наценка
				//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    $markup = $storage_options["markups"][(int)$price];
    		    if($markup == NULL) {
					
    		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
					
    		    }
				
				$price_for_customer = $price + $price * $markup;
				
				$time_to_exe = ((int)$good["deliveryPeriod"] / 24) + $storage_options["additional_time"];
				$time_to_exe_max = ((int)$good["deliveryPeriodMax"] / 24) + $storage_options["additional_time"];
				
				$probability = $storage_options["probability"];
				
				if ($good["deliveryProbability"] != ""
					|| $good["deliveryProbability"] != 0 
				) {
					
					$probability = $good["deliveryProbability"];
					
				}
				
				
				$json = [];
				$json["itemKey"] = $good["itemKey"];
				$json["code"] = $good["code"];
				$json["number"] = $good["number"];
				$json["brand"] = $good["brand"];
				$json["supplierCode"] = $good["supplierCode"];
				
				$json = json_encode( $json );
				
				//------------------Создание объекта-------------------------------//
				$DocpartProduct = new DocpartProduct(
					$good["brand"],
					$good["number"],
					$good["description"],
					(int)$good["availability"],
					$price_for_customer,
					$time_to_exe,
					$time_to_exe_max,
					$good["supplierCode"],
					$good["packing"],
					$probability,
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,
					0,
					0,
					'',
					$json,
					array(
						"rate"=>$storage_options["rate"]
					)
				);
				
				if($DocpartProduct->valid) {
					
					array_push($this->Products, $DocpartProduct);
					
				}			
								
			} // ~ foreach ($array_data as $good)
			
			
		} // ~foreach ($manufacturers as $m_data)
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
		
	}
}
/*
$f = fopen('log_sup.txt','w');
fwrite($f, $_POST["storage_options"]."\n");
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["manufacturers"]."\n");
fclose($f);

$_POST["storage_options"] = '{"login":"s.klenin@yarkamp.ru","password":"MzD7akaWLw2yK9q","useOnlineStocks":"1","color":"#f8903a","probability":"","paymentMethod":"6","shipmentMethod":"23","shipmentAddress":"c5785129-46b4-11ec-a20f-0050568fc16a","markups":[0],"office_id":"1","storage_id":"6","additional_time":2,"office_caption":"\u041e\u0441\u043d\u043e\u0432\u043d\u0430\u044f \u0442\u043e\u0447\u043a\u0430 \u043e\u0431\u0441\u043b\u0443\u0436\u0438\u0432\u0430\u043d\u0438\u044f","storage_caption":"\u041c \u041f\u0430\u0440\u0442\u0441","rate":"1","group_id":"3"}';
$_POST["article"] = 'C110';
$_POST["manufacturers"] = '[{"manufacturer":"DOLZ","manufacturer_id":0,"manufacturer_show":"DOLZ","name":"\u041d\u0430\u0441\u043e\u0441 \u0432\u043e\u0434\u044f\u043d\u043e\u0439 Citroen ZX\/Xsara\/AX 1.0-1.4\/1.4D 87-","storage_id":"6","office_id":"1","synonyms_single_query":true,"params":[],"valid":true}]';
*/
//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob =  new mparts_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit( json_encode($ob) );
?>