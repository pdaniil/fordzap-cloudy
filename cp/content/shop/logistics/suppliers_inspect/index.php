<?php // Этот файл только вывод интерфейса ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр позиций поставщиков</title>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Подключаем jQuery 1.12.4 только если она ещё не загружена (для совместимости с Bootstrap 3) -->
    <script>
        if (typeof jQuery === 'undefined') {
            document.write('<script src="https://code.jquery.com/jquery-1.12.4.min.js"><\/script>');
        }
    </script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Базовые стили (как ранее) с небольшими изменениями */
        .si-wrapper {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }
        .si-wrapper * { box-sizing: border-box; }
        .si-wrapper .si-container {
            max-width: 1400px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            padding: 24px 30px 30px;
        }
        .si-wrapper .si-title {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1a2634;
        }
        .si-wrapper .si-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 16px 24px;
            margin-bottom: 25px;
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 10px;
            border: 1px solid #e9edf4;
        }
        .si-wrapper .si-filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 150px;
        }
        .si-wrapper .si-filter-group label {
            font-size: 13px;
            font-weight: 500;
            color: #5a6a7a;
            letter-spacing: 0.3px;
        }
        .si-wrapper .si-filter-group select,
        .si-wrapper .si-filter-group input {
            padding: 8px 12px;
            border: 1px solid #d0d7e2;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
            transition: border-color 0.2s;
            outline: none;
            width: 100%;
        }
        .si-wrapper .si-filter-group select:focus,
        .si-wrapper .si-filter-group input:focus {
            border-color: #4a90d9;
            box-shadow: 0 0 0 3px rgba(74,144,217,0.15);
        }
        .si-wrapper .si-filter-group input[type="date"] {
            min-width: 150px;
        }
        .si-wrapper .si-filter-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 6px;
        }
        .si-wrapper .si-filter-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .si-wrapper .si-filter-checkbox label {
            font-size: 14px;
            font-weight: 500;
            color: #2c3e4e;
            cursor: pointer;
        }
        .si-wrapper .si-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            margin-bottom: 22px;
            border-bottom: 2px solid #e9edf4;
            padding-bottom: 6px;
        }
        .si-wrapper .si-tab {
            padding: 10px 22px;
            border-radius: 30px;
            background: #f0f3f8;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
            color: #3d4f5f;
            border: 1px solid transparent;
            user-select: none;
        }
        .si-wrapper .si-tab:hover { background: #e4eaf2; }
        .si-wrapper .si-tab.active {
            background: #4a90d9;
            color: #fff;
            border-color: #4a90d9;
            box-shadow: 0 4px 10px rgba(74,144,217,0.25);
        }
        .si-wrapper .si-table-wrapper {
            overflow-x: auto;
            margin-top: 6px;
        }
        .si-wrapper .si-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .si-wrapper .si-table thead th {
            background: #f2f5f9;
            text-align: left;
            padding: 14px 16px;
            font-weight: 600;
            color: #2c3e4e;
            border-bottom: 2px solid #dde3ec;
            white-space: nowrap;
        }
        .si-wrapper .si-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }
        .si-wrapper .si-table tbody tr.si-main-row {
            cursor: pointer;
            transition: background 0.15s;
        }
        .si-wrapper .si-table tbody tr.si-main-row:hover {
            background: #f8faff;
        }
        .si-wrapper .si-table tbody tr.si-main-row.expanded {
            background: #f0f7ff;
        }
        .si-wrapper .si-storage-id {
            font-weight: 500;
            color: #4a6a8a;
        }
        .si-wrapper .si-storage-name {
            font-weight: 500;
        }
        .si-wrapper .si-num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .si-wrapper .si-num strong {
            font-weight: 600;
        }
        .si-wrapper .si-expand-icon {
            font-size: 18px;
            transition: transform 0.25s;
            display: inline-block;
            width: 24px;
            text-align: center;
        }
        .si-wrapper .si-main-row.expanded .si-expand-icon {
            transform: rotate(90deg);
        }
        .si-wrapper .si-detail-row td {
            padding: 0 16px 16px 16px;
            background: #fafcff;
        }
        .si-wrapper .si-detail-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            box-shadow: inset 0 1px 4px rgba(0,0,0,0.04);
        }
        .si-wrapper .si-detail-table th {
            background: #f2f5f9;
            padding: 10px 14px;
            font-weight: 600;
            color: #2c3e4e;
            border-bottom: 1px solid #dde3ec;
            text-align: left;
            font-size: 13px;
        }
        .si-wrapper .si-detail-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #eef2f7;
            font-size: 13px;
        }
        .si-wrapper .si-detail-table tr:last-child td {
            border-bottom: none;
        }
        .si-wrapper .si-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #fff;
            background: #aaa;
            white-space: nowrap;
        }
        .si-wrapper .si-btn-order {
            display: inline-block;
            background: #4a90d9;
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }
        .si-wrapper .si-btn-order:hover {
            background: #357abd;
        }
        .si-wrapper .si-loading {
            text-align: center;
            padding: 30px;
            color: #7a8a9a;
        }
        .si-wrapper .si-empty {
            text-align: center;
            padding: 30px;
            color: #7a8a9a;
            font-style: italic;
        }
        .si-wrapper .si-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(74,144,217,0.2);
            border-radius: 50%;
            border-top-color: #4a90d9;
            animation: si-spin 0.7s linear infinite;
        }
        @keyframes si-spin {
            to { transform: rotate(360deg); }
        }

        /* Адаптивные стили для мобильных устройств */
        @media (max-width: 1200px) {
            .si-wrapper .si-container { padding: 16px; }
            .si-wrapper .si-filters { flex-direction: column; align-items: stretch; }
            .si-wrapper .si-filter-group { min-width: auto; }
            .si-wrapper .si-tabs { gap: 6px; }
            .si-wrapper .si-tab { padding: 8px 16px; font-size: 13px; }

            /* Превращаем таблицу в карточки */
            .si-wrapper .si-table,
            .si-wrapper .si-table thead,
            .si-wrapper .si-table tbody,
            .si-wrapper .si-table tr,
            .si-wrapper .si-table td,
            .si-wrapper .si-table th {
                display: block;
            }
            .si-wrapper .si-table thead {
                display: none; /* скрываем заголовки */
            }
            .si-wrapper .si-table tbody tr {
                display: block;
                margin-bottom: 16px;
                border: 1px solid #e0e6ed;
                border-radius: 8px;
                padding: 12px 14px;
                background: #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            }
            .si-wrapper .si-table tbody tr.si-main-row {
                cursor: pointer;
            }
            .si-wrapper .si-table tbody tr.si-main-row.expanded {
                background: #f0f7ff;
                border-color: #4a90d9;
            }
            /* Каждая ячейка как строка с подписью */
            .si-wrapper .si-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px dashed #eceff5;
                text-align: left;
                background: transparent;
            }
            .si-wrapper .si-table tbody td:last-child {
                border-bottom: none;
            }
            /* Псевдоэлемент для подписи */
            .si-wrapper .si-table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #5a6a7a;
                margin-right: 12px;
                font-size: 13px;
                flex-shrink: 0;
            }
            /* Выравнивание чисел вправо на мобильных (как в десктопе) */
            .si-wrapper .si-table tbody td.si-num {
                justify-content: flex-end;
            }
            .si-wrapper .si-table tbody td.si-num::before {
                margin-right: auto;
            }
            /* Иконка раскрытия – убираем подпись, оставляем только иконку */
            .si-wrapper .si-table tbody td:first-child {
                justify-content: flex-start;
                border-bottom: none;
                padding-bottom: 4px;
            }
            .si-wrapper .si-table tbody td:first-child::before {
                content: '';
                display: none;
            }

            /* Строки деталей – адаптируем вложенную таблицу */
            .si-wrapper .si-detail-row td {
                padding: 10px 14px;
            }
            .si-wrapper .si-detail-table,
            .si-wrapper .si-detail-table thead,
            .si-wrapper .si-detail-table tbody,
            .si-wrapper .si-detail-table tr,
            .si-wrapper .si-detail-table td,
            .si-wrapper .si-detail-table th {
                display: block;
            }
            .si-wrapper .si-detail-table thead {
                display: none;
            }
            .si-wrapper .si-detail-table tbody tr {
                display: block;
                margin-bottom: 12px;
                padding: 10px 12px;
                background: #f8fafc;
                border-radius: 6px;
                border: 1px solid #e9edf4;
            }
            .si-wrapper .si-detail-table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 6px 0;
                border-bottom: 1px dashed #e9edf4;
                text-align: left;
                background: transparent;
            }
            .si-wrapper .si-detail-table tbody td:last-child {
                border-bottom: none;
            }
            .si-wrapper .si-detail-table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #5a6a7a;
                margin-right: 12px;
                font-size: 12px;
                flex-shrink: 0;
            }
            .si-wrapper .si-detail-table tbody td.si-num {
                justify-content: flex-end;
            }
            .si-wrapper .si-detail-table tbody td.si-num::before {
                margin-right: auto;
            }
            /* Действие – кнопка */
            .si-wrapper .si-detail-table tbody td:last-child {
                justify-content: center;
            }
            .si-wrapper .si-detail-table tbody td:last-child::before {
                display: none;
            }
            .si-wrapper .si-btn-order {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="si-wrapper">
    <div class="si-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
			<h1 class="si-title" style="margin-bottom: 0;">📦 Позиции поставщиков в заказах</h1>
			<button id="siPrintBtn" class="si-btn-order" style="background: #28a745; padding: 8px 20px; font-size: 14px;">🖨️ Печать</button>
		</div>

        <div class="si-filters" id="siFilters">
            <div class="si-filter-group">
                <label for="siStatusFilter">Статус позиции</label>
                <select id="siStatusFilter" multiple="multiple" style="width: 100%; min-width: 200px;">
                    <!-- опции загружаются через JS -->
                </select>
            </div>
            <div class="si-filter-group">
                <label for="siDateFrom">Дата с</label>
                <input type="date" id="siDateFrom">
            </div>
            <div class="si-filter-group">
                <label for="siDateTo">Дата по</label>
                <input type="date" id="siDateTo">
            </div>
            <div class="si-filter-group">
                <div class="si-filter-checkbox">
                    <input type="checkbox" id="siForWarehouse">
                    <label for="siForWarehouse">Оприходование</label>
                </div>
            </div>
        </div>

        <div class="si-tabs" id="siTabsContainer"></div>

        <div class="si-table-wrapper">
            <table class="si-table" id="siMainTable">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th>ID склада</th>
                        <th>Название склада</th>
                        <th class="si-num">Всего позиций</th>
                        <th class="si-num">Общая сумма</th>
                        <th class="si-num">Отфильтровано</th>
                        <th class="si-num">Сумма по фильтру</th>
                        <th class="si-num">Разница</th>
                    </tr>
                </thead>
                <tbody id="siTableBody">
                    <tr><td colspan="8" class="si-loading"><div class="si-spinner"></div> Загрузка...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const AJAX_URL = 'https://fordzap.com/cp/content/shop/logistics/suppliers_inspect/ajax.php';

    let siCurrentOffice = 0;
    let siExpandedStorageId = null;

    document.addEventListener('DOMContentLoaded', function() {
		document.getElementById('siPrintBtn').addEventListener('click', siPrint);
        const today = new Date();
        const monthAgo = new Date();
        monthAgo.setMonth(monthAgo.getMonth() - 1);
        const formatDate = (d) => d.toISOString().split('T')[0];
        document.getElementById('siDateFrom').value = formatDate(monthAgo);
        document.getElementById('siDateTo').value = formatDate(today);

        // Загрузка статусов
        fetch(AJAX_URL + '?action=statuses')
            .then(r => r.json())
            .then(res => {
                if (res.status) {
                    const sel = document.getElementById('siStatusFilter');
                    res.data.forEach(st => {
                        const opt = document.createElement('option');
                        opt.value = st.id;
                        opt.textContent = st.name;
                        sel.appendChild(opt);
                    });

                    // Инициализация Select2
                    $('#siStatusFilter').select2({
                        placeholder: 'Выберите статусы',
                        allowClear: true,
                        width: '100%',
                        closeOnSelect: false
                    });

                    // (опционально) выбрать все по умолчанию:
                    // $('#siStatusFilter').val(res.data.map(st => st.id)).trigger('change');
                }
            });

        // Загрузка офисов
        fetch(AJAX_URL + '?action=offices')
            .then(r => r.json())
            .then(res => {
                if (res.status && res.data.length) {
                    const tabs = document.getElementById('siTabsContainer');
                    res.data.forEach((off, idx) => {
                        const div = document.createElement('div');
                        div.className = 'si-tab' + (idx === 0 ? ' active' : '');
                        div.dataset.id = off.id;
                        div.textContent = off.caption;
                        div.addEventListener('click', function() {
                            document.querySelectorAll('.si-tab').forEach(t => t.classList.remove('active'));
                            this.classList.add('active');
                            siCurrentOffice = parseInt(this.dataset.id);
                            if (siExpandedStorageId !== null) {
                                siCloseDetails(siExpandedStorageId);
                            }
                            siLoadData();
                        });
                        tabs.appendChild(div);
                    });
                    siCurrentOffice = parseInt(res.data[0].id);
                    siLoadData();
                } else {
                    document.getElementById('siTableBody').innerHTML = '<tr><td colspan="8" class="si-empty">Нет доступных офисов</td></tr>';
                }
            });

        // Обработчики изменения фильтров
        $('#siStatusFilter').on('change', function() {
            if (siExpandedStorageId !== null) siCloseDetails(siExpandedStorageId);
            siLoadData();
        });
        document.getElementById('siDateFrom').addEventListener('change', function() {
            if (siExpandedStorageId !== null) siCloseDetails(siExpandedStorageId);
            siLoadData();
        });
        document.getElementById('siDateTo').addEventListener('change', function() {
            if (siExpandedStorageId !== null) siCloseDetails(siExpandedStorageId);
            siLoadData();
        });
        document.getElementById('siForWarehouse').addEventListener('change', function() {
            if (siExpandedStorageId !== null) siCloseDetails(siExpandedStorageId);
            siLoadData();
        });
    });

    function siLoadData() {
        const office = siCurrentOffice;
        const selectedStatuses = $('#siStatusFilter').val() || []; // массив выбранных значений
        const dateFrom = document.getElementById('siDateFrom').value;
        const dateTo = document.getElementById('siDateTo').value;
        const forWarehouse = document.getElementById('siForWarehouse').checked ? 1 : -1;

        const tbody = document.getElementById('siTableBody');
        tbody.innerHTML = '<tr><td colspan="8" class="si-loading"><div class="si-spinner"></div> Загрузка...</td></tr>';

        if (!office) {
            tbody.innerHTML = '<tr><td colspan="8" class="si-empty">Выберите офис</td></tr>';
            return;
        }

        const params = new URLSearchParams({
            action: 'data',
            office_id: office,
            date_from: dateFrom,
            date_to: dateTo,
            for_warehouse: forWarehouse
        });
        selectedStatuses.forEach(st => params.append('status[]', st));

        fetch(AJAX_URL + '?' + params.toString())
            .then(r => r.json())
            .then(res => {
                if (!res.status) {
                    tbody.innerHTML = `<tr><td colspan="8" class="si-empty">Ошибка: ${res.message || 'Неизвестная'}</td></tr>`;
                    return;
                }
                const data = res.data;
                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="si-empty">Нет данных для выбранных фильтров</td></tr>';
                    return;
                }

                // Определяем метки для подписей на мобильных
                const labels = [
                    '', // иконка
                    'ID склада',
                    'Название склада',
                    'Всего позиций',
                    'Общая сумма',
                    'Отфильтровано',
                    'Сумма по фильтру',
                    'Разница'
                ];

                let html = '';
                data.forEach(row => {
                    const storageId = row.storage_id;
                    const isExpanded = (siExpandedStorageId === storageId);
                    html += `<tr class="si-main-row ${isExpanded ? 'expanded' : ''}" data-storage-id="${storageId}">`;
                    html += `<td data-label="${labels[0]}"><span class="si-expand-icon">▶</span></td>`;
                    html += `<td data-label="${labels[1]}" class="si-storage-id">${storageId}</td>`;
                    html += `<td data-label="${labels[2]}" class="si-storage-name">${siEscapeHtml(row.storage_name)}</td>`;
                    html += `<td data-label="${labels[3]}" class="si-num"><strong>${row.total_count}</strong></td>`;
                    html += `<td data-label="${labels[4]}" class="si-num"><strong>${row.total_sum}</strong></td>`;
                    html += `<td data-label="${labels[5]}" class="si-num"><strong>${row.filtered_count}</strong></td>`;
                    html += `<td data-label="${labels[6]}" class="si-num"><strong>${row.filtered_sum}</strong></td>`;
                    html += `<td data-label="${labels[7]}" class="si-num"><strong>${row.difference}</strong></td>`;
                    html += `</tr>`;
                });
                tbody.innerHTML = html;

                document.querySelectorAll('.si-main-row').forEach(tr => {
                    tr.addEventListener('click', function(e) {
                        if (e.target.closest('.si-btn-order')) return;
                        const sid = parseInt(this.dataset.storageId);
                        siToggleDetails(sid);
                    });
                });

                if (siExpandedStorageId !== null) {
                    const mainRow = document.querySelector(`.si-main-row[data-storage-id="${siExpandedStorageId}"]`);
                    if (mainRow) {
                        siInsertDetailRow(siExpandedStorageId);
                    } else {
                        siExpandedStorageId = null;
                    }
                }
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="8" class="si-empty">Ошибка загрузки данных: ${err.message}</td></tr>`;
                console.error('siLoadData error:', err);
            });
    }
	
	function siPrint() {
		var office = siCurrentOffice;
		var statuses = $('#siStatusFilter').val() || [];
		var dateFrom = document.getElementById('siDateFrom').value;
		var dateTo = document.getElementById('siDateTo').value;
		var forWarehouse = document.getElementById('siForWarehouse').checked ? 1 : -1;

		// Формируем URL с параметрами
		var url = 'https://fordzap.com/cp/content/shop/logistics/suppliers_inspect/print.php?office_id=' + encodeURIComponent(office) +
				  '&date_from=' + encodeURIComponent(dateFrom) +
				  '&date_to=' + encodeURIComponent(dateTo) +
				  '&for_warehouse=' + encodeURIComponent(forWarehouse);
		statuses.forEach(function(st) {
			url += '&status[]=' + encodeURIComponent(st);
		});
		window.open(url, '_blank');
	}
	
    function siToggleDetails(storageId) {
        if (siExpandedStorageId === storageId) {
            siCloseDetails(storageId);
        } else {
            if (siExpandedStorageId !== null) {
                siCloseDetails(siExpandedStorageId);
            }
            siExpandedStorageId = storageId;
            const mainRow = document.querySelector(`.si-main-row[data-storage-id="${storageId}"]`);
            if (mainRow) {
                mainRow.classList.add('expanded');
            }
            siInsertDetailRow(storageId);
            siLoadDetailsForStorage(storageId);
        }
    }

    function siCloseDetails(storageId) {
        const mainRow = document.querySelector(`.si-main-row[data-storage-id="${storageId}"]`);
        if (mainRow) {
            mainRow.classList.remove('expanded');
            const next = mainRow.nextElementSibling;
            if (next && next.classList && next.classList.contains('si-detail-row')) {
                next.remove();
            }
        }
        if (siExpandedStorageId === storageId) {
            siExpandedStorageId = null;
        }
    }

    function siInsertDetailRow(storageId) {
        const mainRow = document.querySelector(`.si-main-row[data-storage-id="${storageId}"]`);
        if (!mainRow) return;
        const next = mainRow.nextElementSibling;
        if (next && next.classList && next.classList.contains('si-detail-row')) {
            return;
        }
        const detailRow = document.createElement('tr');
        detailRow.className = 'si-detail-row';
        detailRow.dataset.storageId = storageId;
        detailRow.innerHTML = `<td colspan="8"><div class="si-loading"><div class="si-spinner"></div> Загрузка деталей...</div></td>`;
        mainRow.parentNode.insertBefore(detailRow, mainRow.nextSibling);
    }

    function siLoadDetailsForStorage(storageId) {
        const detailRow = document.querySelector(`.si-detail-row[data-storage-id="${storageId}"]`);
        if (!detailRow) {
            console.warn('Detail row not found for storage', storageId);
            return;
        }

        const td = detailRow.querySelector('td');
        td.innerHTML = '<div class="si-loading"><div class="si-spinner"></div> Загрузка деталей...</div>';

        const selectedStatuses = $('#siStatusFilter').val() || [];
        const dateFrom = document.getElementById('siDateFrom').value;
        const dateTo = document.getElementById('siDateTo').value;
        const forWarehouse = document.getElementById('siForWarehouse').checked ? 1 : -1;

        const params = new URLSearchParams({
            action: 'details',
            office_id: siCurrentOffice,
            storage_id: storageId,
            date_from: dateFrom,
            date_to: dateTo,
            for_warehouse: forWarehouse
        });
        selectedStatuses.forEach(st => params.append('status[]', st));

        const url = AJAX_URL + '?' + params.toString();
        console.log('Запрос деталей:', url);

        fetch(url)
            .then(r => r.json())
            .then(res => {
                console.log('Ответ деталей:', res);
                if (!res.status) {
                    td.innerHTML = `<span style="color:#c0392b;">Ошибка: ${res.message || 'Неизвестная'}</span>`;
                    return;
                }
                const items = res.data;
                if (!items || items.length === 0) {
                    td.innerHTML = '<span style="color:#7a8a9a;">Нет позиций по фильтрам</span>';
                    return;
                }

                const detailLabels = ['Наименование', 'Дата заказа', 'Статус', 'Количество', 'Сумма', 'Действие'];
                let html = `<table class="si-detail-table"><tbody>`;
                items.forEach(item => {
                    const statusColor = item.status_color || '#aaa';
                    const statusName = item.status_name || 'Неизвестный';
                    const orderLink = `https://fordzap.com/cp/shop/orders/order?order_id=${item.order_id}`;
                    html += `<tr>`;
                    html += `<td data-label="${detailLabels[0]}">${siEscapeHtml(item.item_name || 'Без названия')}</td>`;
                    html += `<td data-label="${detailLabels[1]}">${siEscapeHtml(item.order_date || '')}</td>`;
                    html += `<td data-label="${detailLabels[2]}"><span class="si-status-badge" style="background:${statusColor};">${siEscapeHtml(statusName)}</span></td>`;
                    html += `<td data-label="${detailLabels[3]}" class="si-num">${item.count_need}</td>`;
                    html += `<td data-label="${detailLabels[4]}" class="si-num">${item.sum}</td>`;
                    html += `<td data-label="${detailLabels[5]}"><a href="${orderLink}" target="_blank" class="si-btn-order">Перейти в заказ</a></td>`;
                    html += `</tr>`;
                });
                html += `</tbody></table>`;
                td.innerHTML = html;
            })
            .catch(err => {
                td.innerHTML = `<span style="color:#c0392b;">Ошибка загрузки деталей: ${err.message}</span>`;
                console.error('siLoadDetailsForStorage error:', err);
            });
    }

    function siEscapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
</body>
</html>