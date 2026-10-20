<?php
// router.php

function dispatch($method, $uri) {
    // Удаляем базовый путь /api/v1
    $basePath = '/api/v1';
    if (strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
    $uri = trim($uri, '/');
    
    // Разбор на сегменты
    $segments = explode('/', $uri);
    $action = $segments[0] ?? '';
    
    // Определяем обработчик
    switch ($action) {
        case 'search':
            if ($method !== 'POST') {
                sendJsonResponse(errorResponse('Метод не разрешен', 405), 405);
            }
            require_once __DIR__ . '/SearchHandler.php';
            handleSearchRequest();
            break;
        case 'health':
            sendJsonResponse(['status' => 'ok'], 200);
            break;
        default:
            sendJsonResponse(errorResponse('Неизвестный эндпоинт', 404), 404);
    }
}