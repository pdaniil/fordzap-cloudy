<?php

$record_sto_id = 0;

$select_sto_id_by_order_id = $db_link->prepare("SELECT `id` FROM `car_service_records` WHERE `orders_ids` = ?;");
$select_sto_id_by_order_id->execute([$_GET["order_id"]]);
$res = $select_sto_id_by_order_id->fetch(PDO::FETCH_ASSOC);

if ($res["id"] > 0) {
	$record_sto_id = $res["id"];
}
	

$_GET["id"] = $record_sto_id;

$user_id = 0;
$car_id = 0;
$mileage = '';

function replaceQuotes($text) {
    return str_replace('"', '»', preg_replace('/((^|\s)"(\w))/um', '\2«\3', $text));
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
	
	$user_id = $sto_record_data['user_id'];
	$car_id = $sto_record_data["car_id"]; 
	$mileage = $sto_record_data["mileage"]; 
	
	
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


$select_statuses = $db_link->prepare("SELECT * FROM `shop_orders_items_statuses_ref` WHERE `for_service` = 1;");
$select_statuses->execute();

$res_select_statuses = $select_statuses->fetchAll(PDO::FETCH_ASSOC);


?>

<?php
// Собираем все данные в PHP
$operatingModeData = [];
foreach ($sto_data as $service) {
    if (isset($service['operating_mode'])) {
        // Декодируем и кодируем обратно для очистки
        $modeData = json_decode($service['operating_mode'], true);
        if ($modeData !== null) {
            $operatingModeData[$service['id']] = $modeData;
        }
    }
}
?>

<script>
// Инициализируем как объект (не массив)
if (typeof operating_mode === 'undefined') {
    operating_mode = {};
}

// Добавляем данные безопасным способом
<?php foreach ($operatingModeData as $id => $mode): ?>
operating_mode['<?= addslashes($id) ?>'] = <?= json_encode($mode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
<?php endforeach; ?>
</script>


 
