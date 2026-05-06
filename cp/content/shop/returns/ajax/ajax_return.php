<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
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

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/returns/ajax/helper.php");

if ($_POST["tech_key"] != $DP_Config->tech_key)
{
    $answer = [
        "status" => false,
        "data" => "Forbidden"
    ];
    exit(json_encode($answer));
}

//При закрытии заявки проверяем позиции на одобрение возврата по его ID и выставляем им соответствующий статус, если флаг указан
function changeItemStatusWhenComplete($return_id, $from_refund) {
	global $db_link;

	//Выбираем статусы для одобрения и отклонения возврата
	$select_resolve_status = $db_link->prepare("SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `complete_return` = 1;");
	$select_resolve_status->execute();
	$resolve_status = $select_resolve_status->fetch();
	$resolve_status = $resolve_status["id"];

	$select_reject_status = $db_link->prepare("SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `reject_return` = 1;");
	$select_reject_status->execute();
	$reject_status = $select_reject_status->fetch();
	$reject_status = $reject_status["id"];


	//Получаем все позиции по этому возврату
	$select_item = $db_link->prepare("SELECT * FROM `shop_orders_returns_items` WHERE `return_id` = ?");
	$select_item->execute([$return_id]);
	$items = $select_item->fetchAll(PDO::FETCH_ASSOC);

	$arr_resolve_items = [];
	$arr_reject_items = [];

	//Подготавливаем массивы для изменения статусов позиций
	foreach ($items as $item) {
			if ($item["return_success"] == 1 && $resolve_status > 0 && $from_refund != 0)
					$arr_resolve_items[] = $item["item_id"];

			//Выставляем статус отказа в возврате, если флаг установлен
			if ($item["return_success"] == 0 && $reject_status > 0 || $from_refund == 0)
					$arr_reject_items[] = $item["item_id"];
	}

	if (count($arr_resolve_items) > 0)
			changeStatus($arr_resolve_items, $resolve_status); //helper.php

	if (count($arr_reject_items) > 0)
			changeStatus($arr_reject_items, $reject_status); //helper.php
}

switch ($_POST["action"]) {
    case "resolve":
        $reason_query = $db_link->prepare("UPDATE `shop_orders_returns_items` SET `return_success` = 1 WHERE `shop_orders_returns_items`.`id` = ?");
        if ($reason_query->execute( array($_POST["id"]) ))
            $answer["status"] = true;
        break;
    case "reject":
        $reason_query = $db_link->prepare("UPDATE `shop_orders_returns_items` SET `return_success` = 0 WHERE `shop_orders_returns_items`.`id` = ?");
        if ($reason_query->execute( array($_POST["id"]) ))
            $answer["status"] = true;
        break;
    case "return" :

        $status_query = $db_link->prepare("SELECT `id` FROM `shop_orders_returns_statuses` WHERE `caption` = 'Закрыта'");
        $status_query->execute();

        $status_result = $status_query->fetch();

        $status_id = $status_result["id"];

        $return_update = $db_link->prepare("UPDATE `shop_orders_returns` SET `return_complete` = 1, `status_id` = ? WHERE `shop_orders_returns`.`id` = ?");
        if ($return_update->execute( array($status_id, $_POST["return_id"]) ))
        {
            $pay_query = $db_link->prepare("INSERT INTO `shop_users_accounting` (`user_id`, `time`, `income`, `amount`, `operation_code`, `active`, `pay_orders`, `tech_value_text`, `order_id`, `office_id`) VALUES (?, ?, '1', ?, '5', '1', '', '', ?, '0')");
            if ($pay_query->execute( array($_POST["user_id"], time(), $_POST["return_sum"], $_POST["order_id"]) ))
            {
				DP_User::addCashOperation('5', $_POST["return_sum"]);
                $answer["status"] = true;
            }
        }
        break;

    case "close" :
        $status_query = $db_link->prepare("SELECT `id` FROM `shop_orders_returns_statuses` WHERE `caption` = 'Закрыта'");
        $status_query->execute();

        $status_result = $status_query->fetch();

        $status_id = $status_result["id"];

        $return_update = $db_link->prepare("UPDATE `shop_orders_returns` SET `return_complete` = 1, `status_id` = ? WHERE `shop_orders_returns`.`id` = ?");
        if ($return_update->execute( array($status_id, $_POST["return_id"]) ))
        {
            $answer["status"] = true;
        }
				
        //Меняем статус позиций
        changeItemStatusWhenComplete($_POST["return_id"],$_POST["from_refund"]);

        break;
    default:
        $answer["data"] = "Действие не выбрано.";
        break;
}

exit(json_encode($answer));
?>
