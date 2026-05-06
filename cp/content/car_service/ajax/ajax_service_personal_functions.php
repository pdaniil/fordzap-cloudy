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
	
	function create_personal() {
		global $db_link;
		global $_POST;
		$insert = $db_link->prepare("INSERT INTO `car_service_personal` (`fio`, `standart_hourly_rate`, `phone`, `service_percent`, `user_id`, `show_price_info`) VALUES (?, ?, ?, ?, ?, ?)");
		
		if (!$insert->execute([$_POST["fio"], $_POST["standart_hourly_rate"],$_POST["phone"],$_POST["service_percent"],$_POST["user_id"], $_POST["show_price_info"] ] ) )
			exitFunction(["status" => 500, "message" => "Возникли ошибки при создании новой записи." . $_POST["address"]]);
		
		$id = $db_link->lastInsertId();
		
		$result = [
			"id" => $id,
			"status" => 200
		];
		
		exitFunction($result);
	}
	
	function update_personal() {
		global $db_link;
		global $_POST;
		
		$update = $db_link->prepare("UPDATE `car_service_personal` SET `fio` = ?, `standart_hourly_rate` = ?, `phone` = ?, `service_percent` = ?, `user_id` = ?, `show_price_info` = ? WHERE `id` = ?;");
		if (!$update->execute([$_POST["fio"], $_POST["standart_hourly_rate"],$_POST["phone"],$_POST["service_percent"],$_POST["user_id"], $_POST["show_price_info"], $_POST["id"]]))
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
		
		$update = $db_link->prepare("DELETE FROM `car_service_personal` WHERE `id` = ?;");
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