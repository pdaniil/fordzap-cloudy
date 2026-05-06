<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php");

use Dompdf\Dompdf;

// Подключаемся к базе данных
$DP_Config = new DP_Config;
try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получаем данные из POST
if (empty($_POST['items_data'])) {
    http_response_code(400);
    echo "Нет данных для печати.";
    exit;
}

$data = json_decode($_POST['items_data'], true);

// Проверяем структуру данных
if (!isset($data['items']) || !isset($data['services'])) {
    http_response_code(400);
    echo "Неверная структура данных. Ожидаются items и services.";
    exit;
}

// Функция для получения данных автосервиса по sto_id
function getServiceStationData($db_link, $sto_id) {
    $stmt = $db_link->prepare("SELECT caption, address, phone FROM car_services WHERE id = :id");
    $stmt->bindParam(':id', $sto_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Получаем ID сервиса из POST
$sto_id = isset($_POST['sto_id']) ? intval($_POST['sto_id']) : 0;
$service_station = getServiceStationData($db_link, $sto_id);

// Получаем пробег из POST
$mileage = isset($_POST['mileage']) ? htmlspecialchars($_POST['mileage'], ENT_QUOTES, 'UTF-8') : '________';

// Текущая дата
$current_date = date('d.m.Y');
// Дата следующего обслуживания (прочерк для ручного ввода)
$next_date = '________';

$dompdf = new Dompdf();
$html = '';

// Берем первую услугу и первый товар
$service = $data['services'][0] ?? null;
$item = $data['items'][0] ?? null;

if (!$service || !$item) {
    http_response_code(400);
    echo "Не хватает данных: нужны и товар, и услуга.";
    exit;
}

// Данные сервиса
$service_name = $service_station ? $service_station['caption'] : 'Автосервис';
$service_address = $service_station ? $service_station['address'] : '';
$service_phone = $service_station ? $service_station['phone'] : '';

// Формируем строку "адрес, телефон"
$address_phone = trim($service_address);
if (!empty($service_phone)) {
    $address_phone .= (!empty($address_phone) ? ', ' : '') . $service_phone;
}

// Генерируем HTML для этикетки без границ и таблиц
$html .= '
<div class="label">
    <div class="header">' . htmlspecialchars($service_name, ENT_QUOTES, 'UTF-8') . '</div>
    
    <div class="service-item">' . htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') . '</div>
    <div class="product-item">' . htmlspecialchars($item['name'] . ' ' . $item['article'] . ' ' . $item['manufacturer'], ENT_QUOTES, 'UTF-8') . '</div>
    
    <div class="dates-line">
        <span class="date-label">' . $current_date . '</span>
        <span class="mileage-label">Пробег: ' . $mileage . ' км</span>
    </div>
    <div class="next-date-line">
        <span class="next-label">След. ТО: ' . $next_date . '</span>
    </div>
	<div class="address-phone">' . htmlspecialchars($address_phone, ENT_QUOTES, 'UTF-8') . '</div>
</div>';

// Загрузка HTML в Dompdf
$dompdf->loadHtml('
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Этикетка услуги</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
        }
        .label {
            width: 100%;
			padding-left: 8px;
            font-size: 9px;
            line-height: 1.2;
        }
        .header {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
			border-bottom: 0.5px solid gray;
			padding-right: 8px;
        }
        .address-phone {
            font-size: 8px;
            text-align: center;
            color: #555;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .service-item {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 1px;
            word-wrap: break-word;
			border-bottom: 0.5px solid gray;
        }
        .product-item {
            font-size: 8px;
            margin-bottom: 2px;
            word-wrap: break-word;
			border-bottom: 0.5px solid gray;
        }
        .dates-line {
            font-size: 8px;
            white-space: nowrap;
        }
        .date-label {
            font-weight: bold;
            margin-right: 10px;
        }
        .mileage-label {
            font-weight: bold;
        }
        .next-date-line {
            font-size: 8px;
        }
        .next-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    ' . $html . '
</body>
</html>');

// Устанавливаем размеры этикетки
$width_mm = 58;  // ширина 58мм
$height_mm = 35; // высота 35мм
$width_pt = $width_mm * 2.83465;
$height_pt = $height_mm * 2.83465;

$dompdf->setPaper([0, 0, $width_pt, $height_pt], 'portrait');

// Рендерим PDF
$dompdf->render();

// Открываем PDF в браузере
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=service_label.pdf");

echo $dompdf->output();
?>