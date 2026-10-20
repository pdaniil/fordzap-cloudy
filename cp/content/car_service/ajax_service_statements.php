<?php
/**
 * Обработка «стейтментов» по услугам записи.
 * Ключ отслеживания — (record_id + caption), т.к. service_record_id меняется при пересохранении записи.
 */
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'No DB connect']);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");
DP_User::getAdminSession();

header('Content-Type: application/json');

$action     = isset($_POST['action'])     ? (string)$_POST['action']       : '';
$record_id  = isset($_POST['record_id'])  ? (int)$_POST['record_id']       : 0;
$service_id = isset($_POST['service_id']) ? (int)$_POST['service_id']      : 0;
$caption    = isset($_POST['caption'])    ? trim((string)$_POST['caption']) : '';

if (!$record_id || $caption === '') {
    echo json_encode(['status' => false, 'message' => 'Не переданы обязательные параметры']);
    exit;
}

// --- Проверяем запись ---
$stmt = $db_link->prepare("SELECT * FROM car_service_records WHERE id = ? AND sto_id = ?");
$stmt->execute([$record_id, $service_id]);
$recordData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recordData) {
    echo json_encode(['status' => false, 'message' => 'Запись не найдена']);
    exit;
}

// --- Проверяем статус «Выполнена» ---
$orderStatus = null;
if (!empty($recordData['orders_ids'])) {
    $orderStmt = $db_link->prepare("SELECT status FROM shop_orders WHERE id = ?");
    $orderStmt->execute([$recordData['orders_ids']]);
    $order = $orderStmt->fetch();
    if ($order) $orderStatus = (int)$order['status'];
}
$isCompleted = ($recordData['time_to'] <= time()) && in_array($orderStatus, [3,6,9]);

if ($isCompleted) {
    echo json_encode(['status' => false, 'message' => 'Запись выполнена — действия запрещены']);
    exit;
}

// --- Проверяем, что услуга с таким caption реально есть в этой записи ---
$stmt = $db_link->prepare("SELECT id FROM car_service_record_services WHERE record_id = ? AND caption = ? LIMIT 1");
$stmt->execute([$record_id, $caption]);
$service_record_id = (int)$stmt->fetchColumn();

if (!$service_record_id) {
    echo json_encode(['status' => false, 'message' => 'Услуга не найдена в этой записи']);
    exit;
}

$order_id = (int)$recordData['orders_ids'];
$now      = time();

/**
 * Последний стейтмент по услуге (record_id + caption).
 */
function getCurrentStatement($db_link, $record_id, $caption) {
    $stmt = $db_link->prepare("
        SELECT * FROM car_service_record_services_statements
        WHERE record_id = ? AND caption = ?
        ORDER BY time DESC, id DESC
        LIMIT 1
    ");
    $stmt->execute([$record_id, $caption]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Удалить стейтменты заданного типа по услуге.
 */
function removeStatementsByType($db_link, $record_id, $caption, $type) {
    $stmt = $db_link->prepare("
        DELETE FROM car_service_record_services_statements
        WHERE record_id = ? AND caption = ? AND statement_type = ?
    ");
    $stmt->execute([$record_id, $caption, $type]);
}

/**
 * Вставить новый стейтмент.
 */
function insertStatement($db_link, $record_id, $service_record_id, $order_id, $caption, $type, $now) {
    $ins = $db_link->prepare("
        INSERT INTO car_service_record_services_statements
            (service_record_id, order_id, caption, statement_type, time, record_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    return $ins->execute([$service_record_id, $order_id, $caption, $type, $now, $record_id]);
}

switch ($action) {

    // ============ РАБОТА ВЫПОЛНЕНА ============
    case 'toggle_done': {
        $done = isset($_POST['done']) && $_POST['done'] === '1';

        $current = getCurrentStatement($db_link, $record_id, $caption);
        if ($current && (int)$current['statement_type'] === 1) {
            echo json_encode(['status' => false, 'message' => 'Сначала отмените запрос на удаление']);
            exit;
        }

        if ($done) {
            removeStatementsByType($db_link, $record_id, $caption, 2);
            insertStatement($db_link, $record_id, $service_record_id, $order_id, $caption, 2, $now);
            echo json_encode(['status' => true, 'statement_type' => 2]);
        } else {
            removeStatementsByType($db_link, $record_id, $caption, 2);
            echo json_encode(['status' => true, 'statement_type' => 0]);
        }
        break;
    }

    // ============ ЗАПРОС НА УДАЛЕНИЕ ============
    case 'request_delete': {
        $current = getCurrentStatement($db_link, $record_id, $caption);
        if ($current && (int)$current['statement_type'] === 2) {
            echo json_encode(['status' => false, 'message' => 'Сначала снимите отметку «Работа выполнена»']);
            exit;
        }
        removeStatementsByType($db_link, $record_id, $caption, 1);
        insertStatement($db_link, $record_id, $service_record_id, $order_id, $caption, 1, $now);
        echo json_encode(['status' => true, 'statement_type' => 1]);
        break;
    }

    // ============ ОТМЕНА ЗАПРОСА ============
    case 'cancel_delete': {
        removeStatementsByType($db_link, $record_id, $caption, 1);
        echo json_encode(['status' => true, 'statement_type' => 0]);
        break;
    }

    default:
        echo json_encode(['status' => false, 'message' => 'Неизвестное действие']);
}