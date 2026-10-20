<?php
// ===================== API ОБРАБОТЧИК =====================
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $pdo = new PDO("mysql:host={$DP_Config->host};dbname={$DP_Config->db};charset=utf8", $DP_Config->user, $DP_Config->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit(json_encode(['status' => 500, 'message' => 'Ошибка подключения к БД']));
}

$action = $_GET['action'] ?? '';

if ($action === 'get_services') {
    $stoId = (int)$_GET['sto_id'];
    if (!$stoId) exit(json_encode(['status' => 400, 'message' => 'sto_id required']));

    $stmt = $pdo->prepare("SELECT id_tree_list_services FROM car_services WHERE id = ?");
    $stmt->execute([$stoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !$row['id_tree_list_services']) {
        exit(json_encode(['status' => 404, 'message' => 'Для этого СТО не задано дерево услуг']));
    }
    $treeListId = (int)$row['id_tree_list_services'];

    $stmt = $pdo->prepare("SELECT * FROM shop_tree_lists_items WHERE tree_list_id = ?");
    $stmt->execute([$treeListId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = [];
    $childrenIds = [];

    foreach ($items as $item) {
        if ((int)$item['parent'] === 0) {
            $categories[(int)$item['id']] = [
                'name' => $item['value'],
                'services' => []
            ];
        } else {
            $childrenIds[] = (int)$item['id'];
        }
    }

    if (!empty($childrenIds)) {
        $placeholders = implode(',', array_fill(0, count($childrenIds), '?'));
        $stmt = $pdo->prepare("
            SELECT id_tree_services_list, hourly_rate, fix_price
            FROM car_service_prices
            WHERE sto_id = ? AND id_tree_services_list IN ($placeholders) AND active = 1
        ");
        $params = array_merge([$stoId], $childrenIds);
        $stmt->execute($params);
        $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $priceMap = [];
        foreach ($prices as $row) {
            $priceMap[$row['id_tree_services_list']] = $row['fix_price'] ?: $row['hourly_rate'];
        }
    } else {
        $priceMap = [];
    }

    foreach ($items as $item) {
        $parentId = (int)$item['parent'];
        if ($parentId > 0 && isset($categories[$parentId])) {
            $serviceId = (int)$item['id'];
            $categories[$parentId]['services'][] = [
                'id'    => $serviceId,
                'name'  => $item['value'],
                'price' => $priceMap[$serviceId] ?? null
            ];
        }
    }

    $categories = array_filter($categories, function($cat) {
        return !empty($cat['services']);
    });

    exit(json_encode(['status' => 200, 'data' => ['categories' => array_values($categories)]]));
}

if ($action === 'get_slots') {
    $stoId   = (int)$_GET['sto_id'];
    $postId  = (int)$_GET['post_id'];
    $date    = $_GET['date'] ?? '';
    if (!$stoId || !$postId || !$date) {
        exit(json_encode(['status' => 400, 'message' => 'Missing parameters']));
    }

    $stmt = $pdo->prepare("SELECT operating_mode, time_from, time_to, record_step FROM car_services WHERE id = ?");
    $stmt->execute([$stoId]);
    $sto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sto) exit(json_encode(['status' => 404, 'message' => 'СТО не найдено']));

    $operatingMode = json_decode($sto['operating_mode'], true);
    $dayOfWeek = date('N', strtotime($date));
    if (!in_array($dayOfWeek, $operatingMode)) {
        exit(json_encode(['status' => 200, 'data' => ['slots' => []]]));
    }

    $timeFrom = (int)$sto['time_from'];
    $timeTo   = (int)$sto['time_to'];
    $step     = (int)$sto['record_step'];

    $start = "$date 00:00:00";
    $end   = "$date 23:59:59";
    $stmt = $pdo->prepare("SELECT time, time_to FROM car_service_records WHERE sto_id = ? AND post_id = ? AND time BETWEEN ? AND ?");
    $stmt->execute([$stoId, $postId, $start, $end]);
    $busy = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $busyIntervals = [];
    foreach ($busy as $rec) {
        $busyIntervals[] = ['start' => strtotime($rec['time']), 'end' => (int)$rec['time_to']];
    }

    $slots = [];
    $startTime = strtotime("$date $timeFrom:00");
    $endTime   = strtotime("$date $timeTo:00");

    if ($date === date('Y-m-d')) {
        $current = time();
        if ($current > $startTime) {
            $rounded = ceil($current / ($step * 60)) * ($step * 60);
            $startTime = max($startTime, $rounded);
        }
    }

    for ($slotStart = $startTime; $slotStart < $endTime; $slotStart += $step * 60) {
        $slotEnd = $slotStart + $step * 60;
        if ($slotEnd > $endTime) continue;
        $available = true;
        foreach ($busyIntervals as $interval) {
            if ($slotStart < $interval['end'] && $slotEnd > $interval['start']) {
                $available = false;
                break;
            }
        }
        if ($available) {
            $slots[] = date('H:i', $slotStart);
        }
    }
    exit(json_encode(['status' => 200, 'data' => ['slots' => $slots]]));
}

if ($action === 'book') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) exit(json_encode(['status' => 400, 'message' => 'Invalid JSON']));

    $stoId        = (int)$input['sto_id'];
    $postId       = (int)$input['post_id'];
    $workerId     = (int)$input['worker_id'] ?: null;
    $datetime     = $input['datetime'];
    $clientName   = trim($input['client_name']);
    $clientPhone  = trim($input['client_phone']);
    $clientEmail  = trim($input['client_email']);
    $carModel     = trim($input['car_model']);
    $carPlate     = trim($input['car_plate']);
    $carVin       = trim($input['car_vin']);
    $carYear      = (int)$input['car_year'];
    $carMileage   = (int)$input['car_mileage'];
    $services     = $input['services'];

    if (!$stoId || !$postId || !$datetime || !$clientName || !$clientPhone || empty($services)) {
        exit(json_encode(['status' => 400, 'message' => 'Не все обязательные поля заполнены']));
    }

    try {
        $pdo->beginTransaction();

        $orderTime = time();
        $stmt = $pdo->prepare("INSERT INTO shop_orders (user_id, time, status, paid, how_get, office_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([null, $orderTime, 1, 0, 0, 0]);
        $orderId = $pdo->lastInsertId();

        $time = date('Y-m-d H:i:s', strtotime($datetime));
        $timeFrom = strtotime($datetime);
        $timeTo   = $timeFrom + 3600;

        $userJson = json_encode(['name' => $clientName, 'phone' => $clientPhone, 'email' => $clientEmail]);
        $carJson  = json_encode(['model' => $carModel, 'gos_num' => $carPlate, 'vin' => $carVin, 'year' => $carYear, 'mileage' => $carMileage]);

        $stmt = $pdo->prepare("INSERT INTO car_service_records 
            (time, post_id, sto_id, worker_id, orders_ids, prepayment, is_archive, is_paid, is_view, time_from, time_to, user_json, car_json) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $time, $postId, $stoId, $workerId, $orderId,
            0, 0, 0, 0,
            $timeFrom, $timeTo,
            $userJson, $carJson
        ]);
        $recordId = $pdo->lastInsertId();

        foreach ($services as $svc) {
            $serviceId = (int)$svc['id'];
            $count     = (int)$svc['count'];
            $stmt = $pdo->prepare("
                SELECT p.hourly_rate, p.fix_price, i.value AS caption 
                FROM car_service_prices p
                JOIN shop_tree_lists_items i ON i.id = p.id_tree_services_list
                WHERE p.sto_id = ? AND p.id_tree_services_list = ?
            ");
            $stmt->execute([$stoId, $serviceId]);
            $priceData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$priceData) continue;
            $price = $priceData['fix_price'] ?: $priceData['hourly_rate'];
            $caption = $priceData['caption'];

            $stmt = $pdo->prepare("INSERT INTO car_service_record_services 
                (record_id, count, hourly_rate, caption, price, fix_price) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $recordId,
                $count,
                $priceData['hourly_rate'] ?: 0,
                $caption,
                $price,
                $priceData['fix_price'] ?: 0
            ]);
        }

        $pdo->commit();
        exit(json_encode(['status' => 200, 'data' => ['record_id' => $recordId, 'order_id' => $orderId]]));
    } catch (Exception $e) {
        $pdo->rollBack();
        exit(json_encode(['status' => 500, 'message' => 'Ошибка сохранения: ' . $e->getMessage()]));
    }
}

// Если действие не распознано
exit(json_encode(['status' => 404, 'message' => 'Неизвестное действие']));