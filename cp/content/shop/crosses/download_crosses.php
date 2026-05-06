<?php
/*
	Скрипт делает выгрузку таблицы кроссов в .csv файл
*/

//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");



// -------------------------------------------------------------------------------
//Проверка привелегий (пользователь должен иметь доступ к следующим страницам)
$pages_to_check = array();
$pages_to_check[] = array('id'=>380, 'url'=>'shop/crosses');//Таблица кроссов
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/control/check_admin_access/check_admin_access.php");
// -------------------------------------------------------------------------------


// -------------------------------------------------------------------------------
//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
// -------------------------------------------------------------------------------

//Проверяем право менеджера
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
if( ! DP_User::isAdmin())
{
	$ansver = array('status'=>false);
	exit(json_encode($ansver));
}

// Выводим HTTP-заголовки
header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
header ( "Cache-Control: no-cache, must-revalidate" );
header ( "Pragma: no-cache" );
header ( "Content-type: application/vnd.ms-excel" );
header ( "Content-Disposition: attachment; filename=crosses.csv" );

//Выводим файл
echo iconv("UTF-8", "WINDOWS-1251","Артикул;Производитель;Аналог;Производитель аналога\r\n");

$tmp_id = 0;
$sql = "SELECT * FROM `shop_docpart_articles_analogs_list` WHERE `id` > ? ORDER BY `id` ASC LIMIT 50000;";
$query = $db_link->prepare($sql);

do
{
	$query->execute(array($tmp_id));
	$tmp_id = 0;
	while($rov = $query->fetch() ){
		$tmp_id = $rov['id'];
		
		$article = $rov['article'];
		$manufacturer_article = $rov['manufacturer_article'];
		$analog = $rov['analog'];
		$manufacturer_analog = $rov['manufacturer_analog'];
		
		$article = iconv("UTF-8", "WINDOWS-1251", $article);
		$manufacturer_article = iconv("UTF-8", "WINDOWS-1251", $manufacturer_article);
		$analog = iconv("UTF-8", "WINDOWS-1251", $analog);
		$manufacturer_analog = iconv("UTF-8", "WINDOWS-1251", $manufacturer_analog);
		
		echo $article.';'.$manufacturer_article.';'.$analog.';'.$manufacturer_analog."\r\n";
	}
}while( $tmp_id > 0 );
?>