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
			  CURLOPT_URL => 'https://ws.a-trast.ru/v1/user/info?expand=available_suppliers,warehouses,delivery_addresses',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $api_key,
			  ),
			));
			
			$response = curl_exec($curl);
			
// 			echo "<per>";
// 			print_r($response);
// 			echo "</pre>";

			curl_close($curl);
			

            $result = json_decode($response, true);
        }
        catch (Exception $e)
        {
            $result['status'] = false;
            $result['message'] = "Ошибка: " . $e->getMessage();
        }
        
        $check_html = false;

        //Формируем ответ для вывода
        if(count($result['contracts']) > 0)
        {

            $check_html = true;
            $connection_options = isset($request_object["connection_options"]) ? $request_object["connection_options"] : array();

            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Атраст Личный кабинет
        		</div>
        		<div class=\"panel-body\">";
           
			$result['html'] .= "<div class=\"form-group\">
							<label class=\"col-lg-6 control-label\">Список доступных договоров</label>
							<div class=\"col-lg-6\">
								 <select class=\"form-control\" id=\"contract_guid\"> <option value=\"-1\">Не указано</option>";

			foreach($result['contracts'] as $contract) {
				
				$is_selected = false;
				if(!empty($connection_options) && isset($connection_options['contract_guid']) && $connection_options['contract_guid'] == $contract["guid"]) $is_selected = true;
				$selected = $is_selected ? 'selected' : '' ;
				$result['html'] .= "<option value=\"" . $contract["guid"] . "\" " . $selected . ">" . $contract["title"] . "</option>";
			}

			$result['html'] .= "</select>
					</div>
				</div>
				<div class=\"hr-line-dashed col-lg-12\"></div>";
				
        }
        
        
        //Формируем ответ для вывода
        if(count($result['available_suppliers']) > 0)
        {
				
			$check_html = true;	
			$result['html'] .= "<div class=\"form-group\">
				<label class=\"col-lg-6 control-label\">Список доступных поставщиков</label>
				<div class=\"col-lg-6\">
					 <select class=\"form-control\" id=\"supplier_id\"> <option value=\"-1\">Не указано</option>";

			foreach($result['available_suppliers'] as $supplier) {
				
				$is_selected = false;
				if(!empty($connection_options) && isset($connection_options['supplier_id']) && $connection_options['supplier_id'] == $supplier["id"]) $is_selected = true;
				$selected = $is_selected ? 'selected' : '' ;
				$result['html'] .= "<option value=\"" . $supplier["id"] . "\" " . $selected . ">" . $supplier["title"] . "</option>";
			}

			$result['html'] .= "</select>
					</div>
				</div>
				<div class=\"hr-line-dashed col-lg-12\"></div>";
				
        }
        
                //Формируем ответ для вывода
        if(count($result['delivery_addresses']) > 0)
        {
			$check_html = true;	
	        $result['html'] .= "<div class=\"form-group\">
				<label class=\"col-lg-6 control-label\">Список доступных адресов доставки</label>
				<div class=\"col-lg-6\">
					 <select class=\"form-control\" id=\"address_guid\"> <option value=\"-1\">Не указано</option>";

			foreach($result['delivery_addresses'] as $address) {
				
				$is_selected = false;
				if(!empty($connection_options) && isset($connection_options['address_guid']) && $connection_options['address_guid'] == $address["guid"]) $is_selected = true;
				$selected = $is_selected ? 'selected' : '' ;
				$result['html'] .= "<option value=\"" . $address["guid"] . "\" " . $selected . ">" . $address["title"] . "</option>";
			}

			$result['html'] .= "</select>
					</div>
				</div>
				<div class=\"hr-line-dashed col-lg-12\"></div>";
        }
        
        //Формируем ответ для вывода
        if(count($result['warehouses']) > 0)
        {
				$check_html = true;
			        $result['html'] .= "<div class=\"form-group\">
				<label class=\"col-lg-6 control-label\">Список доступных РЦ</label>
				<div class=\"col-lg-6\">
					 <select class=\"form-control\" id=\"warehouse_guid\"> <option value=\"-1\">Не указано</option>";

			foreach($result['warehouses'] as $warehouse) {
				
				$is_selected = false;
				if(!empty($connection_options) && isset($connection_options['warehouse_guid']) && $connection_options['warehouse_guid'] == $warehouse["guid"]) $is_selected = true;
				$selected = $is_selected ? 'selected' : '' ;
				$result['html'] .= "<option value=\"" . $warehouse["guid"] . "\" " . $selected . ">" . $warehouse["title"] . "</option>";
			}

			$result['html'] .= "</select>
					</div>
				</div>
				<div class=\"hr-line-dashed col-lg-12\"></div>";
            

            $result['html'] .= "</div><div class=\"hr-line-dashed col-lg-12\"></div>";
            $result['html'] .= "</div></div>";
        }
        
        if($check_html !== true)
        {

            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Атраст Личный кабинет
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