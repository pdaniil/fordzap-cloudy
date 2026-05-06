<?php

header('Content-Type: application/json;charset=utf-8;');

//Скрипт проверки уникальности поля Алиас в рамках одного уровня одной ветви



//Конфигурация CMS

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

$DP_Config = new DP_Config;




if( $_POST["code"] != $DP_Config->secret_succession )

{

	$answer = array();

	$answer["status"] = false;

	$answer["message"] = "Forbidden";

	exit( json_encode($answer) );

}

//Подключение к БД

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
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db_link->query("SET NAMES utf8;");
$answer = array(
    'status' => false,
    'message' => 'Ошибки публикации'
);
require_once "post_social.php";
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : 0;
if($product_id > 0){
	$result = post_social_product($product_id);
    if($result === true){
        $answer["status"] = true;
	    $answer["message"] = "Успешно опубликовано";
    }else{
		$answer["status"] = true;
	    $answer["message"] = $result;
	}
}
exit( json_encode($answer) );