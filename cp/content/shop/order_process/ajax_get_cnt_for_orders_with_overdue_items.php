<?php
/*
Серверный скрипт для получения информации о количестве заказов с просроченными позициями
*/
header('Content-Type: application/json;charset=utf-8;');
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS

//Подключение к БД
try
{
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $result["status"] = false;
    $result["message"] = "DB connect error";
    $result["code"] = 502;
    exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

// -------------------------------------------------------------------------------
//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
// -------------------------------------------------------------------------------

//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

//Проверяем право менеджера
if( ! DP_User::isAdmin())
{
    $result["status"] = false;
    $result["message"] = "Forbidden";
    $result["code"] = 501;
    exit(json_encode($result));
}

$user_id = DP_User::getAdminId();
$select_offices = $db_link->prepare("SELECT * FROM `shop_offices`;");
$select_offices->execute();
$select_offices_res = $select_offices->fetchAll(PDO::FETCH_ASSOC);

$user_offices = [];

foreach ($select_offices_res as $office_info) {
    $manager_arr = json_decode($office_info["users"], true);
    if (in_array($user_id, $manager_arr)) {
        array_push($user_offices, $office_info["id"]);
    }
}

// Если у пользователя нет офисов, возвращаем 0
if (empty($user_offices)) {
    $result["status"] = true;
    $result["count"] = 0;
    exit(json_encode($result));
}

// Текущее время
$current_time = time();

// Создаем плейсхолдеры для каждого офиса
$placeholders = rtrim(str_repeat('?,', count($user_offices)), ',');

// Запрос для подсчета позиций, которые соответствуют условиям:
// 1. Статус позиции (status) в (1, 2, 22)
// 2. Время с момента создания заказа прошло больше чем t2_time_to_exe_guaranteed дней
$query = "
    SELECT COUNT(DISTINCT o.id) AS 'count'
    FROM `shop_orders` o
    INNER JOIN `shop_orders_items` p ON o.id = p.order_id
    WHERE p.status IN (1, 2, 22)
    AND p.t2_time_to_exe_guaranteed IS NOT NULL
    AND p.t2_time_to_exe_guaranteed > 0
    AND o.time IS NOT NULL
    AND ($current_time - o.time) > (p.t2_time_to_exe_guaranteed * 86400)
    AND `office_id` IN ($placeholders)
";

try {
    $stmt = $db_link->prepare($query);
    
    // Передаем каждый ID офиса как отдельный параметр
    $stmt->execute($user_offices);
    
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $result["status"] = true;
    $result["count"] = intval($record["count"]);
    exit(json_encode($result));
} catch (PDOException $e) {
    $result["status"] = false;
    $result["message"] = "Query error: " . $e->getMessage();
    $result["code"] = 503;
    exit(json_encode($result));
}
?>  