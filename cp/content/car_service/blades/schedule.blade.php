<?php
/**
 * Виджет календаря записей автосервиса
 * Версия: 2.1 (автоматическое применение поиска при смене вида/даты/поста)
 * Дата: 2026
 */

$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 1;
?>
 
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
.cw_slot_content.cw_unconfirmed {
    background: #ffedd5 !important;
    border-left: 4px solid #f97316 !important;
}
.cw_has_unconfirmed {
    border: 2px solid #f97316 !important;
}
.cw_slot_content.cw_unconfirmed {
    background: #ffedd5 !important;
    border-left: 4px solid #f97316 !important;
}
    .cw_calendar_wrapper {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
        background: #f5f7fb;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
    .cw_header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding: 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .cw_service_info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .cw_service_name {
        font-size: 20px;
        font-weight: 600;
        color: #1a1f36;
    }
    .cw_service_address {
        font-size: 14px;
        color: #6b7280;
    }
    .cw_service_phone {
        font-size: 14px;
        color: #3b82f6;
        text-decoration: none;
    }
    .cw_controls {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .cw_view_selector {
        display: flex;
        gap: 8px;
        background: #f3f4f6;
        padding: 4px;
        border-radius: 12px;
    }
    .cw_view_btn {
        padding: 8px 16px;
        border: none;
        background: transparent;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.2s;
    }
    .cw_view_btn.cw_active {
        background: white;
        color: #1a1f36;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .cw_date_nav {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .cw_nav_btn {
        width: 36px;
        height: 36px;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 18px;
        color: #4b5563;
    }
    .cw_nav_btn:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    .cw_current_date {
        font-size: 18px;
        font-weight: 500;
        color: #1a1f36;
        min-width: 200px;
        text-align: center;
    }
    .cw_stats_grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .cw_stat_card {
        background: white;
        padding: 16px;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .cw_stat_label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .cw_stat_value {
        font-size: 28px;
        font-weight: 600;
        color: #1a1f36;
        line-height: 1.2;
    }
    .cw_stat_sub {
        font-size: 14px;
        color: #10b981;
        margin-top: 4px;
    }
    .cw_posts_bar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
        padding: 16px;
        background: white;
        border-radius: 16px;
    }
    .cw_post_item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #f9fafb;
        border-radius: 30px;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    .cw_post_item.cw_active {
        background: #3b82f6;
        color: white;
    }
    .cw_post_item.cw_active .cw_post_count {
        background: white;
        color: #3b82f6;
    }
    .cw_post_icon {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }
    .cw_post_name {
        font-size: 14px;
        font-weight: 500;
    }
    .cw_post_count {
        background: #e5e7eb;
        color: #4b5563;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .cw_calendar_container {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .cw_week_grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #e5e7eb;
    }
    .cw_week_day {
        background: white;
        min-height: 140px;
        padding: 16px;
        transition: all 0.2s;
        cursor: pointer;
    }
    .cw_week_day.cw_today {
        background: #f0f9ff;
    }
    .cw_week_day.cw_search_match {
        border: 2px solid #10b981;
        background: #f0fdf4;
        box-shadow: 0 0 8px rgba(16,185,129,0.6);
    }
    .cw_day_header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .cw_day_name {
        font-size: 14px;
        font-weight: 500;
        color: #6b7280;
    }
    .cw_day_date {
        font-size: 20px;
        font-weight: 600;
        color: #1a1f36;
    }
    .cw_day_stats {
        font-size: 13px;
    }
    .cw_day_records {
        color: #3b82f6;
        font-weight: 600;
    }
    .cw_day_free {
        color: #10b981;
    }
    .cw_day_nonworking {
        background: #f3f4f6;
        color: #9ca3af;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 140px;
    }
    .cw_month_grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #e5e7eb;
    }
    .cw_month_cell {
        background: white;
        min-height: 100px;
        padding: 10px;
        transition: all 0.2s;
        cursor: pointer;
    }
    .cw_month_cell.cw_today {
        background: #f0f9ff;
    }
    .cw_month_cell.cw_search_match {
        border: 2px solid #10b981;
        background: #f0fdf4;
        box-shadow: 0 0 8px rgba(16,185,129,0.6);
    }
    .cw_month_day_num {
        font-size: 16px;
        font-weight: 600;
        color: #1a1f36;
        margin-bottom: 8px;
    }
    .cw_month_day_content {
        font-size: 12px;
    }
    .cw_day_view {
        display: flex;
        flex-direction: column;
    }
    .cw_time_slot {
        display: flex;
        border-bottom: 1px solid #f3f4f6;
        min-height: 80px;
    }
    .cw_time_label {
        width: 80px;
        padding: 16px;
        font-weight: 500;
        color: #4b5563;
        background: #f9fafb;
        border-right: 1px solid #e5e7eb;
    }
    .cw_slot_content {
        flex: 1;
        padding: 12px;
        background: white;
        transition: all 0.2s;
        cursor: pointer;
    }
    .cw_slot_content:hover {
        background: #f0f9ff;
    }
    .cw_slot_content.cw_has_record {
        background: #dbeafe;
        border-left: 4px solid #3b82f6;
    }
    .cw_record_info {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .cw_record_client {
        font-weight: 600;
        color: #1a1f36;
    }
    .cw_record_car {
        color: #4b5563;
        font-size: 13px;
    }
    .cw_record_worker {
        background: #e5e7eb;
        padding: 2px 8px;
        border-radius: 16px;
        font-size: 12px;
    }
    .cw_record_services {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }
    .cw_record_price {
        font-weight: 600;
        color: #059669;
    }
    .cw_print_btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        background: #1a1f36;
        color: white;
        border: none;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    .cw_print_btn:hover {
        background: #2d3748;
    }
    .cw_loading {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 300px;
        font-size: 16px;
        color: #6b7280;
    }
    .cw_slot_content.cw_overdue {
        background: #fee2e2 !important;
        border-left: 4px solid #dc2626 !important;
    }
    .cw_slot_content.cw_completed {
        background: #dcfce7 !important;
        border-left: 4px solid #10b981 !important;
    }
    .cw_slot_content.cw_future {
        background: #dbeafe !important;
        border-left: 4px solid #3b82f6 !important;
    }
    .cw_overdue_badge {
        display: inline-block;
        background: #dc2626;
        color: white;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 20px;
        margin-top: 6px;
    }
    .cw_search_panel {
        margin: 0 20px 20px;
        display: flex;
        gap: 12px;
        align-items: flex-end;
    }
    .cw_search_input {
        flex: 1;
        padding: 10px 16px;
        border: 1px solid #cbd5e0;
        border-radius: 30px;
        font-size: 14px;
        transition: all 0.2s;
    }
    .cw_search_input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }
    .cw_search_btn {
        padding: 10px 24px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 30px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    .cw_search_btn:hover {
        background: #2563eb;
        transform: translateY(-1px);
    }
    .cw_search_reset {
        padding: 10px 24px;
        background: #9ca3af;
        color: white;
        border: none;
        border-radius: 30px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    .cw_search_reset:hover {
        background: #6b7280;
    }
    .cw_search_status {
        margin: 0 20px 10px;
        font-size: 13px;
        color: #6b7280;
    }
    @media (max-width: 768px) {
        .cw_header { flex-direction: column; gap: 16px; }
        .cw_week_grid { grid-template-columns: 1fr; }
        .cw_stats_grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .cw_stats_grid { grid-template-columns: 1fr; }
        .cw_controls { width: 100%; }
        .cw_date_nav { width: 100%; justify-content: space-between; }
    }
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1050;
        display: none;
        width: 100%;
        height: 100%;
        overflow: hidden;
        outline: 0;
    }
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1040;
        width: 100vw;
        height: 100vh;
        background-color: #000;
        opacity: 0.5;
    }
    .modal-dialog {
        position: relative;
        width: auto;
        margin: 1.75rem auto;
        pointer-events: none;
        max-width: 800px;
    }
    .modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        pointer-events: auto;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0,0,0,.2);
        border-radius: 0.3rem;
        outline: 0;
    }
    .modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
        border-top-left-radius: 0.3rem;
        border-top-right-radius: 0.3rem;
    }
    .modal-body {
        position: relative;
        flex: 1 1 auto;
        padding: 1rem;
    }
    .modal-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        padding: 0.75rem;
        border-top: 1px solid #dee2e6;
        border-bottom-right-radius: 0.3rem;
        border-bottom-left-radius: 0.3rem;
    }
    .close {
        font-weight: 700;
        line-height: 1;
        color: #000;
        text-shadow: 0 1px 0 #fff;
        opacity: .5;
        cursor: pointer;
        background: transparent;
        border: 0;
        position: absolute;
        right: 25px;
        font-size: 24px;
    }
	
	#services_table_container {
		background : #2B2B3B;
	}
	
	#services_tbody tr:nth-child(odd) {
	  background-color: #6b6e97;
	}
	#total_sum_display {
		color: black !important;
	}
