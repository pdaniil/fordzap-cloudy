<?php
/**
 * API модуля «Рекламация»
 * GET  ?action=get&car_id=123&record_id=456
 * POST  action=save  car_id=123  record_id=456  ...
 */
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
header('Content-Type: application/json; charset=utf-8');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) { header('Content-Type: application/json; charset=utf-8'); http_response_code(200); }
        echo json_encode(['status' => false, 'message' => 'PHP fatal: ' . $e['message']], JSON_UNESCAPED_UNICODE);
    }
});

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db_link->query("SET NAMES utf8mb4;");
} catch (PDOException $e) { echo json_encode(['status' => false, 'message' => 'БД: ' . $e->getMessage()]); exit; }

require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");
DP_User::getAdminSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$carId = (int)($_POST['car_id'] ?? $_GET['car_id'] ?? 0);
$recordId = (int)($_POST['record_id'] ?? $_GET['record_id'] ?? 0);

if ($carId <= 0) { echo json_encode(['status' => false, 'message' => 'Не указан ID автомобиля']); exit; }

// Проверяем, что таблица существует
$tableExists = false;
try { $db_link->query("SELECT 1 FROM car_service_reclamations LIMIT 1"); $tableExists = true; } catch (Exception $e) {}

if ($action === 'get') {
    // Получаем данные авто
    $stmt = $db_link->prepare("SELECT * FROM shop_docpart_garage WHERE id = ?");
    $stmt->execute([$carId]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$car) { echo json_encode(['status' => false, 'message' => 'Автомобиль не найден']); exit; }

    // Получаем данные клиента из последней записи, если есть
    $client = null;
    if ($recordId > 0) {
        $s = $db_link->prepare("SELECT user_id FROM car_service_records WHERE id = ?");
        $s->execute([$recordId]);
        $uid = (int)$s->fetchColumn();
        if ($uid) {
            $s = $db_link->prepare("SELECT u.email, u.phone, (SELECT data_value FROM users_profiles WHERE user_id=u.user_id AND data_key='surname') AS surname, (SELECT data_value FROM users_profiles WHERE user_id=u.user_id AND data_key='name') AS name FROM users u WHERE u.user_id = ?");
            $s->execute([$uid]);
            $client = $s->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    // Получаем существующую рекламацию (если есть)
    $reclamation = null;
    if ($tableExists) {
        $stmt = $db_link->prepare("SELECT * FROM car_service_reclamations WHERE car_id = ? AND record_id = ?");
        $stmt->execute([$carId, $recordId]);
        $reclamation = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    echo json_encode(['status' => true, 'car' => $car, 'client' => $client, 'reclamation' => $reclamation, 'table_exists' => $tableExists], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save') {
    if (!$tableExists) { echo json_encode(['status' => false, 'message' => 'Таблица рекламаций не создана. Выполните SQL-миграцию.']); exit; }

    $claimType = trim((string)($_POST['claim_type'] ?? ''));
    $defectDesc = trim((string)($_POST['defect_desc'] ?? ''));
    $additionalInfo = trim((string)($_POST['additional_info'] ?? ''));
    $claimDate = trim((string)($_POST['claim_date'] ?? ''));

    if ($claimType === '' || $defectDesc === '') { echo json_encode(['status' => false, 'message' => 'Заполните обязательные поля']); exit; }

    try {
        $stmt = $db_link->prepare("
            INSERT INTO car_service_reclamations
                (car_id, record_id, claim_type, defect_desc, additional_info, claim_date, created_at)
            VALUES (:car_id, :record_id, :claim_type, :defect_desc, :additional_info, :claim_date, NOW())
            ON DUPLICATE KEY UPDATE
                claim_type = VALUES(claim_type),
                defect_desc = VALUES(defect_desc),
                additional_info = VALUES(additional_info),
                claim_date = VALUES(claim_date),
                updated_at = NOW()
        ");
        $stmt->execute([
            ':car_id' => $carId,
            ':record_id' => $recordId,
            ':claim_type' => $claimType,
            ':defect_desc' => $defectDesc,
            ':additional_info' => $additionalInfo,
            ':claim_date' => $claimDate ?: null
        ]);
    } catch (Exception $e) { echo json_encode(['status' => false, 'message' => 'SQL save: ' . $e->getMessage()]); exit; }

    echo json_encode(['status' => true, 'message' => 'Сохранено'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['status' => false, 'message' => 'Неизвестное действие: ' . $action]);