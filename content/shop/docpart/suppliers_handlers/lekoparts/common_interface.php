<?php
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class lekoparts_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$user = $storage_options['user'];
		$pass = $storage_options['pass'];
		/*****Учетные данные*****/
       
        $curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'http://lekoparts.ru:35861/SearchByNumber.do?user='. $user .'&pass='. $pass .'&number='. $article,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
		  ),
		));

		$response = curl_exec($curl);
		
		if(curl_errno($curl))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($curl), true) );
			}
		}
		
        curl_close($curl);
        
        $curl_result = json_decode($response, true);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, 'http://lekoparts.ru:35861/SearchByNumber.do?user='. $user .'&pass='. $pass .'&number='. $article, $response, print_r(json_decode($response, true), true) );
		}
		
		foreach ($curl_result as $result)
			foreach ($result['result'] as $arr_prices) 
				foreach ($arr_prices as $product)
				{
					$price = (float)$product["price"];
					$exist = (int)str_replace("> ", "", $product["quantity"]);
					$time_to_exe = str_replace("заказ ", "", $product["status"]);
					$time_to_exe = (int) str_replace(" дн.", "", $time_to_exe);
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
				
					//Создаем объек товара и добавляем его в список:
					$DocpartProduct = new DocpartProduct($product["make"],
						$product["number"],
						$product["name"],
						$exist,
						$price + $price*$markup,
						$time_to_exe + $storage_options["additional_time"],
						$time_to_exe + $storage_options["additional_time"],
						NULL,
						1,
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
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
        
        $this->result = 1;
        
	}//~function __construct($article)
};//~class lekoparts_enclosure

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new lekoparts_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>