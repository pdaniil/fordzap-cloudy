<?php 
	//Проверяем, был ли передан ID
	function checkId() {
		global $DP_Config;
		if (!isset($_GET["id"]) )
		{
			?>
			<script>
				location = '<?= $DP_Config->domain_path . $DP_Config->backend_dir;?>';
			</script>
			<?
			exit();
		}
	}
	
	//Данные, которые выводим на страницу
	$service_data = [];
	
	$select_service_data = $db_link->prepare("SELECT * FROM `car_services` WHERE `id` = ?;");
	$select_service_data->execute([$_GET["id"]]);
	$service_data = $select_service_data->fetch(PDO::FETCH_ASSOC);
	
	$select_service_data_tree = $db_link->prepare("SELECT * FROM `shop_tree_lists_items` WHERE `tree_list_id` = ?;");
	$select_service_data_tree->execute([$service_data["id_tree_list_services"]]);
	$service_tree = $select_service_data_tree->fetchAll(PDO::FETCH_ASSOC);
	
	foreach ($service_tree as $key => $list) {
		$service_tree[$key]["hourly_rate"] = 0;
		
		$select_price = $db_link->prepare("SELECT * FROM `car_service_prices` WHERE `sto_id` = ? AND `id_tree_services_list` = ?;");
		$select_price->execute([$_GET["id"], $list["id"]]);
		
		$res = $select_price->fetch(PDO::FETCH_ASSOC);
		
		if ($res["hourly_rate"] > 0) {
			$service_tree[$key]["hourly_rate"] = $res["hourly_rate"];
		}
		
		$service_tree[$key]["caption"] = $res["caption"] ? $res["caption"] : "";
		$service_tree[$key]["stickers"] = $res["stickers"] ? json_decode($res["stickers"], true) : "";
		$service_tree[$key]["recommendation_block"] = $res["recommendation_block"];
		$service_tree[$key]["worker_price"] = $res["worker_price"];
		$service_tree[$key]["fix_price"] = $res["fix_price"];
		$service_tree[$key]["active"] = $res["active"];
	}
	
	
	
	$service_tree_json = json_encode($service_tree);
	
	$stmt = $db_link->prepare("SELECT * FROM car_service_stickers ORDER BY id DESC");
	$stmt->execute();
    $stickers_arr_json = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<script>
	var hourly_rate_price = parseFloat('<?=$service_data["hourly_rate"];?>');
</script>
