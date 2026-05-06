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

    if ($_POST["tech_key"] != $DP_Config->tech_key)
    {
        $answer = [
            "status" => false,
            "data" => "Forbidden"
        ];
        exit(json_encode($answer));
    }

    switch ($_POST["action"]) {
        case "select":
                $reason_query = $db_link->prepare("SELECT * FROM `shop_orders_returns_reasons`;");
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
                $reason_query = $db_link->prepare("UPDATE `shop_orders_returns_reasons` SET `caption` = ? WHERE `shop_orders_returns_reasons`.`id` = ?");
                if ($reason_query->execute( array(htmlentities($_POST["caption"]), $_POST["id"]) ))
                    $answer["status"] = true;
            break;
        case "delete":
                $reason_query = $db_link->prepare("DELETE FROM `shop_orders_returns_reasons` WHERE `shop_orders_returns_reasons`.`id` = ?");
                if ($reason_query->execute( array($_POST["id"]) ))
                    $answer["status"] = true;
            break;
        case "insert":
                $reason_query = $db_link->prepare("INSERT INTO `shop_orders_returns_reasons` (`caption`) VALUES (?)");
                if ($reason_query->execute( array(htmlentities($_POST["caption"])) ))
                    $answer["status"] = true;
            break;
        default:
            $answer["data"] = "Действие не выбрано.";
            break;
    }

    exit(json_encode($answer));
?>
