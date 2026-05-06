<?php


require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS


//-----------------------------------------------------------------------------------------------

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

//-----------------------------------------------------------------------------------------------

require_once($_SERVER["DOCUMENT_ROOT"]."/modules/debug/debug.class.php");


//Получаем настройки робота
$ROBOT_SQL = "
SELECT *
FROM
	`shop_sao_settings`
;";

$robot_query = $db_link->prepare($ROBOT_SQL);
$robot_query->execute();
$robot_record_data = $robot_query->fetch();

$ex_order_status_ids = array();
$ex_order_item_status_ids = array();
$ex_storages_ids = array();
$ex_user_groups_ids = array();
$actions_enable_db = array();
$actions_enable = array();
$enable_service = 0;
$write_log = 0;
$positive_balance = 0;

$ex_order_status_ids = json_decode($robot_record_data['ex_order_status_ids'], true);
$ex_order_item_status_ids = json_decode($robot_record_data['ex_order_item_status_ids'], true);
$ex_storages_ids = json_decode($robot_record_data['ex_storages_ids'], true);
$ex_user_groups_ids = json_decode($robot_record_data['ex_user_groups_ids'], true);
$actions_enable_db = json_decode($robot_record_data['actions_enable'], true);

if(!empty($actions_enable_db)) {
	foreach($actions_enable_db as $action_id => $action_value) {
		if($action_value == 1) $actions_enable[] = $action_id;
	}
}

$enable_service = ($robot_record_data['enable'] == 1) ? true : false;
$write_log = ($robot_record_data['write_log'] == 1) ? true : false;
$positive_balance = ($robot_record_data['positive_balance'] == 1) ? true : false;


if($write_log) {
    $debug = new Debug();

    $debug->logger("Запуск скрипта", date('Y-m-d H:i:s'));
}

if($write_log) {
    
    //Получаем наименования статусов заказа
    $ex_order_status_names = array();
    if(!empty($ex_order_status_ids)) {
        $order_status_names_query = $db_link->prepare("SELECT `id`, `name` FROM `shop_orders_statuses_ref` WHERE `id` IN (". implode(",", $ex_order_status_ids) .") ;");
        $order_status_names_query->execute();

        while($order_status_names_record_data = $order_status_names_query->fetch())
        {
            $order_status_names_record_id = $order_status_names_record_data['id'];
            $order_status_names_record_name = $order_status_names_record_data['name'];
            $ex_order_status_names[$order_status_names_record_id] = $order_status_names_record_name;
        }
    }

    
    //Получаем наименовани статусов позиций заказа
    $ex_order_item_status_names = array();
    if(!empty($ex_order_item_status_ids)) {
        $order_item_status_names_query = $db_link->prepare("SELECT `id`, `name` FROM `shop_orders_items_statuses_ref` WHERE `id` IN (". implode(",", $ex_order_item_status_ids) .") ;");
        $order_item_status_names_query->execute();
        while($order_item_status_names_record_data = $order_item_status_names_query->fetch())
        {
            $order_item_status_names_record_id = $order_item_status_names_record_data['id'];
            $order_item_status_names_record_name = $order_item_status_names_record_data['name'];
            $ex_order_item_status_names[$order_item_status_names_record_id] = $order_item_status_names_record_name;
        }
    }
    
    //Получаем наименование действий автозаказа
    $actions_enable_names = array();
    if(!empty($actions_enable)) {
        $ctions_enable_names_query = $db_link->prepare("SELECT `id`, `name` FROM `shop_sao_actions` WHERE `id` IN (". implode(",", $actions_enable) .") ;");
        $ctions_enable_names_query->execute();
        while($ctions_enable_names_record_data = $ctions_enable_names_query->fetch())
        {
            $ctions_enable_names_record_id = $ctions_enable_names_record_data['id'];
            $ctions_enable_names_record_name = $ctions_enable_names_record_data['name'];
            $actions_enable_names[$ctions_enable_names_record_id] = $ctions_enable_names_record_name;
        }
    }

    //Получаем наименование складов
    $ex_storages_names = array();
    if(!empty($ex_storages_ids)) {
			$ex_storages_names_query = $db_link->prepare("SELECT `id`, `name` FROM `shop_storages` WHERE `id` IN (". implode(",", $ex_storages_ids) .") ;");
			$ex_storages_names_query->execute();
			while($ex_storages_names_query_record_data = $ex_storages_names_query->fetch())
			{
					$ex_storages_names_record_id = $ex_storages_names_query_record_data['id'];
					$ex_storages_names_record_name = $ex_storages_names_query_record_data['name'];
					$ex_storages_names[$ex_storages_names_record_id] = $ex_storages_names_record_name;
			}
    }


    //Получаем наименование групп
		$ex_user_groups_names = array();
		if(!empty($ex_user_groups_ids)) {
			$ex_user_groups_names_query = $db_link->prepare("SELECT `id`, `value` FROM `groups` WHERE `id` IN (". implode(",", $ex_user_groups_ids) .") ;");
			$ex_user_groups_names_query->execute();
			while($ex_user_groups_names_query_record_data = $ex_user_groups_names_query->fetch())
			{
					$ex_user_groups_names_record_id = $ex_user_groups_names_query_record_data['id'];
					$ex_user_groups_names_record_name = $ex_user_groups_names_query_record_data['value'];
					$ex_user_groups_names[$ex_user_groups_names_record_id] = $ex_user_groups_names_record_name;
			}
		}
    
	$debug->logger("Исключения статусов заказа", $ex_order_status_names, true);
	$debug->logger("Исключения статусов позиции заказа", $ex_order_item_status_names, true);
	$debug->logger("Исключения складов", $ex_storages_names, true);
	$debug->logger("Исключения групп", $ex_user_groups_names, true);
	$debug->logger("Возможные действия", $actions_enable_names, true);
}


