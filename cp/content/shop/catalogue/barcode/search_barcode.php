<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

$DP_Config = new DP_Config;

$source_encoding = 'cp1251';
$target_encoding = 'UTF-8';

try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

// Получение поискового запроса
$searchQuery = $_POST['searchQuery'];

try {
    // Запрос на выборку данных из таблицы с применением поискового запроса
    $query = "SELECT *
              FROM barcode
              WHERE article LIKE :searchQuery
                  OR brand LIKE :searchQuery
                  OR name LIKE :searchQuery
                  OR barcode LIKE :searchQuery";

    $stmt = $db_link->prepare($query);
    $stmt->bindValue(':searchQuery', '%' . $searchQuery . '%', PDO::PARAM_STR);
    $stmt->execute();

    // Вывод результатов
    echo '<div class="list-group">';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<button type="button" onclick="setParams(this)" class="list-group-item list-group-item-action search-result" data-toggle="modal" data-target="#editModal" data-id="'.$row['id'].'" data-article="'.$row['article'].'" data-brand="'.$row['brand'].'" data-name="'.$row['name'].'" data-barcode="'.$row['barcode'].'">';

        echo $row['article']. ' - ';
        echo $row['brand']. ' - ';
        echo $row['name']. '<br>';
        echo $row['barcode'];
        echo '</button>';
    }
    echo '</div>';
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
