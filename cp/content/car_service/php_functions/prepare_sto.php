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
	$sto_data = [];
	
	$sto_data_select = $db_link->prepare("SELECT * FROM `car_services` WHERE `id` = ?;");
	$sto_data_select->execute([$_GET["id"]]);
	$sto_data = $sto_data_select->fetch(PDO::FETCH_ASSOC);
	
	//Получаем данные всех древовидных списков
	$tree_lists_arr = [];
	$tree_lists_arr_select = $db_link->prepare("SELECT * FROM `shop_tree_lists`;");
	$tree_lists_arr_select->execute();
	$tree_lists_arr = $tree_lists_arr_select->fetchAll(PDO::FETCH_ASSOC);

?>