<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

$DP_Config = new DP_Config;

// Получение поискового запроса

$searchQuery = $_POST['searchQuery'];


try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Запрос на выборку данных из двух таблиц с применением поискового запроса
    $query = "SELECT telegram_users.user_id, telegram_users.chat_id, telegram_users.number_phone, GROUP_CONCAT(users_profiles.data_value) AS data_values
              FROM telegram_users 
              JOIN users_profiles ON telegram_users.user_id = users_profiles.user_id
              WHERE users_profiles.data_value LIKE :searchQuery 
                  OR telegram_users.user_id LIKE :searchQuery 
                  OR telegram_users.number_phone LIKE :searchQuery
              GROUP BY telegram_users.user_id";

    $stmt = $db_link->prepare($query);
    $stmt->bindValue(':searchQuery', '%' . $searchQuery . '%', PDO::PARAM_STR);
    $stmt->execute();

    // Вывод результатов
    echo '<div class="list-group">';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<button type="button" class="list-group-item list-group-item-action search-result" data-userid="' . $row['user_id'] . '" data-chatid="' . $row['chat_id'] . '" data-phonenumber="' . $row['number_phone'] . '">';
        echo 'ID пользователя: ' . $row['user_id'] . '<br>';
        echo 'Данные: ' . $row['number_phone'] . ', '. $row['data_values'] ;
        echo '</button>';
    }
    echo '</div>';
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>