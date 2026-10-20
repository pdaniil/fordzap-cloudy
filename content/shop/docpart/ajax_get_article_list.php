<?php
header('Content-Type: application/json;charset=utf-8;');

//Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

//Обработчик ошибок
function handleError($errno, $errstr, $errfile, $errline) {
    $result = array('list' => array());
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
set_error_handler('handleError');

//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_link->query("SET NAMES utf8;");
} catch (PDOException $e) {
    $result = array("list" => array());
    exit(json_encode($result, JSON_UNESCAPED_UNICODE));
}

//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();

// Функции для построения URL
function getCategoryPath($categoryId, $db_link, $path = array()) {
    try {
        if (empty($categoryId) || $categoryId == 0) {
            return $path;
        }
        
        $stmt = $db_link->prepare("
            SELECT 
                id,
                alias,
                parent
            FROM 
                shop_catalogue_categories
            WHERE 
                id = ?
        ");
        
        $stmt->execute(array($categoryId));
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            return $path;
        }
        
        array_unshift($path, $category['alias']);
        
        if (!empty($category['parent']) && $category['parent'] != 0) {
            return getCategoryPath($category['parent'], $db_link, $path);
        }
        
        return $path;
        
    } catch (PDOException $e) {
        return $path;
    }
}

function getProductUrl($productId, $db_link) {
    try {
        $stmt = $db_link->prepare("
            SELECT 
                p.id,
                p.alias as product_alias,
                p.category_id
            FROM 
                shop_catalogue_products p
            WHERE 
                p.id = ?
        ");
        
        $stmt->execute(array($productId));
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return false;
        }
        
        $categoryPath = getCategoryPath($product['category_id'], $db_link);
        $url = array();
        
        if (!empty($categoryPath)) {
            $url = array_merge($url, $categoryPath);
        }
        
        if (!empty($product['product_alias'])) {
            $url[] = $product['product_alias'];
        }
        
        if (empty($url)) {
            return false;
        }
        
        return '/' . implode('/', $url);
        
    } catch (PDOException $e) {
        return false;
    }
}

// Функция для проверки наличия товара на складе
function checkProductAvailability($productId, $db_link) {
    try {
        $stmt = $db_link->prepare("
            SELECT 
                SUM(exist) as total_exist,
                MIN(price) as min_price
            FROM 
                shop_storages_data
            WHERE 
                product_id = ?
                AND exist IS NOT NULL 
                AND exist > 0
                AND price IS NOT NULL
        ");
        
        $stmt->execute(array($productId));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['total_exist'] > 0) {
            return array(
                'has_stock' => true,
                'quantity' => (int)$result['total_exist'],
                'price' => (float)$result['min_price']
            );
        }
        
        return array(
            'has_stock' => false,
            'quantity' => 0,
            'price' => null
        );
        
    } catch (PDOException $e) {
        return array(
            'has_stock' => false,
            'quantity' => 0,
            'price' => null
        );
    }
}

////////////////////////////////////////////
$request_object = json_decode($_POST['request_object'], true);
$value = isset($request_object['value']) ? $request_object['value'] : '';
$cnt_list = 50;
$catalog_items = array();
$catalog_items_no_stock = array();
$dump_list = array();

$standart_list = array( 
    array('article'=>'C110', 'manufacturer'=>'DOLZ', 'name'=>'Насос водяной'),
    array('article'=>'12345', 'manufacturer'=>'FEBI', 'name'=>'Подвески для двигателя и передачи'),
    array('article'=>'S56545', 'manufacturer'=>'BREMBO', 'name'=>'Тормозные колодки')
);

// Функция для добавления элемента в список (совместимость со старым PHP)
function addToList($item, $list, $dump_list, $source = 'other', $stock_info = null) {
    $dump = md5($item['article'] . $item['manufacturer']);
    if (array_search($dump, $dump_list) === false) {
        $dump_list[] = $dump;
        $item['source'] = $source;
        
        if ($stock_info) {
            $item['has_stock'] = $stock_info['has_stock'];
            $item['quantity'] = $stock_info['quantity'];
            $item['price'] = $stock_info['price'];
        } else {
            $item['has_stock'] = false;
            $item['quantity'] = 0;
            $item['price'] = null;
        }
        
        $list[] = $item;
    }
    return array($list, $dump_list);
}

// Получаем последние запросы
$where = '';
$binding_values = array();

