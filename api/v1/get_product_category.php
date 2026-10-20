<?php 
	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
	$DP_Config = new DP_Config;//Конфигурация CMS

	//Подключение к БД
	try
	{
		$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	}
	catch (PDOException $e) 
	{
		exit("No DB connect");
	}
	$db_link->query("SET NAMES utf8;");
	
	$product_id = $_GET["product_id"];
	
	$res = [];
	
	$sql = $db_link->prepare("SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = ?; ");
	$sql->execute([$product_id]);
	
	$res = $sql->fetch(PDO::FETCH_ASSOC);
	exit(json_encode($res));  
	
?>