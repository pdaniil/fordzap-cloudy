<?php 
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
	
	
	/*****Учетные данные*****/
		$key = $request_object["key"];
	/************/
	
	
	$request_object['action'] = 'get_data';
	switch($request_object['action']) {
		case 'get_data':
			
			try
			{
				if(!isset($request_object["key"])) {
					throw new Exception("Отсутствует API Ключ для подключения к Berg.");
				}
				$curl = curl_init();
				
				curl_setopt_array($curl, array(
				  CURLOPT_URL => 'https://api.berg.ru/references/shipment_address/active.json?key=' . $key,
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => '',
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 0,
				  CURLOPT_FOLLOWLOCATION => true,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => 'GET',
				));

				$response = curl_exec($curl);

				curl_close($curl);
				$data = json_decode( $response );
				$connection_options = isset($request_object["connection_options"]) ? $request_object["connection_options"] : array();
				
				$places = $data->shipment_address_list;
				 $result['html'] = "<div class=\"hpanel\">
					<div class=\"panel-heading hbuilt\">
						Настройки Berg Личный кабинет
					</div>
					<div class=\"panel-body\">";
				
				if(!empty($places)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Компания</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"shipment_address_id\">";
                		            $result['html'] .= "<option value=\"\">Не выбрано</option>";
                		            foreach($places as $place) {
                    		              $is_selected = false;
                    		              if(!empty($connection_options) && isset($connection_options['shipment_address_id']) && $connection_options['shipment_address_id'] == $place->id) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $place->id . "\" " . $selected . " >" . $place->address . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>";
                }
                
                // Тип оплаты
                $result['html'] .= "<div class=\"form-group\">
                    <label class=\"col-lg-6 control-label\">Тип оплаты</label>
    		        <div class=\"col-lg-6\">
    		            <select class=\"form-control\" id=\"payment_type\">";
    		            $result['html'] .= "<option value=\"\">Не выбрано</option>";
    		            
    		            $payment_types = array(
    		                0 => array('id' => 1, 'value' => 'Наличный расчет'),
    		                1 => array('id' => 2, 'value' => 'Безналичный расчет')
    		               );
    		            
    		            foreach($payment_types as $payment_type) {
        		              $is_selected = false;
        		              if(!empty($connection_options) && isset($connection_options['payment_type']) && $connection_options['payment_type'] == $payment_type['id']) $is_selected = true;
        		              $selected = $is_selected ? 'selected' : '' ;
        		              $result['html'] .= "<option value=\"" . $payment_type['id'] . "\" " . $selected . " >" . $payment_type['value'] . "</option>";
                		        }
                		        
                $result['html'] .= "</select>
        		        </div>
    		        </div>
    		        <div class=\"hr-line-dashed col-lg-12\"></div>";
    		    //========================================================================================
                
                
                // Тип отгрузки
                $result['html'] .= "<div class=\"form-group\">
                    <label class=\"col-lg-6 control-label\">Тип отгрузки</label>
    		        <div class=\"col-lg-6\">
    		            <select class=\"form-control\" id=\"dispatch_type\">";
    		            $result['html'] .= "<option value=\"\">Не выбрано</option>";
    		            
    		            $dispatch_types = array(
    		                0 => array('id' => 2, 'value' => 'Самовывоз'),
    		                1 => array('id' => 3, 'value' => 'Доставка'),
    		                2 => array('id' => 60580, 'value' => 'Срочная доставка')
    		              );

    		            foreach($dispatch_types as $dispatch_type) {
        		              $is_selected = false;
        		              if(!empty($connection_options) && isset($connection_options['dispatch_type']) && $connection_options['dispatch_type'] == $dispatch_type['id']) $is_selected = true;
        		              $selected = $is_selected ? 'selected' : '' ;
        		              $result['html'] .= "<option value=\"" . $dispatch_type['id'] . "\" " . $selected . " >" . $dispatch_type['value'] . "</option>";
                		        }
                		        
                $result['html'] .= "</select>
        		        </div>
    		        </div>
    		        <div class=\"hr-line-dashed col-lg-12\"></div>";
    		    //========================================================================================
    		    
    		    // Дата доставки
                $result['html'] .= "<div class=\"form-group\">
                    <label class=\"col-lg-6 control-label\">Дата доставки</label>
    		        <div class=\"col-lg-6\">
    		            <select class=\"form-control\" id=\"dispatch_at\">";
    		            $result['html'] .= "<option value=\"\">Не выбрано</option>";
    		            
    		            $dispatch_ats = array(
    		                0 => array('id' => 1, 'value' => 'На следующий день'),
    		                1 => array('id' => 2, 'value' => 'Через день')
    		              );

    		            foreach($dispatch_ats as $dispatch_at) {
        		              $is_selected = false;
        		              if(!empty($connection_options) && isset($connection_options['dispatch_at']) && $connection_options['dispatch_at'] == $dispatch_at['id']) $is_selected = true;
        		              $selected = $is_selected ? 'selected' : '' ;
        		              $result['html'] .= "<option value=\"" . $dispatch_at['id'] . "\" " . $selected . " >" . $dispatch_at['value'] . "</option>";
                		        }
                		        
                $result['html'] .= "</select>
        		        </div>
    		        </div>
    		        <div class=\"hr-line-dashed col-lg-12\"></div>";
    		    //========================================================================================
    		    
                // Флаг времеми доставки
                $result['html'] .= "<div class=\"form-group\">
                    <label class=\"col-lg-6 control-label\">Флаг времеми доставки</label>
    		        <div class=\"col-lg-6\">
    		            <select class=\"form-control\" id=\"dispatch_time\">";
    		            $result['html'] .= "<option value=\"\">Не выбрано</option>";
    		            
    		            $dispatch_times = array(
    		                0 => array('id' => 1, 'value' => 'до 15:00'),
    		                1 => array('id' => 2, 'value' => 'после 15:00')
    		              );

    		            foreach($dispatch_times as $dispatch_time) {
        		              $is_selected = false;
        		              if(!empty($connection_options) && isset($connection_options['dispatch_time']) && $connection_options['dispatch_time'] == $dispatch_time['id']) $is_selected = true;
        		              $selected = $is_selected ? 'selected' : '' ;
        		              $result['html'] .= "<option value=\"" . $dispatch_time['id'] . "\" " . $selected . " >" . $dispatch_time['value'] . "</option>";
                		        }
                		        
                $result['html'] .= "</select>
        		        </div>
    		        </div>
    		        <div class=\"hr-line-dashed col-lg-12\"></div>";
    		    //========================================================================================
    		    
    		    // ФИО покупателя
                $person = '';
                if(!empty($connection_options) && isset($connection_options['person'])) $person = $connection_options['person'];

                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Контактное лицо</label>
                		        <div class=\"col-lg-6\">
                		            <input class=\"form-control\" type=\"text\" id=\"person\" value=\"" . $person . "\" />
                		        </div>
                		    </div>
            		        <div class=\"hr-line-dashed col-lg-12\"></div>";
            	//========================================================================================
            	
                // Контактный телефон
                $phone = '';
                if(!empty($connection_options) && isset($connection_options['phone'])) $phone = $connection_options['phone'];

                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Контактный телефон</label>
                		        <div class=\"col-lg-6\">
                		            <input class=\"form-control\" type=\"text\" id=\"phone\" value=\"" . $phone . "\" />
                		        </div>
                		    </div>
            		        <div class=\"hr-line-dashed col-lg-12\"></div>";
            	//========================================================================================
            	
            	
            	// Комментарий
                $comment = '';
                if(!empty($connection_options) && isset($connection_options['comment'])) $comment = $connection_options['comment'];

                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Комментарий</label>
                		        <div class=\"col-lg-6\">
                		            <input class=\"form-control\" type=\"text\" id=\"comment\" value=\"" . $comment . "\" />
                		        </div>
                		    </div>
            		        <div class=\"hr-line-dashed col-lg-12\"></div>";
            	//========================================================================================
            	
                $is_test = false;
                if(!empty($connection_options) && isset($connection_options['is_test'])) $is_test = $connection_options['is_test'];

                $checked = $is_test ? "checked" : "";
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Тестовый заказ</label>
                		        <div class=\"col-lg-6\">
                		            <input class=\"form-control\" type=\"checkbox\" id=\"is_test\" $checked />
                		        </div>
                		    </div>
            		        <div class=\"hr-line-dashed col-lg-12\"></div>";
				
				$result['html'] .= "</div></div>";
				
			}
			catch (Exception $e)//Не можем создать клиент SOAP
			{
				$result['status'] = false;
				$result['message'] = "Ошибка: " . $e->getMessage();
			}
		break;
		default:
			$result['status'] = false;
			$result['message'] = 'Неизвестное действие';
		break;
	}
	
	exit(json_encode($result));