if($value == ''){
    if(isset($_COOKIE["shop_stat"])) {
        $shop_stat = explode('_', $_COOKIE["shop_stat"]);
        if(is_array($shop_stat)){
            $str = '';
            foreach($shop_stat as $item){
                if($str != ''){
                    $str .= ',';
                }
                $str .= '?';
                $binding_values[] = (int)$item;
            }
            if($str != ''){
                $where = '`id` IN('.$str.')';
            }
        }
    }
    if($user_id > 0){
        if($where != ''){
            $where .= ' AND '; 
        }
        $where .= '`user_id` = ?';
        $binding_values[] = $user_id;
    }
}

if($value != ''){
    if($where != ''){
        $where .= ' AND '; 
    }
    $where .= '(`article` LIKE ? OR `name` LIKE ?)';
    $binding_values[] = $value.'%';
    $binding_values[] = '%'.$value.'%';
}

// 1. Товары из каталога (по артикулу)
if($value != ''){
    $sql_article = "SELECT `product_id`, `value` FROM `shop_properties_values_text` WHERE `property_id` IN(SELECT `id` FROM `shop_categories_properties_map` WHERE `value` = 'Артикул' AND `property_type_id` = 3) AND `value` LIKE ? LIMIT " . ($cnt_list * 2);
    $query_article = $db_link->prepare($sql_article);
    $query_article->execute(array($value.'%'));
    while($record_article = $query_article->fetch()){
        $product_id = $record_article['product_id'];
        
        $sql_manufacturer = "SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = ? AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = (SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = ?) AND `value` = 'Производитель' AND `property_type_id` = 5))";
        $query_manufacturer = $db_link->prepare($sql_manufacturer);
        $query_manufacturer->execute(array($product_id, $product_id));
        $record_manufacturer = $query_manufacturer->fetch();
        
        $sql_product = 'SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = ?';
        $query_product = $db_link->prepare($sql_product);
        $query_product->execute(array($product_id));
        $record_product = $query_product->fetch();
        
        $product_url = getProductUrl($product_id, $db_link);
        $stock_info = checkProductAvailability($product_id, $db_link);
        
        $record = array();
        $record['article'] = $record_article['value'];
        $record['manufacturer'] = $record_manufacturer['value'];
        $record['name'] = $record_product['caption'];
        $record['product_id'] = $product_id;
        $record['product_url'] = $product_url;
        
        if ($stock_info['has_stock']) {
            list($catalog_items, $dump_list) = addToList($record, $catalog_items, $dump_list, 'catalog', $stock_info);
        } else {
            list($catalog_items_no_stock, $dump_list) = addToList($record, $catalog_items_no_stock, $dump_list, 'catalog_no_stock', $stock_info);
        }
    }
}

// 2. Товары из каталога (по наименованию)
if($value != ''){
    $SQL_searsch_str = '';
    $binding_values_catalog = array();
    $values_arr = explode(' ',$value);
    if(!empty($values_arr))
    {
        foreach($values_arr as $item_str)
        {
            $item_str = trim($item_str);
            if(mb_strlen($item_str, 'utf-8') < 3)
            {
                continue;
            }
            if($SQL_searsch_str != '')
            {
                $SQL_searsch_str .= " AND ";
            }
            $SQL_searsch_str .= "(`caption` LIKE ?)";
            $binding_values_catalog[] = '%'.$item_str.'%';
        }
    }
    
    $sql = 'SELECT `id`, `caption` FROM `shop_catalogue_products` LIMIT ' . ($cnt_list * 2);

    if($SQL_searsch_str != '')
    {
        $sql = 'SELECT `id`, `caption` FROM `shop_catalogue_products` WHERE '.$SQL_searsch_str.' LIMIT ' . ($cnt_list * 2);
    }

    $query = $db_link->prepare($sql);
    $query->execute($binding_values_catalog);
    while($record_product = $query->fetch()){
        $product_id = $record_product['id'];
        
        $sql_article = "SELECT `value` FROM `shop_properties_values_text` WHERE `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = (SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = ?) AND `value` = 'Артикул' AND `property_type_id` = 3) AND `product_id` = ?";
        $query_article = $db_link->prepare($sql_article);
        $query_article->execute(array($product_id, $product_id));
        $record_article = $query_article->fetch();
        
        if(empty($record_article['value'])){
            continue;
        }
        
        $sql_manufacturer = "SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = ? AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = (SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = ?) AND `value` = 'Производитель' AND `property_type_id` = 5))";
        $query_manufacturer = $db_link->prepare($sql_manufacturer);
        $query_manufacturer->execute(array($product_id, $product_id));
        $record_manufacturer = $query_manufacturer->fetch();
        
        $product_url = getProductUrl($product_id, $db_link);
        $stock_info = checkProductAvailability($product_id, $db_link);
        
        $record = array();
        $record['article'] = $record_article['value'];
        $record['manufacturer'] = $record_manufacturer['value'];
        $record['name'] = $record_product['caption'];
        $record['product_id'] = $product_id;
        $record['product_url'] = $product_url;
        
        if ($stock_info['has_stock']) {
            list($catalog_items, $dump_list) = addToList($record, $catalog_items, $dump_list, 'catalog', $stock_info);
        } else {
            list($catalog_items_no_stock, $dump_list) = addToList($record, $catalog_items_no_stock, $dump_list, 'catalog_no_stock', $stock_info);
        }
    }
}

