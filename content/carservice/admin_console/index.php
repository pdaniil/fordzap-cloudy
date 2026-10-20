<?php
/**
 * Мобильная версия СТО
 * Версия 2.3 — режим сотрудника (staff mode) через sessionStorage
 */
$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 1;
$backendDir = '';
?>  

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Мобильный календарь СТО</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background-color: #1E1E2D; 
            color: #FFFFFF; 
            padding-bottom: 80px;
            overflow-x: hidden;
        }

        .cw_mobile_app { width: 100%; min-height: 100vh; position: relative; background: #1E1E2D; }

        /* --- Верхняя шапка --- */
        .cw_header {
            padding: 16px 20px 10px;
            background: #1E1E2D;
            border-bottom: 1px solid #2E2E42;
        }
        .cw_service_info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .cw_service_name { font-size: 18px; font-weight: 700; color: #FFFFFF; display: flex; flex-wrap: wrap; align-items: center; gap: 4px; }
        .cw_header_icons { display: flex; gap: 15px; font-size: 18px; color: #A0A0A0; }
        .cw_header_icons span { cursor: pointer; transition: 0.2s; }

        /* ===== STAFF MODE ===== */
        .cw_staff_bar {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; margin-bottom: 10px;
            background: #2B2B3B; border: 1px solid #3A3A50; border-radius: 10px;
        }
        .cw_staff_avatar {
            width: 36px; height: 36px; border-radius: 50%;
            object-fit: cover; border: 2px solid #3B82F6; flex-shrink: 0;
        }
        .cw_staff_initials {
            width: 36px; height: 36px; border-radius: 50%;
            background: #3B82F6; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px; flex-shrink: 0;
        }
        .cw_staff_name { flex: 1; font-weight: 600; font-size: 14px; color: #FFFFFF; }
        .cw_staff_logout {
            background: transparent; border: 1px solid #EF4444;
            color: #EF4444; border-radius: 8px; padding: 6px 12px;
            font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit;
            transition: 0.15s;
        }
        .cw_staff_logout:active { background: #EF4444; color: #fff; }
        /* ===== /STAFF MODE ===== */

        .cw_date_nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .cw_nav_btn {
            background: transparent; border: none; color: #A0A0A0; font-size: 20px; cursor: pointer;
            padding: 5px 10px; border-radius: 8px; transition: 0.2s;
        }
        .cw_nav_btn:active { background: #313146; }
        .cw_current_date { font-weight: 600; font-size: 16px; color: #FFFFFF; }

        .cw_tabs_container {
            display: flex;
            background: #2B2B3B;
            border-radius: 20px;
            padding: 3px;
            margin-top: 4px;
        }
        .cw_tab_btn {
            flex: 1;
            padding: 8px 0;
            border: none;
            background: transparent;
            color: #A0A0B0;
            border-radius: 18px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cw_tab_btn.cw_tab_active {
            background: #3A3A50;
            color: #FFFFFF;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        /* --- Панель настроек подсветки --- */
        .cw_settings_overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.55);
            z-index: 1500;
        }
        .cw_settings_panel {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 1600;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 15px;
            pointer-events: none;
        }
        .cw_settings_panel.cw_open { display: flex; }
        .cw_settings_content {
            background: #2B2B3B;
            border-radius: 16px;
            padding: 20px;
            width: 100%;
            max-width: 400px;
            border: 1px solid #3A3A50;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            pointer-events: auto;
            max-height: 85vh;
            overflow-y: auto;
        }
        .cw_settings_title {
            font-size: 16px;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 16px;
            text-align: center;
        }
        .cw_settings_section { margin-bottom: 16px; }
        .cw_settings_label {
            font-size: 13px;
            color: #A0A0B0;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .cw_status_btn {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 8px;
            background: #1E1E2D;
            border: 2px solid #3A3A50;
            border-radius: 10px;
            color: #D1D5DB;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }
        .cw_status_btn:hover { border-color: #4B5563; }
        .cw_status_btn .cw_status_dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .cw_status_all .cw_status_dot { background: #A0A0B0; }
        .cw_status_overdue .cw_status_dot { background: #EF4444; }
        .cw_status_active .cw_status_dot { background: #3B82F6; }
        .cw_status_completed .cw_status_dot { background: #10B981; }

        .cw_status_btn.cw_status_selected { color: #FFFFFF; }
        .cw_status_all.cw_status_selected {
            border-color: #A0A0B0; background: #313146;
            box-shadow: 0 0 0 2px rgba(160,160,176,0.2);
        }
        .cw_status_overdue.cw_status_selected {
            border-color: #EF4444; background: #2B1C1C;
            box-shadow: 0 0 0 2px rgba(239,68,68,0.2);
        }
        .cw_status_active.cw_status_selected {
            border-color: #3B82F6; background: #1E2A3A;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
        }
        .cw_status_completed.cw_status_selected {
            border-color: #10B981; background: #1C2B1C;
            box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
        }
        .cw_settings_select {
            width: 100%;
            padding: 12px 14px;
            background: #1E1E2D;
            border: 1px solid #3A3A50;
            border-radius: 10px;
            color: #FFFFFF;
            font-size: 14px;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23A0A0B0' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }
        .cw_settings_select:focus { border-color: #3B82F6; }
        .cw_settings_actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 20px;
        }
        .cw_settings_apply {
            padding: 12px;
            background: #3B82F6;
            border: none;
            border-radius: 10px;
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .cw_settings_apply:hover { background: #2563eb; }
        .cw_settings_reset {
            padding: 12px;
            background: #4B5563;
            border: none;
            border-radius: 10px;
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .cw_settings_reset:hover { background: #374151; }
        .cw_settings_active_info {
            display: inline-block;
            padding: 4px 10px;
            background: #1E1E2D;
            border: 1px solid #3A3A50;
            border-radius: 12px;
            font-size: 11px;
            color: #A0A0B0;
            margin-left: 2px;
        }

        /* --- Выбор постов (для Дня) --- */
        .cw_posts_mobile {
            display: none;
            padding: 10px 15px;
            overflow-x: auto;
            white-space: nowrap;
            gap: 10px;
            background: #1E1E2D;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .cw_posts_mobile::-webkit-scrollbar { display: none; }
        .cw_post_chip {
            display: inline-block;
            background: #2B2B3B;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            color: #A0A0B0;
            border: 1px solid #3A3A50;
            cursor: pointer;
            transition: 0.2s;
            margin-right: 8px;
        }
        .cw_post_chip:active { opacity: 0.7; }
        .cw_post_chip.cw_active {
            background: #3B82F6;
            color: #FFFFFF;
            border-color: #3B82F6;
        }
        .cw_post_chip.cw_unconfirmed_chip { border-color: #f97316; color: #f97316; }
        .cw_post_chip.cw_unconfirmed_chip.cw_active { background: #f97316; color: #FFFFFF; }

        /* --- Календарь (Месяц) --- */
        .cw_month_grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            padding: 10px;
        }
        .cw_month_cell {
            background: transparent;
            min-height: 60px;
            padding: 6px;
            border-radius: 8px;
            text-align: center;
            transition: 0.2s;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .cw_month_cell.cw_today { background: #313146; }
        .cw_month_cell.cw_overdue { border-color: #EF4444 !important; background: #2B1C1C; }
        .cw_month_cell.cw_highlight_active { border-color: #3B82F6 !important; background: #1E2A3A; }
        .cw_month_cell.cw_highlight_completed { border-color: #10B981 !important; background: #1C2B1C; }
        .cw_month_cell.cw_has_unconfirmed { border-color: #f97316 !important; }
        .cw_month_day_num { font-size: 16px; font-weight: 500; color: #FFFFFF; margin-bottom: 4px; }
        .cw_month_day_content { font-size: 11px; color: #A0A0B0; }
        .cw_unconfirmed_badge { color: #f97316; font-weight: 600; }

        /* --- Календарь (Неделя - Сетка) --- */
        .cw_week_grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding: 10px;
        }
        .cw_week_day {
            background: #2B2B3B;
            border-radius: 12px;
            padding: 12px;
            border: 1px solid transparent;
            cursor: pointer;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .cw_week_day.cw_today { border-color: #3B82F6; background: #222236; }
        .cw_week_day.cw_overdue { border-color: #EF4444 !important; background: #2B1C1C; }
        .cw_week_day.cw_highlight_active { border-color: #3B82F6 !important; background: #1E2A3A; }
        .cw_week_day.cw_highlight_completed { border-color: #10B981 !important; background: #1C2B1C; }
        .cw_week_day.cw_has_unconfirmed { border-color: #f97316 !important; }
        .cw_day_header { display: flex; flex-direction: column; align-items: center; margin-bottom: 8px; }
        .cw_day_name { font-size: 13px; color: #A0A0B0; }
        .cw_day_date { font-size: 20px; font-weight: 600; color: #FFFFFF; }
        .cw_day_stats { text-align: center; color: #D1D5DB; }
        .cw_day_records { color: #60A5FA; font-weight: 500; font-size: 14px; }
        .cw_day_nonworking { color: #6B7280; text-align: center; font-size: 13px; }

        /* --- Календарь (День) --- */
        .cw_day_grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            padding: 10px 15px 20px;
        }
        .cw_slot_item {
            background: #2B2B3B;
            border-radius: 10px;
            padding: 10px 4px;
            min-height: 75px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid #3A3A50;
            cursor: pointer;
            transition: 0.2s;
            text-align: center;
            word-break: break-word;
        }
        .cw_slot_item:active { transform: scale(0.95); background: #3A3A50; }
        .cw_slot_item.cw_has_record { background: #1E2A3A; border-color: #3B82F6; }
        .cw_slot_item.cw_has_record.cw_overdue { border-color: #EF4444; background: #2B1C1C; }
        .cw_slot_item.cw_has_record.cw_completed { border-color: #10B981; background: #1C2B1C; }
        .cw_slot_item.cw_unconfirmed { border-color: #f97316 !important; background: #3D2A1A; }
        .cw_slot_item.cw_unconfirmed .cw_slot_car { color: #f97316; font-weight: 600; }

        .cw_slot_time { font-size: 14px; font-weight: 700; color: #FFFFFF; margin-bottom: 4px; }
        .cw_slot_free { font-size: 12px; color: #A0A0B0; }
        .cw_slot_car { font-size: 11px; color: #9CA3AF; line-height: 1.2; }

        .cw_back_btn {
            background: transparent;
            border: none;
            color: #3B82F6;
            font-size: 18px;
            padding: 10px 0 15px;
            display: block;
            cursor: pointer;
            font-weight: 600;
        }
        .cw_back_btn:active { opacity: 0.6; }

        #cw_stats_tab_container {
            padding: 15px;
            display: none;
        }
        .cw_stats_grid_mobile {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .cw_stat_card {
            background: #2B2B3B;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #3A3A50;
        }
        .cw_stat_label { font-size: 13px; color: #A0A0B0; margin-bottom: 4px; }
        .cw_stat_value { font-size: 24px; font-weight: 600; color: #FFFFFF; }
        .cw_stat_sub { font-size: 12px; color: #A0A0B0; margin-top: 2px; }

        .cw_mobile_nav {
            position: fixed; bottom: 0; left: 0; width: 100%;
            background: #1E1E2D; border-top: 1px solid #2E2E42;
            display: flex; justify-content: space-around;
            padding: 8px 0 15px; z-index: 999;
        }
        .cw_mobile_nav_item {
            display: flex; flex-direction: column; align-items: center;
            color: #6B7280; font-size: 11px; cursor: pointer; transition: 0.2s;
            border: none; background: transparent; width: 50%; padding-top: 4px;
        }
        .cw_mobile_nav_item span.icon { font-size: 22px; margin-bottom: 3px; line-height: 1; }
        .cw_mobile_nav_item.active { color: #F59E0B; }

        .modal-content { background: #2B2B3B; color: #FFFFFF; border: none; border-radius: 16px; }
        .modal-header { border-bottom: 1px solid #3A3A50; }
        .modal-header .close { color: #A0A0B0; opacity: 1; text-shadow: none; }
        .cw_loading { padding: 40px; text-align: center; color: #A0A0B0; }

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

/* --- Список отфильтрованных записей --- */
.cw_filtered_records {
    padding: 0 10px 30px;
    margin-top: 6px;
}
.cw_filtered_records:empty { display: none; }

.cw-filtered-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    font-weight: 700;
    color: #FFFFFF;
    padding: 14px 6px 10px;
    border-top: 1px solid #2E2E42;
    margin-top: 8px;
}
.cw-filtered-header .cw-filtered-total {
    font-size: 14px;
    font-weight: 700;
    color: #F59E0B;
}

.cw-filtered-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.cw-fl-row {
    display: grid;
    grid-template-columns: 100px minmax(160px, 1.3fr) minmax(140px, 1fr)
                           minmax(120px, 1fr) 110px 100px;
    gap: 10px;
    align-items: center;
    padding: 10px 12px;
    background: #2B2B3B;
    border: 1px solid #3A3A50;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.15s;
    font-size: 13px;
    color: #D1D5DB;
}
.cw-fl-row:hover {
    background: #34344A;
    border-color: #4B4B66;
    transform: translateX(2px);
}
.cw-fl-row.cw-fl-overdue     { border-left: 4px solid #EF4444; }
.cw-fl-row.cw-fl-completed   { border-left: 4px solid #10B981; }
.cw-fl-row.cw-fl-future      { border-left: 4px solid #3B82F6; }
.cw-fl-row.cw-fl-unconfirmed {
    border-left: 4px solid #f97316;
    background: #3D2A1A;
}

.cw-fl-time {
    font-weight: 700;
    color: #FFFFFF;
    white-space: nowrap;
}
.cw-fl-date {
    display: block;
    font-size: 11px;
    color: #A0A0B0;
    font-weight: 500;
    margin-top: 1px;
}

.cw-fl-car strong { color: #FFFFFF; font-weight: 600; }
.cw-fl-vin {
    font-family: ui-monospace, Menlo, Consolas, monospace;
    font-size: 11px;
    color: #6B7280;
}

.cw-fl-client { overflow: hidden; text-overflow: ellipsis; }
.cw-fl-phone {
    display: block;
    font-size: 11px;
    color: #6B7280;
    margin-top: 1px;
}

.cw-fl-worker {
    font-size: 12px;
    color: #A0A0B0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cw-fl-status {
    font-size: 11px;
    font-weight: 600;
    text-align: center;
    padding: 3px 8px;
    border-radius: 10px;
    white-space: nowrap;
}
.cw-fl-status.overdue      { color: #EF4444; background: rgba(239,68,68,0.12); }
.cw-fl-status.completed    { color: #10B981; background: rgba(16,185,129,0.12); }
.cw-fl-status.future       { color: #3B82F6; background: rgba(59,130,246,0.12); }
.cw-fl-status.unconfirmed  { color: #f97316; background: rgba(249,115,22,0.12); }

.cw-fl-sum {
    text-align: right;
    font-weight: 700;
    color: #F59E0B;
    white-space: nowrap;
}

.cw-fl-services {
    grid-column: 1 / -1;
    font-size: 12px;
    color: #6B7280;
    padding-top: 6px;
    margin-top: 4px;
    border-top: 1px dashed #3A3A50;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cw-filtered-empty {
    text-align: center;
    color: #6B7280;
    padding: 22px 10px;
    font-size: 13px;
}

@media (max-width: 900px) {
    .cw-fl-row {
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }
    .cw-fl-services { grid-column: 1 / -1; }
    .cw-fl-worker   { grid-column: 1 / -1; }
    .cw-fl-sum      { text-align: left; }
}			
        @media (max-width: 767px) {
            #services_table thead { display: none; }
            #services_table, #services_table tbody, #services_table tr, #services_table td { display: block; width: 100%; box-sizing: border-box; }
            #services_table tr { margin-bottom: 15px; border: 1px solid #3A3A50; border-radius: 10px; padding: 12px; background: #2B2B3B; }
            #services_table td { border: none; padding: 4px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
            #services_table td:first-child { font-weight: 700; font-size: 16px; border-bottom: 1px solid #3A3A50; margin-bottom: 6px; padding-bottom: 6px; }
            #services_table td:not(:first-child)::before { content: attr(data-label); font-weight: 600; color: #A0A0B0; margin-right: 10px; flex-shrink: 0; }
            #services_table td:last-child { justify-content: flex-end; }
            #services_table td:last-child button { margin-left: auto; }
            #services_table input.service-count, #services_table input.service-hours { width: 50px !important; font-size: 13px; padding: 2px 4px; }
            #services_table td .service-total { white-space: nowrap; }

            #products_table thead { display: none; }
            #products_table, #products_table tbody, #products_table tr, #products_table td { display: block; width: 100%; box-sizing: border-box; }
            #products_table tr { margin-bottom: 15px; border: 1px solid #3A3A50; border-radius: 10px; padding: 12px; background: #2B2B3B; }
            #products_table td { border: none; padding: 4px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
            #products_table td:first-child { font-weight: 700; font-size: 16px; border-bottom: 1px solid #3A3A50; margin-bottom: 6px; padding-bottom: 6px; }
            #products_table td:not(:first-child)::before { content: attr(data-label); font-weight: 600; color: #A0A0B0; margin-right: 10px; flex-shrink: 0; }

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
		
		.table-striped tbody tr {
			background-color: rgba(0, 0, 0, .05) !important;
		}
		.table .table-condensed {
			background-color: rgba(0, 0, 0, .05) !important;
		}
    </style>  
</head>
<body>

<div class="cw_mobile_app" id="cw_calendar_<?php echo $serviceId; ?>">
    <!-- Оверлей и панель настроек -->
    <div class="cw_settings_overlay" id="cw_settings_overlay" onclick="cwToggleSettings()"></div>
    <div class="cw_settings_panel" id="cw_settings_panel">
        <div class="cw_settings_content">
            <div class="cw_settings_title">⚙️ Настройки подсветки</div>

            <div class="cw_settings_section">
                <div class="cw_settings_label">Статус (подсветка / фильтр)</div>
                <button type="button" class="cw_status_btn cw_status_all" data-status="all" onclick="cwSelectStatusFilter('all')">
                    <span class="cw_status_dot"></span> Все записи
                </button>
                <button type="button" class="cw_status_btn cw_status_overdue" data-status="overdue" onclick="cwSelectStatusFilter('overdue')">
                    <span class="cw_status_dot"></span> Просрочена
                </button>
                <button type="button" class="cw_status_btn cw_status_active" data-status="active" onclick="cwSelectStatusFilter('active')">
                    <span class="cw_status_dot"></span> Активна
                </button>
                <button type="button" class="cw_status_btn cw_status_completed" data-status="completed" onclick="cwSelectStatusFilter('completed')">
                    <span class="cw_status_dot"></span> Выполнена
                </button>
            </div>

            <div class="cw_settings_section">
                <div class="cw_settings_label">Мастер</div>
                <select id="cw_filter_worker_select" class="cw_settings_select">
                    <option value="">Выберите мастера</option>
                </select>
            </div>

            <div class="cw_settings_actions">
                <button type="button" class="cw_settings_apply" onclick="cwApplyFilters()">Отфильтровать</button>
                <button type="button" class="cw_settings_reset" onclick="cwResetFilters()">Сбросить фильтры</button>
            </div>
        </div>
    </div>

    <!-- Шапка -->
    <div class="cw_header"></div>
    
    <!-- Переключатель постов (только для дня) -->
    <div class="cw_posts_mobile"></div>
    
    <!-- Основной контент (Календарь) -->
    <div class="cw_calendar_container"></div>
	<div id="cw_filtered_records" class="cw_filtered_records"></div>
    <!-- Контейнер для детального просмотра -->
    <div id="cw_detail_view" style="display: none; padding: 15px;"></div>

    <!-- Отдельный блок Статистики -->
    <div id="cw_stats_tab_container"></div>

    <!-- Нижняя навигация -->
    <div class="cw_mobile_nav">
        <button class="cw_mobile_nav_item active" data-view="calendar"><span class="icon">📅</span>Календарь</button>
        <button class="cw_mobile_nav_item" data-view="stats"><span class="icon">📊</span>Статистика</button>
    </div>
</div>

<!-- Модальное окно -->
<div class="modal fade" id="modal_create_order" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Новая запись</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<script>
    const CW_API_URL = 'https://fordzap.com/cp/content/car_service/modules/calendar/Calendar.php';
    const CW_BACKEND_DIR = ''; 
    const is_admin_console = true;
    const CWState = {
        serviceId: <?php echo $serviceId; ?>,
        view: 'day',
        currentDate: new Date().toISOString().split('T')[0],
        selectedPost: null,
        data: null,
        filterStatus: 'all',   // 'all' | 'overdue' | 'active' | 'completed'
        filterWorkerId: '',
        staff: null            // ===== STAFF MODE =====
    };

    /* ===== STAFF MODE — инициализация до DOMContentLoaded ===== */
    (function cwInitStaffMode() {
        try {
            var raw = sessionStorage.getItem('cw_staff');
            if (!raw) return;

            var staff = JSON.parse(raw);
            var TTL = 12 * 60 * 60 * 1000; // 12 часов

            if (!staff || !staff.id || !staff.ts || (Date.now() - staff.ts > TTL)) {
                sessionStorage.removeItem('cw_staff');
                return;
            }

            CWState.staff = staff;

            // Если хотим автоматически фильтровать всё по этому мастеру — раскомментировать:
            // CWState.filterWorkerId = String(staff.id);
        } catch (e) {
            try { sessionStorage.removeItem('cw_staff'); } catch (ignore) {}
        }
    })();
    /* ===== /STAFF MODE ===== */

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const recordId = urlParams.get('record_id');
        const tab = urlParams.get('tab');
        if (recordId && tab === 'products') {
            const cleanUrl = window.location.pathname + window.location.search.replace(/[?&]record_id=[^&]*/, '').replace(/[?&]tab=[^&]*/, '');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, cleanUrl);
            }
            setTimeout(function() {
                cwEditRecord(parseInt(recordId), 'products');
            }, 300);
        }

        var calendarContainer = document.querySelector('.cw_calendar_container');
        if (calendarContainer) calendarContainer.style.display = 'block';
        document.getElementById('cw_stats_tab_container').style.display = 'none';
        cwLoadData();

        document.querySelectorAll('.cw_mobile_nav_item').forEach(function(item) {
            item.addEventListener('click', function() {
                document.querySelectorAll('.cw_mobile_nav_item').forEach(function(i) {
                    i.classList.remove('active');
                });
                this.classList.add('active');
                var view = this.dataset.view;
                
                if (view === 'calendar') {
                    var detailView = document.getElementById('cw_detail_view');
                    if (detailView && detailView.style.display !== 'none') {
                        detailView.style.display = 'none';
                        var calendarContainer2 = document.querySelector('.cw_calendar_container');
                        if (calendarContainer2) calendarContainer2.style.display = 'block';
                    }
                    var calendarContainer3 = document.querySelector('.cw_calendar_container');
                    if (calendarContainer3) calendarContainer3.style.display = 'block';
                    document.getElementById('cw_stats_tab_container').style.display = 'none';
                    if (CWState.data) cwRenderCalendar(CWState.data);
                    else cwLoadData();
                } else if (view === 'stats') {
                    var calendarContainer4 = document.querySelector('.cw_calendar_container');
                    if (calendarContainer4) calendarContainer4.style.display = 'none';
                    document.getElementById('cw_stats_tab_container').style.display = 'block';
                    if (CWState.data) cwRenderStatsTab();
                    else cwLoadData();
                }
            });
        });
    });

    // ---------- НАСТРОЙКИ ПОДСВЕТКИ / ФИЛЬТРЫ ----------
    function cwToggleSettings() {
        var panel = document.getElementById('cw_settings_panel');
        var overlay = document.getElementById('cw_settings_overlay');
        if (!panel) return;
        if (panel.classList.contains('cw_open')) {
            panel.classList.remove('cw_open');
            panel.style.display = 'none';
            if (overlay) overlay.style.display = 'none';
        } else {
            cwPopulateSettings();
            panel.classList.add('cw_open');
            panel.style.display = 'flex';
            if (overlay) overlay.style.display = 'block';
        }
    }
	
	function cwRenderFilteredList(data) {
		var container = document.getElementById('cw_filtered_records');
		if (!container) return;

		var list = data.filtered_records_list;

		if (!list || !list.length) {
			container.innerHTML = '';
			return;
		}

		var totalSum = 0;
		list.forEach(function(r) { totalSum += parseFloat(r.total_sum) || 0; });

		var view = data.view;
		var periodLabel = view === 'month' ? 'месяц'
						: (view === 'week' ? 'неделю' : 'день');

		var html = '<div class="cw-filtered-header">' +
					   '<span>📋 Отфильтрованные записи за ' + periodLabel +
						   ' — <span style="color:#A0A0B0;font-weight:500;">' + list.length + '</span></span>' +
					   '<span class="cw-filtered-total">' +
						   totalSum.toLocaleString('ru-RU', {maximumFractionDigits: 0}) + ' ₽' +
					   '</span>' +
				   '</div>';

		html += '<div class="cw-filtered-list">';

		list.forEach(function(rec) {
			var statusClass = rec.is_unconfirmed ? 'unconfirmed' : rec.status;
			var statusLabel = rec.is_unconfirmed ? '⚠️ Не подтв.'
							: (rec.status === 'completed' ? '✅ Выполнена'
							: (rec.status === 'overdue'  ? '🔴 Просрочена'
														 : '🔵 Активна'));

			var dp = rec.date.split('-');
			var dateDisplay = dp[2] + '.' + dp[1] + '.' + dp[0];

			var vin   = rec.vin   ? '<span class="cw-fl-vin">VIN: ' + rec.vin + '</span>' : '';
			var phone = rec.client_phone
				? '<span class="cw-fl-phone">📞 ' + rec.client_phone + '</span>'
				: '';

			var servicesStr = (rec.services && rec.services.length)
				? '🛠️ ' + rec.services.join(', ')
				: '';

			html += '<div class="cw-fl-row cw-fl-' + statusClass + '" ' +
						 'onclick="cwEditRecord(' + rec.id + ')">' +
						'<div class="cw-fl-time">' + rec.time +
							'<span class="cw-fl-date">' + dateDisplay + '</span>' +
						'</div>' +
						'<div class="cw-fl-car">' +
							'<strong>' + (rec.car_info || '—') + '</strong>' +
							(vin ? '<br>' + vin : '') +
						'</div>' +
						'<div class="cw-fl-client">' +
							(rec.client_name || '—') +
							(phone ? '<br>' + phone : '') +
						'</div>' +
						'<div class="cw-fl-worker">👨‍🔧 ' + (rec.worker_name || '—') + '</div>' +
						'<div class="cw-fl-status ' + statusClass + '">' + statusLabel + '</div>' +
						'<div class="cw-fl-sum">' +
							(parseFloat(rec.total_sum) || 0)
								.toLocaleString('ru-RU', {maximumFractionDigits: 0}) + ' ₽' +
						'</div>' +
						(servicesStr
							? '<div class="cw-fl-services">' + servicesStr + '</div>'
							: '') +
					'</div>';
		});

		html += '</div>';
		container.innerHTML = html;
	}
	
    function cwPopulateSettings() {
        var select = document.getElementById('cw_filter_worker_select');
        if (select && CWState.data && CWState.data.workers) {
            var workers = CWState.data.workers;
            var currentVal = CWState.filterWorkerId || '';
            var html = '<option value="">Выберите мастера</option>';
            workers.forEach(function(w) {
                var selected = (String(w.id) === String(currentVal)) ? ' selected' : '';
                html += '<option value="' + w.id + '"' + selected + '>' + w.fio + '</option>';
            });
            select.innerHTML = html;
        }
        cwUpdateStatusButtons();
    }

    function cwUpdateStatusButtons() {
        document.querySelectorAll('.cw_status_btn').forEach(function(btn) {
            if (btn.dataset.status === CWState.filterStatus) {
                btn.classList.add('cw_status_selected');
            } else {
                btn.classList.remove('cw_status_selected');
            }
        });
    }

    function cwSelectStatusFilter(status) {
        CWState.filterStatus = status;
        cwUpdateStatusButtons();
    }

    function cwApplyFilters() {
        var select = document.getElementById('cw_filter_worker_select');
        CWState.filterWorkerId = select ? select.value : '';
        cwToggleSettings();
        cwLoadData();
    }

    function cwResetFilters() {
        CWState.filterStatus = 'all';
        CWState.filterWorkerId = '';
        var select = document.getElementById('cw_filter_worker_select');
        if (select) select.value = '';
        cwUpdateStatusButtons();
        cwToggleSettings();
        cwLoadData();
    }

    /* ===== STAFF MODE — выход ===== */
    function cwStaffLogout() {
        try { sessionStorage.removeItem('cw_staff'); } catch (e) {}
        var url = 'staff_select.php';
        if (CWState.serviceId) url += '?service_id=' + encodeURIComponent(CWState.serviceId);
        window.location.href = url;
    }

    function cwStaffInitials(fio) {
        var p = String(fio || '').trim().split(/\s+/);
        var out = '';
        if (p[0]) out += p[0].charAt(0).toUpperCase();
        if (p[1]) out += p[1].charAt(0).toUpperCase();
        return out || '?';
    }
    /* ===== /STAFF MODE ===== */

    // ---------- ЗАГРУЗКА ----------
    function cwLoadData() {
        var detailView = document.getElementById('cw_detail_view');
        if (detailView && detailView.style.display !== 'none') {
            detailView.style.display = 'none';
            var calendarContainer = document.querySelector('.cw_calendar_container');
            if (calendarContainer) calendarContainer.style.display = 'block';
        }

        var container = document.querySelector('.cw_calendar_container');
        if (!container) return;
        var activeTab = document.querySelector('.cw_mobile_nav_item.active')?.dataset.view || 'calendar';
        
        if (activeTab === 'calendar') container.innerHTML = '<div class="cw_loading">Загрузка...</div>';
        else document.getElementById('cw_stats_tab_container').innerHTML = '<div class="cw_loading">Загрузка...</div>';
        
        var params = new URLSearchParams({
            action: 'get_calendar', 
            service_id: CWState.serviceId,
            view: CWState.view, 
            date: CWState.currentDate, 
            post_id: CWState.selectedPost || '',
            filter_status: CWState.filterStatus || 'all',
            filter_worker_id: CWState.filterWorkerId || ''
        });
        
        fetch(CW_API_URL + '?' + params.toString())
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) { 
                    if (activeTab === 'calendar') container.innerHTML = '<div style="color:#EF4444; padding:20px;">' + data.error + '</div>';
                    else document.getElementById('cw_stats_tab_container').innerHTML = '<div style="color:#EF4444; padding:20px;">' + data.error + '</div>';
                    return; 
                }
                CWState.data = data; 

                if (CWState.view === 'day' && !CWState.selectedPost && data.posts && data.posts.length > 0) {
                    CWState.selectedPost = parseInt(data.posts[0].id, 10); 
                    cwLoadData(); return;
                }
                
                cwRenderHeader(data);
                
                var currentTab = document.querySelector('.cw_mobile_nav_item.active')?.dataset.view || 'calendar';
                if (currentTab === 'calendar') {
                    cwRenderCalendar(data);
                } else if (currentTab === 'stats') {
                    cwRenderStatsTab();
                }
            })
            .catch(function(error) { 
                if (activeTab === 'calendar') container.innerHTML = '<div style="color:#EF4444; padding:20px;">Ошибка соединения</div>';
                else document.getElementById('cw_stats_tab_container').innerHTML = '<div style="color:#EF4444; padding:20px;">Ошибка соединения</div>';
            });
    }

    // ---------- РЕНДЕРИНГ ----------
    function cwRenderHeader(data) {
        var header = document.querySelector('.cw_header');
        var service = data.service || {};
        var dateStr = cwFormatCurrentDate(data);
        var view = data.view;

        var filterHint = '';
        var statusLabel = '';
        if (CWState.filterStatus === 'overdue') statusLabel = '🔴 Просрочена';
        else if (CWState.filterStatus === 'active') statusLabel = '🔵 Активна';
        else if (CWState.filterStatus === 'completed') statusLabel = '🟢 Выполнена';
        if (statusLabel) {
            filterHint += '<span class="cw_settings_active_info">' + statusLabel + '</span>';
        }
        if (CWState.filterWorkerId) {
            var wName = '';
            if (data.workers) {
                data.workers.forEach(function(w){
                    if (String(w.id) === String(CWState.filterWorkerId)) wName = w.fio;
                });
            }
            if (wName) filterHint += '<span class="cw_settings_active_info">👨‍🔧 ' + wName + '</span>';
        }

        /* ===== STAFF MODE — панель авторизации ===== */
        var staffBarHtml = '';
        if (CWState.staff) {
            var s = CWState.staff;
            var avatar;
            if (s.image) {
                avatar = '<img class="cw_staff_avatar" src="' + s.image + '" alt="" ' +
                         'onerror="this.outerHTML=\'<span class=&quot;cw_staff_initials&quot;>' +
                         cwStaffInitials(s.fio) + '</span>\'">';
            } else {
                avatar = '<span class="cw_staff_initials">' + cwStaffInitials(s.fio) + '</span>';
            }
            staffBarHtml =
                '<div class="cw_staff_bar">' +
                    avatar +
                    '<span class="cw_staff_name">' + s.fio + '</span>' +
                    '<button type="button" class="cw_staff_logout" onclick="cwStaffLogout()">Выйти</button>' +
                '</div>';
        }
        /* ===== /STAFF MODE ===== */

        header.innerHTML = `
            ${staffBarHtml}
            <div class="cw_service_info">
                <div class="cw_service_name">${service.caption || 'Моя работа'}${filterHint}</div>
                <div class="cw_header_icons"><span onclick="cwToggleSettings()" title="Настройки подсветки">⚙️</span></div>
            </div>
            <div class="cw_date_nav">
                <button class="cw_nav_btn" onclick="cwNavigate('prev')">◀</button>
                <span class="cw_current_date">${dateStr}</span>
                <button class="cw_nav_btn" onclick="cwNavigate('next')">▶</button>
            </div>
            <div class="cw_tabs_container">
                <button class="cw_tab_btn ${view === 'day' ? 'cw_tab_active' : ''}" onclick="cwSetView('day')">День</button>
                <button class="cw_tab_btn ${view === 'week' ? 'cw_tab_active' : ''}" onclick="cwSetView('week')">Неделя</button>
                <button class="cw_tab_btn ${view === 'month' ? 'cw_tab_active' : ''}" onclick="cwSetView('month')">Месяц</button>
            </div>
        `;
    }

    function cwFormatCurrentDate(data) {
        var d = new Date(data.current_date + 'T12:00:00');
        switch (data.view) {
            case 'month': return d.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' });
            case 'week':
                if (data.data.week_start && data.data.week_end) {
                    var start = new Date(data.data.week_start + 'T12:00:00');
                    var end = new Date(data.data.week_end + 'T12:00:00');
                    return start.getDate() + ' - ' + end.getDate() + ' ' + end.toLocaleDateString('ru-RU', { month: 'short' });
                }
                return 'Неделя';
            case 'day': return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
            default: return '';
        }
    }

    function cwRenderCalendar(data) {
        var container = document.querySelector('.cw_calendar_container');
        var postsBar = document.querySelector('.cw_posts_mobile');
        if (data.view === 'day' && data.posts && data.posts.length > 0) {
            postsBar.style.display = 'block';
            cwRenderPostsMobile(data);
        } else {
            postsBar.style.display = 'none';
        }

        switch (data.view) {
            case 'month': container.innerHTML = cwRenderMonth(data.data); break;
            case 'week': container.innerHTML = cwRenderWeek(data.data); break;
            case 'day': container.innerHTML = cwRenderDay(data.data); break;
            default: container.innerHTML = '';
        }
		cwRenderFilteredList(data);
    }

    function cwGetHighlightClass(dayData) {
        if (!dayData) return '';
        var fs = CWState.filterStatus || 'all';
        if (fs === 'active') {
            return (dayData.active_count > 0) ? ' cw_highlight_active' : '';
        }
        if (fs === 'completed') {
            return (dayData.completed_count > 0) ? ' cw_highlight_completed' : '';
        }
        return (dayData.overdue_count > 0) ? ' cw_overdue' : '';
    }

    function cwRenderPostsMobile(data) {
        var container = document.querySelector('.cw_posts_mobile');
        var html = '';
        var posts = data.posts || [];
        posts.forEach(function(post) {
            var postId = Number(post.id);
            var isActive = (postId === CWState.selectedPost);
            html += '<div class="cw_post_chip ' + (isActive ? 'cw_active' : '') + '" onclick="cwSelectPost(' + postId + ')">' + post.caption + '</div>';
        });
        if (data.data.posts_stats && data.data.posts_stats[-1]) {
            var unconfirmedStats = data.data.posts_stats[-1];
            var count = unconfirmedStats.records_count || 0;
            var isActive = (CWState.selectedPost === -1);
            html += '<div class="cw_post_chip cw_unconfirmed_chip ' + (isActive ? 'cw_active' : '') + '" onclick="cwSelectPost(-1)">⚠️ Не подтверждённые (' + count + ')</div>';
        }
        container.innerHTML = html;
    }

    function cwSelectPost(postId) {
        CWState.selectedPost = parseInt(postId, 10);
        cwLoadData();
    }

    // --- Месяц ---
    function cwRenderMonth(data) {
        if (!data || !data.days) return '<div class="cw_loading">Нет данных</div>';
        var html = '<div class="cw_month_grid">';
        var firstDay = new Date(data.year, parseInt(data.month.split('-')[1]) - 1, 1);
        var startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
        for (var i = 0; i < startOffset; i++) html += '<div class="cw_month_cell"></div>';
        for (var day = 1; day <= Object.keys(data.days).length; day++) {
            var dayStr = data.year + '-' + data.month.split('-')[1] + '-' + String(day).padStart(2, '0');
            var dayData = data.days[dayStr];
            var isToday = dayStr === new Date().toISOString().split('T')[0];
            var hasUnconfirmed = dayData && dayData.unconfirmed_count && dayData.unconfirmed_count > 0;
            var highlightClass = cwGetHighlightClass(dayData);
            var cellClass = 'cw_month_cell' + (isToday ? ' cw_today' : '') + highlightClass + (hasUnconfirmed ? ' cw_has_unconfirmed' : '');
            if (dayData && dayData.is_working) {
                html += '<div class="' + cellClass + '" onclick="cwGoToDay(\'' + dayStr + '\')">' +
                            '<div class="cw_month_day_num">' + day + '</div>' +
                            '<div class="cw_month_day_content">' + dayData.records_count + (hasUnconfirmed ? '<div class="cw_unconfirmed_badge">⚠️ ' + dayData.unconfirmed_count + '</div>' : '') + '</div>' +
                        '</div>';
            } else {
                html += '<div class="' + cellClass + '"><div class="cw_month_day_num">' + day + '</div></div>';
            }
        }
        html += '</div>';
        return html;
    }

    // --- Неделя ---
    function cwRenderWeek(data) {
        if (!data || !data.days) return '<div class="cw_loading">Нет данных</div>';
        var daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        var html = '<div class="cw_week_grid">';
        var sortedDays = Object.keys(data.days).sort();
        sortedDays.forEach(function(dayStr, index) {
            var day = data.days[dayStr];
            var isToday = dayStr === new Date().toISOString().split('T')[0];
            var hasUnconfirmed = day && day.unconfirmed_count && day.unconfirmed_count > 0;
            var highlightClass = cwGetHighlightClass(day);
            var cellClass = 'cw_week_day' + (isToday ? ' cw_today' : '') + highlightClass + (hasUnconfirmed ? ' cw_has_unconfirmed' : '');
            html += '<div class="' + cellClass + '" onclick="cwGoToDay(\'' + dayStr + '\')">' +
                        '<div class="cw_day_header"><span class="cw_day_name">' + daysOfWeek[index] + '</span><span class="cw_day_date">' + dayStr.split('-')[2] + '</span></div>' +
                        (day.is_working ? '<div class="cw_day_stats"><div class="cw_day_records">📅 ' + day.records_count + '</div>' + (hasUnconfirmed ? '<div style="color:#f97316; font-size:12px;">⚠️ ' + day.unconfirmed_count + ' неподтв.</div>' : '') + '<div style="margin-top: 4px; font-size: 13px;">💰 ' + day.sum.toLocaleString() + ' ₽</div></div>' : '<div class="cw_day_nonworking">Выходной</div>') +
                    '</div>';
        });
        html += '</div>';
        return html;
    }

    // --- День ---
    function cwRenderDay(data) {
        if (!data || !data.slots) return '<div class="cw_loading">Нет данных</div>';
        var html = '<div class="cw_day_grid">';
        var recordsMap = data.records || {};

        var foundRecords = false;
        for (var time in data.slots) {
            if (data.slots.hasOwnProperty(time)) {
                var slot = data.slots[time];
                var record = recordsMap[time] ? recordsMap[time] : null;
                if (record) foundRecords = true;

                var statusClass = '';
                var onClick = '';
                if (record) {
                    if (record.is_unconfirmed) {
                        statusClass = 'cw_unconfirmed';
                        onClick = 'cwEditRecord(' + record.id + ')';
                    } else {
                        if (record.status === 'overdue') statusClass = 'cw_overdue';
                        else if (record.status === 'completed') statusClass = 'cw_completed';
                        else statusClass = 'cw_future';
                        onClick = 'cwEditRecord(' + record.id + ')';
                    }
                } else {
                    onClick = 'cwCreateFreeSlot(\'' + CWState.currentDate + '\', \'' + time + '\', ' + CWState.selectedPost + ', ' + CWState.serviceId + ')';
                }

                html += '<div class="cw_slot_item ' + (record ? 'cw_has_record ' + statusClass : '') + '" onclick="' + onClick + '">' +
                            '<div class="cw_slot_time">' + time + '</div>' +
                            (record ? '<div class="cw_slot_car">' + (record.is_unconfirmed ? '⚠️ ' : '') + (record.car_info || '—') + '</div>' : '<div class="cw_slot_free">Свободно</div>') +
                        '</div>';
            }
        }
        if (!foundRecords) {
            html += '<div style="grid-column: 1 / -1; padding: 40px 20px; text-align: center; color: #6B7280;"><div style="font-size: 60px; margin-bottom: 15px;">📋</div>На этот день записей нет</div>';
        }
        html += '</div>';
        return html;
    }

    // --- Статистика ---
    function cwRenderStatsTab() {
        var container = document.getElementById('cw_stats_tab_container');
        var stats = CWState.data?.stats; 
        if (!stats) { container.innerHTML = '<div class="cw_loading">Данные не загружены</div>'; return; }
        var periodLabel = CWState.view === 'month' ? 'месяц' : (CWState.view === 'week' ? 'неделю' : 'день');
        var filterInfo = '';
        if (CWState.filterStatus === 'overdue') filterInfo = ' · только просроченные';
        else if (CWState.filterStatus === 'active') filterInfo = ' · только активные';
        else if (CWState.filterStatus === 'completed') filterInfo = ' · только выполненные';
        container.innerHTML = `
            <div style="padding-top: 5px; padding-bottom: 15px; color: #A0A0A0; font-size: 14px;">Статистика за ${periodLabel}${filterInfo}</div>
            <div class="cw_stats_grid_mobile">
                <div class="cw_stat_card"><div class="cw_stat_label">Записей</div><div class="cw_stat_value">${stats.total_records || 0}</div><div class="cw_stat_sub">за период</div></div>
                <div class="cw_stat_card"><div class="cw_stat_label">Сумма</div><div class="cw_stat_value">${(stats.total_sum || 0).toLocaleString()} ₽</div><div class="cw_stat_sub">за период</div></div>
                <div class="cw_stat_card"><div class="cw_stat_label">Нормо-часы</div><div class="cw_stat_value">${(stats.total_hours || 0).toFixed(1)}</div><div class="cw_stat_sub">за период</div></div>
                <div class="cw_stat_card"><div class="cw_stat_label">Клиентов</div><div class="cw_stat_value">${stats.unique_clients || 0}</div><div class="cw_stat_sub">авто: ${stats.unique_cars || 0}</div></div>
                <div class="cw_stat_card" style="grid-column: 1 / -1;"><div class="cw_stat_label">Неподтверждённых</div><div class="cw_stat_value" style="color: #f97316;">${stats.unconfirmed_count || 0}</div></div>
            </div>
        `;
    }

    // ---------- ДЕТАЛЬНЫЙ ПРОСМОТР ----------
    function cwShowDetail(html, activeTab) {
        var oldContainer = document.getElementById('cw_detail_view');
        if (oldContainer) {
            oldContainer.remove();
        }

        var container = document.createElement('div');
        container.id = 'cw_detail_view';
        container.style.display = 'block';
        container.style.padding = '15px';

        var calendarContainer = document.querySelector('.cw_calendar_container');
        if (calendarContainer) {
            calendarContainer.parentNode.insertBefore(container, calendarContainer.nextSibling);
        } else {
            document.body.appendChild(container);
        }

        container.innerHTML = html;

        var backBtn = document.createElement('button');
        backBtn.className = 'cw_back_btn';
        backBtn.textContent = '← Назад';
        backBtn.onclick = cwBackToCalendar;
        container.insertBefore(backBtn, container.firstChild);

        var scripts = container.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var script = scripts[i];
            if (!script.src) {
                try {
                    (function() {
                        eval(script.textContent);
                    })();
                } catch (e) {
                    console.error('Ошибка выполнения скрипта:', e);
                }
            } else {
                var newScript = document.createElement('script');
                newScript.src = script.src;
                document.body.appendChild(newScript);
            }
        }
        var scriptTags = container.querySelectorAll('script');
        scriptTags.forEach(function(s) { s.parentNode.removeChild(s); });

        if (calendarContainer) {
			calendarContainer.style.display = 'none'
		};
		
        if (activeTab && typeof jQuery !== 'undefined') {
            setTimeout(function() {
                var tabId = '#tab-' + activeTab;
                var tabLink = jQuery(tabId);
                if (tabLink.length) {
                    tabLink.tab('show');
                }
            }, 200);
        }

        if (!document.getElementById('modal_create_order')) {
            var dummy = document.createElement('div');
            dummy.id = 'modal_create_order';
            dummy.style.display = 'none';
            document.body.appendChild(dummy);
        }
        if (typeof jQuery.fn.modal !== 'function') {
            jQuery.fn.modal = function() { return this; };
        }
    }

    function cwBackToCalendar() {
        var container = document.getElementById('cw_detail_view');
        if (container) {
            container.remove();
        }
        var calendarContainer = document.querySelector('.cw_calendar_container');
        if (calendarContainer) calendarContainer.style.display = 'block';
        cwLoadData();
    }

    function cwEditRecord(recordId, activeTab) {
        jQuery.ajax({
            type: "POST",
            url: "/cp/content/car_service/ajax_get_modal_create_record.php",
            data: {
                record_id: recordId,
                service_id: CWState.serviceId,
                staff_id: CWState.staff ? CWState.staff.id : 0  /* ===== STAFF MODE ===== */
            },
            dataType: "text",
            success: function(answer) {
                cwShowDetail(answer, activeTab || null);
            },
            error: function() {
                alert('Ошибка загрузки формы редактирования записи');
            }
        });
    }

    function cwCreateFreeSlot(date, time, postId, serviceId) {
        jQuery.ajax({
            type: "POST",
            url: "/cp/content/car_service/ajax_get_modal_create_record.php",
            data: {
                date: date,
                time: time,
                post_id: postId,
                service_id: serviceId,
                staff_id: CWState.staff ? CWState.staff.id : 0  /* ===== STAFF MODE ===== */
            },
            dataType: "text",
            success: function(answer) {
                cwShowDetail(answer);
            },
            error: function() {
                alert('Ошибка загрузки формы');
            }
        });
    }

    function cwNavigate(direction) {
        var current = new Date(CWState.currentDate + 'T12:00:00');
        switch (CWState.view) {
            case 'month': current.setMonth(current.getMonth() + (direction === 'next' ? 1 : -1)); break;
            case 'week': current.setDate(current.getDate() + (direction === 'next' ? 7 : -7)); break;
            case 'day': current.setDate(current.getDate() + (direction === 'next' ? 1 : -1)); break;
        }
        CWState.currentDate = current.toISOString().split('T')[0];
        cwLoadData();
    }

    function cwSetView(view) {
        CWState.view = view;
        if (view === 'day' && !CWState.selectedPost && CWState.data?.posts?.length) {
            CWState.selectedPost = parseInt(CWState.data.posts[0].id, 10);
        }
        cwLoadData();
    }

    function cwGoToDay(dateStr) {
        CWState.view = 'day';
        CWState.currentDate = dateStr;
        cwLoadData();
    }
</script>

<div id="overlay" onClick="closeScanner();" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999;"></div>

<div id="scanner-container" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; border-radius: 8px; padding: 20px; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
	<div id="reader" style="width: 300px; height: 300px;"></div>
	<button onClick="closeScanner();" class="btn btn-danger" style="margin-top: 10px;">Закрыть</button>
</div>

</body>
</html> 