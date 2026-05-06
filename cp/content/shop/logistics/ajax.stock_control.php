<?php
header('Content-Type: application/json;');
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $result["status"] = false;
	$result["message"] = "DB connect error";
	$result["code"] = 502;
	exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

// -------------------------------------------------------------------------------

//Защита от CSRF-атак

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

// -------------------------------------------------------------------------------

//Проверяем право менеджера

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

if( ! DP_User::isAdmin())

{

	$answer = array('status'=>false);

	exit(json_encode($answer));

}

$answer = array(
    'status' => false,
    'message' => 'Неизвестный запрос'
);

function priceRound($price)
{
    global $DP_Config;
    $type = (int)$DP_Config->price_rounding - 1;
    $types = array(1, 5, 10, 50);
    if(isset($types[$type])){
        $type = $types[$type];
        $price = $type * ceil($price / $type);
    }
    return $price;
}

function where_search_text($search_text, &$binding_values)
{
    $search_text_arr = explode(' ', $search_text);
    $where_tmp = '';
    $where_tmp_2 = '';
    if($where_tmp != ''){$where_tmp .= ' OR ';}
    foreach($search_text_arr as $search_text_item){
        if(mb_strlen($search_text_item, 'UTF-8') >= 3){
            if($where_tmp_2 != ''){$where_tmp_2 .= ' AND ';}
            $where_tmp_2 .= "(`shop_catalogue_products`.`id` IN(SELECT DISTINCT `product_id` FROM `shop_properties_values_text` WHERE `property_id` IN(SELECT `id` FROM `shop_categories_properties_map` WHERE `property_type_id` = 3 AND `value` = 'Артикул') AND `value` LIKE ?))";
            array_push($binding_values, '%'.$search_text_item.'%');
        }
    }
    $where_tmp .= $where_tmp_2;
    if($where_tmp != ''){$where_tmp = '('. $where_tmp .')';}

    $where_tmp_2 = '';
    if($where_tmp != ''){$where_tmp .= ' OR ';}
    foreach($search_text_arr as $search_text_item){
        if(mb_strlen($search_text_item, 'UTF-8') >= 3){
            if($where_tmp_2 != ''){$where_tmp_2 .= ' AND ';}
            $where_tmp_2 .= "(`shop_catalogue_products`.`id` IN(SELECT DISTINCT `product_id` FROM `shop_properties_values_text` WHERE `property_id` IN(SELECT `id` FROM `shop_categories_properties_map` WHERE `property_type_id` = 3 AND `value` = 'Штрих-код') AND `value` LIKE ?))";
            array_push($binding_values, '%'.$search_text_item.'%');
        }
    }
    $where_tmp .= $where_tmp_2;
    if($where_tmp != ''){$where_tmp = '('. $where_tmp .')';}


    $where_tmp_2 = '';
    if($where_tmp != ''){$where_tmp .= ' OR ';}
    foreach($search_text_arr as $search_text_item){
        if(mb_strlen($search_text_item, 'UTF-8') >= 3){
            if($where_tmp_2 != ''){$where_tmp_2 .= ' AND ';}
            $where_tmp_2 .= "(`shop_catalogue_products`.`id` IN(SELECT DISTINCT `product_id` FROM `shop_properties_values_list` WHERE `property_id` IN(SELECT `id` FROM `shop_categories_properties_map` WHERE `property_type_id` = 5 AND `value` = 'Производитель') AND `value` IN(SELECT `id` FROM `shop_line_lists_items` WHERE `value` LIKE ?)))";
            array_push($binding_values, '%'.$search_text_item.'%');
        }
    }
    $where_tmp .= $where_tmp_2;
    if($where_tmp != ''){$where_tmp = '('. $where_tmp .')';}


    $where_tmp_2 = '';
    if($where_tmp != ''){$where_tmp .= ' OR ';}
    foreach($search_text_arr as $search_text_item){
        if(mb_strlen($search_text_item, 'UTF-8') >= 3){
            if($where_tmp_2 != ''){$where_tmp_2 .= ' AND ';}
            $where_tmp_2 .= "(`shop_catalogue_products`.`caption` LIKE ?)";
            array_push($binding_values, '%'.$search_text_item.'%');
        }
    }
    $where_tmp .= $where_tmp_2;
    if($where_tmp != ''){$where_tmp = '('. $where_tmp .')';}
    return $where_tmp;
}