</style>

<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-body">
            <div class="cw_calendar_wrapper" id="cw_calendar_<?php echo $serviceId; ?>">
                <div style="margin: 20px;">
                    <button class="cw_print_btn" onclick="cwPrintDay()">
                        <span>🖨️</span> Печать разнорядки
                    </button>
					<button class="cw_print_btn" onclick="window.open('https://fordzap.com/content/carservice/admin_console/index.php', '_blank')">
                        <span>📞</span> Мобильная версия
                    </button>
                </div>
                <div class="cw_search_panel">
                    <input type="text" id="cw_search_input" class="cw_search_input" placeholder="🔍 Поиск по VIN, автомобилю или услуге...">
                    <button id="cw_search_btn" class="cw_search_btn">Найти</button>
                    <button id="cw_search_reset" class="cw_search_reset">Сброс</button>
                </div>
                <div id="cw_search_status" class="cw_search_status"></div>
                <div class="cw_header"></div>
                <div class="cw_stats_grid"></div>
                <div class="cw_posts_bar" style="display: none;"></div>
                <div class="cw_calendar_container"></div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modal_create_order" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header" style="border-bottom: none; padding: 1.5rem 1.5rem 0;">
                <h5 class="modal-title" style="font-weight: 700;">Новая запись в сервис</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;"></div>
        </div>
    </div>
