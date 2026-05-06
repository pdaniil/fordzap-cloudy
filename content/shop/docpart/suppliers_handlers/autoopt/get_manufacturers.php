<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //Предотвратить вывод сообщений об ошибках

require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autoopt_enclosure
{
    public $status = 0;
    public $ProductsManufacturers = array();


    public function __construct($article, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();


        $token = $storage_options['token'];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://beta.autoopt.ru/api/v2/parts/search/' . $article,
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
            $DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу: " . $article, 'https://beta.autoopt.ru/api/v2/parts/search/' . $article, $response, print_r(json_decode($response, true), true));
        }

        if (empty($result))
        {
            return;
        }

        foreach ($result as $product) {

            $DocpartManufacturer = new DocpartManufacturer(
                $product["brand"],
                $product["article"],
                $product["name"],
                $storage_options["office_id"],
                $storage_options["storage_id"],
                true,
                $product["code"]
            );
            if ($DocpartManufacturer->valid) {

                array_push($this->ProductsManufacturers, $DocpartManufacturer);

            }
        }
        // Аналоги
        $curl = curl_init();

        
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://beta.autoopt.ru/api/v2/parts/analogs/' . $product["code"],
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
            $DocpartSuppliersAPI_Debug->log_api_request("Получение списка аналогов по артикулу: " . $article, 'https://beta.autoopt.ru/api/v2/parts/analogs/' . $product["code"], $response, print_r(json_decode($response, true), true));
        }

       
        foreach ($result as $product) {

            $DocpartManufacturer = new DocpartManufacturer(
                $product["brand"],
                $product["article"],
                $product["name"],
                $storage_options["office_id"],
                $storage_options["storage_id"],
                true
            );
            if ($DocpartManufacturer->valid) {

                array_push($this->ProductsManufacturers, $DocpartManufacturer);

            }

        }

        $this->status = 1;

        //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
        if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true));
        }


    }

}

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object(array("storage_id" => $storage_options["storage_id"], "api_script_name" => __FILE__, "api_type" => "CURL-HTTP-JSON"));
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

$ob = new autoopt_enclosure(
    $_POST["article"],
    $storage_options
);
exit(json_encode($ob));
?>