<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class x_autobody_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		$api_key = $storage_options["api_key"];
		
		
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'http://www.autobody.ru/api/getProducts/',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => 'token='. $api_key .'&item=' . $article,
		));

		$response = curl_exec($curl);
		
		$result = json_decode($response , true);
		
		
		
		curl_close($curl);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "с параметрами ".print_r(array( 'api_key' => $api_key, 'article' => $article ), true), "См. ответ API после обработки", print_r(json_decode($response, true), true) );
		}
		

		foreach ($result as $info)
			if (!empty($info["amount"]))
				foreach ($info["amount"] as $key => $product)
				{	
					$price = $info["prices"][$key + 1]['price'];
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					$DocpartProduct = new DocpartProduct($info["properties"]["firm"],
						$info["properties"]["oem"],
						$info["name"],
						$product["quantity"],
						$price + $price*$markup,
						$storage_options["additional_time"],
						$storage_options["additional_time"],
						"",
						1,
						$storage_options["probability"],
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$storage_options["office_caption"],
						$storage_options["color"],
						$storage_options["storage_caption"],
						$price,
						$markup,
						2,0,0,'',"",array("rate"=>$storage_options["rate"])
						);
					
					if($DocpartProduct->valid == true)
					{
						array_push($this->Products, $DocpartProduct);
					}
				}
		
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
        
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



$ob = new x_autobody_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>