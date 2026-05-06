<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");
//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class forum_auto_enclosure
{
    public $status = 0;
    public $ProductsManufacturers = array();
    public function __construct($article, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $login			= trim($storage_options["login"]);
        $password		= trim($storage_options["password"]);
        $article_search	= $article;
        try {
            $client = new SoapClient("https://api.forum-auto.ru/wsdl", array('soap_version' => SOAP_1_2, 'exceptions' => true));
            $search_result = $client->listBrands($login, $password, $article_search);
            
			/*
			echo '<pre>';
			var_dump($search_result);
			echo '</pre>';
			*/
			
			if (!empty($search_result) and is_array($search_result)){
                foreach($search_result as $item){
					if( empty($item['brand']) || empty($item['art']) || ($item['art'] != $article) ){
						continue;
					}
                    $DocpartManufacturer = new DocpartManufacturer( $item['brand'],
                        $item['brand'],
                        $item['art'],
                        $storage_options["office_id"],
                        $storage_options["storage_id"],
                        true,
                        ""
                    );
                    array_push($this->ProductsManufacturers, $DocpartManufacturer);
                    //ЛОГ [API-запрос] (вся информация о запросе)
                }
			}
			
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка производителей ".$article, "SOAP-вызов метода listBrands('".$login."', '".$password."', '".$article."')", "См. ответ API после обработки", print_r($search_result, true) );
			}
			
			$this->status = 1;
			
        } catch (SoapFault $e) {
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
			}
        }

    }
}

/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["storage_options"]."\n");
*/

//$_POST["article"] = 'C110';
//$_POST["storage_options"] = '{"login":"","password":"","client_id":"","color":"#ffffff","probability":"","office_id":"1","storage_id":"3"}';


$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new forum_auto_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));