function get_sub_categories($categoryId)
{
    global $db_link;
    $query = $db_link->prepare("SELECT * FROM shop_catalogue_categories WHERE parent=?");
    $query->execute(array($categoryId));
    $list = array();
    while($row = $query->fetch(PDO::FETCH_ASSOC)){
        $list[$row['id']] = true;
        foreach(get_sub_categories($row['id']) as $subCategoryId){
            $subCategoryId = (int)$subCategoryId;
            $list[$subCategoryId] = true;
        }
    }
    return array_keys($list);
}

function get_data(&$categories, &$allCategories, &$productsIds, &$productsManufacturers, &$manufacturers)
{
    global $db_link;
    $categories = explode(",", isset($_POST['categories']) ? $_POST['categories'] : '');
    $searchText = isset($_POST['search_text']) ? $_POST['search_text'] : '';
    $allCategories = array();
    foreach($categories as $categoryId){
        if(is_numeric($categoryId)){
            $allCategories[] = (int)$categoryId;
            $allCategories = array_merge($allCategories, get_sub_categories($categoryId));
        }
    }
    $bindingValues = array();
    $whereSearchText = $searchText ? where_search_text($searchText, $bindingValues) : '';
    $where =  "WHERE `shop_catalogue_products`.`category_id` IN (" . implode(",", $allCategories) . ")" . ($whereSearchText ? " AND ($whereSearchText)" : "");
    $productsIdsQuery = $db_link->prepare("SELECT * FROM (SELECT DISTINCT `all`.`id` FROM(SELECT `shop_catalogue_products`.`id` FROM `shop_catalogue_products` LEFT JOIN `shop_storages_data` ON `shop_catalogue_products`.`id` = `shop_storages_data`.`product_id` $where) AS `all`) AS `all_count`");
    $productsIds = array();
    if($productsIdsQuery->execute($bindingValues)){
        while($product = $productsIdsQuery->fetch(PDO::FETCH_ASSOC)){
            $productsIds[] = $product['id'];
        }
    }
    $productsManufacturersQuery = $db_link->prepare("SELECT `shop_catalogue_products`.`id`, (SELECT `value` FROM `shop_line_lists_items` WHERE TRIM(`value`) <> '' AND `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE TRIM(`value`) <> '' AND `product_id` = `shop_catalogue_products`.`id`  AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_catalogue_products`.`category_id` AND `value` = 'Производитель' AND `property_type_id` = 5 LIMIT 1) LIMIT 1) LIMIT 1) AS `manufacturer` FROM `shop_catalogue_products` WHERE `shop_catalogue_products`.`id` IN (".implode(",", $productsIds).")");
    $productsManufacturers = array();
    if($productsManufacturersQuery->execute()){
        while($product = $productsManufacturersQuery->fetch(PDO::FETCH_ASSOC)){
            $manufacturer = trim(str_replace(array("\t"), "", $product['manufacturer']));
            if(!mb_strlen($manufacturer)){
                $manufacturer = 'Без производителя';
            }
            $manufacturer = mb_strtoupper(htmlspecialchars_decode($manufacturer));
            $productsManufacturers[$product['id']] = $manufacturer;
            if(!isset($manufacturers[$manufacturer])){
                $manufacturers[$manufacturer] = 0;
            }
            $manufacturers[$manufacturer]++;
        }
    }
}
$categories = array();
$allCategories = array();
$productsIds = array();
$productsManufacturers = array();
$manufacturers = array();

