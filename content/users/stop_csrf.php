<?php
//Универсальный скрипт защиты от CSRF. Подключается перед обработкой форм.

//Вызываем, если не прошли проверку
if( !function_exists('error_exit') )
{
	function error_exit( $error_message )
	{
		$answer = array();
		$answer['error'] = $error_message;
		$answer['message'] = $error_message;
		$answer['status'] = false;
		exit( json_encode($answer) );
	}
}
//Вспомогательная функция проверки "Начинается ли нужная строка с определенной подстроки"
if ( !function_exists('str_starts_with') ) 
{
	function str_starts_with($str, $start) 
	{
		return (@substr_compare($str, $start, 0, strlen($start))==0);
	}
}



//Должно быть соединение с БД
if( !isset($db_link) )
{
	error_exit('Ошибка! CSRF 0');
}


//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );


//Должен быть передан код в форме
if( !isset( $_GET["csrf_guard_key"] ) && !isset( $_POST["csrf_guard_key"] ) )
{
	error_exit('Ошибка! CSRF 1');
}
$csrf_guard_key = '';//Сюда записываем код из формы, полученный от пользователя
if( isset( $_GET["csrf_guard_key"] ) )
{
	$csrf_guard_key = $_GET["csrf_guard_key"];
}
else if( isset( $_POST["csrf_guard_key"] ) )
{
	$csrf_guard_key = $_POST["csrf_guard_key"];
}
else
{
	error_exit('Ошибка! CSRF 2');
}
if( empty($csrf_guard_key) )
{
	error_exit('Ошибка! CSRF 3');
}


//ВАЖНО! У админа и у простого пользователя сессии разные. Поэтому, нужно определить, откуда идет запрос. Если со страниц панели управления, то, проверяем сессию админа, если из клиентской части - значит сессию простого пользователя.
$csrf_check_admin = false;//По умолчанию
if( str_starts_with( $_SERVER['HTTP_REFERER'] , $DP_Config->domain_path.$DP_Config->backend_dir."/" ) )
{
	$csrf_check_admin = true;//Запрос идет из админки
}

if( $csrf_check_admin )
{
	$user_session = DP_User::getAdminSession();
}
else
{
	$user_session = DP_User::getUserSession();
}

if( $user_session == false || !is_array($user_session) )
{
	error_exit('Ошибка! CSRF 3.1');
}

//ПРОВЕРКА
if( $user_session["csrf_guard_key"] != $csrf_guard_key )
{
	error_exit('Ошибка! CSRF 4');
}
?>