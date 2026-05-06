<?php
/*
	Серверный скрипт для загрузки файла на сервер в директорию tmp
*/
header('Content-Type: application/json;charset=utf-8;');
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
	$answer = array('status'=>false);
	exit(json_encode($answer));
}


//Проверям на расширение .csv
$file_format = substr($_FILES['csv_file']['name'], strlen($_FILES['csv_file']['name'])-4, 4);
$file_format = strtolower($file_format);//К нижнему регистру
//Теперь полная проверка расширения
if($file_format != ".csv")
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Wrong file extension. Use csv files";
	exit( json_encode($answer) );
}
//Загружаем файл
$uploaddir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/";
$uploadfile = $uploaddir . basename($_FILES['csv_file']['name']);
if (! move_uploaded_file($_FILES['csv_file']['tmp_name'], $uploadfile)) 
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Could not upload the file";
	exit( json_encode($answer) );
}
else
{
	$answer = array();
	$answer["status"] = true;
	$answer["file_full_path"] = $uploadfile;
	exit( json_encode($answer) );
}
?>