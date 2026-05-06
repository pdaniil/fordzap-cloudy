<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class forcs_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$token = $storage_options["token"];
		/*****Учетные данные*****/
		$curl = curl_init();
		
		$url = 'https://forcs.ru/api/v2/ordering/get_stock?items[0][resource_article]=' . $article . "&analogs=1";
		
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
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
		
		$search_result = json_decode($response, true);
		
		if (count($search_result["warnings"]) > 0)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				$DocpartSuppliersAPI_Debug->log_error("Предупреждение от поставщика", print_r($search_result["warnings"][0]["text"], true) );
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $url, "См. ответ API после обработки", print_r(json_decode($response, true), true) );
		}
		
		foreach ($search_result["resources"] as $offer)
		{
			$name = $offer["name"];
			$article = $offer["article"];
			$brand = $offer["brand"]["name"];
			
			foreach ($offer["offers"] as $product)
			{
				$price = (int)$product["price"];
				$exist = (int)$product["quantity"];
				$time_to_exe = (int)$product["average_period"];
				$time_to_exe_guar = (int)$product["assured_period"];
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($brand,
					$article,
					$name,
					$exist,
					$price + $price*$markup,
					$time_to_exe + $storage_options["additional_time"],
					$time_to_exe_guar + $storage_options["additional_time"],
					NULL,
					$product["multiplication_factor"],
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,0,0,'',null,array("rate"=>$storage_options["rate"])
					);
				
				if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
				}
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}

		$this->result = 1;
	}//~function __construct($article)
};
//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new forcs_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>