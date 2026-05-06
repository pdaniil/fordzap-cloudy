<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class avtoportal_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturer, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		$brand_search = $manufacturer[0]["manufacturer"];
				
		$url = "https://avtoportal.pro/parts/search/" . $article ."/" . $brand_search;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode($login.":" .$password) ) );
		$response = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка остатков по артикулу ".$article, $url, $response, print_r(json_decode($response, true), true) );
		}
		
	
		if(curl_errno($ch)) 
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "При запросе остатков через CURL возникла ошибка.<br>".print_r(curl_error($ch), true) );
			}
			
			curl_close($ch);
			return;
		}
		
		curl_close($ch);
		
		$decode = json_decode($response, true);
		
		$info_parts = $decode["parts"];
		
		foreach($info_parts as $part)
		{
			$price = $part["price"];
			
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			$price_for_custumer = $price + $price*$markup;
			
			$time_to_exe = $part["deliverytime"]["min"] + $storage_options["additional_time"];
			$time_to_exe_guaranteed =  $part["deliverytime"]["max"] + $time_to_exe + $storage_options["additional_time"];
			
			
			$DocpartProduct = new DocpartProduct($part["brand"],
				$part["article"],
				$part["name"],
				$part["stock"],
				$price_for_custumer,
				$time_to_exe,
				$time_to_exe_guaranteed,
				0,
				$part["multiplicity"],
				$storage_options["probability"],
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
				NULL,
				array("rate"=>$storage_options["rate"])
			);
	
			if($DocpartProduct->valid)
			{
				array_push($this->Products, $DocpartProduct);
			}				
		}
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob =  new avtoportal_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit( json_encode($ob) );
?>