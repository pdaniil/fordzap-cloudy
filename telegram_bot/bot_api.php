<?php
header('Content-Type: text/html; charset=utf-8');

if($_SERVER['REMOTE_ADDR'] != "77.222.56.120"){
    $answer = array();
	$answer["result"] = false;
	$answer["message"] = "Server action error";
	exit( json_encode($answer) );
}

$result = array();

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

$action = $_POST["action"];
$type = $_POST["type"];
$bot_state = $_POST["state"];
$chat_id = $_POST["chat_id"];


//Доступные методы API
switch($type){
	case 'bot_command'  :
        //Обработка при переходе по ссылке
	    $arr_action = explode(" ", $action);
	    if(count($arr_action) > 1)
	    {
	        $code = $arr_action[1];
	        $sql = "SELECT `user_id`, `email`, `phone` FROM `users` WHERE `telegram_code` = ?;";
            $query = $db_link->prepare($sql);
            $query->execute(array($code));
            $user = $query->fetch();

            $check_chat_exists_query = $db_link->prepare("SELECT COUNT(*) FROM `telegram_chats` WHERE `chat_id` = ?;");
            $check_chat_exists_query->execute( array($chat_id) );
            $check_chat_exists = $check_chat_exists_query->fetchColumn();
            
            $contact = isset($user['email']) ? $user['email'] : $user['phone'];
            
            if($check_chat_exists == 0){
                
                try{
        	        $sql = "INSERT INTO `telegram_chats`(`user_id`, `chat_id`, `contact`) VALUES (?, ?, ?)";
                    $query = $db_link->prepare($sql);
                    $query->execute(array($user['user_id'], $chat_id, $contact));
                    
                    $sql = "UPDATE `users` SET `telegram` = 1 WHERE `user_id` = ?;";
                    $query = $db_link->prepare($sql);
                    $query->execute(array($user['user_id']));
                    
                    $result['status'] = true;
                    $result['message'] = "Вы успешно авторизованы в системе и можете получать уведомления.";
        	    }
        	    catch(Exception $e){
        	        $result['status'] = false;
                    $result['message'] = "При обработке данных возникла ошибка";
        	    }
            }
            else{
                $check_user_id_query = $db_link->prepare("SELECT `user_id` FROM `telegram_chats` WHERE `chat_id` = ?;");
                $check_user_id_query->execute( array($chat_id) );
                $check_user_id = $check_user_id_query->fetch();
                $check_user_id = $check_user_id['user_id'];
                if($check_user_id == 0)
                {
                    try{
            	        $sql = "UPDATE `telegram_chats` SET `user_id` = ?, `contact` = ? WHERE `chat_id` = ?;";
                        $query = $db_link->prepare($sql);
                        $query->execute(array($user['user_id'], $contact, $chat_id));
                        
                        $sql = "UPDATE `users` SET `telegram` = 1 WHERE `user_id` = ?;";
                        $query = $db_link->prepare($sql);
                        $query->execute(array($user['user_id']));
                    
                        $result['status'] = true;
                        $result['message'] = "Вы успешно авторизованы в системе и можете получать уведомления.";
            	    }
            	    catch(Exception $e){
            	        $result['status'] = false;
                        $result['message'] = "При обработке данных возникла ошибка";
        	        }
                }
        	    else{
        	        $result['status'] = true;
                    $result['message'] = "Вы уже зарегистрированы";
        	    }
                
            }
            break;
	    }
	    
	    switch($action){
	        case '/help' : 
	        case '/start' : 				
            	$text = 'Здравствуйте, список доступных команд указан ниже:'; 

        	   	$result['status'] = true;
                $result['message'] = $text;
            break;
			case '/notifySettings' : 
	            $sql = "SELECT `notify_flag` FROM `telegram_chats` WHERE `chat_id` = ?";
            	$query = $db_link->prepare($sql);
            	$query->execute(array($chat_id));
				$flag = $query->fetch();
				$flag = $flag['notify_flag'];

				if($flag == 1)
				{
					$sql = "UPDATE `telegram_chats` SET `notify_flag` = 0 WHERE `chat_id` = ?";
                    $query = $db_link->prepare($sql);
                    $query->execute(array($chat_id));

					$result['status'] = true;
                	$result['message'] = "Уведомления отключены";
				}
				else{
					$sql = "UPDATE `telegram_chats` SET `notify_flag` = 1 WHERE `chat_id` = ?";
                    $query = $db_link->prepare($sql);
                    $query->execute(array($chat_id));

					$result['status'] = true;
                	$result['message'] = "Уведомления включены";
				}
				    	   	
            break;
	        default :
        		$sql = "SELECT `text` FROM `telegram_commands_text` WHERE `name` = ?";
            	$query = $db_link->prepare($sql);
            	$query->execute(array($action));
            	$state_query = $query->fetch();
            	$result['status'] = true;
                $result['message'] = $state_query['text'];
            break;
	    }
	    break;
	case 'text':
	    $chat_id = $_POST["chat_id"];
	    switch($bot_state){
	        case '/contact':
	            
	            // Обработка телефона, чтобы работал поиск по маске
	            if(!filter_var($action, FILTER_VALIDATE_EMAIL)){
	                 // Сначала чистим от лишних символов
                    $action = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $action);
                    if(strlen($action) == 11){
                    	$action = substr($action, 1);
                    }
                    
                    //Формируем маску
                    if(strlen($action) == 10){
                    	$action = '+7 ('. substr($action, 0, 3) .') '. substr($action, 3, 3) .'-'. substr($action, 6, 2) .'-'. substr($action, 8, 2);
                    }
	            }
                    
	            try{
        	        $sql = "UPDATE `telegram_chats` SET `contact` = ? WHERE `chat_id` = ?";
                    $query = $db_link->prepare($sql);
                    $query->execute(array($action, $chat_id));
                    
                    $result['status'] = true;
                    $result['message'] = "Укажите пароль от аккаунта.";
        	    }
        	    catch(Exception $e){
        	        $result['status'] = false;
                    $result['message'] = "При обработке данных возникла ошибка";
        	    }
        	    
                break;
            case '/password':
	          try{
        	        
        	        $sql = "SELECT `contact` FROM `telegram_chats` WHERE `chat_id` = ?";
                	$contact_query = $db_link->prepare($sql);
                	$contact_query->execute(array($chat_id));
                	$contact = $contact_query->fetch();
                	$contact = $contact["contact"];
                	
        	        $password = md5($action.$DP_Config->secret_succession);
        	        
        	        $sql = "SELECT `user_id` FROM `users` WHERE (`email` = ? OR `phone` = ?) AND `password` = ?";
                	$user_id_query = $db_link->prepare($sql);
                	$user_id_query->execute(array($contact, $contact, $password));
                	$user_id = $user_id_query->fetch();
                	$user_id = $user_id["user_id"];
                	
                
                	
                	if(!empty($user_id)){
                	    $sql = "UPDATE `telegram_chats` SET `user_id` = ? WHERE `chat_id` = ?";
                        $query = $db_link->prepare($sql);
                        $query->execute(array($user_id, $chat_id));
                        
                        $sql = "UPDATE `users` SET `telegram` = 1 WHERE `user_id` = ?;";
                        $query = $db_link->prepare($sql);
                        $query->execute(array($user_id));
                    
                	    $result['status'] = true;
                        $result['message'] = "Ваш контакт успешно сохранен. Теперь вы можете получать уведомления и проверять статус заказа.";
                	}
                	else{
                	    $result['status'] = false;
                        $result['message'] = "Неверный логин/пароль";
                	}
        	    }
        	    catch(Exception $e){
        	        $result['status'] = false;
                    $result['message'] = "При обработке данных возникла ошибка";
        	    }
            	    
                break;
            case '/checkOrderStatus':
                //Получаем id пользователя этого чата
                $sql = "SELECT `user_id` FROM `telegram_chats` WHERE `chat_id` = ?";
            	$user_id_query = $db_link->prepare($sql);
            	$user_id_query->execute(array($chat_id));
            	$user_id = $user_id_query->fetch();
            	$user_id = $user_id["user_id"];
            	
            	//Получаем заказ по id и id пользователя
	            $sql = "SELECT `name` FROM `shop_orders_statuses_ref` WHERE id = (SELECT `status` FROM `shop_orders` WHERE `id` = ? AND `user_id` = ?);";
                $query = $db_link->prepare($sql);
                $query->execute(array($action, $user_id));
                $order_status = $query->fetch();
                $order_status = $order_status["name"];
                
                if (!empty($order_status)){
                    $result['status'] = true;
                    $result['message'] = "Статус заказа: ".$order_status;
                }
                else{
                    $result['status'] = false;
                    $result['message'] = "Неверный ID заказа";
                }
                
                break;
            case '/checkPositionStatus':
            	//Получаем статус позиции по ее id 
	            $sql = "SELECT `name` FROM `shop_orders_items_statuses_ref` WHERE id = (SELECT `status` FROM `shop_orders_items` WHERE `id` = ?);";
                $query = $db_link->prepare($sql);
                $query->execute(array($action));
                $order_item_status = $query->fetch();
                $order_item_status = $order_item_status["name"];
                
                if (!empty($order_item_status)){
                    $result['status'] = true;
                    $result['message'] = "Статус позиции: ".$order_item_status;
                }
                else{
                    $result['status'] = false;
                    $result['message'] = "Неверный ID позиции";
                }
                
                break;
            
            default : break;
	    }
	    break;
	default :
		$answer = array();
		$answer["result"] = false;
		$answer["message"] = "Server action error";
		exit( json_encode($answer) );
	break;
}






exit(json_encode($result))
?>