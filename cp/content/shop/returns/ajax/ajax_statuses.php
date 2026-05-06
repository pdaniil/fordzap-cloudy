<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$DP_Config = new DP_Config;
try
{
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e)
{
    $result["status"] = false;
    $result["message"] = "DB connect error";
    $result["code"] = 502;
    exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

$answer = [
    "status" => false,
    "data" => ""
];

if ($_POST["tech_key"] != $DP_Config->tech_key)
{
    $answer = [
        "status" => false,
        "data" => "Forbidden"
    ];
    exit(json_encode($answer));
}

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

switch ($_POST["action"]) {
    case "select":
        $reason_query = $db_link->prepare("SELECT * FROM `shop_orders_returns_statuses`;");
        $reason_query->execute();

        $arr_reasons = [];
        while ($result_reasons = $reason_query->fetch())
        {
            $arr_reasons[] = $result_reasons;
            $answer["status"] = true;
        }

        $answer["data"] = $arr_reasons;

        break;
    case "update":
        $reason_query = $db_link->prepare("UPDATE `shop_orders_returns_statuses` SET `caption` = ?, `color` = ? WHERE `shop_orders_returns_statuses`.`id` = ?");
        if ($reason_query->execute( array(htmlentities($_POST["caption"]), $_POST["color"], $_POST["id"]) ))
            $answer["status"] = true;
        break;
    case "delete":
        $reason_query = $db_link->prepare("DELETE FROM `shop_orders_returns_statuses` WHERE `shop_orders_returns_statuses`.`id` = ?");
        if ($reason_query->execute( array($_POST["id"]) ))
            $answer["status"] = true;
        break;
    case "insert":
        $reason_query = $db_link->prepare("INSERT INTO `shop_orders_returns_statuses` (`caption`, `color`) VALUES (?, ?)");
        if ($reason_query->execute( array(htmlentities($_POST["caption"]), $_POST["color"]) ))
            $answer["status"] = true;
        break;
    case  "update_return_status" :
        $reason_query = $db_link->prepare("UPDATE `shop_orders_returns` SET `status_id` = ? WHERE `shop_orders_returns`.`id` = ?");
        if ($reason_query->execute( array($_POST["status_id"], $_POST["return_id"]) ))
            $answer["status"] = true;

        //Для отправки уведомлений
        require_once( $_SERVER["DOCUMENT_ROOT"]."/content/notifications/notify_helper.php" );


        $persons[] = array('type' => 'user_id', 'user_id' => DP_User::getUserId());
        $notify_vars["return_id"] = $_POST["return_id"];
        $notify_vars["status_name"] = $_POST["status_name"];
        $notify_vars["site_name"] = $DP_Config->site_name;

        $send_result = send_notify('return_change_status_email', $notify_vars, $persons);
        $answer["data"] = $send_result;
        break;
    default:
        $answer["data"] = "Действие не выбрано.";
        break;
}

exit(json_encode($answer));
?>