</div>

<script>
    const CW_API_URL = 'https://fordzap.com/cp/content/car_service/modules/calendar/Calendar.php';
    
    const CWState = {
        serviceId: <?php echo $serviceId; ?>,
        view: 'week',
        currentDate: new Date().toISOString().split('T')[0],
        selectedPost: null,
        data: null,
        isLoading: false
    };

    const CWSearch = {
        active: false,
        query: '',
        originalData: null
    };

    document.addEventListener('DOMContentLoaded', function() {
        CWState.selectedPost = null;
        cwLoadData();
        document.getElementById('cw_search_btn').addEventListener('click', function() {
            const query = document.getElementById('cw_search_input').value.trim();
            cwApplySearch(query);
        });
        document.getElementById('cw_search_reset').addEventListener('click', function() {
            document.getElementById('cw_search_input').value = '';
            cwResetSearch();
        });
        document.getElementById('cw_search_input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                cwApplySearch(query);
            }
        });
    });

    function cwPrintDay() {
        if (CWState.view !== 'day') {
            alert('Для печати выберите дневной вид');
            return;
        }
        const printUrl = '/content/shop/print_docs/print-sheet.php?service_id=' + CWState.serviceId + '&date=' + CWState.currentDate;
        window.open(printUrl);
    }

    function cwCreateFreeSlot(date, time, postId, serviceId) {
        create_order(date, time, postId, serviceId);
    }
    function create_order(date, time, postId, serviceId) {
        confirm_order_modal(date, time, postId, serviceId);
    }
    function confirm_order_modal(date, time, postId, serviceId) {
        jQuery("#modal_create_order .modal-body").empty();
        jQuery.ajax({
            type: "POST",
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/car_service/ajax_get_modal_create_record.php",
            data: { date: date, time: time, post_id: postId, service_id: serviceId },
            dataType: "text",
            success: function(answer) {
                jQuery("#modal_create_order .modal-body").html(answer);
                jQuery("#modal_create_order").modal('show');
            },
            error: function() { alert('Ошибка загрузки формы создания записи'); }
        });
    }

    function cwLoadData() {
        const container = document.querySelector('.cw_calendar_container');
        container.innerHTML = '<div class="cw_loading">Загрузка...</div>';
        CWState.isLoading = true;
        const params = new URLSearchParams({
            action: 'get_calendar',
            service_id: CWState.serviceId,
            view: CWState.view,
            date: CWState.currentDate,
            post_id: CWState.selectedPost || ''
        });
        fetch(CW_API_URL + '?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = '<div class="cw_loading">Ошибка: ' + data.error + '</div>';
                    return;
                }
                CWState.data = data;
                CWState.isLoading = false;
                if (CWState.view === 'day' && !CWState.selectedPost && data.posts && data.posts.length > 0) {
                    CWState.selectedPost = data.posts[0].id;
                    cwLoadData();
                    return;
                }
                // Всегда сохраняем оригинал для сброса
                CWSearch.originalData = JSON.parse(JSON.stringify(data));
                // Если поиск активен, отображаем отфильтрованные данные, иначе обычные
                if (CWSearch.active && CWSearch.query) {
                    const filteredData = cwGetFilteredData(data, CWSearch.query);
                    cwRenderAll(filteredData);
                    // Обновляем статус с количеством найденных
                    const matchCount = cwCountMatches(data, CWSearch.query);
                    const statusDiv = document.getElementById('cw_search_status');
                    statusDiv.innerHTML = `🔍 Поиск: "${CWSearch.query}" (найдено записей: ${matchCount})`;
                    if (matchCount === 0) statusDiv.innerHTML += ' — ничего не найдено';
                } else {
                    cwRenderAll(data);
                }
            })
            .catch(error => {
                container.innerHTML = '<div class="cw_loading">Ошибка загрузки: ' + error + '</div>';
                CWState.isLoading = false;
            });
    }

    function cwGetFilteredData(originalData, query) {
        const filtered = JSON.parse(JSON.stringify(originalData));
        if (filtered.view === 'day') {
            const filteredRecords = {};
            for (const [time, record] of Object.entries(filtered.data.records || {})) {
                if (cwRecordMatchesSearch(record, query)) {
                    filteredRecords[time] = record;
                }
            }
            filtered.data.records = filteredRecords;
        }
        // Для week/month фильтрация не требуется, подсветка будет в рендере
        return filtered;
    }

    function cwCountMatches(originalData, query) {
        if (originalData.view === 'day') {
            let count = 0;
            for (const record of Object.values(originalData.data.records || {})) {
                if (cwRecordMatchesSearch(record, query)) count++;
            }
            return count;
        } else {
            const recordsByDay = originalData.data.records_by_day || {};
            let total = 0;
            for (const day in recordsByDay) {
                for (const rec of recordsByDay[day]) {
                    if (cwLightRecordMatchesSearch(rec, query)) total++;
                }
            }
            return total;
        }
    }

    function cwRenderAll(data) {
        cwRenderHeader(data);
        cwRenderStats(data.stats);
        cwRenderPosts(data);
        cwRenderCalendar(data);
    }

    function cwRecordMatchesSearch(record, query) {
        if (!query) return true;
        const q = query.toLowerCase();
        if (record.vin && record.vin.toLowerCase().includes(q)) return true;
        if (record.car_info && record.car_info.toLowerCase().includes(q)) return true;
        if (record.services && record.services.length) {
            for (let s of record.services) {
                if (s.caption && s.caption.toLowerCase().includes(q)) return true;
            }
        }
        return false;
    }

    function cwLightRecordMatchesSearch(lightRecord, query) {
        if (!query) return true;
        const q = query.toLowerCase();
        if (lightRecord.vin && lightRecord.vin.toLowerCase().includes(q)) return true;
        if (lightRecord.car_info && lightRecord.car_info.toLowerCase().includes(q)) return true;
        if (lightRecord.services_captions && lightRecord.services_captions.length) {
            for (let cap of lightRecord.services_captions) {
                if (cap.toLowerCase().includes(q)) return true;
            }
        }
        return false;
    }

    function cwApplySearch(query) {
        if (!query) {
            cwResetSearch();
            return;
        }
        CWSearch.query = query;
        CWSearch.active = true;
        // Перезагружаем данные с тем же состоянием, но с активным поиском
        // При загрузке автоматически применится фильтр
        cwLoadData();
    }

    function cwResetSearch() {
        if (!CWSearch.active) return;
        CWSearch.active = false;
        CWSearch.query = '';
        document.getElementById('cw_search_status').innerHTML = '';
        // Перезагружаем данные без фильтрации
        cwLoadData();
    }

    function cwRenderHeader(data) {
        const header = document.querySelector('.cw_header');
        const service = data.service || {};
        header.innerHTML = `
            <div class="cw_service_info">
                <div class="cw_service_name">${service.caption || 'Автосервис'}</div>
                <div class="cw_service_address">${service.address || ''}</div>
                <a href="tel:${service.phone || ''}" class="cw_service_phone">${service.phone || ''}</a>
            </div>
            <div class="cw_controls">
                <div class="cw_view_selector">
                    <button class="cw_view_btn ${data.view === 'month' ? 'cw_active' : ''}" onclick="cwSetView('month')">Месяц</button>
                    <button class="cw_view_btn ${data.view === 'week' ? 'cw_active' : ''}" onclick="cwSetView('week')">Неделя</button>
                    <button class="cw_view_btn ${data.view === 'day' ? 'cw_active' : ''}" onclick="cwSetView('day')">День</button>
                </div>
                <div class="cw_date_nav">
                    <button class="cw_nav_btn" onclick="cwNavigate('prev')">←</button>
                    <span class="cw_current_date">${cwFormatCurrentDate(data)}</span>
                    <button class="cw_nav_btn" onclick="cwNavigate('next')">→</button>
                </div>
            </div>
        `;
    }

    function cwFormatCurrentDate(data) {
        const d = new Date(data.current_date + 'T12:00:00');
        switch (data.view) {
            case 'month': return d.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' });
            case 'week':
                if (data.data.week_start && data.data.week_end) {
                    const start = new Date(data.data.week_start + 'T12:00:00');
                    const end = new Date(data.data.week_end + 'T12:00:00');
                    return `${start.getDate()} ${start.toLocaleDateString('ru-RU', { month: 'short' })} - ${end.getDate()} ${end.toLocaleDateString('ru-RU', { month: 'short' })} ${d.getFullYear()}`;
                }
                return 'Неделя';
            case 'day': return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
            default: return '';
        }
    }

