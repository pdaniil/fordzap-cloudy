<?php
/**
 * API модуля «Акт приёма ТС»
 * Устойчивая версия: возвращает JSON, а не 500 при ошибках.
 */
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

// Отдаём JSON всегда — даже при фатальных ошибках
header('Content-Type: application/json; charset=utf-8');

// Ловим фатальные ошибки и превращаем в JSON
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
        }
        echo json_encode([
            'status'  => false,
            'message' => 'PHP fatal: ' . $e['message'] . ' в ' . basename($e['file']) . ':' . $e['line']
        ], JSON_UNESCAPED_UNICODE);
    }
});

/* ============================================================
 * ВАЖНО: $db_link — глобальная переменная, которую ожидает dp_user.php
 * ============================================================ */
try {
    $db_link = new PDO(
        'mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db,
        $DP_Config->user, $DP_Config->password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_link->query("SET NAMES utf8mb4;");
} catch (PDOException $e) {
    echo json_encode(['status' => false, 'message' => 'БД: ' . $e->getMessage()]);
    exit;
}

// Теперь безопасно подключаем и дёргаем админ-сессию
require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");
DP_User::getAdminSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$carId  = (int)($_POST['car_id'] ?? $_GET['car_id'] ?? 0);

if ($carId <= 0) {
    echo json_encode(['status' => false, 'message' => 'Не указан ID автомобиля']);
    exit;
}

/* ============================================================
 * Проверяем, что таблица актов существует
 * ============================================================ */
$tableExists = false;
try {
    $db_link->query("SELECT 1 FROM car_service_acceptance_acts LIMIT 1");
    $tableExists = true;
} catch (Exception $e) {
    $tableExists = false;
}

/* ============================================================
 * Определяем, какие колонки есть в shop_docpart_garage
 * ============================================================ */
$carColumns = [];
try {
    $cols = $db_link->query("SHOW COLUMNS FROM shop_docpart_garage")->fetchAll(PDO::FETCH_COLUMN, 0);
    $carColumns = array_flip($cols);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Не удалось получить структуру shop_docpart_garage']);
    exit;
}

$carFields = ['id'];
foreach (['marka','model','vin','year','license_plate','body_type','color','note'] as $f) {
    if (isset($carColumns[$f])) $carFields[] = "`$f`";
}
$carFieldsSql = implode(', ', $carFields);

/* ============================================================
 * GET — акт + информация об авто
 * ============================================================ */
if ($action === 'get') {

    try {
        $stmt = $db_link->prepare("SELECT $carFieldsSql FROM shop_docpart_garage WHERE id = ?");
        $stmt->execute([$carId]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'SQL авто: ' . $e->getMessage()]);
        exit;
    }

    if (!$car) {
        echo json_encode(['status' => false, 'message' => 'Автомобиль не найден']);
        exit;
    }
    foreach (['marka','model','vin','year','license_plate','body_type','color','note'] as $f) {
        if (!array_key_exists($f, $car)) $car[$f] = '';
    }

    $act = null;
    if ($tableExists) {
        try {
            $stmt = $db_link->prepare("SELECT id, damages, paint_defects, special_notes,
                                              equipment, fuel_level, mileage
                                       FROM car_service_acceptance_acts WHERE car_id = ?");
            $stmt->execute([$carId]);
            $act = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'message' => 'SQL акт: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($act) {
        $act['damages']   = json_decode($act['damages'], true) ?: [];
        $act['equipment'] = json_decode($act['equipment'], true) ?: [];
        $act['fuel_level']= (int)$act['fuel_level'];
        $act['mileage']   = ($act['mileage'] === null) ? null : (int)$act['mileage'];
    } else {
        $lastMileage = null;
        try {
            $stmt = $db_link->prepare("SELECT mileage FROM car_service_records
                                       WHERE car_id = ? AND mileage IS NOT NULL AND mileage > 0
                                       ORDER BY time DESC LIMIT 1");
            $stmt->execute([$carId]);
            $m = $stmt->fetchColumn();
            if ($m !== false && $m !== null) $lastMileage = (int)$m;
        } catch (Exception $e) { /* ok */ }

        $act = [
            'damages'       => [],
            'paint_defects' => '',
            'special_notes' => '',
            'equipment'     => [],
            'fuel_level'    => 0,
            'mileage'       => $lastMileage
        ];
    }

    echo json_encode([
        'status'       => true,
        'car'          => $car,
        'act'          => $act,
        'table_exists' => $tableExists
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ============================================================
 * POST — сохранение
 * ============================================================ */
if ($action === 'save') {

    if (!$tableExists) {
        echo json_encode([
            'status'  => false,
            'message' => 'Таблица car_service_acceptance_acts не создана. Выполните SQL-миграцию.'
        ]);
        exit;
    }

    $damages = json_decode($_POST['damages'] ?? '[]', true);
    if (!is_array($damages)) $damages = [];

    $equipment = json_decode($_POST['equipment'] ?? '[]', true);
    if (!is_array($equipment)) $equipment = [];

    $paintDefects = trim((string)($_POST['paint_defects'] ?? ''));
    $specialNotes = trim((string)($_POST['special_notes'] ?? ''));
    $fuelLevel    = max(0, min(4, (int)($_POST['fuel_level'] ?? 0)));
    $mileageRaw   = trim((string)($_POST['mileage'] ?? ''));
    $mileage      = ($mileageRaw === '') ? null : max(0, (int)$mileageRaw);

    $validViews = ['left','right','front','rear','top'];
    $validTypes = ['dent','chip','crack','scratch'];
    $damagesClean = [];
    foreach ($damages as $d) {
        if (!is_array($d)) continue;
        if (!in_array($d['view'] ?? '', $validViews, true)) continue;
        if (!in_array($d['type'] ?? '', $validTypes, true)) continue;
        $damagesClean[] = [
            'view' => $d['view'],
            'x'    => round((float)($d['x'] ?? 0), 2),
            'y'    => round((float)($d['y'] ?? 0), 2),
            'type' => $d['type']
        ];
    }

    $equipmentClean = [];
    foreach ($equipment as $e) {
        $e = trim((string)$e);
        if ($e !== '' && mb_strlen($e) <= 155) $equipmentClean[] = $e;
    }

    try {
        $stmt = $db_link->prepare("
            INSERT INTO car_service_acceptance_acts
                (car_id, damages, paint_defects, special_notes, equipment, fuel_level, mileage)
            VALUES
                (:car_id, :damages, :paint_defects, :special_notes, :equipment, :fuel_level, :mileage)
            ON DUPLICATE KEY UPDATE
                damages       = VALUES(damages),
                paint_defects = VALUES(paint_defects),
                special_notes = VALUES(special_notes),
                equipment     = VALUES(equipment),
                fuel_level    = VALUES(fuel_level),
                mileage       = VALUES(mileage)
        ");
        $stmt->execute([
            ':car_id'        => $carId,
            ':damages'       => json_encode($damagesClean, JSON_UNESCAPED_UNICODE),
            ':paint_defects' => $paintDefects,
            ':special_notes' => $specialNotes,
            ':equipment'     => json_encode($equipmentClean, JSON_UNESCAPED_UNICODE),
            ':fuel_level'    => $fuelLevel,
            ':mileage'       => $mileage
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'SQL save: ' . $e->getMessage()]);
        exit;
    }

    if ($mileage !== null) {
        try {
            $upd = $db_link->prepare("UPDATE car_service_records
                                      SET mileage = ?
                                      WHERE car_id = ? AND (mileage IS NULL OR mileage = 0)
                                      ORDER BY time DESC LIMIT 1");
            $upd->execute([$mileage, $carId]);
        } catch (Exception $e) { /* ok */ }
    }

    echo json_encode(['status' => true, 'message' => 'Сохранено'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['status' => false, 'message' => 'Неизвестное действие: ' . $action]);