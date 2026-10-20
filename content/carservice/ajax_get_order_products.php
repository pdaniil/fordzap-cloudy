<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
} catch (PDOException $e) {
    exit(json_encode(['status' => false, 'message' => 'No DB connect']));
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) {
    exit(json_encode(['status' => false, 'message' => 'Order ID required']));
}

// Получаем заказ (для проверки)
$orderStmt = $db_link->prepare("SELECT * FROM shop_orders WHERE id = ?");
$orderStmt->execute([$order_id]);
$orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);
if (!$orderData) {
    exit(json_encode(['status' => false, 'message' => 'Order not found']));
}

// Получаем товары заказа
$itemsStmt = $db_link->prepare("SELECT * FROM shop_orders_items WHERE order_id = ? AND status NOT IN (6, 20)");
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as &$item) {
    $sum = $item['price'] * $item['count_need'] * (1 - $item['discount'] / 100);
    $total += $sum;
    $item['sum'] = $sum;
}
  
header('Content-Type: application/json');
echo json_encode([
    'status' => true,
    'items' => $items,
    'total' => $total
]);