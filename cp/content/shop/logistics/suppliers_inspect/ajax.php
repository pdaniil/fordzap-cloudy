<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

$DP_Config = new DP_Config;
try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_link->query("SET NAMES utf8;");
} catch (PDOException $e) {
    http_response_code(500);
    exit(json_encode(['status' => false, 'message' => 'DB connect error: '.$e->getMessage()]));
}

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'offices') {
    $stmt = $db_link->query("SELECT id, caption FROM shop_offices ORDER BY id");
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => true, 'data' => $offices]);
    exit;
}

if ($action === 'statuses') {
    $stmt = $db_link->query("SELECT id, name, color FROM shop_orders_items_statuses_ref ORDER BY `order`");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => true, 'data' => $statuses]);
    exit;
}

if ($action === 'data') {
    $office_id = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-1 month'));
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    $for_warehouse = isset($_GET['for_warehouse']) ? (int)$_GET['for_warehouse'] : -1;

    // Получаем массив статусов
    $statuses = isset($_GET['status']) ? (array)$_GET['status'] : [];
    $statuses = array_filter($statuses, 'is_numeric');
    $statuses = array_map('intval', $statuses);
    // Преобразуем в строку через запятую для FIND_IN_SET
    $statuses_str = !empty($statuses) ? implode(',', $statuses) : '';

    if (!$office_id) {
        echo json_encode(['status' => false, 'message' => 'Office not selected']);
        exit;
    }

    $ts_from = strtotime($date_from . ' 00:00:00');
    $ts_to = strtotime($date_to . ' 23:59:59');

    // Формируем условие для CASE (фильтрованные позиции)
    if (!empty($statuses_str)) {
        $case_condition = "FIND_IN_SET(oi.status, :statuses)";
    } else {
        $case_condition = "1=1";
    }

    $sql = "
        SELECT 
            s.id AS storage_id,
            s.name AS storage_name,
            SUM(oi.count_need) AS total_count,
            SUM(oi.price * oi.count_need) AS total_sum,
            SUM(CASE WHEN ($case_condition) THEN oi.count_need ELSE 0 END) AS filtered_count,
            SUM(CASE WHEN ($case_condition) THEN oi.price * oi.count_need ELSE 0 END) AS filtered_sum
        FROM shop_orders_items oi
        INNER JOIN shop_orders o ON oi.order_id = o.id
        INNER JOIN shop_storages s ON oi.t2_storage_id = s.id
        WHERE o.office_id = :office_id
          AND o.time BETWEEN :ts_from AND :ts_to
    ";

    if ($for_warehouse !== -1) {
        $sql .= " AND o.for_warehouse = :for_warehouse";
    }

    $sql .= " GROUP BY s.id, s.name
              HAVING total_count > 0 OR filtered_count > 0
              ORDER BY s.name";

    $params = [
        ':office_id' => $office_id,
        ':ts_from' => $ts_from,
        ':ts_to' => $ts_to
    ];
    if ($for_warehouse !== -1) {
        $params[':for_warehouse'] = $for_warehouse;
    }
    if (!empty($statuses_str)) {
        $params[':statuses'] = $statuses_str;
    }

    $stmt = $db_link->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['total_sum'] = number_format($row['total_sum'], 2, '.', '');
        $row['filtered_sum'] = number_format($row['filtered_sum'], 2, '.', '');
        $row['difference'] = number_format((float)$row['total_sum'] - (float)$row['filtered_sum'], 2, '.', '');
    }

    echo json_encode(['status' => true, 'data' => $rows]);
    exit;
}

if ($action === 'details') {
    $office_id = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
    $storage_id = isset($_GET['storage_id']) ? (int)$_GET['storage_id'] : 0;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-1 month'));
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    $for_warehouse = isset($_GET['for_warehouse']) ? (int)$_GET['for_warehouse'] : -1;

    // Получаем массив статусов
    $statuses = isset($_GET['status']) ? (array)$_GET['status'] : [];
    $statuses = array_filter($statuses, 'is_numeric');
    $statuses = array_map('intval', $statuses);
    $statuses_str = !empty($statuses) ? implode(',', $statuses) : '';

    if (!$office_id || !$storage_id) {
        echo json_encode(['status' => false, 'message' => 'Missing parameters']);
        exit;
    }

    $ts_from = strtotime($date_from . ' 00:00:00');
    $ts_to = strtotime($date_to . ' 23:59:59');

    $sql = "
        SELECT 
            oi.id,
            oi.t2_name AS item_name,
            oi.count_need,
            oi.price,
            oi.price * oi.count_need AS sum,
            oi.order_id,
            oi.status AS status_id,
            sr.name AS status_name,
            sr.color AS status_color,
            FROM_UNIXTIME(o.time, '%d.%m.%Y %H:%i') AS order_date
        FROM shop_orders_items oi
        INNER JOIN shop_orders o ON oi.order_id = o.id
        LEFT JOIN shop_orders_items_statuses_ref sr ON oi.status = sr.id
        WHERE o.office_id = :office_id
          AND oi.t2_storage_id = :storage_id
          AND o.time BETWEEN :ts_from AND :ts_to
    ";

    if (!empty($statuses_str)) {
        $sql .= " AND FIND_IN_SET(oi.status, :statuses)";
    }

    if ($for_warehouse !== -1) {
        $sql .= " AND o.for_warehouse = :for_warehouse";
    }

    $sql .= " ORDER BY oi.id";

    $params = [
        ':office_id' => $office_id,
        ':storage_id' => $storage_id,
        ':ts_from' => $ts_from,
        ':ts_to' => $ts_to
    ];
    if (!empty($statuses_str)) {
        $params[':statuses'] = $statuses_str;
    }
    if ($for_warehouse !== -1) {
        $params[':for_warehouse'] = $for_warehouse;
    }

    $stmt = $db_link->prepare($sql);
    $stmt->execute($params);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($details as &$item) {
        $item['sum'] = number_format($item['sum'], 2, '.', '');
        $item['price'] = number_format($item['price'], 2, '.', '');
    }

    echo json_encode(['status' => true, 'data' => $details]);
    exit;
}

echo json_encode(['status' => false, 'message' => 'Unknown action']);
exit;