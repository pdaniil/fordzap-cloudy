<?php
/**
 * Экран выбора сотрудника (для планшета в СТО).
 * Клик по карточке → sessionStorage → index.php
 * Учитывает график смен: подсвечивает, кто на смене сегодня/завтра.
 */
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
} catch (PDOException $e) {
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 1;

// ---------- Сотрудники ----------
$workers_query = $db_link->prepare("SELECT id, fio, image FROM car_service_personal ORDER BY fio");
$workers_query->execute();
$workers = $workers_query->fetchAll(PDO::FETCH_ASSOC);

// ---------- Смены на сегодня/завтра ----------
$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$shifts = []; // [worker_id][date] => shift_type
try {
    $shiftStmt = $db_link->prepare("
        SELECT worker_id, date, shift_type
        FROM car_service_shift_schedule
        WHERE service_id = ? AND date IN (?, ?)
    ");
    $shiftStmt->execute([$serviceId, $today, $tomorrow]);
    while ($row = $shiftStmt->fetch(PDO::FETCH_ASSOC)) {
        $shifts[(int)$row['worker_id']][(string)$row['date']] = (string)$row['shift_type'];
    }
} catch (PDOException $e) {
    // Таблицы графика смен ещё нет — работаем без него
    $shifts = [];
}

// ---------- Справочники ----------
$SHIFT_LABELS = [
    'work'     => ['label' => 'На смене',   'color' => '#10B981', 'icon' => '✅'],
    'vacation' => ['label' => 'Отпуск',     'color' => '#3B82F6', 'icon' => '🏖️'],
    'sick'     => ['label' => 'Больничный', 'color' => '#EF4444', 'icon' => '🤒'],
    'holiday'  => ['label' => 'Праздник',   'color' => '#F59E0B', 'icon' => '🎉'],
];
$OFF_LABEL = ['label' => 'Выходной', 'color' => '#6B7280', 'icon' => '💤'];

function cwStaffGetShiftInfo($type, $SHIFT_LABELS, $OFF_LABEL) {
    if ($type && isset($SHIFT_LABELS[$type])) return $SHIFT_LABELS[$type];
    return $OFF_LABEL;
}
function cwStaffInitials($fio) {
    $fio = trim((string)$fio);
    if ($fio === '') return '?';
    $parts = preg_split('/\s+/u', $fio);
    $out = '';
    if (!empty($parts[0])) $out .= mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'), 'UTF-8');
    if (!empty($parts[1])) $out .= mb_strtoupper(mb_substr($parts[1], 0, 1, 'UTF-8'), 'UTF-8');
    return $out !== '' ? $out : '?';
}

// ---------- Формируем данные для рендера ----------
$workersData = [];
foreach ($workers as $w) {
    $wid = (int)$w['id'];
    $todayType    = isset($shifts[$wid][$today])    ? $shifts[$wid][$today]    : null;
    $tomorrowType = isset($shifts[$wid][$tomorrow]) ? $shifts[$wid][$tomorrow] : null;

    $workersData[] = [
        'id'              => $wid,
        'fio'             => $w['fio'],
        'image'           => $w['image'] ?: '',
        'today'           => $todayType,
        'tomorrow'        => $tomorrowType,
        'today_work'      => ($todayType === 'work'),
        'tomorrow_work'   => ($tomorrowType === 'work'),
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Выбор сотрудника</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: #1E1E2D;
        color: #FFFFFF;
        -webkit-tap-highlight-color: transparent;
        overflow-x: hidden;
    }
    .ss_app {
        min-height: 100vh;
        padding: 20px 16px 40px;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* ---------- Заголовок ---------- */
    .ss_header {
        padding: 10px 4px 20px;
        border-bottom: 1px solid #2E2E42;
        margin-bottom: 18px;
    }
    .ss_title {
        font-size: 24px;
        font-weight: 700;
        color: #FFFFFF;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .ss_subtitle {
        font-size: 13px;
        color: #A0A0B0;
        margin-top: 6px;
    }

    /* ---------- Фильтры ---------- */
    .ss_filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
    }
    .ss_filter_btn {
        padding: 8px 16px;
        background: #2B2B3B;
        border: 1px solid #3A3A50;
        border-radius: 20px;
        color: #A0A0B0;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s;
        font-family: inherit;
    }
    .ss_filter_btn:active { opacity: 0.7; }
    .ss_filter_btn.ss_active {
        background: #3B82F6;
        border-color: #3B82F6;
        color: #FFFFFF;
    }

    /* ---------- Сетка карточек ---------- */
    .ss_grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    @media (min-width: 640px) {
        .ss_grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (min-width: 1024px) {
        .ss_grid { grid-template-columns: repeat(4, 1fr); }
    }

    .ss_card {
        background: #2B2B3B;
        border: 1px solid #3A3A50;
        border-radius: 16px;
        padding: 16px 12px 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        cursor: pointer;
        transition: 0.15s;
        position: relative;
        overflow: hidden;
    }
    .ss_card:active {
        transform: scale(0.97);
        background: #34344A;
    }
    .ss_card.ss_on_shift { border-color: #10B981; box-shadow: 0 0 0 1px rgba(16,185,129,0.25); }

    /* ---------- Аватар ---------- */
    .ss_avatar_wrap {
        width: 88px; height: 88px;
        border-radius: 50%;
        overflow: hidden;
        background: #3B82F6;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        flex-shrink: 0;
        border: 3px solid #3A3A50;
    }
    .ss_card.ss_on_shift .ss_avatar_wrap { border-color: #10B981; }
    .ss_avatar_img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }
    .ss_avatar_initials {
        font-size: 30px;
        font-weight: 700;
        color: #FFFFFF;
        letter-spacing: 1px;
    }

    /* ---------- Имя ---------- */
    .ss_name {
        font-size: 15px;
        font-weight: 600;
        color: #FFFFFF;
        line-height: 1.3;
        margin-bottom: 8px;
        word-break: break-word;
    }

    /* ---------- Бейджи смены ---------- */
    .ss_badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 10px;
        background: rgba(255,255,255,0.06);
        white-space: nowrap;
    }
    .ss_tomorrow {
        margin-top: 6px;
        font-size: 10px;
        color: #6B7280;
    }
    .ss_tomorrow span { color: #A0A0B0; }

    /* ---------- Пусто ---------- */
    .ss_empty {
        text-align: center;
        padding: 60px 20px;
        color: #6B7280;
    }
    .ss_empty_icon { font-size: 56px; margin-bottom: 12px; }

    /* ---------- Нижняя кнопка «Обычный вход» ---------- */
    .ss_footer {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #2E2E42;
        display: flex;
        justify-content: center;
    }
    .ss_plain_btn {
        background: transparent;
        border: 1px solid #3A3A50;
        color: #A0A0B0;
        padding: 10px 24px;
        border-radius: 10px;
        font-size: 13px;
        cursor: pointer;
        font-family: inherit;
        transition: 0.2s;
    }
    .ss_plain_btn:active { background: #2B2B3B; color: #FFFFFF; }
</style>
</head>
<body>

<div class="ss_app">
    <div class="ss_header">
        <div class="ss_title">🔧 Выберите сотрудника</div>
        <div class="ss_subtitle">Нажмите на свою карточку, чтобы начать работу</div>
    </div>

    <div class="ss_filters" id="ss_filters">
        <button type="button" class="ss_filter_btn" data-filter="all">Все</button>
        <button type="button" class="ss_filter_btn ss_active" data-filter="today">На смене сегодня</button>
        <button type="button" class="ss_filter_btn" data-filter="tomorrow">На смене завтра</button>
    </div>

    <div class="ss_grid" id="ss_grid"></div>

    <div class="ss_footer">
        <button type="button" class="ss_plain_btn" onclick="ssPlainEnter()">Обычный вход →</button>
    </div>
</div>

<script>
    var SS_WORKERS   = <?php echo json_encode($workersData, JSON_UNESCAPED_UNICODE); ?>;
    var SS_SERVICE_ID = <?php echo (int)$serviceId; ?>;
    var SS_SHIFTS = {
        work:     { label: 'На смене',   color: '#10B981', icon: '✅' },
        vacation: { label: 'Отпуск',     color: '#3B82F6', icon: '🏖️' },
        sick:     { label: 'Больничный', color: '#EF4444', icon: '🤒' },
        holiday:  { label: 'Праздник',   color: '#F59E0B', icon: '🎉' }
    };
    var SS_OFF = { label: 'Выходной', color: '#6B7280', icon: '💤' };

    var ssCurrentFilter = 'today';

    function ssEsc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    function ssInitials(fio) {
        var p = String(fio || '').trim().split(/\s+/);
        var out = '';
        if (p[0]) out += p[0].charAt(0).toUpperCase();
        if (p[1]) out += p[1].charAt(0).toUpperCase();
        return out || '?';
    }
    function ssGetShift(type) {
        if (type && SS_SHIFTS[type]) return SS_SHIFTS[type];
        return SS_OFF;
    }

    function ssRenderCards() {
        var grid = document.getElementById('ss_grid');
        var list = SS_WORKERS.slice();
        if (ssCurrentFilter === 'today') {
            list = list.filter(function(w) { return w.today_work; });
        } else if (ssCurrentFilter === 'tomorrow') {
            list = list.filter(function(w) { return w.tomorrow_work; });
        }

        if (!list.length) {
            grid.innerHTML =
                '<div class="ss_empty" style="grid-column: 1 / -1;">' +
                    '<div class="ss_empty_icon">👥</div>' +
                    '<div>Нет сотрудников для отображения</div>' +
                '</div>';
            return;
        }

        var html = '';
        list.forEach(function(w) {
            var todayInfo    = ssGetShift(w.today);
            var tomorrowInfo = ssGetShift(w.tomorrow);

            var avatar = w.image
                ? '<img class="ss_avatar_img" src="' + ssEsc(w.image) + '" alt="" ' +
                      'onerror="this.outerHTML=\'<span class=&quot;ss_avatar_initials&quot;>' + ssEsc(ssInitials(w.fio)) + '</span>\'">'
                : '<span class="ss_avatar_initials">' + ssEsc(ssInitials(w.fio)) + '</span>';

            html +=
                '<div class="ss_card' + (w.today_work ? ' ss_on_shift' : '') + '" ' +
                     'data-id="' + w.id + '" ' +
                     'onclick="ssSelect(' + w.id + ')">' +
                    '<div class="ss_avatar_wrap">' + avatar + '</div>' +
                    '<div class="ss_name">' + ssEsc(w.fio) + '</div>' +
                    '<div class="ss_badge" style="color:' + todayInfo.color + ';background:' + ssHexA(todayInfo.color, 0.14) + ';">' +
                        todayInfo.icon + ' ' + ssEsc(todayInfo.label) +
                    '</div>' +
                    '<div class="ss_tomorrow">завтра: <span style="color:' + tomorrowInfo.color + ';">' +
                        tomorrowInfo.icon + ' ' + ssEsc(tomorrowInfo.label) +
                    '</span></div>' +
                '</div>';
        });
        grid.innerHTML = html;
    }

    function ssHexA(hex, alpha) {
        hex = String(hex).replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var r = parseInt(hex.substr(0,2), 16);
        var g = parseInt(hex.substr(2,2), 16);
        var b = parseInt(hex.substr(4,2), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    function ssSelect(id) {
        var w = null;
        for (var i = 0; i < SS_WORKERS.length; i++) {
            if (SS_WORKERS[i].id === id) { w = SS_WORKERS[i]; break; }
        }
        if (!w) return;

        try {
            sessionStorage.setItem('cw_staff', JSON.stringify({
                id:     w.id,
                fio:    w.fio,
                image:  w.image || '',
                ts:     Date.now()
            }));
        } catch (e) { /* quota — игнорируем */ }

        var url = 'index.php';
        if (SS_SERVICE_ID) url += '?service_id=' + encodeURIComponent(SS_SERVICE_ID);
        window.location.href = url;
    }

    function ssPlainEnter() {
        try { sessionStorage.removeItem('cw_staff'); } catch (e) {}
        var url = 'index.php';
        if (SS_SERVICE_ID) url += '?service_id=' + encodeURIComponent(SS_SERVICE_ID);
        window.location.href = url;
    }

    // ---------- Фильтры ----------
    document.getElementById('ss_filters').addEventListener('click', function(e) {
        var btn = e.target.closest('.ss_filter_btn');
        if (!btn) return;
        ssCurrentFilter = btn.dataset.filter || 'all';
        var all = document.querySelectorAll('.ss_filter_btn');
        for (var i = 0; i < all.length; i++) all[i].classList.remove('ss_active');
        btn.classList.add('ss_active');
        ssRenderCards();
    });

    ssRenderCards();
</script>

</body>
</html>