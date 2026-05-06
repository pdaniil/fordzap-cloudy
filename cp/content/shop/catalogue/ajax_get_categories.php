<?php
// Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
} catch (PDOException $e) {
    $answer = array();
    $answer["status"] = false;
    $answer["message"] = "No DB connect";
    exit(json_encode($answer));
}

$db_link->query("SET NAMES utf8;");

// Получение parent_id из GET-запроса
$parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

// Подготовка и выполнение запроса
$query = $db_link->prepare("SELECT id, value FROM shop_catalogue_categories WHERE parent = :parent_id ORDER BY `order`");
$query->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
$query->execute();

// Формирование ответа
$categories = $query->fetchAll(PDO::FETCH_ASSOC);

// Отправка JSON ответа
header('Content-Type: application/json');
echo json_encode($categories);
