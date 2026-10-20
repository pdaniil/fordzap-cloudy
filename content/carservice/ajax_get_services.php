<?php
header('Content-Type: application/json;charset=utf-8;');

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
} catch (PDOException $e) {
    exit(json_encode(['error' => 'DB connect error']));
}
$db_link->query("SET NAMES utf8;");

$tree_id = isset($_GET['tree_id']) ? (int)$_GET['tree_id'] : 0;
if (!$tree_id) {
    exit(json_encode(['services' => []]));
}

// Выбираем только конечные элементы (count = 0) из дерева услуг
$query = $db_link->prepare("
    SELECT `id`, `value` AS `caption` 
    FROM `shop_tree_lists_items` 
    WHERE `tree_list_id` = ? AND `count` = 0 
    ORDER BY `value`
");
$query->execute([$tree_id]);
$services = $query->fetchAll(PDO::FETCH_ASSOC);

exit(json_encode(['services' => $services]));