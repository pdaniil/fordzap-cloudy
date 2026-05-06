<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
} catch (PDOException $e) {
    exit("No DB connect");
}

$db_link->query("SET NAMES utf8;");

if (!isset($_GET['sto_id'])) {
    echo json_encode([]);
    exit;
}

$sto_id = (int)$_GET['sto_id'];

// Получаем данные о сервисе
$stmt = $db_link->prepare("SELECT operating_mode, time_from, time_to FROM car_services WHERE id = ?");
$stmt->execute([$sto_id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    echo json_encode([]);
    exit;
}

$operating_days = json_decode($service['operating_mode']); // Массив строк, например ["1","2","3"]

$time_from = (int)$service['time_from'];
$time_to = (int)$service['time_to'];
$total_hours = $time_to - $time_from;

// Получаем записи на ближайшие 30 дней
$now = time();
$future = $now + 60 * 60 * 24 * 30; // 30 дней вперёд
$stmt = $db_link->prepare("
    SELECT time, duration
    FROM car_service_records
    WHERE sto_id = ?
    AND time BETWEEN ? * 1000 AND ? * 1000
");
$stmt->execute([$sto_id, $now, $future]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Группируем записи по дате
$busy_by_day = [];

foreach ($records as $r) {
    $start = (int)($r['time'] / 1000);
    $date = date('Y-m-d', $start);
    $busy_by_day[$date] = isset($busy_by_day[$date]) ? $busy_by_day[$date] + $r['duration'] : $r['duration'];
}

// Ищем доступные дни
$available_days = [];
for ($i = 1; $i < 31; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $day_of_week = date('N', strtotime($date)); // 1 (Пн) - 7 (Вс)

    if (!in_array((string)$day_of_week, $operating_days)) {
		continue;
	}

    $busy_hours = isset($busy_by_day[$date]) ? $busy_by_day[$date] : 0;
    if ($busy_hours < $total_hours) {
        $available_days[] = date('Y-m-d', strtotime($date)); 
    }
}

header('Content-Type: application/json');
echo json_encode($available_days);
