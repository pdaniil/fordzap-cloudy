<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class shate_m_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$login = $storage_options["login"];
		$password = $storage_options["password"];
    $api_key = $storage_options["api_key"];
		/*****Учетные данные*****/
        
		// -------------------------------------------------------------------------------------------------
		
		$api_path = "";
		switch($storage_options["country"])
		{
			case "ru":
				$api_path = "https://api.shate-m.ru";
				break;
			case "by":
				$api_path = "https://api.shate-m.by";
				break;
			case "kz":
				$api_path = "https://api.shate-m.kz";
				break;
			default: $api_path = "https://api.shate-m.ru";
		}
		
		if(!empty($login) && !empty($password)) {
			$url = $api_path . '/api/v1/auth/login';
			$auth_fields = array(
				'Login' => $login,
				'Password' => $password
			);
		} else {
			$url = $api_path . '/api/v1/auth/loginbyapikey';
			$auth_fields = array(
				'ApiKey' => $api_key
			);
		}

		$auth_field_string = http_build_query($auth_fields);
		
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $auth_field_string);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		
		$result = curl_exec( $ch );

		$curl_result = json_decode($result, true);

		$token = "";

		if(!empty($curl_result['access_token'])) {
			$token = $curl_result['access_token'];
		} else {
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка получения Token 1 шаг", print_r($curl_result, true) );
			}
		}
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос на авторизацию 1 шаг", $url."<br>", $result, "Полученный токен: " . $token);
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка 1 шаг", print_r(curl_error($ch), true) );
			}
		}

		curl_close($ch);
		
		// Запрашиваем товары по артикулу.
		$url_req = $api_path . "/api/v1/articles/search?searchString={$article}";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_req); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 0);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));

		$result = curl_exec($ch); 
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url_req."<br>Метод GET", $result, print_r(json_decode($result, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
		$manufacturers = json_decode($result, true);
		curl_close($ch);

		if(!empty($manufacturers)) {
			//По каждому производителю
			for($i = 0; $i < count($manufacturers); $i++)
			{

				$article_info = $manufacturers[$i]['article'];
				if(!empty($article_info)) {

					$DocpartManufacturer = new DocpartManufacturer($article_info['tradeMarkName'],
						0,
						$article_info['code'],
						$storage_options["office_id"],
						$storage_options["storage_id"],
						true
					);
					
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
				}
			}
		}

		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
	}//~function __construct($article)
};//~class shate_m_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

$ob = new shate_m_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>