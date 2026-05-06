<?php 
	defined('_ASTEXE_') or die('No access');

	//Получить все сто и сохранить в массив
	
	$navbar_arr = [];
	$navbar_arr_select = $db_link->prepare("SELECT * FROM `control_items` WHERE `items_group` = (SELECT `id` FROM `control_groups` WHERE `caption` = 'Автосервис');");
	$navbar_arr_select->execute();
	
	$navbar_arr = $navbar_arr_select->fetchAll(PDO::FETCH_ASSOC);
?>