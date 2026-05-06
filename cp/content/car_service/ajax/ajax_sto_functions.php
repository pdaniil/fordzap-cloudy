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
	
	function create_sto() {
		global $db_link;
		global $_POST;
		$insert = $db_link->prepare("INSERT INTO `car_services` (`operating_mode`, `caption`, `record_step`, `id_tree_list_services`, `address`,`time_from`, `time_to`,`organization`,`inn`,`phone`,`coords`, `hourly_rate`) VALUES (?, ?, ?, ?, ?, ?,?,?,?,?,?,?)");
		
		if (!$insert->execute([$_POST["operating_mode"], $_POST["caption"],$_POST["record_step"],$_POST["id_tree_list_services"], $_POST["address"], $_POST["time_from"], $_POST["time_to"], $_POST["organization"], $_POST["inn"], $_POST["phone"], $_POST["coords"], $_POST["hourly_rate"]]))
			exitFunction(["status" => 500, "message" => "Возникли ошибки при создании новой записи." . $_POST["address"]]);
		
		$id = $db_link->lastInsertId();
		
		$result = [
			"id" => $id,
			"status" => 200
		];
		
		exitFunction($result);
	}
	
	function update_sto() {
		global $db_link;
		global $_POST;
		
		$update = $db_link->prepare("UPDATE `car_services` SET `operating_mode` = ?, `caption` = ?, `record_step` = ?, `id_tree_list_services` = ?, `address` = ?, `time_from` = ?, `time_to` = ?, `organization` = ?, `inn` = ?, `phone` = ?, `coords` = ?, `hourly_rate` = ? WHERE `id` = ?;");
		if (!$update->execute([$_POST["operating_mode"], $_POST["caption"],$_POST["record_step"],$_POST["id_tree_list_services"],$_POST["address"],$_POST["time_from"],$_POST["time_to"],$_POST["organization"],$_POST["inn"],$_POST["phone"],$_POST["coords"],$_POST["hourly_rate"], $_POST["id"]]))
			exitFunction(["status" => 500, "message" => "Возникли ошибки при обновлении."]);
		
		$result = [
			"id" => $id,
			"status" => 200,
			"message" => "Информация обновлена"
		];
		
		exitFunction($result);
	}
	
	function delete_by_id() {
		global $db_link;
		global $_POST;
		
		$update = $db_link->prepare("DELETE FROM `car_services` WHERE `id` = ?;");
		if (!$update->execute([$_POST["id"]]))
			exitFunction(["status" => 500, "message" => "Возникли ошибки при удалении."]);
		
		$result = [
			"status" => 200
		];
		
		exitFunction($result);
	}
	
	if (isset($_POST["action"]) && function_exists($_POST["action"]))
		$_POST["action"]();
	else
		exitFunction(["status" => 404, "message" => "Метод не определён"]);
?>