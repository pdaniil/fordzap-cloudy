<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class shate_m_enclosure
{
    public $result;
    public $Products = array();//Список товаров

    public function __construct($article, $manufacturers, $storage_options)
    {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
        $DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $this->result = 0;//По умолчанию

        /*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
        $api_key = $storage_options["api_key"];
        $AgreementCode = !empty($storage_options["AgreementCode"]) ? $storage_options["AgreementCode"] : null;
        /*****Учетные данные*****/

        // -------------------------------------------------------------------------------------------------
        //Авторизуемся

        $api_path = "";
        switch($storage_options["country"])
        {
            case "ru":
                $api_path = "https://api.shate-m.ru";
                break;
            case "by":
                $api_path = "https://api.shate-m.by";
                break;
            case "kz":
                $api_path = "https://api.shate-m.kz";
                break;
            default: $api_path = "https://api.shate-m.ru";
        }

        if(!empty($login) && !empty($password)) {
            $url = $api_path . '/api/v1/auth/login';
            $auth_fields = array(
                'Login' => $login,
                'Password' => $password
            );
        } else {
            $url = $api_path . '/api/v1/auth/loginbyapikey';
            $auth_fields = array(
                'ApiKey' => $api_key
            );
        }

        $auth_field_string = http_build_query($auth_fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $auth_field_string);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        $result = curl_exec( $ch );

        $curl_result = json_decode($result, true);

        $token = "";

        if(!empty($curl_result['access_token'])) {
            $token = $curl_result['access_token'];
        } else {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("Ошибка получения Token 2 шаг", print_r($curl_result, true) );
            }
        }

        //ЛОГ [API-запрос] (вся информация о запросе)
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос на авторизацию 2 шаг", $url."<br>", $result, "Полученный токен: " . $token);
        }

        if(curl_errno($ch))
        {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("CURL-ошибка 2 шаг", print_r(curl_error($ch), true) );
            }
        }

        curl_close($ch);

        //По каждому производителю
        for($i = 0; $i < count($manufacturers); $i++)
        {
            // Запрашиваем товары по артикулу.
            $url_req = $api_path . "/api/v1/articles/search";

            $post_fields = array(
                'ids' => array(),
                'keys' => array(
                    0 => array("articleCode" => $article, "tradeMarkName" => $manufacturers[$i]['manufacturer'])
                )
            );

            $post_fields_string = json_encode($post_fields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields_string);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorization: Bearer $token"
            ));

            $result = curl_exec($ch);
            $articles = json_decode($result, true);

            //ЛОГ [API-запрос] (вся информация о запросе)
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос для получения товаров с уточнением бренда", $url_req."<br>". $post_fields_string, $result, print_r($articles, true));
            }

            if(curl_errno($ch))
            {
                //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
                if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
                {
                    $DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
                }
            }
            curl_close($ch);

            //На этом шаге мы получили ID артикулов по уточнению бренда
            //Теперь нам нужно получить все ценовые предложения
            if(!empty($articles)){

                foreach($articles as $item){

                    $article_id = $item['article']['id'];// Артикул

                    // Запрашиваем цены по ID товара
                    $url_req = $api_path . "/api/v1/prices/search/with_article_info";

                    $post_fields_params = array(
                        "articleId" => $article_id,
                        "includeAnalogs" => true
                    );

                    if(!empty($AgreementCode)) {
                        $url_req .= "?agreementCode=" . $AgreementCode;
                    }

                    $post_fields = array($post_fields_params);

                    $post_fields_string = json_encode($post_fields);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url_req);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields_string);

                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Content-Type: application/json",
                        "Authorization: Bearer $token"
                    ));

                    $result = curl_exec($ch);
                    $articles_prices = json_decode($result, true);

                    //ЛОГ [API-запрос] (вся информация о запросе)
                    if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
                    {
                        $DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос для получения ценовых предложений", $url_req."<br>". $post_fields_string, $result, print_r($articles_prices, true));
                    }

                    if(curl_errno($ch))
                    {
                        //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
                        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
                        {
                            $DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
                        }
                    }
                    curl_close($ch);

                    if(!empty($articles_prices)){
                        foreach($articles_prices as $item_prices){

                            $article_price_info = $item_prices['article'];
                            $article_price_storages = $item_prices['prices'];

                            $articleId = $article_price_info['id'];
                            $article_name = $article_price_info['name'];
                            $article_description = $article_price_info['description'];
                            $article_value = $article_price_info['code'];
                            $article_manufacturer = $article_price_info['tradeMarkName'];

                            if(!empty($article_price_storages)) {
                                foreach ($article_price_storages as $article_price_storage) {

                                    $locationCode = $article_price_storage['locationCode'];
                                    $agreementCode = $article_price_storage['agreementCode'];
                                    $offerKey = $article_price_storage['id'];

                                    $price = !empty($article_price_storage['price']['value']) ? (float)$article_price_storage['price']['value'] : 0;
                                    $quantity = !empty($article_price_storage['quantity']['available']) ? $article_price_storage['quantity']['available'] : 0;
                                    $multiplicity = !empty($article_price_storage['quantity']['multiplicity']) ? $article_price_storage['quantity']['multiplicity'] : 1;
                                    $return_property = !empty($article_price_storage['addInfo']['isReturnAllowed']) ? $article_price_storage['addInfo']['isReturnAllowed'] : false;

                                    //Наценка
                                    $markup = $storage_options["markups"][(int)$price];
                                    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                                    {
                                        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                                    }

                                    $shippingDateTime = !empty($article_price_storage['deliveryDateTimes']) ? $article_price_storage['deliveryDateTimes'] : array(array("deliveryDateTime" => $article_price_storage['shippingDateTime']));


                                    if(!empty($shippingDateTime)) {

                                        foreach ($shippingDateTime as $shipping_item) {

                                            $shipping_item_date = $shipping_item['deliveryDateTime'];

                                            //Срок доставки
                                            $time_to_exe = 0;
                                            $time_arrive = new DateTime( $shipping_item_date );//Время поступления
                                            $time_now = new DateTime();
                                            $time_now->setTime( 0, 0 ); //Устанавливаем 00:00
                                            $time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
                                            $time_to_exe = $time_interval->days; //Получаем дни (int)

                                            //Набор параметров для SAO
                                            //$json_params = array("OfferKey"=>$offerKey, "ArticleId"=>$articleId, "Multiplicity"=>$multiplicity, 'deliveryPoint' => $shipping_item['deliveryAddressCode']);
                                            $json_params = array("PriceId" => $offerKey, "DeliveryDateTime" => $shipping_item_date, "locationCode" => $locationCode, "agreementCode" => $agreementCode);

                                            //Создаем объек товара и добавляем его в список:
                                            $DocpartProduct = new DocpartProduct($article_manufacturer,
                                                $article_value,
                                                $article_name,
                                                $quantity,
                                                $price + $price*$markup,
                                                $time_to_exe + $storage_options["additional_time"],
                                                $time_to_exe + $storage_options["additional_time"],
                                                NULL,
                                                $multiplicity,
                                                $storage_options["probability"],
                                                $storage_options["office_id"],
                                                $storage_options["storage_id"],
                                                $storage_options["office_caption"],
                                                $storage_options["color"],
                                                $storage_options["storage_caption"],
                                                $price,
                                                $markup,
                                                2,0,0,'', json_encode($json_params), array("rate"=>$storage_options["rate"])
                                            );

                                            if($DocpartProduct->valid == true)
                                            {
                                                array_push($this->Products, $DocpartProduct);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
        }

        $this->result = 1;
    }//~function __construct($article)
};//~class shate_m_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new shate_m_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>