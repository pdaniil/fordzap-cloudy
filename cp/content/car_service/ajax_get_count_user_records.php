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




$query = $db_link->prepare("SELECT COUNT(*) as `count` FROM `car_service_records` WHERE `post_id` = -1 AND worker_id = -1;");
$query->execute();
$cars = $query->fetch(PDO::FETCH_ASSOC);

echo json_encode($cars);
?>