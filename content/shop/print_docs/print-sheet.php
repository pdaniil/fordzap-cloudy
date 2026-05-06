<?php
/**
 * Страница печати разнорядки записей автосервиса
 * Версия: 5.0
 * Дата: 2026
 * Особенность: Минималистичные стили для печати
 */

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

// Получаем параметры
$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (!$serviceId) {
    die('Не указан ID сервиса');
}

// Подключение к БД
try {
    $config = new DP_Config();
    $db = new PDO('mysql:host=' . $config->host . ';dbname=' . $config->db, $config->user, $config->password);
    $db->query("SET NAMES utf8mb4;");
} catch (PDOException $e) {
    die('Ошибка подключения к БД');
}

// Получаем информацию о сервисе
$stmt = $db->prepare("SELECT * FROM `car_services` WHERE `id` = ?");
$stmt->execute([$serviceId]);
$service_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service_info) {
    die('Сервис не найден');
}

// Получаем ВСЕ записи на выбранный день
$sql = "
    SELECT 
        r.*,
        p.fio as worker_name,
        u.user_id,
        u.phone as user_phone,
        up_name.data_value as user_name,
        up_surname.data_value as user_surname,
        g.marka,
        g.model,
        g.license_plate,
        g.mileage as car_mileage,
        post.caption as post_caption
    FROM `car_service_records` r
    LEFT JOIN `car_service_personal` p ON r.worker_id = p.id
    LEFT JOIN `users` u ON r.user_id = u.user_id
    LEFT JOIN `users_profiles` up_name ON up_name.user_id = u.user_id AND up_name.data_key = 'name'
    LEFT JOIN `users_profiles` up_surname ON up_surname.user_id = u.user_id AND up_surname.data_key = 'surname'
    LEFT JOIN `shop_docpart_garage` g ON r.car_id = g.id
    LEFT JOIN `car_service_posts` post ON r.post_id = post.id
    WHERE r.sto_id = :sto_id 
        AND DATE(r.time) = :date
    ORDER BY r.post_id, r.time ASC
