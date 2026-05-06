<?php

set_time_limit(600);

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

//Соединение с БД
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

$answer = array('status'=>false, 'message' => 'Не удалось выполнить скрипт.');

/********************************************************************************************************************** */
/********************************************************************************************************************** */

//Сначала выставляем всем товарам начальные значения
$ITEMS_UPDATE_SQL = "
UPDATE
`managers_cash_session`
SET
	`is_active` = 0
WHERE
	`is_active` = '1'
;";

$items_update_query = $db_link->prepare($ITEMS_UPDATE_SQL);
$items_update_query->execute();


/********************************************************************************************************************** */
/********************************************************************************************************************** */



?>