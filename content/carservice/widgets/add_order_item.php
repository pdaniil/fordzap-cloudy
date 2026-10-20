<?php
/**
 * Обработчик добавления товара в заказ (из каталога или произвольно)
 * Ожидает POST-параметры:
 *   - order_id
 *   - item_product_type (1 – из каталога, 2 – произвольно)
 *   - для типа 1: id_storages_data, count_need
 *   - для типа 2: art, man, name, storage_id, count_need, price, price_zakup,
 *                 time_to_exe, time_to_exe_guaranteed
 *   - office_id, user_id, group_id – обязательны для расчёта цены
 * Возвращает JSON: {status: true/false, message: string}
 */

// Подключение ядра системы
define('_ASTEXE_', 1);
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

// Проверка авторизации менеджера
$manager_id = DP_User::getAdminId();
if (!$manager_id) {
    echo json_encode(['status' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Защита от CSRF
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

// Получение и валидация параметров
$order_id = (int)$_POST['order_id'];
$product_type = (int)$_POST['item_product_type'];
$office_id = (int)$_POST['office_id'];
$user_id = (int)$_POST['user_id'];
$group_id = (int)$_POST['group_id'];

if (!$order_id || !$office_id || !$user_id) {
    echo json_encode(['status' => false, 'message' => 'Отсутствуют обязательные параметры']);
    exit;
}

$db_link = $GLOBALS['db_link'];

// Подстрока для умножения цены на курс валюты склада
$SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";

// ---- Обработка товара из каталога (тип 1) ----
if ($product_type === 1) {
    $id_storages_data = (int)$_POST['id_storages_data'];
    $count_need = (int)$_POST['count_need'];

    if (!$id_storages_data || $count_need <= 0) {
        echo json_encode(['status' => false, 'message' => 'Некорректные данные товара']);
        exit;
    }

    // Запрос данных товара с учётом наценки офиса и группы
    $sql = "
        SELECT *,
            `price` * $SQL_currency_rate 
            + `price` * $SQL_currency_rate * (SELECT `markup`/100 FROM `shop_offices_storages_map` 
                WHERE `office_id` = $office_id 
                AND `storage_id` = `shop_storages_data`.`storage_id` 
                AND `group_id` = $group_id 
                AND `shop_storages_data`.`price`*$SQL_currency_rate >= `min_point` 
                AND `shop_storages_data`.`price`*$SQL_currency_rate < `max_point`) AS `customer_price`,
            (SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`) AS 'caption',
            (SELECT `value` FROM `shop_properties_values_text` 
                WHERE `property_id` = (SELECT `id` FROM `shop_categories_properties_map` 
                    WHERE `category_id` = `shop_storages_data`.`category_id` 
                    AND `value` = 'Артикул' AND `property_type_id` = 3) 
                AND `product_id` = `shop_storages_data`.`product_id`) AS article,
            (SELECT `value` FROM `shop_line_lists_items` 
                WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` 
                    WHERE `product_id` = `shop_storages_data`.`product_id` 
                    AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` 
                        WHERE `category_id` = `shop_storages_data`.`category_id` 
                        AND `value` = 'Производитель' AND `property_type_id` = 5))) AS manufacturer,
            (SELECT `value` FROM `shop_catalogue_categories` 
                WHERE `id` = (SELECT `category_id` FROM `shop_catalogue_products` 
                    WHERE `id` = `shop_storages_data`.`product_id`)) AS 'category',
            (SELECT `name` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) AS 'storage_name'
        FROM `shop_storages_data` 
        WHERE `id` = $id_storages_data
    ";

    $query = $db_link->prepare($sql);
    $query->execute();
    $product = $query->fetch();

    if (!$product) {
        echo json_encode(['status' => false, 'message' => 'Товар не найден']);
        exit;
    }

    // Проверка остатка
    if ($count_need > $product['exist']) {
        echo json_encode(['status' => false, 'message' => 'Недостаточно товара на складе']);
        exit;
    }

    // Расчёт цены с округлением
    $work_price = $product['customer_price'];
    if ($DP_Config->price_rounding == '1') {
        $work_price = ($work_price > (int)$work_price) ? (int)$work_price + 1 : (int)$work_price;
    } elseif ($DP_Config->price_rounding == '2') {
        $work_price = (integer)$work_price;
        $last = (integer)substr((string)$work_price, -1);
        if ($last > 0 && $last < 5) $work_price += 5 - $last;
        elseif ($last > 5 && $last <= 9) $work_price += 10 - $last;
    } elseif ($DP_Config->price_rounding == '3') {
        $work_price = (integer)$work_price;
        if ((integer)substr((string)$work_price, -1) != 0)
            $work_price += 10 - (integer)substr((string)$work_price, -1);
    }

    // Подготовка данных для вставки
    $product_id = $product['product_id'];
    $price = $work_price;
    $price_zakup = (float)$product['price_purchase'];
    $article = $product['article'];
    $manufacturer = $product['manufacturer'];
    $name = $product['caption'];
    $storage_id = (int)$product['storage_id'];
    $t2_exist = $product['exist'];
    $time_to_exe = 0;
    $time_to_exe_guaranteed = 0;

} 
// ---- Произвольный товар (тип 2) ----
elseif ($product_type === 2) {
    $article = trim($_POST['art']);
    $manufacturer = trim($_POST['man']);
    $name = trim($_POST['name']);
    $storage_id = (int)$_POST['storage_id'];
    $count_need = (int)$_POST['count_need'];
    $price = (float)$_POST['price'];
    $price_zakup = (float)$_POST['price_zakup'];
    $time_to_exe = (int)$_POST['time_to_exe'];
    $time_to_exe_guaranteed = (int)$_POST['time_to_exe_guaranteed'];

    if ($count_need <= 0 || $price <= 0) {
        echo json_encode(['status' => false, 'message' => 'Некорректные данные произвольного товара']);
        exit;
    }

    $product_id = 0;
    $t2_exist = 100; // для произвольного товара наличие не контролируется
} else {
    echo json_encode(['status' => false, 'message' => 'Неизвестный тип товара']);
    exit;
}

// ---- Добавление позиции в заказ ----
// Получаем статус "создан" для позиции
$status_query = $db_link->prepare("SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `for_created` = 1;");
$status_query->execute();
$status_record = $status_query->fetch();
$for_created_status = $status_record['id'];

$sql_insert = "
    INSERT INTO `shop_orders_items` (
        `order_id`, `product_type`, `price`, `count_need`, `product_id`, `status`,
        `t2_manufacturer`, `t2_article`, `t2_article_show`, `t2_name`, `t2_exist`,
        `t2_time_to_exe`, `t2_time_to_exe_guaranteed`, `t2_storage`, `t2_min_order`,
        `t2_probability`, `t2_markup`, `t2_price_purchase`, `t2_office_id`, `t2_storage_id`,
        `t2_product_json`, `sao_state`, `sao_robot`, `t2_json_params`
    ) VALUES (
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?
    )
";

$bind = [
    $order_id, $product_type, $price, $count_need, $product_id, $for_created_status,
    $manufacturer, $article, $article, $name, $t2_exist,
    $time_to_exe, $time_to_exe_guaranteed, '', 1,
    100, 0, $price_zakup, $office_id, $storage_id,
    '', 0, 0, ''
];

$stmt = $db_link->prepare($sql_insert);
if (!$stmt->execute($bind)) {
    echo json_encode(['status' => false, 'message' => 'Ошибка добавления позиции в заказ']);
    exit;
}

$order_item_id = $db_link->lastInsertId();

// Если товар из каталога – резервируем и создаём детализацию
if ($product_type === 1) {
    // Добавляем запись в shop_orders_items_details
    $detail_sql = "INSERT INTO `shop_orders_items_details` 
        (`order_id`, `order_item_id`, `office_id`, `storage_id`, `storage_record_id`, `count_reserved`, `price_purchase`) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
    $detail_bind = [$order_id, $order_item_id, $office_id, $storage_id, $id_storages_data, $count_need, $price_zakup];
    $detail_stmt = $db_link->prepare($detail_sql);
    if ($detail_stmt->execute($detail_bind)) {
        // Резервируем товар на складе
        $reserve_sql = "UPDATE `shop_storages_data` SET `exist` = `exist` - ?, `reserved` = `reserved` + ? WHERE `id` = ?";
        $reserve_stmt = $db_link->prepare($reserve_sql);
        $reserve_stmt->execute([$count_need, $count_need, $id_storages_data]);
    }
}

echo json_encode(['status' => true, 'message' => 'Товар успешно добавлен в заказ']);
exit;