";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':sto_id' => $serviceId,
    ':date' => $date
]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем услуги
$servicesByRecord = [];
if (!empty($records)) {
    $recordIds = array_column($records, 'id');
    $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
    
    $stmt = $db->prepare("
        SELECT * FROM `car_service_record_services` 
        WHERE `record_id` IN ($placeholders)
        ORDER BY `id`
    ");
    $stmt->execute($recordIds);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($services as $service_item) {
        $servicesByRecord[$service_item['record_id']][] = $service_item;
    }
}

// Форматируем дату
$dateObj = new DateTime($date);
$formattedDate = $dateObj->format('d.m.Y');
$dayName = '';
switch ($dateObj->format('N')) {
    case 1: $dayName = 'Понедельник'; break;
    case 2: $dayName = 'Вторник'; break;
    case 3: $dayName = 'Среда'; break;
    case 4: $dayName = 'Четверг'; break;
    case 5: $dayName = 'Пятница'; break;
    case 6: $dayName = 'Суббота'; break;
    case 7: $dayName = 'Воскресенье'; break;
}

// Подсчет итогов
$totalSum = 0;
foreach ($records as $record) {
    if (isset($servicesByRecord[$record['id']])) {
        foreach ($servicesByRecord[$record['id']] as $service) {
            $price = $service['price'] ?? 0;
            $discount = $service['discount'] ?? 0;
            $count = $service['count'] ?? 1;
            $totalSum += $price * (1 - $discount/100) * $count;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Разнорядка <?php echo $formattedDate; ?></title>
    <style>
        /* Максимально простые стили для печати */
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            line-height: 1.4;
            color: #000;
        }
        
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        
        h2 {
            font-size: 18px;
            color: #666;
            margin-top: 5px;
            margin-bottom: 20px;
            font-weight: normal;
        }
        
        .service-info {
            margin-bottom: 20px;
            font-size: 14px;
            color: #555;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        
        th {
            background: #f0f0f0;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        td {
            padding: 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .post-cell {
            font-weight: bold;
            color: #0066cc;
        }
        
        .time-cell {
            font-weight: bold;
            white-space: nowrap;
        }
        
        .client-name {
            font-weight: bold;
        }
        
        .client-phone {
            color: #555;
            font-size: 13px;
        }
        
        .car-model {
            font-weight: bold;
        }
        
        .car-details {
            font-size: 12px;
            color: #555;
        }
        
        .services-list {
            font-size: 12px;
        }
        
        .service-item {
            margin-bottom: 5px;
        }
        
        .service-count {
            color: #555;
            font-size: 11px;
        }
        
        .price-cell {
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }
        
        .total-row {
            margin-top: 20px;
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            padding: 10px;
            background: #f9f9f9;
        }
        
        .print-footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
        
        .no-print {
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 8px 20px;
            margin-right: 10px;
            font-size: 14px;
            cursor: pointer;
        }
        
        /* Стили для печати */
        @media print {
            body {
                margin: 20px;
            }
            
            .no-print {
                display: none;
            }
            
            th {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .post-cell {
                color: #000;
                font-weight: bold;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.print()">🖨️ Печать</button>
        <button class="btn" onclick="window.close()">✕ Закрыть</button>
        <hr>
    </div>

    <h1><?php echo htmlspecialchars($service_info['caption']); ?></h1>
    <h2>Разнорядка на <?php echo $formattedDate; ?> (<?php echo $dayName; ?>)</h2>
    
    <div class="service-info">
        📍 <?php echo htmlspecialchars($service_info['address'] ?: 'Адрес не указан'); ?><br>
        📞 <?php echo htmlspecialchars($service_info['phone'] ?: 'Телефон не указан'); ?>
    </div>

    <?php if (empty($records)): ?>
        <p>На этот день нет записей</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Пост</th>
                    <th>Время</th>
                    <th>Клиент</th>
                    <th>Автомобиль</th>
                    <th>Мастер</th>
                    <th>Услуги</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): 
                    $recordTotal = 0;
                    $recordServices = $servicesByRecord[$record['id']] ?? [];
                ?>
                <tr>
                    <td class="post-cell">
                        <?php echo htmlspecialchars($record['post_caption'] ?: '—'); ?>
                    </td>
                    <td class="time-cell"><?php echo date('H:i', strtotime($record['time'])); ?></td>
                    <td>
                        <div class="client-name">
                            <?php 
                            $clientName = trim(($record['user_surname'] ?? '') . ' ' . ($record['user_name'] ?? ''));
                            echo !empty($clientName) ? htmlspecialchars($clientName) : 'Клиент не указан';
                            ?>
                        </div>
                        <?php if (!empty($record['user_phone'])): ?>
                            <div class="client-phone"><?php echo htmlspecialchars($record['user_phone']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($record['marka']) || !empty($record['model'])): ?>
                            <div class="car-model">
                                <?php echo htmlspecialchars(trim(($record['marka'] ?? '') . ' ' . ($record['model'] ?? ''))); ?>
                            </div>
                            <div class="car-details">
                                <?php if (!empty($record['license_plate'])): ?>
                                    Номер: <?php echo htmlspecialchars($record['license_plate']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($record['mileage']) || !empty($record['car_mileage'])): ?>
                                    Пробег: <?php echo htmlspecialchars($record['mileage'] ?: $record['car_mileage']); ?> км
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #999;">Не указано</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($record['worker_name'] ?: '—'); ?>
                    </td>
                    <td class="services-list">
                        <?php if (!empty($recordServices)): ?>
                            <?php foreach ($recordServices as $service): 
                                $price = $service['price'] ?? 0;
                                $discount = $service['discount'] ?? 0;
                                $count = $service['count'] ?? 1;
                                $finalPrice = $price * (1 - $discount/100) * $count;
                                $recordTotal += $finalPrice;
                            ?>
                                <div class="service-item">
                                    <?php echo htmlspecialchars($service['caption'] ?: 'Услуга'); ?>
                                    <?php if ($count > 1): ?>
                                        <span class="service-count">(x<?php echo $count; ?>)</span>
                                    <?php endif; ?>
                                    <?php if ($discount > 0): ?>
                                        <span style="color: #cc0000;">(-<?php echo $discount; ?>%)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color: #999;">Нет услуг</span>
                        <?php endif; ?>
                    </td>
                    <td class="price-cell">
                        <?php if ($recordTotal > 0): ?>
                            <?php echo number_format($recordTotal, 0, '.', ' '); ?> ₽
                        <?php else: ?>
                            <span style="color: #999;">0 ₽</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-row">
            ИТОГО: <?php echo number_format($totalSum, 0, '.', ' '); ?> ₽
        </div>
    <?php endif; ?>

    <div class="print-footer">
        Сформировано: <?php echo date('d.m.Y H:i'); ?>
    </div>
</body>
</html>