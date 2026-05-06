<?php
/**
 * Скрипт для обработки данных в личном кабинете Росско
*/

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

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

switch($request_object['action']) {
    case 'get_data':
        
        try
        {
            if(!(isset($request_object["login"]) && !empty($request_object["login"]))) {
                throw new Exception("Отсутствует login для подключения к Uniqom.");
            }
            if(!(isset($request_object["password"]) && !empty($request_object["password"]))) {
                throw new Exception("Отсутствует password для подключения к Uniqom.");
            }
            
            /*****Учетные данные*****/
            $login = $request_object["login"];
            $password = $request_object["password"];
            /*****Учетные данные*****/
            
            $url_params = http_build_query(array(
                'login' => $login, 
				'password' => $password ));
				
				
			$result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Uniqom Личный кабинет
        		</div>
        		<div class=\"panel-body\">";
        		
            $connection_options = isset($request_object["connection_options"]) ? $request_object["connection_options"] : array();
			
			// Получаем компании
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => "http://uniqom.ru/api/v2.1/companies.json?{$url_params}",
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$response = curl_exec($curl);

			curl_close($curl);
			
			$companies = json_decode($response, true);
			
			if(!empty($companies['data'])) {
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Список юридических лиц</label>
                                <div class=\"col-lg-6\">
                                     <select class=\"form-control\" id=\"company_id\">
                                     <option value=\"\">Не выбран</option>";

                foreach($companies['data'] as $company) {

                    $is_selected = false;
                    if(!empty($connection_options) && isset($connection_options['company_id']) && $connection_options['company_id'] == $company['id']) $is_selected = true;
                    $selected = $is_selected ? 'selected' : '' ;
                    $result['html'] .= "<option value=\"" . $company['id'] . "\" " . $selected . ">" . $company['name'] . "</option>";
                }

                $result['html'] .= "</select>
                        </div>
                    </div>
                    <div class=\"hr-line-dashed col-lg-12\"></div>";
			}
			
			//===============================================================================
		
			
			// Получаем Список торговых точек
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => "http://uniqom.ru/api/v2.1/stores.json?{$url_params}",
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$response = curl_exec($curl);

			curl_close($curl);
			
			$stores = json_decode($response, true);
			
			if(!empty($stores['data'])) {
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Список торговых точек</label>
                                <div class=\"col-lg-6\">
                                     <select class=\"form-control\" id=\"store_id\">
                                     <option value=\"\">Не выбран</option>";

                foreach($stores['data'] as $store) {

                    $is_selected = false;
                    if(!empty($connection_options) && isset($connection_options['store_id']) && $connection_options['store_id'] == $store['id']) $is_selected = true;
                    $selected = $is_selected ? 'selected' : '' ;
                    $result['html'] .= "<option value=\"" . $store['id'] . "\" " . $selected . ">" . $store['name'] . " (" .$store['address']. ")</option>";
                }

                $result['html'] .= "</select>
                        </div>
                    </div>
                    <div class=\"hr-line-dashed col-lg-12\"></div>";
			}
			
			
			
			// Получаем Список типов доставки
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => "http://uniqom.ru/api/v2.1/delivery.json?{$url_params}",
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$response = curl_exec($curl);

			curl_close($curl);
			
			$deliveries = json_decode($response, true);
			
			if(!empty($deliveries)) {
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Список типов доставки</label>
                                <div class=\"col-lg-6\">
                                     <select class=\"form-control\" id=\"delivery_id\">
                                     <option value=\"\">Не выбран</option>";

                foreach($deliveries as $delivery) {

                    $is_selected = false;
                    if(!empty($connection_options) && isset($connection_options['delivery_id']) && $connection_options['delivery_id'] == $delivery['id']) $is_selected = true;
                    $selected = $is_selected ? 'selected' : '' ;
                    $result['html'] .= "<option value=\"" . $delivery['id'] . "\" " . $selected . ">" . $delivery['name'] . "</option>";
                }

                $result['html'] .= "</select>
                        </div>
                    </div>
                    <div class=\"hr-line-dashed col-lg-12\"></div>";
			}
			
			
    	    // Тип оплаты
            $payments = array(
                0 => array("id" => 0, "name" => "Безналичный расчет (УПД)"),
                1 => array("id" => 1, "name" => "Наличный расчет (УПД)"),
                2 => array("id" => 2, "name" => "Наличный расчет (копия чека)")
                
            );
                
            if(!empty($payments)) {
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Тип оплаты</label>
                                <div class=\"col-lg-6\">
                                     <select class=\"form-control\" id=\"is_cash\">
                                     <option value=\"\">Не выбрана</option>";

                foreach($payments as $payment) {

                    $is_selected = false;
                    if(!empty($connection_options) && isset($connection_options['is_cash']) && $connection_options['is_cash'] == $payment['id']) $is_selected = true;
                    $selected = $is_selected ? 'selected' : '' ;
                    $result['html'] .= "<option value=\"" . $payment['id'] . "\" " . $selected . ">" . $payment['name'] . "</option>";
                }

                $result['html'] .= "</select>
                        </div>
                    </div>
                    <div class=\"hr-line-dashed col-lg-12\"></div>";
			}
			
			
			$result['html'] .= "</div></div>";
			
        }
        catch (Exception $e)//Не можем создать клиент SOAP
        {
            $result['status'] = false;
        	$result['message'] = "Ошибка: " . $e->getMessage();
        }
        
    
    break;
    
    case 'get_statuses':
            
        $supplier_statuses = array(
    	    0 => "Размещена",
            1 => "Принята",
            2 => "Подтверждена",
            3 => "В пути",
            5 => "Поступила на склад",
            6 => "Выполнена"
        );
        

        //Формируем ответ для вывода
        if(!empty($supplier_statuses)) {
            
            $result['html'] = "<div class=\"hpanel panel-collapse\">
    		<div class=\"panel-heading hbuilt\">
    			Статусы поставщика Uniqom
    			<div class=\"panel-tools\"><a class=\"showhide\"><i class=\"fa fa-chevron-down\" style=\"color:#000;\"></i></a></div>
    		</div>
    		<div class=\"panel-body\" style=\"display: none;\">
    		    <table class=\"table\" style=\"width: 600px;\"><tbody>
    		        <tr>
    		            <td style=\"font-weight: 600;\">Код статуса</td>
    		        </tr>";
    		

            foreach($supplier_statuses as $status_one_code => $status_one_text) {
                
                $result['html'] .= "<tr>
                    		            <td style=\"\">" .$status_one_text. "</td>
                    		        </tr>";
            }

    	    $result['html'] .= "</tbody></table></div></div>";
        		

        } else {
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Статусы Веб-сервис Uniqom
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