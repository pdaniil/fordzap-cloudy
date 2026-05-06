<?php
// http://api.demo.abcp.ru/orders/instant
ob_start();

$supplier_dir = $_SERVER['DOCUMENT_ROOT'] . '/content/shop/docpart/suppliers_handlers/abcp';

require_once( $supplier_dir . "/ABCPSAO.php" ); 
require_once( $supplier_dir . "/ABPCOrder.php" ); 

$sao_result = array();
$sao_result["status"] = false;
$sao_result_message = "";

$login = $connection_options["login"];
$password = md5( $connection_options["password"] );
$subdomain = $connection_options["subdomain"];

$order_items_base = array();

try {
	
	$abcp_sao = new ABCPSAO ( $login, $password, $subdomain );

	$abcp_order = null;
	
	if(isset($multi_order) && $multi_order === true) {

				$abcp_order = new ABCPOrder ( $abcp_sao, $order_items[0], $db_link ); //Сформировали данные заказа без описания позиции
        
        foreach($order_items as $order_item) {
          
            $t2_json_params = json_decode($order_item["t2_json_params"], true); //Специальные параметры
            
            $supplierCode = $t2_json_params['supplierCode'];
            $order_item_id = $order_item['id'];
    	    
            $position = array( 
              'brand' => $t2_json_params["brand"],
            	'number' => $t2_json_params['number'],
            	'itemKey' => $t2_json_params['itemKey'],
            	'supplierCode' => $supplierCode, 
            	'quantity' => $order_item['count_need'],
            	'comment' => $order_item_id
            ); //описание объекта, добавляемого в корзину.
            					
            $order_items_base[$supplierCode][$order_item_id] = array(
              'brand' => $t2_json_params["brand"],
            	'number' => $t2_json_params['number'],
            	'itemKey' => $t2_json_params['itemKey'],
            	'supplierCode' => $supplierCode, 
            	'quantity' => $order_item['count_need'],
            	'comment' =>  $order_item_id   
            );
						
	    			$abcp_order->addPosition( $position ); //Добавили позцию
            
        }
        
    } else {

				$abcp_order = new ABCPOrder ( $abcp_sao, $order_item, $db_link ); //Сформировали данные заказа без описания позиции
        
        $t2_json_params = json_decode($order_item["t2_json_params"], true); //Специальные параметры
        
        $supplierCode = $t2_json_params['supplierCode'];
        $order_item_id = $order_item['id'];
	    
        $position = array( 
          'brand' => $t2_json_params["brand"],
        	'number' => $t2_json_params['number'],
        	'itemKey' => $t2_json_params['itemKey'],
        	'supplierCode' => $supplierCode, 
        	'quantity' => $order_item['count_need'],
        	'comment' => $order_item_id
        ); //описание объекта, добавляемого в корзину.
        

        $order_items_base[$supplierCode][$order_item_id] = array(
          'brand' => $t2_json_params["brand"],
        	'number' => $t2_json_params['number'],
        	'itemKey' => $t2_json_params['itemKey'],
        	'supplierCode' => $supplierCode, 
        	'quantity' => $order_item['count_need'],
        	'comment' =>  $order_item_id   
        );
        
				$abcp_order->addPosition( $position ); //Добавили позцию
        
    }
	
		echo "<pre>";
    echo date('Y-m-d H:i:s');
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    echo "createOrder";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($abcp_order);
    echo "</pre>";
    echo "</br>";

		$result_order = $abcp_sao->createOrder( $abcp_order ); //Создаём заказ
	
    echo "<pre>";
    echo "Результат";
    echo "</pre>";
    echo "</br>";
    echo "<pre>";
    print_r($result_order);
    echo "</pre>";
    echo "</br>";
	
		$status_order = $result_order['status'];
	
		if ( $status_order == 1 ) {
			
			$orders = $result_order['orders'];
			
      if(!empty($orders)) {
          
          $new_sao_state = 2; //Состояние "Заказано"
          
          foreach($orders as $order_id => $order_data) {
              
              //Меняем SAO-Состояние

      				$order_status_id = $order_data['statusId'];
      				$order_status_text = $order_data['status'];
              
              $positions = $order_data['positions'];
              
              if(!empty($positions)) {
                  
                  foreach($positions as $position) {
                      
                      $position_id = $position['positionId'];
                      $position_status = $position['status'];
                      $position_supplierCode = $position['supplierCode'];
                      $description = $position['description'];
                      
                      $position_itemId = null;
                      
                      if(isset($order_items_base[$position_supplierCode])) {
                          
                          $order_items_supplierCode_data = $order_items_base[$position_supplierCode];
                          foreach($order_items_supplierCode_data as $order_item_base_id => $order_item_base_data) {
                              
                              if($position['brand'] == $order_item_base_data["brand"] && $position['number'] == $order_item_base_data['number'] && $position['quantity'] == $order_item_base_data['quantity']) {
                                  
                                 $position_itemId =  $order_item_base_id;
                                 break;
                              }
                          }
                      }
                      
                      if(!empty($position_itemId)) {
                          
                        //Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
	                  		$new_status_query = $db_link->prepare( 'SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;' );
	                  		$new_status_query->bindValue( ':id', $new_sao_state );
	                  		$new_status_query->execute();
	                  		$new_status_record = $new_status_query->fetch();
                  		
	                  		$new_status = $new_status_record["status_id"];
	                  		
	                  		if($new_status > 0) {
	                  			
	                  			//Отправляем запрос на изменение статуса позиции
	                  			$ch = curl_init();
	                  			curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$position_itemId."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
	                  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	                  			curl_setopt($ch, CURLOPT_HEADER, 0);
	                  			$curl_result = curl_exec($ch);
	                  			curl_close($ch);
	                  			
	                  		}
                  		
	                  		$sao_obj = json_encode(array(
	                  		    		"orderId" => $order_id,
	                              "positionId" => $position['positionId'],
	                              "description" => $position['description'],
	                              "quantity" => $position['quantity'],
	                              "statusId" => $position['statusId'],
	                              "status" => $position_status,
	                              "itemKey" => $position['itemKey'],
	                              "comment" => $position['comment'],
	                              "itemId" => $position_itemId
	                          ));
                  		
	                  		$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа:<br/>{$order_id}<br />Статус: {$position_status}";
	                  		
	                  		//Обновляем SAO-статус
	                  		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object, `sao_robot` = :sao_robot WHERE `id` = :id;');
	                  		$update_query->bindValue(':sao_state', $new_sao_state);
	                  		$update_query->bindValue(':sao_message', $sao_message);
	                  		$update_query->bindValue(':sao_robot', 2);
	                  		$update_query->bindValue(':sao_state_object', $sao_obj);
	                  		$update_query->bindValue(':id', $position_itemId);
	     
	                  		if( $update_query->execute() != true ) {
	                  			
	                  			echo date("d-m-Y H:i:s")."\n";
	                  			echo "Ошибка обновления SAO-статуса\n";
	                  			echo "=======================================================\n";
	                  			
	                  			$sao_result_message .= "Заказ для позиции $position_itemId создан, но при обновлении SAO-состояния возникли ошибки, обратитесь к инженеру поддержки. ";
	                  		}
	                  		else
	                  		{
	                  		    $sao_result_message .= "Заказ для позиции $position_itemId создан. ";
	                  		}
	                      }
	                      else
	                      {
	                          $sao_result_message .= " \n Для заказа $order_id нет идентификатора по позиции $position_id  - $description. ";
	                      }
                      
                  }
              }
              else
              {
                  $sao_result_message .= " \n Для заказа $order_id нет данных по позициям. ";
              }
          }
          
          $sao_result["status"] = true;
          $sao_result["message"] = $sao_result_message;
      }
      else
      {
          throw new Exception( "Ошибка обработки ответа. Отсутствует информация о заказах. ");
      }
			

		} else {
			
			$error_message = $result_order['errorMessage'];
			
			throw new Exception( "Ошибка создания заказа: " . $error_message );
			
		}
	
} catch ( Exception $e ) {
	
	echo $e->getMessage();
	
	$sao_result['status'] = false;
	$sao_result['message'] = $e->getMessage();
	
}
 
file_put_contents( $supplier_dir . "/dump_do_order.log", ob_get_clean() );
?>