<?php
/**
 * Страница «График смен» — календарь на CSS Grid, mobile first.
 * PHP 5.4+
 */
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");
$DP_Config = new DP_Config;

$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 1;
$apiUrl    = '/cp/content/car_service/ajax_shift_schedule.php';
$printUrl  = '/cp/content/car_service/shift_schedule_print.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>График смен</title>
<style>
/* ================= ИЗОЛИРОВАННЫЕ СТИЛИ (префикс cwss-) ================= */
.cwss-app {
    --cwss-bg:            #1E1E2D;
    --cwss-bg-2:          #2B2B3B;
    --cwss-bg-3:          #3A3A50;
    --cwss-bg-head:       #23233A;
    --cwss-border:        #3A3A50;
    --cwss-border-soft:   #2E2E42;
    --cwss-text:          #FFFFFF;
    --cwss-muted:         #A0A0B0;
    --cwss-dim:           #6B7280;
    --cwss-primary:       #3B82F6;
    --cwss-primary-hover: #2563eb;
    --cwss-success:       #10B981;
    --cwss-danger:        #EF4444;
    --cwss-warning:       #F59E0B;

    box-sizing: border-box;
    display: block;
    background: var(--cwss-bg);
    color: var(--cwss-text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.4;
    border-radius: 12px;
    padding: 12px;
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    overflow: hidden;
}
.cwss-app *,
.cwss-app *::before,
.cwss-app *::after { box-sizing: border-box; }

.cwss-content { display: block; }

/* ============= ПАНЕЛЬ НАСТРОЕК (тулбар + легенда) ============= */
.cwss-panel {
    display: block;
    width: 100%;
    margin-bottom: 14px;
}

/* ============= ТУЛБАР ============= */
.cwss-toolbar {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 10px;
}
.cwss-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}
.cwss-month {
    font-size: 15px;
    font-weight: 700;
    color: var(--cwss-text);
    min-width: 140px;
    text-align: center;
    text-transform: capitalize;
}
.cwss-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: center;
}

