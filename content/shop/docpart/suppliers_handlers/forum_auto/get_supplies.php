<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class forum_auto_enclosure
{
    public $result;
    public $Products = array();//Список товаров
    public function __construct($article, $manufacturers, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
        $this->result = 0;//По умолчанию

        /*****Учетные данные*****/
        $client_id = (int)$storage_options["client_id"];
        $login			= trim($storage_options["login"]);
        $password		= trim($storage_options["password"]);
        $article_search	= $article;
        try {
            $client = new SoapClient("https://api.forum-auto.ru/wsdl", array('soap_version' => SOAP_1_2, 'exceptions' => true));
            $search_result = $client->listGoods($login, $password, $article_search, 1, $manufacturers[0]['manufacturer']);

            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturers[0]['manufacturer'], "SOAP-вызов метода listGoods('".$login."', '".$password."', '".$article."', 1, '".$manufacturers[0]['manufacturer']."')", "См. ответ API после обработки", print_r($search_result, true) );
            }
			
			/*
			echo '<pre>';
			var_dump($search_result);
			echo '</pre>';
			*/
			
			if(is_array($search_result)){
				for($i=0; $i < count($search_result); $i++)
				{
					$this_partObject = $search_result[$i];//Получаем очередной Объект с полями одной запчасти из массива объектов

					$price = $this_partObject["price"];

					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}

					//ДЛЯ SAO
					$sao_data = array();
					$sao_data["gid"] =	$this_partObject["gid"];
					$json = json_encode($sao_data);
					//Создаем объек товара и добавляем его в список:
					$DocpartProduct = new DocpartProduct($this_partObject["brand"],
						$this_partObject["art"],
						$this_partObject["name"],
						$this_partObject["num"],
						$price + $price*$markup,
						$this_partObject["d_deliv"] + $storage_options["additional_time"],
						$this_partObject["d_deliv"] + $storage_options["additional_time"],
						$this_partObject["whse"],
						$this_partObject["kr"],
						$storage_options["probability"],
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$storage_options["office_caption"],
						$storage_options["color"],
						$storage_options["storage_caption"],
						$price,
						$markup,
						2,0,0,'',$json,array("rate"=>$storage_options["rate"])
					);

					if($DocpartProduct->valid == true)
					{
						array_push($this->Products, $DocpartProduct);
					}
				}
            }

            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
            }

            $this->result = 1;
        }
        catch(SoapFault $e)
        {
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
            }

            return;
        }
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
$_POST["manufacturers"] = '[{"manufacturer":"DOLZ","manufacturer_id":"DOLZ","manufacturer_show":"DOLZ","name":"C110","storage_id":"3","office_id":"1","synonyms_single_query":true,"params":"","valid":true}]';
$_POST["storage_options"] = '{"login":"","password":"","client_id":"","color":"#ffffff","probability":"","office_id":"1","storage_id":"3"}';
*/

$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new forum_auto_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));