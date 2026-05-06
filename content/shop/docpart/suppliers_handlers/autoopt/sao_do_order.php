<?php 
	try {
		$items = [];

		if(isset($multi_order) && $multi_order === true) 
		{
			foreach ($order_items as $order_item) 
				$items[] = [
					'code' => json_decode($order_item["t2_json_params"])->code,
					'quantity' => $order_item['count_need']
				];
		}
		else
			$items[] = [
				'code' => json_decode($order_item["t2_json_params"])->code,
				'quantity' => $order_item['count_need']
			];
		
		$param_str = '';
		
		foreach($items as $key => $item)
		{
			if ($key > 0)
				$param_str .= '&items['.$key.'][code]='.$item["code"].'&items['.$key.'][quantity]='.$item["quantity"];
			else
				$param_str .= 'items['.$key.'][code]='.$item["code"].'&items['.$key.'][quantity]='.$item["quantity"];
		}
			
		
		
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://beta.autoopt.ru/api/v2/order/items',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_USERAGENT => '123',
		  CURLOPT_POSTFIELDS => $param_str,
		  CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer ' . $connection_options['token'],
			'Content-Type: application/x-www-form-urlencoded',
		  ),
		));

		$response = curl_exec($curl);
		
		curl_close($curl);
		
		$result = json_decode($response, true);
		
		$response_status = $result['status'];
		$response_message = $result['message'];
		$response_orderId = $result['orderId'];
		$response_items = $result['items'];
		
		if ($response_status != 200)
		{
			$message = is_null($result['message']) ? $result['text'] : $result['message'];
			if (is_null($message))
				$message = 'Серверная ошибка.';
			
			throw new Exception($message);
		}
			
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 2;');
		$new_status_query->execute();
		$new_status_record = $new_status_query->fetch();
		$new_status = $new_status_record["status_id"];
		
		//Если массовый заказ, то для каждой позиции записываем id созданного заказа и обновляем статус
		if(isset($multi_order) && $multi_order === true){
			foreach ($order_items as $order_item) 
			{
				if ($new_status > 0)
				{
					//Отправляем запрос на изменение статуса позиции
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item['id']."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					$curl_result = curl_exec($ch);
					curl_close($ch);
				}
				
				$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = 2, `sao_robot` = 2, `sao_state_object`= :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
    			$update_query->bindValue(':sao_state_object', json_encode($result));
    			$update_query->bindValue(':sao_message', 'Заказано: '.date("d.m.Y H:i:s", time()));
    			$update_query->bindValue(':id', $order_item['id']);
    			
    			if( !$update_query->execute() )
				{
					echo date("d-m-Y H:i:s")."\n";
					echo "Ошибка обновления SAO-статуса\n";
					echo "=======================================================\n";
					
					throw new Exception(" Заказ для позиции id-". $order_item['id']." создан, но при обновлении SAO-состояния возникли ошибки, обратитесь к инженеру поддержки.");
				}
				else
				{
					$sao_result_message = " Заказ для позиции id-". $order_item['id']." создан.";
				}
			}
		}
		//Иначе обновляем информацию только для единственной позиции
		else {
			
			if ($new_status > 0)
			{
				//Отправляем запрос на изменение статуса позиции
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item['id']."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$curl_result = curl_exec($ch);
				curl_close($ch);
			}
			
			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = 2, `sao_robot` = 2, `sao_state_object`= :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
			$update_query->bindValue(':sao_state_object', json_encode($result));
			$update_query->bindValue(':sao_message', 'Заказано: '.date("d.m.Y H:i:s", time()));
			$update_query->bindValue(':id', $order_item['id']);
			
			if( !$update_query->execute() )
			{
				echo date("d-m-Y H:i:s")."\n";
				echo "Ошибка обновления SAO-статуса\n";
				echo "=======================================================\n";
				
				throw new Exception(" Заказ для позиции id-".$order_item['id']." создан, но при обновлении SAO-состояния возникли ошибки, обратитесь к инженеру поддержки.");
			}
			else
			{
				$sao_result_message = " Заказ для позиции id-".$order_item['id'] ." создан.";
			}
		}
		
		$sao_result["status"] = true;
		$sao_result["message"] = $sao_result_message;
		
	}
	catch (Exception $e) {
		$sao_result["status"] = false;
		$sao_result["message"] = $e->getMessage();
	}
	
?>