/* ============= КНОПКИ ============= */
.cwss-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 40px;
    padding: 8px 14px;
    margin: 0;
    border: 1px solid var(--cwss-border);
    border-radius: 10px;
    background: var(--cwss-bg-2);
    color: #D1D5DB;
    font-family: inherit;
    font-size: 13px;
    font-weight: 500;
    line-height: 1.2;
    text-align: center;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    user-select: none;
    -webkit-appearance: none;
    appearance: none;
    -webkit-tap-highlight-color: transparent;
    transition: background .15s, border-color .15s, color .15s;
}
.cwss-btn:hover  { background: var(--cwss-bg-3); color: #FFF; }
.cwss-btn:active { background: #45456A; }
.cwss-btn:disabled { opacity: .5; cursor: not-allowed; }
.cwss-btn--icon { width: 40px; padding: 0; font-size: 14px; }
.cwss-btn--ghost { background: transparent; }
.cwss-btn--primary { background: var(--cwss-primary); border-color: var(--cwss-primary); color: #FFF; }
.cwss-btn--primary:hover  { background: var(--cwss-primary-hover); border-color: var(--cwss-primary-hover); }
.cwss-btn--danger  { background: var(--cwss-danger); border-color: var(--cwss-danger); color: #FFF; }
.cwss-btn--danger:hover { background: #DC2626; border-color: #DC2626; }
.cwss-btn--icon-sm { width: 32px; min-height: 32px; padding: 0; font-size: 13px; }
.cwss-btn__label { display: inline; }
@media (max-width: 480px) {
    .cwss-btn__label { display: none; }
    .cwss-btn { padding: 8px 12px; }
}

/* ============= ЛЕГЕНДА ============= */
.cwss-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 14px;
    font-size: 12px;
    color: var(--cwss-muted);
}
.cwss-legend__item { display: inline-flex; align-items: center; gap: 6px; }
.cwss-legend__dot {
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
}

/* ============= КАЛЕНДАРЬ ============= */
.cwss-cal-outer {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
}
.cwss-cal-wrap {
    background: var(--cwss-bg-2);
    border: 1px solid var(--cwss-border);
    border-radius: 14px;
    padding: 10px;
    overflow: hidden;
}
.cwss-cal {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--cwss-border-soft);
    border-radius: 8px;
    overflow: hidden;
}
.cwss-cal > * {
    background: var(--cwss-bg);
    min-width: 0;
}

/* Шапка (дни недели) */
.cwss-cal__dow {
    background: var(--cwss-bg-head);
    color: var(--cwss-muted);
    font-weight: 600;
    font-size: 10px;
    padding: 8px 2px;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.cwss-cal__dow--weekend { color: var(--cwss-warning); }

/* Ячейка */
.cwss-cal__cell {
    position: relative;
    background: var(--cwss-bg);
    cursor: pointer;
    overflow: hidden;
    transition: background .15s;
}
/* Пропорции ячейки: padding-top задаёт соотношение сторон.
   На мобильных — квадрат (100%), на десктопе — прямоугольник (75%, 65%).
   Работает везде, включая IE11. */
.cwss-cal__cell::before {
    content: '';
    display: block;
    padding-top: 100%;
}
.cwss-cal__cell:hover { background: rgba(255,255,255,.06); }
.cwss-cal__cell--empty {
    background: rgba(0,0,0,.25);
    cursor: default;
}
.cwss-cal__cell--empty:hover { background: rgba(0,0,0,.25); }
.cwss-cal__cell--today {
    box-shadow: inset 0 0 0 2px var(--cwss-warning);
}

.cwss-cal__inner {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    padding: 3px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.cwss-cal__daynum {
    flex-shrink: 0;
    font-size: 10px;
    font-weight: 600;
    line-height: 1;
    color: var(--cwss-muted);
}
.cwss-cal__cell--today .cwss-cal__daynum { color: var(--cwss-warning); }

.cwss-cal__workers {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    gap: 1px;
    margin-top: 2px;
    overflow: hidden;
    min-height: 0;
}
.cwss-cal__worker {
    flex-shrink: 0;
    font-size: 9px;
    line-height: 1.1;
    padding: 1px 3px;
    border-radius: 3px;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cwss-cal__worker--work     { background: rgba(16,185,129,.22); color: var(--cwss-success); }
.cwss-cal__worker--vacation { background: rgba(59,130,246,.25); color: var(--cwss-primary); }
.cwss-cal__worker--sick     { background: rgba(239,68,68,.22);  color: var(--cwss-danger); }
.cwss-cal__worker--holiday  { background: rgba(245,158,11,.22); color: var(--cwss-warning); }

/* «+N» прижимается к низу ячейки — виден всегда */
.cwss-cal__more {
    flex-shrink: 0;
    margin-top: auto;
    font-size: 9px;
    line-height: 1.1;
    padding: 1px 3px;
    color: var(--cwss-dim);
    font-weight: 600;
    text-align: right;
}

.cwss-loading {
    padding: 40px 16px;
    text-align: center;
    color: var(--cwss-muted);
    font-size: 13px;
}
.cwss-loading--error { color: var(--cwss-danger); }

/* ============= ОВЕРЛЕЙ + МОДАЛКИ ============= */
.cwss-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,.6);
    z-index: 999998;
}
.cwss-overlay.cwss-open { display: block; }

.cwss-modal {
    display: none;
    position: fixed;
    left: 0; right: 0; bottom: 0;
    z-index: 999999;
    background: var(--cwss-bg-2);
    color: var(--cwss-text);
    border: 1px solid var(--cwss-border);
    border-radius: 16px 16px 0 0;
    padding: 16px 16px 20px;
    max-height: 90vh;
    overflow-y: auto;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.4;
    -webkit-overflow-scrolling: touch;
}
.cwss-modal.cwss-open { display: block; }
.cwss-modal__title { font-size: 16px; font-weight: 700; margin: 0 0 4px; color: var(--cwss-text); }
.cwss-modal__sub { font-size: 12px; color: var(--cwss-muted); margin: 0 0 14px; }

.cwss-field { margin-bottom: 14px; }
.cwss-field__label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--cwss-muted);
    margin-bottom: 6px;
}

.cwss-input,
.cwss-select {
    display: block;
    width: 100%;
    margin: 0;
    padding: 10px 12px;
    min-height: 40px;
    border: 1px solid var(--cwss-border);
    border-radius: 10px;
    background: var(--cwss-bg);
    color: var(--cwss-text);
    font-family: inherit;
    font-size: 14px;
    line-height: 1.2;
    outline: none;
    -webkit-appearance: none;
    appearance: none;
    -webkit-tap-highlight-color: transparent;
    transition: border-color .15s, box-shadow .15s;
}
.cwss-input:focus,
.cwss-select:focus {
    border-color: var(--cwss-primary);
    box-shadow: 0 0 0 2px rgba(59,130,246,.3);
}
.cwss-select {
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23A0A0B0' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    padding-right: 38px;
}
.cwss-input[type=color] { padding: 2px; height: 40px; cursor: pointer; }
.cwss-input[type=date] { color-scheme: dark; }

.cwss-row { display: flex; flex-direction: column; gap: 10px; }
.cwss-row > .cwss-field { flex: 1 1 auto; margin-bottom: 0; }

.cwss-checks {
    max-height: 240px;
    overflow-y: auto;
    border: 1px solid var(--cwss-border);
    border-radius: 10px;
    background: var(--cwss-bg);
    padding: 4px 12px;
    -webkit-overflow-scrolling: touch;
}
.cwss-check {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    font-size: 14px;
    color: var(--cwss-text);
    cursor: pointer;
    user-select: none;
}
.cwss-check input[type=checkbox] {
    width: 18px; height: 18px; margin: 0;
    flex-shrink: 0;
    accent-color: var(--cwss-primary);
    cursor: pointer;
}
.cwss-check--all {
    border-bottom: 1px solid var(--cwss-border-soft);
    padding-bottom: 10px;
    margin-bottom: 4px;
}

/* ============= СПИСОК СОТРУДНИКОВ В МОДАЛКЕ ДНЯ ============= */
.cwss-day-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 6px;
}
.cwss-day-quick { display: flex; gap: 4px; }
.cwss-day-quick .cwss-btn {
    min-height: 26px;
    padding: 2px 10px;
    font-size: 11px;
    border-radius: 6px;
}

.cwss-day-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--cwss-border);
    border-radius: 10px;
    background: var(--cwss-bg);
    padding: 4px;
    -webkit-overflow-scrolling: touch;
}
.cwss-wrow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    user-select: none;
    margin-bottom: 2px;
    border: 1px solid transparent;
    transition: background .15s, border-color .15s, box-shadow .15s;
    -webkit-tap-highlight-color: transparent;
}
.cwss-wrow:last-child { margin-bottom: 0; }
.cwss-wrow:hover { background: rgba(255,255,255,.04); }
.cwss-wrow--sel {
    background: rgba(59,130,246,.14);
    border-color: var(--cwss-primary);
    box-shadow: 0 0 0 1px var(--cwss-primary) inset;
}
.cwss-wrow__name {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 14px;
    color: var(--cwss-text);
}
.cwss-wrow__status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    flex-shrink: 0;
}

.cwss-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.cwss-chip {
    flex: 1 1 auto;
    min-width: calc(50% - 3px);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 8px;
    border: 2px solid var(--cwss-border);
    border-radius: 10px;
    background: var(--cwss-bg);
    color: #D1D5DB;
    font-family: inherit;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    user-select: none;
    -webkit-appearance: none;
    appearance: none;
    transition: border-color .15s, background .15s, color .15s;
}
.cwss-chip:hover { border-color: var(--cwss-primary); color: #FFF; }
.cwss-chip .cwss-legend__dot { width: 8px; height: 8px; }

.cwss-modal__actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
}
.cwss-modal__actions .cwss-btn { width: 100%; }

.cwss-tpl-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 260px;
    overflow-y: auto;
    margin-bottom: 14px;
    -webkit-overflow-scrolling: touch;
}
.cwss-tpl-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: var(--cwss-bg);
    border: 1px solid var(--cwss-border);
    border-radius: 10px;
}
.cwss-tpl-item__swatch {
    width: 12px; height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}
.cwss-tpl-item__body { flex: 1; min-width: 0; font-size: 14px; }
.cwss-tpl-item__body small {
    display: block;
    color: var(--cwss-dim);
    font-size: 11px;
    margin-top: 2px;
}
.cwss-badge {
    display: inline-block;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 8px;
    background: var(--cwss-bg-3);
    color: var(--cwss-muted);
    vertical-align: middle;
    font-weight: 500;
}

