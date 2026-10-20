<?php
/**
 * Скрипт для отправки уведомления администратору о записи, созданной клиентом самостоятельно.
 * Вызывается по AJAX с параметром record_id.
 * Путь: /cp/content/car_service/modules/notifications/send_admin_notification.php
 */

// Отключаем вывод ошибок для AJAX (но логируем)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Заголовки для JSON-ответа
header('Content-Type: application/json; charset=utf-8');

// Константа ID администратора в системе (Telegram-уведомления)
define('ADMIN_USER_ID', 48); // замените на реальный user_id администратора

// Функция логирования (в тот же лог-файл)
function writeLog($message) {
    $logFile = __DIR__ . '/notifications.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Функция получения списка услуг для записи (из car_service_record_services)
function getRecordServices($db, $recordId) {
    $services = [];
    $sql = "SELECT caption, count, price, hourly_rate, discount, fix_price 
            FROM car_service_record_services 
            WHERE record_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$recordId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Формируем строку услуги: название (количество, цена, норма час.)
        $serviceStr = $row['caption'];
        if ($row['count'] > 0) {
            $serviceStr .= " (x{$row['count']})";
        }
        if ($row['price'] > 0) {
            $serviceStr .= " - {$row['price']} руб.";
        }
        if ($row['hourly_rate'] > 0) {
            $serviceStr .= ", норма {$row['hourly_rate']} ч.";
        }
        // Можно добавить скидку/фикс цену, если нужно
        $services[] = $serviceStr;
    }
    return $services;
}

// Функция отправки уведомления в Telegram
function sendTelegramNotification($db, $config, $userId, $message) {
    $select_chat_id = $db->prepare("SELECT `chat_id` FROM `telegram_users` WHERE `user_id` = ?;");
    $select_chat_id->execute([$userId]);
    $res = $select_chat_id->fetch(PDO::FETCH_ASSOC);
    
    if ($res && !empty($res['chat_id'])) {
        try {
            $bot = new \TelegramBot\Api\Client($config->telegram_bot_token);
            $bot->sendMessage($res['chat_id'], str_replace('&nbsp;', ' ', $message), 'HTML');
            writeLog("Telegram: уведомление отправлено администратору (user_id: $userId)");
            return true;
        } catch (Exception $e) {
            writeLog("Ошибка отправки Telegram администратору: " . $e->getMessage());
            return false;
        }
    } else {
        writeLog("Telegram: не найден chat_id для администратора (user_id: $userId)");
        return false;
    }
}

// Основная обработка
try {
    // Проверяем, что передан record_id
    if (empty($_POST['record_id'])) {
        throw new Exception('Не передан ID записи');
    }
    
    $recordId = (int)$_POST['record_id'];
    writeLog("Получен запрос на уведомление для записи ID: $recordId");
    
    // Подключаем конфиг
    require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
    require_once ($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");
    
    $DP_Config = new DP_Config();
    
    // Подключение к БД
    $db = new PDO(
        'mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db,
        $DP_Config->user,
        $DP_Config->password
    );
    $db->query("SET NAMES utf8mb4;");
    
    // Получаем данные записи
    $sql = "SELECT id, time_from, worker_id, user_id, car_id, sto_id, mileage, post_id 
            FROM car_service_records 
            WHERE id = ? AND is_archive = 0";
    $stmt = $db->prepare($sql);
    $stmt->execute([$recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('Запись не найдена или находится в архиве');
    }
    
    // Проверяем, что запись создана клиентом самостоятельно (post_id = -1 и worker_id = -1)
    if ($record['post_id'] != -1 || $record['worker_id'] != -1) {
        throw new Exception('Данная запись не является самосозданной клиентом. Уведомление администратору не требуется.');
    }
    
    // Формируем данные для уведомления
    $recordTime = date('H:i', $record['time_from']);
    $recordDate = date('d.m.Y', $record['time_from']);
    
    // Сервис
    $service_sql = "SELECT caption, address, phone FROM car_services WHERE id = ?";
    $stmt = $db->prepare($service_sql);
    $stmt->execute([$record['sto_id']]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Клиент
    $client_name = 'Клиент';
    $client_phone = '';
    if (!empty($record['user_id'])) {
        $client_sql = "
            SELECT 
                u.phone,
                (SELECT data_value FROM users_profiles WHERE user_id = u.user_id AND data_key = 'name') as name,
                (SELECT data_value FROM users_profiles WHERE user_id = u.user_id AND data_key = 'surname') as surname
            FROM users u
            WHERE u.user_id = ?
        ";
        $stmt = $db->prepare($client_sql);
        $stmt->execute([$record['user_id']]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($client) {
            $client_name = trim(($client['surname'] ?? '') . ' ' . ($client['name'] ?? ''));
            if (empty($client_name)) $client_name = 'Клиент';
            $client_phone = $client['phone'] ?? '';
        }
    }
    
    // Автомобиль
    $car_info = 'автомобиль не указан';
    if (!empty($record['car_id'])) {
        $car_sql = "SELECT marka, model, license_plate FROM shop_docpart_garage WHERE id = ?";
        $stmt = $db->prepare($car_sql);
        $stmt->execute([$record['car_id']]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($car) {
            $car_info = trim(($car['marka'] ?? '') . ' ' . ($car['model'] ?? ''));
            if (!empty($car['license_plate'])) {
                $car_info .= ' (' . $car['license_plate'] . ')';
            }
        }
    }
    if (!empty($record['mileage'])) {
        $car_info .= ', пробег: ' . $record['mileage'] . ' км';
    }
    
    // Услуги
    $services = getRecordServices($db, $recordId);
    
    // Формируем сообщение для администратора
    $message = "🔔 *ЗАПИСЬ СОЗДАНА КЛИЕНТОМ САМОСТОЯТЕЛЬНО*\n\n";
    $message .= "⚠️ Требуется обзвон и подтверждение!\n\n";
    $message .= "🕐 Время: {$recordDate} в {$recordTime}\n";
    $message .= "👤 Клиент: {$client_name}\n";
    $message .= "📞 Телефон: " . ($client_phone ?: 'не указан') . "\n";
    $message .= "🚗 Авто: {$car_info}\n";
    $message .= "📍 Сервис: " . ($service['caption'] ?? '') . "\n";
    if (!empty($service['address'])) {
        $message .= "🏢 Адрес: " . $service['address'] . "\n";
    }
    if (!empty($services)) {
        $message .= "🛠 Услуги:\n";
        foreach ($services as $serviceStr) {
            $message .= " - {$serviceStr}\n";
        }
    } else {
        $message .= "🛠 Услуги: не указаны\n";
    }
    $message .= "\nНеобходимо связаться с клиентом для подтверждения записи.";
    
    // Отправляем уведомление администратору
    $sent = sendTelegramNotification($db, $DP_Config, ADMIN_USER_ID, $message);
    
    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'Уведомление администратору отправлено']);
        writeLog("Уведомление администратору для записи ID: $recordId успешно отправлено");
    } else {
        throw new Exception('Не удалось отправить уведомление администратору (проверьте логи)');
    }
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    writeLog("Ошибка в send_admin_notification: " . $errorMsg);
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}