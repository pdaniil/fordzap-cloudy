<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class lauto_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	
	public function __construct($article, $manufacturers, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
	
	
		$login = $storage_options['login'];
		$password = $storage_options['password'];
		
		
		
		$supplier_items = array();
		
		$base64_str = base64_encode($login . ":" . $password);
		
		$url_request = "https://www.l-auto.by/core/api/Search/Products/VendorCode?vendorCode={$article}&startsWith=false";
		
		$headers = array(
			"Authorization: Basic {$base64_str}"
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url_request);
		
		$exec = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение ID товаров по артикулу ".$article, $url_request."<br>Авторизация через заголовок: base64_encode(".$login.":".$password.")", $exec, print_r(json_decode($exec, true), true) );
		}
		
		
		
		if ( ! $exec) {
			
			return false;
			
		}
		
		$search_result = json_decode($exec, true);
		
		
		foreach ($search_result as $product) 
		{
			/*
			echo "<pre>";
			print_r($product);
			echo "</pre>";
			*/
			$url = 'http://www.l-auto.by/core/api/Offers/ProductKey';
			
						
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'http://www.l-auto.by/core/api/Offers/ProductKey',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>'[{
				"vendorCode": "'.$product["vendorCode"].'",
				"vendorName": "'.$product["vendorName"].'"
			}]',
			  CURLOPT_HTTPHEADER => array(
				'accept: text/plain',
				'Content-Type: application/json-patch+json',
				"Authorization: Basic {$base64_str}",
			  ),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по ID товара: ".$product["vendorCode"], $url."<br>CURL-сессия продолжается", $response, print_r(json_decode($response, true), true) );
			}
			
			
			$supplier_items = json_decode($response, true);
			
			foreach ($supplier_items as $item) {
				/*	
				echo "<pre>";
				print_r($item);
				echo "</pre>";
			*/
				$price_purchase = $item['price'];
				
				 //Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				 $markup = $storage_options["markups"][(int)$price_purchase];
				 
				if($markup == NULL) {
					
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					
				}
				
				$price_for_customer 		= $price_purchase + $price_purchase * $markup;
				
				$manufacturer 				= $item['product']['vendorName'];
				$article 					= $item['product']['vendorPurifiedCode'];
				$name 						= $item['product']['name'];
				$exist 						= (int)$item['quantity'];
				$time_to_exe  				= $item['repositoryDeliveryTime']['sourceStockDeliveryTime']['deliveryTime'] + $storage_options['additional_time'];
				$time_to_exe_guaranteed 	= $item['repositoryDeliveryTime']['sourceStockDeliveryTime']['deliveryTime'] + $storage_options['additional_time'];
				$storage 					= 0;
				$min_order 				= $item['quantityMultiplicity'];
				$probability 				= $storage_options['probability'];
				$product_type 				= 2;
				$product_id 				= 0;
				$storage_record_id		= 0;
				$url 						= "";
				$json_params 				= '';
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
			
			}//~foreach ($supplier_items as $item)
			
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
/*
$f = fopen('log_sup.txt','w');
fwrite($f, $_POST["storage_options"]."\n");
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["manufacturers"]."\n");
fclose($f);

$_POST["storage_options"] = '{"login":"000005410","password":"0d0540e817","probability":"95","color":"#792020","markups":[0],"office_id":"1","storage_id":"11","additional_time":"0","office_caption":"\u0430\u0432\u0442\u043e\u043c\u0438\u0440.\u0431\u0435\u043b","storage_caption":"","rate":"1","group_id":"2"}';
$_POST["article"] = 'C110';
$_POST["manufacturers"] = '';
*/

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();




$ob =  new lauto_enclosure($_POST["article"], 
	json_decode($_POST["manufacturers"], true), 
	$storage_options
);
exit( json_encode($ob) );
?>