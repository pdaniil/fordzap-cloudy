<?php 
	//Скрипт для отправки текстового сообщения $_POST["message"] через тг бота по $_POST["user_id"]
	header('Content-Type: application/json;charset=utf-8;');
	
	require_once ($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");

	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

	use TelegramBot\Api\Client;

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
	
	$user_id = $_POST["user_id"];
	$message = $_POST["message"];

	
	//Отправка уведомления в чат бот
	$select_chat_id = $db_link->prepare("SELECT `chat_id` FROM `telegram_users` WHERE `user_id` = ?;");
	$select_chat_id->execute([$user_id]);
	$res_select_chat_id = $select_chat_id->fetch(PDO::FETCH_ASSOC);
	
	if ($res_select_chat_id["chat_id"]) {
		$bot = new Client($DP_Config->telegram_bot_token);
		// Проверка наличия результата и получение значения chat_id
		if ($res_select_chat_id && isset($res_select_chat_id['chat_id'])) {
			$chatId = $res_select_chat_id['chat_id'];
			try {
				$bot->sendMessage($chatId, str_replace('&nbsp;', ' ', $message));
			}catch (Exception $e) {}
		}
	}
?>  