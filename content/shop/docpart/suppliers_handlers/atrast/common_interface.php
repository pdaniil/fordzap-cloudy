<?php

ini_set('precision', 10);
ini_set('serialize_precision', 10);

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class atrast_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	
	public function __construct($article, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
	
	
		$login = $storage_options['user'];
		$password = $storage_options['password'];
		
		$curl = curl_init();
		$url_request = 'https://ws.a-trast.ru/v1/auth/login';
		
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url_request,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => array('password' => $password ,'username' => $login),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$reset = json_decode($response);
		$token = $reset->token;
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение токена по логину: ".$login." и паролю: ".$password, $url_request."<br>Авторизация через заголовок: bearer ".$token, $response, print_r(json_decode($response, true), true) );
		}
		
		$curl = curl_init();
		$url_request = 'https://ws.a-trast.ru/v1/search/products?query_search_mode=1&expand=analogs,partner_stocks&article='.$article.'&sort=title&page_limit=500';
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url_request,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer ' . $token
		  ),
		));
			
		$response = curl_exec($curl);

		curl_close($curl);
		$result = json_decode($response);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков товара: ".$article, $url_request."<br>CURL-сессия продолжается", $response, print_r(json_decode($response, true), true) );
		}
	
		
		foreach ($result->products as $product) 
		{
					
			$price_purchase = $product->price;
		
			 //Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			 $markup = $storage_options["markups"][(int)$price_purchase];
			 
			if($markup == NULL) {
				
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				
			}
			
			$price_for_customer 		= $price_purchase + $price_purchase * $markup;
			
			$manufacturer 				= $product->brand;
			$article 					= $product->article;
			$name 						= $product->title;
			$exist 					= $product->count;
			$time_to_exe  				= ($product->warehouse_delivery_time / 24) + $storage_options["additional_time"];
			$time_to_exe_guaranteed 	= $product->warehouse_delivery_time / 24 + $storage_options["additional_time"];
			$storage 					= 0;
			$min_order 				= $product->quantity_in_pack;
			$probability 				= $product->fail_percent;
			$product_type 				= 2;
			$product_id 				= 0;
			$storage_record_id		= 0;
			$url 						= "";
			$json_params 				= json_encode(array("product_id" => $product->id, "supplier_id" => $product->supplier_id, "product_code" => $product->product_code, "product_price" => $price_purchase));
			$rest_params  				= array("rate"=>$storage_options["rate"]);
			
			$DocpartProduct = new DocpartProduct($manufacturer, 
				$article,
				$name,
				$exist,
				$price_for_customer,
				$time_to_exe,
				$time_to_exe_guaranteed,
				$storage,
				$min_order,
				$probability,
				$storage_options["office_id"],
				$storage_options["storage_id"],
				$storage_options["office_caption"],
				$storage_options["color"],
				$storage_options["storage_caption"],
				$price_purchase,
				$markup,
				$product_type,
				$product_id,
				$storage_record_id,
				$url,
				$json_params,
				$rest_params
			);
			
			if($DocpartProduct->valid) {
				
				array_push($this->Products, $DocpartProduct);
				
			}
		}//~foreach ($search_result as $product)
		
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		curl_close($ch);
		
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




$ob =  new atrast_enclosure($_POST["article"],
	$storage_options
);
exit( json_encode($ob) );
?>