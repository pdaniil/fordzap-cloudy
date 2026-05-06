<?php
//Скрипт для перепривязки соц сети на другого пользователя
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/social/user_social.php");

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

try {



    //Проверяем, есть ли у пользователя emal, если нет - то привязываем тот, который есть
    $result = $db_link->prepare("SELECT COUNT(*) AS `count` FROM `users` WHERE `user_id` = ? AND `email` = ''");
    $result->execute( array( DP_User::getUserId() ) );
    $count = $result->fetch();

    if ($count["count"] > 0)
    {
        if (!$query = $db_link->prepare("UPDATE `users` SET `email` = ?, `email_confirmed` = 1 WHERE `user_id` = ?")->execute( array( $_GET["email"], DP_User::getUserId() ) ))
				{
                    throw new Exception("Не удалось указать новый email.");
                }
			}

			//В начале выбираем текущие данные, чтобы снять флаг social, если это последняя соц сеть
			$SQL_select = "SELECT `user_id`, `social_id` FROM `social_user_data` WHERE `id` = ?";
			$query = $db_link->prepare($SQL_select);
			$query->execute( array( $_GET["id"] ) );

			$user_data = $query->fetch();


			$SQL_select_login_data = "SELECT COUNT(*) AS `count` FROM `social_user_data` WHERE `user_id` = ? AND `social_id` != ?;";
			$query = $db_link->prepare($SQL_select_login_data);
			$query->execute( array( $user_data["user_id"], $user_data["social_id"] ) );

			$current_count = $query->fetch();

			$count = $current_count["count"];

			//Снимаем флаг, если эта соц сеть последняя
			if ($count == 0)
            {
                $SQL_update_social_flag = "UPDATE `users` SET `social` = 0 WHERE `user_id` = ?";
                $query = $db_link->prepare($SQL_update_social_flag);
                $query->execute( array( $user_data["user_id"] ) );
            }


			$SQL_update = "UPDATE `social_user_data` SET `user_id` = ? WHERE `id` = ?";
			$query = $db_link->prepare($SQL_update);
			$awnser = $query->execute( array( DP_User::getUserId(), $_GET["id"] ) );

			if (!$awnser)
            {

                throw new Exception("Не удалось перепривязать соц сеть.");

            }

	}
catch (Exception $e)
{
    $error_message = $e->getMessage();
    ?>
    <script>
        location="/?error_message=<?php echo urlencode($error_message); ?>";
    </script>
    <?php
    exit();
}

?>
<form method="POST" action="<?php echo "https://".$_SERVER['SERVER_NAME']."/users/profile";?>" id="form">
</form>

<?php

?>

<script>
    window.onload = function(){

        document.querySelector('#form').submit();

    };
</script>
