<?php
/**
 * Скрипт для обработки данных в личном кабинете Автоевро
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


$result = array(
    'status'  => true,
    'message' => '',
    'html'    => '',
    'data'    => array()
);

//Получаем данные
$request_object = json_decode($_POST['request_object'], true);

switch($request_object['action']) {
    case 'get_data':

        try
        {
            if(!(isset($request_object["api_key"]) && !empty($request_object["api_key"]))) {
                throw new Exception("Отсутствует api-ключ для подключения к Автоевро.");
            }

            /*****Учетные данные*****/
            $api_key = $request_object["api_key"];
            /*****Учетные данные*****/

            //Получение данных доставки
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api2.autoeuro.ru/api/v2/json/get_deliveries/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'key: '.$request_object["api_key"]
                ),
            ));


            $response = curl_exec($curl);
            $response = json_decode($response);
            if (isset($response->DATA))
            {
                $result["data"]["delivery"] = $response->DATA;
            }
            else
            {
                throw new Exception("Не правильный API-ключ");
            }

            //Получение данных плательщика
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api2.autoeuro.ru/api/v2/json/get_payers/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'key: '.$request_object["api_key"]
                ),
            ));


            $response = curl_exec($curl);
            $response = json_decode($response);
            if (isset($response->DATA))
            {
                $result["data"]["payers"] = $response->DATA;
            }
            else
            {
                throw new Exception("Не правильный API-ключ");
            }
        }
        catch (Exception $e)
        {
            $result['status'] = false;
            $result['message'] = "Ошибка: " . $e->getMessage();
        }

        //Формируем ответ для вывода
        if($result['status'])
        {

            $connection_options = isset($request_object["connection_options"]) ? $request_object["connection_options"] : array();

            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Автоевро Личный кабинет
        		</div>
        		<div class=\"panel-body\">";

            $address = array();
            if(!empty($result['data']['delivery']))
            {
                $address = $result['data']['delivery'];

            }
            if(!empty($address))
            {
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Адрес доставки</label>
                                <div class=\"col-lg-6\">
                                     <select class=\"form-control\" id=\"delivery_key\">
                                        <option value=\"\">Не выбран</option>";
                foreach($address as $address_item) {
                    $delivery_item_name = $address_item->delivery_name;
                    $is_selected = false;
                    if(!empty($connection_options) && isset($connection_options['delivery_key']) && $connection_options['delivery_key'] == $address_item->delivery_key) $is_selected = true;
                    $selected = $is_selected ? 'selected' : '' ;
                    $result['html'] .= "<option value=\"" . $address_item->delivery_key . "\" " . $selected . ">" . $delivery_item_name . "</option>";
                }

                $result['html'] .= "</select>
                        </div>
                    </div>
                    <div class=\"hr-line-dashed col-lg-12\"></div>";

            }

            $payers = array();
            if(!empty($result['data']['payers']))
            {
                $payers = $result['data']['payers'];

            }
            if(!empty($payers))
            {
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Плательщик</label>
                                <div class=\"col-lg-6\">
                                     <select class=\"form-control\" id=\"payer_key\">
                                        <option value=\"\">Не выбран</option>";
                foreach($payers as $payer) {
                    $payer_name = $payer->payer_name;
                    $is_selected = false;
                    if(!empty($connection_options) && isset($connection_options['payer_key']) && $connection_options['payer_key'] == $payer->payer_key) $is_selected = true;
                    $selected = $is_selected ? 'selected' : '' ;
                    $result['html'] .= "<option value=\"" . $payer->payer_key . "\" " . $selected . ">" . $payer_name . "</option>";
                }

                $result['html'] .= "</select>
                        </div>
                    </div>
                    <div class=\"hr-line-dashed col-lg-12\"></div>";

            }

            $result['html'] .= "</div><div class=\"hr-line-dashed col-lg-12\"></div>";
            $result['html'] .= "</div></div>";
        }
        else
        {

            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Автоевро Личный кабинет
        		</div>
        		<div class=\"panel-body\"><div class=\"alert alert-danger\">" . $result['message'] . "</div></div>
        	</div>";
        }

        break;

    default:
        $result['status'] = false;
        $result['message'] = 'Неизвестное действие';
}

exit(json_encode($result));