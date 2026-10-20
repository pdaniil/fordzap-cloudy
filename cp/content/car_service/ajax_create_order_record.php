<?php
header('Content-Type: application/json;charset=utf-8;');

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

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

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/notifications/notify_helper.php" );

$user_id = isset($_POST["user_id"]) ? (int)$_POST["user_id"] : 0;
$session_id = 0;
if($user_id > 0) {
    $session_id = 0;
} else {
    $session_record = DP_User::getUserSession();
    if($session_record == false) {
        $result = array();
        $result["status"] = false;
        $result["code"] = "incorrect_session";
        $result["message"] = "Ошибка сессии";
        exit(json_encode($result));
    }
    $session_id = $session_record["id"];
}

$record_id = isset($_POST['record_id']) ? (int)$_POST['record_id'] : 0;
$service_record_date = isset($_POST['service_record_date']) ? $_POST['service_record_date'] : '';
$service_post_id = isset($_POST['service_post_id']) ? (int)$_POST['service_post_id'] : 0;
$service_sto_id = isset($_POST['service_sto_id']) ? (int)$_POST['service_sto_id'] : 0;
$service_car_id = isset($_POST['service_car_id']) ? (int)$_POST['service_car_id'] : 0;
$service_worker_id = isset($_POST['service_worker_id']) ? (int)$_POST['service_worker_id'] : 0;
$shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : 0;
$delivery_mode = isset($_POST['delivery_mode']) ? (int)$_POST['delivery_mode'] : 0;

// Получаем услуги как JSON и декодируем в массив
$services_json = isset($_POST['services']) ? $_POST['services'] : '';
$services = json_decode($services_json, true);
if (!is_array($services)) {
    $services = [];
}

if (!$service_record_date || !$service_post_id || !$service_sto_id || !$service_car_id) {
    $result = array();
    $result["status"] = false;
    $result["message"] = "Не хватает данных для записи в сервис";
    exit(json_encode($result));
}

