<?php
// helpers.php

/**
 * Отправка JSON-ответа с заданным HTTP-кодом
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Успешный ответ
 */
function successResponse($data = null, $warnings = [], $meta = []) {
    return [
        'status' => 'success',
        'data' => $data,
        'warnings' => $warnings,
        'meta' => $meta
    ];
}

/**
 * Ошибочный ответ
 */
function errorResponse($message, $code = 400, $errors = []) {
    return [
        'status' => 'error',
        'message' => $message,
        'errors' => $errors,
        'code' => $code
    ];
}

/**
 * Валидация входящих данных
 */
function validateSearchRequest($input) {
    $errors = [];
    
    if (empty($input['article'])) {
        $errors['article'] = 'Артикул обязателен';
    } else {
        // Нормализация артикула: удаление лишних символов
        $input['article'] = normalizeArticle($input['article']);
    }
    
    if (isset($input['manufacturer']) && !is_string($input['manufacturer'])) {
        $errors['manufacturer'] = 'Производитель должен быть строкой';
    }
    
    if (isset($input['geo_id']) && !is_numeric($input['geo_id'])) {
        $errors['geo_id'] = 'geo_id должен быть числом';
    }
    
    // Доп. валидация фильтров
    if (isset($input['filters'])) {
        // можно добавить проверку полей
    }
    
    return ['valid' => empty($errors), 'errors' => $errors, 'data' => $input];
}

/**
 * Нормализация артикула (удаление пробелов, дефисов и т.д.)
 */
function normalizeArticle($article) {
    $sweep = [" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t"];
    $article = str_replace($sweep, "", $article);
    return strtoupper($article);
}

/**
 * Логирование сообщений
 */
function logMessage($message, $level = 'INFO') {
    $logEntry = date('Y-m-d H:i:s') . " [$level] " . $message . PHP_EOL;
    file_put_contents(LOG_PATH, $logEntry, FILE_APPEND);
}

/**
 * Получение geo_id пользователя (из куки или параметра)
 */
function getGeoId($input) {
    if (isset($input['geo_id']) && $input['geo_id'] > 0) {
        return (int) $input['geo_id'];
    }
    if (isset($_COOKIE['my_city'])) {
        return (int) $_COOKIE['my_city'];
    }
    return DEFAULT_GEO_ID;
}