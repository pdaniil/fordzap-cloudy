<?php
ini_set("display_errors", 1);

// Функция для склонения слов
function pluralForm($n, $forms) {
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $forms[2];
    if ($n1 > 1 && $n1 < 5) return $forms[1];
    if ($n1 == 1) return $forms[0];
    return $forms[2];
}

// Функция для получения русского названия месяца
function getRussianMonthName($month_number) {
    $months = [
        1 => 'Январь',
        2 => 'Февраль',
        3 => 'Март',
        4 => 'Апрель',
        5 => 'Май',
        6 => 'Июнь',
        7 => 'Июль',
        8 => 'Август',
        9 => 'Сентябрь',
        10 => 'Октябрь',
        11 => 'Ноябрь',
        12 => 'Декабрь'
    ];
    return $months[$month_number] ?? '';
}

// Получаем ID текущего пользователя
$current_user_id = DP_User::getUserId();

require_once($_SERVER["DOCUMENT_ROOT"] . "/" . $DP_Config->backend_dir . "/content/car_service/modules/workers_salary/WorkersSalary.php");

// Проверяем, является ли пользователь сотрудником
$check_worker = $db_link->prepare("SELECT * FROM `car_service_personal` WHERE `user_id` = ? LIMIT 1;");
$check_worker->execute([$current_user_id]);
$worker = $check_worker->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    echo "<div class='car_service_worker_not_found'>Вы не являетесь сотрудником автосервиса</div>";
    exit;
}

// Определяем, показывать ли цену за услуги
$show_price_info = ($worker["show_price_info"] ?? 0) == 1;

// Получаем параметры фильтрации
$filter_period = isset($_GET['period']) ? $_GET['period'] : 'week';
$filter_month = isset($_GET['month']) ? $_GET['month'] : null;
$now = time();

// Определяем даты в зависимости от периода
switch ($filter_period) {
    case 'week':
        $start_date = date('Y-m-d H:i:s', strtotime('-1 week', $now));
        break;
    case 'two_weeks':
        $start_date = date('Y-m-d H:i:s', strtotime('-2 weeks', $now));
        break;
    case 'month':
        $start_date = date('Y-m-d H:i:s', strtotime('-1 month', $now));
        break;
    case 'all':
    default:
        $start_date = '2000-01-01 00:00:00';
        break;
}

// Если выбран конкретный месяц
if ($filter_month && preg_match('/^\d{4}-\d{2}$/', $filter_month)) {
    $start_date = $filter_month . '-01 00:00:00';
    $end_date = date('Y-m-t 23:59:59', strtotime($start_date));
} else {
    $end_date = date('Y-m-d H:i:s', $now);
}

