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
 

//$update = $db_link->prepare(" `car_service_records` SET `worker_id` = ?, `services_arr` = ?, `car_id` = ?, `color` = ?, `order_id` = ?, `user_id` = ?, `user` = ?, `gos_num` = ?, `year` = ?, `vin` = ?, `prepayment` = ?, `mileage` = ? WHERE `car_service_records`.`id` = ?");
//$update->execute([$_POST["worker_id"], json_encode($_POST["services_arr"]), $_POST["car_id"], $_POST["color"], $_POST["order_id"], $_POST["user_id"], $_POST["user"], $_POST["gos_num"], $_POST["year"],$_POST["vin"],$_POST["prepayment"],$_POST["mileage"], $_POST["record_id"]]);

$insert = $db_link->prepare("
	INSERT INTO car_service_records (
		sto_id,
		services_arr,
		gos_num,
		mileage,
		client_created_json,
		time,
		notify_view,
		duration,
		post_id,
		prepayment
	) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$client_json = json_encode([
	'fio' => $_POST['fio'],
	'phone' => $_POST['phone'],
	'comment' => $_POST['comment'],
	'email' => $_POST['email']
]);

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

$insert->execute([
	$_POST["sto_id"] ?? null,
	json_encode($_POST["services_arr"] ?? []),
	$_POST["gos_num"] ?? null,
	$_POST["mileage"] ?? null,
	$client_json,
	$_POST["time"] ?? null,
	1,
	1,
	1,
	0
]);

$id = $db_link->lastInsertId();

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/notifications/notify_helper.php" );

$managers_query = $db_link->prepare('SELECT `users` FROM `shop_offices` WHERE `id` = ?;');
$managers_query->execute( array(1) );
$managers_record = $managers_query->fetch();
if( $managers_record != false )
{
	//Список ID менеджеров
	$managers_list = json_decode($managers_record["users"], true);

	//Формируем массив получателей
	$persons = array();//Массив получателей
	
	for($i=0; $i < count($managers_list); $i++)
	{
		$persons[] = array( 'type'=>'user_id', 'user_id'=>(int)$managers_list[$i] );
	}
	
	//Значения переменных для уведомления
	$notify_vars = array();
	$notify_vars['link'] = "https://fordzap.com/cp/car_service/sto_record?id=" . $id;
	$notify_vars['time'] = date('d.m.Y, H:i', ($_POST["time"] / 1000));
	
	//Отправляем уведомление
	$send_result = send_notify('carservice_record_admin', $notify_vars, $persons);
	
	$notify_vars = [
		'time' => date('d.m.Y, H:i', ($_POST["time"] / 1000))
	];
	$persons = [];
	$persons[] = [
		'contacts' => [
			'email' => [
				'value' => $_POST['email']
			]
		],
		'type' => 'direct_contact'
	];
	
	$send_result = send_notify('carservice_record_user', $notify_vars, $persons);
}

?>