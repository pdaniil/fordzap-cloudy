<?php
	//Скрипт для получения данных пользователя facebook и перенаправления на аторизацию/регистрацию
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
	
	$user_session = DP_User::getUserSession(); //csrf
	
try {	
	//Id социальной сети
	$SQL = "SELECT `id` FROM `social` WHERE `social_name` = ?";
	$query = $db_link->prepare($SQL);
	$query->execute( array ("facebook.com"));
	if (!$result = $query->fetch())
	{
		throw new Exception("Ошибка получения id соц сети.");
	}
	
	$social_id = $result["id"];
				
	//Получаем настройки социальной сети			
	$SQL_social_options = "SELECT * FROM `social_options` WHERE `id_social` = ?";
	$query = $db_link->prepare($SQL_social_options);
	$query->execute( array ($social_id));
	if (!$result = $query->fetch())
	{
		throw new Exception("Не удалось получить настройки социальной сети.");
	}
	
	$client_id 		= $result["client_id"];
	
	$redirect_uri 	= "https://".$_SERVER['SERVER_NAME'].$result["uri_redirect"];
	
	$client_secret	= $result["secret_code"];
	

	 
	//Запрос на получение кода
	$link_auth = 'https://www.facebook.com/v11.0/dialog/oauth?client_id='.$client_id.'&redirect_uri='.$redirect_uri.'&scope=email';
	
	
	
	if (!isset($_GET["code"]))
	{
		header("Location: ".$link_auth);	
	}
	
	//------------------------------
	
	
	//Делаем запрос на получение токена и id клиента в соц сети
	$params = array(
		'client_id'     => $client_id,
		'client_secret' => $client_secret,
		'redirect_uri'  => $redirect_uri,
		'code'          => $_GET['code']
	);
	
	// Получение access_token
	$data = file_get_contents('https://graph.facebook.com/oauth/access_token?' . urldecode(http_build_query($params)));
	$data = json_decode($data, true);
 
	if (empty($data['access_token'])) 
	{
		throw new Exception("Ошибка получения токена.");
	}
	$params = array(
		'access_token' => $data['access_token'],
		'fields'       => 'id,email,first_name,last_name'
	);
 
	// Получение данных пользователя
	$info = file_get_contents('https://graph.facebook.com/me?' . urldecode(http_build_query($params)));
	$info = json_decode($info, true);
 
	
	$token = $data["access_token"];
	$user_id = $info["id"];
	$email = $info["email"];
	$first_name = $info["first_name"];
	$last_name = $info["last_name"];
	
	
	
	
	
	//Ищем клиента с подходящим id пользователя соц сети и id соц сети
	$SQL_user_social = "SELECT * FROM `social_user_data` WHERE `social_user_id` = ? AND `social_id` = ?";
	$query = $db_link->prepare($SQL_user_social);
	$query->execute( array( $user_id, $social_id ) );
	$result = $query->fetch();
	
	//Нашли такого пользователя, перенаправляем на авторизацию
	if ($result != false)
	{
		//Если пользователь авторизован - то перепривязываем соц сеть
		if (DP_User::getUserId() != 0)
		{
		?>   <script>
				let awnser = confirm("Данная соц сеть привязанна к другому аккаунту.\nЖелаете перепривязать её к своему аккаунту?");
				
				if (!awnser)
				{
					location = 'https://<?php echo $_SERVER["SERVER_NAME"] ?>/users/profile';
			
				}
				else
				{
					location = 'https://<?php echo $_SERVER["SERVER_NAME"] ?>/content/users/social/update_social.php?id=<?php echo $result["id"];?>&email=<?php echo $email; ?>;';
				}
			 </script>
		<?php
			
		}
		else
		{
		//Обновляем токен в записи social_user_data
		$SQL_update_token = "UPDATE `social_user_data` SET `token`= ? WHERE `social_user_id` = ? AND `social_id` = ?;";
		if (!$query = $db_link->prepare($SQL_update_token)->execute( ( array( $token, $user_id, $social_id ) )))
		{
			throw new Exciption("Не удалось обновить токен пользователя.");
		}
		?>
		
		<form method="POST" action="<?php echo "https://".$_SERVER['SERVER_NAME'];?>" id="form">
			<input type="hidden" name="authentication" value="true">
			<input type="hidden" name="auth_contact_type" value="social">
			<input type="hidden" name="social_name" value="facebook.com">
			<input type="hidden" name="token" value="<?php echo $token; ?>">
			<input type="hidden" name="id" value="<?php echo $user_id;?>">
			<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"];?>"> <?php //csrf ?>
		</form>
		
		<?php
		}
	}
	
	//Такой пользователь найден не был, перенаправляем на регистрацию
	else
	{
		//Если такой пользователь авторизован - но при этом данные о аккаунтах с данной соц сетью не найдены, то просто привязываем её
		if (DP_User::getUserId() != 0)
		{
			//Проверяем, есть ли у пользователя emal, если нет - то привязываем тот, который есть
			$result = $db_link->prepare("SELECT COUNT(*) AS `count` FROM `users` WHERE `user_id` = ? AND `email` = ''");
			$result->execute( array( DP_User::getUserId() ) );
			$count = $result->fetch();
			
			if ($count["count"] > 0)
			{
				if (!$query = $db_link->prepare("UPDATE `users` SET `email` = ?, `email_confirmed` = 1 WHERE `user_id` = ?")->execute( array( $email, DP_User::getUserId() ) ))
				{
					throw new Exception("Не удалось указать новый email.");
				}
			}
			
			
			$SQL_insert = "INSERT INTO `social_user_data`(`social_user_id`, `user_id`, `token`, `social_id`) VALUES (?,?,?,?);";
			$query = $db_link->prepare($SQL_insert);
			
			if (!$query = $query->execute( array( $user_id, DP_User::getUserId(), $token, $social_id  ) ))
			{
				throw new Exciption("Не удалось привязать соц сеть.");
			}
			
			header("Location: https://".$_SERVER["SERVER_NAME"]."/users/profile");
		
			
		}
		else
		{
		//Получаем данные пользователя по текущему токену и id пользователя в соц сети
		?>
		
		<form method="POST" action="register.php" id="form">
			<input type="hidden" name="id" value="<?php echo $user_id;?>">
			<input type="hidden" name="name" value="<?php echo $first_name;?>">
			<input type="hidden" name="surname" value="<?php echo $last_name;?>">
			<input type="hidden" name="token" value="<?php echo $token; ?>">
			<input type="hidden" name="email" value="<?php echo $email; ?>">
			<input type="hidden" name="social_name" value="facebook.com">
			<input type="hidden" name="reg_variant" value="1">
			<input type="hidden" name="uri_redirect" value="<?php echo "/content/users/social/facebook.php";?>"><!-- Нужно для редиректа на страницу авторизации сразу после регистрации. -->
		</form>
		
		<?php
		}
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

<script>
	

	history.pushState("", document.title, window.location.pathname);
	window.onload = function(){
		
		document.querySelector('#form').submit();

	};
</script>