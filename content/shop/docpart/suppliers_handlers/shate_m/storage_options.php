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
$connection_options = isset($request_object["connection_options"]) ? $request_object["connection_options"] : array();

switch($request_object['action']) {
    case 'get_data':
        
        try
        {

          $api_path = "";
	    		switch($connection_options["country"])
	    		{
	    			case "ru":
	    				$api_path = "https://api.shate-m.ru/api/v1";
	    				break;
	    			case "by":
	    				$api_path = "https://api.shate-m.by/api/v1";
	    				break;
	    			case "kz":
	    				$api_path = "https://api.shate-m.kz/api/v1";
	    				break;
	    			default: $api_path = "https://api.shate-m.ru/api/v1";
	    		}
    		
				    		if(!empty($request_object['login']) && !empty($request_object['password'])) {
				    			$url = $api_path . '/auth/login';
				    			$auth_fields = array(
				    				'Login' => $request_object['login'],
				    				'Password' => $request_object['password']
				    			);
				    		} else if(!empty($request_object["api_key"])) {
				    			$url = $api_path . '/auth/loginbyapikey';
				    			$auth_fields = array(
				    				'ApiKey' => $request_object['api_key']
				    			);
				    		} else {
				    		    throw new Exception("Отсутствует Логин и Пароль или Api-Key для авторизации в Шате-М.");
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
				    		
				    		$curl_json = curl_exec($ch);
				    
				    		$curl_result = json_decode($curl_json, true);
				    		
				    		curl_close($ch);
				    		
				    		$token = "";
				    
				    		if(!empty($curl_result['access_token'])) {
				    		    
				    			$token = $curl_result['access_token'];
				    			
				    		} else {
				    			throw new Exception("Ошибка получения токена. " . $url);
				    		}
				    		
				    		
				    		//Получаем договор
				    		$ch = curl_init(); 
				    		curl_setopt($ch, CURLOPT_URL, $api_path . "/customer/agreements");
				    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
				    		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата
				    
				    		curl_setopt($ch, CURLOPT_POST, false);
				    		curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_HTTPHEADER, array(
								"Content-Type: application/json",
								"Authorization: Bearer $token"
							));
				    		
				    		$curl_json = curl_exec($ch);
				    
				    		$agreements = json_decode($curl_json, true);
				    		
				    		curl_close($ch);
				    		
				    		
				    		$result['html'] = "<div class=\"hpanel\">
				        		<div class=\"panel-heading hbuilt\">
				        			Настройки Шате-М Личный кабинет
				        		</div>
				        		<div class=\"panel-body\">";


				            $AgreementCodeGroup = '';
				            if(!empty($connection_options) && isset($connection_options['AgreementCodeGroup'])) $AgreementCodeGroup = $connection_options['AgreementCodeGroup'];
				            if(!empty($agreements))
				            {
				                $result['html'] .= "<div class=\"form-group\">
				                                <label class=\"col-lg-6 control-label\">Внутренний номер договора</label>
				                                <div class=\"col-lg-6\">
				                                     <input type=\"hidden\" id=\"AgreementCodeGroup\" value=\"" . $AgreementCodeGroup . "\" />
				                                     <select class=\"form-control\" id=\"AgreementCode\">
				                                     <option value=\"\">Не выбран</option>";

				                foreach($agreements as $agreement_item) {
				                    $agreement_item_code = $agreement_item['code'];
				                    $agreement_item_group = $agreement_item['agreementGroup'];
				                    $is_selected = false;
				                    if(!empty($connection_options) && isset($connection_options['AgreementCode']) && $connection_options['AgreementCode'] == $agreement_item_code) $is_selected = true;
				                    $selected = $is_selected ? 'selected' : '' ;
				                    $result['html'] .= "<option data-code-group=\"$agreement_item_group\" value=\"" . $agreement_item_code . "\" " . $selected . ">" . $agreement_item_code . "</option>";
				                }

				                $result['html'] .= "</select>
				                        </div>
				                    </div>
				                    <div class=\"hr-line-dashed col-lg-12\"></div>";

				            }
				            
				            $delivery = array('pickup' => 'Самовывоз', 'courier' => 'Доставка по адресу');
				            if(!empty($delivery))
				            {
				                $result['html'] .= "<div class=\"form-group\">
				                                <label class=\"col-lg-6 control-label\">Способ доставки</label>
				                                <div class=\"col-lg-6\">
				                                     <select class=\"form-control\" id=\"delivery\">
				                                     <option value=\"\">Не выбрана</option>";

				                foreach($delivery as $delivery_item_key =>$delivery_item) {
				    
				                    $is_selected = false;
				                    if(!empty($connection_options) && isset($connection_options['delivery']) && $connection_options['delivery'] == $delivery_item_key) $is_selected = true;
				                    $selected = $is_selected ? 'selected' : '' ;
				                    $result['html'] .= "<option value=\"" . $delivery_item_key . "\" " . $selected . ">" . $delivery_item . "</option>";
				                }

				                $result['html'] .= "</select>
				                        </div>
				                    </div>
				                    <div class=\"hr-line-dashed col-lg-12\"></div>";

				            }
				            
				            
				            //Получаем адреса доставки
				    		$ch = curl_init(); 
				    		curl_setopt($ch, CURLOPT_URL, $api_path . "/delivery/addresses");
				    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
				    		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата
				    
				    		curl_setopt($ch, CURLOPT_POST, false);
				    		curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_HTTPHEADER, array(
								"Content-Type: application/json",
								"Authorization: Bearer $token"
							));
				    		
				    		$curl_json = curl_exec($ch);
				    
				    		$addresses = json_decode($curl_json, true);
				    		
				    		curl_close($ch);
				    		
				    		
				            if(!empty($addresses))
				            {
				                $result['html'] .= "<div class=\"form-group\">
				                                <label class=\"col-lg-6 control-label\">Код адреса доставки</label>
				                                <div class=\"col-lg-6\">
				                                     <select class=\"form-control\" id=\"DeliveryAddressCode\">
				                                     <option value=\"\">Не выбран</option>";

				                foreach($addresses as $address) {
				                    $address_code = $address['code'];
				                    $address_city = $address['city'];
				                    $address_path = $address['address'];
				                    
				                    $address_full_path = $address['city'] . ", " . $address['address'];

				                    $is_selected = false;
				                    if(!empty($connection_options) && isset($connection_options['DeliveryAddressCode']) && $connection_options['DeliveryAddressCode'] == $address_code) $is_selected = true;
				                    $selected = $is_selected ? 'selected' : '' ;
				                    $result['html'] .= "<option value=\"" . $address_code . "\" " . $selected . ">" . $address_full_path . "</option>";
				                }

				                $result['html'] .= "</select>
				                        </div>
				                    </div>
				                    <div class=\"hr-line-dashed col-lg-12\"></div>";

				            }
				            
				            
				            $deliveryType = array('ManyDelivery' => 'Несколькими доставками по мере поступления товара', 'OneDelivery' => 'Одной доставкой (по самой поздней дате доставки в строке заказа)');
				            if(!empty($delivery))
				            {
				                $result['html'] .= "<div class=\"form-group\">
				                                <label class=\"col-lg-6 control-label\">Тип доставки</label>
				                                <div class=\"col-lg-6\">
				                                     <select class=\"form-control\" id=\"DeliveryType\">
				                                     <option value=\"\">Не выбран</option>";

				                foreach($deliveryType as $deliveryType_key =>$deliveryType_item) {
				    
				                    $is_selected = false;
				                    if(!empty($connection_options) && isset($connection_options['DeliveryType']) && $connection_options['DeliveryType'] == $deliveryType_key) $is_selected = true;
				                    $selected = $is_selected ? 'selected' : '' ;
				                    $result['html'] .= "<option value=\"" . $deliveryType_key . "\" " . $selected . ">" . $deliveryType_item . "</option>";
				                }

				                $result['html'] .= "</select>
				                        </div>
				                    </div>
				                    <div class=\"hr-line-dashed col-lg-12\"></div>";

				            }
				            
				            
				            //Контактный номер
				            $phone = '';
				            if(!empty($connection_options) && isset($connection_options['phone'])) $phone = $connection_options['phone'];

				            $result['html'] .= "<div class=\"form-group\">
				                            <label class=\"col-lg-6 control-label\">Контактный номер</label>
				            		        <div class=\"col-lg-6\">
				            		            <input class=\"form-control\" type=\"text\" id=\"phone\" value=\"" . $phone . "\" />
				            		        </div>
				            		    </div>
				        		        <div class=\"hr-line-dashed col-lg-12\"></div>";
				        		        
				            //ФИО покупателя
				            $fio = '';
				            if(!empty($connection_options) && isset($connection_options['fio'])) $fio = $connection_options['fio'];

				            $result['html'] .= "<div class=\"form-group\">
				                            <label class=\"col-lg-6 control-label\">ФИО покупателя</label>
				            		        <div class=\"col-lg-6\">
				            		            <input class=\"form-control\" type=\"text\" id=\"fio\" value=\"" . $fio . "\" />
				            		        </div>
				            		    </div>
				        		        <div class=\"hr-line-dashed col-lg-12\"></div>";
				            
				            
				    // 		echo "<pre>";
				    // 		print_r($curl_result);
				    // 		echo "</pre>";
				    // 		echo "<br>";   

        }
        catch (Exception $e)
        {
            $result['status'] = false;
            $result['message'] = "Ошибка: " . $e->getMessage();
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Шате-М Личный кабинет
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