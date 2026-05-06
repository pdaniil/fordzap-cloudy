<?php

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class autoeuro_v2_enclosure
{
    public $result = 0;
    public $Products = array();

    public function __construct($article, $manufacturers, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $this->result = 0;//По умолчанию

        $time_now = time();//Время сейчас

        /*****Учетные данные*****/
        $api_key = $storage_options["api_key"];
        $delivery_key = $storage_options["delivery_key"];
        $with_offers = $storage_options["with_offers"];
        $with_crosses = $storage_options["with_crosses"];
        /*****Учетные данные*****/



        foreach ($manufacturers as $manufacturer) {


            $brand = urlencode($manufacturer["manufacturer"]);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api2.autoeuro.ru/api/v2/json/search_items/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('code' => $article, 'brand' => $brand, 'delivery_key' => $delivery_key, 'with_crosses' => $with_crosses, 'with_offers' => $with_offers),
                CURLOPT_HTTPHEADER => array(
                    'key: ' . $api_key
                ),
            ));

            $curl_result = curl_exec($curl);


            curl_close($curl);

            // -------------------------------------------------------------------------------------------------

            //ЛОГ [API-запрос] (вся информация о запросе)
            if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
                $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу " . $article . " и бренду " . $brand, "https://api2.autoeuro.ru/api/v2/json/search_items/", $curl_result, print_r(json_decode($curl_result, true), true));
            }

            if (curl_errno($curl)) {
                //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
                if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
                    $DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($curl));
                }
            }

            curl_close($curl);

            $result = json_decode($curl_result);

            //Сначала по запрошенному артикулу
            if (isset($result->DATA)) {
                $products = $result->DATA;

                if (isset($products)) {
                    //--------------По данным ответа---------------//
                    foreach ($products as $product) {
                        $price = (int)$product->price;

                        //Наценка
                        $markup = $storage_options["markups"][(int)$price];
                        if ($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                        {
                            $markup = $storage_options["markups"][count($storage_options["markups"]) - 1];
                        }

                        $min_order = $product->packing;
                        if (empty($min_order)) {
                            $min_order = 1;
                        }

                        $delivery_time = (strtotime($product->delivery_time) - time()) / 60 / 60 / 24;

                        if ($delivery_time < 0)
                        {
                            $delivery_time = 0;
                        }
                        $delivery_time_guaranteed = $delivery_time;

												//Срок доставки
												$time_to_exe = 0;
												$time_arrive = new DateTime( $product->delivery_time );//Время поступления
												$time_now = new DateTime();
												$time_now->setTime( 0, 0 ); //Устанавливаем 00:00
												$time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
												$time_to_exe = $time_interval->days; //Получаем дни (int)
                        
                        $rejects = $product->rejects;
                        $probability = $storage_options["probability"];
                        if(!empty($rejects) && $rejects != 0) {
                           $probability = 100 - $rejects;
                        }


                        // //Создаем объек товара и добавляем его в список:
                        $DocpartProduct = new DocpartProduct((string)$product->brand,
                            (string)$product->code,
                            (string)$product->name,
                            (int)$product->amount,
                            $price + $price * $markup,
                            $time_to_exe + $storage_options["additional_time"],
                            $time_to_exe + $storage_options["additional_time"],
                            NULL,
                            $min_order,
                            $probability,
                            $storage_options["office_id"],
                            $storage_options["storage_id"],
                            $storage_options["office_caption"],
                            $storage_options["color"],
                            $storage_options["storage_caption"],
                            $price,
                            $markup,
                            2, 0, 0, '', json_encode(array('offer_key' => $product->offer_key)), array("rate" => $storage_options["rate"])
                        );
                        if ($DocpartProduct->valid == true) {
                            array_push($this->Products, $DocpartProduct);
                        }
                    }
                }
            }


            // -------------------------------------------------------------------------------------------------

            //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
            if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
                $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true));
            }

            $this->result = 1;
        }
    }
};
//~class autoeuro_v2_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new autoeuro_v2_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>