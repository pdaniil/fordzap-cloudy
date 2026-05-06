<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class agdl_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	
	public function __construct($article,  $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
	
		$uid = $storage_options['uid'];
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://agdl.ru/jsonCross.py?pn='.$article.'&uid='.$uid,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		));

		$response = curl_exec($curl);
		$products = json_decode($response)->goods;
		foreach( $products as $product ) {
			$newArticle = preg_replace('/[^ a-zа-яё\d]/ui', '',$product->A );
			
			$price = (int)$product->P;
			
			//Наценка
			//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			$markup = $storage_options["markups"][(int)$price];
			
			if($markup == NULL) {
				
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				
			}
			
			$price_for_customer = $price + $price * $markup;
			
			
			$time_to_exe = 0;
			if (!is_null($product->S));
				$time_to_exe = $product->S;
			
			//Время доставки в днях
			$time_to_exe += $storage_options["additional_time"];
			
			if (!is_null($product->F))
				$min_order = $product->F;
			
			else
				$min_order = 1;
			
			$DocpartProduct = new DocpartProduct($product->M, 
				$newArticle,
				$product->T,
				$product->K,
				$price_for_customer,
				$time_to_exe,
				$time_to_exe,
				$storage,
				$min_order,
				$storage_options["probability"],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				$storage_options["office_caption"],
				$storage_options["color"],
				$storage_options["storage_caption"],
				$price,
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
			
		}
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $url_request."<br>Авторизация через UID: ".$uid, $response, print_r(json_decode($response, true), true) );
		}
		
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список список остатков", print_r($this->Products, true) );
		}
		
		
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




$ob =  new agdl_enclosure($_POST["article"], 	$storage_options);
exit( json_encode($ob) );
?>