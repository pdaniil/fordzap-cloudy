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
	
	function get_by_id() {
		global $db_link;
		global $_POST;
		$select = $db_link->prepare("SELECT * FROM `car_service_prices` WHERE `id_tree_services_list` = ? AND `sto_id` = ?;");
		
		$arr_values = [];
		
		$row = json_decode($_POST["data"], true);
		
		$arr_values[] = $row["id_tree_services_list"];
		$arr_values[] = $row["sto_id"];
		
		$select->execute($arr_values);
		$res_select = $select->fetch(PDO::FETCH_ASSOC);
		
		if (empty($res_select["stickers"]))
			$res_select["stickers"] = '[]';
		
		$result = [
			"data" => $res_select,
			"status" => 200
		];
		
		exitFunction($result);
	}
	
	//Вначале удаляем, потом вставляем
	function set_data() {
		global $db_link;
		global $_POST;
		
		
		$SQL_insert = "INSERT INTO `car_service_prices` (`active`, `id_tree_services_list`, `sto_id`, `hourly_rate`, `caption`, `stickers`, `recommendation_block`, `worker_price`, `fix_price`) VALUES ";
		$data = json_decode($_POST["data"], true);
		
		$count = count($data);
		
		$arr_values = [];
		
		$SQL_delete = "";
		$delete_str = "DELETE FROM `car_service_prices` WHERE `id_tree_services_list` = ? AND `sto_id` = ?;";
		$arr_delete_values = [];
		
		foreach ($data as $key => $row) {
			$SQL_insert .= "(?,?,?,?,?,?,?,?,?)";
			if (($count - 1) > $key )
				$SQL_insert .= ",";
			
			$arr_values[] = $row["active"];
			$arr_values[] = $row["id_tree_services_list"];
			$arr_values[] = $row["sto_id"];
			$arr_values[] = $row["hourly_rate"];
			$arr_values[] = $row["caption"];
			$arr_values[] = $row["stickers"];
			$arr_values[] = $row["recommendation_block"];
			$arr_values[] = $row["worker_price"];
			$arr_values[] = $row["fix_price"];
			
			$SQL_delete .= $delete_str;
			$arr_delete_values[] = $row["id_tree_services_list"];
			$arr_delete_values[] = $row["sto_id"];
		}
		
		$insert = $db_link->prepare($SQL_delete . $SQL_insert);
		
		if (!$insert->execute(array_merge($arr_delete_values, $arr_values)))
		{
			$result = [
				"status" => 404,
				"message" => "Возникли ошибки при обновлении данных"
			];
			exitFunction($result);
		}
		
		$result = [
			"status" => 200,
			"message" => "Данные обновлены"
		];
		
		exitFunction($result);
	}
	
	
	if (isset($_POST["action"]) && function_exists($_POST["action"]))
		$_POST["action"]();
	else
		exitFunction(["status" => 404, "message" => "Метод не определён"]);
?>