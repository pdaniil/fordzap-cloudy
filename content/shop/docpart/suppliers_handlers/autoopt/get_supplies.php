<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //Предотвратить вывод сообщений об ошибках

require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autoopt_enclosure
{
    public $result = 0;
    public $Products = array();


    public function __construct($article, $manufacturers, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
        $this->result = 0; //По умолчанию

        $token = $storage_options['token'];
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://beta.autoopt.ru/api/v2/parts/search/' . $manufacturers[0]["manufacturer_id"],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_USERAGENT => '123',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token
                ),
            )
        );

        $response = curl_exec($curl);
        curl_close($curl);
        

        $result = json_decode($response, true);



        //ЛОГ [API-запрос] (вся информация о запросе)
        if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
            $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков товара: " . $manufacturers[0]["manufacturer_id"], 'https://beta.autoopt.ru/api/v2/parts/search/' . $manufacturers[0]["manufacturer_id"], $response, print_r(json_decode($response, true), true));
        }

        if (empty($result))
        {
            return;
        }

        foreach ($result as $product) {
            
			$price_purchase = $product["price"];

            //Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
            $markup = $storage_options["markups"][(int) $price_purchase];

            if ($markup == NULL) {

                $markup = $storage_options["markups"][count($storage_options["markups"]) - 1];

            }

            $price_for_customer = $price_purchase + $price_purchase * $markup;

            $manufacturer = $product["brand"];
            $article = $product["article"];
            $name = $product["name"];
            $time_to_exe = $storage_options["additional_time"];
            $time_to_exe_guaranteed = $storage_options["additional_time"];
            $storage = 0;
            $min_order = 1;
            $probability = $storage_options["probability"];
            $product_type = 2;
            $product_id = 0;
            $storage_record_id = 0;
            $url = "";
            $json_params = ['code' => $product['code']];
            $rest_params = array("rate" => $storage_options["rate"]);

            foreach ($product["warehouses"] as $storage) {
                $exist = $storage["quantity"];
                $DocpartProduct = new DocpartProduct(
                    $manufacturer,
                    $article,
                    $name,
                    $exist,
                    $price_for_customer,
                    $time_to_exe,
                    $time_to_exe_guaranteed,
                    $storage,
                    $min_order,
                    $probability,
                    $storage_options["office_id"],
                    $storage_options["storage_id"],
                    $storage_options["office_caption"],
                    $storage_options["color"],
                    $storage_options["storage_caption"],
                    $price_purchase,
                    $markup,
                    $product_type,
                    $product_id,
                    $storage_record_id,
                    $url,
                    json_encode($json_params),
                    $rest_params
                );

                if ($DocpartProduct->valid) {

                    array_push($this->Products, $DocpartProduct);

                }
            }
        }

        //Аналоги 

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://beta.autoopt.ru/api/v2/parts/analogs/' . $manufacturers[0]["params"],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_USERAGENT => '123',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token
                ),
            )
        );

        $response = curl_exec($curl);
        curl_close($curl);

        $result_analogs = json_decode($response, true);


        //ЛОГ [API-запрос] (вся информация о запросе)
        if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
            $DocpartSuppliersAPI_Debug->log_api_request("Получение списка аналогов по артикулу: " . $manufacturers[0]["manufacturer_id"], 'https://beta.autoopt.ru/api/v2/parts/analogs/' . $manufacturers[0]["params"], $response, print_r(json_decode($response, true), true));
        }

       
        foreach ($result_analogs as $product_analog) {
            $curl = curl_init();

            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_URL => 'https://beta.autoopt.ru/api/v2/parts/search/' . $product_analog["article"],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_USERAGENT => '123',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer ' . $token
                    ),
                )
            );

            $response = curl_exec($curl);
            curl_close($curl);

            
            $result = json_decode($response, true);
            if (empty($result))
            {
                continue;
            }
            //ЛОГ [API-запрос] (вся информация о запросе)
            if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
                $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков товаров для аналога: " . $product_analog["article"], 'https://beta.autoopt.ru/api/v2/parts/search/' . $product_analog["article"], $response, print_r(json_decode($response, true), true));
            }


            foreach ($result as $product) {
                $product = $result[0];

                $price_purchase = $product["price"];

                //Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                $markup = $storage_options["markups"][(int) $price_purchase];

                if ($markup == NULL) {

                    $markup = $storage_options["markups"][count($storage_options["markups"]) - 1];

                }

                $price_for_customer = $price_purchase + $price_purchase * $markup;

                $manufacturer = $product["brand"];
                $article = $product["article"];
                $name = $product["name"];
                $time_to_exe = $storage_options["additional_time"];
                $time_to_exe_guaranteed = $storage_options["additional_time"];
                $storage = 0;
                $min_order = 1;
                $probability = $storage_options["probability"];
                $product_type = 2;
                $product_id = 0;
                $storage_record_id = 0;
                $url = "";
                $json_params = ['code' => $product['code']];
                $rest_params = array("rate" => $storage_options["rate"]);

                foreach ($product["warehouses"] as $storage) {
                    $exist = $storage["quantity"];

                    $DocpartProduct = new DocpartProduct(
                        $manufacturer,
                        $article,
                        $name,
                        $exist,
                        $price_for_customer,
                        $time_to_exe,
                        $time_to_exe_guaranteed,
                        $storage,
                        $min_order,
                        $probability,
                        $storage_options["office_id"],
                        $storage_options["storage_id"],
                        $storage_options["office_caption"],
                        $storage_options["color"],
                        $storage_options["storage_caption"],
                        $price_purchase,
                        $markup,
                        $product_type,
                        $product_id,
                        $storage_record_id,
                        $url,
                        json_encode($json_params),
                        $rest_params
                    );

                    if ($DocpartProduct->valid) {

                        array_push($this->Products, $DocpartProduct);

                    }
                }
            }
        }

        $this->result = 1;

        //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
        if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true));
        }


    }

}
/*
$f = fopen('log_sup.txt','w');
fwrite($f, $_POST["storage_options"]."\n");
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["manufacturers"]."\n");
fclose($f);

$_POST["storage_options"] = '';  
$_POST["article"] = '';
$_POST["manufacturers"] = '';
*/
//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object(array("storage_id" => $storage_options["storage_id"], "api_script_name" => __FILE__, "api_type" => "CURL-HTTP-JSON"));



$ob = new autoopt_enclosure(
    $_POST["article"],
    json_decode($_POST["manufacturers"], true),
    $storage_options
);
exit(json_encode($ob));
?>