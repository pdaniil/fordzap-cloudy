<?php
header('Content-Type: application/json;charset=utf-8;');

//Рекурвиная функция. Обрабатывает все значения древовидного массива через htmlentities
function prepare_json_htmlentities($how_get)
{
	foreach($how_get AS $key=>$value)
	{
		if( is_array($value) )
		{
			$how_get[$key] = prepare_json_htmlentities($value);
		}
		else
		{
			$how_get[$key] = htmlentities($value);
		}
	}
	
	return $how_get;
}

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
	$result = array();
	$result["status"] = false;
	$result["message"] = "No DB connect";
	exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

// -------------------------------------------------------------------------------
//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
// -------------------------------------------------------------------------------

//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
// $user_id = DP_User::getUserId();

$user_id = $_POST["user_id"];

//Проверка, включена ли функции заказа без регистрации, если пользователь не авторизован
if( $user_id == 0 )
{
	if( isset( $DP_Config->order_without_auth ) )
	{
		if( $DP_Config->order_without_auth != 1 )
		{
			$result = array();
			$result["status"] = false;
			$result["message"] = "Заказ может оформить только зарегистрированный покупатель";
			exit(json_encode($result));
		}
	}
}

//Для отправки уведомлений
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/notifications/notify_helper.php" );

if($user_id > 0)
{
	//Поля для авторизованного пользователя
	$session_id = 0;
}
else
{
	//Поля для НЕавторизованного пользователя
	$session_record = DP_User::getUserSession();
	
	if($session_record == false)
	{
		$result = array();
		$result["status"] = false;
		$result["code"] = "incorrect_session";
		$result["message"] = "Ошибка сессии";
		exit(json_encode($result));
	}
	
	$session_id = $session_record["id"];
}

//СОЗДАНИЕ ЗАКАЗА ЧЕРЕЗ ТРАНЗАКЦИЮ
try
{
	//Меняем статус autocommit на FALSE. Т.е. старт транзакции
	if( ! $db_link->beginTransaction()  )
	{
		throw new Exception("Не удалось стартовать транзакцию");
	}
	
	$time = time();//Время создания заказа

	$office_executer = 0;//Офис - исполнитель

	//1.
	//Получаем статус заказа, который присваивается для вновь-оформленного
	$for_created_status_query = $db_link->prepare('SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_created`=1;');
	$for_created_status_query->execute();
	$for_created_status_record = $for_created_status_query->fetch();
	$for_created_status = $for_created_status_record["id"];
	
	if( (int)$for_created_status <= 0 )
	{
		throw new Exception("Не определен статус для нового заказа.");
	}
	
	//Выясняем способ получения:
	if( !isset($_COOKIE["how_get"]) )
	{
		throw new Exception("Не определен способ получения заказа.");
	}

	$how_get = json_decode($_COOKIE["how_get"], true);

	$how_get["mode"] = (int)$how_get["mode"];
	//Проверяем, не подменен ли $how_get["mode"]
	$check_how_get_mode_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_obtaining_modes` WHERE `id` = ?;");
	$check_how_get_mode_query->execute( array($how_get["mode"]) );
	if( $check_how_get_mode_query->fetchColumn() != 1 )
	{
		throw new Exception("Ошибка определения способа получения.");
	}

	$office_executer = (int)$how_get["office_id"];//Назначаем офис-исполнитель
		
	if( $office_executer <= 0 )
	{
		throw new Exception("Ошибка определения точки выдачи.");
	}
	
	//$_COOKIE["how_get"] может содержать информацию в JSON-виде. Поэтому, перед записью в БД - нужно пронать все его значения через htmlentities. Вложенность может быть любая, поэтому, делаем рекурсивно
	$how_get = prepare_json_htmlentities($how_get);

	if( $db_link->prepare('INSERT INTO `shop_orders` (`user_id`, `session_id`, `time`, `successfully_created`, `status`, `paid`, `how_get`, `how_get_json`, `phone_not_auth`, `email_not_auth`) VALUES (?,?,?,?,?,?,?,?,?,?);')->execute( array($user_id, $session_id, $time, 0, $for_created_status, 0, $how_get["mode"], json_encode($how_get), htmlentities($phone_not_auth), htmlentities($email_not_auth) ) ) != true)
	{
		throw new Exception("SQL-ошибка создания записи заказа.");
	}
	
	//1.1. Получаем ID созданной записи
	$order_id = $db_link->lastInsertId();
	if( ! ($order_id > 0) )
	{
		throw new Exception("ID заказа не определен");
	}
	
	if( $db_link->prepare('UPDATE `shop_orders` SET `successfully_created`=1, `office_id`=? WHERE `id` = ?;')->execute( array($office_executer, $order_id) ) != true)
	{
		throw new Exception("SQL-ошибка указания точки обслуживания.");
	}
	
	//Записываем комментарий покупателя:
	if( !empty($_POST["order_message"]) )
	{
		$order_message = trim($_POST["order_message"]);
		$order_message = htmlentities($order_message);
		$order_message = str_replace("\r","",$order_message);
		$order_message = str_replace("\t","",$order_message);
		$order_message = str_replace("\n","<br/>",$order_message);
		
		$db_link->prepare('INSERT INTO `shop_orders_messages` (`order_id`, `is_customer`, `text`, `time`) VALUES (?,?,?,?);')->execute( array($order_id, 1, $order_message, time()) );
	}
	
	//Пишем лог заказа
	if( $db_link->prepare('INSERT INTO `shop_orders_logs` (`order_id`,`time`,`user_id`,`is_manager`,`text`) VALUES (?,?,?,?,?);')->execute( array($order_id, time(), $user_id, 0, 'Заказ создан') ) != true )
	{
		throw new Exception("SQL-ошибка журнала заказа");
	}
	
}
catch (Exception $e)
{
	$db_link->rollBack();//Откатываем все изменения и закрываем транзакцию
	
	$result = array();
	$result["status"] = false;
	$result["message"] = "Ошибка оформления заказа. ".$e->getMessage();
	exit(json_encode($result));
}

//Дошли сюда - значит все запросы выполнены без ошибок
$db_link->commit();//Коммитим все изменения и закрываем транзакцию

//Возвращаем результат - УСПЕШНО
$result = array();
$result["status"] = true;
$result["message"] = "Заказ создан. ID заказа: ".$order_id;
$result["order_id"] = $order_id;
exit(json_encode($result));
?>