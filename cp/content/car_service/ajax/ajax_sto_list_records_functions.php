<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
	//Соединение с основной БД
	$DP_Config = new DP_Config;//Конфигурация CMS

	//Подключение к БД
	try
	{
		$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	}
	catch (PDOException $e) 
	{
		exit("No DB connect");
	}
	$db_link->query("SET NAMES utf8;");
	
	function exitFunction($result) {
		exit(json_encode($result));
	}
	
	function fetchNode($id) {
        global $db_link;
        $query = "SELECT id, parent, value FROM shop_tree_lists_items WHERE id = :id";
        $stmt = $db_link->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    function getFullCarName($car_json) {
        global $db_link;
		
		$car_arr = json_decode($car_json, true);
		
		$fullName = $car_arr["model"];
		
        // Удаление лишнего пробела в конце строки
        return trim($fullName);
    }
	
	
	function get_records_list_html() {
		global $db_link;
		global $_POST;
		global $DP_Config;
		
		$orders_statuses = array();

		$orders_statuses_query = $db_link->prepare("SELECT * FROM `shop_orders_statuses_ref` ORDER BY `order` ASC;");
		$orders_statuses_query->execute();
		while($status = $orders_statuses_query->fetch() )
		{
			$orders_statuses[$status["id"]] = array("name"=>$status["name"], "color"=>$status["color"]);
		}
		
		$sto_record_data_select = $db_link->prepare("SELECT * FROM `car_service_records`;");
		$sto_record_data_select->execute();
		$sto_records_data = $sto_record_data_select->fetchAll(PDO::FETCH_ASSOC);

		$arr_records = [];

		foreach ($sto_records_data as $sto_record_data)
		{
			$services_id = json_decode($sto_record_data['services_arr'], true);


			//Получаем список услуг
			if (!is_null($services_id) && count($services_id) > 0)
			{
				$placeholders = implode(',', array_fill(0, count($services_id), '?'));

				$sql = "SELECT GROUP_CONCAT(' ',value) as `services` FROM `shop_tree_lists_items` WHERE `id` IN ($placeholders)";
				$tree_lists_services_arr_select = $db_link->prepare($sql);

				$tree_lists_services_arr_select->execute($services_id);

				$services_str = $tree_lists_services_arr_select->fetch(PDO::FETCH_ASSOC);
				$services_str = trim($services_str['services']);
			}
			else
				$services_str = 'Не выбрано';



			//Получаем авто
			if (!empty($sto_record_data["car_json"]))
				$carFullName = getFullCarName($sto_record_data["car_json"]);
			else
				$carFullName = 'Не выбрано';

			//Пост
			$select_post = $db_link->prepare("SELECT `caption` FROM `car_service_posts` WHERE `id` = ?;");
			$select_post->execute([$sto_record_data["post_id"]]);
			$post_str = $select_post->fetch(PDO::FETCH_ASSOC);

			$post_str = $post_str['caption'];

			//Мастер
			if ($sto_record_data['worker_id'] > 0)
			{
				$select_worker = $db_link->prepare("SELECT `fio` FROM `car_service_personal` WHERE `id` = ?;");
				$select_worker->execute([$sto_record_data['worker_id']]);
				$worker_str = $select_worker->fetch(PDO::FETCH_ASSOC);
				$worker_str = $worker_str['fio'];
			}
			else
				$worker_str = 'Не выбрано';
 
			//Клиент
			if (!empty($sto_record_data["user_json"]))
			{
				$user_arr = json_decode($sto_record_data["user_json"], true);
				$user_str = 'ID: ' . $user_arr['user_id'] . ', ' .  $user_arr['email'] . ', ' . $user_arr['phone'];
			}
			else
				$user_str = $sto_record_data["user"] != '' ? $sto_record_data["user"] : 'Не выбрано';

			//СТО
			$select_service = $db_link->prepare("SELECT `caption` FROM `car_services` WHERE `id` = ?;");
			$select_service->execute([$sto_record_data['sto_id']]);
			$service_str = $select_service->fetch(PDO::FETCH_ASSOC);
			$service_str = $service_str['caption'];


			//Строка заказа
			$order_str = $sto_record_data['order_id'] > 0 ? $sto_record_data['order_id'] : 'Не выбран';


			//Строка времени
			$time_str = $sto_record_data['time'];

			$record_obj = [
				"id" => $sto_record_data['id'],
				"service_str" => $service_str,
				"post_str" => $post_str,
				"worker_str" => $worker_str,
				"user_str" => $user_str,
				"carFullName" => $carFullName,
				"services_str" => $services_str,
				"order_str" => $order_str,
				"time_str" => $time_str,
				"sto_id" => $sto_record_data['sto_id'],
				"worker_id" => $sto_record_data['worker_id'],
				"time" => $sto_record_data['time'],
				"post_id" => $sto_record_data['post_id'],
				"sto_id" => $sto_record_data['sto_id'],
				"is_archive" => $sto_record_data['is_archive'],
				"is_view" => $sto_record_data['is_view'],
				"is_paid" => $sto_record_data['is_paid'],
				"paid_text" => $sto_record_data["is_paid"] == 1 ? "Да" : "Нет"
			];
			$arr_records[] = $record_obj;
			
			$arr_records = filter_records($arr_records);
			$arr_records = sort_records($arr_records);
			
		}
		
		if (count($arr_records) > 0) {
			foreach ($arr_records as $record) {
				$a_item = '<a href="'.$DP_Config->domain_path.$DP_Config->backend_dir.'/car_service/sto_record?id=' . $record["id"] . '">';
				ob_start();
				?>
					<tr <?=$record["is_view"] == 0 ? "style=\"background: #a7a7a7;\"" : "style=\"background: ".$orders_statuses[$record["status_id"]]["color"].";\""; ?> >
						<td><input class="checkbox_for_records" type="checkbox" data-id="<?=$record["id"];?>" id="checkbox_<?=$record["id"];?>"></td>
						<td><?= $a_item .$record["id"];?></a></td>
						<td><?=$a_item.$record["service_str"];?></a></td>
						<td><?=$a_item.$record["worker_str"];?></a></td>
						<td><?=$a_item.$record["post_str"];?></a></td>
						<td><?=$a_item.$record["paid_text"];?></a></td>
						<td><?=$a_item.$record["user_str"];?></a></td>
						<td><?=$a_item.$record["carFullName"];?></a></td>
						<td><?=$a_item.$record["time_str"];?></a></td>
					</tr>
				<?php
				$html_records .= ob_get_clean();
			}		
		}
		else {
			$html_records = "";
			
		}
		
		$result = [
			"status" => 200,
			"html" => $html_records
		];
		
		exitFunction($result);
	}
		
	
	function filter_records($arr_records) {
		global $db_link;
		global $_POST;
		
		$filter_arr = json_decode($_POST["filter_obj"], true);
		
		if (!empty($filter_arr["worker_id"]) && $filter_arr["worker_id"] > 0) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return $record["worker_id"] == $filter_arr["worker_id"];
			});
		}
		
		
		if (!empty($filter_arr["post_id"]) && $filter_arr["post_id"] > 0) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return $record["post_id"] == $filter_arr["post_id"];
			});
		}
		
		if (!empty($filter_arr["sto_id"]) && $filter_arr["sto_id"] > 0) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return $record["sto_id"] == $filter_arr["sto_id"];
			});
		}
		
		if (!empty($filter_arr["is_paid"]) && $filter_arr["is_paid"] > 0) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return $record["is_paid"] == $filter_arr["is_paid"];
			});
		}
		
		if (!empty($filter_arr["is_view"]) && $filter_arr["is_view"] > 0) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return $record["is_view"] == $filter_arr["is_view"];
			});
		}
		
		if (!empty($filter_arr["is_archive"]) && $filter_arr["is_archive"] > 0) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return $record["is_archive"] == 1;
			});
		}
		else {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return $record["is_archive"] == 0;
			});
		}
		
		if (!empty($filter_arr["id"]) && $filter_arr["id"] > 0) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return $record["id"] == $filter_arr["id"];
			});
		}
		
		if (!empty($filter_arr["auto_str"])) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return stripos($record["carFullName"], $filter_arr["auto_str"]) !== false;
			});
		}
		
		if (!empty($filter_arr["time_to"]) && $filter_arr["time_to"] > 0) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				$recordTimeMs = strtotime($record["time"]) * 1000; // переводим в мс
				return $recordTimeMs <= $filter_arr["time_to"];
			});
		}

		if (!empty($filter_arr["time_from"]) && $filter_arr["time_from"] > 0) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				$recordTimeMs = strtotime($record["time"]) * 1000; // переводим в мс
				return $recordTimeMs >= $filter_arr["time_from"];
			});
		}
		 
		if (!empty($filter_arr["customer"])) {
			$arr_records = array_filter($arr_records, function($record) use ($filter_arr) {
				return stripos($record["user_str"], $filter_arr["customer"]) !== false;
			}); 
		}
		  
		return $arr_records;
	}
	
	function sort_records($arr_records) {
		global $db_link;
		global $_POST;
		
		
		
		return $arr_records;
	}
	
	function delete_by_ids() {
		global $db_link;
		global $_POST;
		
		$ids = explode(',', $_POST['ids']); // делаем массив
		$ids = array_filter($ids, 'is_numeric'); 

		// Генерируем плейсхолдеры вида ?,?,?... 
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		
		$update = $db_link->prepare("DELETE FROM `car_service_records` WHERE `id` IN (".$placeholders.");");
		if (!$update->execute($ids))
			exitFunction(["status" => 500, "message" => "Возникли ошибки при удалении."]);
		
		$result = [
			"status" => 200
		];
		
		exitFunction($result);
	}
	
	function archive_by_ids () {
		global $db_link;
		global $_POST;
		
		$ids = explode(',', $_POST['ids']); // делаем массив
		$ids = array_filter($ids, 'is_numeric'); 

		// Генерируем плейсхолдеры вида ?,?,?... 
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		
		$update = $db_link->prepare("UPDATE `car_service_records` SET `is_archive` = 1 WHERE `id` IN (".$placeholders.");");
		if (!$update->execute($ids))
			exitFunction(["status" => 500, "message" => "Возникли ошибки при архивации."]);
		
		$result = [
			"status" => 200
		];
		
		exitFunction($result);
	}
	
	function unarchive_by_ids () {
		global $db_link;
		global $_POST;
		
		$ids = explode(',', $_POST['ids']); // делаем массив
		$ids = array_filter($ids, 'is_numeric'); 

		// Генерируем плейсхолдеры вида ?,?,?... 
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		
		$update = $db_link->prepare("UPDATE `car_service_records` SET `is_archive` = 0 WHERE `id` IN (".$placeholders.");");
		if (!$update->execute($ids))
			exitFunction(["status" => 500, "message" => "Возникли ошибки при разархивации."]);
		
		$result = [
			"status" => 200
		];
		
		exitFunction($result);
	}
	
	function view_by_ids () {
		global $db_link;
		global $_POST;
		
		$update = $db_link->prepare("UPDATE `car_service_records` SET `view` = 1 WHERE `id` IN (?);");
		if (!$update->execute([$_POST["ids"]]))
			exitFunction(["status" => 500, "message" => "Возникли ошибки при Обновлении."]);
		
		$result = [
			"status" => 200
		];
		
		exitFunction($result);
	}
	
	function unview_by_ids () {
		global $db_link;
		global $_POST;
		
		$update = $db_link->prepare("UPDATE `car_service_records` SET `view` = 0 WHERE `id` IN ?;");
		if (!$update->execute([$_POST["ids"]]))
			exitFunction(["status" => 500, "message" => "Возникли ошибки при Обновлении."]);
		
		$result = [
			"status" => 200
		];
		
		exitFunction($result);
	}
	
	if (isset($_POST["action"]) && function_exists($_POST["action"]))
		$_POST["action"]();
	else
		exitFunction(["status" => 404, "message" => "Метод не определён"]);