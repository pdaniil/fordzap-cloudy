<?php
	
	$orders_statuses = array();

	$orders_statuses_query = $db_link->prepare("SELECT * FROM `shop_orders_statuses_ref` ORDER BY `order` ASC;");
	$orders_statuses_query->execute();
	while($status = $orders_statuses_query->fetch() )
	{
		$orders_statuses[$status["id"]] = array("name"=>$status["name"], "color"=>$status["color"]);
	}
	
	$sto_filter_list = [];
	$worker_filter_list = [];
	$post_filter_list = [];
	
	$sto_list_sql = $db_link->prepare("SELECT `id`, `caption` FROM `car_services`;");
	$sto_list_sql->execute();
	
	$sto_filter_list = $sto_list_sql->fetchAll(PDO::FETCH_ASSOC);
	
	$worker_list_sql = $db_link->prepare("SELECT `id`, `fio` FROM `car_service_personal`;");
	$worker_list_sql->execute();
	
	$worker_filter_list = $worker_list_sql->fetchAll(PDO::FETCH_ASSOC);
	
	$post_list_sql = $db_link->prepare("SELECT `id`, `caption` FROM `car_service_posts`;");
	$post_list_sql->execute();
	
	$post_filter_list = $post_list_sql->fetchAll(PDO::FETCH_ASSOC);
	
?>
