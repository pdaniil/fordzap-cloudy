<?php
/**
 * Встраиваемый календарь для мастера
 * Версия: 2.0 (фильтрация по worker_id)
 * 
 * Настройки:
 *   SERVICE_ID – ID сервиса (захардкодить)
 *   WORKER_ID  – ID мастера; 0 – показывать все записи
 */
$serviceId = 1;          // замените на нужный ID

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Календарь мастера</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* Сброс и глобальные стили (полностью скопированы из оригинала) */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background-color: #1E1E2D; 
            color: #FFFFFF; 
            padding-bottom: 80px;
            overflow-x: hidden;
        }
        .cw_mobile_app { width: 100%; min-height: 100vh; position: relative; background: #1E1E2D; }
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
        .cw_service_name { font-size: 18px; font-weight: 700; color: #FFFFFF; }
        .cw_header_icons { display: flex; gap: 15px; font-size: 18px; color: #A0A0A0; }
        .cw_header_icons span { cursor: pointer; transition: 0.2s; }
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
        .cw_month_cell.cw_has_unconfirmed { border-color: #f97316 !important; }
        .cw_month_day_num { font-size: 16px; font-weight: 500; color: #FFFFFF; margin-bottom: 4px; }
        .cw_month_day_content { font-size: 11px; color: #A0A0B0; }
        .cw_unconfirmed_badge { color: #f97316; font-weight: 600; }

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
        .cw_week_day.cw_has_unconfirmed { border-color: #f97316 !important; }
        .cw_day_header { display: flex; flex-direction: column; align-items: center; margin-bottom: 8px; }
        .cw_day_name { font-size: 13px; color: #A0A0B0; }
        .cw_day_date { font-size: 20px; font-weight: 600; color: #FFFFFF; }
        .cw_day_stats { text-align: center; color: #D1D5DB; }
        .cw_day_records { color: #60A5FA; font-weight: 500; font-size: 14px; }
        .cw_day_nonworking { color: #6B7280; text-align: center; font-size: 13px; }

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
        .cw_slot_item.cw_occupied { border-color: #6B7280; background: #2B2B3B; cursor: default; opacity: 0.6; }
        .cw_slot_item.cw_occupied:active { transform: none; background: #2B2B3B; }

        .cw_slot_time { font-size: 14px; font-weight: 700; color: #FFFFFF; margin-bottom: 4px; }
        .cw_slot_free { font-size: 12px; color: #A0A0B0; }
        .cw_slot_car { font-size: 11px; color: #9CA3AF; line-height: 1.2; }

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

        .cw_loading { padding: 40px; text-align: center; color: #A0A0B0; }
        .cw_error { color: #EF4444; padding: 20px; text-align: center; }
    </style>
</head>
<body>

<div class="cw_mobile_app" id="cw_calendar_app">
    <!-- Шапка -->
    <div class="cw_header"></div>
    
    <!-- Переключатель постов (только для дня) -->
    <div class="cw_posts_mobile"></div>
    
    <!-- Основной контент (Календарь) -->
    <div class="cw_calendar_container"></div>

    <!-- Отдельный блок Статистики -->
    <div id="cw_stats_tab_container"></div>

    <!-- Нижняя навигация -->
    <div class="cw_mobile_nav">
        <button class="cw_mobile_nav_item active" data-view="calendar"><span class="icon">📅</span>Календарь</button>
        <button class="cw_mobile_nav_item" data-view="stats"><span class="icon">📊</span>Статистика</button>
    </div>
</div>

<script>
    // ===================== НАСТРОЙКИ =====================
    const SERVICE_ID = <?php echo (int)$serviceId; ?>;
    const WORKER_ID  = <?php echo (int)$workerId; ?>;   // 0 – все мастера, иначе фильтр по worker_id

    const CW_API_URL = 'https://fordzap.com/cp/content/car_service/modules/calendar/Calendar.php';
    
    // ===================== СОСТОЯНИЕ =====================
    const CWState = {
        serviceId: SERVICE_ID,
        view: 'day',
        currentDate: new Date().toISOString().split('T')[0],
        selectedPost: null,
        data: null
    };

    // ===================== ГЛАВНАЯ ФУНКЦИЯ ОБРАБОТКИ КЛИКА ПО СЛОТУ =====================
    /**
     * Вызывается при клике на временной слот.
     * @param {Object|null} record - данные записи (если слот занят этим мастером), иначе null
     * @param {string} time - время слота (H:i)
     * @param {string} date - дата (Y-m-d)
     * @param {number} postId - ID поста
     * @param {number} serviceId - ID сервиса
     * @param {boolean} isFree - true, если слот свободен
     */
    function cwSlotClick(record, time, date, postId, serviceId, isFree) {
        // Пока просто выводим в консоль и alert для демонстрации
        console.log('Клик по слоту:', { record, time, date, postId, serviceId, isFree });
        if (isFree) {
            alert('Свободный слот: ' + date + ' ' + time + '\nПост: ' + postId);
        } else {
            alert('Запись мастера:\n' + 
                  'Клиент: ' + (record.client_name || '—') + '\n' +
                  'Авто: ' + (record.car_info || '—') + '\n' +
                  'Услуги: ' + (record.services ? record.services.map(s => s.caption).join(', ') : '—') + '\n' +
                  'ID заказа: ' + (record.orders_ids || 'нет'));
        }
        // Здесь вы можете вставить свою логику (например, открыть свою модалку или перенаправить)
    }

    // ===================== ЗАГРУЗКА ДАННЫХ =====================
    function cwLoadData() {
        const container = document.querySelector('.cw_calendar_container');
        const activeTab = document.querySelector('.cw_mobile_nav_item.active').dataset.view;
        
        if (activeTab === 'calendar') container.innerHTML = '<div class="cw_loading">Загрузка...</div>';
        else document.getElementById('cw_stats_tab_container').innerHTML = '<div class="cw_loading">Загрузка...</div>';
        
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
                    const errHtml = '<div class="cw_error">' + data.error + '</div>';
                    if(activeTab === 'calendar') container.innerHTML = errHtml;
                    else document.getElementById('cw_stats_tab_container').innerHTML = errHtml;
                    return; 
                }
                CWState.data = data; 

                // Автовыбор поста, если не выбран
                if (CWState.view === 'day' && !CWState.selectedPost && data.posts && data.posts.length > 0) {
                    CWState.selectedPost = parseInt(data.posts[0].id, 10); 
                    cwLoadData(); return;
                }
                
                cwRenderHeader(data);
                
                const currentTab = document.querySelector('.cw_mobile_nav_item.active').dataset.view;
                if (currentTab === 'calendar') {
                    cwRenderCalendar(data);
                } else if (currentTab === 'stats') {
                    cwRenderStatsTab();
                }
            })
            .catch(error => { 
                const errHtml = '<div class="cw_error">Ошибка соединения</div>';
                if(activeTab === 'calendar') container.innerHTML = errHtml;
                else document.getElementById('cw_stats_tab_container').innerHTML = errHtml;
            });
    }

    // ===================== РЕНДЕРИНГ =====================
    function cwRenderHeader(data) {
        const header = document.querySelector('.cw_header');
        const service = data.service || {};
        const dateStr = cwFormatCurrentDate(data);
        const view = data.view;

        header.innerHTML = `
            <div class="cw_service_info">
                <div class="cw_service_name">${service.caption || 'Моя работа'}</div>
                <div class="cw_header_icons"><span onclick="alert('Настройки в разработке.')">⚙️</span></div>
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
        const d = new Date(data.current_date + 'T12:00:00');
        switch (data.view) {
            case 'month': return d.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' });
            case 'week':
                if (data.data.week_start && data.data.week_end) {
                    const start = new Date(data.data.week_start + 'T12:00:00');
                    const end = new Date(data.data.week_end + 'T12:00:00');
                    return `${start.getDate()} - ${end.getDate()} ${end.toLocaleDateString('ru-RU', { month: 'short' })}`;
                }
                return 'Неделя';
            case 'day': return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
            default: return '';
        }
    }

    function cwRenderCalendar(data) {
        const container = document.querySelector('.cw_calendar_container');
        // Показываем/скрываем посты (с подсветкой активного)
        const postsBar = document.querySelector('.cw_posts_mobile');
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
    }

    // --- Посты (Переключатель) ---
    function cwRenderPostsMobile(data) {
        const container = document.querySelector('.cw_posts_mobile');
        let html = '';
        const posts = data.posts || [];
        posts.forEach(post => {
            const postId = Number(post.id);
            const isActive = (postId === CWState.selectedPost);
            html += `<div class="cw_post_chip ${isActive ? 'cw_active' : ''}" onclick="cwSelectPost(${postId})">
                        ${post.caption}
                    </div>`;
        });
        // Псевдо-пост для неподтверждённых (если есть такие записи)
        if (data.data.posts_stats && data.data.posts_stats[-1]) {
            const unconfirmedStats = data.data.posts_stats[-1];
            const count = unconfirmedStats.records_count || 0;
            const isActive = (CWState.selectedPost === -1);
            html += `<div class="cw_post_chip cw_unconfirmed_chip ${isActive ? 'cw_active' : ''}" onclick="cwSelectPost(-1)">
                        ⚠️ Не подтверждённые (${count})
                    </div>`;
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
        let html = `<div class="cw_month_grid">`;
        const firstDay = new Date(data.year, parseInt(data.month.split('-')[1]) - 1, 1);
        const startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
        for (let i = 0; i < startOffset; i++) html += '<div class="cw_month_cell"></div>';
        for (let day = 1; day <= Object.keys(data.days).length; day++) {
            const dayStr = `${data.year}-${data.month.split('-')[1]}-${String(day).padStart(2, '0')}`;
            const dayData = data.days[dayStr];
            const isToday = dayStr === new Date().toISOString().split('T')[0];
            const hasOverdue = dayData && dayData.overdue_count && dayData.overdue_count > 0;
            const hasUnconfirmed = dayData && dayData.unconfirmed_count && dayData.unconfirmed_count > 0;
            let cellClass = `cw_month_cell ${isToday ? 'cw_today' : ''} ${hasOverdue ? 'cw_overdue' : ''} ${hasUnconfirmed ? 'cw_has_unconfirmed' : ''}`;
            if (dayData && dayData.is_working) {
                html += `<div class="${cellClass}" onclick="cwGoToDay('${dayStr}')">
                            <div class="cw_month_day_num">${day}</div>
                            <div class="cw_month_day_content">
                                ${dayData.records_count}
                                ${hasUnconfirmed ? `<div class="cw_unconfirmed_badge">⚠️ ${dayData.unconfirmed_count}</div>` : ''}
                            </div>
                        </div>`;
            } else {
                html += `<div class="${cellClass}"><div class="cw_month_day_num">${day}</div></div>`;
            }
        }
        html += '</div>';
        return html;
    }

    // --- Неделя ---
    function cwRenderWeek(data) {
        if (!data || !data.days) return '<div class="cw_loading">Нет данных</div>';
        const daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        let html = `<div class="cw_week_grid">`;
        const sortedDays = Object.keys(data.days).sort();
        sortedDays.forEach((dayStr, index) => {
            const day = data.days[dayStr];
            const isToday = dayStr === new Date().toISOString().split('T')[0];
            const hasOverdue = day && day.overdue_count && day.overdue_count > 0;
            const hasUnconfirmed = day && day.unconfirmed_count && day.unconfirmed_count > 0;
            let cellClass = `cw_week_day ${isToday ? 'cw_today' : ''} ${hasOverdue ? 'cw_overdue' : ''} ${hasUnconfirmed ? 'cw_has_unconfirmed' : ''}`;
            html += `<div class="${cellClass}" onclick="cwGoToDay('${dayStr}')">
                        <div class="cw_day_header">
                            <span class="cw_day_name">${daysOfWeek[index]}</span>
                            <span class="cw_day_date">${dayStr.split('-')[2]}</span>
                        </div>
                        ${day.is_working ? `
                            <div class="cw_day_stats">
                                <div class="cw_day_records">📅 ${day.records_count}</div>
                                ${hasUnconfirmed ? `<div style="color:#f97316; font-size:12px;">⚠️ ${day.unconfirmed_count} неподтв.</div>` : ''}
                                <div style="margin-top: 4px; font-size: 13px;">💰 ${day.sum.toLocaleString()} ₽</div>
                            </div>
                        ` : `<div class="cw_day_nonworking">Выходной</div>`}
                    </div>`;
        });
        html += '</div>';
        return html;
    }

    // --- День (с фильтрацией по worker_id) ---
    function cwRenderDay(data) {
        if (!data || !data.slots) return '<div class="cw_loading">Нет данных</div>';
        let html = `<div class="cw_day_grid">`;
        const recordsMap = data.records || {};

        // Если WORKER_ID > 0, фильтруем записи: оставляем только свои
        const filteredRecordsMap = {};
        if (WORKER_ID > 0) {
            for (const [time, record] of Object.entries(recordsMap)) {
                if (record && record.worker_id == WORKER_ID) {
                    filteredRecordsMap[time] = record;
                }
            }
        } else {
            // Все записи
            Object.assign(filteredRecordsMap, recordsMap);
        }

        let foundRecords = false;
        for (const [time, slot] of Object.entries(data.slots)) {
            const record = filteredRecordsMap[time] ? filteredRecordsMap[time] : null;
            // Проверяем, есть ли запись другого мастера (если фильтр активен)
            let otherRecord = null;
            if (WORKER_ID > 0 && recordsMap[time] && recordsMap[time].worker_id != WORKER_ID) {
                otherRecord = recordsMap[time];
            }

            let statusClass = '';
            let isUnconfirmed = false;
            let onClick = '';
            let isFree = false;
            let isOccupied = false;

            if (record) {
                foundRecords = true;
                if (record.is_unconfirmed) {
                    statusClass = 'cw_unconfirmed';
                    isUnconfirmed = true;
                } else {
                    if (record.status === 'overdue') statusClass = 'cw_overdue';
                    else if (record.status === 'completed') statusClass = 'cw_completed';
                    else statusClass = 'cw_future';
                }
                // Клик передаёт данные записи
                onClick = `cwSlotClick(${JSON.stringify(record).replace(/"/g, "'")}, '${time}', '${CWState.currentDate}', ${CWState.selectedPost || 0}, ${CWState.serviceId}, false)`;
            } else if (otherRecord) {
                // Занято другим мастером – не кликабельно
                isOccupied = true;
                statusClass = 'cw_occupied';
                onClick = ''; // ничего не делаем
            } else {
                // Свободный слот
                isFree = true;
                onClick = `cwSlotClick(null, '${time}', '${CWState.currentDate}', ${CWState.selectedPost || 0}, ${CWState.serviceId}, true)`;
            }

            // Формируем классы
            let slotClasses = 'cw_slot_item';
            if (record) slotClasses += ' cw_has_record ' + statusClass;
            else if (isOccupied) slotClasses += ' cw_occupied';
            // Если запись есть и она неподтверждённая, добавляем класс
            if (record && record.is_unconfirmed) slotClasses += ' cw_unconfirmed';

            html += `<div class="${slotClasses}" onclick="${onClick}">
                        <div class="cw_slot_time">${time}</div>
                        ${record ? `
                            <div class="cw_slot_car">${record.is_unconfirmed ? '⚠️ ' : ''}${record.car_info || '—'}</div>
                        ` : (isOccupied ? `<div class="cw_slot_free" style="color:#6B7280;">Занято</div>` : `<div class="cw_slot_free">Свободно</div>`)}
                    </div>`;
        }
        if (!foundRecords && WORKER_ID > 0) {
            html += `<div style="grid-column: 1 / -1; padding: 40px 20px; text-align: center; color: #6B7280;">
                        <div style="font-size: 60px; margin-bottom: 15px;">📋</div>
                        На этот день записей нет
                    </div>`;
        } else if (!foundRecords && WORKER_ID === 0) {
            // Если нет записей вообще
            html += `<div style="grid-column: 1 / -1; padding: 40px 20px; text-align: center; color: #6B7280;">
                        <div style="font-size: 60px; margin-bottom: 15px;">📋</div>
                        На этот день записей нет
                    </div>`;
        }
        html += '</div>';
        return html;
    }

    // ---------- СТАТИСТИКА ----------
    function cwRenderStatsTab() {
        const container = document.getElementById('cw_stats_tab_container');
        const stats = CWState.data?.stats; 
        if (!stats) { container.innerHTML = '<div class="cw_loading">Данные не загружены</div>'; return; }
        const periodLabel = CWState.view === 'month' ? 'месяц' : (CWState.view === 'week' ? 'неделю' : 'день');
        container.innerHTML = `
            <div style="padding-top: 5px; padding-bottom: 15px; color: #A0A0A0; font-size: 14px;">Статистика за ${periodLabel}</div>
            <div class="cw_stats_grid_mobile">
                <div class="cw_stat_card"><div class="cw_stat_label">Записей</div><div class="cw_stat_value">${stats.total_records || 0}</div><div class="cw_stat_sub">за период</div></div>
                <div class="cw_stat_card"><div class="cw_stat_label">Сумма</div><div class="cw_stat_value">${(stats.total_sum || 0).toLocaleString()} ₽</div><div class="cw_stat_sub">за период</div></div>
                <div class="cw_stat_card"><div class="cw_stat_label">Нормо-часы</div><div class="cw_stat_value">${(stats.total_hours || 0).toFixed(1)}</div><div class="cw_stat_sub">за период</div></div>
                <div class="cw_stat_card"><div class="cw_stat_label">Клиентов</div><div class="cw_stat_value">${stats.unique_clients || 0}</div><div class="cw_stat_sub">авто: ${stats.unique_cars || 0}</div></div>
                <div class="cw_stat_card" style="grid-column: 1 / -1;"><div class="cw_stat_label">Неподтверждённых</div><div class="cw_stat_value" style="color: #f97316;">${stats.unconfirmed_count || 0}</div></div>
            </div>
        `;
    }

    // ---------- УПРАВЛЕНИЕ (Навигация и Действия) ----------
    function cwNavigate(direction) {
        const current = new Date(CWState.currentDate + 'T12:00:00');
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

    // ===================== ИНИЦИАЛИЗАЦИЯ =====================
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.cw_calendar_container').style.display = 'block';
        document.getElementById('cw_stats_tab_container').style.display = 'none';
        cwLoadData();

        document.querySelectorAll('.cw_mobile_nav_item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.cw_mobile_nav_item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                const view = this.dataset.view;
                
                if (view === 'calendar') {
                    document.querySelector('.cw_calendar_container').style.display = 'block';
                    document.getElementById('cw_stats_tab_container').style.display = 'none';
                    if (CWState.data) cwRenderCalendar(CWState.data);
                    else cwLoadData();
                } else if (view === 'stats') {
                    document.querySelector('.cw_calendar_container').style.display = 'none';
                    document.getElementById('cw_stats_tab_container').style.display = 'block';
                    if (CWState.data) cwRenderStatsTab();
                    else cwLoadData();
                }
            });
        });
    });
</script>
</body>
</html>