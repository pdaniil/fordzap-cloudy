<?php
ini_set("display_errors", 1);
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8mb4;");
} catch (PDOException $e) { exit('Ошибка подключения к БД: ' . $e->getMessage()); }

require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");
DP_User::getAdminSession();

$carId    = (int)($_GET['car_id']    ?? 0);
$recordId = (int)($_GET['record_id'] ?? 0);
$blank    = (int)($_GET['blank']     ?? 0);   // ← 1 = пустой бланк для ручного заполнения

if ($carId <= 0 && $recordId > 0) {
    $s = $db_link->prepare("SELECT car_id FROM car_service_records WHERE id = ?");
    $s->execute([$recordId]);
    $carId = (int)$s->fetchColumn();
}
if ($carId <= 0) exit('Не указан автомобиль');

// ---------- Запись (заказ-наряд) ----------
$record = null;
if ($recordId > 0) {
    $s = $db_link->prepare("SELECT r.*, p.fio AS worker_fio FROM car_service_records r LEFT JOIN car_service_personal p ON p.id = r.worker_id WHERE r.id = ?");
    $s->execute([$recordId]);
    $record = $s->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ---------- СТО ----------
$sto = [];
if ($record && $record['sto_id']) {
    $s = $db_link->prepare("SELECT * FROM car_services WHERE id = ?");
    $s->execute([$record['sto_id']]);
    $sto = $s->fetch(PDO::FETCH_ASSOC) ?: [];
}

// ---------- Авто ----------
$s = $db_link->prepare("SELECT * FROM shop_docpart_garage WHERE id = ?");
$s->execute([$carId]);
$car = $s->fetch(PDO::FETCH_ASSOC);
if (!$car) exit('Автомобиль не найден');

// ---------- Клиент ----------
$clientName = ''; $clientPhone = '';
if ($record && $record['user_id']) {
    $s = $db_link->prepare("SELECT u.email, u.phone, (SELECT data_value FROM users_profiles WHERE user_id=u.user_id AND data_key='surname') AS surname, (SELECT data_value FROM users_profiles WHERE user_id=u.user_id AND data_key='name') AS name, (SELECT data_value FROM users_profiles WHERE user_id=u.user_id AND data_key='patronymic') AS patronymic FROM users u WHERE u.user_id = ?");
    $s->execute([$record['user_id']]);
    $u = $s->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $clientName  = trim(($u['surname'] ?? '').' '.($u['name'] ?? '').' '.($u['patronymic'] ?? ''));
        $clientPhone = $u['phone'] ?: $u['email'];
    }
}

// ---------- Рекламация ----------
if ($blank) {
    // Пустой бланк — не подтягиваем данные из БД, поля оставляем пустыми
    $claimType      = '';
    $defectDesc     = '';
    $additionalInfo = '';
    $claimDate      = date('Y-m-d');
} else {
    $s = $db_link->prepare("SELECT * FROM car_service_reclamations WHERE car_id = ? AND record_id = ?");
    $s->execute([$carId, $recordId]);
    $rec = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    $claimType      = $rec['claim_type'] ?? '';
    $defectDesc     = $rec['defect_desc'] ?? '';
    $additionalInfo = $rec['additional_info'] ?? '';
    $claimDate      = $rec['claim_date'] ?? date('Y-m-d');
}

$docDate  = date('d.m.Y', strtotime($claimDate));
$orderNum = ($record && !empty($record['orders_ids'])) ? (int)$record['orders_ids'] : 0;
$logoUrl  = 'https://fordzap.com/content/files/%D0%A1%D0%BD%D0%B8%D0%BC%D0%BE%D0%BA%20%D1%8D%D0%BA%D1%80%D0%B0%D0%BD%D0%B0%202024-02-01%20223453.png?v=123';

