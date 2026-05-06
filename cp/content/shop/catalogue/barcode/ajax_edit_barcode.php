<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

$DP_Config = new DP_Config;

try
{
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e)
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

// Получение данных из POST-запроса
$id = $_POST['id'];
$article = $_POST['article'];
$brand = $_POST['brand'];
$name = $_POST['name'];
$barcode = $_POST['barcode'];

// Создание подключения к базе данных

// Проверка соединения
if ($db_link->connect_error) {
    die("Connection failed: " . $db_link->connect_error);
}

$sql = "UPDATE barcode SET article = :article, brand = :brand, name = :name, barcode = :barcode WHERE id = :id";

try {
    $stmt = $db_link->prepare($sql);
    $stmt->bindParam(':article', $article);
    $stmt->bindParam(':brand', $brand);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':barcode', $barcode);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    $stmt->execute();

    // Проверка, была ли запись успешно обновлена
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Запись успешно обновлена"]);
    } else {
        // Нет изменений в базе данных
        echo json_encode(["success" => false, "message" => "Нет изменений в записи"]);
    }
} catch (PDOException $e) {
    // Ошибка выполнения запроса
    echo json_encode(["success" => false, "message" => "Ошибка при обновлении записи: " . $e->getMessage()]);
}
?>
