<?php
/**
 * Скрипт для обработки данных в личном кабинете Росско
*/

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

$supplier_dir = $_SERVER['DOCUMENT_ROOT'] . '/content/shop/docpart/suppliers_handlers/abcp';

require_once( $supplier_dir . "/ABCPSAO.php" );

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
            if(!(isset($request_object["login"]) && !empty($request_object["login"]))) {
                throw new Exception("Отсутствует Логин для подключения к ABCP.");
            }
            if(!(isset($request_object["password"]) && !empty($request_object["password"]))) {
                throw new Exception("Отсутствует Пароль для подключения к ABCP.");
            }
            if(!(isset($request_object["subdomain"]) && !empty($request_object["subdomain"]))) {
                throw new Exception("Отсутствует Поддомен для подключения к ABCP.");
            }
			
            /*****Учетные данные*****/
            $login = $request_object["login"];
            $password = md5($request_object["password"]);
            $subdomain = $request_object["subdomain"];
           
            /*****Учетные данные*****/
            
			
			$abcp_sao = new ABCPSAO( $login, $password, $subdomain );
			
			$payments	= $abcp_sao->getPaymentMethods();
		
			$shipments	= $abcp_sao->getShipmentMethods();
			
			$offices	= $abcp_sao->getShipmentOffices();
			
			$addresses	= $abcp_sao->getShipmentAddresses();
			
			$dates		= $abcp_sao->getShipmentDates();
			
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
        			Настройки ABCP Личный кабинет
        		</div>
        		<div class=\"panel-body\">";

                
                //Способы оплаты
                if(!empty($payments)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Способ оплаты</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"payment_id\"><option value=\"\">Не выбран</option>";
                		            
                		            foreach($payments as $payment_item) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['payment_id']) && $connection_options['payment_id'] == $payment_item['id']) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $payment_item['id'] . "\" " . $selected . ">" . $payment_item['name'] . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                }      

				  //Способы доставки
                if(!empty($shipments)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Способ доставки</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"shipment_id\"><option value=\"\">Не выбран</option>";
                		            
                		            foreach($shipments as $item) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['shipment_id']) && $connection_options['shipment_id'] == $item['id']) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $item['id'] . "\" " . $selected . ">" . $item['name'] . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                } 
				
				if(!empty($offices)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Офис</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"office_id\"><option value=\"\">Не выбран</option>";
                		            
                		            foreach($offices as $item) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['office_id']) && $connection_options['office_id'] == $item['id']) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $item['id'] . "\" " . $selected . ">" . $item['name'] . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                }
				
				if(!empty($addresses)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Адрес</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"address_id\"><option value=\"\">Не выбран</option>";
                		            
                		            foreach($addresses as $item) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['address_id']) && $connection_options['address_id'] == $item['id']) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $item['id'] . "\" " . $selected . ">" . $item['name'] . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                }
				
				if(false) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Дата</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"date_id\"><option value=\"\">Не выбран</option>";
                		            
                		            foreach($dates as $item) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['date_id']) && $connection_options['date_id'] == $item['id']) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $item['id'] . "\" " . $selected . ">" . $item['name'] . "</option>";
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
        			Настройки ABCP Личный кабинет
        		</div>
        		<div class=\"panel-body\"><div class=\"alert alert-danger\">" . $result['message'] . "</div></div>
        	</div>";
        	
        }
    
    break;
    
    case 'get_statuses':
            $supplier_statuses = array(
      
    );
         $supplier_statuses = array(
            2 => "Отправлен поставщику",
            3 => "Пришло на склад",
            4 => "Подтверждено поставщиком",
            5 => "Выдано",
            6 => "Отказ",
            8 => "В работе",
            10 => "Получен",
    	    1803 => "Получен",
            1804 => "В работе",
            1805 => "Пришло на склад",
            1806 => "Выдано",
            1807 => "Задержка поставщики",
            1808 => "Отказ",
            64222 => "Технический",
            68891 => "Обрабатывается",
            129198 => "Возврат"
        );
        

        //Формируем ответ для вывода
        if(!empty($supplier_statuses)) {
            
            $result['html'] = "<div class=\"hpanel panel-collapse\">
    		<div class=\"panel-heading hbuilt\">
    			Статусы поставщика ABCP
    			<div class=\"panel-tools\"><a class=\"showhide\"><i class=\"fa fa-chevron-down\" style=\"color:#000;\"></i></a></div>
    		</div>
    		<div class=\"panel-body\" style=\"display: none;\">
    		    <table class=\"table\" style=\"width: 600px;\"><tbody>
    		        <tr>
    		            <td style=\"font-weight: 600;\">Наименование статуса</td>
    		            <td style=\"font-weight: 600;\">Код статуса</td>
    		        </tr>";
    		

            foreach($supplier_statuses as $status_one_code => $status_one_text) {
                
                $result['html'] .= "<tr>
                    		            <td style=\"\">" .$status_one_text. "</td>
                    		            <td style=\"\">" .$status_one_code. "</td>
                    		        </tr>";
            }

    	    $result['html'] .= "</tbody></table></div></div>";
        		

        } else {
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Статусы Веб-сервис ABCP
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