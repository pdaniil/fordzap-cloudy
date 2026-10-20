<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit(json_encode(["status" => false, "message" => "No DB connect"]));
}
$db_link->query("SET NAMES utf8;");

require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");

$action = isset($_POST['action']) ? trim($_POST['action']) : 'save';
$car_id = isset($_POST['car_id']) ? (int)$_POST['car_id'] : 0;

// -------------------- GET (для редактирования) --------------------
if ($action === 'get') {
    if ($car_id <= 0) {
        exit(json_encode(["status" => false, "message" => "Не указан ID авто"]));
    }
    $stmt = $db_link->prepare("SELECT * FROM shop_docpart_garage WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$car) {
        exit(json_encode(["status" => false, "message" => "Автомобиль не найден"]));
    }
    echo json_encode(["status" => true, "car" => $car], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------- SAVE (создание / обновление) --------------------
$user_id        = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$caption        = isset($_POST['caption']) ? trim($_POST['caption']) : '';
$marka          = isset($_POST['marka']) ? trim($_POST['marka']) : '';
$model          = isset($_POST['model']) ? trim($_POST['model']) : '';
$year           = (isset($_POST['year']) && $_POST['year'] !== '') ? (int)$_POST['year'] : null;
$body_type      = isset($_POST['body_type']) ? trim($_POST['body_type']) : '';
$engine_value   = isset($_POST['engine_value']) ? trim($_POST['engine_value']) : '';
$fuel_type      = isset($_POST['fuel_type']) ? trim($_POST['fuel_type']) : '';
$transmission   = isset($_POST['transmission']) ? trim($_POST['transmission']) : '';
$vin            = isset($_POST['vin']) ? trim($_POST['vin']) : '';
$frame          = isset($_POST['frame']) ? trim($_POST['frame']) : '';
$color          = isset($_POST['color']) ? trim($_POST['color']) : '';
$country        = isset($_POST['country']) ? trim($_POST['country']) : '';
$wheel          = isset($_POST['wheel']) ? trim($_POST['wheel']) : '';
$license_plate  = isset($_POST['license_plate']) ? trim($_POST['license_plate']) : '';
$note           = isset($_POST['note']) ? trim($_POST['note']) : '';

// Валидация
if ($user_id <= 0) {
    exit(json_encode(["status" => false, "message" => "Не выбран клиент"]));
}
if ($caption === '') {
    exit(json_encode(["status" => false, "message" => "Укажите название автомобиля"]));
}
if ($marka === '' || $model === '') {
    exit(json_encode(["status" => false, "message" => "Укажите марку и модель"]));
}
if ($vin === '') {
    exit(json_encode(["status" => false, "message" => "Укажите VIN"]));
}

try {
    if ($car_id > 0) {
        // --- Редактирование ---
        $check = $db_link->prepare("SELECT id FROM shop_docpart_garage WHERE id = ?");
        $check->execute([$car_id]);
        if (!$check->fetch()) {
            exit(json_encode(["status" => false, "message" => "Автомобиль не найден"]));
        }

        $sql = "UPDATE shop_docpart_garage SET
                    active = 1,
                    caption = ?,
                    marka = ?,
                    model = ?,
                    year = ?,
                    body_type = ?,
                    engine_value = ?,
                    fuel_type = ?,
                    transmission = ?,
                    vin = ?,
                    frame = ?,
                    color = ?,
                    country = ?,
                    wheel = ?,
                    license_plate = ?,
                    note = ?,
                    user_id = ?
                WHERE id = ?";
        $stmt = $db_link->prepare($sql);
        $stmt->execute([
            $caption, $marka, $model, $year, $body_type, $engine_value,
            $fuel_type, $transmission, $vin, $frame, $color, $country,
            $wheel, $license_plate, $note, $user_id, $car_id
        ]);
        $saved_id = $car_id;
    } else {
        // --- Создание ---
        $sql = "INSERT INTO shop_docpart_garage
                    (active, caption, marka, model, year, body_type, engine_value,
                     fuel_type, transmission, vin, frame, color, country, wheel,
                     license_plate, note, user_id)
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db_link->prepare($sql);
        $stmt->execute([
            $caption, $marka, $model, $year, $body_type, $engine_value,
            $fuel_type, $transmission, $vin, $frame, $color, $country,
            $wheel, $license_plate, $note, $user_id
        ]);
        $saved_id = (int)$db_link->lastInsertId();
    }

    // Возвращаем полные данные автомобиля
    $stmt = $db_link->prepare("SELECT * FROM shop_docpart_garage WHERE id = ?");
    $stmt->execute([$saved_id]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);

    // Формируем текст для селекта
    $parts = [];
    if (!empty($car['caption'])) $parts[] = $car['caption'];
    $title = trim(($car['marka'] ?? '') . ' ' . ($car['model'] ?? ''));
    if ($title !== '') $parts[] = $title;
    if (!empty($car['vin'])) $parts[] = '(VIN: ' . $car['vin'] . ')';
    $display_text = implode(' ', $parts);

    echo json_encode([
        "status" => true,
        "car_id" => (int)$saved_id,
        "display_text" => $display_text,
        "car" => $car
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(["status" => false, "message" => $e->getMessage()]);
}