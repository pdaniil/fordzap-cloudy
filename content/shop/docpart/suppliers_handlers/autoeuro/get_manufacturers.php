<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class autoeuro_v2_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию

		
		/*****Учетные данные*****/
		$api_key = $storage_options["api_key"];
		/*****Учетные данные*****/



        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api2.autoeuro.ru/api/v2/json/search_brands/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('code' => $article),
            CURLOPT_HTTPHEADER => array(
                'key: '.$api_key
            ),
        ));

        $curl_result = curl_exec($curl);
			
		// -------------------------------------------------------------------------------------------------

		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "https://api2.autoeuro.ru/api/v2/json/search_brands/", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if(curl_errno($curl))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($curl) );
			}
		}

		curl_close($curl);

		$result = json_decode($curl_result);

		if(isset($result->DATA))
		{
			$manufacturers = $result->DATA;
			if (isset($manufacturers)) 
			{
				//--------------По данным ответа---------------//
				foreach ($manufacturers as $manufacturer) 
				{
					$DocpartManufacturer = new DocpartManufacturer(
						$manufacturer->brand,
						0,
						$manufacturer->name,
						$storage_options["office_id"],
						$storage_options["storage_id"],
						true,
						array()
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
	}
};//~class autoeuro_v2_enclosure





//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new autoeuro_v2_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>