<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class drive_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
        $this->result = 0;//По умолчанию
        
        $api_key 	   = $storage_options["api_key"];	// Учетные данные
		$show_analog   = $storage_options["show_analog"];	// Адрес для запроса
		
        // инициализация сеанса
        $curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://drive.co.ua/api/search/',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => array('api_key' => $api_key, 'article' => $article, 'show_analog' => $show_analog),
		));

		$response = curl_exec($curl);
		
		curl_close($curl);

        //ЛОГ [ПОСЛЕ API-запроса] (название запроса, ответ, обработанный ответ)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_after_api_request("Ответ от поставщика", $response, print_r( json_decode($response, true), true ) );
        }
        
        $result_array = json_decode($response, true);

        if($result_array['info']["status"] == 1)
		{   
            foreach($result_array["data"] as $product){

                $price = $product["price"];
                
                $markup = $storage_options["markups"][(int)$price];
                if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                {
                    $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                }
                                    
                $exists = 0;
                $time_to_exe - 0;

				$DocpartProduct = new DocpartProduct($product["brand"],
					$product["article"],
					$product["sub_group_name"],
					$product["amount"],
					$price + $price * $markup,
					$storage_options["additional_time"],
					$storage_options["additional_time"],
					0,
					0,
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,
					0,
					0,
					'',
					NULL,
					array("rate"=>$storage_options["rate"])
				);
		
				if($DocpartProduct->valid)
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

    }
}

$storage_options = json_decode($_POST["storage_options"], true);

//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

$ob = new drive_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>