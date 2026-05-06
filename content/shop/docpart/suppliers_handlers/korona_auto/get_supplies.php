<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class korona_enclosure
{
    public $result = 0;
    public $Products = array();

    public function __construct($article, $manufacturers, $storage_options)
    {
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
        $api_key = $storage_options['api_key'];
        $url_request = "https://korona-auto.com/api/search/?q={$article}&apiUid={$api_key}&dataType=json";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url_request);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $exec = curl_exec($ch);


        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_api_request("Получение списка товаров по артикулу ".$article, $url_request, $exec, print_r(json_decode($exec, true), true) );
        }


        if(curl_errno($ch))
        {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
            }
        }
		
		
		curl_close($ch);
		
        $curl_result = json_decode($exec, true);
        
		/*
		echo '<pre>';
		var_dump($curl_result);
		echo '</pre>';
		*/

        $products = $curl_result["product"];

        if(!empty($products))
        {
            //Ограничим максимальное количество запросов по товарам
            $req_count = 0;

            foreach($products as $product)
            {
                if($product['producer'] != $manufacturers[0]['manufacturer'] || $req_count > 20){
					continue;
				}
				
                $product_id = $product['id'];

                $url_request = "https://korona-auto.com/api/product/info/?id={$product_id}&apiUid={$api_key}&dataType=json";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, $url_request);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                $exec = curl_exec($ch);


                //ЛОГ [API-запрос] (вся информация о запросе)
                if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
                {
                    $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по ID товара ".$product_id, $url_request, $exec, print_r(json_decode($exec, true), true) );
                }


                if(curl_errno($ch))
                {
                    //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
                    if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
                    {
                        $DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
                    }
                }


                $curl_result = json_decode($exec, true);
				
				/*
				echo '<pre>';
				var_dump($curl_result);
				echo '</pre>';
				*/
				
                $product_info = $curl_result["product"];
				
                if(isset($product_info) && !empty($product_info)){
					
                    $price = (int) $product_info["prices"][0]["warehouse"]["value"];
                    $qnty = (int) $product_info["stock"][0]["warehouse"]["quantity"];

                    $storages = $product_info["stock"];

                    //Наценка
                    $markup = $storage_options["markups"][(int)$price];
                    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                    {
                        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                    }

                    $min_order = 1;

                    $time_to_exe = 0;
                    $time_arrive = new DateTime( $product_info['stock'][0]['warehouse']['deliveryDate'] );//Время поступления
                    $time_now = new DateTime();
                    $time_now->setTime( 0, 0 ); //Устанавливаем 00:00
                    $time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
                    $time_to_exe = $time_interval->days; //Получаем дни (int)

                    $time_to_exe_guaranteed = 0;
                    $time_arrive = new DateTime( $product_info['stock'][0]['warehouse']['deliveryDate']);//Время поступления
                    $time_now = new DateTime();
                    $time_now->setTime( 0, 0 ); //Устанавливаем 00:00
                    $time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
                    $time_to_exe_guaranteed = (int)$time_interval->days; //Получаем дни (int)

                    $brands_str = $product_info["producer"];

                    $brands = explode(",", $brands_str);
                    if(is_array($brands)){
                        foreach($brands as $brand){

                            $brand = trim($brand);

                            for($i=0; $i<count($storages); $i++){

                                $qnt = $storages[$i]["warehouse"]["quantity"];
                                $name_storage = $storages[$i]["warehouse"]["name"];

                                //Создаем объек товара и добавляем его в список:
                                $DocpartProduct = new DocpartProduct((string)($brand),
                                    (string)$product_info["factory_number"],
                                    (string)$product_info["name"],
                                    $qnt,
                                    $price + $price*$markup,
                                    $time_to_exe + $storage_options["additional_time"],
                                    $time_to_exe_guaranteed + $storage_options["additional_time"],
                                    $name_storage,
                                    $min_order,
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
                    }
                }

                $req_count++;

            }//~foreach ($search_result as $product)
        }
		
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
        }

        $this->result = 1;
    }
}



/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["manufacturers"]."\n");
fwrite($f, $_POST["storage_options"]."\n");
*/

/*
$_POST["article"] = 'C110';
$_POST["manufacturers"] = '';
$_POST["storage_options"] = '';
*/



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );



$ob = new korona_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>