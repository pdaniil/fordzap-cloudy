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

function exitFunction($result) {
    exit(json_encode($result));
}

function get_cart() {
	global $db_link;
	
	$select_cart_data = $db_link->prepare("SELECT * FROM `shop_orders_items_temp` WHERE `order_id` = ?;");
	$select_cart_data->execute([$_POST["order_id"]]);
	
	$res = $select_cart_data->fetchAll(PDO::FETCH_ASSOC);
	
	exitFunction(["items" => $res, "success" => true]);
}

function remove_item() {
	global $db_link;
	
	$select_cart_data = $db_link->prepare("DELETE FROM `shop_orders_items_temp` WHERE `id` = ?;");
	$select_cart_data->execute([$_POST["item_id"]]);
	
	exitFunction(["success" => true]);
}

function add_to_order() {
    global $db_link;
    
    $order_id = (int) $_POST["order_id"];
    
    if(empty($order_id)) {
        exitFunction([
            "success" => false,
            "message" => "No order ID provided"
        ]);
    }
    
    try {
        // Начинаем транзакцию
        if(!$db_link->beginTransaction()) {
            throw new Exception("Не удалось стартовать транзакцию");
        }
        
        // Получаем все записи из временной таблицы
        $sql_select = "SELECT * FROM `shop_orders_items_temp` WHERE `order_id` = ?";
        $query_select = $db_link->prepare($sql_select);
        $query_select->execute([$order_id]);
        $temp_items = $query_select->fetchAll(PDO::FETCH_ASSOC);
        
        if(empty($temp_items)) {
            $db_link->rollBack();
            exitFunction([
                "success" => false,
                "message" => "Нет временных позиций для этого заказа"
            ]);
        }
        
        // Получаем список полей для shop_orders_items (без id)
        $sql_columns = "SHOW COLUMNS FROM `shop_orders_items` WHERE `Field` != 'id'";
        $query_columns = $db_link->query($sql_columns);
        $columns = $query_columns->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Подготавливаем запрос для вставки
        $column_list = implode(', ', array_map(function($col) {
            return "`$col`";
        }, $columns));
        
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql_insert = "INSERT INTO `shop_orders_items` ($column_list) VALUES ($placeholders)";
        $stmt_insert = $db_link->prepare($sql_insert);
        
        // Получаем данные для логирования
        $admin_id = DP_User::getAdminId();
        $current_time = time();
        
        // Переносим каждую запись
        $transferred_ids = [];
        foreach($temp_items as $temp_item) {
            // Формируем значения для вставки
            $values = [];
            foreach($columns as $column) {
                $values[] = $temp_item[$column] ?? null;
            }
            
            // Вставляем запись
            if(!$stmt_insert->execute($values)) {
                throw new Exception("Ошибка при переносе записи из временной таблицы");
            }
            
            $new_id = $db_link->lastInsertId();
            $transferred_ids[] = [
                'old_id' => $temp_item['id'],
                'new_id' => $new_id
            ];
            
            // ЛОГИРОВАНИЕ КАК В ИСХОДНОМ КОДЕ
            $log_text = 'В заказ добавлена позиция из проценки id ' . $new_id . 
                       ' - <b>' . $temp_item['t2_manufacturer'] . ' - ' . $temp_item['t2_article'] . 
                       '</b> с ценой: ' . $temp_item['price'];
            
            // Используем точно такой же формат как в оригинальном коде
            $db_link->prepare('INSERT INTO `shop_orders_logs` (`order_id`,`time`,`user_id`,`is_manager`,`text`, `is_robot`) VALUES (?, ?, ?, ?, ?, ?)')
                   ->execute([
                       $order_id, 
                       $current_time, 
                       $admin_id, 
                       1,
                       $log_text, 
                       0
                   ]);
        }
        
        // Удаляем из временной таблицы
        $sql_delete = "DELETE FROM `shop_orders_items_temp` WHERE `order_id` = ?";
        $query_delete = $db_link->prepare($sql_delete);
        if(!$query_delete->execute([$order_id])) {
            throw new Exception("Ошибка при удалении записей из временной таблицы");
        }
        
        // Коммитим
        $db_link->commit();
        
        exitFunction([
            "success" => true,
            "message" => "Данные успешно перенесены",
            "transferred_count" => count($temp_items),
            "transferred_ids" => $transferred_ids,
            "order_id" => $order_id
        ]);
        
    } catch (Exception $e) {
        // Откатываем при ошибке
        if($db_link->inTransaction()) {
            $db_link->rollBack();
        }
        
        // Логируем ошибку
        if(isset($order_id)) {
            $admin_id = DP_User::getAdminId();
            $error_text = 'Ошибка при переносе позиций из временной таблицы: ' . $e->getMessage();
            
            $db_link->prepare('INSERT INTO `shop_orders_logs` (`order_id`,`time`,`user_id`,`is_manager`,`text`, `is_robot`) VALUES (?, ?, ?, ?, ?, ?)')
                   ->execute([
                       $order_id, 
                       time(), 
                       $admin_id, 
                       1,
                       $error_text, 
                       0
                   ]);
        }
        
        exitFunction([
            "success" => false,
            "message" => "Ошибка при переносе данных: " . $e->getMessage()
        ]);
    }
}    

if (isset($_POST["action"]) && function_exists($_POST["action"]))
    $_POST["action"]();
else
    exitFunction(["status" => 404, "message" => "Метод не определён"]);
?>