/* ============= TABLET (≥ 640px) ============= */
@media (min-width: 640px) {
    .cwss-app { padding: 16px; border-radius: 14px; }
    .cwss-toolbar {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }
    .cwss-nav { justify-content: flex-start; }
    .cwss-actions { justify-content: flex-end; }
    .cwss-month { font-size: 16px; min-width: 180px; }

    .cwss-cal-wrap { padding: 14px; }

    /* прямоугольные ячейки — шире, чем выше */
    .cwss-cal__cell::before { padding-top: 75%; }

    .cwss-cal__dow { font-size: 12px; padding: 10px 4px; }
    .cwss-cal__inner { padding: 5px; }
    .cwss-cal__daynum { font-size: 12px; }
    .cwss-cal__workers { gap: 2px; margin-top: 3px; }
    .cwss-cal__worker { font-size: 11px; padding: 2px 5px; border-radius: 4px; }
    .cwss-cal__more   { font-size: 10px; padding: 2px 5px; }

    .cwss-modal {
        left: 50%; right: auto; top: 50%; bottom: auto;
        transform: translate(-50%, -50%);
        width: 92%; max-width: 560px;
        border-radius: 16px;
        padding: 22px;
        max-height: 88vh;
    }
    .cwss-modal__actions { flex-direction: row; }
    .cwss-modal__actions .cwss-btn { width: auto; flex: 1; }

    .cwss-row { flex-direction: row; }
    .cwss-chip { min-width: auto; flex: 1 1 0; }
}

/* ============= DESKTOP (≥ 1024px) ============= */
@media (min-width: 1024px) {
    .cwss-app { padding: 20px; }
    .cwss-cal-outer { max-width: 1080px; } /* ~+20% от прежних 900px */

    /* ещё более вытянутые по ширине */
    .cwss-cal__cell::before { padding-top: 65%; }

    .cwss-cal-wrap { padding: 16px; }
    .cwss-cal__inner { padding: 6px; }
    .cwss-cal__daynum { font-size: 13px; }
    .cwss-cal__worker { font-size: 12px; padding: 3px 6px; }
    .cwss-cal__more   { font-size: 11px; }
    .cwss-cal__dow { font-size: 13px; padding: 12px 4px; }
}
</style>
</head>
<body>

<div class="cwss-app" id="cwss-app-root"></div>

