<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");
$DP_Config = new DP_Config;

$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$workerId  = isset($_GET['worker_id'])  ? (int)$_GET['worker_id']  : 0;
$dateFrom  = isset($_GET['date_from'])  ? (string)$_GET['date_from'] : date('Y-m-01');
$dateTo    = isset($_GET['date_to'])    ? (string)$_GET['date_to']   : date('Y-m-t');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-t');
if ($dateFrom > $dateTo) { $tmp = $dateFrom; $dateFrom = $dateTo; $dateTo = $tmp; }

try {
    $db = new PDO(
        'mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db,
        $DP_Config->user,
        $DP_Config->password
    );
    $db->query("SET NAMES utf8mb4;");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die('Ошибка подключения к БД'); }

$service = array('caption' => 'Автосервис', 'address' => '', 'phone' => '');
if ($serviceId) {
    $stmt = $db->prepare("SELECT caption, address, phone FROM car_services WHERE id = ?");
    $stmt->execute(array($serviceId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $service = $row;
}

$worker = array('id' => 0, 'fio' => 'Не найден');
if ($workerId) {
    $stmt = $db->prepare("SELECT id, fio FROM car_service_personal WHERE id = ?");
    $stmt->execute(array($workerId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $worker = $row;
}

$schedule = array();
if ($serviceId && $workerId) {
    $stmt = $db->prepare("
        SELECT date, shift_type, comment
        FROM car_service_shift_schedule
        WHERE service_id = ? AND worker_id = ? AND date BETWEEN ? AND ?
        ORDER BY date ASC
    ");
    $stmt->execute(array($serviceId, $workerId, $dateFrom, $dateTo));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $schedule[$row['date']] = array(
            'type'    => $row['shift_type'],
            'comment' => $row['comment']
        );
    }
}

$stats = array('work' => 0, 'off' => 0, 'vacation' => 0, 'sick' => 0, 'holiday' => 0);
$totalDays = 0;
$cur = new DateTime($dateFrom);
$end = new DateTime($dateTo);
while ($cur <= $end) {
    $totalDays++;
    $ds = $cur->format('Y-m-d');
    if (isset($schedule[$ds])) {
        $t = $schedule[$ds]['type'];
        if (isset($stats[$t])) $stats[$t]++;
    } else {
        $stats['off']++;
    }
    $cur->modify('+1 day');
}

$LETTERS = array('work'=>'Р','off'=>'','vacation'=>'О','sick'=>'Б','holiday'=>'П');

$startDt = new DateTime($dateFrom);
$endDt   = new DateTime($dateTo);

$dowStart = (int)$startDt->format('N');
$gridStart = clone $startDt;
if ($dowStart > 1) $gridStart->modify('-' . ($dowStart - 1) . ' days');

$dowEnd = (int)$endDt->format('N');
$gridEnd = clone $endDt;
if ($dowEnd < 7) $gridEnd->modify('+' . (7 - $dowEnd) . ' days');

function fmtRu($dateStr) {
    if (!$dateStr) return '';
    $d = new DateTime($dateStr);
    return $d->format('d.m.Y');
}

$inPeriod = function ($dateStr) use ($dateFrom, $dateTo) {
    return $dateStr >= $dateFrom && $dateStr <= $dateTo;
};
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>График смен — <?php echo htmlspecialchars($worker['fio']); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12pt;
    color: #000;
    background: #fff;
    padding: 12mm;
    line-height: 1.35;
}

.pr-toolbar { display: flex; gap: 8px; margin-bottom: 14px; }
.pr-btn {
    padding: 8px 18px;
    background: #111;
    color: #fff;
    border: 1px solid #111;
    border-radius: 4px;
    font-family: inherit;
    font-size: 11pt;
    font-weight: 600;
    cursor: pointer;
}
.pr-btn:hover { background: #333; border-color: #333; }
.pr-btn--ghost { background: #fff; color: #111; }
.pr-btn--ghost:hover { background: #eee; color: #111; }

.pr-service { border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 14px; }
.pr-service__name { font-size: 16pt; font-weight: 700; margin-bottom: 3px; }
.pr-service__info { font-size: 10pt; color: #333; }
.pr-service__info span + span::before { content: ' · '; }

.pr-worker { margin-bottom: 16px; }
.pr-worker__title { font-size: 10pt; text-transform: uppercase; letter-spacing: 1px; color: #666; margin-bottom: 3px; }
.pr-worker__name { font-size: 18pt; font-weight: 700; line-height: 1.1; }
.pr-worker__period { font-size: 11pt; color: #333; margin-top: 4px; }

.pr-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid #999;
}
.pr-stat {
    flex: 1 1 120px;
    min-width: 110px;
    border: 1px solid #000;
    padding: 8px 10px;
    background: #fff;
}
.pr-stat__value { font-size: 20pt; font-weight: 700; line-height: 1; }
.pr-stat__label { font-size: 9pt; color: #333; margin-top: 4px; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }
.pr-stat--total { border-width: 2px; }

/* ================= Таблица ================= */
.pr-cal-wrap { margin-bottom: 20px; }
.pr-cal {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    background: #fff;
}
.pr-cal th,
.pr-cal td {
    border: 1px solid #000;
    text-align: center;
}
.pr-cal th {
    padding: 6px 4px;
    font-size: 10pt;
    font-weight: 700;
    text-transform: uppercase;
    background: #eaeaea;
    letter-spacing: .5px;
}
.pr-cal td {
    height: 20mm;
    vertical-align: top;
    padding: 2mm;
    position: relative;
    background: #fff;
}
.pr-cal td.pr-cal__cell--empty {
    background: #ececec;
}
.pr-cal__daynum {
    display: block;
    text-align: left;
    font-size: 10pt;
    font-weight: 700;
    line-height: 1;
}
.pr-cal__mark {
    display: block;
    font-size: 22pt;
    font-weight: 700;
    text-align: center;
    margin-top: 2mm;
    line-height: 1;
}

.pr-footer {
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px solid #999;
    font-size: 9pt;
    color: #444;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.pr-footer__legend { display: flex; gap: 12px; flex-wrap: wrap; }
.pr-footer__legend b { color: #000; }

/* ================= Печать ================= */
@media print {
    @page { size: A4 portrait; margin: 10mm; }

    html, body {
        /* сохраняем фоны и цвета */
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    body { padding: 0; font-size: 11pt; }

    .no-print { display: none !important; }

    /* Таблица — единственное надёжное место, где нужны все границы.
       border-collapse: separate без gap + граница на каждой ячейке
       даёт печатаемые линии во всех браузерах. */
    .pr-cal {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        border-top: 1px solid #000 !important;
        border-left: 1px solid #000 !important;
    }
    .pr-cal th,
    .pr-cal td {
        border: 0 !important;
        border-right: 1px solid #000 !important;
        border-bottom: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .pr-cal td.pr-cal__cell--empty {
        background: #ececec !important;
    }

    /* Не рвать строки */
    .pr-stat,
    .pr-cal tr,
    .pr-worker,
    .pr-service { break-inside: avoid; page-break-inside: avoid; }
}
</style>
</head>
<body>

<div class="pr-toolbar no-print">
    <button type="button" class="pr-btn" onclick="window.print();">🖨️ Распечатать</button>
    <button type="button" class="pr-btn pr-btn--ghost" onclick="window.close();">✕ Закрыть</button>
</div>

<div class="pr-service">
    <div class="pr-service__name"><?php echo htmlspecialchars($service['caption']); ?></div>
    <div class="pr-service__info">
        <?php if (!empty($service['address'])): ?><span>📍 <?php echo htmlspecialchars($service['address']); ?></span><?php endif; ?>
        <?php if (!empty($service['phone'])):   ?><span>📞 <?php echo htmlspecialchars($service['phone']); ?></span><?php endif; ?>
    </div>
</div>

<div class="pr-worker">
    <div class="pr-worker__title">График смен</div>
    <div class="pr-worker__name"><?php echo htmlspecialchars($worker['fio']); ?></div>
    <div class="pr-worker__period">
        Период: <b><?php echo fmtRu($dateFrom); ?></b> — <b><?php echo fmtRu($dateTo); ?></b>
    </div>
</div>

<div class="pr-stats">
    <div class="pr-stat"><div class="pr-stat__value"><?php echo (int)$stats['work']; ?></div><div class="pr-stat__label">Рабочих</div></div>
    <div class="pr-stat"><div class="pr-stat__value"><?php echo (int)$stats['off']; ?></div><div class="pr-stat__label">Выходных</div></div>
    <div class="pr-stat"><div class="pr-stat__value"><?php echo (int)$stats['vacation']; ?></div><div class="pr-stat__label">Отпуск</div></div>
    <div class="pr-stat"><div class="pr-stat__value"><?php echo (int)$stats['sick']; ?></div><div class="pr-stat__label">Больничный</div></div>
    <div class="pr-stat"><div class="pr-stat__value"><?php echo (int)$stats['holiday']; ?></div><div class="pr-stat__label">Праздник</div></div>
    <div class="pr-stat pr-stat--total"><div class="pr-stat__value"><?php echo (int)$totalDays; ?></div><div class="pr-stat__label">Всего дней</div></div>
</div>

<div class="pr-cal-wrap">
    <table class="pr-cal">
        <thead>
            <tr><th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th>Сб</th><th>Вс</th></tr>
        </thead>
        <tbody>
            <?php
            $cur = clone $gridStart;
            while ($cur <= $gridEnd) {
                echo '<tr>';
                for ($i = 0; $i < 7; $i++) {
                    $ds = $cur->format('Y-m-d');
                    $dayNum = (int)$cur->format('j');

                    if (!$inPeriod($ds)) {
                        echo '<td class="pr-cal__cell--empty"></td>';
                    } else {
                        $type = 'off';
                        if (isset($schedule[$ds])) $type = $schedule[$ds]['type'];
                        $letter = isset($LETTERS[$type]) ? $LETTERS[$type] : '';
                        echo '<td>';
                        echo '<span class="pr-cal__daynum">' . $dayNum . '</span>';
                        if ($letter !== '') {
                            echo '<span class="pr-cal__mark">' . $letter . '</span>';
                        }
                        echo '</td>';
                    }
                    $cur->modify('+1 day');
                }
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<div class="pr-footer">
    <div class="pr-footer__legend">
        <span><b>Р</b> — рабочий</span>
        <span><b>О</b> — отпуск</span>
        <span><b>Б</b> — больничный</span>
        <span><b>П</b> — праздник</span>
        <span>(пусто) — выходной</span>
    </div>
    <div>Сформировано: <?php echo date('d.m.Y H:i'); ?></div>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 250);
});
</script>

</body>
</html>