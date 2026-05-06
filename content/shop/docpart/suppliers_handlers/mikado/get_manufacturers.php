<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

ini_set("memory_limit", "512M");

//Класс бренда
require_once( $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class mikado_enclosure
{
    public $status = 0;
    public $ProductsManufacturers = array();
    private $storage_options = array();


    public function __construct( $article, $storage_options )
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $this->storage_options = $storage_options;

        //ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_simple_message("Внимание! Для API поставщика действует ограничение доступа по IP-адресу. Убедитесь, что IP-адрес Вашего сайта добавлен в список допущенных в личном кабинете на сайте поставщика.");
        }

        $this->status = 0;//По умолчанию
        $items_order = $storage_options["items_order"];
        /*****Учетные данные*****/
        $login = $storage_options["user"];
        $password = $storage_options["password"];
        /*****Учетные данные*****/
        $ch = curl_init();
        $url_request = "http://www.mikado-parts.ru/ws1/service.asmx/Code_Search?Search_Code={$article}&ClientID={$login}&Password={$password}&FromStockOnly={$items_order}";

        curl_setopt( $ch, CURLOPT_URL, $url_request );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        $execute = curl_exec( $ch );
        
		//ЛОГ [API-запрос] (вся информация о запросе)
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $xml = simplexml_load_string( $execute );
            $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $url_request, htmlentities($execute), print_r($xml, true) );
        }

        if(curl_errno($ch))
        {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
            }
        }

        curl_close( $ch );

        $xml = simplexml_load_string( $execute );
        $rows = $xml->List;
        // Формирование массива товаров по запрошенному артикулу
        foreach ( $rows->Code_List_Row as $row ) {
			$ProducerCode = $row->ProducerCode->__toString();
			$ProducerCode = mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $ProducerCode), "UTF-8");
			
			if($ProducerCode === $article){
				$srock = $row->Srock->__toString();
				$DocpartManufacturer = new DocpartManufacturer(
					$brand_name = $row->ProducerBrand->__toString(),
					$brand_id = null,
					$item_name = $row->Name->__toString(),
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$synonyms_single_query = true,
					$params = $row->ZakazCode->__toString()
				);
				array_push($this->ProductsManufacturers, $DocpartManufacturer);
			}
        }

        //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список производителей", print_r($this->ProductsManufacturers, true) );
        }

        $this->status = 1;

    }//~function __construct($article)

};//~class mikado_enclosure

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
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new mikado_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>