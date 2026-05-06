<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class japarts_enclosure
{
    public $result ;

    public $Products = array();//Список товаров

    public function __construct($article, $manufacturers, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $this->result = 0;//По умолчанию

        /*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
        /*****Учетные данные*****/


        $url = 'https://www.japarts.ru/?pass='.$password.'&detailnum='.$article.'&id=ws&login='.$login.'&makename='.$manufacturers[0]['manufacturer'].'&action=search3&cross=1&crosslimit=10&rowlimit=5';
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
		var_dump($manufacturers[0]['manufacturer']);
		var_dump($url);
		var_dump($obj);
		echo '</pre>';
		*/

        //ЛОГ [API-запрос] (вся информация о запросе)
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и бренду ".$manufacturers[0]['manufacturer'], $url, '', print_r($obj, true), true);
        }

        if(curl_errno($curl))
        {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($curl), true));
            }
        }


        curl_close($curl);


        for($i=0; $i < count($obj); $i++)//Цикл наполнения массивов
        {
            $price = $obj[$i]->pricerur;


            //Наценка
            $markup = $storage_options["markups"][(int)$price];
            if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
            {
                $markup = $storage_options["markups"][count($storage_options["markups"])-1];
            }


            //Создаем объек товара и добавляем его в список:
            $DocpartProduct = new DocpartProduct($obj[$i]->makename,
                $obj[$i]->detailnum,
                $obj[$i]->detailname,
                $obj[$i]->quantity,
                $price + $price*$markup,
                $obj[$i]->timegar + $storage_options["additional_time"],
                $obj[$i]->timegar + $storage_options["additional_time"],
                NULL,
                1,
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
        }//~for

        //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true));
        }

        $this->result = 1;
    }//~__construct
};//~japarts_enclosure



/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["manufacturers"]."\n");
fwrite($f, $_POST["storage_options"]."\n");
*/

/*
$_POST["article"] = 'C230';
$_POST["manufacturers"] = '';
$_POST["storage_options"] = '';
*/



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );



$ob = new japarts_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>