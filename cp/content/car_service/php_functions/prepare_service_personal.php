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
	$service_personal_data = [];
	
	$service_personal_data_data_select = $db_link->prepare("SELECT * FROM `car_service_personal` WHERE `id` = ?;");
	$service_personal_data_data_select->execute([$_GET["id"]]);
	$service_personal_data = $service_personal_data_data_select->fetch(PDO::FETCH_ASSOC);
	
?>