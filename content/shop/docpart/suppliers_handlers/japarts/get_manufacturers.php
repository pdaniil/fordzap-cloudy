<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

function remove_utf8_bom($str)
{
    return str_replace("\xEF\xBB\xBF",'',$str);
}

class japarts_enclosure
{
    public $status;

    public $ProductsManufacturers = array();//Список производителей

    public function __construct($article, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $this->status = 0;//По умолчанию

        /*****Учетные данные*****/
        $login = trim($storage_options["login"]);
        $password = trim($storage_options["password"]);
        /*****Учетные данные*****/


        $url = 'https://www.japarts.ru/?pass='.$password.'&detailnum='.$article.'&id=ws&login='.$login.'&action=search3&cross=0&crosslimit=0&rowlimit=5';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));



        $string= curl_exec($curl);
        $string = iconv('cp1251', 'UTF-8',$string);
        $obj = json_decode($string);



		/*
		echo '<pre>';
		var_dump($obj);
		echo '</pre>';
		*/

        //ЛОГ [API-запрос] (вся информация о запросе)
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $url, '', print_r($obj, true), true);
        }

        if(curl_errno($curl))
        {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($curl), true) );
            }
        }


        curl_close($curl);


        for($i=0; $i < count($obj); $i++)//Цикл наполнения массивов
        {
			if($obj[$i]->detailnum == $article){
				//Создаем объек производителя и добавляем его в список:
				$DocpartManufacturer = new DocpartManufacturer(
					$obj[$i]->makename,
					$obj[$i]->detailnum,
					$obj[$i]->detailname,
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true
				);

				if($DocpartManufacturer->valid == true)
				{
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
				}
			}
        }//~for

        //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
        }

        $this->status = 1;
    }//~__construct
};//~japarts_enclosure



/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["storage_options"]."\n");
*/

/*
$_POST["article"] = 'C230';
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



$ob = new japarts_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>