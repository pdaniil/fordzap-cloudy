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
            

            /*****Учетные данные*****/
            $api_key = $request_object["authorization"];
            /*****Учетные данные*****/

            $curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'http://api.tmparts.ru/api/Shipping',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			  CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $api_key
			  ),
			));

			$response = curl_exec($curl);
            $shipping_arr = json_decode($response, true);
			
            if (isset($response["message"]))
            {
                throw new Exception("Не правильный API-ключ");
            }
			
			
			curl_close($curl);
			
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'http://api.tmparts.ru/api/Contracts',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			  CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $api_key
			  ),
			));

			$response = curl_exec($curl);
            $contracts_arr = json_decode($response, true);
			
            if (isset($response["message"]))
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
        			Настройки TMParts Личный кабинет
        		</div>
        		<div class=\"panel-body\">";

            if(!empty($shipping_arr))
            {
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Адрес доставки</label>
                                <div class=\"col-lg-6\">
                                     <select class=\"form-control\" id=\"ShipCode\"> <option value=\"-1\">Не указано</option>";

                foreach($shipping_arr as $address_item) {
                    $is_selected = false;
                    if(!empty($connection_options) && isset($connection_options['ShipCode']) && $connection_options['ShipCode'] == $address_item["ShipCode"]) $is_selected = true;
                    $selected = $is_selected ? 'selected' : '' ;
                    $result['html'] .= "<option value=\"" . $address_item["ShipCode"] . "\" " . $selected . ">" . $address_item["ShipName"] . "</option>";
                }

                $result['html'] .= "</select>
                        </div>
                    </div>
                    <div class=\"hr-line-dashed col-lg-12\"></div>";

            }
			
            if(!empty($contracts_arr))
            {
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Договор</label>
                                <div class=\"col-lg-6\">
                                     <select class=\"form-control\" id=\"ContractCode\"> <option value=\"-1\">Не указано</option>";

                foreach($contracts_arr as $contract) {
                    
                    $is_selected = false;
                    if(!empty($connection_options) && isset($connection_options['ContractCode']) && $connection_options['ContractCode'] == $contract["ContractCode"]) $is_selected = true;
                    $selected = $is_selected ? 'selected' : '' ;
                    $result['html'] .= "<option value=\"" . $contract["ContractCode"] . "\" " . $selected . ">" . $contract["ContractName"] . "</option>";
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
        			Настройки TMParts Личный кабинет
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