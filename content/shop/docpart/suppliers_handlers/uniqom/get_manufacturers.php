<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class uniqom_enclosure
{
    public $status = 0;
    public $ProductsManufacturers = array();

    public function __construct($article, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        /*****Учетные данные*****/
        $login = urlencode(trim($storage_options["login"]));
        $password = trim($storage_options["password"]);
        /*****Учетные данные*****/



        //СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://uniqom.ru/api/v2.1/searchall.json?term=".$article."&login=".$login."&password=".$password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $curl_result = curl_exec($ch);


        //ЛОГ [API-запрос] (вся информация о запросе)
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "http://uniqom.ru/api/v2.1/searchall.json?term=".$article."&login=".$login."&password=".$password, $curl_result, print_r(json_decode($curl_result, true), true) );
        }

        if(curl_errno($ch))
        {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
            }
        }


        // завершение сеанса и освобождение ресурсов
        curl_close($ch);

        $curl_result = json_decode($curl_result, true);
        $curl_result = $curl_result["goods"];
		
		/*
		echo '<pre>';
		var_dump($curl_result);
		echo '</pre>';
		*/

        for($i=0; $i < count($curl_result); $i++)
        {
            $manufacturer = $curl_result[$i]["brand"];
            $category_id = $curl_result[$i]["category_id"];
            $name = $curl_result[$i]["header"];
            
			//Создаем объек товара и добавляем его в список:
			$DocpartManufacturer = new DocpartManufacturer($manufacturer,
				$category_id,
				$name,
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true
			);

			if($DocpartManufacturer->valid == true)
			{
				array_push($this->ProductsManufacturers, $DocpartManufacturer);
			}
        }

        //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
        }

        $this->status = 1;
    }//~function __construct($article)
};//~class uniqom_enclosure



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



$ob = new uniqom_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>