<?php 
	defined('_ASTEXE_') or die('No access');
	//Получить все сто и сохранить в массив
	$sto_list_arr = [];
	$sto_list_select = $db_link->prepare("SELECT * FROM `car_services`;");
	$sto_list_select->execute();
	
	$sto_list_arr = $sto_list_select->fetchAll(PDO::FETCH_ASSOC);
?>