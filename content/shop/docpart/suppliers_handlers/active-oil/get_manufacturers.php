<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class active_oil
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$token = $storage_options["token"];
		/*****Учетные данные*****/
				
		// -------------------------------------------------------------------------------------------------
		//Если нет токена, то получаем его
		//Получаем список сбытовых организаций клиента

	
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Токен есть.");
		}
		
		$request_data = '{ "token" : "'.$token.'" , "articul" : "'.$article.'" }';
		
		$curl = curl_init();
		
		$HEADERS = ['Content-Type:application/json', 'User-Agent:'. $_SERVER["SERVER_NAME"] .'/1.0', 'Connection:keep-alive', 'Accept-Encoding:gzip, deflate, br'];
		
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://activ-oil.ru/api/catalog/searchbyarticul',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>$request_data,
		  CURLOPT_HTTPHEADER => $HEADERS,
		));

		$response = curl_exec($curl);
			

		// -------------------------------------------------------------------------------------------------

		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "https://activ-oil.ru/api/catalog/searchbyarticul", $response, print_r(json_decode($response, true), true) );
		}
		
		if(curl_errno($curl))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($curl) );
			}
		}

		curl_close($curl);
		
		$result = json_decode($response);
	
		$manufacturers = $result->data->variants;
		
		if(!empty($manufacturers)) {
				//--------------По данным ответа---------------//
				foreach ($manufacturers as $manufacturer) {
					$DocpartManufacturer = new DocpartManufacturer(
						$manufacturer->brand,
						0,
						$manufacturer->name,
						$storage_options["office_id"],
						$storage_options["storage_id"],
						true,
						array()
					);
					
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
					
				}

		}

			//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
			}
			
			$this->status = 1;
		
	}
};//~class armtek_enclosure

/*
$f = fopen('log.txt','w'); 
fwrite($f, $_POST["storage_options"]."\n");
fwrite($f, $_POST["article"]."\n");
fclose($f);

$_POST["storage_options"] = '{"token":"VKHkjHGCdXDKOVtcXBvxe7nxmjCOjuZWIMtP58fscUrRvFu7FzTU3JDnPH66c0Vt","color":"#eb4747","probability":"95","office_id":"2","storage_id":"60"}';
$_POST["article"] = 'C110';
*/
//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new active_oil($_POST["article"], $storage_options);
exit(json_encode($ob));
?>