<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

$DP_Config = new DP_Config;
try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_link->query("SET NAMES utf8;");
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}

// Получаем параметры из GET
$office_id = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-1 month'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$for_warehouse = isset($_GET['for_warehouse']) ? (int)$_GET['for_warehouse'] : -1;
$statuses = isset($_GET['status']) ? (array)$_GET['status'] : [];
$statuses = array_filter($statuses, 'is_numeric');
$statuses = array_map('intval', $statuses);
$statuses_str = !empty($statuses) ? implode(',', $statuses) : '';

// Получаем название офиса
$office_name = '';
if ($office_id) {
    $stmt = $db_link->prepare("SELECT caption FROM shop_offices WHERE id = :id");
    $stmt->execute([':id' => $office_id]);
    $office = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($office) $office_name = $office['caption'];
}

$ts_from = strtotime($date_from . ' 00:00:00');
$ts_to = strtotime($date_to . ' 23:59:59');

// Условие для фильтра по статусам
if (!empty($statuses_str)) {
    $case_condition = "FIND_IN_SET(oi.status, :statuses)";
} else {
    $case_condition = "1=1";
}

// Основной запрос – сводка по складам
$sql = "
    SELECT 
        s.id AS storage_id,
        s.name AS storage_name,
        SUM(oi.count_need) AS total_count,
        SUM(oi.price * oi.count_need) AS total_sum,
        SUM(CASE WHEN ($case_condition) THEN oi.count_need ELSE 0 END) AS filtered_count,
        SUM(CASE WHEN ($case_condition) THEN oi.price * oi.count_need ELSE 0 END) AS filtered_sum
    FROM shop_orders_items oi
    INNER JOIN shop_orders o ON oi.order_id = o.id
    INNER JOIN shop_storages s ON oi.t2_storage_id = s.id
    WHERE o.office_id = :office_id
      AND o.time BETWEEN :ts_from AND :ts_to
";

if ($for_warehouse !== -1) {
    $sql .= " AND o.for_warehouse = :for_warehouse";
}

$sql .= " GROUP BY s.id, s.name
          HAVING total_count > 0 OR filtered_count > 0
          ORDER BY s.name";

$params = [
    ':office_id' => $office_id,
    ':ts_from' => $ts_from,
    ':ts_to' => $ts_to
];
if ($for_warehouse !== -1) {
    $params[':for_warehouse'] = $for_warehouse;
}
if (!empty($statuses_str)) {
    $params[':statuses'] = $statuses_str;
}

$stmt = $db_link->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Форматирование чисел
foreach ($rows as &$row) {
    $row['total_sum'] = number_format($row['total_sum'], 2, '.', '');
    $row['filtered_sum'] = number_format($row['filtered_sum'], 2, '.', '');
    $row['difference'] = number_format((float)$row['total_sum'] - (float)$row['filtered_sum'], 2, '.', '');
}

// Получаем названия выбранных статусов для отображения в шапке
$status_names = [];
if (!empty($statuses)) {
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $stmt2 = $db_link->prepare("SELECT name FROM shop_orders_items_statuses_ref WHERE id IN ($placeholders)");
    $stmt2->execute($statuses);
    $status_names = $stmt2->fetchAll(PDO::FETCH_COLUMN);
}
$statuses_display = !empty($status_names) ? implode(', ', $status_names) : 'все';
$warehouse_display = ($for_warehouse == 1) ? 'Да' : (($for_warehouse == 0) ? 'Нет' : 'Все');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Печать сводки по складам</title>
    <style>
        /* Базовые стили для экрана и печати */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #000;
        }
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .print-header h1 {
            font-size: 22px;
            margin: 0 0 6px 0;
        }
        .print-header p {
            margin: 3px 0;
            font-size: 13px;
            color: #222;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #e9edf4;
            border: 1px solid #999;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
        }
        td {
            border: 1px solid #999;
            padding: 6px 10px;
            vertical-align: middle;
        }
        .num {
            text-align: right;
        }
        .footer {
            margin-top: 25px;
            font-size: 11px;
            color: #555;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        /* Кнопки только для экрана, при печати скрываются */
        .no-print {
            margin-bottom: 15px;
            text-align: right;
        }
        .no-print button {
            padding: 8px 18px;
            margin-left: 10px;
            background: #4a90d9;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        .no-print button:hover {
            background: #357abd;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0.5cm;
            }
            th {
                background: #e9edf4 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        /* Адаптив для мобильных – при печати не нужен, но оставляем для экрана */
        @media screen and (max-width: 700px) {
            table, thead, tbody, tr, td, th {
                display: block;
            }
            thead {
                display: none;
            }
            tr {
                margin-bottom: 16px;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 8px;
            }
            td {
                display: flex;
                justify-content: space-between;
                border: none;
                border-bottom: 1px dashed #ccc;
                padding: 6px 4px;
            }
            td:last-child {
                border-bottom: none;
            }
            td::before {
                content: attr(data-label);
                font-weight: 600;
                margin-right: 10px;
            }
            .num {
                text-align: right;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">🖨️ Печать</button>
    <button onclick="window.close()">✖ Закрыть</button>
</div>

<div class="print-header">
    <h1>Сводка по складам</h1>
    <p><strong>Офис:</strong> <?php echo htmlspecialchars($office_name); ?></p>
    <p><strong>Период:</strong> с <?php echo htmlspecialchars($date_from); ?> по <?php echo htmlspecialchars($date_to); ?></p>
    <p><strong>Статусы:</strong> <?php echo htmlspecialchars($statuses_display); ?></p>
    <p><strong>Оприходование:</strong> <?php echo htmlspecialchars($warehouse_display); ?></p>
</div>

<table>
    <thead>
        <tr>
            <th>ID склада</th>
            <th>Название склада</th>
            <th class="num">Всего позиций</th>
            <th class="num">Общая сумма</th>
            <th class="num">Отфильтровано</th>
            <th class="num">Сумма по фильтру</th>
            <th class="num">Разница</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="7" style="text-align:center;">Нет данных для выбранных фильтров</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td data-label="ID склада"><?php echo htmlspecialchars($row['storage_id']); ?></td>
                <td data-label="Название склада"><?php echo htmlspecialchars($row['storage_name']); ?></td>
                <td class="num" data-label="Всего позиций"><?php echo htmlspecialchars($row['total_count']); ?></td>
                <td class="num" data-label="Общая сумма"><?php echo htmlspecialchars($row['total_sum']); ?></td>
                <td class="num" data-label="Отфильтровано"><?php echo htmlspecialchars($row['filtered_count']); ?></td>
                <td class="num" data-label="Сумма по фильтру"><?php echo htmlspecialchars($row['filtered_sum']); ?></td>
                <td class="num" data-label="Разница"><?php echo htmlspecialchars($row['difference']); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="footer">
    Дата печати: <?php echo date('d.m.Y H:i:s'); ?>
</div>

</body>
</html>