function cwRenderStats(stats) {
    if (!stats) return;
    const container = document.querySelector('.cw_stats_grid');
    container.innerHTML = `
        <div class="cw_stat_card"><div class="cw_stat_label">Записей</div><div class="cw_stat_value">${stats.total_records || 0}</div><div class="cw_stat_sub">за период</div></div>
        <div class="cw_stat_card"><div class="cw_stat_label">Сумма</div><div class="cw_stat_value">${(stats.total_sum || 0).toLocaleString()} ₽</div></div>
        <div class="cw_stat_card"><div class="cw_stat_label">Нормо-часы</div><div class="cw_stat_value">${(stats.total_hours || 0).toFixed(1)}</div></div>
        <div class="cw_stat_card"><div class="cw_stat_label">Клиентов</div><div class="cw_stat_value">${stats.unique_clients || 0}</div><div class="cw_stat_sub">авто: ${stats.unique_cars || 0}</div></div>
        <div class="cw_stat_card"><div class="cw_stat_label">Неподтверждённых</div><div class="cw_stat_value" style="color: #f97316;">${stats.unconfirmed_count || 0}</div></div>
    `;
}

function cwRenderPosts(data) {
    const postsBar = document.querySelector('.cw_posts_bar');
    if (data.view !== 'day' || !data.posts || data.posts.length === 0) {
        postsBar.style.display = 'none';
        return;
    }
    postsBar.style.display = 'flex';
    let html = '';
    data.posts.forEach(post => {
        const postStats = data.data.posts_stats ? data.data.posts_stats[post.id] : null;
        const count = postStats ? postStats.records_count : 0;
        const isActive = CWState.selectedPost === post.id;
        html += `<div class="cw_post_item ${isActive ? 'cw_active' : ''}" onclick="cwSelectPost(${post.id})">
                    <img class="cw_post_icon" src="${post.image || ''}" alt="" onerror="this.style.display='none'">
                    <span class="cw_post_name">${post.caption}</span>
                    <span class="cw_post_count">${count}</span>
                </div>`;
    });
    if (data.data.posts_stats && data.data.posts_stats[-1]) {
        const unconfirmedStats = data.data.posts_stats[-1];
        const count = unconfirmedStats.records_count || 0;
        const isActive = CWState.selectedPost === -1;
        html += `<div class="cw_post_item ${isActive ? 'cw_active' : ''}" onclick="cwSelectPost(-1)">
                    <span class="cw_post_name" style="color: #f97316;">⚠️ Не подтверждённые</span>
                    <span class="cw_post_count">${count}</span>
                </div>`;
    }
    postsBar.innerHTML = html;
}


    function cwRenderCalendar(data) {
        const container = document.querySelector('.cw_calendar_container');
        switch (data.view) {
            case 'month': container.innerHTML = cwRenderMonth(data.data); break;
            case 'week': container.innerHTML = cwRenderWeek(data.data); break;
            case 'day': container.innerHTML = cwRenderDay(data.data); break;
            default: container.innerHTML = '';
        }
    }

