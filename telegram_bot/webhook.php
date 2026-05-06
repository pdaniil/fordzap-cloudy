<?php
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

$data = file_get_contents('php://input'); // весь ввод перенаправляем в $data

$data = json_decode($data, true);
if(empty($data))
{
    exit;
}

$text = trim($data['message']['text']);
$chat_id = $data['message']['from']['id'];

//если было отправлено сообщение
if(isset($data['message'])){
    $chat_id = $data['message']['from']['id'];
    $text = trim($data['message']['text']);
}
elseif (isset($data['callback_query'])){
    //Если нажали на кнопку
    $chat_id = $data['callback_query']['from']['id'];
    $text = trim($data['callback_query']['data']);
}

$check_chat_exists_query = $db_link->prepare("SELECT COUNT(*) FROM `telegram_chats` WHERE `chat_id` = ?;");
$check_chat_exists_query->execute( array($chat_id) );
$check_chat_exists = $check_chat_exists_query->fetchColumn();

if($check_chat_exists == 0 && trim($data['message']['text']) == "/start"){
    $sql = "INSERT INTO `telegram_chats`(`chat_id`) VALUES (?);";
    $query = $db_link->prepare($sql);
    $query->execute(array($chat_id));
}

$bot_state = get_bot_state($chat_id);
$reply_markup = get_reply_markup();
$postdata = http_build_query(
	array(
		'data' => $data,
		'bot_state' => $bot_state,
		'domain' => $DP_Config->domain_path,
        'reply_markup' => $reply_markup
	)
);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://docpartbot.testpart.ru/bot/bot.php");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

$curl_result = curl_exec($curl);

$curl_result = json_decode($curl_result, true);

if($curl_result['result'] == true)
{
    set_bot_state($chat_id, $curl_result['action']);
}

// получить текущее состояние бота для пользователя
function get_bot_state($chat_id)
{
    global $db_link;
    try{
        $sql = "SELECT `bot_state` FROM `telegram_chats` WHERE `chat_id` = ?";
    	$query = $db_link->prepare($sql);
    	$query->execute(array($chat_id));
    	$state_query = $query->fetch();
    	return $state_query['bot_state'];
    }
    catch (PDOException $e) 
    {	
        return '';
    }
    
}

// сохранить состояние бота для пользователя
function set_bot_state ($chat_id, $data)
{
    global $db_link;
    try{
        $binding_values = array();
        array_push($binding_values, $data);
    	array_push($binding_values, $chat_id); 
    
        $sql = "UPDATE `telegram_chats` SET `bot_state` = ? WHERE `chat_id` = ?";
    	$query = $db_link->prepare($sql);
    	$query->execute($binding_values);
    }
    catch (PDOException $e) 
    {	
        exit();	
    }
}

//Получение списка всех команд
function get_reply_markup()
{
    global $db_link;

    $buttons = array();
    $button_line = array();
    $keyboard = array();

    $sql = "SELECT `name`, `caption` FROM `telegram_commands_text`";
    $query = $db_link->prepare($sql);
    $query->execute();
    while( $command = $query->fetch() )
    {
        $buttons[] = array(
            'text' => $command['caption'],
            'callback_data' => $command['name'],
        );
    }

    for($i = 0; $i <= count($buttons); $i++)
    {
        if($i%2==0)
        {
            $keyboard[] = $button_line;
            $button_line = array();
            $button_line[] = $buttons[$i];
        }
        else if($i == count($buttons)){
            $keyboard[] = $button_line;
        }
        else{
            $button_line[] = $buttons[$i];
        }
    }

    $reply_markup = json_encode(array('inline_keyboard' => $keyboard));
    return $reply_markup;
}

?>