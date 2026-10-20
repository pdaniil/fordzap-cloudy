<?php
header('Content-Type: application/json; charset=utf-8');

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
} catch (PDOException $e) {
    echo json_encode([]);
    exit;
}
$db_link->query("SET NAMES utf8;");

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
if (!$user_id) {
    echo json_encode([]);
    exit;
}

$query = $db_link->prepare("SELECT id, caption, marka, model FROM shop_docpart_garage WHERE user_id = ? ORDER BY id DESC");
$query->execute([$user_id]);
$cars = $query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($cars);
?>