// Получаем данные о работах с фильтрацией по времени
$work_records_query = $db_link->prepare("
    SELECT cr.* 
    FROM `car_service_records` cr
    WHERE cr.`worker_id` = ? 
    AND cr.`time` >= ?
    AND cr.`time` <= ?
    ORDER BY cr.`time` DESC
");
$work_records_query->execute([$worker["id"], $start_date, $end_date]);
$work_records = $work_records_query->fetchAll(PDO::FETCH_ASSOC);

// Получаем данные о выплатах с фильтрацией по времени
$salary_payments_query = $db_link->prepare("
    SELECT * 
    FROM `car_service_personal_salary` 
    WHERE `worker_id` = ? 
    AND `time` >= ?
    AND `time` <= ?
    ORDER BY `time` DESC
");
$salary_payments_query->execute([$worker["id"], $start_date, $end_date]);
$salary_payments = $salary_payments_query->fetchAll(PDO::FETCH_ASSOC);

// ПОДГОТОВКА: Получаем заработок за прошлый месяц
$last_month_start = date('Y-m-01 00:00:00', strtotime('-1 month'));
$last_month_end = date('Y-m-t 23:59:59', strtotime('-1 month'));

$last_month_works_query = $db_link->prepare("
    SELECT cr.* 
    FROM `car_service_records` cr
    WHERE cr.`worker_id` = ? 
    AND cr.`time` >= ?
    AND cr.`time` <= ?
");
$last_month_works_query->execute([$worker["id"], $last_month_start, $last_month_end]);
$last_month_works = $last_month_works_query->fetchAll(PDO::FETCH_ASSOC);

$last_month_earnings = 0;
foreach ($last_month_works as $record) {
    // Получаем услуги для записи
    $services_query = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?");
    $services_query->execute([$record["id"]]);
    $services = $services_query->fetchAll(PDO::FETCH_ASSOC);
    
    $record_earnings = 0;
    foreach ($services as $service) {
        // Учитываем только услуги со статусом 21 (выполнены)
        if (($service["status_id"] ?? 0) == 21) {
            // Проверяем фиксированную цену
            if (isset($service["fix_price"]) && $service["fix_price"] > 0) {
                $worker_amount = $service["fix_price"] * $service["count"];
            } else {
                $price = $service["price"] * $service["count"];
                if (isset($service["discount"]) && $service["discount"] > 0) {
                    $price = $price * (1 - $service["discount"] / 100);
                }
                $worker_amount = $price * ($worker["service_percent"] / 100);
            }
            $record_earnings += $worker_amount;
        }
    }
    $last_month_earnings += $record_earnings;
}

// Получаем последние 6 месяцев для фильтра
$last_months = [];
for ($i = 0; $i < 6; $i++) {
    $month_date = strtotime("-$i month");
    $last_months[] = [
        'value' => date('Y-m', $month_date),
        'name' => date('F Y', $month_date)
    ];
}

// Подготавливаем данные для отображения
$worker_data = [
    'id' => $worker["id"],
    'user_id' => $worker["user_id"],
    'fio' => $worker["fio"],
    'service_percent' => $worker["service_percent"],
    'work_records' => [],
    'salary_payments' => []
];

// Обрабатываем записи о работах
$total_accrued = 0;
foreach ($work_records as $record) {
    // ПОЛУЧАЕМ РАБОТЫ ИЗ car_service_records_services
    $services_query = $db_link->prepare("
        SELECT * FROM `car_service_record_services`
        WHERE record_id = ?
    ");
    $services_query->execute([$record["id"]]);
    $services = $services_query->fetchAll(PDO::FETCH_ASSOC);
    
    $service_names = [];
    $service_details = [];
    $record_worker_amount = 0;
    $record_total_price = 0;
    
    foreach ($services as $service) {
        $service_price = $service["price"] * $service["count"];
        if (isset($service["discount"]) && $service["discount"] > 0) {
            $service_price = $service_price * (1 - $service["discount"] / 100);
        }
        
        $record_total_price += $service_price;
        
        // Определяем, готова ли услуга к оплате (статус 21)
        $is_ready = ($service["status_id"] ?? 0) == 21;
        
        // Рассчитываем выплату сотруднику (только если статус 21)
        $worker_service_amount = 0;
       
		if (isset($service["fix_price"]) && $service["fix_price"] > 0) {
			$worker_service_amount = $service["fix_price"] * $service["count"];
		} else {
			$worker_service_amount = $service_price * ($worker["service_percent"] / 100);
		}
		if ($is_ready)
			$record_worker_amount += $worker_service_amount;
        // Получаем название статуса
        $status_name = WorkersSalary::get_status_name($service["status_id"] ?? 0);
        
        $service_names[] = $service["name"] ?? $service["caption"] ?? "Услуга";
        $service_details[] = [
            'name' => $service["name"] ?? $service["caption"] ?? "Услуга",
            'price' => $service_price, // Цена услуги (может быть скрыта)
            'worker_share' => $worker_service_amount, // Сумма сотрудника (всегда показываем)
            'count' => $service["count"],
            'unit_price' => $service["price"], // Цена за единицу (может быть скрыта)
            'discount' => $service["discount"] ?? 0,
            'fix_price' => $service["fix_price"] ?? 0,
            'is_ready' => $is_ready,
            'status_id' => $service["status_id"] ?? 0,
            'status_name' => $status_name,
            'payment_type' => (isset($service["fix_price"]) && $service["fix_price"] > 0) ? 'fixed' : 'percent'
        ];
    }
    
    // Добавляем запись, если есть хотя бы одна услуга или если показываем все
    if (!empty($service_details)) {
        $total_accrued += $record_worker_amount;
        
        $worker_data['work_records'][] = [
            'date' => getOrderEndDate($record["orders_ids"]) ? getOrderEndDate($record["orders_ids"]) : strtotime($record["time"]),
            'order_id' => $record["orders_ids"],
            'price' => $record_total_price,
            'worker_amount' => $record_worker_amount,
            'type' => 'work',
            'service_names' => $service_names,
            'service_details' => $service_details,
            'services_text' => implode(', ', $service_names)
        ];
    }
}

function getOrderEndDate($order_id) {
	global $db_link;
	
	$sql = $db_link->prepare("SELECT `time` FROM `shop_orders_logs` WHERE `order_id` = ? AND `text` = 'Заказу присвоен статус <b>Завершен</b>'");
	$sql->execute([$order_id]);
	
	$res = $sql->fetch(PDO::FETCH_ASSOC);
	
	if ($res["time"] > 0) return $res["time"]; 
	else return false;
}

// Обрабатываем выплаты
$total_paid = 0;
foreach ($salary_payments as $payment) {
    if ($payment["salary"] <= 0) continue;
    
    $total_paid += $payment["salary"];
    
    $worker_data['salary_payments'][] = [
        'date' => strtotime($payment["time"]),
        'salary' => $payment["salary"],
        'type' => 'payment'
    ];
}

// Получаем общий биллинг (для сортировки по дате)
$bill_info = [];
foreach ($worker_data['work_records'] as $work) {
    $bill_info[] = $work;
}
foreach ($worker_data['salary_payments'] as $payment) {
    $bill_info[] = $payment;
}

// Сортируем по дате (новые сверху)
usort($bill_info, function($a, $b) {
    return $b['date'] - $a['date'];
});

// Текущий баланс (ЗА ВСЁ ВРЕМЯ всегда)
$total_accrued_all_time = 0;
$all_work_records_query = $db_link->prepare("
    SELECT * FROM `car_service_records` WHERE `worker_id` = ?
");
$all_work_records_query->execute([$worker["id"]]);
$all_work_records = $all_work_records_query->fetchAll(PDO::FETCH_ASSOC);

foreach ($all_work_records as $record) {
    $services_query = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?");
    $services_query->execute([$record["id"]]);
    $services = $services_query->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($services as $service) {
        if (($service["status_id"] ?? 0) == 21) {
            if (isset($service["fix_price"]) && $service["fix_price"] > 0) {
                $total_accrued_all_time += $service["fix_price"] * $service["count"];
            } else {
                $price = $service["price"] * $service["count"];
                if (isset($service["discount"]) && $service["discount"] > 0) {
                    $price = $price * (1 - $service["discount"] / 100);
                }
                $total_accrued_all_time += $price * ($worker["service_percent"] / 100);
            }
        }
    }
}

// Получаем все выплаты за всё время
$total_paid_all_time = 0;
$all_salary_payments_query = $db_link->prepare("
    SELECT * FROM `car_service_personal_salary` WHERE `worker_id` = ?
");
$all_salary_payments_query->execute([$worker["id"]]);
$all_salary_payments = $all_salary_payments_query->fetchAll(PDO::FETCH_ASSOC);

foreach ($all_salary_payments as $payment) {
    if ($payment["salary"] > 0) {
        $total_paid_all_time += $payment["salary"];
    }
}

// Текущий баланс за всё время
$current_balance = $total_accrued_all_time - $total_paid_all_time;





?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .car_service_worker_container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }
    
    .car_service_worker_header {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 30px 40px;
        position: relative;
        overflow: hidden;
    }
    
    .car_service_worker_header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1%, transparent 1%);
        background-size: 20px 20px;
        opacity: 0.3;
    }
    
    .car_service_worker_title {
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
        color: #ffffff !important;
    }
    
    .car_service_worker_name {
        font-size: 18px;
        opacity: 0.9;
        margin-bottom: 30px;
        position: relative;
        z-index: 1;
    }
    
    .car_service_worker_stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
        position: relative;
        z-index: 1;
    }
    
    .car_service_worker_stat_card {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 25px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .car_service_worker_stat_card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }
    
    .car_service_worker_stat_label {
        font-size: 14px;
        opacity: 0.8;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .car_service_worker_stat_label i {
        font-size: 16px;
    }
    
    .car_service_worker_stat_value {
        font-size: 32px;
        font-weight: 700;
        margin: 10px 0;
    }
    
    .car_service_worker_stat_value.positive {
        color: #2ecc71;
    }
    
    .car_service_worker_stat_value.negative {
        color: #ff7675;
    }
    
    .car_service_worker_stat_value.neutral {
        color: #636e72;
    }
    
    .car_service_worker_stat_hint {
        font-size: 12px;
        opacity: 0.7;
        margin-top: 5px;
    }
    
    .car_service_worker_content {
        padding: 40px;
    }
    
    .car_service_worker_filters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .car_service_worker_filter_btn {
        padding: 12px 24px;
        background: #ffffff;
        border: 2px solid #cbd5e0;
        border-radius: 10px;
        font-weight: 600;
        color: #4a5568;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        text-decoration: none;
    }
    
    .car_service_worker_filter_btn:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    .car_service_worker_filter_btn.active {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        border-color: #3498db;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    }
    
    /* Стили для выпадающего списка месяцев */
    .month_filter_container {
        position: relative;
        display: inline-block;
    }
    
    .month_filter_dropdown {
        padding: 12px 24px;
        background: #ffffff;
        border: 2px solid #cbd5e0;
        border-radius: 10px;
        font-weight: 600;
        color: #4a5568;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
    }
    
    .month_filter_dropdown:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    .month_filter_dropdown.active {
        background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        color: white;
        border-color: #9b59b6;
        box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3);
    }
    
    .month_filter_menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: white;
        border: 1px solid #cbd5e0;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        min-width: 200px;
        z-index: 1000;
        margin-top: 5px;
    }
    
    .month_filter_menu.show {
        display: block;
        animation: fadeInUp 0.2s ease-out;
    }
    
    .month_filter_item {
        padding: 12px 20px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .month_filter_item:hover {
        background-color: #f0f7ff;
    }
    
    .month_filter_item.active {
        background-color: #f0f7ff;
        color: #3498db;
        font-weight: 600;
    }
    
    .month_filter_item:last-child {
        border-bottom: none;
    }
    
    .month_filter_item .badge {
        background: #ff7675;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .car_service_worker_table_container {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid #cbd5e0;
    }
    
    .car_service_worker_table_header {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 20px 25px;
        font-weight: 600;
        color: #2c3e50;
        border-bottom: 2px solid #dee2e6;
    }
    
    .car_service_worker_table_row {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr;
        padding: 20px 25px;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.3s ease;
    }
    
    .car_service_worker_table_row:hover {
        background-color: #f8f9fa;
    }
    
    .car_service_worker_table_row:last-child {
        border-bottom: none;
    }
    
    .car_service_worker_date {
        color: #2c3e50;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .car_service_worker_date i {
        color: #3498db;
        font-size: 14px;
    }
    
    .car_service_worker_type {
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    
    .car_service_worker_type_badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .car_service_worker_type_badge.work {
        background: rgba(39, 174, 96, 0.1);
        color: #27ae60;
        border: 1px solid #27ae60;
    }
    
    .car_service_worker_type_badge.payment {
        background: rgba(149, 165, 166, 0.1);
        color: #7f8c8d;
        border: 1px solid #7f8c8d;
    }
    
    /* Новые стили для статусов */
    .service_status_badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }
    
    .service_status_badge.ready {
        background: rgba(46, 204, 113, 0.1);
        color: #27ae60;
        border: 1px solid #27ae60;
    }
    
    .service_status_badge.not-ready {
        background: rgba(241, 196, 15, 0.1);
        color: #f1c40f;
        border: 1px solid #f1c40f;
    }
    
    .service_status_badge.unknown {
        background: rgba(127, 140, 141, 0.1);
        color: #7f8c8d;
        border: 1px solid #7f8c8d;
    }
    
    /* Стили для фиксированной цены */
    .fix_price_badge {
        display: inline-block;
        padding: 2px 6px;
        background: rgba(52, 152, 219, 0.1);
        color: #3498db;
        border: 1px solid #3498db;
        border-radius: 4px;
        font-size: 10px;
        margin-left: 5px;
    }
    
    .unready-notice {
        margin-top: 10px;
        padding: 8px 12px;
        background: #fff3cd;
        border: 1px solid #ffeeba;
        border-radius: 4px;
        color: #856404;
        font-size: 12px;
    }
    
    .unready-notice i {
        margin-right: 5px;
    }
    
    .car_service_worker_amount {
        font-weight: 600;
        font-size: 18px;
    }
    
    .car_service_worker_amount.positive {
        color: #27ae60;
    }
    
    .car_service_worker_amount.negative {
        color: #7f8c8d;
    }
    
    /* Стили для деталей работы */
    .work_details {
        margin-top: 10px;
        padding: 15px;
        background: #ffffff;
        border-radius: 10px;
        border-left: 4px solid #3498db;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .work_details_title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }
    
    .work_details_list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .work_details_item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #eaeaea;
        align-items: flex-start;
    }
    
    .work_details_item:last-child {
        border-bottom: none;
    }
    
    .work_details_service_info {
        flex: 1;
    }
    
    .work_details_service_name {
        color: #2c3e50;
        font-weight: 500;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .work_details_service_meta {
        font-size: 12px;
        color: #7f8c8d;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .work_details_service_price {
        color: #27ae60;
        font-weight: 600;
        text-align: right;
        min-width: 120px;
    }
    
    .work_details_service_share {
        color: #3498db;
        font-weight: 600;
        text-align: right;
        min-width: 120px;
        margin-top: 4px;
    }
    
    .work_summary {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px dashed #dee2e6;
        display: flex;
        justify-content: space-between;
        font-weight: 600;
        color: #2c3e50;
        font-size: 15px;
    }
    
    .work_percent_info {
        font-size: 12px;
        color: #7f8c8d;
        margin-top: 5px;
        text-align: right;
    }
    
    .car_service_worker_payment_info {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #7f8c8d;
        font-weight: 500;
        padding: 8px 16px;
        border-radius: 8px;
        background: rgba(149, 165, 166, 0.1);
    }
    
    .car_service_worker_no_data {
        text-align: center;
        padding: 60px 20px;
        color: #7f8c8d;
    }
    
    .car_service_worker_no_data i {
        font-size: 48px;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .car_service_worker_no_data p {
        font-size: 18px;
        margin-bottom: 10px;
    }
    
    .car_service_worker_no_data .hint {
        font-size: 14px;
        opacity: 0.7;
    }
    
    .car_service_worker_footer {
        text-align: center;
        padding: 30px;
        background: #f8f9fa;
        color: #7f8c8d;
        font-size: 14px;
        border-top: 1px solid #eaeaea;
    }
    
    /* Стили для тоггла деталей */
    .toggle_details_btn {
        background: none;
        border: none;
        color: #3498db;
        cursor: pointer;
        font-size: 12px;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.2s;
        background-color: rgba(52, 152, 219, 0.1);
    }
    
    .toggle_details_btn:hover {
        background-color: rgba(52, 152, 219, 0.2);
        transform: translateY(-1px);
    }
    
    .service_count_badge {
        display: inline-block;
        background: #3498db;
        color: white;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 12px;
        margin-left: 8px;
    }
    
    /* Мобильные стили */
    .mobile-only {
        display: none;
    }
    
    @media (max-width: 768px) {
		
		.car_service_worker_amount {
			order: 3;
			text-align: center;
			font-size: 20px;
			padding: 10px;
			background: #f8f9fa;
			border-radius: 8px;
			display: flex;
			flex-direction: column;
			align-items: center;
		}
		
		.car_service_worker_amount > div {
			justify-content: center !important;
		}
		
        .car_service_worker_container {
            border-radius: 15px;
            margin: 10px;
        }
        
        .car_service_worker_header {
            padding: 20px 25px;
        }
        
        .car_service_worker_title {
            font-size: 24px;
            text-align: center;
        }
        
        .car_service_worker_name {
            text-align: center;
            font-size: 16px;
        }
        
        .car_service_worker_stats {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .car_service_worker_stat_card {
            padding: 18px;
        }
        
        .car_service_worker_stat_value {
            font-size: 26px;
        }
        
        .car_service_worker_content {
            padding: 20px;
        }
        
        .car_service_worker_filters {
            flex-direction: column;
            gap: 8px;
        }
        
        .car_service_worker_filter_btn,
        .month_filter_dropdown {
            width: 100%;
            justify-content: center;
            padding: 14px 20px;
        }
        
        .month_filter_container {
            width: 100%;
        }
        
        .month_filter_menu {
            width: 100%;
        }
        
        .car_service_worker_table_header {
            display: none;
        }
        
        .car_service_worker_table_row {
            display: flex;
            flex-direction: column;
            padding: 16px;
            border: 1px solid #eaeaea;
            border-radius: 12px;
            margin-bottom: 12px;
            gap: 12px;
        }
        
        .car_service_worker_date {
            order: 1;
            font-size: 15px;
            justify-content: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #2c3e50;
        }
        
        .car_service_worker_type {
            order: 2;
            flex-direction: column;
            width: 100%;
        }
        
        .car_service_worker_type_badge {
            align-self: flex-start;
        }
        
        .car_service_worker_amount {
            order: 3;
            text-align: center;
            font-size: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .work_details {
            margin-top: 12px;
            padding: 12px;
        }
        
        .work_details_item {
            flex-direction: column;
            gap: 8px;
        }
        
        .work_details_service_info {
            width: 100%;
        }
        
        .work_details_service_price,
        .work_details_service_share {
            text-align: left;
            min-width: auto;
            width: 100%;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px dashed #eaeaea;
        }
        
        .work_summary {
            flex-direction: column;
            gap: 8px;
        }
        
        .toggle_details_btn {
            width: 100%;
            justify-content: center;
        }
        
        .desktop-only {
            display: none;
        }
        
        .mobile-only {
            display: block;
        }
        
        .service_status_badge {
            margin-left: 0;
            margin-top: 5px;
        }
    }
    
    /* Анимации */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .car_service_worker_container {
        animation: fadeInUp 0.5s ease-out;
    }
</style>

<div class="car_service_worker_container">
    <!-- Шапка с информацией -->
    <div class="car_service_worker_header">
        <h1 class="car_service_worker_title">Мои финансы</h1>
        <div class="car_service_worker_name">
            <?=htmlspecialchars($worker["fio"]);?>
        </div>
        
        <div class="car_service_worker_stats">
            <!-- Текущий баланс (ВСЕГДА ЗА ВСЁ ВРЕМЯ) -->
            <div class="car_service_worker_stat_card">
                <div class="car_service_worker_stat_label">
                    <i class="fas fa-wallet"></i>
                    Текущий баланс
                </div>
                <div class="car_service_worker_stat_value <?=$current_balance > 0 ? 'positive' : ($current_balance < 0 ? 'negative' : 'neutral');?>">
                    <?=($current_balance > 0 ? '+' : ($current_balance < 0 ? '-' : '')) . number_format(abs($current_balance), 0, '', ' ');?> руб.
                </div>
                <div class="car_service_worker_stat_hint">
                    На данный момент
                </div>
            </div>
            
            <!-- Начислено за период -->
            <div class="car_service_worker_stat_card">
                <div class="car_service_worker_stat_label">
                    <i class="fas fa-arrow-up"></i>
                    Начислено
                </div>
                <div class="car_service_worker_stat_value positive">
                    +<?=number_format($total_accrued, 0, '', ' ');?> руб.
                </div>
                <div class="car_service_worker_stat_hint">
                    За выбранный период
                </div>
            </div>
            
            <!-- Выплачено за период -->
            <div class="car_service_worker_stat_card">
                <div class="car_service_worker_stat_label">
                    <i class="fas fa-arrow-down"></i>
                    Выплачено
                </div>
                <div class="car_service_worker_stat_value negative">
                    -<?=number_format($total_paid, 0, '', ' ');?> руб.
                </div>
                <div class="car_service_worker_stat_hint">
                    За выбранный период
                </div>
            </div>
            
            <!-- Заработано за прошлый месяц -->
            <div class="car_service_worker_stat_card">
                <div class="car_service_worker_stat_label">
                    <i class="fas fa-calendar-minus"></i>
                    Прошлый месяц
                </div>
                <div class="car_service_worker_stat_value <?=$last_month_earnings > 0 ? 'positive' : 'neutral';?>">
                    +<?=number_format($last_month_earnings, 0, '', ' ');?> руб.
                </div>
                <div class="car_service_worker_stat_hint">
                    <?=getRussianMonthName(date('n', strtotime('-1 month')));?> <?=date('Y', strtotime('-1 month'));?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Основное содержимое -->
    <div class="car_service_worker_content">
        <!-- Фильтры по времени -->
        <div class="car_service_worker_filters">
            <a href="/worker_salary?period=week<?=$filter_month ? '&month=' . urlencode($filter_month) : '';?>" class="car_service_worker_filter_btn <?=$filter_period == 'week' ? 'active' : '';?>">
                <i class="fas fa-calendar-week"></i>
                <span class="desktop-only">Неделя</span>
                <span class="mobile-only">Неделя</span>
            </a>
            <a href="/worker_salary?period=two_weeks<?=$filter_month ? '&month=' . urlencode($filter_month) : '';?>" class="car_service_worker_filter_btn <?=$filter_period == 'two_weeks' ? 'active' : '';?>">
                <i class="fas fa-calendar-alt"></i>
                <span class="desktop-only">2 недели</span>
                <span class="mobile-only">2 нед.</span>
            </a>
            <a href="/worker_salary?period=month<?=$filter_month ? '&month=' . urlencode($filter_month) : '';?>" class="car_service_worker_filter_btn <?=$filter_period == 'month' ? 'active' : '';?>">
                <i class="fas fa-calendar"></i>
                <span class="desktop-only">Месяц</span>
                <span class="mobile-only">Месяц</span>
            </a>
            <a href="/worker_salary?period=all<?=$filter_month ? '&month=' . urlencode($filter_month) : '';?>" class="car_service_worker_filter_btn <?=$filter_period == 'all' ? 'active' : '';?>">
                <i class="fas fa-infinity"></i>
                <span class="desktop-only">Всё время</span>
                <span class="mobile-only">Всё время</span>
            </a>
            
            <!-- Фильтр по месяцам -->
            <div class="month_filter_container">
                <div class="month_filter_dropdown <?=$filter_month ? 'active' : '';?>" onclick="toggleMonthFilter()">
                    <i class="fas fa-filter"></i>
                    <?php 
                    if ($filter_month): 
                        $month_name = getRussianMonthName(date('n', strtotime($filter_month . '-01')));
                    ?>
                        <span class="desktop-only"><?=$month_name;?> <?=date('Y', strtotime($filter_month . '-01'));?></span>
                        <span class="mobile-only"><?=mb_substr($month_name, 0, 3, 'UTF-8');?> <?=date('y', strtotime($filter_month . '-01'));?></span>
                    <?php else: ?>
                        <span class="desktop-only">Выбрать месяц</span>
                        <span class="mobile-only">Месяц</span>
                    <?php endif; ?>
                </div>
                <div class="month_filter_menu" id="monthFilterMenu">
                    <div class="month_filter_item <?=!$filter_month ? 'active' : '';?>" onclick="selectMonth('')">
                        <span>Все месяцы</span>
                    </div>
                    <?php foreach ($last_months as $month): ?>
                        <?php
                        $month_num = date('n', strtotime($month['value'] . '-01'));
                        $russian_month = getRussianMonthName($month_num);
                        ?>
                        <div class="month_filter_item <?=$filter_month == $month['value'] ? 'active' : '';?>" onclick="selectMonth('<?=$month['value'];?>')">
                            <span><?=$russian_month;?> <?=date('Y', strtotime($month['value'] . '-01'));?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Кнопка сброса фильтров -->
            <?php if ($filter_month || $filter_period != 'week'): ?>
                <a href="/worker_salary" class="car_service_worker_filter_btn" style="background: #ffffff; color: #ff7675; border-color: #ff7675;">
                    <i class="fas fa-times"></i>
                    <span class="desktop-only">Сбросить фильтры</span>
                    <span class="mobile-only">Сброс</span>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Таблица операций -->
        <div class="car_service_worker_table_container">
            <?php if (!empty($bill_info)): ?>
                <!-- Заголовок таблицы -->
                <div class="car_service_worker_table_header">
                    <div>Дата</div>
                    <div>Операция</div>
                    <div>Сумма</div>
                </div>
                
                <!-- Строки таблицы -->
                <?php foreach ($bill_info as $index => $item): ?>
                    <div class="car_service_worker_table_row">
                        <!-- Дата -->
                        <div class="car_service_worker_date">
                            <i class="far fa-calendar"></i>
                            <?=date('d.m.Y', $item['date']);?>
                        </div>
                        
                        <!-- Тип операции и детали -->
                        <div class="car_service_worker_type">
                            <?php if ($item['type'] == 'work'): ?>
                                <span class="car_service_worker_type_badge work">
                                    <i class="fas fa-tools"></i> Работа
                                </span>
                                
                                <div style="flex: 1;">
                                    <div style="font-weight: 500; color: #2c3e50; margin-bottom: 5px;">
                                        Заказ №<?=htmlspecialchars($item['order_id']);?>
                                        <?php if (!empty($item['service_names'])): ?>
                                            <span class="service_count_badge"><?=count($item['service_names']);?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($item['service_details'])): ?>
                                        <!-- Кнопка показа деталей -->
                                        <button class="toggle_details_btn" onclick="toggleWorkDetails('details_<?=$index;?>', this)">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Показать детали</span>
                                        </button>
                                        
                                        <!-- Детали работы (скрыты по умолчанию) -->
                                        <div class="work_details" id="details_<?=$index;?>" style="display: none;">
                                            <div class="work_details_title">
                                                <i class="fas fa-tools"></i>
                                                Детализация работ:
                                            </div>
                                            <ul class="work_details_list">
                                                <?php foreach ($item['service_details'] as $service): ?>
                                                    <li class="work_details_item">
                                                        <div class="work_details_service_info">
                                                            <div class="work_details_service_name">
                                                                <?=htmlspecialchars($service['name']);?>
                                                                <span class="service_status_badge <?=$service['is_ready'] ? 'ready' : 'not-ready';?>">
                                                                    <?php if ($service['is_ready']): ?>
                                                                        <i class="fas fa-check-circle"></i> Готово
                                                                    <?php else: ?>
                                                                        <i class="fas fa-hourglass-half"></i> 
                                                                        <?=htmlspecialchars($service['status_name']);?>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                            <div class="work_details_service_meta">
                                                                <span><?=$service['count'];?> <?=pluralForm($service['count'], ['шт.', 'шт.', 'шт.']);?></span>
                                                                
                                                                <!-- Показываем цену услуги ТОЛЬКО если show_price_info = 1 -->
                                                                <?php if ($show_price_info): ?>
                                                                    <span>× <?=number_format($service['unit_price'], 0, '', ' ');?> руб.</span>
                                                                    <?php if ($service['discount'] > 0): ?>
                                                                        <span style="color: #ff7675;">Скидка <?=$service['discount'];?>%</span>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <div>
                                                            <!-- Показываем стоимость услуги ТОЛЬКО если show_price_info = 1 -->
                                                            <?php if ($show_price_info): ?>
                                                                <div class="work_details_service_price">
                                                                    <?=number_format($service['price'], 0, '', ' ');?> руб.
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Сумма сотрудника показывается ВСЕГДА -->
                                                           <?php if ($service['is_ready']): ?>
																<div class="work_details_service_share">
																	→ <?=number_format($service['worker_share'], 0, '', ' ');?> руб.
																</div>
															<?php else: ?>
																<div class="work_details_service_share" style="color: #f39c12;">
																	<i class="fas fa-clock"></i> 
																	поступит <?=number_format($service['worker_share'], 0, '', ' ');?> руб.
																</div>
															<?php endif; ?>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            
                                            <!-- Общая сумма заказа (скрываем если show_price_info = 0) -->
                                            <?php if ($show_price_info): ?>
                                                <div class="work_summary">
                                                    <span>Общая сумма заказа:</span>
                                                    <span style="color: #27ae60;"><?=number_format($item['price'], 0, '', ' ');?> руб.</span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Заработок сотрудника (показываем ВСЕГДА) -->
                                            <div class="work_summary">
                                                <span>Ваш заработок:</span>
                                                <span style="color: #3498db;"><?=number_format($item['worker_amount'], 0, '', ' ');?> руб.</span>
                                            </div>
                                            
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="car_service_worker_type_badge payment">
                                    <i class="fas fa-money-bill-wave"></i> Выплата
                                </span>
                                <div style="color: #7f8c8d;">
                                    Выплата заработной платы
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Сумма -->
                        <div class="car_service_worker_amount <?=$item['type'] == 'work' ? 'positive' : 'negative';?>">
                            <?php if ($item['type'] == 'work'): ?>
								<!-- Уже поступило (выполненные работы) -->
								<div style="color: #27ae60; font-weight: 600;">
									+<?=number_format($item['worker_amount'], 0, '', ' ');?> руб.
								</div>
								
								<?php
								// Рассчитываем ожидаемый заработок по этому заказу (не готовые услуги)
								$order_pending = 0;
								foreach ($item['service_details'] as $service) {
									if (!$service['is_ready']) {
										$order_pending += $service['worker_share'];
									}
								}
								?>
								
								<!-- Ожидаемое поступление (работы в процессе) -->
								<?php if ($order_pending > 0): ?>
									<div style="color: #f39c12; font-size: 14px; margin-top: 4px; font-weight: 500; display: flex; align-items: center; gap: 4px; justify-content: flex-end;">
										<i class="fas fa-clock" style="font-size: 12px;"></i>
										<span>ожидается +<?=number_format($order_pending, 0, '', ' ');?> руб.</span>
									</div>
								<?php endif; ?>
                            <?php else: ?>
                                -<?=number_format($item['salary'], 0, '', ' ');?> руб.
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            <?php else: ?>
                <!-- Сообщение об отсутствии данных -->
                <div class="car_service_worker_no_data">
                    <i class="far fa-folder-open"></i>
                    <p>Нет операций за выбранный период</p>
                    <p class="hint">Измените период фильтрации или дождитесь новых записей</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Подвал -->
    <div class="car_service_worker_footer">
        <p>Информация актуальна на <?=date('d.m.Y');?>. Процент сотрудника: <strong><?=$worker["service_percent"];?>%</strong></p>
        <?php if (!$show_price_info): ?>
            <p style="font-size: 12px; margin-top: 5px;"><i class="fas fa-eye-slash"></i> Цены на услуги скрыты</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Функции для фильтра по месяцам
    function toggleMonthFilter() {
        const menu = document.getElementById('monthFilterMenu');
        menu.classList.toggle('show');
    }
    
    function selectMonth(month) {
        const currentParams = new URLSearchParams(window.location.search);
        
        if (month) {
            currentParams.set('month', month);
        } else {
            currentParams.delete('month');
        }
        
        if (!currentParams.has('period')) {
            currentParams.set('period', '<?=$filter_period;?>');
        }
        
        window.location.href = '/worker_salary?' + currentParams.toString();
    }
    
    // Закрыть меню при клике вне его
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('monthFilterMenu');
        const dropdown = document.querySelector('.month_filter_dropdown');
        
        if (dropdown && menu && !dropdown.contains(event.target) && !menu.contains(event.target)) {
            menu.classList.remove('show');
        }
    });
    
    // Функция для показа/скрытия деталей работы
    function toggleWorkDetails(detailsId, button) {
        const details = document.getElementById(detailsId);
        
        if (details.style.display === 'none' || !details.style.display) {
            details.style.display = 'block';
            button.innerHTML = '<i class="fas fa-chevron-up"></i> <span>Скрыть детали</span>';
        } else {
            details.style.display = 'none';
            button.innerHTML = '<i class="fas fa-info-circle"></i> <span>Показать детали</span>';
        }
    }
    
    // Автоматически разворачиваем детали на мобильных
    document.addEventListener('DOMContentLoaded', function() {
        const isMobile = window.innerWidth <= 768;
        const allDetails = document.querySelectorAll('.work_details');
        const allButtons = document.querySelectorAll('.toggle_details_btn');
        
        if (isMobile && allDetails.length > 0 && allButtons.length > 0) {
            // На мобильных разворачиваем все детали
            allDetails.forEach((details, index) => {
                if (index < allButtons.length) {
                    details.style.display = 'block';
                    allButtons[index].innerHTML = '<i class="fas fa-chevron-up"></i> <span>Скрыть детали</span>';
                }
            });
        } else if (!isMobile && allDetails.length > 0 && allButtons.length > 0) {
            // На десктопе разворачиваем только первую
            allDetails[0].style.display = 'block';
            allButtons[0].innerHTML = '<i class="fas fa-chevron-up"></i> <span>Скрыть детали</span>';
        }
    });
</script> 