function cwRenderMonth(data) {
    if (!data || !data.days) return '<div class="cw_loading">Нет данных</div>';
    const daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
    let html = '<div class="cw_month_grid">';
    daysOfWeek.forEach(day => { html += `<div style="background: #f9fafb; padding: 10px; text-align: center; font-weight: 500;">${day}</div>`; });
    const firstDay = new Date(data.year, parseInt(data.month.split('-')[1]) - 1, 1);
    const startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
    for (let i = 0; i < startOffset; i++) html += '<div class="cw_month_cell" style="background: #f9fafb;"></div>';
    for (let day = 1; day <= Object.keys(data.days).length; day++) {
        const dayStr = `${data.year}-${data.month.split('-')[1]}-${String(day).padStart(2, '0')}`;
        const dayData = data.days[dayStr];
        const isToday = dayStr === new Date().toISOString().split('T')[0];
        let isSearchMatch = false;
        if (CWSearch.active && data.records_by_day && data.records_by_day[dayStr]) {
            for (let rec of data.records_by_day[dayStr]) {
                if (cwLightRecordMatchesSearch(rec, CWSearch.query)) { isSearchMatch = true; break; }
            }
        }
        let cellClass = `cw_month_cell ${isToday ? 'cw_today' : ''}`;
        if (isSearchMatch) cellClass += ' cw_search_match';
        if (dayData && dayData.unconfirmed_count > 0) cellClass += ' cw_has_unconfirmed';
        if (dayData) {
            html += `<div class="${cellClass}" onclick="cwGoToDay('${dayStr}')">
                        <div class="cw_month_day_num">${day}</div>
                        <div class="cw_month_day_content">
                            ${dayData.is_working ? `<div>📅 ${dayData.records_count} зап.</div><div>💰 ${dayData.sum.toLocaleString()} ₽</div>${dayData.overdue_count > 0 ? `<div class="cw_overdue_badge">🔴 ${dayData.overdue_count} просрочено</div>` : ''}${dayData.unconfirmed_count > 0 ? `<div style="color: #f97316;">⚠️ ${dayData.unconfirmed_count} неподтв.</div>` : ''}` : '<div style="color: #9ca3af;">Выходной</div>'}
                        </div>
                    </div>`;
        } else {
            html += `<div class="${cellClass}"><div class="cw_month_day_num">${day}</div></div>`;
        }
    }
    html += '</div>';
    return html;
}

