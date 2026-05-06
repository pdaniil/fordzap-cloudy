<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class tehnomir_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
    $token = $storage_options["token"];

    $url = "https://api.tehnomir.com.ua/price/search";

		$postdata = array(
			"apiToken" => $token,
			"code" => $article,
			"isShowAnalogs" => 1,
			"currency" => "USD"
		);

		$headers = array(
			"Content-Type: application/json",
			"accept: application/json"
		);
    

    // -------------------------------------------------------------------------------------------------
    
    foreach($manufacturers as $manufacturer)
    {

        $manufacturer_id = $manufacturer['manufacturer_id'];

				if(!empty($manufacturer_id)) {
					$postdata['brandId'] = $manufacturer_id;
				}
				
				$postdata = json_encode($postdata);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
				curl_setopt($ch, CURLOPT_VERBOSE, 1 );
				$execute = curl_exec($ch);
				
        //ЛОГ [API-запрос] (вся информация о запросе)
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
						$DocpartSuppliersAPI_Debug->log_api_request("Получение списка товаров", $url, $execute, print_r(json_decode($execute, true), true) );
        }

				$data = json_decode($execute, true);

        if($data['success'] !== false) {

            $prices = $data['data'];

            if(!empty($prices)) {

              foreach($prices as $product) {

									if(!empty($product['rests'])) {

										foreach ($product['rests'] as $product_rest) {
											
											$price = (float)$product_rest["price"];

											//Наценка
											$markup = $storage_options["markups"][(int)$price];
											if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
											{
													$markup = $storage_options["markups"][count($storage_options["markups"])-1];
											}

											//Срок доставки
											$time_to_exe = 0;
											$time_arrive = new DateTime( $product_rest["deliveryDate"] );//Время поступления
											$time_now = new DateTime();
											$time_now->setTime( 0, 0 ); //Устанавливаем 00:00
											$time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
											$time_to_exe = $time_interval->days; //Получаем дни (int)

											if(empty($time_to_exe)) {
													$time_to_exe = 0;
											}

											$probability = $storage_options["probability"];
											if(!empty($product_rest['deliveryPercent'])) {
												$probability = $product_rest['deliveryPercent'];
											}

											$json_data = array(
												"is_tehnomir" => true,
												"deliveryType" => $product_rest["deliveryType"],
												"weight" => round($product["weight"], 3),
												"supplier" => $product_rest["priceLogo"],
												"ProdId" => $product["productId"],
												"SupCode" => $product_rest["priceLogo"],
												"BrandId" => $product_rest["brandId"],
												"Code" => $product["code"]
											);
						
											$json = json_encode($json_data);

											//Создаем объек товара и добавляем его в список:
											$DocpartProduct = new DocpartProduct($product["brand"],
											$product["code"],
											$product["descriptionRus"],
											$product_rest["quantity"],
											$price + $price*$markup,
											$time_to_exe + $storage_options["additional_time"],
											$time_to_exe + $storage_options["additional_time"],
											NULL,
											$product_rest["multiplicity"],
											$probability,
											$storage_options["office_id"],
											$storage_options["storage_id"],
											$storage_options["office_caption"],
											$storage_options["color"],
											$storage_options["storage_caption"],
											$price,
											$markup,
											2,0,0,'',
											$json,
											array("rate"=>$storage_options["rate"])
											);

											 if($DocpartProduct->valid == true)
											 {
													 array_push($this->Products, $DocpartProduct);
											 }
										}
									}

              }

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
};//~class armtek_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML-JSON") );




$ob = new tehnomir_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>