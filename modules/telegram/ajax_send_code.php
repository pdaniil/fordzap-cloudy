<?php

//Серверный скрипт для асинхронной записи настроек пользователя
header('Content-Type: application/json;charset=utf-8;');
//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

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

$answer = array();
$answer['status'] = true;

$request_object = json_decode($_POST["request_object"], true);
$user_id = $request_object['user_id'];

$telegram_code = md5(md5($user_id).md5($DP_Config->secret_succession));//Код активации
// $telegram_code = "123456";

if($user_id != 0)
{
    try
    {
    	$check_options_exists_query = $db_link->prepare("UPDATE `users` SET `telegram_code` = ? WHERE `user_id` = ?;");
        $check_options_exists_query->execute( array($telegram_code, $user_id) );
        $answer['telegram_code'] = $telegram_code;
    }
    catch (PDOException $e) 
    {
        $answer['status'] = false;
    }
}
else{
   $answer['telegram_code'] = '';
}




exit(json_encode($answer));









?>