<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class armtek_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		$time_now = time();//Время сейчас
		
		/*****Учетные данные*****/
    $login = $storage_options["login"];
    $password = $storage_options["password"];
    $vkorg = !empty($storage_options["vkorg"]) ? $storage_options["vkorg"] : null;
    $kunrg = !empty($storage_options["kunrg"]) ? $storage_options["kunrg"] : null;
		/*****Учетные данные*****/
        
		//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
		if($vkorg === null && $DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Не выбрана сбытовая организация.");
		}

		//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
		if($kunrg === null && $DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Не выбран покупатель для сбытовой организации.");
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_search/assortment_search");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
		curl_setopt($ch, CURLOPT_POSTFIELDS, "format=json&STRUCTURE=1&VKORG=".$vkorg."&PIN=".$article);
		$execute = curl_exec($ch);
		curl_close($ch);

		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получаем KUNNR_RG и бренды по сбытовой организации VKORG ".$vkorg." и артикулу ".$article, "http://ws.armtek.ru/api/ws_search/assortment_search<br>Авторизация через HTTPHEADER Authorization: Basic base64_encode(".$login.":".$password.")<br>Метод: POST<br>Поля: "."format=json&STRUCTURE=1&VKORG=".$vkorg."&PIN=".$article, $execute, print_r(json_decode($execute, true), true) );
		}
		
		$curl_result = json_decode( $execute, true );
			
		for($k=0; $k < count($curl_result["RESP"]); $k++)
		{
			$DocpartManufacturer = new DocpartManufacturer($curl_result["RESP"][$k]["BRAND"],
					0,
				$curl_result["RESP"][$k]["NAME"],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true//Посылать только один запрос для одного синонима
			);
			
			array_push($this->ProductsManufacturers, $DocpartManufacturer);
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
        $this->status = 1;
	}//~function __construct($article)
};//~class armtek_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new armtek_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>