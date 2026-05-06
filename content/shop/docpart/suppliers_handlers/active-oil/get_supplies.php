<?php

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class active_oil
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$token = $storage_options["token"];
		/*****Учетные данные*****/
			
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Токен есть.");
		}
		
      
		$HEADERS = ['Content-Type:application/json', 'User-Agent:'. $_SERVER["SERVER_NAME"] .'/1.0', 'Connection:keep-alive', 'Accept-Encoding:gzip, deflate, br'];
      
		foreach($manufacturers as $manufacturer) {

			$brand = preg_replace("/[^a-zа-яё\d]+/iu", '', $manufacturer["manufacturer"]);
			
			$request_data = '{ "token" : "'.$token.'" , "articul" : "'.$article.'", "brand" : "'.$brand.'" }';
			
			$curl = curl_init();
			
			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'https://activ-oil.ru/api/catalog/searchbyarticulandbrand',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>$request_data,
			  CURLOPT_HTTPHEADER => $HEADERS,
			));

			$response = curl_exec($curl);
			// -------------------------------------------------------------------------------------------------

			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "https://activ-oil.ru/api/catalog/searchbyarticulandbrand", $response, print_r(json_decode($response, true), true) );
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
          
			$products = json_decode($response);
			$products = $products->data->variants;
	
            if (isset($products) && !empty($products)) {

                //--------------По данным ответа---------------//
                foreach ($products as $product) {

                    $price = (int)$product->price;

                    //Наценка
                    $markup = $storage_options["markups"][(int)$price];
                    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                    {
                        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                    }

                    $min_order = 1;
                    if(empty($min_order)) {
                        $min_order = 1;
                    }

                    $delivery_time = $product->delivery_period;
                    $delivery_time_guaranteed = $product->delivery_period;


                    if(empty($delivery_time)) {
                        $delivery_time = 0;
                        $delivery_time_guaranteed = 0;
                    }



                    // //Создаем объек товара и добавляем его в список:
                    $DocpartProduct = new DocpartProduct((string)$product->brand,
                        (string)$product->articul,
                        (string)$product->name,
                        (int)$product->stocks,
                        $price + $price*$markup,
                        $delivery_time + $storage_options["additional_time"],
                        $delivery_time_guaranteed + $storage_options["additional_time"],
                        NULL,
                        $min_order,
                        $storage_options["probability"],
                        $storage_options["office_id"],
                        $storage_options["storage_id"],
                        $storage_options["office_caption"],
                        $storage_options["color"],
                        $storage_options["storage_caption"],
                        $price,
                        $markup,
                        2,0,0,'',null,array("rate"=>$storage_options["rate"], "id_for_order" => $product->id_for_order)
                        );

                    if($DocpartProduct->valid == true)
                    {
                        array_push($this->Products, $DocpartProduct);
                    }

                }

            }

        }


		// -------------------------------------------------------------------------------------------------


			//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
			
	}
};//~class armtek_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new active_oil($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);

exit(json_encode($ob));
?>