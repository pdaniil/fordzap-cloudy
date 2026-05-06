<?php
ini_set("display_errors", 1);
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Соединение с основной БД
$DP_Config = new DP_Config;//Конфигурация CMS

//Подключение к БД 
try
{
	$pdo = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
	exit("No DB connect");
}
$pdo->query("SET NAMES utf8;");

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $stmt = $pdo->prepare("SELECT * FROM car_service_stickers ORDER BY id DESC");
	$stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($action === 'add') {
    $text = trim($_POST['text'] ?? '');
    $color = trim($_POST['color'] ?? '#ffffff');
    if ($text) {
        $stmt = $pdo->prepare("INSERT INTO car_service_stickers (text, bg_color) VALUES (?, ?)");
        $stmt->execute([$text, $color]);
    }
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $text = $_POST['text'] ?? null;
    $color = $_POST['color'] ?? null;

    if ($id > 0) {
        if (!is_null($text) ) {
            $stmt = $pdo->prepare("UPDATE car_service_stickers SET text = ? WHERE id = ?");
            $stmt->execute([$text, $id]);
        }
        if (!is_null($color)) {
            $stmt = $pdo->prepare("UPDATE car_service_stickers SET bg_color = ? WHERE id = ?");
            $stmt->execute([$color, $id]);
        }
    }
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM car_service_stickers WHERE id = ?");
        $stmt->execute([$id]);
    }
}