<script>
(function () {
    'use strict';

    var SHIFT_TYPES = {
        work:     { label: 'Рабочий',    color: '#10B981' },
        vacation: { label: 'Отпуск',     color: '#3B82F6' },
        sick:     { label: 'Больничный', color: '#EF4444' },
        holiday:  { label: 'Праздник',   color: '#F59E0B' }
    };
    var OFF_TYPE = 'off';

    function pad(n) { n = String(n); return n.length < 2 ? '0' + n : n; }
    function fmtDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function daysInMonth(y, m) { return new Date(y, m + 1, 0).getDate(); }
    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function shortName(fio) {
        if (!fio) return '';
        var parts = String(fio).trim().split(/\s+/);
        if (parts.length === 1) return parts[0];
        return parts[0] + ' ' + parts[1].charAt(0) + '.';
    }
    function formatDateRu(dateStr) {
        var months = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
        var d = new Date(dateStr + 'T12:00:00');
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }
    function computeMaxShown() {
        var w = window.innerWidth;
        if (w >= 1024) return 5;
        if (w >= 640)  return 3;
        return 2;
    }

    var API_URL    = <?php echo json_encode($apiUrl); ?>;
    var PRINT_URL  = <?php echo json_encode($printUrl); ?>;
    var SERVICE_ID = <?php echo (int)$serviceId; ?>;

    var state = {
        month: (function () { var n = new Date(); return new Date(n.getFullYear(), n.getMonth(), 1); })(),
        workers: [],
        templates: [],
        schedule: {}
    };

    var app = document.getElementById('cwss-app-root');

    var content = document.createElement('div');
    content.className = 'cwss-content';
    content.innerHTML = '<div class="cwss-loading">Загрузка...</div>';
    app.appendChild(content);

    var overlay = document.createElement('div');
    overlay.className = 'cwss-overlay';
    app.appendChild(overlay);

    var modalTpl   = createModal();
    var modalApply = createModal();
    var modalClear = createModal();
    var modalDay   = createModal();
    var modalPrint = createModal();
    function createModal() {
        var m = document.createElement('div');
        m.className = 'cwss-modal';
        app.appendChild(m);
        return m;
    }

    overlay.addEventListener('click', closeAllModals);

    function api(action, params, method) {
        params = params || {};
        method = method || 'POST';

        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            var url = API_URL;
            var body = null;

            if (method === 'GET') {
                var qs = 'action=' + encodeURIComponent(action);
                for (var k in params) {
                    if (!params.hasOwnProperty(k)) continue;
                    var v = params[k];
                    if (Object.prototype.toString.call(v) === '[object Array]') {
                        for (var i = 0; i < v.length; i++) {
                            qs += '&' + encodeURIComponent(k) + '[]=' + encodeURIComponent(v[i]);
                        }
                    } else if (v !== null && v !== undefined) {
                        qs += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(v);
                    }
                }
                url += '?' + qs;
                xhr.open('GET', url, true);
            } else {
                xhr.open('POST', url, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                var parts = ['action=' + encodeURIComponent(action)];
                for (var k2 in params) {
                    if (!params.hasOwnProperty(k2)) continue;
                    var v2 = params[k2];
                    if (Object.prototype.toString.call(v2) === '[object Array]') {
                        for (var j = 0; j < v2.length; j++) {
                            parts.push(encodeURIComponent(k2) + '[]=' + encodeURIComponent(v2[j]));
                        }
                    } else if (v2 !== null && v2 !== undefined) {
                        parts.push(encodeURIComponent(k2) + '=' + encodeURIComponent(v2));
                    }
                }
                body = parts.join('&');
            }

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                if (xhr.status !== 200) {
                    reject(new Error('HTTP ' + xhr.status + ': ' + xhr.responseText.slice(0, 200)));
                    return;
                }
                var data;
                try { data = JSON.parse(xhr.responseText); }
                catch (e) { reject(new Error('Некорректный ответ сервера')); return; }
                if (!data || data.status !== true) {
                    reject(new Error((data && data.message) || 'Ошибка запроса'));
                    return;
                }
                resolve(data);
            };
            xhr.onerror = function () { reject(new Error('Сеть недоступна')); };
            xhr.send(body);
        });
    }

    function loadAll() {
        content.innerHTML = '<div class="cwss-loading">Загрузка...</div>';
        Promise.all([
            api('get_workers', {}, 'GET'),
            api('get_templates', {}, 'GET')
        ]).then(function (res) {
            state.workers   = res[0].workers || [];
            state.templates = res[1].templates || [];
            return loadSchedule();
        }).then(function () {
            renderAll();
        }).catch(function (e) {
            content.innerHTML = '<div class="cwss-loading cwss-loading--error">' + esc(e.message) + '</div>';
        });
    }

    function loadSchedule() {
        if (!state.workers.length) { state.schedule = {}; return Promise.resolve(); }
        var from = periodFrom();
        var to   = periodTo();
        var wids = [];
        for (var i = 0; i < state.workers.length; i++) wids.push(state.workers[i].id);

        return api('get_schedule', {
            service_id: SERVICE_ID,
            date_from: from,
            date_to: to,
            worker_ids: wids
        }, 'GET').then(function (resp) {
            var map = {};
            var rows = resp.schedule || [];
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var wid = parseInt(row.worker_id);
                var dt  = String(row.date).slice(0, 10);
                if (!map[wid]) map[wid] = {};
                map[wid][dt] = {
                    type: row.shift_type,
                    comment: row.comment || '',
                    template_id: row.template_id ? parseInt(row.template_id) : null
                };
            }
            state.schedule = map;
        });
    }

    function periodFrom() {
        return state.month.getFullYear() + '-' + pad(state.month.getMonth() + 1) + '-01';
    }
    function periodTo() {
        return state.month.getFullYear() + '-' + pad(state.month.getMonth() + 1) + '-' +
               pad(daysInMonth(state.month.getFullYear(), state.month.getMonth()));
    }

    function renderAll() {
        var html = '';
        html += '<div class="cwss-panel">';
        html +=   renderToolbar();
        html +=   renderLegend();
        html += '</div>';
        html += renderCalendar();
        content.innerHTML = html;
        bindContent();
    }

    function renderToolbar() {
        var monthLabel = state.month.toLocaleString('ru-RU', { month: 'long', year: 'numeric' });
        monthLabel = monthLabel.charAt(0).toUpperCase() + monthLabel.slice(1);

        return '' +
            '<div class="cwss-toolbar">' +
              '<div class="cwss-nav">' +
                '<button type="button" class="cwss-btn cwss-btn--icon" data-act="prev" aria-label="Предыдущий месяц">◀</button>' +
                '<span class="cwss-month">' + esc(monthLabel) + '</span>' +
                '<button type="button" class="cwss-btn cwss-btn--icon" data-act="next" aria-label="Следующий месяц">▶</button>' +
                '<button type="button" class="cwss-btn cwss-btn--ghost" data-act="today">Сегодня</button>' +
              '</div>' +
              '<div class="cwss-actions">' +
                '<button type="button" class="cwss-btn cwss-btn--primary" data-act="apply">🎯 <span class="cwss-btn__label">Шаблон</span></button>' +
                '<button type="button" class="cwss-btn" data-act="clear">🗑 <span class="cwss-btn__label">Очистить</span></button>' +
                '<button type="button" class="cwss-btn" data-act="print">🖨️ <span class="cwss-btn__label">Печать</span></button>' +
                '<button type="button" class="cwss-btn" data-act="settings">⚙️ <span class="cwss-btn__label">Шаблоны</span></button>' +
              '</div>' +
            '</div>';
    }

    function renderLegend() {
        var html = '<div class="cwss-legend">';
        for (var key in SHIFT_TYPES) {
            if (!SHIFT_TYPES.hasOwnProperty(key)) continue;
            var t = SHIFT_TYPES[key];
            html += '<span class="cwss-legend__item">' +
                        '<span class="cwss-legend__dot" style="background:' + t.color + '"></span>' +
                        esc(t.label) +
                    '</span>';
        }
        html += '</div>';
        return html;
    }

    function renderCalendar() {
        var y = state.month.getFullYear();
        var m = state.month.getMonth();
        var days = daysInMonth(y, m);
        var todayStr = fmtDate(new Date());

        var firstDay = new Date(y, m, 1);
        var dowRaw = firstDay.getDay();
        var offset = (dowRaw === 0) ? 6 : dowRaw - 1;
        var weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

        var maxShown = computeMaxShown();

        var html = '<div class="cwss-cal-outer"><div class="cwss-cal-wrap"><div class="cwss-cal">';

        // Заголовки дней недели
        for (var i = 0; i < 7; i++) {
            var dowCls = 'cwss-cal__dow' + (i >= 5 ? ' cwss-cal__dow--weekend' : '');
            html += '<div class="' + dowCls + '">' + weekdays[i] + '</div>';
        }

        var totalCells = 42;
        if (offset + days <= 35) totalCells = 35;

        for (var c = 0; c < totalCells; c++) {
            var dayNum = c - offset + 1;
            if (dayNum < 1 || dayNum > days) {
                html += '<div class="cwss-cal__cell cwss-cal__cell--empty"></div>';
                continue;
            }

            var cd = new Date(y, m, dayNum);
            var ds = fmtDate(cd);

            // Собираем сотрудников со сменами в этот день
            var dayWorkers = [];
            for (var w = 0; w < state.workers.length; w++) {
                var worker = state.workers[w];
                var wid = parseInt(worker.id);
                var rec = (state.schedule[wid] || {})[ds];
                if (rec && rec.type && rec.type !== OFF_TYPE && SHIFT_TYPES[rec.type]) {
                    dayWorkers.push({ wid: wid, fio: worker.fio, type: rec.type });
                }
            }

            var cellCls = 'cwss-cal__cell';
            if (ds === todayStr) cellCls += ' cwss-cal__cell--today';

            var cellHtml = '<div class="' + cellCls + '" data-date="' + ds + '">';
            cellHtml +=   '<div class="cwss-cal__inner">';
            cellHtml +=     '<div class="cwss-cal__daynum">' + dayNum + '</div>';

            if (dayWorkers.length) {
                cellHtml += '<div class="cwss-cal__workers">';
                var shown = Math.min(dayWorkers.length, maxShown);
                for (var k = 0; k < shown; k++) {
                    var dw = dayWorkers[k];
                    cellHtml += '<div class="cwss-cal__worker cwss-cal__worker--' + dw.type + '" title="' +
                                    esc(dw.fio) + ' — ' + esc(SHIFT_TYPES[dw.type].label) + '">' +
                                    esc(shortName(dw.fio)) +
                                '</div>';
                }
                if (dayWorkers.length > shown) {
                    cellHtml += '<div class="cwss-cal__more">+' + (dayWorkers.length - shown) + '</div>';
                }
                cellHtml += '</div>';
            }

            cellHtml +=   '</div>';
            cellHtml += '</div>';
            html += cellHtml;
        }

        html += '</div></div></div>';
        return html;
    }

    function bindContent() {
        var btns = content.querySelectorAll('.cwss-toolbar [data-act]');
        for (var i = 0; i < btns.length; i++) {
            (function (btn) {
                btn.addEventListener('click', function () {
                    var act = btn.getAttribute('data-act');
                    if (act === 'prev')     shiftMonth(-1);
                    if (act === 'next')     shiftMonth(1);
                    if (act === 'today')    gotoToday();
                    if (act === 'apply')    openApplyModal();
                    if (act === 'clear')    openClearModal();
                    if (act === 'print')    openPrintModal();
                    if (act === 'settings') openTemplatesModal();
                });
            })(btns[i]);
        }

        var cells = content.querySelectorAll('.cwss-cal__cell[data-date]');
        for (var k = 0; k < cells.length; k++) {
            (function (cell) {
                cell.addEventListener('click', function () {
                    openDayModal(cell.getAttribute('data-date'));
                });
            })(cells[k]);
        }
    }

    function shiftMonth(delta) {
        state.month = new Date(state.month.getFullYear(), state.month.getMonth() + delta, 1);
        refresh();
    }
    function gotoToday() {
        var n = new Date();
        state.month = new Date(n.getFullYear(), n.getMonth(), 1);
        refresh();
    }
    function refresh() {
        content.innerHTML = '<div class="cwss-loading">Загрузка...</div>';
        loadSchedule().then(function () {
            renderAll();
        }).catch(function (e) { alert(e.message); });
    }

    // ===== Модалка дня (single select) =====
    function openDayModal(dateStr) {
        var before   = {}; // wid -> 'work'|'vacation'|'sick'|'holiday'|'off' (как на сервере)
        var dayState = {}; // текущее (редактируемое) состояние

        for (var i = 0; i < state.workers.length; i++) {
            var w   = state.workers[i];
            var wid = parseInt(w.id);
            var rec = (state.schedule[wid] || {})[dateStr];
            var t   = (rec && rec.type && SHIFT_TYPES[rec.type]) ? rec.type : OFF_TYPE;
            before[wid]   = t;
            dayState[wid] = t;
        }

        var selectedWid = null; // выбран ровно один сотрудник или null

        var ALL_TYPES = [
            { key: 'work',     label: 'Рабочий',    color: '#10B981' },
            { key: 'vacation', label: 'Отпуск',     color: '#3B82F6' },
            { key: 'sick',     label: 'Больничный', color: '#EF4444' },
            { key: 'holiday',  label: 'Праздник',   color: '#F59E0B' },
            { key: 'off',      label: 'Выходной',   color: '#6B7280' }
        ];

        function getTypeInfo(key) {
            for (var k = 0; k < ALL_TYPES.length; k++) {
                if (ALL_TYPES[k].key === key) return ALL_TYPES[k];
            }
            return ALL_TYPES[ALL_TYPES.length - 1];
        }

        function renderWorkers() {
            var html = '';
            for (var i = 0; i < state.workers.length; i++) {
                var w   = state.workers[i];
                var wid = parseInt(w.id);
                var st  = getTypeInfo(dayState[wid]);
                var isSel = (wid === selectedWid);
                html += '<div class="cwss-wrow' + (isSel ? ' cwss-wrow--sel' : '') +
                        '" data-wrow="' + wid + '">' +
                            '<span class="cwss-wrow__name">' + esc(w.fio) + '</span>' +
                            '<span class="cwss-wrow__status">' +
                                '<span class="cwss-legend__dot" style="background:' + st.color + '"></span>' +
                                '<span style="color:' + st.color + '">' + esc(st.label) + '</span>' +
                            '</span>' +
                        '</div>';
            }
            return html;
        }

        function renderChips() {
            var activeKey = (selectedWid !== null) ? dayState[selectedWid] : null;
            var html = '';
            for (var k = 0; k < ALL_TYPES.length; k++) {
                var t = ALL_TYPES[k];
                var active = (t.key === activeKey);
                var style = active
                    ? ' style="border-color:' + t.color + ';color:' + t.color + ';background:rgba(255,255,255,.03);"'
                    : '';
                html += '<button type="button" class="cwss-chip" data-apply-status="' + t.key + '"' + style + '>' +
                            '<span class="cwss-legend__dot" style="background:' + t.color + '"></span>' +
                            esc(t.label) +
                        '</button>';
            }
            return html;
        }

        modalDay.innerHTML =
            '<div class="cwss-modal__title">Смена</div>' +
            '<div class="cwss-modal__sub">' + esc(formatDateRu(dateStr)) + '</div>' +

            '<div class="cwss-field">' +
              '<label class="cwss-field__label">Сотрудник</label>' +
              '<div class="cwss-day-list" data-el="workers">' + renderWorkers() + '</div>' +
            '</div>' +

            '<div class="cwss-field">' +
              '<label class="cwss-field__label">Статус</label>' +
              '<div class="cwss-chips" data-el="chips">' + renderChips() + '</div>' +
            '</div>' +

            '<div class="cwss-modal__actions">' +
              '<button type="button" class="cwss-btn" data-close="1">Отмена</button>' +
              '<button type="button" class="cwss-btn cwss-btn--primary" data-submit="day">💾 Сохранить</button>' +
            '</div>';

        var workersEl = modalDay.querySelector('[data-el=workers]');
        var chipsEl   = modalDay.querySelector('[data-el=chips]');

        // Клик по сотруднику — одиночное выделение
        workersEl.addEventListener('click', function (e) {
            var row = findClosestAttr(e.target, workersEl, 'data-wrow');
            if (!row) return;
            var wid = parseInt(row.getAttribute('data-wrow'));

            if (selectedWid === wid) {
                // повторный клик — снять выделение (опционально, можно убрать)
                selectedWid = null;
            } else {
                selectedWid = wid;
            }

            workersEl.innerHTML = renderWorkers();
            chipsEl.innerHTML   = renderChips();
        });

        // Клик по чипу статуса — применяем к выделенному сотруднику
        chipsEl.addEventListener('click', function (e) {
            var btn = findClosestAttr(e.target, chipsEl, 'data-apply-status');
            if (!btn) return;
            if (selectedWid === null) { alert('Сначала выберите сотрудника'); return; }

            dayState[selectedWid] = btn.getAttribute('data-apply-status');

            workersEl.innerHTML = renderWorkers();
            chipsEl.innerHTML   = renderChips();
        });

        modalDay.querySelector('[data-close]').addEventListener('click', closeAllModals);
        modalDay.querySelector('[data-submit=day]').addEventListener('click', function () {
            saveDayChanges(dateStr, before, dayState, this);
        });

        openModal(modalDay);
    }

    // Хелпер: подняться по родителям до элемента с заданным атрибутом
    function findClosestAttr(el, root, attr) {
        while (el && el !== root) {
            if (el.getAttribute && el.getAttribute(attr) !== null) return el;
            el = el.parentNode;
        }
        return null;
    }

    function saveDayChanges(dateStr, before, dayState, saveBtn) {
        saveBtn.disabled = true;

        var promises = [];
        for (var i = 0; i < state.workers.length; i++) {
            var wid     = parseInt(state.workers[i].id);
            var wasType = before[wid]   || OFF_TYPE;
            var newType = dayState[wid] || OFF_TYPE;

            if (wasType !== newType) {
                promises.push(api('set_day', {
                    service_id: SERVICE_ID,
                    worker_id:  wid,
                    date:       dateStr,
                    shift_type: newType
                }));
            }
        }

        if (!promises.length) { closeAllModals(); return; }

        Promise.all(promises).then(function () {
            for (var i = 0; i < state.workers.length; i++) {
                var wid     = parseInt(state.workers[i].id);
                var newType = dayState[wid] || OFF_TYPE;
                if (!state.schedule[wid]) state.schedule[wid] = {};

                if (newType === OFF_TYPE) {
                    delete state.schedule[wid][dateStr];
                } else {
                    state.schedule[wid][dateStr] = {
                        type: newType,
                        comment: '',
                        template_id: null
                    };
                }
            }
            closeAllModals();
            renderAll();
        }).catch(function (e) {
            alert(e.message);
            saveBtn.disabled = false;
        });
    }

    function openModal(modal) {
        overlay.classList.add('cwss-open');
        var all = [modalTpl, modalApply, modalClear, modalDay, modalPrint];
        for (var i = 0; i < all.length; i++) all[i].classList.remove('cwss-open');
        modal.classList.add('cwss-open');
    }
    function closeAllModals() {
        overlay.classList.remove('cwss-open');
        var all = [modalTpl, modalApply, modalClear, modalDay, modalPrint];
        for (var i = 0; i < all.length; i++) all[i].classList.remove('cwss-open');
    }

    // ===== Печать =====
    function openPrintModal() {
        if (!state.workers.length) { alert('Нет сотрудников'); return; }

        var workerOptions = '';
        for (var i = 0; i < state.workers.length; i++) {
            var w = state.workers[i];
            workerOptions += '<option value="' + w.id + '">' + esc(w.fio) + '</option>';
        }

        modalPrint.innerHTML =
            '<div class="cwss-modal__title">🖨️ Печать графика</div>' +
            '<div class="cwss-modal__sub">A4: данные СТО, сотрудник, статистика и ч/б календарь.</div>' +
            '<div class="cwss-field"><label class="cwss-field__label">Сотрудник</label>' +
              '<select class="cwss-select" data-p="worker">' + workerOptions + '</select></div>' +
            '<div class="cwss-row">' +
              '<div class="cwss-field"><label class="cwss-field__label">С даты</label>' +
                '<input type="date" class="cwss-input" data-p="date_from" value="' + periodFrom() + '"></div>' +
              '<div class="cwss-field"><label class="cwss-field__label">По дату</label>' +
                '<input type="date" class="cwss-input" data-p="date_to" value="' + periodTo() + '"></div>' +
            '</div>' +
            '<div class="cwss-modal__actions">' +
              '<button type="button" class="cwss-btn" data-close="1">Отмена</button>' +
              '<button type="button" class="cwss-btn cwss-btn--primary" data-submit="print">🖨️ Открыть печать</button>' +
            '</div>';

        modalPrint.querySelector('[data-close]').addEventListener('click', closeAllModals);
        modalPrint.querySelector('[data-submit=print]').addEventListener('click', function () {
            var wid = modalPrint.querySelector('[data-p=worker]').value;
            var df  = modalPrint.querySelector('[data-p=date_from]').value;
            var dt  = modalPrint.querySelector('[data-p=date_to]').value;
            if (!wid) { alert('Выберите сотрудника'); return; }
            if (!df || !dt) { alert('Укажите период'); return; }

            var url = PRINT_URL
                + '?service_id=' + encodeURIComponent(SERVICE_ID)
                + '&worker_id='  + encodeURIComponent(wid)
                + '&date_from='  + encodeURIComponent(df)
                + '&date_to='    + encodeURIComponent(dt);

            window.open(url, '_blank');
            closeAllModals();
        });

        openModal(modalPrint);
    }

    // ===== Применить шаблон =====
    function openApplyModal() {
        var opts = '';
        for (var i = 0; i < state.templates.length; i++) {
            var t = state.templates[i];
            var suffix = parseInt(t.is_system) ? '' : ' · свой';
            opts += '<option value="' + t.id + '">' + esc(t.name) + ' (' + t.work_days + '/' + t.rest_days + ')' + suffix + '</option>';
        }
        opts += '<option value="custom">— Свой цикл —</option>';

        var checks = '';
        for (var w = 0; w < state.workers.length; w++) {
            var worker = state.workers[w];
            var wid = parseInt(worker.id);
            checks += '<label class="cwss-check"><input type="checkbox" data-w-check="' + wid + '" checked> ' + esc(worker.fio) + '</label>';
        }

        modalApply.innerHTML =
            '<div class="cwss-modal__title">🎯 Применить шаблон</div>' +
            '<div class="cwss-field"><label class="cwss-field__label">Сотрудники</label>' +
              '<div class="cwss-checks">' +
                '<label class="cwss-check cwss-check--all"><input type="checkbox" data-w-check-all="1" checked> <b>Все</b></label>' +
                checks +
              '</div></div>' +
            '<div class="cwss-field"><label class="cwss-field__label">Шаблон</label>' +
              '<select class="cwss-select" data-f="template">' + opts + '</select></div>' +
            '<div class="cwss-row" data-f-block="custom" style="display:none;">' +
              '<div class="cwss-field"><label class="cwss-field__label">Рабочих</label>' +
                '<input type="number" min="1" class="cwss-input" data-f="work_days" value="3"></div>' +
              '<div class="cwss-field"><label class="cwss-field__label">Выходных</label>' +
                '<input type="number" min="0" class="cwss-input" data-f="rest_days" value="1"></div>' +
            '</div>' +
            '<div class="cwss-row">' +
              '<div class="cwss-field"><label class="cwss-field__label">Начало цикла</label>' +
                '<input type="date" class="cwss-input" data-f="anchor" value="' + periodFrom() + '"></div>' +
              '<div class="cwss-field"><label class="cwss-field__label">С даты</label>' +
                '<input type="date" class="cwss-input" data-f="date_from" value="' + periodFrom() + '"></div>' +
              '<div class="cwss-field"><label class="cwss-field__label">По дату</label>' +
                '<input type="date" class="cwss-input" data-f="date_to" value="' + periodTo() + '"></div>' +
            '</div>' +
            '<label class="cwss-check"><input type="checkbox" data-f="overwrite" checked> Перезаписывать существующие</label>' +
            '<div class="cwss-modal__actions">' +
              '<button type="button" class="cwss-btn" data-close="1">Отмена</button>' +
              '<button type="button" class="cwss-btn cwss-btn--primary" data-submit="apply">Применить</button>' +
            '</div>';

        var sel = modalApply.querySelector('[data-f=template]');
        var customBlock = modalApply.querySelector('[data-f-block=custom]');
        sel.addEventListener('change', function () {
            customBlock.style.display = (sel.value === 'custom') ? '' : 'none';
        });

        var allCb = modalApply.querySelector('[data-w-check-all]');
        allCb.addEventListener('change', function () {
            var cbs = modalApply.querySelectorAll('[data-w-check]:not([data-w-check-all])');
            for (var i = 0; i < cbs.length; i++) cbs[i].checked = allCb.checked;
        });

        modalApply.querySelector('[data-close]').addEventListener('click', closeAllModals);
        modalApply.querySelector('[data-submit=apply]').addEventListener('click', submitApply);

        openModal(modalApply);
    }

    function submitApply() {
        var btn = modalApply.querySelector('[data-submit=apply]');
        var cbs = modalApply.querySelectorAll('[data-w-check]:not([data-w-check-all])');
        var workerIds = [];
        for (var i = 0; i < cbs.length; i++) if (cbs[i].checked) workerIds.push(parseInt(cbs[i].getAttribute('data-w-check')));
        if (!workerIds.length) { alert('Выберите хотя бы одного сотрудника'); return; }

        var tplVal = modalApply.querySelector('[data-f=template]').value;
        var params = {
            service_id: SERVICE_ID,
            worker_ids: workerIds,
            anchor_date: modalApply.querySelector('[data-f=anchor]').value,
            date_from: modalApply.querySelector('[data-f=date_from]').value,
            date_to: modalApply.querySelector('[data-f=date_to]').value,
            overwrite: modalApply.querySelector('[data-f=overwrite]').checked ? 1 : 0
        };
        if (tplVal === 'custom') {
            params.work_days = parseInt(modalApply.querySelector('[data-f=work_days]').value) || 1;
            params.rest_days = parseInt(modalApply.querySelector('[data-f=rest_days]').value) || 0;
        } else {
            params.template_id = parseInt(tplVal);
        }

        btn.disabled = true;
        api('apply_template', params).then(function () {
            closeAllModals();
            refresh();
        }).catch(function (e) { alert(e.message); btn.disabled = false; });
    }

    // ===== Очистить =====
    function openClearModal() {
        var checks = '';
        for (var w = 0; w < state.workers.length; w++) {
            var worker = state.workers[w];
            var wid = parseInt(worker.id);
            checks += '<label class="cwss-check"><input type="checkbox" data-c-w-check="' + wid + '" checked> ' + esc(worker.fio) + '</label>';
        }

        modalClear.innerHTML =
            '<div class="cwss-modal__title">🗑 Очистить смены</div>' +
            '<div class="cwss-field"><label class="cwss-field__label">Сотрудники</label>' +
              '<div class="cwss-checks">' +
                '<label class="cwss-check cwss-check--all"><input type="checkbox" data-c-w-check-all="1" checked> <b>Все</b></label>' +
                checks +
              '</div></div>' +
            '<div class="cwss-row">' +
              '<div class="cwss-field"><label class="cwss-field__label">С даты</label>' +
                '<input type="date" class="cwss-input" data-f="date_from" value="' + periodFrom() + '"></div>' +
              '<div class="cwss-field"><label class="cwss-field__label">По дату</label>' +
                '<input type="date" class="cwss-input" data-f="date_to" value="' + periodTo() + '"></div>' +
            '</div>' +
            '<div class="cwss-modal__actions">' +
              '<button type="button" class="cwss-btn" data-close="1">Отмена</button>' +
              '<button type="button" class="cwss-btn cwss-btn--danger" data-submit="clear">Очистить</button>' +
            '</div>';

        var allCb = modalClear.querySelector('[data-c-w-check-all]');
        allCb.addEventListener('change', function () {
            var cbs = modalClear.querySelectorAll('[data-c-w-check]:not([data-c-w-check-all])');
            for (var i = 0; i < cbs.length; i++) cbs[i].checked = allCb.checked;
        });

        modalClear.querySelector('[data-close]').addEventListener('click', closeAllModals);
        modalClear.querySelector('[data-submit=clear]').addEventListener('click', function () {
            var btn = this;
            var cbs = modalClear.querySelectorAll('[data-c-w-check]:not([data-c-w-check-all])');
            var workerIds = [];
            for (var i = 0; i < cbs.length; i++) if (cbs[i].checked) workerIds.push(parseInt(cbs[i].getAttribute('data-c-w-check')));
            if (!workerIds.length) { alert('Выберите хотя бы одного'); return; }
            if (!confirm('Удалить смены за выбранный период?')) return;

            btn.disabled = true;
            api('clear_period', {
                service_id: SERVICE_ID,
                worker_ids: workerIds,
                date_from: modalClear.querySelector('[data-f=date_from]').value,
                date_to: modalClear.querySelector('[data-f=date_to]').value
            }).then(function () {
                closeAllModals();
                refresh();
            }).catch(function (e) { alert(e.message); btn.disabled = false; });
        });

        openModal(modalClear);
    }

    // ===== Шаблоны =====
    function openTemplatesModal() {
        function listHtml() {
            var out = '';
            for (var i = 0; i < state.templates.length; i++) {
                var t = state.templates[i];
                var isSys = parseInt(t.is_system);
                out += '<div class="cwss-tpl-item" data-id="' + t.id + '">' +
                    '<span class="cwss-tpl-item__swatch" style="background:' + (t.color || '#4B5563') + '"></span>' +
                    '<div class="cwss-tpl-item__body">' +
                        esc(t.name) +
                        ' <span class="cwss-badge">' + (isSys ? 'системный' : 'свой') + '</span>' +
                        '<small>' + t.work_days + ' раб / ' + t.rest_days + ' вых</small>' +
                    '</div>' +
                    (isSys ? '' :
                        '<button type="button" class="cwss-btn cwss-btn--icon-sm" data-edit="' + t.id + '">✏️</button>' +
                        '<button type="button" class="cwss-btn cwss-btn--icon-sm cwss-btn--danger" data-del="' + t.id + '">✕</button>') +
                '</div>';
            }
            return out;
        }

        modalTpl.innerHTML =
            '<div class="cwss-modal__title">⚙️ Шаблоны смен</div>' +
            '<div class="cwss-tpl-list" data-el="list">' + listHtml() + '</div>' +
            '<div style="border-top:1px solid var(--cwss-border);padding-top:14px;">' +
              '<div class="cwss-field__label" data-el="form-title" style="margin-bottom:10px;">Новый шаблон</div>' +
              '<div class="cwss-row">' +
                '<div class="cwss-field"><label class="cwss-field__label">Название</label>' +
                  '<input type="text" class="cwss-input" data-tf="name" placeholder="напр. 3/1"></div>' +
                '<div class="cwss-field"><label class="cwss-field__label">Рабочих</label>' +
                  '<input type="number" min="1" class="cwss-input" data-tf="work_days" value="3"></div>' +
                '<div class="cwss-field"><label class="cwss-field__label">Выходных</label>' +
                  '<input type="number" min="0" class="cwss-input" data-tf="rest_days" value="1"></div>' +
                '<div class="cwss-field"><label class="cwss-field__label">Цвет</label>' +
                  '<input type="color" class="cwss-input" data-tf="color" value="#8B5CF6"></div>' +
              '</div>' +
              '<input type="hidden" data-tf="id" value="0">' +
              '<div class="cwss-modal__actions">' +
                '<button type="button" class="cwss-btn" data-tf-reset="1">Очистить</button>' +
                '<button type="button" class="cwss-btn cwss-btn--primary" data-tf-save="1">💾 Сохранить</button>' +
              '</div>' +
            '</div>' +
            '<div class="cwss-modal__actions" style="margin-top:14px;">' +
              '<button type="button" class="cwss-btn" data-close="1">Закрыть</button>' +
            '</div>';

        modalTpl.querySelector('[data-close]').addEventListener('click', closeAllModals);

        function refillForm(tpl) {
            var fId = modalTpl.querySelector('[data-tf=id]');
            var fN  = modalTpl.querySelector('[data-tf=name]');
            var fW  = modalTpl.querySelector('[data-tf=work_days]');
            var fR  = modalTpl.querySelector('[data-tf=rest_days]');
            var fC  = modalTpl.querySelector('[data-tf=color]');
            var fT  = modalTpl.querySelector('[data-el=form-title]');
            if (!tpl) {
                fId.value = 0; fN.value = ''; fW.value = 3; fR.value = 1; fC.value = '#8B5CF6';
                fT.textContent = 'Новый шаблон';
            } else {
                fId.value = tpl.id; fN.value = tpl.name; fW.value = tpl.work_days;
                fR.value = tpl.rest_days; fC.value = tpl.color || '#8B5CF6';
                fT.textContent = 'Редактирование: ' + tpl.name;
            }
        }

        var listEl = modalTpl.querySelector('[data-el=list]');
        listEl.addEventListener('click', function (e) {
            var editBtn = e.target.closest ? e.target.closest('[data-edit]') : null;
            var delBtn  = e.target.closest ? e.target.closest('[data-del]') : null;
            if (!editBtn && !delBtn) {
                var el = e.target;
                while (el && el !== listEl) {
                    if (el.getAttribute) {
                        if (el.getAttribute('data-edit') !== null) { editBtn = el; break; }
                        if (el.getAttribute('data-del') !== null)  { delBtn  = el; break; }
                    }
                    el = el.parentNode;
                }
            }
            if (editBtn) {
                var id = parseInt(editBtn.getAttribute('data-edit'));
                for (var i = 0; i < state.templates.length; i++) {
                    if (parseInt(state.templates[i].id) === id) { refillForm(state.templates[i]); break; }
                }
            }
            if (delBtn) {
                if (!confirm('Удалить шаблон?')) return;
                var did = parseInt(delBtn.getAttribute('data-del'));
                api('delete_template', { id: did }).then(function () {
                    var out = [];
                    for (var i = 0; i < state.templates.length; i++) {
                        if (parseInt(state.templates[i].id) !== did) out.push(state.templates[i]);
                    }
                    state.templates = out;
                    openTemplatesModal();
                }).catch(function (err) { alert(err.message); });
            }
        });

        modalTpl.querySelector('[data-tf-reset]').addEventListener('click', function () { refillForm(null); });
        modalTpl.querySelector('[data-tf-save]').addEventListener('click', function () {
            var btn = this;
            var params = {
                id: parseInt(modalTpl.querySelector('[data-tf=id]').value) || 0,
                name: modalTpl.querySelector('[data-tf=name]').value.replace(/^\s+|\s+$/g, ''),
                work_days: parseInt(modalTpl.querySelector('[data-tf=work_days]').value) || 1,
                rest_days: parseInt(modalTpl.querySelector('[data-tf=rest_days]').value) || 0,
                color: modalTpl.querySelector('[data-tf=color]').value
            };
            if (!params.name) { alert('Укажите название'); return; }

            btn.disabled = true;
            api('save_template', params).then(function (resp) {
                var idx = -1;
                for (var i = 0; i < state.templates.length; i++) {
                    if (parseInt(state.templates[i].id) === parseInt(resp.template.id)) { idx = i; break; }
                }
                if (idx >= 0) state.templates[idx] = resp.template;
                else state.templates.push(resp.template);
                openTemplatesModal();
            }).catch(function (err) { alert(err.message); btn.disabled = false; });
        });

        openModal(modalTpl);
    }

    // Обработка ресайза — пересчёт maxShown
    var resizeTimer = null;
    window.addEventListener('resize', function () {
        if (resizeTimer) clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (state.workers.length) renderAll();
        }, 200);
    });

    loadAll();
})();
</script>
</body>
</html>  