<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php");

use League\Csv\Writer;
use League\Csv\CannotInsertRecord;

$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

try {
    $stmt = $db_link->query("
        SELECT 
            avs.*, 
            avc.* 
        FROM 
            avito_settings avs, 
            avito_categories avc
    ");
    $avitoData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $avito = [];
    $avitoCategory = [];

    foreach ($avitoData as $row) {
        if (isset($row['delivery'])) {
            $avito[] = $row;
        }
        $avitoCategory[] = $row;
    }

    // Обработка данных доставки
    $delivery = '';
    if (isset($avito[0]['delivery']) && !empty($avito[0]['delivery'])) {
        $data = json_decode($avito[0]['delivery'], true);
        if ($data !== null) {
            $delivery = implode(' | ', array_keys($data));
        }
    }

    // Объединенный запрос для shop_offices
    $stmtOffices = $db_link->query('SELECT * FROM shop_offices');
    $offices = $stmtOffices->fetchAll(PDO::FETCH_ASSOC);

    // Генерация адресов
    $address1 = $offices[0]['region'] . ', ' . $offices[0]['city'] . ', ' . $offices[0]['address'];
    $address2 = $offices[1]['region'] . ', ' . $offices[1]['city'] . ', ' . $offices[1]['address'];

    // Основной запрос
    $sql = "
    SELECT
        scp.caption,
        GROUP_CONCAT(spi.file_name SEPARATOR ' | ') AS file_names,
        ssd.product_id,
        ssd.storage_id,
        ssd.exist,
        ssd.price,
        spt.content,
        ac.name AS avito_category_name,
        ac.parent_id AS avito_category_parent,
        (SELECT `value` 
         FROM `shop_line_lists_items` 
         WHERE `id` = (SELECT `value` 
                       FROM `shop_properties_values_list` 
                       WHERE `product_id` = scp.id 
                       AND `property_id` = (SELECT `id` 
                                            FROM `shop_categories_properties_map` 
                                            WHERE `category_id` = scp.category_id 
                                            AND `value` = 'Производитель' 
                                            AND `property_type_id` = 5)
                      )
        ) AS brand,
        (SELECT `value` 
         FROM `shop_properties_values_text` 
         WHERE `property_id` = (SELECT `id` 
                                FROM `shop_categories_properties_map` 
                                WHERE `category_id` = scp.category_id 
                                AND `value` = 'Артикул' 
                                AND `property_type_id` = 3) 
         AND `product_id` = scp.id
        ) AS article,
        (SELECT `value` 
         FROM `shop_properties_values_text` 
         WHERE `property_id` = (SELECT `id` 
                                FROM `shop_categories_properties_map` 
                                WHERE `category_id` = scp.category_id 
                                AND `value` = 'Состояние' 
                                AND `property_type_id` = 3) 
         AND `product_id` = scp.id
        ) AS cond
    FROM
        shop_catalogue_products scp
    JOIN
        shop_products_images spi ON scp.id = spi.product_id
    JOIN
        shop_storages_data ssd ON ssd.product_id = scp.id
    JOIN
        shop_products_text spt ON spt.product_id = scp.id
    JOIN
        shop_catalogue_categories scc ON scp.category_id = scc.id
    JOIN
        avito_categories_mapping acm ON scc.id = acm.shop_catalogue_categorie_id
    JOIN
        avito_categories ac ON acm.avito_category_id = ac.id
    WHERE
        ssd.exist > 0
    GROUP BY
        scp.id, scp.caption, ssd.product_id, ssd.storage_id, ssd.exist, ssd.price, spt.content, ac.name, ac.parent_id;
    ";

    $stmt = $db_link->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Путь к файлу CSV
    $csvFilePath = $_SERVER["DOCUMENT_ROOT"] . '/content/files/Documents/avito/9ef70430-01b7-48d2-a537-e83a823ee4ae.csv';

    // Создание CSV файла
    $csv = Writer::createFromPath($csvFilePath, 'w+');

    // Добавление заголовков
    if (!empty($results)) {
        $urlToImage = 'https://fordzap.com/content/files/images/products_images/';
        $headers = [
            'Id', 'ContactPhone', 'Address', 'ContactMethod', 'InternetCalls', 'Delivery', 'Category', 'Title', 'Description',
            'Price', 'ImageUrls', 'GoodsType', 'AdType', 'ProductType', 'SparePartType', 'EngineSparePartType',
            'BodySparePartType', 'Condition', 'Brand', 'OEM'
        ];
        $csv->insertOne($headers);

        // Добавление данных
        foreach ($results as $row) {
            if (empty($row['article'])) {
                continue;
            }
            $fileNamesArray = !empty($row['file_names']) ? explode('|', $row['file_names']) : [];
            $images = implode(' | ', array_map(function ($fileName) use ($urlToImage) {
                return $urlToImage . trim($fileName);
            }, $fileNamesArray));

            $sparePartType = $row['avito_category_parent'] === null ? $row['avito_category_name'] : findNameById($avitoCategory, $row['avito_category_parent']);

            $data = [
                $row['product_id'],
                $row['storage_id'] == 1 ? $offices[0]['phone'] : $offices[1]['phone'],
                $row['storage_id'] == 1 ? $address1 : $address2,
                $avito[0]['contact_method'],
                $avito[0]['interne_calls'],
                $delivery,
                'Запчасти и аксессуары',
                $row['caption'],
                $row['caption'] . ' <br>' . $row['content'],
                intval($row['price']),
                $images,
                'Запчасти',
                'Товар приобретен на продажу',
                'Для автомобилей',
                $sparePartType,
                $sparePartType == 'Двигатель' ? $row['avito_category_name'] : '',
                $sparePartType == 'Кузов' ? $row['avito_category_name'] : '',
                $value = isset($row['cond']) ? $row['cond'] : 'б/у',
                html_entity_decode(trim($row['brand'])),
                $row['article']
            ];
            $csv->insertOne($data);
        }
    }
    echo json_encode(["success" => true, "message" => "Успешно создан файл: " . $csvFilePath]);
} catch (\PDOException $e) {
    echo json_encode(["success" => false, "message" => "Connection error: " . $e->getMessage()]);
}

// Функция поиска имени по id
function findNameById($data, $id) {
    $ids = array_column($data, 'id');
    $index = array_search($id, $ids);
    return $index !== false ? $data[$index]['name'] : null;
}