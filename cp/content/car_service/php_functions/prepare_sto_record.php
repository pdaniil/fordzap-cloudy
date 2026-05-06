<?php
//Проверяем, был ли передан ID
function checkId() {
    global $DP_Config;
    if (!isset($_GET["id"]) )
    {
        
    }
}

function replaceQuotes($text) {
    return str_replace('"', '»', preg_replace('/((^|\s)"(\w))/um', '\2«\3', $text));
}

function getUserInfoByOrderId($order_id) {
	global $db_link;
	
	$select = $db_link->prepare("SELECT `user_id` FROM `shop_orders` WHERE `id` = ?;");
	$select->execute([$order_id]);
	
	$res = $select->fetch(PDO::FETCH_ASSOC);
	
	return $res["user_id"];
}

if ($_GET["id"] > 0) {
	$update_view = $db_link->prepare("UPDATE `car_service_records` SET `is_view` = 1 WHERE `id` = ?");
	$update_view->execute([$_GET["id"]]);


	//Данные, которые выводим на страницу
	$sto_record_data = [];

	$sto_record_data_select = $db_link->prepare("SELECT *, DATE_FORMAT(`time`, '%Y-%m-%d %H:%i') AS `time_formatted` FROM `car_service_records` WHERE `id` = ?;");
	$sto_record_data_select->execute([$_GET["id"]]);
	$sto_record_data = $sto_record_data_select->fetch(PDO::FETCH_ASSOC);
	$sto_record_data['user'] = replaceQuotes($sto_record_data['user']);

	if (is_null($sto_record_data["services_arr"]))
		$sto_record_data["services_arr"] = json_encode([-1]);


	$time_to = $sto_record_data['time_to'];
	$time_from = $sto_record_data['time_from'];
	$user_json = json_decode($sto_record_data['user_json'], true);
	$car_json = json_decode($sto_record_data['car_json'], true);
	
	if (!empty($car_json["car_id"])) {
		
		$select_users_cars = $db_link->prepare("SELECT * FROM `shop_docpart_garage` WHERE `user_id` = ?;");
		$select_users_cars->execute([$user_json["user_id"]]);
		
		$cars_arr = $select_users_cars->fetchAll(PDO::FETCH_ASSOC);
	}
	
	$select_services = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?;");
	$select_services->execute([$_GET["id"]]);
	
	$current_services = $select_services->fetchAll(PDO::FETCH_ASSOC);
}

//Получаем данные сто
$sto_data_select = $db_link->prepare("SELECT * FROM `car_services`;");
$sto_data_select->execute();
$sto_data = $sto_data_select->fetchAll(PDO::FETCH_ASSOC);

//Получаем данные древовидного списка услуг (только конечные элементы)
$tree_lists_services_arr = [];
$tree_lists_services_arr_select = $db_link->prepare("SELECT `id`,`value` FROM `shop_tree_lists_items` WHERE `tree_list_id` = ? AND `count` = 0;");
$tree_lists_services_arr_select->execute([$sto_data["id_tree_list_services"]]);
$tree_lists_services_arr = $tree_lists_services_arr_select->fetchAll(PDO::FETCH_ASSOC);

//Получаем данные сотрудников (которые работают в этом СТО на момент записи, занятость на других записях не проверяем)
$arr_workers = [];
$arr_workers_select = $db_link->prepare("SELECT * FROM `car_service_personal`");
$arr_workers_select->execute();

$arr_workers = $arr_workers_select->fetchAll(PDO::FETCH_ASSOC);

//Посты
$sto_posts_data_select = $db_link->prepare("SELECT * FROM `car_service_posts`;");
$sto_posts_data_select->execute();
$sto_data_posts = $sto_posts_data_select->fetchAll(PDO::FETCH_ASSOC);

//Пользователи
$users_data_select = $db_link->prepare("SELECT `user_id`, `email`, `phone` FROM `users`;");
$users_data_select->execute();
$users_data = $users_data_select->fetchAll(PDO::FETCH_ASSOC);


$order_items = [];

if (!empty($sto_record_data["orders_ids"])) {
    $ids = explode(',', $sto_record_data["orders_ids"]); // превращаем строку в массив
    $placeholders = rtrim(str_repeat('?,', count($ids)), ','); // ?,?,?
	
	$SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
	$SELECT_type2_name = "CONCAT(`t2_manufacturer`, ' ', `t2_article`, '. ', `t2_name`)";//Для типа продукта = 2
	$SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";
	
    $sql = "SELECT *, $SELECT_product_name AS `caption` FROM `shop_orders_items` WHERE `order_id` IN ($placeholders)";
    $select_order_items = $db_link->prepare($sql);
    $select_order_items->execute($ids);

    $order_items = $select_order_items->fetchAll(PDO::FETCH_ASSOC);
}


$selected_user_id = getUserInfoByOrderId($sto_record_data["orders_ids"]);

$arr_users = [];

$selest_users = $db_link->prepare("SELECT *, (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'name' AND `user_id` = `users`.`user_id`) as `name`, (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'surname' AND `user_id` = `users`.`user_id`) as `surname` FROM `users`;");
$selest_users->execute();
$arr_users = $selest_users->fetchAll(PDO::FETCH_ASSOC);

foreach ($arr_users as $key=>$user) {
	$arr_users[$key]["data_str"] = "ID: " . $user["user_id"] . ", " . $user["email"] . ", " . $user["surname"] . " " . $user["name"] . ", " . $user["phone"];
}

if ($selected_user_id > 0) {
	$user_json["user_id"] = $selected_user_id; 
}

$orders_statuses = array();

$orders_statuses_query = $db_link->prepare("SELECT * FROM `shop_orders_statuses_ref` ORDER BY `order` ASC;");
$orders_statuses_query->execute();
while($status = $orders_statuses_query->fetch() )
{
	$orders_statuses[$status["id"]] = array("name"=>$status["name"], "color"=>$status["color"]);
}



// Получаем список статусов
$item_statuses = array();
$item_statuses_query = $db_link->prepare("SELECT * FROM `shop_orders_items_statuses_ref` ORDER BY `order` ASC;");
$item_statuses_query->execute();
while($status = $item_statuses_query->fetch() ) {
    $item_statuses[$status["id"]] = array(
        "name" => $status["name"], 
        "color" => $status["color"]
    );
}

$select_statuses = $db_link->prepare("SELECT * FROM `shop_orders_items_statuses_ref`;");
$select_statuses->execute();

$res_select_statuses = $select_statuses->fetchAll(PDO::FETCH_ASSOC);


?>