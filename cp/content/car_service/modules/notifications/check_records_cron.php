<?php
/**
 * Скрипт для отправки уведомлений о предстоящих записях
 * Запускается по cron каждый час
 * Путь: /cp/content/car_service/modules/notifications/check_records_cron.php
 */

// Отключаем вывод ошибок в браузер для cron
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Функция для логирования
function writeLog($message) {
    $logFile = __DIR__ . '/notifications.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Подключаем конфиг и автозагрузку
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");

use TelegramBot\Api\Client;

try {
    $DP_Config = new DP_Config();
    
    // Подключение к БД
    $db = new PDO(
        'mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db,
        $DP_Config->user,
        $DP_Config->password
    );
    $db->query("SET NAMES utf8mb4;");
    
    writeLog("Начало проверки записей");
    
    // Текущее время + 1 час (с погрешностью 15 минут)
    $now = time();
    $hourLater = $now + 3600; // +1 час
    $timeFrom = $hourLater - 900; // -15 минут (1 час - 15 мин)
    $timeTo = $hourLater + 900;   // +15 минут (1 час + 15 мин)
    
    writeLog("Поиск записей от " . date('Y-m-d H:i:s', $timeFrom) . " до " . date('Y-m-d H:i:s', $timeTo));
    
    // ШАГ 1: Получаем записи, которые будут через час
    $records_sql = "
        SELECT 
            r.id,
            r.time_from,
            r.worker_id,
            r.user_id,
            r.car_id,
            r.sto_id,
            r.mileage
        FROM `car_service_records` r
        WHERE r.time_from BETWEEN :time_from AND :time_to
          AND r.is_archive = 0
        ORDER BY r.time_from ASC
    ";
    
    $stmt = $db->prepare($records_sql);
    $stmt->execute([
        ':time_from' => $timeFrom,
        ':time_to' => $timeTo
    ]);
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    writeLog("Найдено записей: " . count($records));
    
    if (empty($records)) {
        writeLog("Нет записей для уведомлений");
        exit;
    }
    
    // Для каждой записи собираем данные и отправляем уведомления
    foreach ($records as $record) {
        $recordId = $record['id'];
        $recordTime = date('H:i', $record['time_from']);
        $recordDate = date('d.m.Y', $record['time_from']);
        
        writeLog("Обработка записи ID: {$recordId}");
        
        // ШАГ 2: Получаем данные сервиса
        $service_sql = "SELECT caption, address, phone FROM car_services WHERE id = ?";
        $stmt = $db->prepare($service_sql);
        $stmt->execute([$record['sto_id']]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ШАГ 3: Получаем данные мастера и его user_id
        $worker_user_id = null;
        $worker_name = null;
        
        if (!empty($record['worker_id'])) {
            $worker_sql = "SELECT fio, user_id FROM car_service_personal WHERE id = ?";
            $stmt = $db->prepare($worker_sql);
            $stmt->execute([$record['worker_id']]);
            $worker = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($worker) {
                $worker_name = $worker['fio'];
                $worker_user_id = $worker['user_id'];
            }
        }
        
        // ШАГ 4: Получаем данные клиента
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
        
        // ШАГ 5: Получаем данные автомобиля
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
        
        // Добавляем пробег если есть
        if (!empty($record['mileage'])) {
            $car_info .= ', пробег: ' . $record['mileage'] . ' км';
        }
        
        // 1. Уведомление МАСТЕРУ (через его user_id из таблицы car_service_personal)
        if (!empty($worker_user_id)) {
            $workerMessage = "🔧 *НАПОМИНАНИЕ О ЗАПИСИ*\n\n";
            $workerMessage .= "Через час у вас запись:\n";
            $workerMessage .= "🕐 Время: {$recordDate} в {$recordTime}\n";
            $workerMessage .= "👤 Клиент: {$client_name}\n";
            $workerMessage .= "📞 Телефон: " . ($client_phone ?: 'не указан') . "\n";
            $workerMessage .= "🚗 Авто: {$car_info}\n";
            $workerMessage .= "📍 Сервис: " . ($service['caption'] ?? '') . "\n";
            
            if (!empty($service['address'])) {
                $workerMessage .= "🏢 Адрес: " . $service['address'] . "\n";
            }
            
            $workerMessage .= "\nНе забудьте подготовить рабочее место!";
            
            sendTelegramNotification($db, $DP_Config, $worker_user_id, $workerMessage);
            writeLog("Уведомление отправлено мастеру (worker_id: {$record['worker_id']}, user_id: {$worker_user_id}) для записи ID: {$recordId}");
        }
        
        // 2. Уведомление КЛИЕНТУ
        if (!empty($record['user_id'])) {
            $clientMessage = "🚗 *НАПОМИНАНИЕ О ЗАПИСИ В АВТОСЕРВИС*\n\n";
            $clientMessage .= "Уважаемый(ая) {$client_name}!\n\n";
            $clientMessage .= "Напоминаем, что через час у вас запись в автосервис:\n";
            $clientMessage .= "🕐 Дата и время: {$recordDate} в {$recordTime}\n";
            $clientMessage .= "🔧 Мастер: " . ($worker_name ?: 'назначен') . "\n";
            $clientMessage .= "📍 Сервис: " . ($service['caption'] ?? '') . "\n";
            
            if (!empty($service['address'])) {
                $clientMessage .= "🏢 Адрес: " . $service['address'] . "\n";
            }
            if (!empty($service['phone'])) {
                $clientMessage .= "📞 Телефон сервиса: " . $service['phone'] . "\n";
            }
            
            $clientMessage .= "\n🚗 Ваш автомобиль: {$car_info}\n";
            $clientMessage .= "\nЖдем вас!";
            
            sendTelegramNotification($db, $DP_Config, $record['user_id'], $clientMessage);
            writeLog("Уведомление отправлено клиенту ID: {$record['user_id']} для записи ID: {$recordId}");
        }
        
        // 3. Уведомление АДМИНИСТРАТОРУ (user_id = 1)
        $adminMessage = "📋 *УВЕДОМЛЕНИЕ О ПРЕДСТОЯЩЕЙ ЗАПИСИ*\n\n";
        $adminMessage .= "Через час запись:\n";
        $adminMessage .= "🕐 Время: {$recordDate} в {$recordTime}\n";
        $adminMessage .= "🔧 Мастер: " . ($worker_name ?: 'не назначен') . "\n";
        $adminMessage .= "👤 Клиент: {$client_name}\n";
        $adminMessage .= "📞 Тел. клиента: " . ($client_phone ?: 'не указан') . "\n";
        $adminMessage .= "🚗 Авто: {$car_info}\n";
        $adminMessage .= "📍 Сервис: " . ($service['caption'] ?? '') . "\n";
        
        if (!empty($service['address'])) {
            $adminMessage .= "🏢 Адрес: " . $service['address'] . "\n";
        }
        
        sendTelegramNotification($db, $DP_Config, 48, $adminMessage);
        writeLog("Уведомление отправлено администратору (ID: 1) для записи ID: {$recordId}");
        
        // Небольшая задержка между отправками
        usleep(500000); // 0.5 секунды
    }
    
    writeLog("Обработка завершена");
    
} catch (Exception $e) {
    writeLog("ОШИБКА: " . $e->getMessage());
}

/**
 * Функция отправки уведомления в Telegram
 */
function sendTelegramNotification($db, $config, $userId, $message) {
    // Получаем chat_id пользователя
    $select_chat_id = $db->prepare("SELECT `chat_id` FROM `telegram_users` WHERE `user_id` = ?;");
    $select_chat_id->execute([$userId]);
    $res_select_chat_id = $select_chat_id->fetch(PDO::FETCH_ASSOC);
    
    if ($res_select_chat_id && !empty($res_select_chat_id['chat_id'])) {
        try {
            $bot = new Client($config->telegram_bot_token);
            $bot->sendMessage($res_select_chat_id['chat_id'], str_replace('&nbsp;', ' ', $message), 'HTML');
            writeLog("Telegram: сообщение отправлено пользователю {$userId}");
            return true;
        } catch (Exception $e) {
            writeLog("Ошибка отправки Telegram пользователю {$userId}: " . $e->getMessage());
            return false;
        }
    } else {
        writeLog("Telegram: не найден chat_id для пользователя {$userId}");
        return false;
    }
}
?>