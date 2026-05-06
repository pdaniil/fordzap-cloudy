<?php
// Максимально простой тест
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$result = [
    'success' => true,
    'message' => 'Simple API is working',
    'time' => time(),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown'
];

echo json_encode($result, JSON_PRETTY_PRINT);
?>