// 3. Затем товары из истории запросов
$history_items = array();
if($where != ''){
    $sql = 'SELECT * FROM `shop_stat_article_queries` WHERE `id` IN(SELECT MAX(`id`) FROM `shop_stat_article_queries` WHERE '.$where.' GROUP BY `article`, `manufacturer`) ORDER BY `id` DESC LIMIT ' . $cnt_list;
    $query = $db_link->prepare($sql);
    $query->execute($binding_values);
    while($record = $query->fetch()){
        $item = array(
            'article' => $record['article'],
            'manufacturer' => $record['manufacturer'],
            'name' => $record['name']
        );
        list($history_items, $dump_list) = addToList($item, $history_items, $dump_list, 'history');
    }
}

// 4. Затем из прайс-листов по артикулу
$price_items = array();
if($value != ''){
    $sql = 'SELECT * FROM `shop_docpart_prices_data` WHERE `article` LIKE ? LIMIT ' . $cnt_list;
    $query = $db_link->prepare($sql);
    $query->execute(array($value.'%'));
    while($record = $query->fetch()){
        $item = array(
            'article' => $record['article'],
            'manufacturer' => $record['manufacturer'],
            'name' => $record['name']
        );
        list($price_items, $dump_list) = addToList($item, $price_items, $dump_list, 'price');
    }
}

// 5. Из прайс-листов по наименованию
if($value != ''){
    $SQL_searsch_str = '';
    $binding_values_price = array();
    $values_arr = explode(' ',$value);
    if(!empty($values_arr))
    {
        foreach($values_arr as $item_str)
        {
            $item_str = trim($item_str);
            if(mb_strlen($item_str, 'utf-8') < 3)
            {
                continue;
            }
            if($SQL_searsch_str != '')
            {
                $SQL_searsch_str .= " AND ";
            }
            $SQL_searsch_str .= "(`name` LIKE ?)";
            $binding_values_price[] = '%'.$item_str.'%';
        }
    }
    $sql = 'SELECT * FROM `shop_docpart_prices_data` LIMIT ' . $cnt_list;

    if($SQL_searsch_str != '')
    {
        $sql = 'SELECT * FROM `shop_docpart_prices_data` WHERE '.$SQL_searsch_str.' LIMIT ' . $cnt_list;
    }

    $query = $db_link->prepare($sql);
    $query->execute($binding_values_price);
    while($record = $query->fetch()){
        $item = array(
            'article' => $record['article'],
            'manufacturer' => $record['manufacturer'],
            'name' => $record['name']
        );
        list($price_items, $dump_list) = addToList($item, $price_items, $dump_list, 'price');
    }
}

// 6. Стандартные записи
$standard_items = array();
if($value == ''){
    for($i=0; (count($standard_items) < $cnt_list && count($standart_list) > $i); $i++){
        $item = $standart_list[$i];
        list($standard_items, $dump_list) = addToList($item, $standard_items, $dump_list, 'standard');
    }
} else {
    for($i=0; count($standart_list) > $i; $i++){
        if(strpos(mb_strtoupper($standart_list[$i]['article'], "UTF-8"), mb_strtoupper(trim($value), "UTF-8")) === 0){
            $item = $standart_list[$i];
            list($standard_items, $dump_list) = addToList($item, $standard_items, $dump_list, 'standard');
        }
    }
}

// Формируем финальный список
$final_list = array_merge($catalog_items, $catalog_items_no_stock, $history_items, $price_items, $standard_items);
$final_list = array_slice($final_list, 0, $cnt_list);

$result = array();
$result['list'] = $final_list;
$json = json_encode($result, JSON_UNESCAPED_UNICODE);
exit($json);
?>