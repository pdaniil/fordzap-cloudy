<?php
// error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/realparts/helper.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class realparts_enclosure
{
	public $result = 0; 
	public $Products = array();
	public $order_params = array();
	
	public function __construct($article, $manufacturers, $storage_options)
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
		
		foreach ( $manufacturers as $m ) {
			
			$manufacturer_id = $m["manufacturer_id"];
			
			
			$execute = getProducts($manufacturer_id, $article, 'true', $token);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer_id, '', $execute, print_r(json_decode($execute, true), true) );
			}
			
			$decode = json_decode( $execute, true );
			$supplier_data = $decode["data"];
			
			$offers = $supplier_data["offers"];
			$products = $supplier_data["products"];
			$manufacturer_codes = $supplier_data["manufacturer_codes"];
			
			//Обрабатываем информацию по позиции
			foreach($offers as $offer)
			{
				$product_info = $products[$offer["product_id"]];
				$manuf_info = $manufacturer_codes[$product_info["manufacturer_code"]];
				
				$art = $manuf_info["code"];
				$brand = $manuf_info["manufacturer"]["name"];
				
				$price = $offer["price"];
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				
				$DocpartProduct = new DocpartProduct($brand,
					$art,
					$product_info["name"]["name"],
					$offer["quantity"],
					$price + $price * $markup,
					$storage_options["additional_time"],
					$storage_options["additional_time"],
					0,
					1,
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2, //product_type
					0, //product_id
					0, //storage_record_id
					"", //url
					"", //json_params
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


try 
{
	$ob =  new realparts_enclosure( $_POST["article"], json_decode( $_POST["manufacturers"], true ), $storage_options );
}
catch( Exception $e ) 
{	
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	}
}
exit(json_encode($ob));
?>