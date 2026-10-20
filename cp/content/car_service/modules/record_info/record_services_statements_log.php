<?php
if (!isset($db_link) || !isset($order_id)) return;
$order_id = (int)$order_id;

// -----------------------------------------------------------------------------
// Все стейтменты по заказу. caption хранится прямо в стейтменте.
// -----------------------------------------------------------------------------
$sql = "
    SELECT
        s.`id`             AS statement_id,
        s.`record_id`      AS record_id,
        s.`order_id`       AS order_id,
        s.`caption`        AS service_caption,
        s.`statement_type` AS statement_type,
        s.`time`           AS time,

        t.`caption`        AS type_caption,
        t.`color`          AS type_color,

        r.`worker_id`      AS worker_id,
        p.`fio`            AS worker_fio
    FROM `car_service_record_services_statements` s
    LEFT JOIN `car_service_record_services_statements_types` t ON t.`id` = s.`statement_type`
    LEFT JOIN `car_service_records` r ON r.`id` = s.`record_id`
    LEFT JOIN `car_service_personal` p ON p.`id` = r.`worker_id`
    WHERE s.`order_id` = ?
    ORDER BY s.`time` DESC, s.`id` DESC
";
$stmt = $db_link->prepare($sql);
$stmt->execute([$order_id]);
$statements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------------------------------------------------------
// Группируем по caption
// -----------------------------------------------------------------------------
$currentByCaption = [];
$historyByCaption = [];

foreach ($statements as $s) {
    $cap = trim((string)$s['service_caption']);
    if ($cap === '') $cap = '—';   // старые стейтменты без caption

    if (!isset($currentByCaption[$cap])) {
        $currentByCaption[$cap] = $s;   // последняя (сортировка DESC)
    }
    $historyByCaption[$cap][] = $s;
}
?>

<style>
    .cw-statements-log hpanel .panel-body { padding-top: 10px; }
    .cw-statements-list { margin: 0; padding: 0; list-style: none; }
    .cw-statements-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 8px;
        margin-bottom: 8px;
        background: #f7f7f9;
        border-left: 4px solid #bbb;
    }
    .cw-statements-item .cw-st-icon {
        font-size: 20px;
        line-height: 1;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .cw-statements-item .cw-st-body { flex: 1; min-width: 0; }
    .cw-statements-item .cw-st-title {
        font-weight: 600;
        color: #222;
        word-break: break-word;
    }
    .cw-statements-item .cw-st-meta {
        font-size: 12px;
        color: #666;
        margin-top: 3px;
    }
    .cw-statements-item .cw-st-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        margin-left: 6px;
        vertical-align: middle;
        white-space: nowrap;
    }
    .cw-statements-empty {
        color: #888;
        text-align: center;
        padding: 15px 5px;
    }
    .cw-statements-current {
        font-size: 12px;
        color: #888;
        margin: 0 0 10px;
    }
    .cw-statements-toggle {
        font-size: 12px;
        color: #3B82F6;
        cursor: pointer;
        user-select: none;
        text-decoration: underline dotted;
        margin-left: 4px;
    }
    .cw-statements-history {
        margin-top: 6px;
        padding-left: 8px;
        border-left: 2px dotted #ccc;
        display: none;
    }
    .cw-statements-history.cw-open { display: block; }
    .cw-statements-history .cw-hist-row {
        font-size: 12px;
        color: #555;
        padding: 3px 0;
    }
    .cw-statements-history .cw-hist-row .cw-hist-dot {
        display: inline-block;
        width: 8px; height: 8px;
        border-radius: 50%;
        margin-right: 6px;
        vertical-align: middle;
    }
</style>

<div class="hpanel cw-statements-log">
    <div class="panel-heading hbuilt">
        <div class="panel-tools">
            <a class="showhide"><i class="fa fa-chevron-up"></i></a>
        </div>
        Лог состояний услуг
        <span style="font-size:12px;color:#888;font-weight:normal;">(всего: <?php echo count($statements); ?>)</span>
    </div>
    <div class="panel-body">
        <?php if (empty($statements)): ?>
            <div class="cw-statements-empty">
                Пока нет записей о выполнении / удалении услуг.
            </div>
        <?php else: ?>

            <div class="cw-statements-current">
                Показано текущее состояние по каждой услуге. Разверните — увидите историю.
            </div>

            <ul class="cw-statements-list">
                <?php foreach ($currentByCaption as $cap => $cur):
					$type       = (int)$cur['statement_type'];
					$color      = $cur['type_color'] ?: '#888';
					$typeCaption = $cur['type_caption'] ?: ('Тип ' . $type);
					$icon       = ($type === 2) ? '✅' : (($type === 1) ? '🗑️' : '•');
					$serviceCaption = $cur['service_caption'] ?: '—';
					$timeStr    = date('d.m.Y H:i', (int)$cur['time']);
					$worker     = $cur['worker_fio'] ?: '—';
					$history    = $historyByCaption[$cap] ?? [];
				?>
					<li class="cw-statements-item" style="border-left-color: <?php echo htmlspecialchars($color); ?>;">
						<div class="cw-st-icon"><?php echo $icon; ?></div>
						<div class="cw-st-body">
							<div class="cw-st-title">
								<?php echo htmlspecialchars($serviceCaption); ?>
								<span class="cw-st-badge" style="background: <?php echo htmlspecialchars($color); ?>;">
									<?php echo htmlspecialchars($typeCaption); ?>
								</span>
							</div>
							<div class="cw-st-meta">
								⏱ <?php echo $timeStr; ?>
								&nbsp;·&nbsp; 👨‍🔧 <?php echo htmlspecialchars($worker); ?>
								&nbsp;·&nbsp; 🆔 запись #<?php echo (int)$cur['record_id']; ?>
							</div>

							<?php if (count($history) > 1): ?>
								<span class="cw-statements-toggle"
									  onclick="cwToggleStatementsHistory(this);">
									История (<?php echo count($history); ?>)
								</span>
								<div class="cw-statements-history">
									<?php foreach ($history as $h):
										$hType  = (int)$h['statement_type'];
										$hColor = $h['type_color'] ?: '#888';
										$hCaption = $h['type_caption'] ?: ('Тип ' . $hType);
										$hTime  = date('d.m.Y H:i:s', (int)$h['time']);
									?>
										<div class="cw-hist-row">
											<span class="cw-hist-dot" style="background: <?php echo htmlspecialchars($hColor); ?>;"></span>
											<?php echo htmlspecialchars($hCaption); ?>
											&nbsp;·&nbsp; <?php echo $hTime; ?>
											<span style="color:#bbb;">&nbsp;(stmt #<?php echo (int)$h['statement_id']; ?>)</span>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
            </ul>

        <?php endif; ?>
    </div>
</div>

<script>
    function cwToggleStatementsHistory(el) {
        var history = el.nextElementSibling;
        if (!history) return;
        history.classList.toggle('cw-open');
    }
</script>