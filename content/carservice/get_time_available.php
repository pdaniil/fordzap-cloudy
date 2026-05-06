<?php
ini_set("display_errors", "1");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Соединение с основной БД
$DP_Config = new DP_Config;//Конфигурация CMS

//Подключение к БД
try
{
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e)
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

function exitFunction($result) {
    exit(json_encode($result));
}

$_POST = json_decode(file_get_contents("php://input"), true);
//$_POST = json_decode('{"sto_id":"1","time":1746086400000,"fio":"123","phone":"asd","email":"spec4@intask.pro","mileage":"asd","gos_num":"123asd","comment":"123asd","services_arr":["20"]}', true);


$sto_id = (int)$_POST["sto_id"];

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


// Преобразуем timestamp в секунды и получаем дату
$requested_date = date('Y-m-d', $_POST['time'] / 1000);

// Проверяем, входит ли день недели в рабочие дни
$day_of_week = date('N', strtotime($requested_date)); // 1 (Пн) - 7 (Вс)
if (!in_array((string)$day_of_week, $operating_days)) {
    exitFunction(['error' => 'Нерабочий день']);
}

// Формируем все возможные часовые слоты в этот день
$slots = [];
for ($h = $time_from; $h + 1 <= $time_to; $h++) {
    $slot_start = strtotime("$requested_date $h:00:00");
    $slots[] = [
        'start' => $slot_start,
        'end' => $slot_start + 3600
    ];
}

// Составляем массив занятых интервалов в секундах
$busy_slots = [];
foreach ($records as $r) {
    $start = (int)($r['time'] / 1000);
    $end = $start + ((int)$r['duration']) * 60;
    $r_date = date('Y-m-d', $start);
    if ($r_date === $requested_date) {
		
        for ($i = 0; $i <= $r['duration'] - 1; $i++) {
            $slot_start = $start + ($i * 3600); // каждый следующий час
            $busy_slots[] = [
                'start' => $slot_start,
                'end' => $slot_start + 3600
            ];
        }
    }
}

// Ищем первое свободное окно
$found = false;
foreach ($slots as $slot) {
    $overlap = false;
    foreach ($busy_slots as $b) {
        if ($slot['start'] < $b['end'] && $slot['end'] > $b['start']) {
            $overlap = true;
            break;
        }
    }
    if (!$overlap) {
        $_POST['time'] = $slot['start'] * 1000; // Переводим обратно в миллисекунды
        $found = true;
        break;
    }
}

exit(json_encode($_POST));