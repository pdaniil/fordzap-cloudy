<?php
ini_set("display_errors", 1);
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

/* ---------- $db_link — глобальная для dp_user.php ---------- */
try {
    $db_link = new PDO(
        'mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db,
        $DP_Config->user, $DP_Config->password
    );
    $db_link->query("SET NAMES utf8mb4;");
} catch (PDOException $e) {
    exit('Ошибка подключения к БД: ' . $e->getMessage());
}

require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");
DP_User::getAdminSession();

/* ============================================================
 * Входные параметры
 * ============================================================ */
$carId    = (int)($_GET['car_id']    ?? 0);
$recordId = (int)($_GET['record_id'] ?? 0);

/* Если передан только record_id — узнаём car_id */
if ($carId <= 0 && $recordId > 0) {
    $s = $db_link->prepare("SELECT car_id FROM car_service_records WHERE id = ?");
    $s->execute([$recordId]);
    $carId = (int)$s->fetchColumn();
}
if ($carId <= 0) exit('Не указан автомобиль');

/* ============================================================
 * Запись (заказ-наряд) — ищем по record_id либо последнюю для авто
 * ============================================================ */
$record = null;

if ($recordId > 0) {
    $s = $db_link->prepare("
        SELECT r.*, p.fio AS worker_fio
        FROM car_service_records r
        LEFT JOIN car_service_personal p ON p.id = r.worker_id
        WHERE r.id = ?
    ");
    $s->execute([$recordId]);
    $record = $s->fetch(PDO::FETCH_ASSOC) ?: null;
} else {
    $s = $db_link->prepare("
        SELECT r.*, p.fio AS worker_fio
        FROM car_service_records r
        LEFT JOIN car_service_personal p ON p.id = r.worker_id
        WHERE r.car_id = ?
        ORDER BY r.time DESC LIMIT 1
    ");
    $s->execute([$carId]);
    $record = $s->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($record) $recordId = (int)$record['id'];
}

/* ============================================================
 * СТО
 * ============================================================ */
$sto = [];
if ($record && $record['sto_id']) {
    $s = $db_link->prepare("SELECT * FROM car_services WHERE id = ?");
    $s->execute([$record['sto_id']]);
    $sto = $s->fetch(PDO::FETCH_ASSOC) ?: [];
}

/* ============================================================
 * Авто
 * ============================================================ */
$s = $db_link->prepare("SELECT * FROM shop_docpart_garage WHERE id = ?");
$s->execute([$carId]);
$car = $s->fetch(PDO::FETCH_ASSOC);
if (!$car) exit('Автомобиль не найден');

/* ============================================================
 * Клиент
 * ============================================================ */
$clientName = ''; $clientPhone = ''; $clientId = 0;
if ($record && $record['user_id']) {
    $clientId = (int)$record['user_id'];
    $s = $db_link->prepare("
        SELECT u.email, u.phone,
          (SELECT data_value FROM users_profiles WHERE user_id=u.user_id AND data_key='surname')    AS surname,
          (SELECT data_value FROM users_profiles WHERE user_id=u.user_id AND data_key='name')       AS name,
          (SELECT data_value FROM users_profiles WHERE user_id=u.user_id AND data_key='patronymic') AS patronymic
        FROM users u WHERE u.user_id = ?
    ");
    $s->execute([$clientId]);
    $u = $s->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $clientName  = trim(($u['surname'] ?? '').' '.($u['name'] ?? '').' '.($u['patronymic'] ?? ''));
        $clientPhone = $u['phone'] ?: $u['email'];
    }
}

/* ============================================================
 * Акт приёма
 * ============================================================ */
$s = $db_link->prepare("SELECT * FROM car_service_acceptance_acts WHERE car_id = ?");
$s->execute([$carId]);
$act = $s->fetch(PDO::FETCH_ASSOC);

$damages = []; $equipment = [];
$paintDefects = $specialNotes = '';
$fuelLevel = 0;
$actMileage = null;

if ($act) {
    $damages      = json_decode($act['damages'], true) ?: [];
    $equipment    = json_decode($act['equipment'], true) ?: [];
    $paintDefects = $act['paint_defects'] ?? '';
    $specialNotes = $act['special_notes'] ?? '';
    $fuelLevel    = (int)$act['fuel_level'];
    $actMileage   = ($act['mileage'] !== null) ? (int)$act['mileage'] : null;
}

/* ============================================================
 * Пробег: приоритет 1) акт, 2) запись, 3) последняя запись авто
 * ============================================================ */
$currentMileage = 0;
if ($actMileage !== null) {
    $currentMileage = $actMileage;
} elseif ($record && !empty($record['mileage'])) {
    $currentMileage = (int)$record['mileage'];
} else {
    $s = $db_link->prepare("SELECT mileage FROM car_service_records
                            WHERE car_id = ? AND mileage > 0
                            ORDER BY time DESC LIMIT 1");
    $s->execute([$carId]);
    $m = $s->fetchColumn();
    $currentMileage = ($m !== false && $m !== null) ? (int)$m : 0;
}

/* Предыдущий пробег — как в print.php */
$pastMileage = 0;
if ($record) {
    $s = $db_link->prepare("
        SELECT mileage FROM car_service_records
        WHERE car_id = ? AND user_id = ? AND id <= ?
        ORDER BY id DESC LIMIT 2
    ");
    $s->execute([$carId, $clientId, $recordId]);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    if (isset($rows[1]['mileage'])) {
        $pastMileage = (int)$rows[1]['mileage'];
    }
}
$mileageDiff = ($currentMileage > 0 && $pastMileage > 0 && $currentMileage > $pastMileage)
    ? ($currentMileage - $pastMileage) : 0;

/* ============================================================
 * Метки
 * ============================================================ */
$damagesByView = ['left'=>[], 'right'=>[], 'front'=>[], 'rear'=>[], 'top'=>[]];
foreach ($damages as $d) {
    if (isset($damagesByView[$d['view']])) $damagesByView[$d['view']][] = $d;
}

$DAMAGE_META = [
    'dent'    => ['code'=>'В', 'color'=>'#FF3B30', 'label'=>'Вмятина'],
    'chip'    => ['code'=>'С', 'color'=>'#007AFF', 'label'=>'Скол'],
    'crack'   => ['code'=>'Т', 'color'=>'#FF9500', 'label'=>'Трещина'],
    'scratch' => ['code'=>'Ц', 'color'=>'#CC73E1', 'label'=>'Царапина']
];

$ASSETS = '/content/carservice/admin_console/assets/';
$IMAGES = [
    'left'  => $ASSETS . 'car_left.jpg',
    'right' => $ASSETS . 'car_right.jpg',
    'front' => $ASSETS . 'car_front.jpg',
    'rear'  => $ASSETS . 'car_rear.jpg',
    'top'   => $ASSETS . 'car_top.jpg'
];

$fuelLabels = ['Пусто', '1/4', '1/2', '3/4', 'Полный'];
$fuelPct    = [0, 25, 50, 75, 100];

/* Даты и заголовок */
$acceptedAt   = $record ? date('d.m.Y H:i', strtotime($record['time'])) : date('d.m.Y H:i');
$docDate      = $record ? date('d.m.Y',    strtotime($record['time'])) : date('d.m.Y');
$orderNum     = ($record && !empty($record['orders_ids'])) ? (int)$record['orders_ids'] : 0;

/* Логотип — тот же, что в print.php */
$logoUrl = 'https://fordzap.com/content/files/%D0%A1%D0%BD%D0%B8%D0%BC%D0%BE%D0%BA%20%D1%8D%D0%BA%D1%80%D0%B0%D0%BD%D0%B0%202024-02-01%20223453.png?v=123';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Акт осмотра ТС<?= $orderNum ? ' к ЗН-'.$orderNum : '' ?> от <?= $docDate ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

    * { box-sizing: border-box; }

    html, body {
        font-family: 'Inter', Arial, sans-serif;
        background: #f0f7f9;
        color: rgba(0,0,0,.87);
        font-size: 12px;
        margin: 0; padding: 0;
    }

    .check-content-wrapper {
        box-shadow: 4px 4px 44px 8px rgba(34,60,80,.2);
        max-width: 210mm;
        margin: 10px auto;
    }
    .check-content { background: #fff; border: 1px solid #D3D3D3; }

    /* ---- Шапка СТО ---- */
    .shop-info {
        background: #f6f8f9;
        padding: 10px 16px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        border-bottom: 1px solid #dee2e6;
    }
    .shop-left { display: flex; align-items: center; gap: 12px; }
    .shop-logo {
        width: 56px; height: 56px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #fff;
        overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .shop-logo img { width: 100%; height: auto; }
    .shop-name { font-weight: 700; font-size: 13px; line-height: 1.3; }
    .shop-address, .shop-phone { color: rgba(0,0,0,.56); font-size: 10px; line-height: 1.4; }

    .shop-right { text-align: right; font-size: 10px; color: #333; line-height: 1.6; white-space: nowrap; }
    .shop-right b { font-weight: 700; }

    /* ---- Заголовок ---- */
    .check-header {
        font-size: 15px;
        line-height: 1.2;
        padding: 8px 16px 6px;
        text-align: center;
        font-weight: 700;
        border-bottom: 1px solid #e9ecef;
    }

    /* ---- Информация о клиенте и ТС ---- */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1px 24px;
        padding: 8px 16px 10px;
        font-size: 11px;
        line-height: 1.55;
        border-bottom: 1px solid #e9ecef;
    }
    .info-row { display: flex; gap: 6px; }
    .info-row .k { color: #495057; min-width: 96px; flex-shrink: 0; }
    .info-row .v { color: #000; font-weight: 600; word-break: break-word; }
    .info-row.muted .v { font-weight: 400; color: #6c757d; }

    /* ---- Заголовок схемы ---- */
    .section-title {
        text-align: center;
        font-weight: 700;
        font-size: 11.5px;
        padding: 6px 16px 4px;
    }

    /* ---- Основной двухколоночный блок ---- */
    .act-main {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
        gap: 10px;
        padding: 0 16px 6px;
        align-items: start;
    }

    /* ------------------------------------------------------------
     * Раскладка видов:
     *   1) перёд | зад
     *   2) лево (во всю ширину)
     *   3) право (во всю ширину)
     *   4) верх (во всю ширину)
     * ------------------------------------------------------------ */
.act-views {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3px;
    grid-template-areas:
        "front  rear"
        "left   left"
        "right  right"
        "top    top";
    max-width: 90%;          /* ← уменьшение на 20% */
}
    .act-view {
        position: relative;
        border: 1px solid #d8e0e6;
        border-radius: 3px;
        background: #fff;
        padding: 0;
        overflow: hidden;
        line-height: 0;
        font-size: 0;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .act-view--front { grid-area: front; }
    .act-view--rear  { grid-area: rear;  }
    .act-view--left  { grid-area: left;  }
    .act-view--right { grid-area: right; }
    .act-view--top   { grid-area: top;   }
    .act-view img { display: block; width: 100%; height: auto; }

    .act-marker {
        position: absolute;
        width: 12px; height: 12px;
        transform: translate(-50%, -50%) rotate(45deg);
        border-radius: 2px;
        border: 1px solid rgba(255,255,255,.9);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700;
        box-shadow: 0 1px 2px rgba(0,0,0,.3);
        pointer-events: none;
    }
    .act-marker span { transform: rotate(-45deg); font-size: 7px; line-height: 1; }

    /* ---- Правая колонка ---- */
    .act-fields { display: flex; flex-direction: column; gap: 7px; }

    .act-field-label { font-weight: 700; font-size: 10.5px; display: block; margin-bottom: 2px; }
    .act-field-value {
        font-size: 10px; color: #333;
        white-space: word-break: break-word;
        line-height: 1.45;
    }

    /* Уровень топлива */
    .fuel-widget { display: flex; flex-direction: column; gap: 1px; position: relative; }
    .fuel-bar {
        position: relative;
        height: 5mm;
        border: 1px solid #adb5bd;
        background: #e9ecef;
        overflow: hidden;
    }
    .fuel-bar-fill { position: absolute; left: 0; top: 0; bottom: 0; background: #2c3e50; }
    .fuel-bar-mid  { position: absolute; left: 50%; top: 0; bottom: 0; width: 1px; background: #fff; opacity: .9; }
    .fuel-labels {
        display: flex;
        justify-content: space-between;
        font-size: 9px; color: #495057;
        position: relative;
    }
    .fuel-labels .mid {
        position: absolute;
        left: 50%; transform: translateX(-50%);
    }

    /* ---- Легенда ---- */
    .act-legend {
        padding: 4px 16px 6px;
        font-size: 10px;
        color: #333;
        text-align: center;
        border-top: 1px solid #e9ecef;
    }
    .act-legend .item { display: inline; margin: 0 6px; }

    /* ---- Подписи ---- */
    .act-signatures {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        padding: 10px 16px 12px;
    }
    .act-signatures .sig {
        font-size: 11px;
        color: #333;
        padding-top: 14px;
        border-top: 1px solid #000;
        text-align: center;
    }

    /* ---- Футер ---- */
    .act-page-footer {
        padding: 4px 16px 8px;
        text-align: right;
        font-size: 9px;
        color: #6c757d;
        border-top: 1px solid #e9ecef;
    }

    /* ---- Кнопка печати ---- */
    .no-print { position: fixed; top: 16px; right: 16px; z-index: 1000; }

    /* =========================
       ПРАВИЛА ПЕЧАТИ
       ========================= */
    @media print {
        @page { size: A4 portrait; margin: 8mm; }

        html, body {
            background: #fff !important;
            font-size: 11px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .no-print { display: none !important; }
        .check-content-wrapper {
            box-shadow: none !important;
            max-width: 100% !important;
            margin: 0 !important;
        }
        .check-content { border: none !important; }
        .shop-info { background: #f6f8f9 !important; }

        .act-main, .act-view, .act-fields, .act-signatures, .act-legend, .info-grid {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }
    }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print();return false;" class="btn btn-secondary btn-sm">
        <i class="fas fa-print"></i> Печать
    </button>
</div>

<div class="check-content-wrapper">
  <div class="check-content">

    <!-- =============== ШАПКА =============== -->
    <div class="shop-info">
      <div class="shop-left">
        <div class="shop-logo">
          <img src="<?= htmlspecialchars($logoUrl) ?>" alt="">
        </div>
        <div>
          <div class="shop-name"><?= htmlspecialchars($sto['organization'] ?? $sto['caption'] ?? 'Автосервис') ?></div>
          <?php if (!empty($sto['inn'])): ?>
            <div class="shop-address">ИНН: <?= htmlspecialchars($sto['inn']) ?></div>
          <?php endif; ?>
          <?php if (!empty($sto['address'])): ?>
            <div class="shop-address"><?= htmlspecialchars($sto['address']) ?></div>
          <?php endif; ?>
          <?php if (!empty($sto['phone'])): ?>
            <div class="shop-phone">Тел.: <?= htmlspecialchars($sto['phone']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="shop-right">
        <div>Принят: <b><?= htmlspecialchars($acceptedAt) ?></b></div>
        <div>Закрыт: <b>________________</b></div>
      </div>
    </div>

    <!-- =============== ЗАГОЛОВОК =============== -->
    <div class="check-header">
      Акт осмотра ТС<?= $orderNum ? ' к заказ-наряду № '.$_GET["record_id"] : '' ?>
      от <?= $docDate ?>
    </div>

    <!-- =============== ИНФО О КЛИЕНТЕ / ТС =============== -->
    <div class="info-grid">
      <div>
        <div class="info-row"><span class="k">Заказчик:</span>       <span class="v"><?= htmlspecialchars($clientName ?: '—') ?></span></div>
        <div class="info-row"><span class="k">Телефон:</span>        <span class="v"><?= htmlspecialchars($clientPhone ?: '—') ?></span></div>
        <div class="info-row"><span class="k">Модель:</span>         <span class="v"><?= htmlspecialchars(trim(($car['marka'] ?? '').' '.($car['model'] ?? '')) ?: '—') ?></span></div>
        <div class="info-row"><span class="k">Год выпуска:</span>    <span class="v"><?= htmlspecialchars($car['year'] ?? '—') ?></span></div>
        <div class="info-row"><span class="k">Пробег:</span>         <span class="v"><?= $currentMileage > 0 ? (int)$currentMileage.' км' : '—' ?></span></div>
        <?php if ($pastMileage > 0): ?>
          <div class="info-row"><span class="k">Пред. пробег:</span> <span class="v"><?= (int)$pastMileage ?> км</span></div>
        <?php endif; ?>
        <?php if ($mileageDiff > 0): ?>
          <div class="info-row"><span class="k">Разница:</span>      <span class="v"><?= (int)$mileageDiff ?> км</span></div>
        <?php endif; ?>
        <div class="info-row"><span class="k">Мастер-приёмщик:</span><span class="v"><?= htmlspecialchars($record['worker_fio'] ?? '—') ?></span></div>
      </div>
      <div>
        <div class="info-row"><span class="k">VIN-номер:</span>      <span class="v"><?= htmlspecialchars($car['vin'] ?? '—') ?></span></div>
        <div class="info-row"><span class="k">Гос.номер:</span>      <span class="v"><?= htmlspecialchars($car['license_plate'] ?? '—') ?></span></div>
        <div class="info-row"><span class="k">Цвет:</span>           <span class="v"><?= htmlspecialchars($car['color'] ?? '—') ?></span></div>
        <div class="info-row"><span class="k">Кузов:</span>          <span class="v"><?= htmlspecialchars($car['body_type'] ?? '—') ?></span></div>
        <div class="info-row"><span class="k">Двигатель:</span>      <span class="v"><?= htmlspecialchars($car['engine_value'] ?? '—') ?></span></div>
        <div class="info-row"><span class="k">КПП:</span>            <span class="v"><?= htmlspecialchars($car['transmission'] ?? '—') ?></span></div>
      </div>
    </div>

    <!-- =============== ЗАГОЛОВОК СХЕМЫ =============== -->
    <div class="section-title">Комплектность транспортного средства и наличие повреждений ЛКП</div>

    <!-- =============== СХЕМА + ПОЛЯ =============== -->
    <div class="act-main">

      <div class="act-views">
        <?php foreach (['front', 'rear', 'left', 'right'] as $vk): ?>
          <div class="act-view act-view--<?= $vk ?>">
            <img src="<?= htmlspecialchars($IMAGES[$vk]) ?>" alt="">
            <?php foreach ($damagesByView[$vk] as $d):
                $meta = $DAMAGE_META[$d['type']] ?? $DAMAGE_META['dent']; ?>
              <div class="act-marker"
                   style="left:<?= (float)$d['x'] ?>%;top:<?= (float)$d['y'] ?>%;background:<?= $meta['color'] ?>;">
                <span><?= $meta['code'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <div class="act-view act-view--top">
          <img src="<?= htmlspecialchars($IMAGES['top']) ?>" alt="">
          <?php foreach ($damagesByView['top'] as $d):
              $meta = $DAMAGE_META[$d['type']] ?? $DAMAGE_META['dent']; ?>
            <div class="act-marker"
                 style="left:<?= (float)$d['x'] ?>%;top:<?= (float)$d['y'] ?>%;background:<?= $meta['color'] ?>;">
              <span><?= $meta['code'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="act-fields">

        <div>
          <span class="act-field-label">Уровень топлива:</span>
          <div class="fuel-widget">
            <div class="fuel-bar">
              <div class="fuel-bar-fill" style="width:<?= (int)$fuelPct[$fuelLevel] ?>%;"></div>
              <div class="fuel-bar-mid"></div>
            </div>
            <div class="fuel-labels">
              <span>0</span>
              <span class="mid">1/2</span>
              <span>F</span>
            </div>
          </div>
        </div>

        <div>
          <span class="act-field-label">Дефекты ЛКП:</span>
          <div class="act-field-value"><?= htmlspecialchars($paintDefects ?: 'Дефекты ЛКП не указаны') ?></div>
        </div>

        <div>
          <span class="act-field-label">Комплектность транспортного средства:</span>
          <div class="act-field-value">
            <?php if (!empty($equipment)): ?>
              <?php foreach ($equipment as $eq): ?>
                <div><?= htmlspecialchars($eq) ?></div>
              <?php endforeach; ?>
            <?php else: ?>—<?php endif; ?>
          </div>
        </div>

        <div>
          <span class="act-field-label">Особые отметки:</span>
          <div class="act-field-value"><?= htmlspecialchars($specialNotes ?: '') ?></div>
        </div>

      </div>
    </div>

    <!-- =============== ЛЕГЕНДА =============== -->
    <div class="act-legend">
      <?php $leg = []; foreach ($DAMAGE_META as $m) { $leg[] = '<span class="item"><b>'.$m['code'].'</b> - '.$m['label'].'</span>'; }
            echo implode(', ', $leg); ?>
    </div>

    <!-- =============== ПОДПИСИ =============== -->
    <div class="act-signatures">
      <div class="sig">Заказчик: ____________________________</div>
      <div class="sig">Приемщик: ____________________________</div>
    </div>

    <div class="act-page-footer">Страница 1 / 1</div>

  </div>
</div>

</body>
</html>