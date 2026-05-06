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

switch($request_object['action']) {

    case 'get_statuses':
            
        /*****Учетные данные*****/
				$token = $request_object['connection_options']["token"];
				/*****Учетные данные*****/

				$url = "https://api.tehnomir.com.ua/info/getPositionStatuses";


				$postdata = array(
					"apiToken" => $token
			);

				$headers = array(
					"Content-Type: application/json",
					"accept: application/json"
				);


				$postdata = json_encode($postdata);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
				curl_setopt($ch, CURLOPT_VERBOSE, 1 );
				$execute = curl_exec($ch);
				$data = json_decode($execute, true);
			
		
				if($data['success'] !== false) {

	        //Формируем ответ для вывода
	        if(!empty($data['data'])) {
	            
	            $result['html'] = "<div class=\"hpanel panel-collapse\">
	    		<div class=\"panel-heading hbuilt\">
	    			Статусы поставщика Tehnomir
	    			<div class=\"panel-tools\"><a class=\"showhide\"><i class=\"fa fa-chevron-down\" style=\"color:#000;\"></i></a></div>
	    		</div>
	    		<div class=\"panel-body\" style=\"display: none;\">
	    		    <table class=\"table\" style=\"width: 600px;\"><tbody>
	    		        <tr>
	    		            <td style=\"font-weight: 600;\">Наименование статуса</td>
	    		            <td style=\"font-weight: 600;\">Код статуса</td>
	    		            <td style=\"font-weight: 600;\">Описание</td>
	    		        </tr>";
	    		

	            foreach($data['data'] as $status_one) {
	                
	                $result['html'] .= "<tr>
	                    		            <td style=\"\">" .$status_one['status']. "</td>
	                    		            <td style=\"\">" .$status_one['statusId']. "</td>
																			<td style=\"\">" .$status_one['description']. "</td>
	                    		        </tr>";
	            }

	    	    $result['html'] .= "</tbody></table></div></div>";
	        		

	        } else {
	            
	            $result['html'] = "<div class=\"hpanel\">
	        		<div class=\"panel-heading hbuilt\">
	        			Статусы Веб-сервис Tehnomir
	        		</div>
	        		<div class=\"panel-body\"><div class=\"alert alert-danger\">" . $result['message'] . "</div></div>
	        	</div>";
	        	
	        }
				}
    
    break;
    
    
    default:
        $result['status'] = false;
        $result['message'] = 'Неизвестное действие';
}




exit(json_encode($result));