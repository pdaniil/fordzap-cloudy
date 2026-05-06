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
            $api_key = $request_object["api_key"];
            /*****Учетные данные*****/

           $curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'https://api.v-avto.ru/v1/orders/deliveries',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			  CURLOPT_HTTPHEADER => array(
				'X-Voshod-API-KEY: ' . $api_key
			  ),
			));

			$response = curl_exec($curl);

			curl_close($curl);

            $shipping_arr = json_decode($response, true);
			
            if (empty($shipping_arr["errors"]))
            {
                throw new Exception($response);
            }
        }
        catch (Exception $e)
        {
            $result['status'] = false;
            $result['message'] = "Ошибка: " . $e->getMessage();
        }

        //Формируем ответ для вывода
        if(isset($shipping_arr['response']["deliveries"]))
        {

            $connection_options = isset($request_object["connection_options"]) ? $request_object["connection_options"] : array();

            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Восход Личный кабинет
        		</div>
        		<div class=\"panel-body\">";
			
			if ($connection_options['delivery_type'] == 0)
				$self = 'selected';
			else
				$adress = 'selected';
			
			$result['html'] .= "<div class=\"form-group\">
							<label class=\"col-lg-6 control-label\">Способ получения</label>
							<div class=\"col-lg-6\">
								 <select class=\"form-control\" id=\"delivery_type\"> <option ". $self. " value=\"0\">Самовывоз</option><option " . $adress ." value=\"1\">Доставка по адресу</option>";
			$result['html'] .= "</select>
					</div>
				</div>
				<div class=\"hr-line-dashed col-lg-12\"></div>";

            
			
           
			$result['html'] .= "<div class=\"form-group\">
							<label class=\"col-lg-6 control-label\">Адрес доставки</label>
							<div class=\"col-lg-6\">
								 <select class=\"form-control\" id=\"delivery_address\"> <option value=\"-1\">Не указано</option>";

			foreach($shipping_arr['response']["deliveries"] as $contract) {
				
				$is_selected = false;
				if(!empty($connection_options) && isset($connection_options['delivery_address']) && $connection_options['delivery_address'] == $contract["name"]) $is_selected = true;
				$selected = $is_selected ? 'selected' : '' ;
				$result['html'] .= "<option value=\"" . $contract["name"] . "\" " . $selected . ">" . $contract["name"] . "</option>";
			}

			$result['html'] .= "</select>
					</div>
				</div>
				<div class=\"hr-line-dashed col-lg-12\"></div>";
            

            $result['html'] .= "</div><div class=\"hr-line-dashed col-lg-12\"></div>";
            $result['html'] .= "</div></div>";
        }
        else
        {

            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Восход Личный кабинет
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