// Вспомогательная функция для вставки услуг
function insertRecordServices($record_id, $services, $db_link) {
    if (empty($services)) {
        return true;
    }
    $insertService = $db_link->prepare("
        INSERT INTO car_service_record_services 
        (record_id, count, hourly_rate, caption, price, discount, status_id, fix_price) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($services as $item) {
        $count = (int)($item['count'] ?? 1);
        $hourly_rate = (float)($item['hourly_rate'] ?? 0);
        $price = (float)($item['price'] ?? 0);
        $discount = (float)($item['discount'] ?? 0);
        $caption = $item['caption'] ?? '';

        $insertService->execute([
            $record_id,
            $count,
            $hourly_rate,
            $caption,
            $price,
            $discount,
            -1,
            0
        ]);
    }
    return true;
}

try {
    if( ! $db_link->beginTransaction() ) {
        throw new Exception("Не удалось стартовать транзакцию");
    }
    
    $time = time();
    $for_created_status_query = $db_link->prepare('SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_created`=1;');
    $for_created_status_query->execute();
    $for_created_status_record = $for_created_status_query->fetch();
    $for_created_status = $for_created_status_record["id"];
    if( (int)$for_created_status <= 0 ) {
        throw new Exception("Не определен статус для нового заказа.");
    }

    // Установка параметров способа получения по умолчанию
    $how_get = array(
        "mode" => "1",
        "office_id" => $shop_id
    );
    $office_executer = $shop_id;
    $how_get_mode = 1; // для колонки how_get

    // Если record_id > 0, то это редактирование существующей записи
    if ($record_id > 0) {
        // Проверяем, есть ли уже заказ
        $checkOrder = $db_link->prepare("SELECT orders_ids FROM car_service_records WHERE id = ? AND sto_id = ?");
        $checkOrder->execute([$record_id, $service_sto_id]);
        $existingOrderId = $checkOrder->fetchColumn();
        $order_id = (int)$existingOrderId;

        // Если заказа нет (или 0 / -1), создаём новый
        if ($order_id <= 0) {
            // Создаём заказ
            $insertOrder = $db_link->prepare('INSERT INTO `shop_orders` (`user_id`, `session_id`, `time`, `successfully_created`, `status`, `paid`, `how_get`, `how_get_json`, `phone_not_auth`, `email_not_auth`) VALUES (?,?,?,?,?,?,?,?,?,?);');
            if( !$insertOrder->execute( array($user_id, $session_id, $time, 0, $for_created_status, 0, $how_get_mode, json_encode($how_get), '', '' ) ) ) {
                throw new Exception("SQL-ошибка создания заказа.");
            }
            $order_id = $db_link->lastInsertId();
            if( ! ($order_id > 0) ) {
                throw new Exception("ID заказа не определен");
            }
            $updateOrder = $db_link->prepare('UPDATE `shop_orders` SET `successfully_created`=1, `office_id`=? WHERE `id` = ?;');
            if( !$updateOrder->execute( array($office_executer, $order_id) ) ) {
                throw new Exception("SQL-ошибка указания точки обслуживания.");
            }
            // Комментарий
            if( !empty($_POST["order_message"]) ) {
                $order_message = trim($_POST["order_message"]);
                $order_message = htmlentities($order_message);
                $order_message = str_replace("\r","",$order_message);
                $order_message = str_replace("\t","",$order_message);
                $order_message = str_replace("\n","<br/>",$order_message);
                $db_link->prepare('INSERT INTO `shop_orders_messages` (`order_id`, `is_customer`, `text`, `time`) VALUES (?,?,?,?);')->execute( array($order_id, 1, $order_message, time()) );
            }
            $db_link->prepare('INSERT INTO `shop_orders_logs` (`order_id`,`time`,`user_id`,`is_manager`,`text`) VALUES (?,?,?,?,?);')->execute( array($order_id, time(), $user_id, 0, 'Заказ создан при подтверждении записи') );
        }

        // Обновляем запись
        $record_time = strtotime($service_record_date);
        if (!$record_time) {
            throw new Exception("Некорректная дата/время записи");
        }
        $step_query = $db_link->prepare("SELECT `record_step` FROM `car_services` WHERE `id` = ?");
        $step_query->execute([$service_sto_id]);
        $step = (int)$step_query->fetchColumn();
        if (!$step) $step = 30;
        $time_from = $record_time;
        $time_to = $record_time + $step * 60;

        $worker_percent = 0;
        if ($service_worker_id > 0) {
            $worker_query = $db_link->prepare("SELECT `service_percent` FROM `car_service_personal` WHERE `id` = ?");
            $worker_query->execute([$service_worker_id]);
            $worker_percent = (int)$worker_query->fetchColumn();
        }

        $updateRecord = $db_link->prepare('UPDATE `car_service_records` SET 
            `time` = ?,
            `post_id` = ?,
            `worker_id` = ?,
            `user_id` = ?,
            `car_id` = ?,
            `time_from` = ?,
            `time_to` = ?,
            `worker_percent` = ?,
            `orders_ids` = ?
            WHERE `id` = ? AND `sto_id` = ?');
        if (!$updateRecord->execute([
            $service_record_date,
            $service_post_id,
            $service_worker_id > 0 ? $service_worker_id : null,
            $user_id,
            $service_car_id,
            $time_from,
            $time_to,
            $worker_percent,
            $order_id,
            $record_id,
            $service_sto_id
        ])) {
            throw new Exception("Ошибка обновления записи в сервис");
        }

        // Удаляем старые услуги
        $db_link->prepare("DELETE FROM `car_service_record_services` WHERE `record_id` = ?")->execute([$record_id]);

        // Вставляем новые услуги с переданными данными (из JSON)
        insertRecordServices($record_id, $services, $db_link);

        $message = "Запись обновлена, ID заказа: " . $order_id;

    } else {
        // ---------- СОЗДАНИЕ НОВОЙ ЗАПИСИ ----------
        $insertOrder = $db_link->prepare('INSERT INTO `shop_orders` (`user_id`, `session_id`, `time`, `successfully_created`, `status`, `paid`, `how_get`, `how_get_json`, `phone_not_auth`, `email_not_auth`) VALUES (?,?,?,?,?,?,?,?,?,?);');
        if( !$insertOrder->execute( array($user_id, $session_id, $time, 0, $for_created_status, 0, $how_get_mode, json_encode($how_get), '', '' ) ) ) {
            throw new Exception("SQL-ошибка создания заказа.");
        }
        $order_id = $db_link->lastInsertId();
        if( ! ($order_id > 0) ) {
            throw new Exception("ID заказа не определен");
        }
        $updateOrder = $db_link->prepare('UPDATE `shop_orders` SET `successfully_created`=1, `office_id`=? WHERE `id` = ?;');
        if( !$updateOrder->execute( array($office_executer, $order_id) ) ) {
            throw new Exception("SQL-ошибка указания точки обслуживания.");
        }
        if( !empty($_POST["order_message"]) ) {
            $order_message = trim($_POST["order_message"]);
            $order_message = htmlentities($order_message);
            $order_message = str_replace("\r","",$order_message);
            $order_message = str_replace("\t","",$order_message);
            $order_message = str_replace("\n","<br/>",$order_message);
            $db_link->prepare('INSERT INTO `shop_orders_messages` (`order_id`, `is_customer`, `text`, `time`) VALUES (?,?,?,?);')->execute( array($order_id, 1, $order_message, time()) );
        }
        $db_link->prepare('INSERT INTO `shop_orders_logs` (`order_id`,`time`,`user_id`,`is_manager`,`text`) VALUES (?,?,?,?,?);')->execute( array($order_id, time(), $user_id, 0, 'Заказ создан') );

        $record_time = strtotime($service_record_date);
        if (!$record_time) {
            throw new Exception("Некорректная дата/время записи");
        }
        $step_query = $db_link->prepare("SELECT `record_step` FROM `car_services` WHERE `id` = ?");
        $step_query->execute([$service_sto_id]);
        $step = (int)$step_query->fetchColumn();
        if (!$step) $step = 30;
        $time_from = $record_time;
        $time_to = $record_time + $step * 60;

        $worker_percent = 0;
        if ($service_worker_id > 0) {
            $worker_query = $db_link->prepare("SELECT `service_percent` FROM `car_service_personal` WHERE `id` = ?");
            $worker_query->execute([$service_worker_id]);
            $worker_percent = (int)$worker_query->fetchColumn();
        }

        $insertRecord = $db_link->prepare('INSERT INTO `car_service_records` 
            (`time`, `post_id`, `sto_id`, `worker_id`, `orders_ids`, `user_id`, `car_id`, `time_from`, `time_to`, `is_archive`, `is_paid`, `is_view`, `prepayment`, `status_id`, `worker_percent`) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        if (!$insertRecord->execute([
            $service_record_date,
            $service_post_id,
            $service_sto_id,
            $service_worker_id > 0 ? $service_worker_id : null,
            $order_id,
            $user_id,
            $service_car_id,
            $time_from,
            $time_to,
            0,
            0,
            0,
            0,
            3,
            $worker_percent
        ])) {
            throw new Exception("Ошибка создания записи в сервис");
        }
        $record_id = $db_link->lastInsertId();
        if (!$record_id) {
            throw new Exception("Не удалось получить ID записи");
        }

        // Вставляем услуги из переданного JSON
        insertRecordServices($record_id, $services, $db_link);

        $message = "Заказ и запись созданы. ID заказа: " . $order_id;
    }

    $db_link->commit();

    $result = array();
    $result["status"] = true;
    $result["message"] = $message;
    $result["order_id"] = $order_id;
    exit(json_encode($result));

} catch (Exception $e) {
    $db_link->rollBack();
    $result = array();
    $result["status"] = false;
    $result["message"] = "Ошибка: ".$e->getMessage();
    exit(json_encode($result));
}
?>