function cwRenderWeek(data) {
    if (!data || !data.days) return '<div class="cw_loading">Нет данных</div>';
    const daysOfWeek = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
    let html = '<div class="cw_week_grid">';
    Object.keys(data.days).sort().forEach((dayStr, index) => {
        const day = data.days[dayStr];
        const isToday = dayStr === new Date().toISOString().split('T')[0];
        let isSearchMatch = false;
        if (CWSearch.active && data.records_by_day && data.records_by_day[dayStr]) {
            for (let rec of data.records_by_day[dayStr]) {
                if (cwLightRecordMatchesSearch(rec, CWSearch.query)) { isSearchMatch = true; break; }
            }
        }
        let cellClass = `cw_week_day ${isToday ? 'cw_today' : ''}`;
        if (isSearchMatch) cellClass += ' cw_search_match';
        if (day && day.unconfirmed_count > 0) cellClass += ' cw_has_unconfirmed';
        html += `<div class="${cellClass}" onclick="cwGoToDay('${dayStr}')">
                    <div class="cw_day_header"><span class="cw_day_name">${daysOfWeek[index]}</span><span class="cw_day_date">${dayStr.split('-')[2]}</span></div>
                    ${day.is_working ? `<div class="cw_day_stats"><div class="cw_day_records">📅 ${day.records_count} записей</div>${day.overdue_count > 0 ? `<div class="cw_overdue_badge">🔴 Просрочено: ${day.overdue_count}</div>` : ''}${day.unconfirmed_count > 0 ? `<div style="color: #f97316; font-size: 12px;">⚠️ ${day.unconfirmed_count} неподтв.</div>` : ''}<div style="margin-top: 8px; font-size: 12px;">💰 ${day.sum.toLocaleString()} ₽</div></div>` : `<div class="cw_day_nonworking">Выходной</div>`}
                </div>`;
    });
    html += '</div>';
    return html;
}

