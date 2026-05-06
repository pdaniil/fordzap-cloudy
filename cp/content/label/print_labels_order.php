<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php");

use Dompdf\Dompdf;
use chillerlan\QRCode\QRCode;

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

$data = json_decode($_POST['items_data'], true); // Декодируем JSON

// Функция для получения телефона по office_id
function getPhoneByOfficeId($db_link, $office_id) {
    $stmt = $db_link->prepare("SELECT phone FROM shop_offices WHERE id = :id");
    $stmt->bindParam(':id', $office_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['phone'] ?? 'Телефон не указан';
}

$dompdf = new Dompdf();
$html = '';
$totalItems = count($data); // Количество товаров

// Генерируем HTML для каждой этикетки
foreach ($data as $index => $item) {
    $barcode = (new QRCode)->render($item['article']); // Генерация QR-кода из артикула
    $phone = getPhoneByOfficeId($db_link, $item['office_id']); // Получаем телефон из базы данных

    $html .= '
    <div ' . ($index + 1 !== $totalItems ? 'style="page-break-after: always;"' : '') . '>
        <table>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <span style="font-weight: bold; font-size: 20px;">FORDZAP</span><br>
                    контрактные автозапчасти
                </td>
            </tr>
            <tr class="center">
                <td style="width: 30%;">
                    <img src="' . htmlspecialchars($barcode, ENT_QUOTES, 'UTF-8') . '" alt="QR Code" width="100%"/>
                </td>
                <td style="text-align: left;">
                    ' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center;">
                    ' . htmlspecialchars($item['manufacturer'], ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($item['article'], ENT_QUOTES, 'UTF-8') . ' | <b>Заказ ' . htmlspecialchars($item['order_id'], ENT_QUOTES, 'UTF-8') . '
                </b>
                </td>
            </tr>
            <tr>
                <td style="text-align: center">fordzap.com</td>
                <td style="text-align: center">' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>
    </div>';
}

// Загрузка HTML в Dompdf
$dompdf->loadHtml('
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Этикетки</title>
    <style>
        html {
            width: 100%;
            height: 100%;
            padding: 0;
            margin: 0;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 8px;
        }
        table {
            border-collapse: collapse;
            margin: 0 auto;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        td {
            padding: 1px;
            vertical-align: top;
        }
        .center {
            text-align: center;
        }
    </style>
</head>
<body>
    ' . $html . '
</body>
</html>');

// Устанавливаем размеры этикетки
$dompdf->setPaper([0, 0, 113.5, 170], 'landscape');

// Рендерим PDF
$dompdf->render();

// Открываем PDF в браузере
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=item.pdf"); // inline для открытия в браузере

echo $dompdf->output();
