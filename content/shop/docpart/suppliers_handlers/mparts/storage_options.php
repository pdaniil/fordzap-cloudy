<?php
/**
 * Скрипт для обработки данных в личном кабинете Росско
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

$login = '';
$password =- '';

$paymentMethod = [];
$shipmentMethod = [];
$shipmentAddress = [];
$shipmentOffices = [];


switch($request_object['action']) {
    case 'get_data':
        
        try
        {
            if(!(isset($request_object["login"]) && !empty($request_object["login"]))) {
                throw new Exception("Отсутствует login для подключения к Mparts.");
            }
            if(!(isset($request_object["password"]) && !empty($request_object["password"]))) {
                throw new Exception("Отсутствует password для подключения к Rossko.");
            }
            
            /*****Учетные данные*****/
            $login = $request_object["login"];
            $password = md5($request_object["password"]);
            /*****Учетные данные*****/
			
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'http://v01.ru/api/devinsight/basket/paymentMethods?userlogin='.$login.'&userpsw=' . $password,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$response = curl_exec($curl);

			curl_close($curl);
			
			$paymentResponse = json_decode($response);
			
			foreach ($paymentResponse as $method)
				$paymentMethod[$method->id] = $method->name;
			
			
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'http://v01.ru/api/devinsight/basket/shipmentMethods?userlogin='.$login.'&userpsw=' . $password,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$response = curl_exec($curl);

			curl_close($curl);
			
			$shipmentResponse = json_decode($response);
			
			foreach ($shipmentResponse as $method)
				$shipmentMethod[$method->id] = $method->name;
			
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'http://v01.ru/api/devinsight/basket/shipmentAddresses?userlogin='.$login.'&userpsw=' . $password,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$response = curl_exec($curl);

			curl_close($curl);
			
			$shipmentResponse = json_decode($response);
			
			foreach ($shipmentResponse as $method)
				$shipmentAddress[$method->id] = $method->name;
				
				
		    $curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'https://v01.ru/api/devinsight/basket/shipmentOffices?userlogin='.$login.'&userpsw=' . $password,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$response = curl_exec($curl);

			curl_close($curl);
			
			$shipmentResponse = json_decode($response);
			
			foreach ($shipmentResponse as $method)
				$shipmentOffices[$method->id] = $method->name;
				
        	
        }
        catch (Exception $e)//Не можем создать клиент SOAP
        {
            $result['status'] = false;
        	$result['message'] = "Ошибка: " . $e->getMessage();
        }
        
        //Формируем ответ для вывода
        if($result['status']) {
            
            $connection_options = isset($request_object["connection_options"]) ? $request_object["connection_options"] : array();
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Mparts Личный кабинет
        		</div>
        		<div class=\"panel-body\">";
                
				
				//Способ оплаты
                if(!empty($paymentMethod)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Способ оплаты</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"paymentMethod\">
									<option>Не выбрано</option>
									";
                		            foreach($paymentMethod as $key => $method) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['paymentMethod']) && $connection_options['paymentMethod'] == $key) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $key . "\" " . $selected . " >" . $method . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                }
				//Способ оставки
                if(!empty($shipmentMethod)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Способ доставки</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"shipmentMethod\">
									<option>Не выбрано</option>
									";
                		            foreach($shipmentMethod as $key => $method) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['shipmentMethod']) && $connection_options['shipmentMethod'] == $key) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $key . "\" " . $selected . " >" . $method . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                }
				//Способ оплаты
                if(!empty($shipmentAddress)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Адрес доставки</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"shipmentAddress\">
									<option>Не выбрано</option>
									";
                		            foreach($shipmentAddress as $key => $method) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['shipmentAddress']) && $connection_options['shipmentAddress'] == $key) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $key . "\" " . $selected . " >" . $method . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                }
                if(!empty($shipmentOffices)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Офисы самовывоза</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"shipmentOffice\">
									<option>Не выбрано</option>
									";
                		            foreach($shipmentOffices as $key => $method) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['shipmentOffice']) && $connection_options['shipmentOffice'] == $key) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $key . "\" " . $selected . " >" . $method . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                }
                
                                
        	$result['html'] .= "</div></div>";
            
        } else {
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Mparts Личный кабинет
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