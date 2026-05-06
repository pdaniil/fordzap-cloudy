<?php
header('Content-Type: application/json;charset=utf-8;');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class euroauto_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public $storage_options;//Для использования в других методах
	
	public function __construct($article, $manufacturers, $storage_options)
	{
	    $this->result = 0;//По умолчанию
		
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		/*****Учетные данные*****/
        $storage_id = $storage_options["storage_id"];
        $login = trim($storage_options["login"]);
        $password = trim($storage_options["password"]);
        $token = trim($storage_options["token"]);
        $expires = trim($storage_options["expires"]);
        $view_sh = trim($storage_options["view_sh"]);
		/*****Учетные данные*****/
		
		foreach ($manufacturers as $item_manufacturer) 
		{
			$manufacturer = $item_manufacturer['manufacturer'];
			$manufacturer_id = $item_manufacturer['manufacturer_id'];
			
			//Получение товаров в наличии по артикулу производителя {new}
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, "https://whls.euroauto.ru/api/offers/products/manufacturers/".$manufacturer_id."/new?code=".$article."&replacements=true&delivery_id[]=1-100-0");
			curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$token, "Accept: application/json"));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, false);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20); 
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			$curl_result = curl_exec($curl);
			curl_close($curl);
			
			$products_all = json_decode($curl_result, true);
			
			//echo '<pre>';
			//var_dump($products_all);
			//echo '</pre>';
			//echo '<hr/>';
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение товаров в наличии по артикулу производителя {new} - ".$article." - ".$manufacturer, "Вызов метода https://whls.euroauto.ru/api/offers/products/manufacturers/".$manufacturer_id."/new?code=".$article."&replacements=true&delivery_id[]=1-100-0", $curl_result, print_r($products_all, true) );
			}
			
			if(!empty($products_all['data']['offers'])){
				foreach($products_all['data']['offers'] as $item_offer){
					if(!empty($item_offer['product_id'])){
						
						$product_id = $item_offer['product_id'];
						$store_id = $item_offer['store_id'];
						$price = $item_offer['price'];
						$exist = $item_offer['quantity'];
						
						$product = $products_all['data']['products'][$product_id];
						
						$name = $product['name']['name'] .' -/- ID: '. $product_id;
						$manufacturer_code = $product['manufacturer_code'];
						
						$manufacturer_codes = $products_all['data']['manufacturer_codes'][$manufacturer_code];
						
						$product_article = $manufacturer_codes['code'];
						$product_manufacturer = $manufacturer_codes['manufacturer']['name'];
						
						$storage = $products_all['data']['stores'][$store_id]['name'];
						$min_order = 1;
						$markup = 0;
						$json = '';
						
						//Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct($product_manufacturer,
							$product_article,
							$name,
							$exist,
							$price,
							$storage_options["additional_time"],
							$storage_options["additional_time"],
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
							2,0,0,'',$json,array("rate"=>$storage_options["rate"])
							);
						
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
						}
						
					}
				}
			}
			
			///////////////////////////////////////////////////////////////////////////////////////////////
			if($view_sh){
				//Получение товаров в наличии по артикулу производителя {used} - товары бывшие в употреблении
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, "https://whls.euroauto.ru/api/offers/products/manufacturers/".$manufacturer_id."/used?code=".$article."&replacements=true&delivery_id[]=1-100-0");
				curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$token, "Accept: application/json"));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, false);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20); 
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				$curl_result = curl_exec($curl);
				curl_close($curl);
				
				$products_all = json_decode($curl_result, true);
				
				//echo '<pre>';
				//var_dump($products_all);
				//echo '</pre>';
				//echo '<hr/>';
				
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение товаров в наличии по артикулу производителя {used} - ".$article." - ".$manufacturer, "Вызов метода https://whls.euroauto.ru/api/offers/products/manufacturers/".$manufacturer_id."/used?code=".$article."&replacements=true&delivery_id[]=1-100-0", $curl_result, print_r($products_all, true) );
				}
				
				if(!empty($products_all['data']['offers'])){
					foreach($products_all['data']['offers'] as $item_offer){
						if(!empty($item_offer['product_id'])){
							
							$product_id = $item_offer['product_id'];
							$store_id = $item_offer['store_id'];
							$price = $item_offer['price'];
							$exist = $item_offer['quantity'];
							
							$product = $products_all['data']['products'][$product_id];
							
							$name = 'Б.У.';
							
							if(!empty($product['attributes']['wearout'])){
								$name .= ' -/- Износ: '. $product['attributes']['wearout'] .'%';
							}
							
							$name .= ' -/- '. $product['name']['name'] .' -/- '. $product['comment'] .' -/- ID: '. $product_id;
							
							$manufacturer_code = $product['manufacturer_code'];
							
							$manufacturer_codes = $products_all['data']['manufacturer_codes'][$manufacturer_code];
							
							$product_article = $manufacturer_codes['code'];
							$product_manufacturer = $manufacturer_codes['manufacturer']['name'];
							
							$storage = $products_all['data']['stores'][$store_id]['name'];
							$min_order = 1;
							$markup = 0;
							
							$images = $product['media']['images'];
							
							// --------------------------------------------------------------------------------------------------------------------------
							
							// Запрос всех картинок
							
							$offer_id = $item_offer['id'];
							
							$curl = curl_init();
							curl_setopt($curl, CURLOPT_URL, "https://whls.euroauto.ru/api/offers/".$offer_id);
							curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$token, "Accept: application/json"));
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($curl, CURLOPT_POST, false);
							curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20); 
							curl_setopt($curl, CURLOPT_TIMEOUT, 20);
							curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
							curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
							$curl_result = curl_exec($curl);
							curl_close($curl);
							
							$products_all_info = json_decode($curl_result, true);
							if(!empty($products_all_info['data']['product']['media']['images'])){
								$images = $products_all_info['data']['product']['media']['images'];
							}
							
							// --------------------------------------------------------------------------------------------------------------------------
							
							$json = array('used' => '1', 'product_id' => $product_id, 'images' => $images);// - товары бывшие в употреблении
							$json = json_encode($json);
							
							//Создаем объек товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct($product_manufacturer,
								$product_article,
								$name,
								$exist,
								$price,
								$storage_options["additional_time"],
								$storage_options["additional_time"],
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
								2,0,0,'',$json,array("rate"=>$storage_options["rate"])
								);
							
							if($DocpartProduct->valid == true)
							{
								array_push($this->Products, $DocpartProduct);
							}
							
						}
					}
				}
			}
			///////////////////////////////////////////////////////////////////////////////////////////////
			
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class euroauto_enclosure


/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["manufacturers"]."\n");
fwrite($f, $_POST["storage_options"]."\n");
*/


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL") );


try 
{
	$ob = new euroauto_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
	$json = json_encode($ob);
	
	if ( ! $json ) 
	{
		throw new Exception("Ошибка преобразования DocpartProduct в json: " . json_last_error());
	}
} 
catch (Exception $e) 
{
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	} 
}


exit($json);
?>