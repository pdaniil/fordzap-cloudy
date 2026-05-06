<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

$DP_Config = new DP_Config;

$source_encoding = 'cp1251';
$target_encoding = 'UTF-8';

// Получение поискового запроса
$searchQuery = $_POST['searchQuery'];


try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db.';charset=utf8', $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Запрос на выборку данных из таблицы с применением поискового запроса
    $query = "SELECT *
              FROM telegram_car_to
              WHERE part LIKE :searchQuery
                  OR replacement_interval LIKE :searchQuery";

    $stmt = $db_link->prepare($query);
    $stmt->bindValue(':searchQuery', '%' . $searchQuery . '%', PDO::PARAM_STR);
    $stmt->execute();

    // Вывод результатов
    echo '<div class="list-group">';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<button type="button" onclick="setParams(this)" class="list-group-item list-group-item-action search-result" data-toggle="modal" data-target="#editModal" data-id="'.$row['id'].'" data-part="'.$row['part'].'" data-replacement-interval="'.$row['replacement_interval'].'">';

        //echo '<button type="button" class="list-group-item list-group-item-action search-result" value="'.$row['id'].'">';
        echo $row['part']. '<br>';
        echo $row['replacement_interval'];
        echo '</button>';
    }
    echo '</div>';
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