function cwEditRecord(recordId) {
    jQuery.ajax({
        type: "POST",
        url: "/cp/content/car_service/ajax_get_modal_create_record.php",
        data: { record_id: recordId, service_id: CWState.serviceId },
        dataType: "text",
        success: function(answer) {
            jQuery("#modal_create_order .modal-body").html(answer);
            jQuery("#modal_create_order").modal('show');
        },
        error: function() { alert('Ошибка загрузки формы редактирования записи'); }
    });
}

function cwRenderDay(data) {
    if (!data || !data.slots) return '<div class="cw_loading">Нет данных</div>';
    let html = '<div class="cw_day_view">';
    const currentPostId = CWState.selectedPost;
    const currentServiceId = CWState.serviceId;
    const currentDate = CWState.currentDate;
    const recordsMap = data.records || {};
    for (const [time, slot] of Object.entries(data.slots)) {
        const record = recordsMap[time] ? recordsMap[time] : null;
        if (CWSearch.active && !record) continue;
        let statusClass = '';
        let unconfirmedClass = '';
        let unconfirmedBadge = '';
        let orderLink = '';
        let onclickAttr = '';
        if (record) {
            if (record.is_unconfirmed) {
                unconfirmedClass = 'cw_unconfirmed';
                unconfirmedBadge = `<div style="color: #f97316; font-weight: 600; background: #ffedd5; padding: 2px 8px; border-radius: 12px; display: inline-block; margin-top: 4px;">⚠️ Не подтверждено</div>`;
                onclickAttr = `onclick="cwEditRecord(${record.id})"`;
            } else {
                if (record.status === 'overdue') statusClass = 'cw_overdue';
                else if (record.status === 'completed') statusClass = 'cw_completed';
                else statusClass = 'cw_future';
                if (record.orders_ids) {
                    onclickAttr = `onclick="window.open('https://fordzap.com/cp/shop/orders/order?order_id=${record.orders_ids}', '_blank')"`;
                }
            }
        } else {
            onclickAttr = `onclick="cwCreateFreeSlot('${currentDate}', '${time}', ${currentPostId}, ${currentServiceId})"`;
        }
        const cursorStyle = record ? 'cursor: pointer;' : 'cursor: pointer;';
        html += `<div class="cw_time_slot">
                    <div class="cw_time_label">${time}</div>
                    <div class="cw_slot_content ${record ? 'cw_has_record ' + statusClass + ' ' + unconfirmedClass : ''}" style="${cursorStyle}" ${onclickAttr}>
                        ${record ? `<div class="cw_record_info"><span class="cw_record_client">${record.client_name || 'Клиент'}</span><span class="cw_record_car">${record.car_info || ''}</span><span class="cw_record_worker">${record.worker_name || ''}</span></div>
                                    <div class="cw_record_status">${record.is_unconfirmed ? '' : (record.status === 'overdue' ? '🔴 Просрочена' : (record.status === 'completed' ? '✅ Выполнена' : '⏳ Активна'))}</div>
                                    ${unconfirmedBadge}
                                    ${record.services && record.services.length ? `<div class="cw_record_services">${record.services.map(s => s.caption).join(', ')}</div>` : ''}` : 'Свободно'}
                    </div>
                </div>`;
    }
    html += '</div>';
    return html;
}

    function cwNavigate(direction) {
        const current = new Date(CWState.currentDate + 'T12:00:00');
        switch (CWState.view) {
            case 'month': current.setMonth(current.getMonth() + (direction === 'next' ? 1 : -1)); break;
            case 'week': current.setDate(current.getDate() + (direction === 'next' ? 7 : -7)); break;
            case 'day': current.setDate(current.getDate() + (direction === 'next' ? 1 : -1)); break;
        }
        CWState.currentDate = current.toISOString().split('T')[0];
        // Не сбрасываем поиск, просто загружаем новые данные (поиск применится автоматически)
        cwLoadData();
    }

    function cwSetView(view) {
        CWState.view = view;
        CWState.selectedPost = null;
        // Не сбрасываем поиск
        cwLoadData();
    }

    function cwSelectPost(postId) {
        CWState.selectedPost = postId;
        // Не сбрасываем поиск
        cwLoadData();
    }

    function cwGoToDay(dateStr) {
        CWState.view = 'day';
        CWState.currentDate = dateStr;
        cwLoadData();
    }
</script>