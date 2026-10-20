<?php
header('Content-Type: application/json');

// Получаем путь и метод из клиентского запроса
$path = $_GET['path'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if (empty($path)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing path']);
    exit;
}

$targetUrl = 'https://api.fordzap.com/' . $path;

// Для GET-запросов передаём query-параметры
if ($method === 'GET') {
    $query = http_build_query($_GET);
    if (!empty($query)) {
        // Убираем 'path' из параметров
        $params = $_GET;
        unset($params['path']);
        $targetUrl .= '?' . http_build_query($params);
    }
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Для POST-запросов передаём тело
if ($method === 'POST') {
    $input = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpCode);
echo $response;
?>