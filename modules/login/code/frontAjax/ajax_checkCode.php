<?php
ini_set('display_errors', 0);

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

    require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
    $user_session = DP_User::getUserSession();

    $_POST = json_decode(file_get_contents("php://input"), true);

    // -------------------------------------------------------------------------------
    //Защита от CSRF-атак
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
    // -------------------------------------------------------------------------------

    $_POST = json_decode(file_get_contents("php://input"), true);

    $session_data = json_decode($user_session["data"], true);

    $faCode = $user_session["2fa_code"];
    $faAttempts = $user_session["2fa_attempts"];

    if ($faAttempts < 1)
        exit(json_encode(["status" => 501, "message" => "Количество попыток исчерпано"]));
    else if ($faCode == $_POST["code"])
        if ($session_data["expireFaCode"] < time())
            exit(json_encode(["status" => 501, "message" => "Истекло время жизни кода. Отправьте код заново."]));
        else
            exit(json_encode(["status" => 200]));

    else
    {
        $faAttempts = $faAttempts - 1;
        $sqlUpdateAttempt = $db_link->prepare("UPDATE `sessions` SET `2fa_attempts` = ? WHERE `session` = ?;");
        $sqlUpdateAttempt->execute([$faAttempts, $user_session["session"]]);

        exit(json_encode(["status" => 501, "message" => "Не правильный код. Осталось попыток: " . $faAttempts . "."]));
    }

    exit(json_encode(["status" => 200]));
?>