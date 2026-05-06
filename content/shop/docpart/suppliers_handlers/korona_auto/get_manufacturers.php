<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class korona_enclosure
{
    public $status = 0;
    public $ProductsManufacturers = array();
    public function __construct($article, $storage_options)
    {
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


        //ЛОГ [API-запрос] (вся информация о запросе)
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
		
        if(is_array($curl_result)){
            foreach($curl_result['product'] as $item)
            {
                $DocpartManufacturer = new DocpartManufacturer($item['producer'],
                    $item['producer'],
                    $item['name'],
                    $storage_options["office_id"],
                    $storage_options["storage_id"],
                    true,
                    $item['factory_number']
                );
				
				if($DocpartManufacturer->valid == true){
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
				}
            }
        }

        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true));
        }
		
        $this->status = 1;
    }
}



/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["storage_options"]."\n");
*/

/*
$_POST["article"] = 'C110';
$_POST["storage_options"] = '';
*/



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new korona_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>