if($enable_service == false) {
    
    if($write_log) {
    	$debug->logger("Сервис выключен");
    }
}
else
{
    //Получаем позиции заказов, у которых есть задание роботу
    $SQL_orders_items_for_robot = "SELECT 
    `shop_orders_items`.`id`,
    `shop_orders_items`.`sao_robot`,
    `shop_orders`.`id` AS `order_id`,
    `shop_orders`.`status`,
		`shop_sao_actions`.`name` AS 'action',
    `shop_sao_actions`.`script`,
    `shop_sao_actions`.`id` AS 'action_id',
    `users`.`user_id`,
		`users_groups_bind`.`group_id`
    FROM `shop_orders_items`
		INNER JOIN `shop_storages` ON `shop_storages`.`id` = `shop_orders_items`.`t2_storage_id` 
    INNER JOIN `shop_sao_states_types_link` ON `shop_storages`.`interface_type` = `shop_sao_states_types_link`.`interface_type_id`  AND `shop_orders_items`.`sao_state` = `shop_sao_states_types_link`.`state_id`
    INNER JOIN `shop_sao_states_types_actions_link` ON `shop_sao_states_types_actions_link`.`state_type_id` = `shop_sao_states_types_link`.`id`
    INNER JOIN `shop_sao_actions` ON `shop_sao_actions`.`id` = `shop_sao_states_types_actions_link`.`action_id`
    LEFT JOIN `shop_orders` ON  `shop_orders`.`id` = `shop_orders_items`.`order_id`
    LEFT JOIN `users` ON  `shop_orders`.`user_id` = `users`.`user_id`
		LEFT JOIN `users_groups_bind` ON `users_groups_bind`.`user_id` = `users`.`user_id`
    ";
    
    $where = "";
    
    $where .= " WHERE `users`.`auto_sao_user` = 1";

// 		$where .= " AND `shop_orders`.`paid` = 2 OR `shop_orders`.`paid` = 1";
    
    //Проверяем заказы
    if(!empty($ex_order_status_ids)) {
      if($where == "") {
    		$where .= " WHERE";
    	} 
    	else
    	{
    		$where .= " AND";
    	}
    
    	$ex_order_status_ids_str = implode(',', $ex_order_status_ids);
    
    	$where .= " `shop_orders`.`status` NOT IN ($ex_order_status_ids_str)";
    }
    
    //Проверяем позиции заказов
    if(!empty($ex_order_item_status_ids)) {
        if($where == "") {
    		$where .= " WHERE";
    	} 
    	else
    	{
    		$where .= " AND";
    	}
    
    	$ex_order_item_status_ids_str = implode(',', $ex_order_item_status_ids);
    
    	$where .= " `shop_orders_items`.`status` NOT IN ($ex_order_item_status_ids_str)";
    }

	  //Проверяем склады
		if(!empty($ex_storages_ids)) {
				if($where == "") {
				$where .= " WHERE";
			} 
			else
			{
				$where .= " AND";
			}
		
			$ex_storages_ids_str = implode(',', $ex_storages_ids);
		
			$where .= " `shop_storages`.`id` NOT IN ($ex_storages_ids_str)";
		}

		//Проверяем группы
		if(!empty($ex_user_groups_ids)) {
				if($where == "") {
				$where .= " WHERE";
			} 
			else
			{
				$where .= " AND";
			}
		
			$ex_user_groups_ids_str = implode(',', $ex_user_groups_ids);
		
			$where .= " `users_groups_bind`.`group_id` NOT IN ($ex_user_groups_ids_str)";
		}

    //Проверяем возможные действия
    if(!empty($actions_enable)) {
        if($where == "") {
    		$where .= " WHERE";
    	} 
    	else
    	{
    		$where .= " AND";
    	}
    
    	$actions_enable_str = implode(',', $actions_enable);
    
    	$where .= " `shop_orders_items`.`sao_robot` IN ($actions_enable_str)";
    }
    
    $SQL_orders_items_for_robot .= $where . ";";
    
    
    if($write_log) {
    	$debug->logger("Запрос в БД", $SQL_orders_items_for_robot, true);
    }
    
    $orders_items_query = $db_link->prepare($SQL_orders_items_for_robot);
    $orders_items_query->execute();
    
    $counter = 0;
    
    while( $item = $orders_items_query->fetch() )
    {
    	$item_id = $item['id'];
    	$user_id = $item['user_id'];
        if($write_log) {
            $debug->logger_collapse_start("$counter Работаем с позицией {$item_id}.");
    
            $debug->logger("$counter Данные позиции", $item, true);
        }
        
        
    $user_balance = $db_link->prepare('SELECT *,( IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=1 AND `active` = 1), 0) - IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=0 AND `active` = 1),0) ) AS `balance` FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `active` = 1;');
		$user_balance->bindValue(':user_id', $user_id);
		$user_balance->execute();
		$balance_record = $user_balance->fetch(PDO::FETCH_ASSOC);
		
		$balance = $balance_record["balance"];
		if($balance == "")
		{
			$balance = 0;
		}
		
		if($write_log) {

            $debug->logger("$counter Баланс клиента {$user_id}", $balance);
        }
        
        $allowed = true;
        
				// Проверка на баланс
        if($positive_balance == true && $balance < 0) $allowed = false;

				// Проверка на следующее действия: sao_robot должен соответствовать настроенному действию в БД
        if($item["sao_robot"] != $item["action_id"]) $allowed = false;
        
        $url = $DP_Config->domain_path.$DP_Config->backend_dir."/content/shop/sao/ajax_exec_action.php?order_item_id=".$item_id."&sao_action_id=".$item["sao_robot"]."&key=".urlencode($DP_Config->tech_key);
        
        $allowed_text = $allowed ? 'Все условия выполнены.' : 'Условия не соблюдены. Ошибка баланса или не соответствие действия.';
        
        if($write_log) {

            $debug->logger("$counter Ссылка для активации действия {$url}", $allowed_text);
        }

        if($allowed == true) {
            
            $ch = curl_init();
        	curl_setopt($ch, CURLOPT_URL,  $url);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        	curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERPWD, $DP_Config->http_login . ":" . $DP_Config->http_password);
        	$curl_result = curl_exec($ch);
        
        	if($write_log) {
        		$curl_result = json_decode($curl_result, true);
        
                $debug->logger("$counter Выполнение действия", $curl_result, true);
            }
        
        	curl_close($ch);
        	
        }
   

    	if($write_log) {
            $debug->logger_collapse_end();
        }
    
    	$counter++;
    }
    
}



?>