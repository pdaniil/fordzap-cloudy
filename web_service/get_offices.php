<?php
/**
 * API Docpart - метод "get_offices" - получение списка магазинов
*/
defined('_API_') or die('No access');



//Магазины
$offices = array();
$query = $db_link->prepare("SELECT * FROM `shop_offices`;");
$query->execute();
while( $office = $query->fetch(PDO::FETCH_ASSOC) )
{
	unset($office['coordinates']);
	unset($office['coordinates']);
	unset($office['users']);
	unset($office['pay_system_id']);
	unset($office['pay_system_parameters']);
	unset($office['commission']);
	unset($office['subscription_fee']);
	$offices[] = $office;
}


/*
echo '<pre>';
var_dump($offices);
echo '</pre>';
exit;
*/


$answer = array();
$answer["result"] = true;
$answer["message"] = "";
$answer["offices"] = $offices;
exit( json_encode($answer) );
?>