// Пустые линии для ручного заполнения
$emptyLine = '____________________________________________________________________________________';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $blank ? 'Бланк рекламации (претензии)' : 'Рекламация (претензия)' ?><?= $orderNum ? ' по заказ-наряду № ЗН-'.$orderNum : '' ?> от <?= $docDate ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
    * { box-sizing: border-box; }
    html, body { font-family: 'Inter', Arial, sans-serif; background: #f0f7f9; color: rgba(0,0,0,.87); font-size: 12px; margin: 0; padding: 0; }
    .check-content-wrapper { box-shadow: 4px 4px 44px 8px rgba(34,60,80,.2); max-width: 210mm; margin: 10px auto; }
    .check-content { background: #fff; border: 1px solid #D3D3D3; }
    .shop-info { background: #f6f8f9; padding: 10px 16px; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; border-bottom: 1px solid #dee2e6; }
    .shop-left { display: flex; align-items: center; gap: 12px; }
    .shop-logo { width: 56px; height: 56px; border-radius: 50%; background: #fff; border: 2px solid #fff; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .shop-logo img { width: 100%; height: auto; }
    .shop-name { font-weight: 700; font-size: 13px; line-height: 1.3; }
    .shop-address, .shop-phone { color: rgba(0,0,0,.56); font-size: 10px; line-height: 1.4; }
    .shop-right { text-align: right; font-size: 10px; color: #333; line-height: 1.6; white-space: nowrap; }
    .check-header { font-size: 15px; line-height: 1.2; padding: 8px 16px 6px; text-align: center; font-weight: 700; border-bottom: 1px solid #e9ecef; }
    .check-header .blank-note { display: block; font-size: 10px; font-weight: 400; color: #6c757d; margin-top: 2px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1px 24px; padding: 8px 16px 10px; font-size: 11px; line-height: 1.55; border-bottom: 1px solid #e9ecef; }
    .info-row { display: flex; gap: 6px; }
    .info-row .k { color: #495057; min-width: 96px; flex-shrink: 0; }
    .info-row .v { color: #000; font-weight: 600; word-break: break-word; }
    .info-row .v.empty { color: #adb5bd; font-weight: 400; }
    .act-fields { display: flex; flex-direction: column; gap: 10px; padding: 0 16px 10px; }
    .act-field-label { font-weight: 700; font-size: 10.5px; display: block; margin-bottom: 2px; }
    .act-field-value { font-size: 10px; color: #333; white-space: pre-wrap; word-break: break-word; line-height: 1.5; }
    .act-field-value.box { border: 1px solid #dee2e6; border-radius: 4px; padding: 6px 8px; min-height: 40px; }
    .act-field-value.box.lg { min-height: 70px; }
    .blank-lines { border-bottom: 1px dashed #adb5bd; height: 18px; margin-top: 6px; }
    .blank-lines + .blank-lines { margin-top: 4px; }
    .act-legal-text { font-size: 10px; line-height: 1.6; color: #000; padding: 0 16px 10px; }
    .act-legal-text p { margin: 0 0 4px 0; }
    .act-signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding: 10px 16px 12px; }
    .act-signatures .sig { font-size: 11px; color: #333; padding-top: 14px; border-top: 1px solid #000; text-align: center; }
    .act-page-footer { padding: 4px 16px 8px; text-align: right; font-size: 9px; color: #6c757d; border-top: 1px solid #e9ecef; }
    .no-print { position: fixed; top: 16px; right: 16px; z-index: 1000; display: flex; gap: 8px; }
    @media print {
        @page { size: A4 portrait; margin: 8mm; }
        html, body { background: #fff !important; font-size: 11px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .no-print { display: none !important; }
        .check-content-wrapper { box-shadow: none !important; max-width: 100% !important; margin: 0 !important; }
        .check-content { border: none !important; }
        .shop-info { background: #f6f8f9 !important; }
        .act-fields, .act-signatures, .info-grid, .act-legal-text { break-inside: avoid !important; page-break-inside: avoid !important; }
    }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print();return false;" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i> Печать</button>
</div>

<div class="check-content-wrapper">
  <div class="check-content">

    <!-- Шапка СТО -->
    <div class="shop-info">
      <div class="shop-left">
        <div class="shop-logo"><img src="<?= htmlspecialchars($logoUrl) ?>" alt=""></div>
        <div>
          <div class="shop-name"><?= htmlspecialchars($sto['organization'] ?? $sto['caption'] ?? 'Автосервис') ?></div>
          <?php if (!empty($sto['inn'])): ?><div class="shop-address">ИНН: <?= htmlspecialchars($sto['inn']) ?></div><?php endif; ?>
          <?php if (!empty($sto['address'])): ?><div class="shop-address"><?= htmlspecialchars($sto['address']) ?></div><?php endif; ?>
          <?php if (!empty($sto['phone'])): ?><div class="shop-phone">Тел.: <?= htmlspecialchars($sto['phone']) ?></div><?php endif; ?>
        </div>
      </div>
      <div class="shop-right">
        <div>Дата: <b><?= $blank ? '_____________' : $docDate ?></b></div>
      </div>
    </div>

    <!-- Заголовок -->
    <div class="check-header">
      <?= $blank ? 'Бланк рекламации (претензии)' : 'Рекламация (претензия)' ?><?= $orderNum ? ' по заказ-наряду № '.$_GET["record_id"]: '' ?>
      <?php if ($blank): ?>
        <span class="blank-note">Форма для ручного заполнения</span>
      <?php endif; ?>
    </div>

    <!-- Инфо о клиенте и ТС -->
    <div class="info-grid">
      <div>
        <div class="info-row"><span class="k">Заказчик (от кого):</span><span class="v"><?= htmlspecialchars($clientName ?: '—') ?></span></div>
        <div class="info-row"><span class="k">Телефон:</span>        <span class="v"><?= htmlspecialchars($clientPhone ?: '—') ?></span></div>
        <div class="info-row"><span class="k">Исполнитель (кому):</span><span class="v"><?= htmlspecialchars($sto['organization'] ?? $sto['caption'] ?? '—') ?></span></div>
        <div class="info-row"><span class="k">Адрес исполнителя:</span><span class="v"><?= htmlspecialchars($sto['address'] ?? '—') ?></span></div>
      </div>
      <div>
        <div class="info-row"><span class="k">Марка, модель:</span>   <span class="v"><?= htmlspecialchars(trim(($car['marka'] ?? '').' '.($car['model'] ?? '')) ?: '—') ?></span></div>
        <div class="info-row"><span class="k">VIN-номер:</span>       <span class="v"><?= htmlspecialchars($car['vin'] ?? '—') ?></span></div>
        <div class="info-row"><span class="k">Гос.номер:</span>       <span class="v"><?= htmlspecialchars($car['license_plate'] ?? '—') ?></span></div>
        <div class="info-row"><span class="k">Год выпуска:</span>     <span class="v"><?= htmlspecialchars($car['year'] ?? '—') ?></span></div>
      </div>
    </div>

    <?php if ($blank): ?>
    <!-- ================== ПУСТОЙ БЛАНК ================== -->
    <div class="act-fields">
      <div>
        <span class="act-field-label">Основание:</span>
        <div class="act-field-value box">
          Заказ-наряд № -________ от ____.____.________
          <div class="blank-lines"></div>
        </div>
      </div>
      <div>
        <span class="act-field-label">Суть требования:</span>
        <div class="act-field-value box lg">
          <div class="blank-lines"></div>
          <div class="blank-lines"></div>
          <div class="blank-lines"></div>
        </div>
      </div>
      <div>
        <span class="act-field-label">Описание выявленных недостатков:</span>
        <div class="act-field-value box lg">
          <div class="blank-lines"></div>
          <div class="blank-lines"></div>
          <div class="blank-lines"></div>
          <div class="blank-lines"></div>
          <div class="blank-lines"></div>
        </div>
      </div>
      <div>
        <span class="act-field-label">Дополнительная информация:</span>
        <div class="act-field-value box lg">
          <div class="blank-lines"></div>
          <div class="blank-lines"></div>
        </div>
      </div>
    </div>
    <?php else: ?>
    <!-- ================== ЗАПОЛНЕННАЯ РЕКЛАМАЦИЯ ================== -->
    <div class="act-fields">
      <div>
        <span class="act-field-label">Основание:</span>
        <div class="act-field-value">Заказ-наряд № ЗН-<?= $orderNum ?: '—' ?> от <?= $record ? date('d.m.Y', strtotime($record['time'])) : '—' ?></div>
      </div>
      <div>
        <span class="act-field-label">Суть требования:</span>
        <div class="act-field-value"><?= htmlspecialchars($claimType ?: '—') ?></div>
      </div>
      <div>
        <span class="act-field-label">Описание выявленных недостатков:</span>
        <div class="act-field-value box"><?= nl2br(htmlspecialchars($defectDesc ?: '—')) ?></div>
      </div>
      <div>
        <span class="act-field-label">Дополнительная информация:</span>
        <div class="act-field-value box"><?= nl2br(htmlspecialchars($additionalInfo ?: '—')) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Юридический текст -->
    <div class="act-legal-text">
      <p>На основании ст. 4, 29 Закона РФ от 07.02.1992 № 2300-1 «О защите прав потребителей», а также п. 28 Правил оказания услуг (выполнения работ) по техническому обслуживанию и ремонту автомототранспортных средств, прошу рассмотреть настоящую претензию и удовлетворить изложенные требования в установленный законом срок.</p>
      <p>В случае отказа или отсутствия ответа в установленный срок, я оставляю за собой право обратиться в суд с требованием о возмещении убытков, неустойки и компенсации морального вреда.</p>
    </div>

    <!-- Приложения -->
    <div class="act-fields">
      <div>
        <span class="act-field-label">Приложения:</span>
        <div class="act-field-value">
          <?php if ($blank): ?>
            <div>1. ___________________________________________________________________________</div>
            <div>2. ___________________________________________________________________________</div>
            <div>3. ___________________________________________________________________________</div>
          <?php else: ?>
            <div>1. Копия заказ-наряда № ЗН-<?= $orderNum ?: '—' ?></div>
            <div>2. Копия квитанции об оплате работ</div>
            <div>3. Фотоматериалы / иные доказательства</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Подписи -->
    <div class="act-signatures">
      <div class="sig">Заказчик: ____________________________</div>
      <div class="sig">Исполнитель: __________________________</div>
    </div>

    <div class="act-page-footer">Страница 1 / 1</div>

  </div>
</div>

</body>
</html>