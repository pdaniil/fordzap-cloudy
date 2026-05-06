<?php
// error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
//ini_set('display_errors', 1);

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/realparts/helper.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class realparts_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		$token_data = getToken($login, $password);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение токена по логину и паролю ", '', $token_data, print_r(json_decode($token_data, true), true) );
		}
		
		$token_data_json = json_decode($token_data, true);
		$token = $token_data_json["data"]["token"];
		
		$execute = getManufacturers($article, $token);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, '', $execute, print_r(json_decode($execute, true), true) );
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
		
		$decode = json_decode($execute, true);
		$data = $decode["data"];
		
		foreach($data as $manufacturer)
		{
			$DocpartManufacturer = new DocpartManufacturer($manufacturer["manufacturer"]["name"],
				$manufacturer["manufacturer"]["id"],
				$manufacturer["name"]["name"],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true,
				null
			);
			
			
			array_push($this->ProductsManufacturers, $DocpartManufacturer);
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
		
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

$ob = new realparts_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>