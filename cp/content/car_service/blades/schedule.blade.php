<?php
/**
 * Виджет календаря записей автосервиса
 * Дата: 2026
 */

// Получаем ID сервиса из GET или используем 1 по умолчанию
$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 1;
?>


    <style>
        /* Уникальные классы с префиксом cw_ (calendar-widget) */
        .cw_calendar_wrapper {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f7fb;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        /* Шапка календаря */
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

        /* Статистика */
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

        /* Посты (только для дня) */
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

        /* Календарь */
        .cw_calendar_container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        /* Вид недели */
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
        }

        .cw_week_day.cw_today {
            background: #f0f9ff;
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

        /* Вид месяца */
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
        }

        .cw_month_cell.cw_today {
            background: #f0f9ff;
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

        /* Вид дня */
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

        /* Кнопка печати */
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

        /* Загрузка */
        .cw_loading {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
            font-size: 16px;
            color: #6b7280;
        }

        /* Курсор pointer для кликабельных элементов */
        .cw_post_item,
        .cw_nav_btn,
        .cw_view_btn,
        .cw_print_btn,
        .cw_month_cell,
        .cw_week_day,
        .cw_slot_content.cw_has_record {
            cursor: pointer;
        }

        /* Анимация при наведении на занятые слоты */
        .cw_slot_content.cw_has_record:hover {
            background: #bfdbfe !important;
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            transition: all 0.2s ease;
        }

        /* Анимация для ячеек календаря */
        .cw_month_cell:hover,
        .cw_week_day:hover {
            background: #f0f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            z-index: 10;
            position: relative;
        }

        /* Анимация для постов */
        .cw_post_item:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }

        .cw_post_item.cw_active:hover {
            background: #2563eb;
        }

        /* Анимация для кнопок */
        .cw_nav_btn:hover,
        .cw_view_btn:hover,
        .cw_print_btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .cw_header {
                flex-direction: column;
                gap: 16px;
            }
            
            .cw_week_grid {
                grid-template-columns: 1fr;
            }
            
            .cw_stats_grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .cw_stats_grid {
                grid-template-columns: 1fr;
            }
            
            .cw_controls {
                width: 100%;
            }
            
            .cw_date_nav {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>

<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-body">
			<div class="cw_calendar_wrapper" id="cw_calendar_<?php echo $serviceId; ?>">
				<!-- Кнопка печати -->
				<div style="margin: 20px;">
					<button class="cw_print_btn" onclick="cwPrintDay()">
						<span>🖨️</span> Печать разнорядки
					</button>
				</div>
				<!-- Шапка будет заполнена через JS -->
				<div class="cw_header"></div>
				
				<!-- Статистика -->
				<div class="cw_stats_grid"></div>
				
				<!-- Посты (для дня) -->
				<div class="cw_posts_bar" style="display: none;"></div>
				
				<!-- Календарь -->
				<div class="cw_calendar_container"></div>
			</div>
		</div>
	</div>
</div>
	
	
    <script>
		 
		// Печать разнорядки
		function cwPrintDay() {
			if (CWState.view !== 'day') {
				alert('Для печати выберите дневной вид');
				return;
			}
			
			// Формируем URL для печати (только service_id и date)
			const printUrl = '/content/shop/print_docs/print-sheet.php?' + 
				'service_id=' + CWState.serviceId + 
				'&date=' + CWState.currentDate;
			
			// Открываем в новом окне
			window.open(printUrl);
		}
		
        // Укажите здесь URL к вашему API файлу
        const CW_API_URL = 'https://fordzap.com/cp/content/car_service/modules/calendar/Calendar.php'; // ИЗМЕНИТЕ ЭТОТ ПУТЬ!
        
        // Состояние календаря
        const CWState = {
            serviceId: <?php echo $serviceId; ?>,
            view: 'week',
            currentDate: new Date().toISOString().split('T')[0],
            selectedPost: null,
            data: null,
            isLoading: false
        };

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            CWState.selectedPost = null; // Будет установлен после загрузки данных
            cwLoadData();
        });

        // Загрузка данных
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
                    
                    // Если первый пост не выбран, выбираем первый
                    if (CWState.view === 'day' && !CWState.selectedPost && data.posts && data.posts.length > 0) {
                        CWState.selectedPost = data.posts[0].id;
                        cwLoadData(); // Перезагружаем с выбранным постом
                        return;
                    }
                    
                    cwRenderHeader(data);
                    cwRenderStats(data.stats);
                    cwRenderPosts(data);
                    cwRenderCalendar(data);
                })
                .catch(error => {
                    container.innerHTML = '<div class="cw_loading">Ошибка загрузки: ' + error + '</div>';
                    CWState.isLoading = false;
                });
        }

        // Отрисовка шапки
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

        // Форматирование текущей даты
        function cwFormatCurrentDate(data) {
            const d = new Date(data.current_date + 'T12:00:00');
            
            switch (data.view) {
                case 'month':
                    return d.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' });
                case 'week':
                    if (data.data.week_start && data.data.week_end) {
                        const start = new Date(data.data.week_start + 'T12:00:00');
                        const end = new Date(data.data.week_end + 'T12:00:00');
                        return `${start.getDate()} ${start.toLocaleDateString('ru-RU', { month: 'short' })} - ${end.getDate()} ${end.toLocaleDateString('ru-RU', { month: 'short' })} ${d.getFullYear()}`;
                    }
                    return 'Неделя';
                case 'day':
                    return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
                default:
                    return '';
            }
        }

        // Отрисовка статистики
        function cwRenderStats(stats) {
            if (!stats) return;
            
            const container = document.querySelector('.cw_stats_grid');
            container.innerHTML = `
                <div class="cw_stat_card">
                    <div class="cw_stat_label">Записей</div>
                    <div class="cw_stat_value">${stats.total_records || 0}</div>
                    <div class="cw_stat_sub">за период</div>
                </div>
                <div class="cw_stat_card">
                    <div class="cw_stat_label">Сумма</div>
                    <div class="cw_stat_value">${(stats.total_sum || 0).toLocaleString()} ₽</div>
                </div>
                <div class="cw_stat_card">
                    <div class="cw_stat_label">Нормо-часы</div>
                    <div class="cw_stat_value">${(stats.total_hours || 0).toFixed(1)}</div>
                </div>
                <div class="cw_stat_card">
                    <div class="cw_stat_label">Клиентов</div>
                    <div class="cw_stat_value">${stats.unique_clients || 0}</div>
                    <div class="cw_stat_sub">авто: ${stats.unique_cars || 0}</div>
                </div>
            `;
        }

        // Отрисовка постов
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
                
                html += `
                    <div class="cw_post_item ${isActive ? 'cw_active' : ''}" onclick="cwSelectPost(${post.id})">
                        <img class="cw_post_icon" src="${post.image || ''}" alt="" onerror="this.style.display='none'">
                        <span class="cw_post_name">${post.caption}</span>
                        <span class="cw_post_count">${count}</span>
                    </div>
                `;
            });
            
            postsBar.innerHTML = html;
        }

        // Отрисовка календаря
        function cwRenderCalendar(data) {
            const container = document.querySelector('.cw_calendar_container');
            
            switch (data.view) {
                case 'month':
                    container.innerHTML = cwRenderMonth(data.data);
                    break;
                case 'week':
                    container.innerHTML = cwRenderWeek(data.data);
                    break;
                case 'day':
                    container.innerHTML = cwRenderDay(data.data);
                    break;
                default:
                    container.innerHTML = '';
            }
        }

        // Отрисовка месяца
        function cwRenderMonth(data) {
            if (!data || !data.days) return '<div class="cw_loading">Нет данных</div>';
            
            const daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
            let html = '<div class="cw_month_grid">';
            
            // Заголовки дней недели
            daysOfWeek.forEach(day => {
                html += `<div style="background: #f9fafb; padding: 10px; text-align: center; font-weight: 500;">${day}</div>`;
            });
            
            // Определяем первый день месяца
            const firstDay = new Date(data.year, parseInt(data.month.split('-')[1]) - 1, 1);
            const startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
            
            // Пустые ячейки
            for (let i = 0; i < startOffset; i++) {
                html += '<div class="cw_month_cell" style="background: #f9fafb;"></div>';
            }
            
            // Дни месяца
            for (let day = 1; day <= Object.keys(data.days).length; day++) {
                const dayStr = `${data.year}-${data.month.split('-')[1]}-${String(day).padStart(2, '0')}`;
                const dayData = data.days[dayStr];
                const isToday = dayStr === new Date().toISOString().split('T')[0];
                
                if (dayData) {
                    html += `
                        <div class="cw_month_cell ${isToday ? 'cw_today' : ''}" onclick="cwGoToDay('${dayStr}')">
                            <div class="cw_month_day_num">${day}</div>
                            <div class="cw_month_day_content">
                                ${dayData.is_working ? `
                                    <div>📅 ${dayData.records_count} зап.</div>
                                    <div>💰 ${dayData.sum.toLocaleString()} ₽</div>
                                    <div style="color: #10b981;">🟢 ${dayData.free_slots} свободно</div>
                                ` : '<div style="color: #9ca3af;">Выходной</div>'}
                            </div>
                        </div>
                    `;
                } else {
                    html += `<div class="cw_month_cell"><div class="cw_month_day_num">${day}</div></div>`;
                }
            }
            
            html += '</div>';
            return html;
        }

        // Отрисовка недели
        function cwRenderWeek(data) {
            if (!data || !data.days) return '<div class="cw_loading">Нет данных</div>';
            
            const daysOfWeek = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
            let html = '<div class="cw_week_grid">';
            
            Object.keys(data.days).sort().forEach((dayStr, index) => {
                const day = data.days[dayStr];
                const isToday = dayStr === new Date().toISOString().split('T')[0];
                
                html += `
                    <div class="cw_week_day ${isToday ? 'cw_today' : ''}" onclick="cwGoToDay('${dayStr}')">
                        <div class="cw_day_header">
                            <span class="cw_day_name">${daysOfWeek[index]}</span>
                            <span class="cw_day_date">${dayStr.split('-')[2]}</span>
                        </div>
                        ${day.is_working ? `
                            <div class="cw_day_stats">
                                <div class="cw_day_records">📅 ${day.records_count} записей</div>
                                <div class="cw_day_free">🟢 ${day.free_slots} свободно</div>
                                <div style="margin-top: 8px; font-size: 12px;">💰 ${day.sum.toLocaleString()} ₽</div>
                            </div>
                        ` : `
                            <div class="cw_day_nonworking">
                                Выходной
                            </div>
                        `}
                    </div>
                `;
            });
            
            html += '</div>';
            return html;
        }

        
		// Отрисовка дня
		function cwRenderDay(data) {
			if (!data || !data.slots) return '<div class="cw_loading">Нет данных</div>';
			
			let html = '<div class="cw_day_view">';
			
			Object.keys(data.slots).sort().forEach(time => {
				const slot = data.slots[time];
				const record = data.records && data.records[time] ? data.records[time] : null;
				
				// Формируем атрибут для перехода к заказу, если есть запись и есть orders_ids
				// ИСПРАВЛЕНО: используем record.orders_ids вместо record.id
				const orderLink = record && record.orders_ids ? 
					`onclick="window.open('https://fordzap.com/cp/shop/orders/order?order_id=${record.orders_ids}', '_blank')"` : '';
				// Курсор будет pointer только для занятых слотов
				const cursorStyle = record ? 'cursor: pointer;' : '';
				
				html += `
					<div class="cw_time_slot">
						<div class="cw_time_label">${time}</div>
						<div class="cw_slot_content ${record ? 'cw_has_record' : ''}" 
							 style="${cursorStyle}"
							 ${orderLink}>
							${record ? `
								<div class="cw_record_info">
									<span class="cw_record_client">${record.client_name || 'Клиент'}</span>
									<span class="cw_record_car">${record.car_info || ''}</span>
									<span class="cw_record_worker">${record.worker_name || ''}</span>
								</div>
								${record.services && record.services.length > 0 ? `
									<div class="cw_record_services">
										${record.services.map(s => `${s.caption} (${s.count} шт.)`).join(', ')}
									</div>
								` : ''}
								<div class="cw_record_price">
									${record.total_sum ? record.total_sum.toLocaleString() + ' ₽' : ''}
								</div>
							` : 'Свободно'}
						</div>
					</div>
				`;
			});
			
			html += '</div>';
			return html;
		}

        // Навигация
        function cwNavigate(direction) {
            const current = new Date(CWState.currentDate + 'T12:00:00');
            
            switch (CWState.view) {
                case 'month':
                    current.setMonth(current.getMonth() + (direction === 'next' ? 1 : -1));
                    break;
                case 'week':
                    current.setDate(current.getDate() + (direction === 'next' ? 7 : -7));
                    break;
                case 'day':
                    current.setDate(current.getDate() + (direction === 'next' ? 1 : -1));
                    break;
            }
            
            CWState.currentDate = current.toISOString().split('T')[0];
            cwLoadData();
        }

        // Установка вида
        function cwSetView(view) {
            CWState.view = view;
            CWState.selectedPost = null;
            cwLoadData();
        }

        // Выбор поста
        function cwSelectPost(postId) {
            CWState.selectedPost = postId;
            cwLoadData();
        }

        // Переход к конкретному дню
        function cwGoToDay(dateStr) {
            CWState.view = 'day';
            CWState.currentDate = dateStr;
            cwLoadData();
        }
    </script>  