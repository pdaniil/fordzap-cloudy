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
            
        $supplier_statuses = array(
    	    1 => "Новый заказ",
            3 => "Размещено",
            4 => "Закуплено",
            5 => "В дороге",
            6 => " К выдаче",
            7 => "Выдано",
            8 => "Отказ",
            9 => "Зарезервировано (позициия в статусах, кроме Отказ и Выдано)",
            0 => "Все состояния"
        );
        
        //Формируем ответ для вывода
        if(!empty($supplier_statuses)) {
            
            $result['html'] = "<div class=\"hpanel panel-collapse\">
    		<div class=\"panel-heading hbuilt\">
    			Статусы поставщика Froza
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
        			Статусы Веб-сервис Froza
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