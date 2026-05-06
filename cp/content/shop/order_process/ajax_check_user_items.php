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
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");


//Проверяем право менеджера
if( ! DP_User::isAdmin())
{
	$result["status"] = false;
	$result["message"] = "Forbidden";
	$result["code"] = 501;
	exit(json_encode($result));//Вообще не является администратором бэкенда
}


	//Получаем данные:
	$binding_values = [];
	$arr_id = json_decode($_POST["request_object"]);
	
	$IN_condition = "";
	
	$flag = true;
	
	foreach ($arr_id as $value)
	{
		if ($flag)
		{
			$flag = false;
			$IN_condition = " ?";
		}
		else
		{
			$IN_condition .= ", ?";
		}
		$binding_values[] = $value;
	}
	
	$SQL_select = "SELECT `user_id`, `id` FROM `shop_orders` WHERE `id` IN (SELECT `order_id` FROM `shop_orders_items` WHERE `id` IN (".$IN_condition.")) GROUP BY `user_id` ";
	
	$query = $db_link->prepare($SQL_select);
	$query->execute( $binding_values  );
	$order_id = '';
	$counter = 0;
	while ( $user = $query->fetch() )
	{
		$order_id = $user['id'];
		$counter++;
		if ($counter > 1)
		{
			$answer = array();
			$answer["status"] = false;
			$answer["data"] = $SQL_select;
			$answer["message"] = "Выбраны ID заказов разных пользователей.";
			exit(json_encode($answer));
		}
		
	}
	
	
 $answer = array();
 $answer["status"] = true;
 $answer["data"] = array( 'order_id' => $order_id);
 $answer["message"] = "Успешно.";
 exit(json_encode($answer));

?>