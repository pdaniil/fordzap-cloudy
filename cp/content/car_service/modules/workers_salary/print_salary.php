<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
session_start();

// Простая проверка авторизации (замените на свою систему)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    die("Доступ запрещён");
}

$DP_Config = new DP_Config;
try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_link->query("SET NAMES utf8;");
} catch (PDOException $e) {
    die("Ошибка подключения к БД");
}

require_once("WorkersSalary.php");

$worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
$date_from = isset($_GET['from']) ? $_GET['from'] : '';
$date_to = isset($_GET['to']) ? $_GET['to'] : '';

if (!$worker_id || !$date_from || !$date_to) {
    die("Не все параметры переданы");
}

$from_datetime = $date_from . ' 00:00:00';
$to_datetime = $date_to . ' 23:59:59';

// Данные сотрудника
$stmt = $db_link->prepare("SELECT * FROM `car_service_personal` WHERE `id` = ?");
$stmt->execute([$worker_id]);
$worker = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$worker) {
    die("Сотрудник не найден");
}

// Получаем записи и выплаты за период
$records = WorkersSalary::get_detal_info($worker_id, $from_datetime, $to_datetime);
$payments = WorkersSalary::get_salary_info_by_period($worker_id, $from_datetime, $to_datetime);

$total_accrued = 0;
$total_paid = 0;
foreach ($records as $rec) {
    $total_accrued += $rec['percent_salary'];
}
foreach ($payments as $pay) {
    $total_paid += $pay['salary'];
}
$balance = $total_accrued - $total_paid;

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ведомость зарплаты - <?=htmlspecialchars($worker['fio'])?></title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 12px;
            margin: 20px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
        }
        .header p {
            margin: 5px 0;
            color: #555;
        }
        .worker-info {
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #777;
        }
        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
        button {
            margin-bottom: 20px;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #2980b9;
        }
        .services-detail {
            margin-top: 5px;
            font-size: 10px;
            color: #555;
        }
    </style>
</head>
<body>
    <button onclick="window.print();" class="no-print">Распечатать</button>
    <div class="header">
        <h1>Ведомость начислений и выплат</h1>
        <p>За период с <?=date('d.m.Y', strtotime($date_from))?> по <?=date('d.m.Y', strtotime($date_to))?></p>
    </div>
    
    <div class="worker-info">
        <strong>Сотрудник:</strong> <?=htmlspecialchars($worker['fio'])?><br>
        <strong>Процент от работ:</strong> <?=$worker['service_percent']?>%<br>
        <strong>Часовая ставка:</strong> <?=number_format($worker['standart_hourly_rate'], 2, '.', ' ')?> руб.
    </div>
    
    <h3>Выполненные работы</h3>
    <?php if(count($records) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Автомобиль</th>
                <th>Сумма услуг</th>
                <th>Начислено сотруднику</th>
                <th>Номер заказа</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($records as $rec): ?>
            <tr>
                <td><?=date('d.m.Y H:i', strtotime($rec['date']))?></td>
                <td><?=htmlspecialchars($rec['car_brand'] ?? '')?> <?=htmlspecialchars($rec['car_model'] ?? '')?></td>
                <td><?=number_format($rec['price'], 0, '', ' ')?> руб.</td>
                <td><?=number_format($rec['percent_salary'], 0, '', ' ')?> руб.</td>
                <td>
                    <?php if($rec['order_id']): ?>
                        <a href="https://fordzap.com/cp/shop/orders/order?order_id=<?=$rec['order_id']?>" target="_blank">Заказ №<?=$rec['order_id']?></a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <?php if(!empty($rec['services'])): ?>
            <tr>
                <td colspan="5">
                    <div class="services-detail">
                        <strong>Услуги:</strong>
                        <?php foreach($rec['services'] as $srv): ?>
                            <div>— <?=htmlspecialchars($srv['service_name'])?> (<?=number_format($srv['price'], 0, '', ' ')?> руб., начислено: <?=number_format($srv['worker_payment'], 0, '', ' ')?> руб.)</div>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>Нет выполненных работ за указанный период.</p>
    <?php endif; ?>
    
    <h3>Выплаты</h3>
    <?php if(count($payments) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Дата выплаты</th>
                <th>Сумма</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($payments as $pay): ?>
            <tr>
                <td><?=date('d.m.Y H:i', strtotime($pay['date']))?></td>
                <td><?=number_format($pay['salary'], 0, '', ' ')?> руб.</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>Выплат за указанный период не было.</p>
    <?php endif; ?>
    
    <table class="total-row">
        <tr>
            <th>Итого начислено:</th>
            <td><?=number_format($total_accrued, 0, '', ' ')?> руб.</td>
        </tr>
        <tr>
            <th>Итого выплачено:</th>
            <td><?=number_format($total_paid, 0, '', ' ')?> руб.</td>
        </tr>
        <tr>
            <th>Остаток к выплате:</th>
            <td><strong><?=number_format($balance, 0, '', ' ')?> руб.</strong></td>
        </tr>
    </table>
    
    <div class="footer">
        Дата формирования отчёта: <?=date('d.m.Y H:i:s')?>
    </div>
</body>
</html>