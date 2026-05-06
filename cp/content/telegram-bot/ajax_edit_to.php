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

// Получение данных из POST-запроса
$id = $_POST['id'];
$part = $_POST['part'];
$replacementInterval = $_POST['replacement_interval'];

// Создание подключения к базе данных


// Проверка соединения
if ($db_link->connect_error) {
    die("Connection failed: " . $db_link->connect_error);
}

$sql = "UPDATE telegram_car_to SET part = :part, replacement_interval = :replacementInterval WHERE id = :id";

try {
    $stmt = $db_link->prepare($sql);
    $stmt->bindParam(':part', $part);
    $stmt->bindParam(':replacementInterval', $replacementInterval);
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
