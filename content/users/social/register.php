<?php

	//Конфигурация
	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/social/user_social.php");
	
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
	
	
	//Функция привязки соцй сети к существующему аккаунту
	function add_social($user_id, $user)
	{
		global $db_link;
		//Если пользователь авторизован - то просто заносим данные о соц сети
			
			//Проверяем, есть ли email/phone у этого пользователя
			$SQL_select_user_data = "SELECT `phone`, `email` FROM `users` WHERE `user_id` = ?";
			$query = $db_link->prepare($SQL_select_user_data);
			$query->execute( array( $user_id ) );
			
			$result = $query->fetch();
			
			//Подготавливаем запрос
			$SQL_update = "UPDATE `users` SET `social` = 1";
			$UPDATE_condition = "";
			$sql_update_end = " WHERE `user_id` = ?;";
			
			//Массив параметров
			$bind_params = [];
			
			
			//Если телефон или email - пустые поля, то заносим данные из соц сети в них, в противном случае - не трогаем, так же поднимаем флаг (таблица users)
			if ($result["phone"] == '')
			{
				$UPDATE_condition .= ", `phone` = ?";
				$bind_params[] = $user->phone;
			}
			if ($result["email"] == '')
			{
				$UPDATE_condition .= ", `email` = ?";
				$bind_params[] = $user->email;
			}
			
			$bind_params[] = $user_id;
			
			$SQL_finally = $SQL_update.$UPDATE_condition.$sql_update_end;
			
			$query = $db_link->prepare($SQL_finally);
			
			
			
			$exp = $query->execute( $bind_params );
			
			if (!$exp)
			{
				throw new Exception("Не удалось привязать соц сеть к профилю пользователя. (err reg 1)");
			}
			
			//Теперь создаём строку в таблице social_user_data
			if ( $db_link->prepare('INSERT INTO `social_user_data` (`social_user_id`, `user_id`, `token`, `social_id`) VALUES (?, ?, ?, ?);')->execute( array( $user->id, $user_id, $user->token, $user->social_id) )!= true)
			{
				throw new Exception("Ошибка записи данных соц сети пользователя. (err reg 2)");
			}
			
			
	}
	
	
	
	//Получаем id социальной сети из таблицы social
	$sql = $db_link->prepare("SELECT `id` FROM `social` WHERE `social_name` = ?");
	$sql->execute(array($_POST["social_name"]));
	$result = $sql->fetch();
	
	
	
	//Данные для регистрации должны передаваться методом POST
	
	$token 			= $_POST["token"];
	$phone 			= $_POST["phone"];
	$email 			= $_POST["email"];
	$first_name 	= $_POST["name"];
	$last_name 		= $_POST["surname"];
	$social_id		= $result["id"];
	$id 			= $_POST["id"];
	$check_phone	= 0;
	$chek_email		= 0;
	
	//Если  телефон или email были переданы, то сразу ставим флаг о пройденной верефикации
	if ($_POST["phone"] != NULL)
	{
		$check_phone = 1;
	}
	
	if ($_POST["email"] != NULL)
	{
		$check_email = 1;
	}
	
	
	//Создаём объект класса UserSocial
	$user = new UserSocial($id, $token, $email, $phone, $first_name, $last_name, $social_id);
	
	//var_dump($user);
	//exit();
	
	try 
	{
		//Старт транзакции
		if( ! $db_link->beginTransaction()  )
		{
			throw new Exception("Не удалось стартовать транзакцию");
		}
	
		// -------------------------------------------------------------------------------------------
		
		//Проверка IP-адреса - предотвращения регистраций роботами и хулиганами - блокируем ну сутки
		$ip = $_SERVER["REMOTE_ADDR"];
		if($ip == "" || $ip == NULL)
		{
			throw new Exception("Ошибка 2.1");
		}
		$time_day = time() - 86400;//Сутки назад

		$ip_query = $db_link->prepare('SELECT COUNT(*) FROM `users` WHERE `ip_address` = ? AND `time_registered` > ? AND `email_confirmed` = ? AND `phone_confirmed` = ?;');
		$ip_query->execute( array($ip, $time_day, 0, 0) );
		if( $ip_query->fetchColumn() > 0 )
		{
			throw new Exception("Попробуйте зарегистрироваться позже");
		}
		// ----------------------
		
		
		
		$user_id = DP_User::getUserId();
		//Проверка, что пользователь авторизован
		if($user_id != 0)
		{	//Пользователь авторизован - привязываем соц сеть
			add_social( $user_id, $user);
		}
		//Пользователь не авторизован - проверяем, есть ли email/phone уже в базе
		else 
		{
			
			$SQL_select_user_data = "SELECT `user_id` FROM `users` WHERE (`email` = ? AND `email` != '') OR (`phone` = ? and `phone` != '');";
			$query = $db_link->prepare($SQL_select_user_data);
			$query->execute( array( $user->email, $user->phone ) );
			$result = $query->fetch();
			
			//Если такие email/phone уже есть в базе - то просто привязываем соц сеть к этому аккаунту
			if ($result["user_id"] != '' )
			{
				add_social($result["user_id"], $user);
			}
			
			//Регистрируем данного пользователя с нуля
			else
			{
		
				//Добавление строки в таблицу users
		
				if($db_link->prepare('INSERT INTO `users` (`reg_variant`,  `time_registered`, `unlocked`,  `email`, `phone`, `phone_confirmed`, `email_confirmed`, `social`) VALUES (?, ?, ?,  ?, ?, ?, ?, ?);')->execute( array( 1,  time(), 1, $user->email, $user->phone, $check_phone, $check_email, 1 ) ) != true)
				{
					throw new Exception("Ошибка создания учетной записи пользователя. ");
				}
				else//Запись добавлена - узнаем user_id добавленного пользователя
				{
					$user_id = $db_link->lastInsertId();
				}
				// -------------------
		
		
				//Добавление строки в таблицу users_profiles
		
				//Получаем дополнительные регистрационные поля
				$reg_fields_query = $db_link->prepare('SELECT * FROM `reg_fields` WHERE `main_flag` = 0;');
				$reg_fields_query->execute();
				while( $reg_field_record = $reg_fields_query->fetch() )
				{
					$show_for = json_decode($reg_field_record["show_for"], true);
		
					//Есть ли данное поле в этом Регистрационном Варианте показано
					if(array_search($_POST["reg_variant"], $show_for) !== false)
					{
						if( $db_link->prepare('INSERT INTO `users_profiles` (`user_id`, `data_key`, `data_value`) VALUES (?, ?, ?);')->execute( array($user_id, $reg_field_record["name"], htmlentities($_POST[$reg_field_record["name"]])) ) != true)
						{
							throw new Exception("Ошибка записи профиля пользователя");
						}
					}
				}
				// -------------------
		
				// ПРИВЯЗКА ПОЛЬЗОВАТЕЛЯ К ГРУППЕ РЕГИСТРАЦИИ
				$for_registrated_group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `for_registrated` = 1;');
				$for_registrated_group_query->execute();
				$for_registrated_group_record = $for_registrated_group_query->fetch();
				if( $db_link->prepare('INSERT INTO `users_groups_bind` (`user_id`, `group_id`) VALUES (?, ?);')->execute( array($user_id, $for_registrated_group_record["id"]) ) != true)
				{
					throw new Exception("Ошибка добавления пользователя в группу");
				}

				// -------------------------------------------------------------------------------------------
		
				// Добавление записи в таблицу social_user_data
				if ( $db_link->prepare('INSERT INTO `social_user_data` (`social_user_id`, `user_id`, `token`, `social_id`) VALUES (?, ?, ?, ?);')->execute( array( $user->id, (int) $user_id, $user->token, (int)$social_id) )!= true)
				{
				throw new Exception("Ошибка записи данных соц сети пользователя");
				}
			}
		}
		
	}
	
	catch (Exception $e)
	{
		$db_link->rollBack();
		$error_message = $e->getMessage();
		?>
		<script>
			location="/?error_message=<?php echo urlencode($error_message); ?>";
		</script>
		<?php
		exit();
	}
	
	//Дошли до сюда, значит выполнено ОК
	$db_link->commit();//Коммитим все изменения и закрываем транзакцию
	$success_message = "Вы успешно прошли регистрацию! После авторизации проверьте данные в профиле, а также установите пароль, email и телефон. Это нужно для того, чтобы Вы получали уведомления о смене статуса Вашего заказа.";
	
	//Перенаправляем на скрипт-обработчик выбранной соц сети для прохождения авторизации. 
	header("Location: https://".$_SERVER['SERVER_NAME'].$_POST["uri_redirect"]);
	
?>