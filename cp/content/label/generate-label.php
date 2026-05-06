<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php");

use Dompdf\Dompdf;
use chillerlan\QRCode\{QRCode, QROptions};

$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Получаем и проверяем параметр product_id из GET-запроса
if (isset($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
} else {
    die("Некорректный идентификатор продукта.");
}

// Подготавливаем и выполняем объединенный запрос для получения всех данных
try {
    $sql = "
    SELECT
        scp.caption,
        -- Артикул
        (SELECT spvt.value
         FROM shop_properties_values_text spvt
         INNER JOIN shop_categories_properties_map scpm
            ON spvt.property_id = scpm.id
         WHERE spvt.product_id = scp.id
            AND scpm.category_id = scp.category_id
            AND scpm.value = 'Артикул'
            AND scpm.property_type_id = 3
         LIMIT 1) AS article,
        -- Состояние
        (SELECT spvt.value
         FROM shop_properties_values_text spvt
         INNER JOIN shop_categories_properties_map scpm
            ON spvt.property_id = scpm.id
         WHERE spvt.product_id = scp.id
            AND scpm.category_id = scp.category_id
            AND scpm.value = 'Состояние'
            AND scpm.property_type_id = 3
         LIMIT 1) AS cond,
        -- Штрих-код
        (SELECT spvt.value
         FROM shop_properties_values_text spvt
         INNER JOIN shop_categories_properties_map scpm
            ON spvt.property_id = scpm.id
         WHERE spvt.product_id = scp.id
            AND scpm.category_id = scp.category_id
            AND scpm.value = 'Штрих-код'
            AND scpm.property_type_id = 3
         LIMIT 1) AS barcode,
        -- Магазин
        (SELECT spvtl.value
         FROM shop_properties_values_tree_list spvtl
         WHERE spvtl.product_id = scp.id
            AND (spvtl.value = 15026 OR spvtl.value = 15027)
         LIMIT 1) AS shop,
        -- Производитель
        (SELECT slli.value
         FROM shop_properties_values_list spvl
         INNER JOIN shop_categories_properties_map scpm
            ON spvl.property_id = scpm.id
         INNER JOIN shop_line_lists_items slli
            ON spvl.value = slli.id
         WHERE spvl.product_id = scp.id
            AND scpm.category_id = scp.category_id
            AND scpm.value = 'Производитель'
            AND scpm.property_type_id = 5
         LIMIT 1) AS manufacturer,
        -- Номер ячейки (cell_number)
        (SELECT ssd.cell_number
         FROM shop_storages_data ssd
         WHERE ssd.product_id = scp.id
         LIMIT 1) AS cell_number
    FROM shop_catalogue_products scp
    WHERE scp.id = :product_id;
";


    $stmt = $db_link->prepare($sql);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();

    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        echo 'Продукт не найден.';
        exit;
    }

    $caption = $product['caption'];
    $article = $product['article'] ?? 'Не указано';
    $condition = $product['cond'] ?? 'Не указано';
    $manufacturer = $product['manufacturer'] ?? 'Не указано';
    $barcode = $product['barcode'] ?? 'Не указано';
    $shop = $product['shop'] ?? '';
    $cell = $product['cell_number'] ?? '';

} catch (PDOException $e) {
    echo 'Ошибка при получении данных продукта: ' . $e->getMessage();
    exit;
}

function getPhoneByShop($db_link, $shop) {
    // Определяем ID записи в зависимости от значения $shop
    switch ($shop) {
        case 15026:
            $id = 1;
            break;
        case 15027:
            $id = 3;
            break;
        default:
            return 'Телефон не указан';
    }

    try {
        $stmt = $db_link->prepare("SELECT phone FROM shop_offices WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['phone'])) {
            return htmlspecialchars($result['phone'], ENT_QUOTES, 'UTF-8');
        } else {
            return 'Телефон не указан';
        }
    } catch (PDOException $e) {
        return 'Ошибка при получении телефона: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Получаем номер телефона для текущего магазина
$phone = getPhoneByShop($db_link, $shop);

$barcode = (new QRCode)->render($barcode);
//printf('<img src="%s" alt="QR Code" />', $barcode);

$dompdf = new Dompdf();
$path = $_SERVER["DOCUMENT_ROOT"] . '/content/files/images/fordzap.jpg';
$type = pathinfo($path, PATHINFO_EXTENSION);
$data = file_get_contents($path);
$logo = 'data:image/' . $type . ';base64,' . base64_encode($data);

$html = '
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Информация о продукте</title>
    <style>
           html{
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
    <table>
            <!-- Первая строка: Изображение -->
        <tr>
            <td colspan="3" style="text-align: center;">
                <span style="font-weight: bold; font-size: 20px;">FORDZAP</span><br>
                контрактный автозапчасти
            </td>            
        </tr>
        <tr class="center">
            <td style="width: 30%;">
                <img src="' . htmlspecialchars($barcode, ENT_QUOTES, 'UTF-8') . '" alt="QR Code" width="100%"/>
            </td>
            
            <td colspan="2" style="text-align: left;">
                ' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '
            </td>            
                          
        </tr>
        <!-- Вторая строка: Производитель и артикул -->
        <tr>
            <td colspan="3" style="text-align: center;">
                сост. ' . htmlspecialchars($condition, ENT_QUOTES, 'UTF-8') . ' | ' . htmlspecialchars($manufacturer, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($article, ENT_QUOTES, 'UTF-8') . '
            </td>
        </tr>
        <!-- Третья строка: Дополнительный текст -->
        <tr>
            <td style="text-align: center">' . htmlspecialchars('fordzap.com', ENT_QUOTES, 'UTF-8') . '</td>
            <td style="text-align: center">' . $phone . '</td>
            <td style="text-align: center;">
                <b>' . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') . '</b>               
            </td> 
        </tr>
    </table>
</body>
</html>
';

//echo $html;



$dompdf->loadHtml($html);

// размеры этикетки в пикселях
$dompdf->setPaper(array(0, 0, 113.5, 170), 'landscape');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream('', array('Attachment' => 0));

?>
