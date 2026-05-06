<?php 
	defined('_ASTEXE_') or die('No access');
	//Получить все сто и сохранить в массив
	$service_personal_list_arr = [];
	$service_personal_list_select = $db_link->prepare("SELECT * FROM `car_service_personal`;;");
	$service_personal_list_select->execute();
	
	$service_personal_list_arr = $service_personal_list_select->fetchAll(PDO::FETCH_ASSOC);
?>