<?php
/**
 * Создание финансовой операции для оплаты заказа через интернет-эквайринг.
 * Админ-версия: используется из модального окна заказа автосервиса.
 * Обходит проблему CSRF shop-сессии — работает под админ-сессией.
 */
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO(
        'mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db,
        $DP_Config->user,
        $DP_Config->password
    );
    $db_link->query("SET NAMES utf8;");
} catch (PDOException $e) {
    header('Content-Type: application/json;charset=utf-8;');
    echo json_encode(['result' => false, 'message' => 'No DB Connect']);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");

// Проверяем, что мы под админом
$admin_id = DP_User::getAdminId();
if (!$admin_id) {
    header('Content-Type: application/json;charset=utf-8;');
    echo json_encode(['result' => false, 'message' => 'Доступ запрещён']);
    exit;
}
$user_session = DP_User::getAdminSession();

header('Content-Type: application/json;charset=utf-8;');

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$amount   = isset($_POST['amount'])   ? (float)$_POST['amount'] : 0;

if (!$order_id || $amount <= 0) {
    echo json_encode(['result' => false, 'message' => 'Неверные параметры']);
    exit;
}

// --- Заказ ---
$stmt = $db_link->prepare("SELECT * FROM shop_orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo json_encode(['result' => false, 'message' => 'Заказ не найден']);
    exit;
}

// Проверяем, что заказ принадлежит записи автосервиса
$stmt = $db_link->prepare("SELECT id FROM car_service_records WHERE orders_ids = ? LIMIT 1");
$stmt->execute([$order_id]);
if (!$stmt->fetchColumn()) {
    echo json_encode(['result' => false, 'message' => 'Заказ не привязан к записи автосервиса']);
    exit;
}

// --- Сумма по товарам (без отменённых позиций, статусы 6 и 20) ---
$stmt = $db_link->prepare("
    SELECT CAST(SUM(`price`*`count_need` - `price`*`count_need`*`discount`/100) AS DECIMAL(20,2))
    FROM shop_orders_items
    WHERE order_id = ? AND status NOT IN (6, 20)
");
$stmt->execute([$order_id]);
$items_sum = (float)$stmt->fetchColumn();

// --- Сумма по услугам автосервиса из записи, привязанной к этому заказу ---
$stmt = $db_link->prepare("
    SELECT CAST(SUM(
        `price` * `count` - `price` * `count` * `discount` / 100
    ) AS DECIMAL(20,2))
    FROM car_service_record_services
    WHERE record_id = (
        SELECT id FROM car_service_records WHERE orders_ids = ? LIMIT 1
    )
");
$stmt->execute([$order_id]);
$services_sum = (float)$stmt->fetchColumn();

// --- Полная сумма заказа = товары + услуги ---
$order_sum = $items_sum + $services_sum;

// Уже оплачено
$stmt = $db_link->prepare("SELECT IFNULL(SUM(amount),0) FROM shop_users_accounting WHERE active=1 AND income=1 AND order_id=?");
$stmt->execute([$order_id]);
$incomeSum = (float)$stmt->fetchColumn();

$stmt = $db_link->prepare("SELECT IFNULL(SUM(amount),0) FROM shop_users_accounting WHERE active=1 AND income=0 AND order_id=?");
$stmt->execute([$order_id]);
$outcomeSum = (float)$stmt->fetchColumn();

$paid_sum  = max(0, $outcomeSum - $incomeSum);
$paid_left = max(0, $order_sum - $paid_sum);

if ($paid_left <= 0.009) {
    echo json_encode(['result' => false, 'message' => 'Заказ уже полностью оплачен']);
    exit;
}

if ($amount > $paid_left + 0.009) {
    echo json_encode(['result' => false, 'message' => 'Сумма превышает остаток долга по заказу']);
    exit;
}

// Частичная оплата — та же логика, что и в оригинале
if ($amount < $paid_left - 0.009) {
    $partial_on  = isset($DP_Config->partial_payment) && $DP_Config->partial_payment;
    $min_percent = isset($DP_Config->partial_payment_min_percent) ? (float)$DP_Config->partial_payment_min_percent : 0;

    if (!$partial_on) {
        echo json_encode(['result' => false, 'message' => 'Частичная оплата не разрешена']);
        exit;
    }
    if ($amount < $order_sum * ($min_percent / 100) - 0.009) {
        echo json_encode(['result' => false, 'message' => 'Сумма меньше минимально допустимой']);
        exit;
    }
}

// --- Определяем платёжную систему ---
$office_id = (int)$order['office_id'];
$active_system = null;

if (isset($DP_Config->wholesaler)) {
    $stmt = $db_link->prepare('SELECT * FROM shop_payment_systems WHERE id = (SELECT pay_system_id FROM shop_offices WHERE id = ?)');
    $stmt->execute([$office_id]);
    $system = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $office_id = 0;
    $stmt = $db_link->prepare('SELECT * FROM shop_payment_systems WHERE active = 1');
    $stmt->execute();
    $system = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($system) {
    $active_system = $system['handler'];
}

if (!$active_system) {
    echo json_encode(['result' => false, 'message' => 'Платёжная система не подключена']);
    exit;
}

// --- Создаём операцию ---
// Привязываем к user_id ЗАКАЗА (клиента), не к админу.
$operation_key = '4_income_for_direct_pay';
$customer_id   = (int)$order['user_id'];

$ins = $db_link->prepare("
    INSERT INTO shop_users_accounting
        (user_id, time, income, amount, operation_code, active, pay_orders, office_id)
    VALUES (?, ?, 1, ?, ?, 0, ?, ?)
");
$ok = $ins->execute([$customer_id, time(), $amount, $operation_key, $order_id, $office_id]);

if (!$ok) {
    echo json_encode(['result' => false, 'message' => 'Ошибка создания операции']);
    exit;
}

$operation_id = (int)$db_link->lastInsertId();

echo json_encode([
    'result'     => true,
    'operation'  => $operation_id,
    'pay_system' => $active_system
]);