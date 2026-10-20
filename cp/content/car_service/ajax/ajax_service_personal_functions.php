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
	
	/* ================== ИЗОБРАЖЕНИЯ ================== */
	
	function personalImagesUrl() {
		return "https://fordzap.com/cp/content/car_service/files/";
	}
	function personalImagesDir() {
		return $_SERVER["DOCUMENT_ROOT"]."/cp/content/car_service/files/";
	}
	
	// Возвращает URL загруженного файла, null (нет файла) или false (ошибка)
	function uploadPersonalImage() {
		if (!isset($_FILES["image"]) || $_FILES["image"]["error"] === UPLOAD_ERR_NO_FILE) {
			return null;
		}
		if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
			return false;
		}
		
		$file = $_FILES["image"];
		$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
		$allowed = ["jpg", "jpeg", "png", "gif", "webp"];
		if (!in_array($ext, $allowed)) {
			return false;
		}
		
		// Проверяем, что это действительно изображение
		if (@getimagesize($file["tmp_name"]) === false) {
			return false;
		}
		
		$dir = personalImagesDir();
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		if (!is_dir($dir) || !is_writable($dir)) {
			return false;
		}
		
		$imageName = "personal_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $ext;
		$destPath  = $dir . $imageName;
		
		if (!move_uploaded_file($file["tmp_name"], $destPath)) {
			return false;
		}
		
		return personalImagesUrl() . $imageName;
	}
	
	// Удаляет физический файл по его полному URL (только внутри нашей папки)
	function deletePersonalImageFile($url) {
		if (empty($url)) return;
		$prefix = personalImagesUrl();
		if (strpos($url, $prefix) !== 0) return;
		$filename = basename($url);
		if ($filename === '' || strpos($filename, '..') !== false) return;
		$path = personalImagesDir() . $filename;
		if (is_file($path)) {
			@unlink($path);
		}
	}
	
	/* ================== CRUD ================== */
	
	function create_personal() {
		global $db_link;
		global $_POST;
		
		// Загрузка изображения (если передано)
		$imageUrl = "";
		if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
			$uploaded = uploadPersonalImage();
			if ($uploaded === false) {
				exitFunction(["status" => 500, "message" => "Ошибка загрузки изображения."]);
			}
			if ($uploaded !== null) {
				$imageUrl = $uploaded;
			}
		}
		
		$insert = $db_link->prepare("INSERT INTO `car_service_personal` 
			(`fio`, `standart_hourly_rate`, `phone`, `service_percent`, `user_id`, `show_price_info`, `image`) 
			VALUES (?, ?, ?, ?, ?, ?, ?)");
		
		if (!$insert->execute([
			$_POST["fio"],
			$_POST["standart_hourly_rate"],
			$_POST["phone"],
			$_POST["service_percent"],
			$_POST["user_id"],
			isset($_POST["show_price_info"]) ? (int)$_POST["show_price_info"] : 0,
			$imageUrl
		])) {
			// Откатываем загруженный файл, чтобы не мусорить
			if (!empty($imageUrl)) deletePersonalImageFile($imageUrl);
			exitFunction(["status" => 500, "message" => "Возникли ошибки при создании новой записи."]);
		}
		
		$id = $db_link->lastInsertId();
		
		exitFunction([
			"id"     => $id,
			"image"  => $imageUrl,
			"status" => 200
		]);
	}
	
	function update_personal() {
		global $db_link;
		global $_POST;
		
		$id = (int)$_POST["id"];
		
		// Текущее изображение
		$sel = $db_link->prepare("SELECT `image` FROM `car_service_personal` WHERE `id` = ?;");
		$sel->execute([$id]);
		$row = $sel->fetch(PDO::FETCH_ASSOC);
		$currentImage = $row ? (string)$row["image"] : "";
		
		$imageUrl = $currentImage;
		
		// 1) Пользователь нажал «Удалить изображение»
		if (isset($_POST["image_delete"]) && $_POST["image_delete"] == "1") {
			deletePersonalImageFile($currentImage);
			$imageUrl = "";
		}
		
		// 2) Загружен новый файл — он приоритетнее
		if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
			$uploaded = uploadPersonalImage();
			if ($uploaded === false) {
				exitFunction(["status" => 500, "message" => "Ошибка загрузки изображения."]);
			}
			if ($uploaded !== null) {
				if (!empty($currentImage) && $currentImage !== $uploaded) {
					deletePersonalImageFile($currentImage);
				}
				$imageUrl = $uploaded;
			}
		}
		
		$update = $db_link->prepare("UPDATE `car_service_personal` 
			SET `fio` = ?, `standart_hourly_rate` = ?, `phone` = ?, `service_percent` = ?, `user_id` = ?, `show_price_info` = ?, `image` = ? 
			WHERE `id` = ?;");
		
		if (!$update->execute([
			$_POST["fio"],
			$_POST["standart_hourly_rate"],
			$_POST["phone"],
			$_POST["service_percent"],
			$_POST["user_id"],
			isset($_POST["show_price_info"]) ? (int)$_POST["show_price_info"] : 0,
			$imageUrl,
			$id
		])) {
			exitFunction(["status" => 500, "message" => "Возникли ошибки при обновлении."]);
		}
		
		exitFunction([
			"id"      => $id,
			"image"   => $imageUrl,
			"status"  => 200,
			"message" => "Информация обновлена"
		]);
	}
	
	function delete_by_id() {
		global $db_link;
		global $_POST;
		
		$id = (int)$_POST["id"];
		
		// Достаём URL картинки, чтобы удалить файл после успешного DELETE
		$sel = $db_link->prepare("SELECT `image` FROM `car_service_personal` WHERE `id` = ?;");
		$sel->execute([$id]);
		$row = $sel->fetch(PDO::FETCH_ASSOC);
		$currentImage = $row ? (string)$row["image"] : "";
		
		$delete = $db_link->prepare("DELETE FROM `car_service_personal` WHERE `id` = ?;");
		if (!$delete->execute([$id])) {
			exitFunction(["status" => 500, "message" => "Возникли ошибки при удалении."]);
		}
		
		if (!empty($currentImage)) {
			deletePersonalImageFile($currentImage);
		}
		
		exitFunction(["status" => 200]);
	}
	
	if (isset($_POST["action"]) && function_exists($_POST["action"]))
		$_POST["action"]();
	else
		exitFunction(["status" => 404, "message" => "Метод не определён"]);
?>