<?php
defined('_ASTEXE_') or die('No access');
//Получить все сто и сохранить в массив
$post_list_arr = [];
$post_list_select = $db_link->prepare("SELECT * FROM `car_service_posts`;");
$post_list_select->execute();

$post_list_arr = $post_list_select->fetchAll(PDO::FETCH_ASSOC);
?>