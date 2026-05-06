<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class uniqom_enclosure
{
    public $result;
    public $Products = array();//Список товаров

    public function __construct($article, $manufacturers, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $this->result = 0;//По умолчанию

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
            $DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "http://uniqom.ru/api/v2.1/searchall.json?term=".$article."&login=".$login."&password=".$password, $curl_result, print_r(json_decode($curl_result, true), true));
        }

        if(curl_errno($ch))
        {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true));
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
			//Проверяем выбранную категорию на первом этапе
			if($manufacturers[0]['manufacturer_id'] != $curl_result[$i]['category_id']){
				continue;
			}
			
            //Данные на весь блок
            $manufacturer = $curl_result[$i]["brand"];
            $article = $curl_result[$i]["article_code"];
            $name = $curl_result[$i]["header"];
            $min_order = $curl_result[$i]["mult_amount"];
						$good_id = $curl_result[$i]["id"];

            //Склады
            $deposit_storages = $curl_result[$i]["deposit_storages"];

            //Пробегаем по складам
            for($s=0; $s < count($deposit_storages); $s++)
            {
                $price = $deposit_storages[$s]["price"];
				
								//Наценка
								$markup = $storage_options["markups"][(int)$price];
								if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
								{
									$markup = $storage_options["markups"][count($storage_options["markups"])-1];
								}

								$storage_id = $deposit_storages[$s]["id"];
								$json = json_encode(array("good_id" => $good_id, "storage_id" => $storage_id));
				
                //Создаем объек товара и добавляем его в список:
                $DocpartProduct = new DocpartProduct($manufacturer,
                    $article,
                    $name,
                    $deposit_storages[$s]["deposit"],
                    $price + $price*$markup,
                    $deposit_storages[$s]["days"] + $storage_options["additional_time"],
                    $deposit_storages[$s]["days"] + $storage_options["additional_time"],
                    $deposit_storages[$s]["header"],
                    $min_order,
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

        //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true));
        }

        $this->result = 1;
    }//~function __construct($article)
};//~class uniqom_enclosure



/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["manufacturers"]."\n");
fwrite($f, $_POST["storage_options"]."\n");
*/

/*
$_POST["article"] = 'C110';
$_POST["manufacturers"] = '';
$_POST["storage_options"] = '';
*/



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );



$ob = new uniqom_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>