if(isset($_POST['action'])){
    switch($_POST['action']){
        case 'get_history':
            $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            $limit = $DP_Config->products_count_for_page ? (int)$DP_Config->products_count_for_page : 10;
            $historyQuery = $db_link->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM shop_prices_percent_change_history" . ($offset ? " WHERE id < " . $offset : "" ) . " ORDER BY id DESC LIMIT ". $limit);
            $list = array();
            $count = 0;
            if($historyQuery->execute()){
                $historyCountQuery = $db_link->prepare("SELECT FOUND_ROWS() as count");
                $count = $historyCountQuery->execute() ? (int) $historyCountQuery->fetch(PDO::FETCH_ASSOC)['count'] : 0;
                while($history = $historyQuery->fetch(PDO::FETCH_ASSOC)){
                    $history['percent'] = $history['percent'] * 100;
                    $history['created_ex'] = date('d.m.Y H:i:s', $history['created']);
                    $list[] = $history;
                }
            }
            $answer = array(
                'status' => true,
                'message' => array(
                    'has_more' => $count - $limit > 0,
                    'count' => $count,
                    'items' => $list
                )
            );
        break;
        case 'search_products':
            get_data($categories, $allCategories, $productsIds, $productsManufacturers, $manufacturers);
            $message = '';
            $message = "Выбрано категорий: <b>" . count($categories) . "</b><br>Всего категорий (включая вложенные): <b>" . count($allCategories) . "</b>";
            if($searchText){
                $message .= "<br>Подстрока поиска: <b>" . $searchText . "</b>";
            }
            $message .= "<br>Найдено товаров: <b>" . count($productsIds) . "</b><div class='form-group'><label class='form-label'>Выберите производителей</label><select id='manufacturers-list' multiple='multiple'>";
            foreach($manufacturers as $manufacturer => $count){
                $message .= "<option value='" . $manufacturer . "' data-count='" . $count . "'>" . $manufacturer . " (товаров: ". $count. ")</option>";
            }
            $message .= "</select></div><script>$('#manufacturers-list').multipleSelect({placeholder: 'Нажмите для выбора...', width:'100%', selectAll: true}).multipleSelect('checkAll');</script>";
            $answer = array(
                'status' => true,
                'message' => $message,
                'products_manufacturers' => $productsManufacturers
            );
        break;
        case 'update_prices':
            get_data($categories, $allCategories, $productsIds, $productsManufacturers, $manufacturers);
            $answer = array(
                'status' => false,
                'message' => 'Не удалось обновить цены'
            );
            $percent = (isset($_POST['percent']) ? floatval($_POST['percent']) : 0) / 100;
            $crossedOut = isset($_POST['crossed_out']) ? (bool)$_POST['crossed_out'] : true;
            $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
            $selectedManufacturers = isset($_POST['manufacturers']) ? explode(",", $_POST['manufacturers']) : array();
            if(count($productsIds) && mb_strlen($comment)){
                $selectedManufacturers = array_fill_keys($selectedManufacturers, '');
                $trueProductIds = array();
                foreach($productsIds as $productId){
                    if($productsManufacturers[$productId] && isset($selectedManufacturers[$productsManufacturers[$productId]])){
                        $trueProductIds[] = $productId;
                    }
                }
                if(count($trueProductIds)){
                    $productsQuery = $db_link->prepare("SELECT id, price, price_crossed_out FROM shop_storages_data WHERE product_id IN (".implode(",", $trueProductIds).")");
                    if($productsQuery->execute()){
                        while($product = $productsQuery->fetch(PDO::FETCH_ASSOC)){
                            $price = $product['price'];
                            $priceCrossedOut = $product['price_crossed_out'];
                            $price = priceRound($price + $price * $percent);
                            if($crossedOut){
                                $priceCrossedOut = priceRound($priceCrossedOut + $priceCrossedOut * $percent);
                            }
                            $priceQuery = $db_link->prepare("UPDATE shop_storages_data SET price=?,price_crossed_out=? WHERE id=?");
                            $priceQuery->execute(array($price, $priceCrossedOut, $product['id']));
                        }
                        $historyQuery = $db_link->prepare("INSERT INTO shop_prices_percent_change_history (user,user_id,percent,created,comment) VALUES (?,?,?,?,?)");
                        $adminName = array();
                        $adminProfile = DP_User::getAdminProfile();
                        $adminId = 0;
                        if(is_array($adminProfile)){
                            $adminId = $adminProfile['user_id'];
                            if(isset($adminProfile['surname'])){
                                $adminName[] = $adminProfile['surname'];
                            }
                            if(isset($adminProfile['name'])){
                                $adminName[] = $adminProfile['name'];
                            }
                            if(isset($adminProfile['patronymic'])){
                                $adminName[] = $adminProfile['patronymic'];
                            }
                        }
                        $adminName = count($adminName) ? implode(" ", $adminName) : 'Администратор';
                        $historyQuery->execute(array(
                            $adminName,
                            $adminId,
                            $percent,
                            time(),
                            $comment
                        ));
                        $answer = array(
                            'status' => true,
                            'message' => 'Цены успешно обновлены'
                        );
                    }
                }
            }
        break;
    }
}
echo json_encode($answer);