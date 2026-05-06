<?php
/**
 * API Docpart
*/


//ini_set('display_errors',1);
ini_set('memory_limit', '500M');

/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST['action']."\n");
fwrite($f, $_POST['login']."\n");
fwrite($f, $_POST['password']."\n");
fwrite($f, $_POST['offices']."\n");
fwrite($f, $_POST['article']."\n");
fwrite($f, $_POST['manufacturers']."\n");
fwrite($f, json_encode($_POST)."\n");
*/

//Тестовые параметры
/*
$_POST['action'] = 'get_offices';
$_POST['login'] = 'info@intask.pro';
$_POST['password'] = '12345678';
*/
/*
$_POST['action'] = 'get_brends';
$_POST['offices'] = json_encode(array(2));
$_POST['login'] = 'info@intask.pro';
$_POST['password'] = '12345678';
$_POST['article'] = 'c110';
*/
/*
$_POST['action'] = 'get_products';
$_POST['offices'] = json_encode(array(2));
$_POST['login'] = 'info@intask.pro';
$_POST['password'] = '12345678';
$_POST['article'] = 'oc247';
$_POST['manufacturers'] = '[{"manufacturer":"KNECHT\/MAHLE","manufacturer_id":0,"manufacturer_show":"KNECHT\/MAHLE","name":"\u0424\u0438\u043b\u044c\u0442\u0440","storage_id":0,"office_id":0,"synonyms_single_query":true,"params":{"type":"prices"},"valid":true},{"manufacturer":"Knecht","manufacturer_id":0,"manufacturer_show":"KNECHT\/MAHLE","name":"\u0421\u043d\u044f\u0442, \u0437\u0430\u043c\u0435\u043d\u0430 OC 479 \u0424\u0438\u043b\u044c\u0442\u0440 \u043c\u0430\u0441\u043b\u044f\u043d\u044b\u0439","storage_id":"19","office_id":"2","synonyms_single_query":true,"params":null,"valid":true},{"manufacturer":"KNECHT","manufacturer_id":0,"manufacturer_show":"KNECHT\/MAHLE","name":"\u041c\u0430\u0441\u043b\u044f\u043d\u044b\u0439 \u0444\u0438\u043b\u044c\u0442\u0440","storage_id":0,"office_id":0,"synonyms_single_query":true,"params":{"type":"server"},"valid":true},{"manufacturer":"MAHLE","manufacturer_id":0,"manufacturer_show":"KNECHT\/MAHLE","name":"\u041c\u0430\u0441\u043b\u044f\u043d\u044b\u0439 \u0444\u0438\u043b\u044c\u0442\u0440","storage_id":0,"office_id":0,"synonyms_single_query":true,"params":{"type":"server"},"valid":true},{"manufacturer":"MAHLE FILTER","manufacturer_id":0,"manufacturer_show":"KNECHT\/MAHLE","name":"\u041c\u0430\u0441\u043b\u044f\u043d\u044b\u0439 \u0444\u0438\u043b\u044c\u0442\u0440","storage_id":0,"office_id":0,"synonyms_single_query":true,"params":{"type":"server"},"valid":true},{"manufacturer":"MAHLE ORIGINAL","manufacturer_id":0,"manufacturer_show":"KNECHT\/MAHLE","name":"\u041c\u0430\u0441\u043b\u044f\u043d\u044b\u0439 \u0444\u0438\u043b\u044c\u0442\u0440","storage_id":0,"office_id":0,"synonyms_single_query":true,"params":{"type":"server"},"valid":true}]';
*/


//Доступные методы API
switch($_POST['action']){
	case 'get_offices'  :
	case 'get_brends'   :
	case 'get_products' :
	break;
	default :
		$answer = array();
		$answer["result"] = false;
		$answer["message"] = "Server action error";
		exit( json_encode($answer) );
	break;
}



header('Content-Type: text/html; charset=utf-8');
define("_API_", "1");


//Конфигурация
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch(PDOException $e) 
{
    $answer = array();
	$answer["result"] = false;
	$answer["message"] = "Server not available";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");



if(trim($_POST["check"]) !== $DP_Config->tech_key){
	//Проверка доступа к api
	$login    = trim($_POST["login"]);
	$password = trim($_POST["password"]);
	if(!empty($DP_Config->secret_succession)){
		$password = $password . $DP_Config->secret_succession;
	}
	$password = md5($password);

	$query = $db_link->prepare("SELECT * FROM `users` WHERE (`email` = ? OR `phone` = ?) AND `password` = ?;");
	$query->execute( array($login, $login, $password) );
	$customer = $query->fetch();

	if(empty($customer)){
		$answer = array();
		$answer["result"] = false;
		$answer["message"] = "Wrong login or password";
		exit( json_encode($answer) );
	}
}else{
	if(!empty($_POST["user_id"])){
		$query = $db_link->prepare("SELECT * FROM `users` WHERE `user_id` = ?;");
		$query->execute( array($_POST["user_id"]) );
		$customer = $query->fetch();
	}else if(!empty($_POST["login"]) && !empty($_POST["password"])){
		$login    = trim($_POST["login"]);
		$password = trim($_POST["password"]);
		if(!empty($DP_Config->secret_succession)){
			$password = $password . $DP_Config->secret_succession;
		}
		$password = md5($password);

		$query = $db_link->prepare("SELECT * FROM `users` WHERE (`email` = ? OR `phone` = ?) AND `password` = ?;");
		$query->execute( array($login, $login, $password) );
		$customer = $query->fetch();

		if(empty($customer)){
			$answer = array();
			$answer["result"] = false;
			$answer["message"] = "Wrong login or password";
			exit( json_encode($answer) );
		}
	}else{
		$customer['user_id'] = 0;
	}
}



//Подключаем обработчик метода API
require_once($_SERVER["DOCUMENT_ROOT"]."/web_service/".$_POST['action'].".php");



?>