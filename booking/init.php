<?php
// ===================== ИНИЦИАЛИЗАЦИЯ ДАННЫХ ДЛЯ ОТОБРАЖЕНИЯ =====================
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $pdo = new PDO("mysql:host={$DP_Config->host};dbname={$DP_Config->db};charset=utf8", $DP_Config->user, $DP_Config->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Ошибка подключения к БД');
}

// --- Получение ID пользователя через глобальную функцию (из config.php) ---
$user_id = DP_User::getUserId();

$client_data = ['name' => '', 'phone' => '', 'email' => '', 'fio' => ''];
$cars = [];

if ($user_id > 0) {
    $stmt = $pdo->prepare("SELECT email, phone FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $client_data['email'] = $user['email'];
        $client_data['phone'] = $user['phone'];
    }
    $stmt = $pdo->prepare("SELECT data_key, data_value FROM users_profiles WHERE user_id = ? AND data_key IN ('name', 'surname')");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['data_key'] == 'name') $client_data['name'] = $row['data_value'];
        if ($row['data_key'] == 'surname') $client_data['surname'] = $row['data_value'];
    }
    $client_data['fio'] = trim(($client_data['surname'] ?? '') . ' ' . ($client_data['name'] ?? ''));

    $stmt = $pdo->prepare("SELECT * FROM shop_docpart_garage WHERE user_id = ? ORDER BY id");
    $stmt->execute([$user_id]);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Список СТО ---
$sto_list = $pdo->query("SELECT * FROM car_services ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$default_sto = $sto_list[0] ?? null;
$default_sto_id = $default_sto ? $default_sto['id'] : 0;

// --- Посты и мастера (для совместимости, но не используются) ---
$posts = $pdo->query("SELECT * FROM car_service_posts ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$workers = $pdo->query("SELECT * FROM car_service_personal ORDER BY fio")->fetchAll(PDO::FETCH_ASSOC);

// Базовый URL для API
$apiBase = 'https://fordzap.com/booking/api.php';  