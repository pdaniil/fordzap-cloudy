<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Соединение с основной БД
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

function exitFunction($result) {
    exit(json_encode($result));
}

function get_work_time() {
    global $db_link;
    $select = $db_link->prepare("SELECT `time_from`, `time_to`, `operating_mode` FROM `car_services` WHERE `id` = ?;");
    $select->execute([$_POST["id"]]);

    $res = $select->fetch(PDO::FETCH_ASSOC);

    exitFunction(["time" => $res]);
}

function get_post_types() {
    global $db_link;
    $select = $db_link->prepare("SELECT * FROM `car_service_posts`;");
    $select->execute();

    $res = $select->fetchAll(PDO::FETCH_ASSOC);

    exitFunction(["posts" => $res]);
}

function get_records() {
    global $db_link;
    $select = $db_link->prepare("SELECT * FROM `car_service_records` WHERE `time` >= ? AND `time` <= ? AND `sto_id` = ?;");
    $select->execute([$_POST["time_from"], $_POST["time_to"], $_POST["sto_id"]]);

    $res = $select->fetchAll(PDO::FETCH_ASSOC);

    for ($i = 0; $i < count($res); $i++)
    {
        $res[$i]['worker_fio'] = get_worker_fio($res[$i]['worker_id']);
        $res[$i]['car_name'] = get_auto_full_name($res[$i]['car_id']);
    }

    exitFunction(["records" => $res]);
}

function get_records_by_post() {
    global $db_link;
    $select = $db_link->prepare("SELECT * FROM `car_service_records` WHERE `time` >= ? AND `time` <= ? AND `sto_id` = ? AND `post_id` = ?;");
    $select->execute([$_POST["time_from"], $_POST["time_to"], $_POST["sto_id"], $_POST["post_type"]]);

    $res = $select->fetchAll(PDO::FETCH_ASSOC);

    for ($i = 0; $i < count($res); $i++)
    {
        $res[$i]['worker_fio'] = get_worker_fio($res[$i]['worker_id']);
        $res[$i]['car_name'] = get_auto_full_name($res[$i]['car_id']);
    }

    exitFunction(["records" => $res]);
}

function update_record() {
    global $db_link;
    $update = $db_link->prepare("UPDATE `car_service_records` SET `post_id` = ?, `time` = ?, `duration` = ? WHERE `id` = ?;");
    $record = $_POST["record"];
    $update->execute([$record["post_id"], $record["time"], $record["duration"], $record["id"]]);

    exitFunction(["status" => 200]);
}

function get_worker_fio($worker_id) {
    global $db_link;
    $select = $db_link->prepare("SELECT `fio` FROM `car_service_personal` WHERE `id` = ?;");
    $select->execute([$worker_id]);

    $res = $select->fetch(PDO::FETCH_ASSOC);

    return trim($res['fio']);
}

// Функция для рекурсивного обхода дерева
function fetchNode($id) {
    global $db_link;
    $query = "SELECT id, parent, value FROM shop_tree_lists_items WHERE id = :id";
    $stmt = $db_link->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getFullCarName($startId) {
    global $db_link;

    // Начальная настройка
    $fullName = "";
    $currentNode = fetchNode($startId);

    // Построение полного имени рекурсивно
    while ($currentNode) {
        $fullName = $currentNode['value'] . ' ' . $fullName;
        $currentNode = $currentNode['parent'] ? fetchNode($currentNode['parent']) : null;
    }

    // Удаление лишнего пробела в конце строки
    return trim($fullName);
}

function get_auto_full_name($car_id) {
    global $db_link;
    $carFullName = getFullCarName($car_id);

    return $carFullName;
}

function create_record() {
    global $db_link;

    $insert = $db_link->prepare("INSERT INTO `car_service_records` (`id`, `time`, `post_id`, `sto_id`, `duration`, `worker_id`, `services_arr`, `car_id`, `color`, `default_price`, `order_id`, `user_id`, `user`) VALUES (NULL, ?, ?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)");
    $insert->execute([$_POST['time'], $_POST['post_type'], $_POST['sto_id'], 1]);

    exitFunction(["status" => 200]);
}

$_POST = json_decode(file_get_contents("php://input"), true);

if (isset($_POST["action"]) && function_exists($_POST["action"]))
    $_POST["action"]();
else
    exitFunction(["status" => 404, "message" => "Метод не определён"]);
?>