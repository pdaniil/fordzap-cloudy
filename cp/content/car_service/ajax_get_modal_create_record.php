<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
} catch (PDOException $e) {
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");
$user_session = DP_User::getAdminSession();

$record_id  = isset($_POST['record_id'])  ? (int)$_POST['record_id']  : 0;
$date       = isset($_POST['date'])       ? $_POST['date']            : '';
$time       = isset($_POST['time'])       ? $_POST['time']            : '';
$post_id    = isset($_POST['post_id'])    ? (int)$_POST['post_id']    : 0;
$service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;

/* ===== STAFF MODE — id сотрудника, под которым работает планшет ===== */
$staff_id   = isset($_POST['staff_id'])   ? (int)$_POST['staff_id']   : 0;
/* ===== /STAFF MODE ===== */

$recordData = null;
$preselectedCaptions = [];
$statusLabel = '';
$readonly = false;
$isRewrite = 0;
$rewriteComment = '';

if ($record_id > 0) {
    $stmt = $db_link->prepare("SELECT * FROM car_service_records WHERE id = ? AND sto_id = ?");
    $stmt->execute([$record_id, $service_id]);
    $recordData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($recordData) {
        $date = substr($recordData['time'], 0, 10);
        $time = substr($recordData['time'], 11, 5);
        $post_id = $recordData['post_id'];
        $servicesStmt = $db_link->prepare("SELECT caption FROM car_service_record_services WHERE record_id = ?");
        $servicesStmt->execute([$record_id]);
        $preselectedCaptions = $servicesStmt->fetchAll(PDO::FETCH_COLUMN);

        $orderStatus = null;
        if ($recordData['orders_ids']) {
            $orderStmt = $db_link->prepare("SELECT status FROM shop_orders WHERE id = ?");
            $orderStmt->execute([$recordData['orders_ids']]);
            $order = $orderStmt->fetch();
            if ($order) $orderStatus = (int)$order['status'];
        }
        $timeTo = $recordData['time_to'];
        $currentTime = time();
        if ($timeTo <= $currentTime) {
            if (in_array($orderStatus, [3,6,9])) {
                $statusLabel = '✅ Выполнена';
            } else {
                $statusLabel = '🔴 Просрочена';
            }
        } else {
            $statusLabel = '⏳ Активна';
        }
        if ($recordData['post_id'] == -1) {
            $statusLabel = '⚠️ Не подтверждена';
        }

        if ($recordData['post_id'] != -1) {
            //$readonly = true;
        }

        $isRewrite = (int)($recordData['is_rewrite'] ?? 0);
        $rewriteComment = $recordData['rewrite_comment'] ?? '';
    }
}
// Запрет любых действий для выполненных записей
$isCompleted = ($statusLabel === '✅ Выполнена');
if ($isCompleted) {
    $readonly = true;
}

// --- Товары заказа ---
$orderItems = [];
$orderTotal = 0;
$orderData = null;

if ($recordData && !empty($recordData['orders_ids'])) {
    $orderId = (int)$recordData['orders_ids'];
    $orderStmt = $db_link->prepare("SELECT * FROM shop_orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($orderData) {
        $itemsStmt = $db_link->prepare("SELECT * FROM shop_orders_items WHERE order_id = ?");
        $itemsStmt->execute([$orderId]);
        $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $CW_CANCELLED_STATUSES = [6, 20];

        foreach ($orderItems as $item) {
            if (in_array((int)$item['status'], $CW_CANCELLED_STATUSES, true)) {
                continue;
            }
            $orderTotal += $item['price'] * $item['count_need'] * (1 - $item['discount'] / 100);
        }
    }
}

// --- Рекомендации по заказу ---
$order_recomendation = '';
if ($orderData && isset($orderData['recomendation'])) {
    $order_recomendation = (string)$orderData['recomendation'];
}

// --- Оплаченная сумма по заказу ---
$order_paid_sum = 0;
if ($recordData && !empty($recordData['orders_ids'])) {
    $oid = (int)$recordData['orders_ids'];

    $incStmt = $db_link->prepare("SELECT IFNULL(SUM(`amount`),0) FROM `shop_users_accounting` WHERE `active`=1 AND `income`=1 AND `order_id`=?");
    $incStmt->execute([$oid]);
    $incomeSum = (float)$incStmt->fetchColumn();

    $outStmt = $db_link->prepare("SELECT IFNULL(SUM(`amount`),0) FROM `shop_users_accounting` WHERE `active`=1 AND `income`=0 AND `order_id`=?");
    $outStmt->execute([$oid]);
    $outcomeSum = (float)$outStmt->fetchColumn();

    $order_paid_sum = $outcomeSum - $incomeSum;
    if ($order_paid_sum < 0) $order_paid_sum = 0;
}

$service_query = $db_link->prepare("SELECT caption, address, phone, id_tree_list_services, time_from, time_to, record_step, operating_mode, hourly_rate FROM car_services WHERE id = ?");
$service_query->execute([$service_id]);
$service = $service_query->fetch(PDO::FETCH_ASSOC);
$operating_mode = json_decode($service['operating_mode'], true);

$posts_query = $db_link->prepare("SELECT id, caption, image FROM car_service_posts ORDER BY id");
$posts_query->execute();
$posts = $posts_query->fetchAll(PDO::FETCH_ASSOC);

$workers_query = $db_link->prepare("SELECT id, fio, user_id FROM car_service_personal ORDER BY fio");
$workers_query->execute();
$workers = $workers_query->fetchAll(PDO::FETCH_ASSOC);

$selectedClient = $recordData ? $recordData['user_id'] : 0;
$selectedCar = $recordData ? $recordData['car_id'] : 0;
$selectedWorker = $recordData ? $recordData['worker_id'] : 0;
$selectedPost = $post_id;
$comment = $recordData ? $recordData['comment'] : '';

$login_user_id = DP_User::getUserId();

if (!($record_id > 0)) {
    $worker_id_selected = 0;
    foreach ($workers as $worker) {
        if ($worker["user_id"] == $login_user_id) {
            $selectedWorker = $worker["id"];
            $selectedClient = 1;
            $selectedCar = 552;
        }
    }
}

/* ===== STAFF MODE — приоритетное предзаполнение мастера при создании новой записи ===== */
if (!($record_id > 0) && $staff_id > 0) {
    foreach ($workers as $worker) {
        if ((int)$worker['id'] === $staff_id) {
            $selectedWorker = $staff_id;
            break;
        }
    }
}
/* ===== /STAFF MODE ===== */

$services_list = [];
$tree_id = (int)$service['id_tree_list_services'];
if ($tree_id) {
    $stmt = $db_link->prepare("SELECT id, caption, price, hourly_rate FROM car_service_services WHERE tree_id = ? AND active = 1 ORDER BY caption");
    $stmt->execute([$tree_id]);
    $services_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$selected_services_details = [];
if ($record_id > 0) {
    $stmt = $db_link->prepare("SELECT * FROM car_service_record_services WHERE record_id = ?");
    $stmt->execute([$record_id]);
    $selected_services_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Стейтменты по услугам ---
$services_statements = [];
if ($record_id > 0) {
    $stmt = $db_link->prepare("
        SELECT id, caption, statement_type
        FROM car_service_record_services_statements
        WHERE record_id = ?
        ORDER BY time ASC, id ASC
    ");
    $stmt->execute([$record_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $services_statements[(string)$row['caption']] = [
            'id'             => (int)$row['id'],
            'statement_type' => (int)$row['statement_type']
        ];
    }
}

// --- Для виджета добавления товаров ---
$canAddProducts = ($record_id > 0 && !empty($recordData['orders_ids']));
$orderId = $canAddProducts ? (int)$recordData['orders_ids'] : 0;
$officeId = 0;
$userId = $selectedClient;
$groupId = 1;

if ($canAddProducts && $orderData) {
    $officeId = (int)$orderData['office_id'];
}
  
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- === АКТ ПРИЁМА: подключение модуля === -->
<script src="/cp/content/car_service/acceptance_act.js"></script>

<!-- === РЕКЛАМАЦИЯ: подключение модуля === -->
<script src="/cp/content/car_service/reclamation.js"></script>

<style>
    .cw-modal-dark { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #FFFFFF; background: transparent; }
    .cw-service-card { background: #2B2B3B; border-radius: 16px; padding: 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 12px; border: 1px solid #3A3A50; }
    .cw-service-icon { font-size: 32px; }
    .cw-service-info { flex: 1; }
    .cw-service-name { font-size: 18px; font-weight: 700; color: #FFFFFF; }
    .cw-service-address, .cw-service-phone { font-size: 13px; color: #A0A0B0; }
    .cw-field { margin-bottom: 18px; }
    .cw-field-label { font-size: 14px; font-weight: 600; margin-bottom: 6px; color: #A0A0B0; }
    .cw-modal-select, .cw-search-input, textarea { width: 100%; padding: 10px 14px; border: 1px solid #3A3A50; border-radius: 10px; font-size: 14px; background: #2B2B3B; color: #FFFFFF; outline: none; }
    .cw-modal-select:focus, .cw-search-input:focus, textarea:focus { border-color: #3B82F6; box-shadow: 0 0 0 2px rgba(59,130,246,0.3); }
    .cw-submit-btn { background: #3B82F6; color: white; border: none; padding: 14px 24px; border-radius: 30px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; transition: background 0.2s; }
    .cw-submit-btn:hover { background: #2563eb; }
    .cw-submit-btn:disabled { opacity: 0.6; }
    .cw-close-btn { background: #6B7280; }
    .cw-close-btn:hover { background: #4B5563; }
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple { background: #2B2B3B; border: 1px solid #3A3A50; border-radius: 10px; min-height: 44px; padding: 0 12px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { color: #FFFFFF; line-height: 42px; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice { background: #3A3A50; border: none; color: #FFFFFF; border-radius: 12px; padding: 4px 12px; }
    .select2-dropdown { background: #2B2B3B; border: 1px solid #3A3A50; border-radius: 10px; }
    .select2-results__option { color: #D1D5DB; }
    .select2-results__option--highlighted { background: #3A3A50 !important; color: #FFFFFF !important; }
    .select2-results__option[aria-selected="true"] { background: #3B82F6 !important; color: #FFFFFF !important; }

    .calendar-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); z-index:2000; justify-content:center; align-items:center; }
    .calendar-modal { background: #2B2B3B; border-radius:16px; padding:20px; max-width:700px; width:95%; display:flex; flex-wrap:wrap; gap:20px; color:#fff; max-height:90vh; overflow-y:auto; position:relative; }
    .calendar-modal > div { flex:1; min-width:280px; }
    .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom:15px; }
    .calendar-close-btn { background: transparent; border: none; color: #A0A0B0; font-size: 20px; cursor: pointer; padding: 0 8px; }
    .calendar-close-btn:hover { color: #fff; }
    .calendar-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
    .calendar-grid div { padding:8px; text-align:center; border-radius:8px; cursor:default; }
    .calendar-grid .available { cursor:pointer; background:#3A3A50; }
    .calendar-grid .available:hover { background:#3B82F6; }
    .calendar-grid .today { border:2px solid #3B82F6; }
    .calendar-grid .selected { background:#3B82F6; }
    .calendar-grid .disabled { color:#666; background:#222236; }
    .slots-list { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; max-height:200px; overflow-y:auto; }
    .btn-slot { padding:6px 14px; background:#3A3A50; border:none; border-radius:20px; color:#fff; cursor:pointer; font-size:14px; }
    .btn-slot:hover { background:#3B82F6; }
    .time-record-display { padding:10px 14px; background:#2B2B3B; border:1px solid #3A3A50; border-radius:10px; cursor:pointer; color:#A0A0B0; }
    .time-record-display:hover { border-color:#3B82F6; }
    .time-record-display.disabled { opacity:0.5; cursor:not-allowed; pointer-events:none; }
    .nav-tabs .nav-link { color: #A0A0B0; border: none; background: transparent; }
    .nav-tabs .nav-link.active { color: #FFFFFF; background: #3A3A50; border-radius: 10px 10px 0 0; }
    .tab-content { padding-top: 16px; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: 600; background: #2B2B3B; border: 1px solid #3A3A50; }
    .status-badge.overdue { color: #EF4444; border-color: #EF4444; }
    .status-badge.completed { color: #10B981; border-color: #10B981; }
    .status-badge.active { color: #3B82F6; border-color: #3B82F6; }
    .status-badge.unconfirmed { color: #f97316; border-color: #f97316; }

    #select_services_to_add { max-width: 100%; min-width: 0; flex: 1 1 auto; }
    .cw-field .d-flex { overflow: hidden; }

    /* Сетка для модалки авто */
    .cw-field-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .cw-field-row .cw-field { flex: 1 1 200px; margin-bottom: 18px; }
	
	/* Строка «работа выполнена» — зелёная подсветка */
	#services_table.table-striped > tbody > tr.service-row-done,
	#services_table.table-striped > tbody > tr.service-row-done:nth-of-type(odd) {
		background-color: rgba(16, 185, 129, 0.22) !important;
	}
	#services_table.table-striped > tbody > tr.service-row-done > td {
		background-color: transparent !important;
	}

	/* Строка «запрос на удаление» — визуально неактивна */
	#services_table.table-striped > tbody > tr.service-row-delete-request,
	#services_table.table-striped > tbody > tr.service-row-delete-request:nth-of-type(odd) {
		background-color: rgba(239, 68, 68, 0.10) !important;
		opacity: 0.55;
	}
	#services_table.table-striped > tbody > tr.service-row-delete-request > td {
		background-color: transparent !important;
	}

	/* Чекбокс «Работа выполнена» */
	#services_table .service-done-checkbox {
		width: 20px;
		height: 20px;
		cursor: pointer;
		accent-color: #10B981;
	}
	#services_table .service-done-checkbox:disabled {
		cursor: not-allowed;
		opacity: 0.45;
	}
	
	/* Бейдж «Итого» в карточке сервиса */
	.cw-service-total-badge {
		background: rgba(245, 158, 11, 0.15);
		border: 1px solid rgba(245, 158, 11, 0.5);
		border-radius: 10px;
		padding: 8px 12px;
		text-align: right;
		flex-shrink: 0;
		min-width: 110px;
	}
	.cw-service-total-label {
		font-size: 11px;
		color: #F59E0B;
		margin-bottom: 2px;
		white-space: nowrap;
	}
	.cw-service-total-value {
		font-size: 18px;
		font-weight: 700;
		color: #F59E0B;
		white-space: nowrap;
	}
	
	.cw-pay-block {
		background: #252533;
		border: 1px solid #3A3A50;
		border-radius: 12px;
		padding: 14px;
		margin-bottom: 15px;
	}
	.cw-pay-title {
		font-size: 15px;
		font-weight: 700;
		color: #FFFFFF;
		margin-bottom: 10px;
	}
	.cw-pay-info {
		display: flex;
		flex-wrap: wrap;
		gap: 14px;
		font-size: 13px;
		color: #A0A0B0;
		margin-bottom: 12px;
	}
	.cw-pay-info > div > span {
		color: #FFFFFF;
		font-weight: 600;
	}
	.cw-pay-row {
		display: flex;
		gap: 8px;
		align-items: stretch;
	}
	.cw-pay-row .cw-modal-select {
		flex: 1 1 auto;
		min-width: 0;
	}
	.cw-pay-btn {
		background: #10B981;
		color: #fff;
		border: none;
		border-radius: 10px;
		padding: 10px 20px;
		font-size: 14px;
		font-weight: 600;
		cursor: pointer;
		white-space: nowrap;
		transition: background 0.2s;
		flex-shrink: 0;
	}
	.cw-pay-btn:hover  { background: #059669; }
	.cw-pay-btn:active { background: #047857; }
	.cw-pay-btn:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}
	.cw-pay-hint {
		margin-top: 8px;
		font-size: 12px;
		color: #6B7280;
	}
	
	
	/* Блок рекомендаций */
	.cw-recomendation-block {
		background: #252533;
		border: 1px solid #3A3A50;
		border-radius: 12px;
		padding: 14px;
		margin-bottom: 15px;
	}
	.cw-recomendation-title {
		font-size: 15px;
		font-weight: 700;
		color: #FFFFFF;
		margin-bottom: 8px;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
	}
	.cw-recomendation-status {
		font-size: 12px;
		color: #6B7280;
		font-weight: normal;
	}
	.cw-recomendation-status.cw-saved  { color: #10B981; }
	.cw-recomendation-status.cw-saving { color: #F59E0B; }
	.cw-recomendation-status.cw-error  { color: #EF4444; }
	.cw-recomendation-textarea {
		width: 100%;
		min-height: 90px;
		padding: 10px 12px;
		background: #1E1E2D;
		border: 1px solid #3A3A50;
		border-radius: 10px;
		color: #FFFFFF;
		font-size: 14px;
		font-family: inherit;
		outline: none;
		resize: vertical;
	}
	.cw-recomendation-textarea:focus {
		border-color: #3B82F6;
		box-shadow: 0 0 0 2px rgba(59,130,246,0.25);
	}
	.cw-recomendation-textarea:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}
	
	@media (max-width: 480px) {
		.cw-pay-row { flex-direction: column; }
		.cw-pay-btn { width: 100%; }
	}
	
	
	
    @media (max-width: 767px) {
        #services_table thead { display: none; }
        #services_table,
        #services_table tbody,
        #services_table tr,
        #services_table td { display: block; width: 100%; box-sizing: border-box; }
        #services_table tr { margin-bottom: 15px; border: 1px solid #3A3A50; border-radius: 10px; padding: 12px; background: #2B2B3B; }
        #services_table td { border: none; padding: 4px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        #services_table td:first-child { font-weight: 700; font-size: 16px; border-bottom: 1px solid #3A3A50; margin-bottom: 6px; padding-bottom: 6px; }
        #services_table td:not(:first-child)::before { content: attr(data-label); font-weight: 600; color: #A0A0B0; margin-right: 10px; flex-shrink: 0; }
        #services_table td:last-child { justify-content: flex-end; }
        #services_table td:last-child button { margin-left: auto; }
        #services_table input.service-count,
        #services_table input.service-hours { width: 50px !important; font-size: 13px; padding: 2px 4px; }
        #services_table td .service-total { white-space: nowrap; }
    }
	
	@media (max-width: 767px) {
		#products_table thead { display: none; }
		#products_table,
		#products_table tbody,
		#products_table tr,
		#products_table td { display: block; width: 100%; box-sizing: border-box; }
		#products_table tr { margin-bottom: 15px; border: 1px solid #3A3A50; border-radius: 10px; padding: 12px; background: #2B2B3B; }
		#products_table td { border: none; padding: 4px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
		#products_table td:first-child { font-weight: 700; font-size: 16px; border-bottom: 1px solid #3A3A50; margin-bottom: 6px; padding-bottom: 6px; }
		#products_table td:not(:first-child)::before { content: attr(data-label); font-weight: 600; color: #A0A0B0; margin-right: 10px; flex-shrink: 0; }
	}
	
	.cw-products-widget .nav-tabs .nav-link { color: #A0A0B0; border: none; background: transparent; padding: 8px 16px; }
	.cw-products-widget .nav-tabs .nav-link.active { color: #FFFFFF; background: #3A3A50; border-radius: 10px 10px 0 0; }
	.cw-products-widget .tab-content { padding: 12px 0; }
	.cw-products-widget .form-control { background: #2B2B3B; border: 1px solid #3A3A50; color: #FFFFFF; border-radius: 10px; }
	.cw-products-widget .form-control:focus { border-color: #3B82F6; box-shadow: 0 0 0 2px rgba(59,130,246,0.3); }
	.cw-products-widget .table { color: #FFFFFF; background: transparent; }
	.cw-products-widget .table thead th { border-bottom: 1px solid #3A3A50; color: #A0A0B0; }
	.cw-products-widget .table td { border-top: 1px solid #3A3A50; }
	.cw-products-widget .btn-primary { background: #3B82F6; border: none; }
	.cw-products-widget .btn-primary:hover { background: #2563eb; }
	
	@media (max-width: 767px) {
    .cw-products-widget .table-responsive { border: 0; }
    .cw-products-widget .table { display: block; width: 100%; }
    .cw-products-widget .table thead { display: none; }
    .cw-products-widget .table tbody { display: block; width: 100%; }
    .cw-products-widget .table tr { display: block; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; padding: 10px; }
    .cw-products-widget .table td { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border: none; border-bottom: 1px solid #eee; }
    .cw-products-widget .table td:last-child { border-bottom: none; }
    .cw-products-widget .table td::before { content: attr(data-label); font-weight: bold; color: #555; margin-right: 15px; flex-shrink: 0; }
    .cw-products-widget .table td[data-label="Действие"] { justify-content: flex-end; background: transparent; padding-top: 10px; }
    .cw-products-widget .table td[data-label="Действие"]::before { display: none; }
    .cw-products-widget .table td[data-label="Наименование"] { font-weight: 500; }
	
	.cw-field .d-flex { flex-direction: column; gap: 10px; }
    .cw-field .d-flex button { width: 100% !important; flex-shrink: 0; }
    .cw-field .d-flex .select2 { width: 100% !important; }
}

.select2-container--default .select2-selection--single .select2-selection__clear {
	width: 10px !important;
}

/* Стили для слотов перезаписи */
#rewrite_slots .btn-slot, #rewrite2_slots .btn-slot { padding:6px 14px; background:#3A3A50; border:none; border-radius:20px; color:#fff; cursor:pointer; font-size:14px; }
#rewrite_slots .btn-slot:hover, #rewrite2_slots .btn-slot:hover { background:#3B82F6; }
#rewrite_date, #rewrite2_date { color-scheme: dark; }
</style>

<div class="cw-modal-dark">
	<input type="hidden" id="modal_service_hourly_rate" value="<?php echo (float)$service['hourly_rate']; ?>">
	<input type="hidden" id="modal_order_id" value="<?php echo $orderId; ?>">
	<div class="cw-service-card">
		<div class="cw-service-icon">🔧</div>
		<div class="cw-service-info">
			<div class="cw-service-name"><?php echo htmlspecialchars($service['caption'] ?? 'Автосервис'); ?></div>
			<div class="cw-service-address">📍 <?php echo htmlspecialchars($service['address'] ?? ''); ?></div>
			<div class="cw-service-phone">📞 <?php echo htmlspecialchars($service['phone'] ?? ''); ?></div>
		</div>
		<div class="cw-service-total-badge">
			<div class="cw-service-total-label">💰 Итого</div>
			<div class="cw-service-total-value" id="cw_grand_total">0 ₽</div>
		</div>
	</div>

    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="tab-record" data-toggle="tab" href="#record-pane" role="tab">📋 Запись</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-client" data-toggle="tab" href="#client-pane" role="tab">👤 Клиент</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-services" data-toggle="tab" href="#services-pane" role="tab">🛠️ Услуги</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-products" data-toggle="tab" href="#products-pane" role="tab">📦 Товары</a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="record-pane" role="tabpanel">
            <?php if ($record_id > 0): ?>
                <div class="cw-field">
                    <div class="cw-field-label">Статус</div>
                    <div class="status-badge <?php
                        if ($recordData['post_id'] == -1) echo 'unconfirmed';
                        elseif ($statusLabel == '🔴 Просрочена') echo 'overdue';
                        elseif ($statusLabel == '✅ Выполнена') echo 'completed';
                        else echo 'active';
                    ?>"><?php echo $statusLabel; ?></div>
                </div>
            <?php endif; ?>

            <div class="cw-field">
                <div class="cw-field-label"><i>🛠️</i> Пост <span style="color:#f97316;">*</span></div>
                <select id="select_post" class="cw-modal-select" <?php echo $readonly ? 'disabled' : ''; ?> required>
                    <option value="">-- Выберите пост --</option>
                    <?php foreach ($posts as $post): ?>
                        <option value="<?php echo $post['id']; ?>" <?php echo ($selectedPost == $post['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($post['caption']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cw-field">
                <div class="cw-field-label"><i>📅</i> Дата и время записи <span style="color:#f97316;">*</span></div>
                <div id="time-record-display" class="time-record-display <?php echo empty($selectedPost) || $readonly ? 'disabled' : ''; ?>">
                    <?php echo ($date && $time && !empty($selectedPost)) ? $date . ' ' . $time : 'Сначала выберите пост'; ?>
                </div>
                <input type="hidden" id="selected_datetime" value="<?php echo ($date && $time) ? $date . ' ' . $time : ''; ?>">
            </div>

            <div class="cw-field">
                <div class="cw-field-label"><i>👨‍🔧</i> Мастер</div>
                <select id="select_worker" class="cw-modal-select" <?php echo $readonly ? 'disabled' : ''; ?>>
                    <option value="">-- Не выбран --</option>
                    <?php foreach ($workers as $worker): ?>
                        <option value="<?php echo $worker['id']; ?>" <?php echo ($selectedWorker == $worker['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($worker['fio']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cw-field">
                <div class="cw-field-label"><i>🏪</i> Магазин <span style="color:#f97316;">*</span></div>
                <select id="select_shop" class="cw-modal-select" <?php echo $readonly ? 'disabled' : ''; ?>>
                    <?php
                        $offices_query = $db_link->prepare('SELECT id, caption FROM shop_offices ORDER BY id;');
                        $offices_query->execute();
                        while($office = $offices_query->fetch()) {
                            echo '<option value="'.$office['id'].'">'.htmlspecialchars($office['caption']).'</option>';
                        }
                    ?>
                </select>
            </div>

            <div class="cw-field">
                <div class="cw-field-label"><i>💬</i> Комментарий</div>
                <textarea id="modal_order_comment" rows="3" placeholder="Дополнительная информация..." <?php echo $readonly ? 'readonly' : ''; ?>><?php echo htmlspecialchars($comment); ?></textarea>
            </div>
			
			<?php if ($record_id > 0 && !empty($recordData['orders_ids'])): ?>
			<div class="cw-recomendation-block">
				<div class="cw-recomendation-title">
					<span>💡 Рекомендации</span>
					<span class="cw-recomendation-status" id="cw_recomendation_status"></span>
				</div>
				<textarea id="cw_order_recomendation"
						  class="cw-recomendation-textarea"
						  placeholder="Рекомендации клиенту по этому заказу..."><?php echo htmlspecialchars($order_recomendation); ?></textarea>
			</div>
			<?php endif; ?>
			
			<?php if ($record_id > 0) : ?>
			<div class="cw-field" style="display: flex; gap: 10px; flex-wrap: wrap;">
				<?php if (DP_User::getAdminId() == 1) : ?>
					<button type="button" class="cw-submit-btn" style="width: 160px;" onclick="window.open('https://fordzap.com/cp/shop/orders/order?order_id=<?=$recordData['orders_ids'];?>', '_blank')">
						Карта заказа
					</button>
				<?php endif; ?>

				<?php if ($isRewrite) : ?>
					<button type="button" class="cw-submit-btn"
							style="background: #f97316; white-space: nowrap; width: fit-content;"
							data-toggle="modal" data-target="#modal_rewrite_info">
						ℹ️ Информация о перезаписи
					</button>
				<?php elseif ($statusLabel == '🔴 Просрочена') : ?>
					<button type="button" class="cw-submit-btn"
							style="background: #f97316; white-space: nowrap; width: fit-content;"
							data-toggle="modal" data-target="#modal_rewrite">
						🔄 Перезапись
					</button>
				<?php endif; ?>

				<button type="button" class="cw-submit-btn"
						id="cw_print_work_order_btn"
						style="background: #F59E0B; white-space: nowrap; width: fit-content;">
					🖨️ Заказ-наряд
				</button>

				<!-- === РЕКЛАМАЦИЯ: кнопка === -->
				<button type="button" class="cw-submit-btn"
						id="cw_reclamation_btn"
						data-car-id="<?php echo (int)$selectedCar; ?>"
						style="background: #EF4444; white-space: nowrap; width: fit-content;">
					📄 Рекламация
				</button>
				<!-- === /РЕКЛАМАЦИЯ === -->
			</div> 
			<?php endif; ?>
        </div>

        <div class="tab-pane" id="client-pane" role="tabpanel">
            <div class="cw-field">
                <div class="cw-field-label"><i>👤</i> Клиент <span style="color:#f97316;">*</span></div>
                <select id="select_client" style="width:100%;" <?php echo $readonly ? 'disabled' : ''; ?>>
                    <option value="">-- Выберите клиента --</option>
                    <?php
                        $SQL = "SELECT u.user_id, u.email, u.phone,
                            CASE
                                WHEN u.reg_variant = 1 THEN CONCAT('ФЛ ', IFNULL(p_surname.data_value,''), ' ', IFNULL(p_name.data_value,''), ' ', IFNULL(p_patronymic.data_value,''), ' ', IFNULL(p_phone.data_value,''))
                                WHEN u.reg_variant = 2 THEN CONCAT('ИП  ', IFNULL(p_surname.data_value,''), ' ', IFNULL(p_name.data_value,''), ' ИНН', IFNULL(p_inn.data_value,''))
                                WHEN u.reg_variant = 3 THEN CONCAT(IFNULL(p_company.data_value,'Компания'), ' ИНН', IFNULL(p_inn.data_value,''))
                                ELSE ''
                            END as customer
                        FROM users u
                        LEFT JOIN users_profiles p_surname ON p_surname.user_id = u.user_id AND p_surname.data_key = 'surname'
                        LEFT JOIN users_profiles p_name ON p_name.user_id = u.user_id AND p_name.data_key = 'name'
                        LEFT JOIN users_profiles p_patronymic ON p_patronymic.user_id = u.user_id AND p_patronymic.data_key = 'patronymic'
                        LEFT JOIN users_profiles p_phone ON p_phone.user_id = u.user_id AND p_phone.data_key = 'phonen'
                        LEFT JOIN users_profiles p_inn ON p_inn.user_id = u.user_id AND p_inn.data_key = 'inn'
                        LEFT JOIN users_profiles p_company ON p_company.user_id = u.user_id AND p_company.data_key = 'company_name'
                        ORDER BY customer";
                        $user_query = $db_link->prepare($SQL);
                        $user_query->execute();
                        while($user_record = $user_query->fetch()) {
                            $uid = $user_record["user_id"];
                            $email = $user_record['email'];
                            $phone = $user_record['phone'];
                            $customer = $user_record['customer'];
                            $main_field = "";
                            if(!empty($email)) $main_field .= $email . " ";
                            if(!empty($phone)) $main_field .= str_replace([' ', '(', ')', '-'], '', $phone) . " ";
                            $main_field = trim($main_field);
                            $display_text = $customer . ' ' . $main_field . ' (id-' . $uid . ')';
                            $selected = ($selectedClient == $uid) ? 'selected' : '';
                            echo '<option value="'.$uid.'" '.$selected.'>'.htmlspecialchars($display_text).'</option>';
                        }
                    ?>
                </select>
            </div>
			<div class="cw-field">
				<button type="button" class="btn btn-secondary" id="btn_create_client" style="background: #4B5563; border: none; color: white; padding: 8px 16px; border-radius: 8px;" <?php echo $readonly ? 'disabled' : ''; ?>>➕ Создать клиента</button>
			</div>  
            <div class="cw-field">
                <div class="cw-field-label"><i>🚗</i> Автомобиль <span style="color:#f97316;">*</span></div>
                <select id="select_car" class="cw-modal-select" <?php echo $readonly ? 'disabled' : ''; ?>>
                    <option value="">-- Сначала выберите клиента --</option>
                    <?php if ($selectedClient && $selectedCar): 
                        $carStmt = $db_link->prepare("SELECT id, marka, model, vin FROM shop_docpart_garage WHERE id = ?");
                        $carStmt->execute([$selectedCar]);
                        $car = $carStmt->fetch();
                        if ($car): ?>
                            <option value="<?php echo $car['id']; ?>" selected><?php echo htmlspecialchars($car['marka'] . ' ' . $car['model'] . ' (VIN: ' . $car['vin'] . ')'); ?></option>
                    <?php endif; endif; ?>
                </select>
            </div>
			<!-- === АКТ ПРИЁМА: кнопки === -->
			<div class="cw-field" style="display:flex; gap:10px; flex-wrap:wrap;">
				<button type="button" class="btn btn-secondary" id="btn_create_car" style="background: #4B5563; border: none; color: white; padding: 8px 16px; border-radius: 8px;" <?php echo $readonly ? 'disabled' : ''; ?> >➕ Создать авто</button>
				<button type="button" class="btn btn-secondary" id="btn_car_details" style="background: #4B5563; border: none; color: white; padding: 8px 16px; border-radius: 8px;" <?php echo ($readonly || empty($selectedCar)) ? 'disabled' : ''; ?> >📝 Детали</button>
				<button type="button" class="btn btn-secondary" id="btn_acceptance_act" style="background: #F59E0B; border: none; color: white; padding: 8px 16px; border-radius: 8px;" <?php echo empty($selectedCar) ? 'disabled' : ''; ?> >📋 Акт приёма</button>
			</div>  
        </div>

        <div class="tab-pane" id="services-pane" role="tabpanel">
            <div class="cw-field">
                <div class="cw-field-label"><i>➕</i> Добавить услугу</div>
                <div class="d-flex" style="display: flex; gap: 10px; overflow: hidden;">
                    <select id="select_services_to_add" style="flex:1;" <?php echo $readonly ? 'disabled' : ''; ?>>
                        <option value="">-- Выберите услугу --</option>
                    </select>
                    <button id="btn_add_service" class="btn btn-primary" style="padding: 8px 16px; border-radius: 10px; background: #3B82F6; color: white; border: none; flex-shrink: 0;" <?php echo $readonly ? 'disabled' : ''; ?>>Добавить</button>
                </div>
            </div>

            <div class="cw-field">
                <div class="cw-field-label"><i>🛠️</i> Выбранные услуги</div>
                <div id="services_table_container">
                    <table id="services_table" class="table table-dark table-striped" style="color: #FFFFFF; background: transparent;">
                        <thead>
                            <tr>
								<th style="width:40px; text-align:center;">✓</th>
                                <th>Услуга</th>
                                <th style="width:80px;">Кол-во</th>
                                <th style="width:80px;">Нормо-часы</th>
                                <th style="width:100px;">Цена</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="services_tbody">
                        </tbody>
                    </table>
                </div>
                <div id="services_total" style="margin-top: 10px; text-align: right; color: #A0A0B0; font-size: 14px;">
                    Итого: <span id="total_sum_display" style="color: #FFFFFF; font-weight: 600;">0 ₽</span>
                </div>
            </div>
        </div>

       <div class="tab-pane" id="products-pane" role="tabpanel">
			<?php if ($canAddProducts && $orderId > 0): ?>
				<div class="cw-field">
					<div style="">
						<button class="btn btn-primary" onclick="$('#modal_part_search_valuation').modal('show');" style="background: none;border: 3px solid;font-size: 14px;font-weight: 600;margin-bottom: 6px;color: #A0A0B0;">
							Проценка 🔍
						</button>
						<div class="cw-field-label"><i>➕</i> Добавить товар</div> 
					</div>
					<?php require_once($_SERVER["DOCUMENT_ROOT"] . '/content/carservice/admin_console/add_order_items_widget_adapted.php'); ?>
				</div>

				<div class="cw-field">
					<div class="cw-field-label"><i>🛒</i> Товары в заказе</div>
					<div id="products_table_container">
						<?php if (!empty($orderItems)): ?>
							<table id="products_table" class="table table-dark table-striped" style="color: #FFFFFF; background: transparent;">
								<thead>
									<tr>
										<th>Наименование</th>
										<th>Производитель</th>
										<th>Артикул</th>
										<th style="width:80px;">Кол-во</th>
										<th style="width:100px;">Цена</th>
										<th style="width:100px;">Сумма</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($orderItems as $item): 
										$unit_price = (float)$item['price'];
										$discount   = (float)$item['discount'];
										$unit_final = $unit_price * (1 - $discount / 100);
										$sum        = $unit_final * (int)$item['count_need'];
									?>
									<tr>
										<td><?php echo htmlspecialchars($item['t2_name'] ?? '—'); ?></td>
										<td><?php echo htmlspecialchars($item['t2_manufacturer'] ?? '—'); ?></td>
										<td><?php echo htmlspecialchars($item['t2_article'] ?? '—'); ?></td>
										<td data-label="Кол-во"><?php echo (int)$item['count_need']; ?></td>
										<td data-label="Цена">
											<?php if ($discount > 0): ?>
												<s style="color:#6B7280;"><?php echo number_format($unit_price, 2, '.', ' '); ?> ₽</s>
												<span style="color:#F59E0B; font-weight:600;"><?php echo number_format($unit_final, 2, '.', ' '); ?> ₽</span>
												<span style="color:#EF4444; font-size:11px;">−<?php echo number_format($discount, 0); ?>%</span>
											<?php else: ?>
												<?php echo number_format($unit_price, 2, '.', ' '); ?> ₽
											<?php endif; ?>
										</td>
										<td data-label="Сумма"><?php echo number_format($sum, 2, '.', ' '); ?> ₽</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<div style="margin-top: 10px; text-align: right; color: #A0A0B0; font-size: 14px;">
								Итого по заказу: <span style="color: #FFFFFF; font-weight: 600;"><?php echo number_format($orderTotal, 2, '.', ' '); ?> ₽</span>
							</div>
						<?php else: ?>
							<p style="color: #A0A0B0;">В заказе пока нет товаров.</p>
						<?php endif; ?>
					</div>
				</div>
			<?php elseif ($record_id == 0): ?>
				<div style="padding: 30px; text-align: center; color: #6B7280;">
					<div style="font-size: 48px; margin-bottom: 10px;">📦</div>
					<p>Сначала сохраните запись, чтобы создать заказ и добавить товары.</p>
				</div>
			<?php else: ?>
				<div style="padding: 30px; text-align: center; color: #6B7280;">
					<div style="font-size: 48px; margin-bottom: 10px;">📦</div>
					<p>Для этой записи заказ не найден.</p>
				</div>
			<?php endif; ?>
		</div>
	
	<?php if ($record_id > 0 && !empty($recordData['orders_ids'])): ?>
	<div class="cw-pay-block">
		<div class="cw-pay-title">💳 Оплата заказа</div>
		<div class="cw-pay-info">
			<div>Итого: <span id="cw_pay_total">0 ₽</span></div>
			<div>Оплачено: <span><?php echo number_format($order_paid_sum, 2, '.', ' '); ?> ₽</span></div>
			<div>К оплате: <span id="cw_pay_left" style="color:#F59E0B;">0 ₽</span></div>
		</div>
		<div class="cw-pay-row">
			<input type="number" id="cw_pay_amount" class="cw-modal-select"
				   placeholder="Сумма платежа" min="0.01" step="0.01">
			<button type="button" id="cw_pay_online_btn" class="cw-pay-btn">
				💳 Оплатить online
			</button>
		</div>
		<div class="cw-pay-hint">
			Будет создана операция и вы перейдёте на страницу оплаты.
		</div>
	</div>
	<?php endif; ?>
	
    <button class="cw-submit-btn <?php echo $readonly ? 'cw-close-btn' : ''; ?>" id="confirm_order-btn" <?php echo $readonly ? 'data-dismiss="modal"' : ''; ?>>
        <?php if ($readonly): ?>
            ✕ Закрыть
        <?php else: ?>
            <?php echo $record_id > 0 ? '✅ Сохранить изменения' : '✅ Оформить запись'; ?>
        <?php endif; ?>
    </button>

    <input type="hidden" id="modal_record_id" value="<?php echo $record_id; ?>">
    <input type="hidden" id="modal_service_id" value="<?php echo $service_id; ?>">
    <input type="hidden" id="modal_service_tree_id" value="<?php echo (int)$service['id_tree_list_services']; ?>">
    <input type="hidden" id="modal_operating_mode" value='<?php echo json_encode($operating_mode); ?>'>
    <input type="hidden" id="modal_time_from" value="<?php echo $service['time_from']; ?>">
    <input type="hidden" id="modal_time_to" value="<?php echo $service['time_to']; ?>">
    <input type="hidden" id="modal_record_step" value="<?php echo $service['record_step']; ?>">
</div>


<!-- Модальное окно создания пользователя -->
<div class="modal fade" id="modal_create_user" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background: #2B2B3B; color: #FFFFFF; border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 1px solid #3A3A50;">
                <h5 class="modal-title">Создание нового клиента</h5>
                <button type="button" class="close" data-dismiss="modal" style="color: #FFFFFF;">&times;</button>
            </div>
            <div class="modal-body">
                <form id="create_user_form">
                    <div class="cw-field">
                        <label class="cw-field-label">Фамилия <span style="color:#f97316;">*</span></label>
                        <input type="text" class="cw-modal-select" id="new_user_surname" required>
                    </div>
                    <div class="cw-field">
                        <label class="cw-field-label">Имя <span style="color:#f97316;">*</span></label>
                        <input type="text" class="cw-modal-select" id="new_user_name" required>
                    </div>
                    <div class="cw-field">
                        <label class="cw-field-label">Отчество</label>
                        <input type="text" class="cw-modal-select" id="new_user_patronymic">
                    </div>
					<div class="cw-field">
						<label class="cw-field-label">Телефон <span style="color:#f97316;">*</span></label>
						<input type="text" class="cw-modal-select" id="new_user_phone" placeholder="+7 (___)-___-__-__" required>
					</div>
                    <div class="cw-field">
                        <label class="cw-field-label">Email <span style="color:#f97316;">*</span></label>
                        <input type="email" class="cw-modal-select" id="new_user_email" required>
                    </div>
                    <input type="hidden" id="new_user_password" value="">
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #3A3A50;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background: #6B7280;">Отмена</button>
                <button type="button" class="btn btn-primary" id="save_new_user_btn" style="background: #3B82F6;">Создать</button>
            </div>
        </div>
    </div>
</div>


<!-- Модальное окно создания/редактирования автомобиля -->
<div class="modal fade" id="modal_car" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog" role="document" style="max-width: 720px;">
        <div class="modal-content" style="background: #2B2B3B; color: #FFFFFF; border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 1px solid #3A3A50;">
                <h5 class="modal-title" id="car_modal_title">Создание нового автомобиля</h5>
                <button type="button" class="close" data-dismiss="modal" style="color: #FFFFFF;">&times;</button>
            </div>
            <div class="modal-body" style="max-height: 72vh; overflow-y: auto;">
                <form id="car_form" onsubmit="return false;">
                    <input type="hidden" id="car_id" value="">
                    <input type="hidden" id="car_user_id" value="">

                    <div class="cw-field">
                        <label class="cw-field-label">Название для себя <span style="color:#f97316;">*</span></label>
                        <input type="text" class="cw-modal-select" id="car_caption" placeholder="Мой основной автомобиль" required>
                    </div>

                    <div class="cw-field-row">
                        <div class="cw-field">
                            <label class="cw-field-label">Марка <span style="color:#f97316;">*</span></label>
                            <input type="text" class="cw-modal-select" id="car_marka" placeholder="Toyota" required>
                        </div>
                        <div class="cw-field">
                            <label class="cw-field-label">Модель <span style="color:#f97316;">*</span></label>
                            <input type="text" class="cw-modal-select" id="car_model" placeholder="Camry" required>
                        </div>
                    </div>

                    <div class="cw-field-row">
                        <div class="cw-field">
                            <label class="cw-field-label">Год выпуска <span style="color:#f97316;">*</span></label>
                            <select class="cw-modal-select" id="car_year" required>
                                <option value="">Выберите год</option>
                            </select>
                        </div>
                        <div class="cw-field">
                            <label class="cw-field-label">Тип кузова</label>
                            <select class="cw-modal-select" id="car_body_type">
                                <option value="">Выберите тип кузова</option>
                                <option value="sedan">Седан</option>
                                <option value="hatchback">Хэтчбек</option>
                                <option value="station">Универсал</option>
                                <option value="suv">Внедорожник</option>
                                <option value="crossover">Кроссовер</option>
                                <option value="coupe">Купе</option>
                                <option value="cabriolet">Кабриолет</option>
                                <option value="minivan">Минивэн</option>
                                <option value="pickup">Пикап</option>
                            </select>
                        </div>
                    </div>

                    <div class="cw-field-row">
                        <div class="cw-field">
                            <label class="cw-field-label">Объем двигателя</label>
                            <input type="text" class="cw-modal-select" id="car_engine_value" placeholder="2.0 л">
                        </div>
                        <div class="cw-field">
                            <label class="cw-field-label">Тип топлива</label>
                            <select class="cw-modal-select" id="car_fuel_type">
                                <option value="">Выберите тип топлива</option>
                                <option value="pet">Бензин</option>
                                <option value="dis">Дизель</option>
                                <option value="hybrid">Гибрид</option>
                                <option value="elect">Электричество</option>
                                <option value="gas">Газ</option>
                            </select>
                        </div>
                    </div>

                    <div class="cw-field-row">
                        <div class="cw-field">
                            <label class="cw-field-label">Тип КПП</label>
                            <select class="cw-modal-select" id="car_transmission">
                                <option value="">Выберите КПП</option>
                                <option value="mkpp">Механическая</option>
                                <option value="akpp">Автоматическая</option>
                                <option value="robot">Робот</option>
                                <option value="variator">Вариатор</option>
                            </select>
                        </div>
                        <div class="cw-field">
                            <label class="cw-field-label">Расположение руля</label>
                            <select class="cw-modal-select" id="car_wheel">
                                <option value="">Выберите расположение</option>
                                <option value="left">Левый</option>
                                <option value="right">Правый</option>
                            </select>
                        </div>
                    </div>

                    <div class="cw-field-row">
                        <div class="cw-field">
                            <label class="cw-field-label">VIN <span style="color:#f97316;">*</span></label>
                            <input type="text" class="cw-modal-select" id="car_vin" placeholder="Введите VIN номер" maxlength="17" required>
                        </div>
                        <div class="cw-field">
                            <label class="cw-field-label">FRAME</label>
                            <input type="text" class="cw-modal-select" id="car_frame" placeholder="Номер кузова">
                        </div>
                    </div>

                    <div class="cw-field-row">
                        <div class="cw-field">
                            <label class="cw-field-label">Цвет</label>
                            <input type="text" class="cw-modal-select" id="car_color" placeholder="Черный">
                        </div>
                        <div class="cw-field">
                            <label class="cw-field-label">Страна сборки</label>
                            <input type="text" class="cw-modal-select" id="car_country" placeholder="Япония">
                        </div>
                    </div>

                    <div class="cw-field">
                        <label class="cw-field-label">Гос номер</label>
                        <input type="text" class="cw-modal-select" id="car_license_plate" placeholder="А123БВ777">
                    </div>

                    <div class="cw-field">
                        <label class="cw-field-label">Примечание</label>
                        <textarea class="cw-modal-select" id="car_note" rows="3" placeholder="Дополнительная информация об автомобиле"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #3A3A50;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background: #6B7280;">Закрыть</button>
                <button type="button" class="btn btn-primary" id="save_car_btn" style="background: #3B82F6;">Сохранить</button>
            </div>
        </div>
    </div>
</div>


<!-- Модальное окно перезаписи -->
<div class="modal fade" id="modal_rewrite" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 99999;">
    <div class="modal-dialog" role="document" style="max-width: 600px;">
        <div class="modal-content" style="background: #2B2B3B; color: #FFFFFF; border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 1px solid #3A3A50;">
                <h5 class="modal-title">🔄 Перезапись визита</h5>
                <button type="button" class="close" data-dismiss="modal" style="color: #FFFFFF;">&times;</button>
            </div>
            <div class="modal-body">
                <div class="cw-field">
                    <label class="cw-field-label">Текущая дата и время</label>
                    <div style="padding:10px 14px;background:#1F1F2E;border-radius:10px;color:#A0A0B0;">
                        <?php echo htmlspecialchars(($date && $time) ? ($date.' '.$time) : '—'); ?>
                    </div>
                </div>
                <div class="cw-field">
                    <label class="cw-field-label">Новая дата <span style="color:#f97316;">*</span></label>
                    <input type="date" id="rewrite_date" class="cw-modal-select">
                </div>
                <div class="cw-field">
                    <label class="cw-field-label">Доступное время <span style="color:#f97316;">*</span></label>
                    <div id="rewrite_slots" class="slots-list" style="color:#A0A0B0;">Выберите дату</div>
                    <input type="hidden" id="rewrite_selected_datetime" value="">
                </div>
                <div class="cw-field">
                    <label class="cw-field-label">Комментарий перезаписи</label>
                    <textarea id="rewrite_comment_input" rows="3" class="cw-modal-select" placeholder="Причина перезаписи / примечание..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #3A3A50;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background: #6B7280;">Отмена</button>
                <button type="button" class="btn btn-primary" id="save_rewrite_btn" style="background: #3B82F6;">Сохранить перезапись</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно информации о перезаписи -->
<?php if ($isRewrite) : ?>
<div class="modal fade" id="modal_rewrite_info" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 99999;">
    <div class="modal-dialog" role="document" style="max-width: 600px;">
        <div class="modal-content" style="background: #2B2B3B; color: #FFFFFF; border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 1px solid #3A3A50;">
                <h5 class="modal-title">ℹ️ Информация о перезаписи</h5>
                <button type="button" class="close" data-dismiss="modal" style="color: #FFFFFF;">&times;</button>
            </div>
            <div class="modal-body">
                <div class="cw-field">
                    <label class="cw-field-label">Комментарий перезаписи</label>
                    <div style="padding:12px;background:#1F1F2E;border-radius:10px;color:#E5E7EB;min-height:44px;">
                        <?php echo $rewriteComment !== '' ? nl2br(htmlspecialchars($rewriteComment)) : '<span style="color:#6B7280;">—</span>'; ?>
                    </div>
                </div>

                <hr style="border-color:#3A3A50;">

                <div class="cw-field">
                    <label class="cw-field-label" style="font-size:16px;color:#FFFFFF;">🔁 Перезаписать снова</label>
                </div>

                <div class="cw-field">
                    <label class="cw-field-label">Новая дата <span style="color:#f97316;">*</span></label>
                    <input type="date" id="rewrite2_date" class="cw-modal-select">
                </div>
                <div class="cw-field">
                    <label class="cw-field-label">Доступное время <span style="color:#f97316;">*</span></label>
                    <div id="rewrite2_slots" class="slots-list" style="color:#A0A0B0;">Выберите дату</div>
                    <input type="hidden" id="rewrite2_selected_datetime" value="">
                </div>
                <div class="cw-field">
                    <label class="cw-field-label">Новый комментарий</label>
                    <textarea id="rewrite2_comment_input" rows="3" class="cw-modal-select" placeholder="Комментарий к новой перезаписи..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #3A3A50;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background: #6B7280;">Закрыть</button>
                <button type="button" class="btn btn-primary" id="save_rewrite2_btn" style="background: #3B82F6;">Перезаписать снова</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<script>
var preselectedServices = <?php echo json_encode($selected_services_details); ?>;
var servicesStatements  = <?php echo json_encode($services_statements); ?>;
var readonly            = <?php echo $readonly ? 'true' : 'false'; ?>;
var isRecordCompleted   = <?php echo !empty($isCompleted) ? 'true' : 'false'; ?>;
var allServices = [];
var serviceHourlyRate = parseFloat(document.getElementById('modal_service_hourly_rate').value) || 0;
var orderId = document.getElementById('modal_order_id').value;

var productsTotal = <?php echo (float)$orderTotal; ?>;
var paidSum       = <?php echo (float)$order_paid_sum; ?>;
var servicesTotal = 0;

function cwFormatMoney(v) {
    return Number(v).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

function cwUpdateGrandTotal() {
    var grand = servicesTotal + productsTotal;
    var left  = grand - paidSum;
    if (left < 0) left = 0;

    var elGrand = document.getElementById('cw_grand_total');
    if (elGrand) elGrand.textContent = cwFormatMoney(grand) + ' ₽';

    var elPayTotal = document.getElementById('cw_pay_total');
    if (elPayTotal) elPayTotal.textContent = cwFormatMoney(grand) + ' ₽';

    var elLeft = document.getElementById('cw_pay_left');
    if (elLeft) elLeft.textContent = cwFormatMoney(left) + ' ₽';

    // Автоподстановка суммы в поле оплаты (если пользователь ещё не трогал)
    var amountEl = document.getElementById('cw_pay_amount');
    if (amountEl && !amountEl.dataset.touched) {
        amountEl.value = left.toFixed(2);
    }
}

// ================= РЕКОМЕНДАЦИИ =================
(function() {
    function bindRecomendationSave() {
        var el = document.getElementById('cw_order_recomendation');
        if (!el) return;

        var statusEl = document.getElementById('cw_recomendation_status');
        var orderIdEl = document.getElementById('modal_order_id');
        var orderId   = orderIdEl ? parseInt(orderIdEl.value) : 0;
        if (!orderId) return;

        var originalValue = el.value;
        var timer = null;

        function setStatus(text, cls) {
            if (!statusEl) return;
            statusEl.textContent = text;
            statusEl.className = 'cw-recomendation-status' + (cls ? ' ' + cls : '');
        }

        function doSave() {
            var value = el.value;
            if (value === originalValue) return;

            setStatus('⏳ Сохранение...', 'cw-saving');

            jQuery.ajax({
                type: "POST",
                url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_set_order_recomendation.php",
                dataType: "json",
                data: {
                    order_id: orderId,
                    recomendation: value,
                    csrf_guard_key: '<?php echo $user_session["csrf_guard_key"]; ?>'
                },
                success: function(answer) {
                    if (answer && answer.status == true) {
                        originalValue = value;
                        setStatus('✓ Сохранено', 'cw-saved');
                        setTimeout(function() {
                            if (statusEl && statusEl.textContent === '✓ Сохранено') {
                                setStatus('');
                            }
                        }, 2500);
                    } else {
                        setStatus('⚠ Ошибка сохранения', 'cw-error');
                    }
                },
                error: function() {
                    setStatus('⚠ Ошибка соединения', 'cw-error');
                }
            });
        }

        el.addEventListener('input', function() {
            if (timer) clearTimeout(timer);
            timer = setTimeout(doSave, 1500);
        });

        el.addEventListener('blur', function() {
            if (timer) clearTimeout(timer);
            doSave();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindRecomendationSave);
    } else {
        bindRecomendationSave();
    }
})();

(function() {
    var currentDate = new Date();
    var selectedDateTime = document.getElementById('selected_datetime').value;
    var selectedDay = null;
    var display = document.getElementById('time-record-display');
    var overlay = document.getElementById('calendar-overlay');
    var slotsHeader = document.getElementById('slots-header');
    var slotsList = document.getElementById('slots-list');
    var operating_mode = JSON.parse(document.getElementById('modal_operating_mode').value || '[]');
    var isPostSelected = <?php echo !empty($selectedPost) ? 'true' : 'false'; ?>;

    var selectedServices = [];

    function formatDateLocal(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function getCalendarStart(year, month) {
        var first = new Date(year, month, 1);
        var dow = (first.getDay() + 6) % 7;
        var start = new Date(first);
        start.setDate(first.getDate() - dow);
        start.setHours(0,0,0,0);
        return start;
    }

    function isWorkingDay(dow) {
        var dayInDataFormat = (dow === 0) ? 7 : dow;
        return operating_mode.indexOf(dayInDataFormat.toString()) !== -1;
    }

    function renderCalendar() {
        var monthName = currentDate.toLocaleString('ru-RU', { month: 'long', year: 'numeric' });
        var monthEl = document.getElementById('calendar-month');
        if (monthEl) monthEl.textContent = monthName;

        var grid = document.querySelector('.calendar-grid');
        if (!grid) return;
        grid.innerHTML = '';

        var year = currentDate.getFullYear();
        var month = currentDate.getMonth();
        var start = getCalendarStart(year, month);
        var today = new Date(); today.setHours(0,0,0,0);

        for (var i = 0; i < 42; i++) {
            var cellDate = new Date(start);
            cellDate.setDate(start.getDate() + i);
            var div = document.createElement('div');
            div.textContent = cellDate.getDate();

            if (cellDate.getMonth() !== month) {
                div.classList.add('disabled');
            } else {
                var dow = cellDate.getDay();
                var isToday = cellDate.getTime() === today.getTime();
                var isFuture = cellDate.getTime() >= today.getTime();

                if (isToday) div.classList.add('today');
                if (selectedDay && cellDate.getTime() === selectedDay.getTime()) {
                    div.classList.add('selected');
                }

                if (isFuture && isWorkingDay(dow) && !readonly) {
                    div.classList.add('available');
                    (function(dayCopy) {
                        div.addEventListener('click', function() {
                            selectDate(dayCopy);
                        });
                    })(new Date(cellDate));
                } else {
                    div.classList.add('disabled');
                }
            }
            grid.appendChild(div);
        }
    }

    function selectDate(day) {
        if (readonly) return;
        selectedDay = new Date(day);
        var formattedDate = formatDateLocal(day);
        var postId = document.getElementById('select_post').value;
        var serviceId = document.getElementById('modal_service_id').value;
        if (slotsHeader) slotsHeader.textContent = 'Доступное время: ' + formattedDate;
        if (slotsList) slotsList.innerHTML = '<div>Загрузка...</div>';
        renderCalendar();

        jQuery.ajax({
            type: "POST",
            url: "/cp/content/car_service/modules/calendar/Calendar.php",
            data: {
                action: 'get_slots',
                service_id: serviceId,
                date: formattedDate,
                post_id: postId
            },
            dataType: "json",
            success: function(answer) {
                if (answer && answer.slots) {
                    renderSlots(answer.slots);
                } else {
                    if (slotsList) slotsList.innerHTML = '<div>Нет свободных слотов</div>';
                }
            },
            error: function() {
                if (slotsList) slotsList.innerHTML = '<div>Ошибка загрузки слотов</div>';
            }
        });
    }

    function renderSlots(slots) {
        if (!slotsList) return;
        slotsList.innerHTML = '';
        if (slots.length === 0) {
            slotsList.innerHTML = '<div>Нет свободных слотов</div>';
        } else {
            slots.forEach(function(slot) {
                var btn = document.createElement('button');
                btn.className = 'btn-slot';
                btn.textContent = slot;
                btn.addEventListener('click', function() {
                    var datePart = slotsHeader ? slotsHeader.textContent.replace('Доступное время: ', '') : '';
                    selectedDateTime = datePart + ' ' + slot;
                    if (display) display.textContent = selectedDateTime;
                    document.getElementById('selected_datetime').value = selectedDateTime;
                    closeCalendar();
                });
                slotsList.appendChild(btn);
            });
        }
    }

    function openCalendar() {
        if (readonly) return;
        if (!isPostSelected) {
            alert('Сначала выберите пост!');
            return;
        }
        if (overlay) overlay.style.display = 'flex';
        renderCalendar();
    }

    function closeCalendar() {
        if (overlay) overlay.style.display = 'none';
    }

function loadServicesList() {
    var treeId = document.getElementById('modal_service_tree_id').value;
    if (!treeId) return;
    var $select = jQuery('#select_services_to_add');

    if ($select.data('select2')) {
        $select.select2('destroy');
    }

    if ($select.find('option').length > 1) {
        initSelect2($select, 'Поиск услуги...', true);
        return;
    }

    jQuery.ajax({
        type: "GET",
        url: "/content/carservice/ajax_get_services.php",
        data: { tree_id: treeId },
        dataType: "json",
        success: function(data) {
            if (data && data.services && data.services.length) {
                allServices = data.services;
                var html = '<option value="">-- Выберите услугу --</option>';
                data.services.forEach(function(service) {
                    html += '<option value="' + service.id + '" data-price="' + service.price + '" data-hourly="' + service.hourly_rate + '">' + service.caption + '</option>';
                });
                $select.html(html);
                initSelect2($select, 'Поиск услуги...', true);
            } else {
                $select.html('<option value="">Нет доступных услуг</option>');
                initSelect2($select, 'Поиск услуги...', true);
            }
        },
        error: function() {
            console.error('Ошибка загрузки услуг');
            $select.html('<option value="">Ошибка загрузки</option>');
            initSelect2($select, 'Поиск услуги...', true);
        }
    });
}

function initSelect2($select, placeholder, allowClear) {
    if ($select.data('select2')) {
        $select.select2('destroy');
    }
    var readonly = window.readonly || false;
    var parent = jQuery('#cw_detail_view').length ? jQuery('#cw_detail_view') : null;
    if (typeof is_admin_console === 'undefined') {
        parent = jQuery('#modal_create_order .modal-body');
    }
    
    $select.select2({
        placeholder: placeholder || 'Поиск...',
        allowClear: allowClear !== undefined ? allowClear : true,
        width: '100%',
        dropdownParent: parent,
        disabled: readonly
    });
}

function addService() {
    if (readonly) return;
    var select = document.getElementById('select_services_to_add');
    var selectedOption = select.options[select.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
        alert('Выберите услугу');
        return;
    }
    var caption = selectedOption.text;
    var baseHours = parseFloat(selectedOption.dataset.hourly) || 0;
    var price = baseHours * serviceHourlyRate;

    if (selectedServices.some(s => s.caption === caption)) {
        alert('Эта услуга уже добавлена');
        return;
    }

	selectedServices.push({
		id: 0,
		caption: caption,
		hours: baseHours,
		price: price,
		count: 1,
		discount: 0,
		statement_type: 0
	});
    renderServicesTable();
    select.selectedIndex = 0;
    jQuery(select).val(null).trigger('change');
}

function renderServicesTable() {
    var tbody = document.getElementById('services_tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    var totalSum = 0;

    selectedServices.forEach(function(item, index) {
        var currentHours = parseFloat(item.hours) || 0;
        var pricePerUnit = currentHours * serviceHourlyRate;
        var finalPrice   = pricePerUnit * (1 - item.discount/100) * item.count;
        totalSum += finalPrice;

        var serviceRecordId = parseInt(item.id) || 0;
        var caption         = item.caption || '';
        var statementType   = parseInt(item.statement_type) || 0;
        var isDone          = (statementType === 2);
        var isDeleteRequest = (statementType === 1);

        var canToggleDone  = (serviceRecordId > 0) && !readonly && !isRecordCompleted && !isDeleteRequest;
        var inputsDisabled = (readonly || isRecordCompleted || isDeleteRequest) ? 'disabled' : '';

        var rowClass = '';
        if (isDone)          rowClass = 'service-row-done';
        if (isDeleteRequest) rowClass = 'service-row-delete-request';

        var checkboxTitle = '';
        if (serviceRecordId === 0)  checkboxTitle = 'Сначала сохраните запись';
        else if (isDeleteRequest)   checkboxTitle = 'Есть запрос на удаление';
        else if (isRecordCompleted) checkboxTitle = 'Запись выполнена';
        else                        checkboxTitle = 'Работа выполнена';

        var checkboxHtml =
            '<input type="checkbox" class="service-done-checkbox" ' +
            ' data-index="' + index + '" ' +
            (isDone ? 'checked' : '') + ' ' +
            (canToggleDone ? '' : 'disabled') +
            ' title="' + checkboxTitle + '">';

        var actionBtn;
        if (isDeleteRequest) {
            actionBtn =
                '<button type="button" class="btn btn-sm btn-warning cancel-delete-service" ' +
                ' data-index="' + index + '" title="Отменить запрос на удаление" ' +
                (readonly || isRecordCompleted ? 'disabled' : '') +
                ' style="background:#f97316;border:none;padding:2px 8px;border-radius:6px;color:#fff;">↩️</button>';
        } else {
            actionBtn =
                '<button type="button" class="btn btn-sm btn-danger remove-service" ' +
                ' data-index="' + index + '" title="Запросить удаление" ' +
                (readonly || isRecordCompleted ? 'disabled' : '') +
                ' style="background:#EF4444;border:none;padding:2px 8px;border-radius:6px;color:#fff;">✕</button>';
        }

        var tr = document.createElement('tr');
        if (rowClass) tr.className = rowClass;
        tr.innerHTML =
            '<td style="text-align:center;">' + checkboxHtml + '</td>' +
            '<td>' + caption + '</td>' +
            '<td data-label="Кол-во"><input type="number" class="form-control form-control-sm service-count" ' +
                'value="' + item.count + '" min="1" ' + inputsDisabled +
                ' style="width:60px;background:#2B2B3B;color:#fff;border:1px solid #3A3A50;border-radius:6px;padding:4px;" data-index="' + index + '"></td>' +
            '<td data-label="Нормо-часы"><input type="number" class="form-control form-control-sm service-hours" ' +
                'value="' + currentHours + '" min="0" step="0.1" ' + inputsDisabled +
                ' style="width:70px;background:#2B2B3B;color:#fff;border:1px solid #3A3A50;border-radius:6px;padding:4px;" data-index="' + index + '"></td>' +
            '<td data-label="Цена за ед.">' + pricePerUnit.toFixed(2) + ' ₽</td>' +
            '<td data-label="Итого" class="service-total">' + finalPrice.toFixed(2) + ' ₽</td>' +
            '<td style="text-align:right;">' + actionBtn + '</td>';
        tbody.appendChild(tr);
    });

    document.getElementById('total_sum_display').textContent = totalSum.toFixed(2) + ' ₽';

    tbody.querySelectorAll('.service-count').forEach(function(input) {
        input.addEventListener('change', function() {
            var idx = parseInt(this.dataset.index);
            var val = parseInt(this.value) || 1;
            if (val < 1) val = 1;
            selectedServices[idx].count = val;
            renderServicesTable();
        });
    });
    tbody.querySelectorAll('.service-hours').forEach(function(input) {
        input.addEventListener('change', function() {
            var idx = parseInt(this.dataset.index);
            var val = parseFloat(this.value) || 0;
            if (val < 0) val = 0;
            selectedServices[idx].hours = val;
            renderServicesTable();
        });
    });

    tbody.querySelectorAll('.service-done-checkbox').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var idx  = parseInt(this.dataset.index);
            var item = selectedServices[idx];
            var done = this.checked;
            if (!item) return;

            if (!item.id || !item.caption) {
                this.checked = !done;
                alert('Сначала сохраните запись');
                return;
            }

            var self = this;
            self.disabled = true;

            jQuery.ajax({
                type: "POST",
                url: "/cp/content/car_service/ajax_service_statements.php",
                dataType: "json",
                data: {
                    action:     'toggle_done',
                    record_id:  document.getElementById('modal_record_id').value,
                    service_id: document.getElementById('modal_service_id').value,
                    caption:    item.caption,
                    done:       done ? 1 : 0
                },
                success: function(resp) {
                    if (resp && resp.status) {
                        item.statement_type = parseInt(resp.statement_type) || 0;
                        renderServicesTable();
                    } else {
                        self.checked = !done;
                        self.disabled = false;
                        alert((resp && resp.message) || 'Ошибка');
                    }
                },
                error: function() {
                    self.checked = !done;
                    self.disabled = false;
                    alert('Ошибка соединения с сервером');
                }
            });
        });
    });

    tbody.querySelectorAll('.remove-service').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var idx  = parseInt(this.dataset.index);
            var item = selectedServices[idx];
            if (!item) return;

            if (!item.id) {
                if (confirm('Удалить услугу?')) {
                    selectedServices.splice(idx, 1);
                    renderServicesTable();
                }
                return;
            }

            if (!confirm('Создать запрос на удаление этой услуги?')) return;

            var self = this;
            self.disabled = true;

            jQuery.ajax({
                type: "POST",
                url: "/cp/content/car_service/ajax_service_statements.php",
                dataType: "json",
                data: {
                    action:     'request_delete',
                    record_id:  document.getElementById('modal_record_id').value,
                    service_id: document.getElementById('modal_service_id').value,
                    caption:    item.caption
                },
                success: function(resp) {
                    if (resp && resp.status) {
                        item.statement_type = parseInt(resp.statement_type) || 0;
                        renderServicesTable();
                    } else {
                        self.disabled = false;
                        alert((resp && resp.message) || 'Ошибка');
                    }
                },
                error: function() {
                    self.disabled = false;
                    alert('Ошибка соединения с сервером');
                }
            });
        });
    });

    tbody.querySelectorAll('.cancel-delete-service').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var idx  = parseInt(this.dataset.index);
            var item = selectedServices[idx];
            if (!item) return;

            if (!confirm('Отменить запрос на удаление?')) return;

            var self = this;
            self.disabled = true;

            jQuery.ajax({
                type: "POST",
                url: "/cp/content/car_service/ajax_service_statements.php",
                dataType: "json",
                data: {
                    action:     'cancel_delete',
                    record_id:  document.getElementById('modal_record_id').value,
                    service_id: document.getElementById('modal_service_id').value,
                    caption:    item.caption
                },
                success: function(resp) {
                    if (resp && resp.status) {
                        item.statement_type = parseInt(resp.statement_type) || 0;
                        renderServicesTable();
                    } else {
                        self.disabled = false;
                        alert((resp && resp.message) || 'Ошибка');
                    }
                },
                error: function() {
                    self.disabled = false;
                    alert('Ошибка соединения с сервером');
                }
            });
        });
    });
	
	servicesTotal = totalSum;
	cwUpdateGrandTotal();
	
}
	
function loadExistingServices() {
    if (preselectedServices && preselectedServices.length > 0) {
        selectedServices = preselectedServices.map(function(s) {
            var hours = parseFloat(s.hourly_rate) || 0;
            var price = parseFloat(s.price) || (hours * serviceHourlyRate);
            var stmt  = servicesStatements[s.caption] || null;
            return {
                id:             parseInt(s.id) || 0,
                caption:        s.caption || '',
                hours:          hours,
                price:          price,
                count:          parseInt(s.count) || 1,
                discount:       parseFloat(s.discount) || 0,
                statement_type: stmt ? parseInt(stmt.statement_type) : 0
            };
        });
        renderServicesTable();
    }
}

    function confirm_order() {
        if (readonly) return;
        var btn = document.getElementById('confirm_order-btn');
        if (!btn || btn.disabled) return;
        btn.disabled = true;
        btn.innerHTML = '⏳ Сохранение...';

        var record_id = document.getElementById('modal_record_id').value;
        var service_id = document.getElementById('modal_service_id').value;
        var datetime = document.getElementById('selected_datetime').value;
        var post_id = document.getElementById('select_post').value;
        var user_id = jQuery('#select_client').val();
        var car_id = document.getElementById('select_car').value;
        var worker_id = document.getElementById('select_worker').value;
        var shop_id = document.getElementById('select_shop').value;
        var comment = document.getElementById('modal_order_comment').value;

var servicesData = selectedServices.map(function(item) {
    var hours = parseFloat(item.hours) || 0;
    var price = hours * serviceHourlyRate;

    return {
        id:          item.id || 0,
        caption:     item.caption,
        count:       item.count,
        price:       price,
        discount:    item.discount,
        hourly_rate: hours
    };
});

        if (!post_id) { alert("Выберите пост"); resetButton(); return; }
        if (!datetime) { alert("Выберите дату и время"); resetButton(); return; }
        if (!user_id) { alert("Выберите клиента"); resetButton(); return; }
        if (!car_id) { alert("Выберите автомобиль"); resetButton(); return; }
        if (!shop_id) { alert("Выберите магазин"); resetButton(); return; }

        var data = {
            record_id: record_id,
            user_id: user_id,
            order_message: comment,
            csrf_guard_key: "<?php echo $user_session["csrf_guard_key"]; ?>",
            service_record_date: datetime,
            service_post_id: post_id,
            service_sto_id: service_id,
            service_car_id: car_id,
            service_worker_id: worker_id,
            shop_id: shop_id,
            services: JSON.stringify(servicesData)
        };

        jQuery.ajax({
            type: "POST",
            url: "/cp/content/car_service/ajax_create_order_record.php",
            dataType: "json",
            data: data,
            success: function(answer) {
                if (answer.status == true) {
                    jQuery("#modal_create_order").modal('hide');
                    if (typeof window.parent.cwLoadData === 'function') {
                        window.parent.cwLoadData();
                    } else if (typeof cwLoadData === 'function') {
                        cwLoadData();
                    } else {
                        location.reload();
                    }
                } else {
                    alert(answer.message);
                    resetButton();
                }
            },
            error: function() {
                alert("Ошибка при сохранении");
                resetButton();
            }
        });

        function resetButton() {
            btn.disabled = false;
            btn.innerHTML = record_id ? '✅ Сохранить изменения' : '✅ Оформить запись';
        }
    }
	
	// ================= ОПЛАТА ONLINE =================
	function cwPayOnline() {
		var orderIdEl = document.getElementById('modal_order_id');
		var orderId   = orderIdEl ? parseInt(orderIdEl.value) : 0;
		if (!orderId) {
			alert('Сначала сохраните запись, чтобы создать заказ');
			return;
		}

		var amountEl = document.getElementById('cw_pay_amount');
		var amount   = parseFloat(amountEl.value) || 0;
		if (amount <= 0) {
			alert('Укажите сумму платежа больше 0');
			return;
		}

		var grand = servicesTotal + productsTotal;
		var left  = grand - paidSum;
		if (left < 0) left = 0;

		if (amount > left && left > 0) {
			if (!confirm('Сумма превышает остаток по заказу (' + cwFormatMoney(left) + ' ₽). Продолжить?')) {
				return;
			}
		}

		var btn = document.getElementById('cw_pay_online_btn');
		var origText = btn.innerHTML;
		btn.disabled = true;
		btn.innerHTML = '⏳ Создание операции...';

		var request_object = {
			order_id: orderId,
			amount: amount
		};
		
		jQuery.ajax({
			type: "POST",
			url: "/cp/content/car_service/ajax_pay_order_online.php",
			dataType: "json",
			data: {
				order_id: orderId,
				amount:   amount
			},
			success: function(answer_ob) {
				if (typeof answer_ob.result === 'undefined') {
					alert('Ошибка парсинга результата');
					btn.disabled = false;
					btn.innerHTML = origText;
					return;
				}

				if (answer_ob.result == true) {
					if (!answer_ob.pay_system || answer_ob.pay_system == 0) {
						alert('К сайту не подключена платёжная система. Пополнение баланса возможно через кассу.');
						btn.disabled = false;
						btn.innerHTML = origText;
						return;
					}

					var csrf = '<?php echo $user_session["csrf_guard_key"]; ?>';
					location = "/content/shop/finance/payment_systems/" + answer_ob.pay_system
							 + "/go_to_pay.php?operation=" + answer_ob.operation
							 + "&csrf_guard_key=" + encodeURIComponent(csrf);
				} else {
					alert(answer_ob.message || 'Ошибка создания операции');
					btn.disabled = false;
					btn.innerHTML = origText;
				}
			},
			error: function(xhr) {
				var msg = 'Ошибка соединения с сервером';
				try {
					var r = JSON.parse(xhr.responseText);
					if (r && r.message) msg = r.message;
				} catch (e) {}
				alert(msg);
				btn.disabled = false;
				btn.innerHTML = origText;
			}
		});
	}

	// Привязка обработчиков
	jQuery(document).ready(function() {
		jQuery('#cw_pay_online_btn').on('click', cwPayOnline);

		jQuery('#cw_pay_amount').on('input', function() {
			this.dataset.touched = '1';
		});
	});
	
    // ===== Работа с модалкой авто =====
    function generateYearOptions() {
        var $year = jQuery('#car_year');
        if ($year.find('option').length > 1) return;
        var nowYear = new Date().getFullYear();
        var html = '<option value="">Выберите год</option>';
        for (var y = nowYear + 1; y >= 1950; y--) {
            html += '<option value="' + y + '">' + y + '</option>';
        }
        $year.html(html);
    }

    function openCarModal(carId) {
        generateYearOptions();
        jQuery('#car_form')[0].reset();
        jQuery('#car_id').val(0);
        jQuery('#car_user_id').val(jQuery('#select_client').val() || '');

        if (carId && parseInt(carId) > 0) {
            jQuery('#car_modal_title').text('Редактирование автомобиля');
            jQuery('#save_car_btn').prop('disabled', true).text('Загрузка...');

            jQuery.ajax({
                type: "POST",
                url: "/cp/content/car_service/ajax_create_car.php",
                dataType: "json",
                data: { action: 'get', car_id: carId },
                success: function(resp) {
                    jQuery('#save_car_btn').prop('disabled', false).text('Сохранить');
                    if (resp.status && resp.car) {
                        var c = resp.car;
                        jQuery('#car_id').val(c.id);
                        jQuery('#car_user_id').val(c.user_id);
                        jQuery('#car_caption').val(c.caption || '');
                        jQuery('#car_marka').val(c.marka || '');
                        jQuery('#car_model').val(c.model || '');
                        jQuery('#car_year').val(c.year || '');
                        jQuery('#car_body_type').val(c.body_type || '');
                        jQuery('#car_engine_value').val(c.engine_value || '');
                        jQuery('#car_fuel_type').val(c.fuel_type || '');
                        jQuery('#car_transmission').val(c.transmission || '');
                        jQuery('#car_vin').val(c.vin || '');
                        jQuery('#car_frame').val(c.frame || '');
                        jQuery('#car_color').val(c.color || '');
                        jQuery('#car_country').val(c.country || '');
                        jQuery('#car_wheel').val(c.wheel || '');
                        jQuery('#car_license_plate').val(c.license_plate || '');
                        jQuery('#car_note').val(c.note || '');
                    } else {
                        alert('Ошибка: ' + (resp.message || 'Не удалось загрузить авто'));
                    }
                },
                error: function() {
                    jQuery('#save_car_btn').prop('disabled', false).text('Сохранить');
                    alert('Ошибка соединения с сервером');
                }
            });
        } else {
            jQuery('#car_modal_title').text('Создание нового автомобиля');
        }

        jQuery('#modal_car').modal('show');
    }

    jQuery(document).ready(function() {
		
        loadServicesList();

        // ====== ЛОГИКА КЛИЕНТ -> АВТО ======
        function refreshCarsForClient(userId, preselectCarId) {
            var carSelect = document.getElementById('select_car');
            if (!carSelect) return;

            if (!userId) {
                carSelect.innerHTML = '<option value="">-- Сначала выберите клиента --</option>';
                jQuery('#btn_create_car').prop('disabled', true);
                jQuery('#btn_car_details').prop('disabled', true);
                /* === АКТ ПРИЁМА === */
                jQuery('#btn_acceptance_act').prop('disabled', true);
                /* === /АКТ ПРИЁМА === */
                return;
            }

            jQuery('#btn_create_car').prop('disabled', false);

            jQuery.ajax({
                type: "POST",
                url: "/cp/content/car_service/ajax_get_user_cars.php",
                data: { user_id: userId },
                dataType: "json",
                success: function(cars) {
                    carSelect.innerHTML = '<option value="">-- Выберите автомобиль --</option>';
                    if (cars && cars.length) {
                        cars.forEach(function(car) {
                            var option = document.createElement('option');
                            option.value = car.id;
                            option.textContent = car.caption + ' (' + car.marka + ' ' + car.model + ')';
                            if (preselectCarId && parseInt(preselectCarId) === parseInt(car.id)) {
                                option.selected = true;
                            }
                            carSelect.appendChild(option);
                        });
                    } else {
                        carSelect.innerHTML = '<option value="">Нет автомобилей</option>';
                    }
                    // Обновляем состояние кнопок
                    jQuery('#btn_car_details').prop('disabled', !carSelect.value || readonly);
                    /* === АКТ ПРИЁМА === */
                    jQuery('#btn_acceptance_act').prop('disabled', !carSelect.value);
                    /* === /АКТ ПРИЁМА === */
                }
            });
        }

        jQuery('#select_client').on('change', function() {
            if (readonly) return;
            var userId = jQuery(this).val();
            refreshCarsForClient(userId, null);
        });

        jQuery('#select_car').on('change', function() {
            if (readonly) return;
            jQuery('#btn_car_details').prop('disabled', !jQuery(this).val());
            /* === АКТ ПРИЁМА === */
            jQuery('#btn_acceptance_act').prop('disabled', !jQuery(this).val());
            /* === /АКТ ПРИЁМА === */
        });

        document.getElementById('btn_add_service').addEventListener('click', addService);

        var displayEl = document.getElementById('time-record-display');
        if (displayEl) {
            displayEl.addEventListener('click', openCalendar);
        }

        var closeBtn = document.getElementById('calendar-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeCalendar);
        }

        var prevBtn = document.getElementById('prev-month');
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            });
        }
        var nextBtn = document.getElementById('next-month');
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            });
        }

        var postSelect = document.getElementById('select_post');
        if (postSelect) {
            postSelect.addEventListener('change', function() {
                if (readonly) return;
                var postVal = this.value;
                var displayEl2 = document.getElementById('time-record-display');
                if (postVal) {
                    isPostSelected = true;
                    if (displayEl2) displayEl2.classList.remove('disabled');
                    if (selectedDay) {
                        selectDate(selectedDay);
                    } else {
                        if (displayEl2) displayEl2.textContent = 'Выберите дату и время';
                        document.getElementById('selected_datetime').value = '';
                    }
                } else {
                    isPostSelected = false;
                    if (displayEl2) {
                        displayEl2.classList.add('disabled');
                        displayEl2.textContent = 'Сначала выберите пост';
                    }
                    document.getElementById('selected_datetime').value = '';
                    selectedDateTime = '';
                }
            });
        }

        if (!readonly) {
            var confirmBtn = document.getElementById('confirm_order-btn');
            if (confirmBtn) {
                jQuery(confirmBtn).on('click', confirm_order);
            }
        }

        loadExistingServices();

        if (isPostSelected && !readonly) {
            var displayEl3 = document.getElementById('time-record-display');
            if (displayEl3) displayEl3.classList.remove('disabled');
            if (selectedDateTime) {
                var parts = selectedDateTime.split(' ');
                if (parts.length >= 2) {
                    var dateParts = parts[0].split('-');
                    selectedDay = new Date(parseInt(dateParts[0]), parseInt(dateParts[1])-1, parseInt(dateParts[2]));
                    setTimeout(function() {
                        selectDate(selectedDay);
                    }, 300);
                }
            }
        } else if (readonly) {
            var displayEl4 = document.getElementById('time-record-display');
            if (displayEl4) displayEl4.classList.add('disabled');
        } else {
            var displayEl5 = document.getElementById('time-record-display');
            if (displayEl5) {
                displayEl5.classList.add('disabled');
                displayEl5.textContent = 'Сначала выберите пост';
            }
        }
		
		if (orderId > 0) {
			loadOrderProducts(orderId);
		}
		
		
		initSelect2(jQuery('#select_client'), 'Поиск клиента...', true);
		
		// ====== Инициализация: если клиент уже выбран при загрузке ======
		var initialClientId = jQuery('#select_client').val();
		if (initialClientId) {
			jQuery('#btn_create_car').prop('disabled', readonly);
			var initialCarId = '<?php echo (int)$selectedCar; ?>';
			refreshCarsForClient(initialClientId, initialCarId);
		}
		
		/* === АКТ ПРИЁМА: обработчики === */
		$('#btn_acceptance_act').on('click', function () {
			var carId = parseInt(jQuery('#select_car').val()) || 0;
			if (!carId) {
				alert('Сначала выберите автомобиль');
				return;
			}
			var recordId  = parseInt(document.getElementById('modal_record_id').value) || 0;
			var serviceId = parseInt(document.getElementById('modal_service_id').value) || 0;

			if (typeof window.CwAcceptanceAct === 'undefined') {
				alert('Модуль акта приёма не загружен. Проверьте подключение /cp/content/car_service/acceptance_act.js');
				return;
			}
			window.CwAcceptanceAct.open({
				carId:     carId,
				recordId:  recordId,
				serviceId: serviceId
			});
		});

		// Инициализация состояния кнопки при загрузке формы
		jQuery('#btn_acceptance_act').prop('disabled', !jQuery('#select_car').val());
		/* === /АКТ ПРИЁМА === */
		
		// Обработчик открытия модалки создания пользователя
		$('#btn_create_client').on('click', function() {
			if (readonly) return;
			$('#modal_create_user').modal('show');
			$('#create_user_form')[0].reset();
		});

		// Обработчик сохранения нового пользователя
		$('#save_new_user_btn').on('click', function() {
			var btn = $(this);
			var surname = $('#new_user_surname').val().trim();
			var name = $('#new_user_name').val().trim();
			var patronymic = $('#new_user_patronymic').val().trim();
			var phoneRaw = $('#new_user_phone').val().trim();
			var email = $('#new_user_email').val().trim();

			if (!surname || !name || !email) {
				alert('Заполните Фамилию, Имя и Email');
				return;
			}

			var phoneDigits = phoneRaw.replace(/\D/g, '');
			if (phoneDigits.length !== 11) {
				alert('Введите корректный номер телефона в формате +7 (XXX) XXX-XX-XX');
				return;
			}

			if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
				alert('Введите корректный email');
				return;
			}

			btn.prop('disabled', true).text('Создание...');

			$.ajax({
				type: "POST",
				url: "/cp/content/car_service/ajax_create_user.php",
				dataType: "json",
				data: {
					surname: surname,
					name: name,
					patronymic: patronymic,
					phone: phoneRaw,
					email: email
				},
				success: function(response) {
					btn.prop('disabled', false).text('Создать');
					if (response.status) {
						$('#modal_create_user').modal('hide');
						var select = $('#select_client');
						var newOption = new Option(response.display_text, response.user_id, true, true);
						select.append(newOption).trigger('change');
						if (select.data('select2')) {
							select.trigger('change');
						}
						jQuery('#btn_create_car').prop('disabled', false);
					} else {
						alert('Ошибка: ' + (response.message || 'Неизвестная ошибка'));
					}
				},
				error: function() {
					btn.prop('disabled', false).text('Создать');
					alert('Ошибка соединения с сервером');
				}
			});
		});
		
		
		// ====== ЛОГИКА СОЗДАНИЯ / РЕДАКТИРОВАНИЯ АВТО ======
		$('#btn_create_car').on('click', function() {
			if (readonly) return;
			var clientId = jQuery('#select_client').val();
			if (!clientId) {
				alert('Сначала выберите клиента');
				return;
			}
			openCarModal(0);
		});

		$('#btn_car_details').on('click', function() {
			if (readonly) return;
			var carId = jQuery('#select_car').val();
			if (!carId) {
				alert('Сначала выберите автомобиль');
				return;
			}
			openCarModal(carId);
		});

		// Сохранение авто
		$('#save_car_btn').on('click', function() {
			var btn = $(this);
			var carId = parseInt($('#car_id').val()) || 0;
			var userId = parseInt($('#car_user_id').val()) || parseInt(jQuery('#select_client').val()) || 0;

			var caption = $('#car_caption').val().trim();
			var marka = $('#car_marka').val().trim();
			var model = $('#car_model').val().trim();
			var year = $('#car_year').val();
			var body_type = $('#car_body_type').val();
			var engine_value = $('#car_engine_value').val().trim();
			var fuel_type = $('#car_fuel_type').val();
			var transmission = $('#car_transmission').val();
			var vin = $('#car_vin').val().trim();
			var frame = $('#car_frame').val().trim();
			var color = $('#car_color').val().trim();
			var country = $('#car_country').val().trim();
			var wheel = $('#car_wheel').val();
			var license_plate = $('#car_license_plate').val().trim();
			var note = $('#car_note').val().trim();

			if (!userId) { alert('Не выбран клиент'); return; }
			if (!caption) { alert('Укажите название автомобиля'); return; }
			if (!marka || !model) { alert('Укажите марку и модель'); return; }
			if (!vin) { alert('Укажите VIN'); return; }
			if (vin.length !== 17) { alert('VIN должен содержать 17 символов'); return; }

			btn.prop('disabled', true).text('Сохранение...');

			$.ajax({
				type: "POST",
				url: "/cp/content/car_service/ajax_create_car.php",
				dataType: "json",
				data: {
					action: 'save',
					car_id: carId,
					user_id: userId,
					caption: caption,
					marka: marka,
					model: model,
					year: year,
					body_type: body_type,
					engine_value: engine_value,
					fuel_type: fuel_type,
					transmission: transmission,
					vin: vin,
					frame: frame,
					color: color,
					country: country,
					wheel: wheel,
					license_plate: license_plate,
					note: note
				},
				success: function(response) {
					btn.prop('disabled', false).text('Сохранить');
					if (response.status) {
						$('#modal_car').modal('hide');
						var carSelect = document.getElementById('select_car');
						if (carId > 0) {
							for (var i = 0; i < carSelect.options.length; i++) {
								if (parseInt(carSelect.options[i].value) === parseInt(carId)) {
									carSelect.options[i].textContent = response.display_text;
									carSelect.selectedIndex = i;
									break;
								}
							}
						} else {
							if (carSelect.options.length === 1 && carSelect.options[0].value === '') {
								carSelect.innerHTML = '';
							} else if (carSelect.options.length === 1 && carSelect.options[0].textContent === 'Нет автомобилей') {
								carSelect.innerHTML = '';
							}
							var newOption = document.createElement('option');
							newOption.value = response.car_id;
							newOption.textContent = response.display_text;
							newOption.selected = true;
							carSelect.appendChild(newOption);
						}
						// Обновляем состояние кнопок
						jQuery('#btn_car_details').prop('disabled', !carSelect.value);
						/* === АКТ ПРИЁМА === */
						jQuery('#btn_acceptance_act').prop('disabled', !carSelect.value);
						/* === /АКТ ПРИЁМА === */
					} else {
						alert('Ошибка: ' + (response.message || 'Неизвестная ошибка'));
					}
				},
				error: function() {
					btn.prop('disabled', false).text('Сохранить');
					alert('Ошибка соединения с сервером');
				}
			});
		});
		
		
		// Инициализация маски для телефона
		$('#new_user_phone').mask('+7 (000) 000-00-00', {
			placeholder: '+7 (___) ___-__-__'
		});

		// При открытии модалки сбрасываем поля и маску
		$('#modal_create_user').on('shown.bs.modal', function () {
			$('#create_user_form')[0].reset();
			$('#new_user_phone').val('').trigger('input');
		});
		
		// Сброс полей при открытии модалки авто (на случай, если открываем вручную)
		$('#modal_car').on('shown.bs.modal', function () {
			generateYearOptions();
		});

		// ====== ПЕРЕЗАПИСЬ ======
		function getRewriteServiceId() {
			return document.getElementById('modal_service_id').value;
		}
		function getRewritePostId() {
			return document.getElementById('select_post').value;
		}

		function setMinDateForRewrite($input) {
			var today = new Date();
			var y = today.getFullYear();
			var m = String(today.getMonth() + 1).padStart(2, '0');
			var d = String(today.getDate()).padStart(2, '0');
			$input.attr('min', y + '-' + m + '-' + d);
		}

		function loadRewriteSlots(date, containerId, hiddenInputId) {
			var $container = jQuery('#' + containerId);
			var postId = getRewritePostId();
			var serviceId = getRewriteServiceId();

			$container.html('Загрузка...');
			jQuery('#' + hiddenInputId).val('');

			jQuery.ajax({
				type: "POST",
				url: "/cp/content/car_service/modules/calendar/Calendar.php",
				data: {
					action: 'get_slots',
					service_id: serviceId,
					date: date,
					post_id: postId
				},
				dataType: "json",
				success: function(answer) {
					var slots = (answer && answer.slots) ? answer.slots : [];
					$container.empty();
					if (!slots.length) {
						$container.html('<div>Нет свободных слотов</div>');
						return;
					}
					slots.forEach(function(slot) {
						var btn = document.createElement('button');
						btn.type = 'button';
						btn.className = 'btn-slot';
						btn.textContent = slot;
						btn.addEventListener('click', function() {
							$container.find('.btn-slot').css('background', '');
							btn.style.background = '#3B82F6';
							jQuery('#' + hiddenInputId).val(date + ' ' + slot);
						});
						$container.append(btn);
					});
				},
				error: function() {
					$container.html('<div>Ошибка загрузки слотов</div>');
				}
			});
		}

		// Открытие модалки «Перезапись»
		jQuery('#modal_rewrite').on('show.bs.modal', function() {
			jQuery('#rewrite_date').val('');
			jQuery('#rewrite_slots').html('Выберите дату');
			jQuery('#rewrite_selected_datetime').val('');
			jQuery('#rewrite_comment_input').val('');
			setMinDateForRewrite(jQuery('#rewrite_date'));
		});
		jQuery('#rewrite_date').on('change', function() {
			var d = jQuery(this).val();
			if (!d) return;
			loadRewriteSlots(d, 'rewrite_slots', 'rewrite_selected_datetime');
		});

		// Открытие модалки «Информация о перезаписи»
		jQuery('#modal_rewrite_info').on('show.bs.modal', function() {
			jQuery('#rewrite2_date').val('');
			jQuery('#rewrite2_slots').html('Выберите дату');
			jQuery('#rewrite2_selected_datetime').val('');
			jQuery('#rewrite2_comment_input').val('');
			setMinDateForRewrite(jQuery('#rewrite2_date'));
		});
		jQuery('#rewrite2_date').on('change', function() {
			var d = jQuery(this).val();
			if (!d) return;
			loadRewriteSlots(d, 'rewrite2_slots', 'rewrite2_selected_datetime');
		});

		// Сохранение перезаписи (общий обработчик)
		function saveRewrite(datetime, comment, $btn, originalText, modalSelector) {
			if (!datetime) { alert('Выберите дату и время'); return; }

			$btn.prop('disabled', true).text('Сохранение...');

			jQuery.ajax({
				type: "POST",
				url: "/cp/content/car_service/ajax_rewrite_record.php",
				dataType: "json",
				data: {
					record_id: document.getElementById('modal_record_id').value,
					service_id: document.getElementById('modal_service_id').value,
					post_id: getRewritePostId(),
					worker_id: document.getElementById('select_worker').value,
					datetime: datetime,
					comment: comment
				},
				success: function(answer) {
					if (answer && answer.status) {
						jQuery(modalSelector).modal('hide');
						if (typeof window.parent.cwLoadData === 'function') {
							window.parent.cwLoadData();
						} else if (typeof cwLoadData === 'function') {
							cwLoadData();
						} else {
							location.reload();
						}
						jQuery('#modal_create_order').modal('hide');
					} else {
						alert((answer && answer.message) || 'Ошибка сохранения');
						$btn.prop('disabled', false).text(originalText);
					}
				},
				error: function() {
					alert('Ошибка соединения с сервером');
					$btn.prop('disabled', false).text(originalText);
				}
			});
		}

		jQuery('#save_rewrite_btn').on('click', function() {
			saveRewrite(
				jQuery('#rewrite_selected_datetime').val(),
				jQuery('#rewrite_comment_input').val().trim(),
				jQuery(this),
				'Сохранить перезапись',
				'#modal_rewrite'
			);
		});

		jQuery('#save_rewrite2_btn').on('click', function() {
			saveRewrite(
				jQuery('#rewrite2_selected_datetime').val(),
				jQuery('#rewrite2_comment_input').val().trim(),
				jQuery(this),
				'Перезаписать снова',
				'#modal_rewrite_info'
			);
		});
		
		
		cwUpdateGrandTotal();
		
		jQuery('#cw_print_work_order_btn').on('click', function() {
			var recEl = document.getElementById('modal_record_id');
			var record_id = recEl ? parseInt(recEl.value) : 0;

			if (record_id > 0) {
				var baseUrl = '<?php echo $DP_Config->domain_path . $DP_Config->backend_dir; ?>';
				window.open(baseUrl + '/content/car_service/print.php?id=' + record_id, '_blank');
			} else {
				alert('Запись не создана.');
			}
		});

		/* === РЕКЛАМАЦИЯ: обработчик === */
		jQuery('#cw_reclamation_btn').on('click', function () {
			var recordId = parseInt(document.getElementById('modal_record_id').value) || 0;
			if (!recordId) {
				alert('Сначала сохраните запись');
				return;
			}

			// car_id берём из data-атрибута кнопки (заполняется PHP при рендере)
			var carId = parseInt(this.dataset.carId) || 0;
			if (!carId) {
				// fallback — пробуем вытащить из select_car (если значение ещё в DOM)
				carId = parseInt(jQuery('#select_car').val()) || 0;
			}
			if (!carId) {
				alert('Не удалось определить автомобиль по записи');
				return;
			}

			if (typeof window.CwReclamation === 'undefined') {
				alert('Модуль рекламации не загружен. Проверьте подключение /cp/content/car_service/reclamation.js');
				return;
			}

			window.CwReclamation.open({
				carId:    carId,
				recordId: recordId
			});
		});
		/* === /РЕКЛАМАЦИЯ === */
    });
	
	function loadOrderProducts(orderId) {
		if (!orderId) return;
		jQuery.ajax({
			type: "GET",
			url: "/content/carservice/ajax_get_order_products.php",
			data: { order_id: orderId },
			dataType: "json",
			success: function(response) {
				if (response.status) {
					renderProductsTable(response.items, response.total);
				} else {
					console.error('Ошибка загрузки товаров:', response.message);
				}
			},
			error: function() {
				console.error('Ошибка AJAX запроса');
			}
		});
	}

	function renderProductsTable(items, total) {
		var container = document.getElementById('products_table_container');
		if (!container) return;

		if (!items || items.length === 0) {
			container.innerHTML = '<p style="color: #A0A0B0;">В заказе пока нет товаров.</p>';
			productsTotal = 0;
			cwUpdateGrandTotal();
			return;
		}

		var CANCELLED = [6, 20];
		var realTotal = 0;

		var html = `
			<table id="products_table" class="table table-dark table-striped" style="color: #FFFFFF; background: transparent;">
				<thead>
					<tr>
						<th>Наименование</th>
						<th>Производитель</th>
						<th>Артикул</th>
						<th style="width:80px;">Кол-во</th>
						<th style="width:100px;">Цена</th>
						<th style="width:100px;">Сумма</th>
					</tr>
				</thead>
				<tbody>
		`;

		items.forEach(function(item) {
			var st = parseInt(item.status);
			var isCancelled = CANCELLED.indexOf(st) !== -1;

			var discount  = parseFloat(item.discount) || 0;
			var unitPrice = parseFloat(item.price) || 0;
			var unitFinal = unitPrice * (1 - discount / 100);
			var sum       = item.sum || (unitFinal * parseInt(item.count_need));

			if (!isCancelled) {
				realTotal += parseFloat(sum);
			}

			var rowStyle = isCancelled
				? ' style="opacity:0.45;text-decoration:line-through;"'
				: '';

			var fmt = function(v) {
				return Number(v).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
			};

			var priceHtml = (discount > 0)
				? '<s style="color:#6B7280;">' + fmt(unitPrice) + ' ₽</s> ' +
				  '<span style="color:#F59E0B;font-weight:600;">' + fmt(unitFinal) + ' ₽</span> ' +
				  '<span style="color:#EF4444;font-size:11px;">−' + discount.toFixed(0) + '%</span>'
				: fmt(unitPrice) + ' ₽';

			html += `
				<tr${rowStyle}>
					<td>${escapeHtml(item.t2_name || '—')}${isCancelled ? ' <span style="color:#EF4444;">(отменён)</span>' : ''}</td>
					<td>${escapeHtml(item.t2_manufacturer || '—')}</td>
					<td>${escapeHtml(item.t2_article || '—')}</td>
					<td data-label="Кол-во">${parseInt(item.count_need)}</td>
					<td data-label="Цена">${priceHtml}</td>
					<td data-label="Сумма">${fmt(sum)} ₽</td>
				</tr>
			`;
		});

		html += `
				</tbody>
			</table>
			<div style="margin-top: 10px; text-align: right; color: #A0A0B0; font-size: 14px;">
				Итого по заказу (без отменённых):
				<span style="color: #FFFFFF; font-weight: 600;">${realTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} ₽</span>
			</div>
		`;

		container.innerHTML = html;

		productsTotal = realTotal;
		cwUpdateGrandTotal();
	}

	function escapeHtml(unsafe) {
		if (!unsafe) return '—';
		return unsafe
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}
	
	
})();


</script>