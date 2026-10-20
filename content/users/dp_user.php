<?php

/**
 * Класс пользователя. Предназначен для получения данных о текущем авторизованном пользователе.
 */

class DP_User
{
	/*Получение ID пользователя*/
	public static function getUserId()
	{
		global $DP_Config;
		global $db_link;

		$session = null;
		if (isset($_COOKIE["session"])) {
			$session = $_COOKIE["session"];
		}
		$u_id = null;
		if (isset($_COOKIE["u_id"])) {
			$u_id = $_COOKIE["u_id"];
		}

		$check_authentication_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `session`=? AND `user_id`=?;');
		$check_authentication_query->execute(array($session, $u_id));
		$session_count = $check_authentication_query->fetchColumn();

		if ($session_count == 0) {
			return 0;
		} else if ($session_count == 1) {
			return $u_id;
		} else {
			exit();
		}
	} //public static function getUserId()


	// ---------------------------------------------------------------------------------------------------

	/*Получение сессии пользователя (который сейчас сам работает)*/
	public static function getUserSession()
	{
		global $db_link;

		$session = null;
		if (isset($_COOKIE["session"])) {
			$session = $_COOKIE["session"];
		}
		$u_id = null;
		if (isset($_COOKIE["u_id"])) {
			$u_id = $_COOKIE["u_id"];
		}

		$session_query = $db_link->prepare("SELECT * FROM `sessions` WHERE `session`=? AND `user_id`=?;");
		$session_query->execute(array($session, $u_id));
		$session_record = $session_query->fetch();

		return $session_record;
	}

	// ---------------------------------------------------------------------------------------------------

	/*Получение профиля пользователя*/
	public static function getUserGroup($user_id = null)
	{
		$user_group = null;

		global $DP_Config;
		global $db_link;

		if (empty($user_id)) {
			$user_id = DP_User::getUserId(); //Получаем ID пользователя
		}

		if ($user_id == 0) //Если пользователь не авторизован - ставим группу для гостей
		{

			$guest_group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `for_guests`=?;');
			$guest_group_query->execute(array(1));
			$guest_group_record = $guest_group_query->fetch();
			$user_group = $guest_group_record["id"]; // <-- ЗАПИСЬ ЗНАЧЕНИЯ
			return $user_group;
		}

		//Получаем список групп пользователя
		$groups_query = $db_link->prepare('SELECT `group_id` FROM `users_groups_bind` WHERE `user_id` = ?;');
		$groups_query->execute(array($user_id));
		$group_record = $groups_query->fetch();
		$user_group = $group_record["group_id"]; // <-- ЗАПИСЬ ЗНАЧЕНИЯ

		return $user_group;
	} //public static function getUserProfile()

	// ---------------------------------------------------------------------------------------------------

	/*Получение профиля пользователя (который сейчас сам работает)*/
	public static function getUserProfile()
	{
		$profile = array(); //Ассоциативный массив с данными пользователя

		global $DP_Config;
		global $db_link;

		$user_id = DP_User::getUserId(); //Получаем ID пользователя

		if ($user_id == 0) //Если пользователь не авторизован - ставим группу для гостей
		{
			$profile["user_id"] = 0; // <-- ЗАПИСЬ ЗНАЧЕНИЯ

			$guest_group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `for_guests`=?;');
			$guest_group_query->execute(array(1));
			$guest_group_record = $guest_group_query->fetch();
			$profile["groups"] = array($guest_group_record["id"]); // <-- ЗАПИСЬ ЗНАЧЕНИЯ
			return $profile;
		}

		//Формируем массив с данными по пользователю:
		//ID пользователя:
		$profile["user_id"] = $user_id; // <-- ЗАПИСЬ ЗНАЧЕНИЯ

		//Поля из users:
		$user_query = $db_link->prepare('SELECT * FROM `users` WHERE `user_id`=?;');
		$user_query->execute(array($user_id));
		$user_record = $user_query->fetch();
		$need_cols = array('email', 'email_confirmed', 'email_code_send_lock_expired', 'phone', 'phone_confirmed', 'phone_code_send_lock_expired', 'reg_variant'); //Массив колонок таблицы users, которые нужно указать в ответе
		for ($i = 0; $i < count($need_cols); $i++) {
			$profile[$need_cols[$i]] = $user_record[$need_cols[$i]]; // <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}

		//По полям профиля:
		$profile_query = $db_link->prepare('SELECT * FROM `users_profiles` WHERE `user_id`=?;');
		$profile_query->execute(array($user_id));
		while ($profile_record = $profile_query->fetch()) {
			$profile[$profile_record["data_key"]] = $profile_record["data_value"]; // <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}

		//Получаем список групп пользователя
		$profile["groups"] = array();
		$groups_query = $db_link->prepare('SELECT * FROM `users_groups_bind` WHERE `user_id` = ?;');
		$groups_query->execute(array($user_id));
		while ($group_record = $groups_query->fetch()) {
			array_push($profile["groups"], $group_record["group_id"]);
		}

		return $profile;
	} //public static function getUserProfile()

	// ---------------------------------------------------------------------------------------------------


	/*Получение профиля пользователя по его ID*/
	public static function getUserProfileById($user_id)
	{
		$profile = array(); //Ассоциативный массив с данными пользователя

		global $DP_Config;
		global $db_link;

		if ($user_id == 0) //Если пользователь не авторизован - ставим группу для гостей
		{
			$profile["user_id"] = 0; // <-- ЗАПИСЬ ЗНАЧЕНИЯ

			$guest_group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `for_guests` = ?;');
			$guest_group_query->execute(array(1));
			$guest_group_record = $guest_group_query->fetch();

			$profile["groups"] = array($guest_group_record["id"]); // <-- ЗАПИСЬ ЗНАЧЕНИЯ
			return $profile;
		}

		//Формируем массив с данными по пользователю:
		//ID пользователя:
		$profile["user_id"] = $user_id; // <-- ЗАПИСЬ ЗНАЧЕНИЯ

		//Поля из users:
		$user_query = $db_link->prepare('SELECT * FROM `users` WHERE `user_id`=?;');
		$user_query->execute(array($user_id));
		$user_record = $user_query->fetch();
		$need_cols = array('email', 'email_confirmed', 'email_code_send_lock_expired', 'phone', 'phone_confirmed', 'phone_code_send_lock_expired', 'reg_variant'); //Массив колонок таблицы users, которые нужно указать в ответе
		for ($i = 0; $i < count($need_cols); $i++) {
			$profile[$need_cols[$i]] = $user_record[$need_cols[$i]]; // <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}

		//По полям профиля:
		$profile_query = $db_link->prepare('SELECT * FROM `users_profiles` WHERE `user_id`= ?;');
		$profile_query->execute(array($user_id));
		while ($profile_record = $profile_query->fetch()) {
			$profile[$profile_record["data_key"]] = $profile_record["data_value"]; // <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}

		//Получаем список групп пользователя
		$profile["groups"] = array();
		$groups_query = $db_link->prepare('SELECT * FROM `users_groups_bind` WHERE `user_id` = ?;');
		$groups_query->execute(array($user_id));
		while ($group_record = $groups_query->fetch()) {
			array_push($profile["groups"], $group_record["group_id"]);
		}

		return $profile;
	} //public static function getUserProfileById()



	// ---------------------------------------------------------------------------------------------------


	/*Является ли пользователь администратором*/
	public static function isAdmin()
	{
		global $DP_Config;
		global $db_link;

		$admin_session = null;
		if (isset($_COOKIE["admin_session"])) {
			$admin_session = $_COOKIE["admin_session"];
		}
		$admin_u_id = null;
		if (isset($_COOKIE["admin_u_id"])) {
			$admin_u_id = $_COOKIE["admin_u_id"];
		}

		$check_authentication_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `session`=? AND `type` = ? AND `user_id` = ?;');
		$check_authentication_query->execute(array($admin_session, 1, $admin_u_id));

		$sessions_count = $check_authentication_query->fetchColumn();

		if ($sessions_count == 0) {
			return 0;
		} else if ($sessions_count == 1) {
			return 1;
		} else {
			exit();
		}
	} //public static function getUserId()

	// ---------------------------------------------------------------------------------------------------

	/*Получение ID администратора*/
	public static function getAdminId()
	{
		global $DP_Config;
		global $db_link;

		$admin_session = null;
		if (isset($_COOKIE["admin_session"])) {
			$admin_session = $_COOKIE["admin_session"];
		}
		$admin_u_id = null;
		if (isset($_COOKIE["admin_u_id"])) {
			$admin_u_id = $_COOKIE["admin_u_id"];
		}


		//Сначала получаем количество сессий
		$check_authentication_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `session`=? AND `type` = ? AND `user_id` = ?;');
		$check_authentication_query->execute(array($admin_session, 1, $admin_u_id));

		$sessions_count = $check_authentication_query->fetchColumn();

		if ($sessions_count == 0) {
			return 0; //Сессий нет, это не админ
		} else if ($sessions_count != 1) {
			exit(); //Сессий не одна - такого быть не должно
		}

		return $admin_u_id;
	} //public static function getAdminId()



	// ---------------------------------------------------------------------------------------------------



	/*Получение профиля администратора*/
	public static function getAdminProfile()
	{
		$profile = array(); //Ассоциативный массив с данными администратора

		global $DP_Config;
		global $db_link;

		$user_id = DP_User::getAdminId(); //Получаем ID администратора

		if ($user_id == 0) //Если пользователь не авторизован
		{
			return false;
		}

		//Формируем массив с данными по пользователю:
		//ID пользователя:
		$profile["user_id"] = $user_id; // <-- ЗАПИСЬ ЗНАЧЕНИЯ

		//Поля из users:
		$user_query = $db_link->prepare('SELECT * FROM `users` WHERE `user_id`=?;');
		$user_query->execute(array($user_id));
		$user_record = $user_query->fetch();
		$need_cols = array('email', 'email_confirmed', 'email_code_send_lock_expired', 'phone', 'phone_confirmed', 'phone_code_send_lock_expired', 'reg_variant'); //Массив колонок таблицы users, которые нужно указать в ответе
		for ($i = 0; $i < count($need_cols); $i++) {
			$profile[$need_cols[$i]] = $user_record[$need_cols[$i]]; // <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}

		//По полям профиля:
		$profile_query = $db_link->prepare('SELECT * FROM `users_profiles` WHERE `user_id`= ?;');
		$profile_query->execute(array($user_id));
		while ($profile_record = $profile_query->fetch()) {
			$profile[$profile_record["data_key"]] = $profile_record["data_value"]; // <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}

		//Получаем список групп пользователя
		$profile["groups"] = array();
		$groups_query = $db_link->prepare('SELECT * FROM `users_groups_bind` WHERE `user_id` = ?;');
		$groups_query->execute(array($user_id));
		while ($group_record = $groups_query->fetch()) {
			array_push($profile["groups"], $group_record["group_id"]);
		}

		return $profile;
	} //public static function getAdminProfile()

	// ---------------------------------------------------------------------------------------------------

	/*Получение сессии администратора*/
	public static function getAdminSession()
	{
		global $db_link;

		$session = null;
		if (isset($_COOKIE["admin_session"])) {
			$session = $_COOKIE["admin_session"];
		}
		$u_id = null;
		if (isset($_COOKIE["admin_u_id"])) {
			$u_id = $_COOKIE["admin_u_id"];
		}

		$session_query = $db_link->prepare("SELECT * FROM `sessions` WHERE `session`=? AND `user_id`=? AND `type` = ?;");
		$session_query->execute(array($session, $u_id, 1));
		$session_record = $session_query->fetch();

		return $session_record;
	}


	// ---------------------------------------------------------------------------------------------------

	//Проверка наличия сессии 2fa для администратора
	public static function is2FASessionAdmin()
	{
		global $DP_Config;
		global $db_link;


		$user_id = DP_User::getAdminId(); //Получаем ID администратора
		if ($user_id == 0) //Если пользователь не авторизован
		{
			return false;
		}

		//Нет сессии в куки
		if (empty($_COOKIE["2fa"])) {
			return false;
		}

		$check_2fa_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `user_id` = ? AND `2fa_session` = ?;');
		$check_2fa_query->execute(array($user_id, $_COOKIE["2fa"]));


		if ($check_2fa_query->fetchColumn() == 0) {
			return false;
		} else //Сессия 2FA есть
		{
			return true;
		}
	}

	// ---------------------------------------------------------------------------------------------------

	/*Метод получения ВСЕХ пользовательских настроек (из таблицы users_options)*/
	public static function get_user_options()
	{
		global $DP_Config;
		global $db_link;

		//Пользовательские настройки привязываются к зарегистрированному пользователю по user_id и для незарегистрированного пользователя по session_id
		$user_id = DP_User::getUserId(); //Получаем ID пользователя
		$session = DP_User::getUserSession();
		if ($session == false) {
			//Это может быть, если пользователь удалил куки session
			return false;
		}
		$session_id = $session["id"];

		$users_options = array();
		$users_options_query = $db_link->prepare("SELECT * FROM `users_options` WHERE `user_id` = ? AND `session_id` = ?;");
		$users_options_query->execute(array($user_id, $session_id));
		while ($option = $users_options_query->fetch()) {
			$users_options[$option["data_key"]] = $option["data_value"];
		}

		return $users_options;
	}

	// ---------------------------------------------------------------------------------------------------

	/*Метод записи пользовательской настройки (в таблицу users_options)*/
	public static function set_user_option($key, $value)
	{
		global $DP_Config;
		global $db_link;

		//Пользовательские настройки привязываются к зарегистрированному пользователю по user_id и для незарегистрированного пользователя по session_id
		$user_id = DP_User::getUserId(); //Получаем ID пользователя
		$session = DP_User::getUserSession();
		if ($session == false) {
			//Это может быть, если пользователь удалил куки session
			return false;
		}
		$session_id = $session["id"];


		//Сначала проверяем, установлено ли уже значение для данной настройки
		$check_options_exists_query = $db_link->prepare("SELECT COUNT(*) FROM `users_options` WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?;");
		$check_options_exists_query->execute(array($user_id, $session_id, $key));
		$check_options_exists = $check_options_exists_query->fetchColumn();
		if ($check_options_exists == 0) {
			return $db_link->prepare("INSERT INTO `users_options` (`user_id`, `session_id`, `data_key`, `data_value`) VALUES (?,?,?,?);")->execute(array($user_id, $session_id, $key, $value));
		} else if ($check_options_exists == 1) {
			return $db_link->prepare("UPDATE `users_options` SET `data_value` = ? WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?;")->execute(array($value, $user_id, $session_id, $key));
		} else {
			return false;
		}
	}

	// ---------------------------------------------------------------------------------------------------

	/*Метод удаления пользовательской настройки (из таблицы users_options)*/
	public static function delete_user_option($key)
	{
		global $DP_Config;
		global $db_link;

		//Пользовательские настройки привязываются к зарегистрированному пользователю по user_id и для незарегистрированного пользователя по session_id
		$user_id = DP_User::getUserId(); //Получаем ID пользователя
		$session = DP_User::getUserSession();
		if ($session == false) {
			//Это может быть, если пользователь удалил куки session
			return false;
		}
		$session_id = $session["id"];


		//Сначала проверяем, установлено ли уже значение для данной настройки
		$check_options_exists_query = $db_link->prepare("SELECT COUNT(*) FROM `users_options` WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?;");
		$check_options_exists_query->execute(array($user_id, $session_id, $key));
		$check_options_exists = $check_options_exists_query->fetchColumn();
		if ($check_options_exists == 1) {
			return $db_link->prepare("DELETE FROM `users_options` WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?")->execute(array($user_id, $session_id, $key));
		}

		return true; //Нечего удалять
	}

	// ---------------------------------------------------------------------------------------------------

	/*Метод получения значения определенной настройки пользователя по ключу (из таблицы users_options)*/
	public static function get_user_option_by_key($key)
	{
		global $DP_Config;
		global $db_link;

		//Пользовательские настройки привязываются к зарегистрированному пользователю по user_id и для незарегистрированного пользователя по session_id
		$user_id = DP_User::getUserId(); //Получаем ID пользователя
		$session = DP_User::getUserSession();
		if ($session == false) {
			//Это может быть, если пользователь удалил куки session
			return false;
		}
		$session_id = $session["id"];


		$users_options_query = $db_link->prepare("SELECT * FROM `users_options` WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?;");
		$users_options_query->execute(array($user_id, $session_id, $key));
		$option = $users_options_query->fetch();
		if ($option == false) {
			return false; //Означает, что данная опция не установлена для пользователя
		} else {
			return $option["data_value"];
		}
	}

	// ---------------------------------------------------------------------------------------------------

	/*Проверка профиля на принадлежность к группе Администратор*/
	public static function isAdminGroup()
	{
		$profile = array(); //Ассоциативный массив с данными пользователя

		global $DP_Config;
		global $db_link;

		$user_id = DP_User::getUserId(); //Получаем ID пользователя

		if ($user_id == 0) //Если пользователь не авторизован - ставим группу для гостей
		{
			return false;
		}

		//Формируем массив с данными по пользователю:
		//ID пользователя:
		$profile["user_id"] = $user_id; // <-- ЗАПИСЬ ЗНАЧЕНИЯ

		//Группа администраторов:
		$group_query = $db_link->prepare('SELECT `id` FROM `groups` WHERE `value` LIKE \'%Администратор%\' ;');
		$group_query->execute();
		$group_admin_id = $group_query->fetchColumn();

		//По полям профиля:
		$profile_query = $db_link->prepare('SELECT COUNT(*) FROM `users_groups_bind` WHERE `user_id`=? AND `group_id` = ?;');
		$profile_query->execute(array($user_id, $group_admin_id));

		if ($profile_query->fetchColumn() > 0) {

			return true;
		}

		return false;
	} //public static function getUserProfile()


	/*Проверка профиля на принадлежность к группе Бэкэнд*/
	public static function isBackendGroup()
	{
		$profile = array(); //Ассоциативный массив с данными пользователя

		global $DP_Config;
		global $db_link;

		$user_id = DP_User::getUserId(); //Получаем ID пользователя

		if ($user_id == 0) //Если пользователь не авторизован - ставим группу для гостей
		{
			return false;
		}

		//Формируем массив с данными по пользователю:
		//ID пользователя:
		$profile["user_id"] = $user_id; // <-- ЗАПИСЬ ЗНАЧЕНИЯ
		$backend_group = array();

		//Группа администраторов:
		$group_backend_query = $db_link->prepare('SELECT `id` FROM `groups` WHERE `for_backend` = 1 ;');
		$group_backend_query->execute();
		$group_backend_id = $group_backend_query->fetchColumn();

		$backend_group[] = $group_backend_id;

		//Группа администраторов:
		$group_query = $db_link->prepare('SELECT `id` FROM `groups` WHERE `parent` = ? ;');
		$group_query->execute(array($group_backend_id));
		while ($group_id = $group_query->fetch()) {
			$backend_group[] = $group_id['id'];
		}

		$backend_group_str = implode(",", $backend_group);

		//По полям профиля:
		$profile_query = $db_link->prepare("SELECT COUNT(*) FROM `users_groups_bind` WHERE `user_id`=? AND `group_id` IN ($backend_group_str);");
		$profile_query->execute(array($user_id));

		if ($profile_query->fetchColumn() > 0) {

			return true;
		}

		return false;
	} //public static function getUserProfile()

	// ---------------------------------------------------------------------------------------------------


	/*Метод определения доступных видов связи на сайте (отправка писем по SMTP и SMS на телефон)*/
	public static function available_communications()
	{
		global $DP_Config;
		global $db_link;

		$result = array(); //Ассоциативный массив для ответа.

		//Считаем, что способ связи доступен, если он настроен. Но, нужно иметь ввиду, что в данном методе никак не контролируется корректность настроек для SMTP или SMS.


		//Определяем доступность SMTP
		$result["smtp"] = false;
		if (!empty($DP_Config->from_name) && !empty($DP_Config->from_email) && !empty($DP_Config->smtp_mode) && !empty($DP_Config->smtp_encryption) && !empty($DP_Config->smtp_host) && !empty($DP_Config->smtp_port) && !empty($DP_Config->smtp_username) && !empty($DP_Config->smtp_password)) {
			$result["smtp"] = true;
		}

		//Определяем доступность SMS
		$result["sms"] = false;
		$check_sms_query = $db_link->prepare("SELECT COUNT(*) FROM `sms_api` WHERE `active` = ?;");
		$check_sms_query->execute(array(1));
		if ($check_sms_query->fetchColumn() == 1) {
			$result["sms"] = true;
		}


		//Для удобства использования в скриптах - добавим поле all
		$result["all"] = false;
		if ($result["smtp"] && $result["sms"]) {
			$result["all"] = true;
		}

		return $result;
	}


	// ---------------------------------------------------------------------------------------------------

	/*Вспомогательный метод проверки E-mail и Телефона по регулярному выражению*/
	public static function check_contact_by_regexp($contact, $type)
	{
		global $DP_Config;
		global $db_link;

		if (empty($contact) || ($type != 'email' && $type != 'phone')) {
			return false;
		}

		$regexp_query = $db_link->prepare('SELECT `regexp` FROM `reg_fields` WHERE `name` = ?;');
		$regexp_query->execute(array($type));
		$regexp = $regexp_query->fetchColumn();
		if (!empty($regexp)) {
			preg_match("/" . $regexp . "/", $contact, $matches);
			if (count($matches) == 1) {
				if ($matches[0] != $contact) {
					return false;
				}
			} else {
				return false;
			}
		}

		return true;
	}


	// ---------------------------------------------------------------------------------------------------

	/*Проверка пользователя на принадлежность к группе ids в настройках ПУ*/
	public static function inSettingsGroup()
	{
		$profile = array(); //Ассоциативный массив с данными пользователя

		global $DP_Config;
		global $db_link;

		$users_settings_groups_str = $DP_Config->settings_group_ids;
		if (empty($users_settings_groups_str)) {
			return false;
		}

		$users_settings_groups = explode(',', $users_settings_groups_str);
		if (is_array($users_settings_groups)) {
			foreach ($users_settings_groups as &$users_settings_group) {
				$users_settings_group = trim($users_settings_group);
			}
		} else {
			$users_settings_groups = array();
		}

		if (empty($users_settings_groups)) {
			return false;
		}

		$groups_id_settings = array();

		foreach ($users_settings_groups as $users_settings_group_id) {
			$groups_id_settings[] = $users_settings_group_id;
			//Дочерние группы
			$group_query = $db_link->prepare('SELECT `id` FROM `groups` WHERE `parent` = ? ;');
			$group_query->execute(array($users_settings_group_id));
			while ($group_id = $group_query->fetch()) {
				$groups_id_settings[] = $group_id['id'];
			}
		}

		$groups_id_settings_str = implode(",", $groups_id_settings);

		//По полям профиля:
		$profile_query = $db_link->prepare("SELECT COUNT(*) FROM `users_groups_bind` WHERE `user_id`=? AND `group_id` IN ($groups_id_settings_str);");
		$profile_query->execute(array($user_id));

		if ($profile_query->fetchColumn() > 0) {
			return true;
		}

		return false;
	} //public static function getUserProfile()

	// ---------------------------------------------------------------------------------------------------

	public static function get_user_markup($price, $storage_id = null, $office_id = null, $user_id = null)
	{

		global $DP_Config;
		global $db_link;

		if (empty($price)) {
			return false;
		}

		$markup = null;

		//Получаем группу пользователя
		$group_id = DP_User::getUserGroup($user_id);

		//1. Формируем массив наценок
		$markups_2 = array();
		$markups_query = $db_link->prepare('SELECT `min_point`, `max_point`, `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id`=? ORDER BY `min_point`;');
		$markups_query->execute(array($office_id, $storage_id, $group_id));
		while ($markup = $markups_query->fetch()) {
			array_push($markups_2, $markup);
		} //for($i)

		//Ищем наценку для этой цены и обрабатываем округление
		foreach ($markups_2 as $markup_range) {
			if ($price >= $markup_range["min_point"] && $price <= $markup_range["max_point"]) {
				return (float)($markup_range["markup"]);
			}
		}

		return $markup;
	}

	// ---------------------------------------------------------------------------------------------------

	public static function get_price_purchase_by_cart_product($cart_product_id)
	{

		global $DP_Config;
		global $db_link;

		$price_purchase = null;

		//1. Получаем запись корзины
		$cart_object = array();
		$cart_object_query = $db_link->prepare('SELECT * FROM `shop_carts` WHERE `id` = ?;');
		$cart_object_query->execute(array($cart_product_id));

		$cart_object = $cart_object_query->fetch();

		if (!empty($cart_object)) {

			$product_type = $cart_object["product_type"];

			if ($product_type == 1) {

				//Получаем детальную запись корзины
				//Получаем складскую запись
				//Получаем закупочную стоимость
				$cart_record_details_query = $db_link->prepare('SELECT * FROM `shop_carts_details` WHERE `cart_record_id` = ?;');
				$cart_record_details_query->execute(array($cart_product_id));
				$cart_record_detail = $cart_record_details_query->fetch();

				if (!empty($cart_record_detail)) {

					$storage_record_id = $cart_record_detail["storage_record_id"];
					//Получаем цену ЗАКУПА по данной поставке со склада:
					$SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";

					$price_purchase_query = $db_link->prepare('SELECT `price`*' . $SQL_currency_rate . ' AS `price` FROM `shop_storages_data` WHERE `id`= ?;');
					$price_purchase_query->execute(array($storage_record_id));
					$price_purchase_record = $price_purchase_query->fetch();
					$price_purchase = $price_purchase_record["price"];
				}
			} else if ($product_type == 2) {

				$price_purchase = (isset($cart_object["t2_price_purchase"]) && !empty($cart_object["t2_price_purchase"])) ? $cart_object["t2_price_purchase"] : null;
			}
		}

		return $price_purchase;
	}

	// ---------------------------------------------------------------------------------------------------

	/*Проверка user_id на принадлежность к группе Бэкэнд*/
	public static function isBackendGroupById($user_id)
	{
		global $DP_Config;
		global $db_link;

		if ($user_id == 0) //Если пользователь не авторизован - ставим группу для гостей
		{
			return false;
		}

		$parent_ids = '';
		$backend_group = array();

		//Группа администраторов:
		do {
			if ($parent_ids == '') {
				$group_backend_query = $db_link->prepare('SELECT * FROM `groups` WHERE `for_backend` = 1;');
				$group_backend_query->execute();
			} else {
				$group_backend_query = $db_link->prepare('SELECT * FROM `groups` WHERE `parent` IN(' . $parent_ids . ');');
				$group_backend_query->execute();
				$parent_ids = '';
			}
			while ($group_backend_record = $group_backend_query->fetch()) {
				$backend_group[] = $group_backend_record['id'];
				if ($parent_ids != '') {
					$parent_ids .= ',';
				}
				$parent_ids .= $group_backend_record['id'];
			}
		} while ($parent_ids != '');

		$backend_group_str = implode(",", $backend_group);

		//По полям профиля:
		$profile_query = $db_link->prepare("SELECT COUNT(*) FROM `users_groups_bind` WHERE `user_id` = ? AND `group_id` IN ($backend_group_str);");
		$profile_query->execute(array($user_id));

		if ($profile_query->fetchColumn() > 0) {

			return true;
		}

		return false;
	} //public static function isBackendGroupById($user_id)

	// ---------------------------------------------------------------------------------------------------

	public static function getUserOrdersById($user_id)
	{
		global $DP_Config;
		global $db_link;
		$select = $db_link->prepare("SELECT *, (SELECT SUM(`price` * `count_need`-`price` * `count_need`*`discount`/100) FROM `shop_orders_items` WHERE `shop_orders`.`id` = `order_id` AND `status` IN (SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `count_flag` = 1)) as `sum_items`,
		(SELECT SUM((`price` * `count`) * (1 - `discount`/100)) 
		FROM `car_service_record_services` 
		WHERE `record_id` = (SELECT `id` FROM `car_service_records` WHERE `shop_orders`.`id` = `orders_ids`)) as `sum_services`,		
       (SELECT SUM(`amount`) 
        FROM `shop_users_accounting` 
        WHERE `shop_orders`.`id` = `order_id` AND `income` = 0 AND `active` = 1) as `debt_out`,                      
        (SELECT SUM(`amount`) 
        FROM `shop_users_accounting` 
        WHERE `shop_orders`.`id` = `order_id` AND `income` = 1 AND `active` = 1)
        AS `debt_in`
       FROM `shop_orders` WHERE `user_id` = ? ORDER BY `id` DESC;");
		$select->execute([$user_id]);
		$total_sum = 0;
		$total_debt = 0;
		$total_sum_services = 0;
		$counter = 0;

		$resultArr = [];
		$resultArr['data'] = [];
		while ($result = $select->fetch()) {
			$order_obj = [];

			$select_status = $db_link->prepare('SELECT `name` FROM `shop_orders_statuses_ref` WHERE `id` = ?;');
			$select_status->execute([$result['status']]);
			$select_status_result = $select_status->fetch();
			$total_sum += $result['sum_items'] + $result['sum_services'];
			$total_sum_services += $result['sum_services'];
			$counter++;

			$order_obj['id'] = $result['id'];
			$order_obj['sum'] = $result['sum_items'];
			$order_obj['sum_services'] = $result['sum_services']; 
			$order_obj['status'] = $select_status_result['name'];
			$order_obj['debt'] = (float)$result['sum_items'] + (float)$result['sum_services'] - ((float)$result['debt_out'] - (float)$result['debt_in']);
			$total_debt += $order_obj['debt'];

			$order_obj['date'] = date('d.m.Y', $result['time']);

			$resultArr['data'][] = $order_obj;
		}

		$resultArr['count'] = $counter;
		$resultArr['total'] =  $total_sum;
		$resultArr['total_services'] =  $total_sum_services;
		$resultArr['total_debt'] =  $total_debt;

		return $resultArr;
	}

	public static function getUserCartsById($user_id)
	{
		global $DP_Config;
		global $db_link;
		$select = $db_link->prepare("SELECT * FROM `shop_carts` WHERE `user_id` = ? ORDER BY `id` DESC;");
		$select->execute([$user_id]);
		$total_sum = 0;
		$counter = 0;
		$resultArr = [];
		$resultArr['data'] = [];
		while ($result = $select->fetch()) {

			$cart_obj = [];

			$json = json_decode($result['t2_product_json'], true);
			$name_str = $json['manufacturer'] . ', ' . $json['name'] . ' ' . $json['article'];
			$storage_id = $json['storage_id'];
			$select_storage = $db_link->prepare("SELECT `name` FROM `shop_storages` WHERE `id` = ?;");
			$select_storage->execute([$storage_id]);
			$result_select_storage = $select_storage->fetch();

			$cart_obj['name'] = $name_str;
			$cart_obj['count_need'] = $result['count_need'];
			$cart_obj['price'] = $result['price'];
			$cart_obj['storage'] = $result_select_storage['name'];

			$total_sum += $result['price'] * $result['count_need'];
			$counter++;
			$resultArr['data'][] = $cart_obj;
		}

		$resultArr['count'] = $counter;
		$resultArr['total'] =  $total_sum;

		return $resultArr;
	}

	public static function getUserCarsById($user_id)
	{
		global $DP_Config;
		global $db_link;
		$select = $db_link->prepare("SELECT * FROM `shop_docpart_garage` WHERE `user_id` = ? ORDER BY `id` DESC;");
		$select->execute([$user_id]);
		$total_sum = 0;
		$counter = 0;
		$resultArr = [];
		$resultArr['data'] = [];
		while ($result = $select->fetch()) {
			$car_obj = [];

			$select_mark = $db_link->prepare('SELECT `caption` FROM `shop_docpart_cars` WHERE `id` = ?;');
			$select_mark->execute([$result['mark_id']]);
			$result_mark = $select_mark->fetch();
			$counter++;

			$car_obj['vin'] = $result['vin'];
			$car_obj['frame'] = $result['frame'];
			$car_obj['mark'] = $result_mark['caption'];
			$car_obj['model'] = $result['model'];
			$car_obj['engine_value'] = $result['engine_value'];

			$resultArr['data'][] = $car_obj;
		}

		$resultArr['count'] = $counter;

		return $resultArr;
	}

	public static function getUserFinanceById($user_id)
	{
		global $DP_Config;
		global $db_link;
		$select = $db_link->prepare("SELECT * FROM `shop_users_accounting` WHERE `user_id` = ? AND `active` = 1 ORDER BY `id` DESC;");
		$select->execute([$user_id]);
		$total_sum_income = 0;
		$total_sum_out = 0;
		$counter_income = 0;
		$counter_out = 0;

		$resultArr = [];
		$resultArr['data'] = [];
		while ($result = $select->fetch()) {
			$obj = [];
			if ($result['income'] == '1') {
				$counter_income++;
				$total_sum_income += $result['amount'];
			} else {
				$counter_out++;
				$total_sum_out += $result['amount'];
			}

			$obj['id'] = $result['id'];
			$obj['time'] = date('d.m.Y, G:i:s', $result['time']);
			$obj['type'] = $result['income'] == '1' ? 'Приходная' : 'Расходная';;
			$obj['amount'] = $result['amount'];
			$obj['order_id'] = $result['order_id'] > 0 ? $result['order_id'] : '-';;

			$resultArr['data'][] = $obj;
		}

		$resultArr['count_out'] = $counter_out;
		$resultArr['count_in'] = $counter_income;
		$resultArr['total_out'] =  $total_sum_out;
		$resultArr['total_in'] =  $total_sum_income;

		return $resultArr;
	}

	public static function getUserQueriesById($user_id)
	{
		global $DP_Config;
		global $db_link;

		$select = $db_link->prepare("SELECT * FROM `shop_stat_article_queries` WHERE `user_id` = ? ORDER BY `id` DESC;");
		$select->execute([$user_id]);
		$counter = 0;

		$resultArr = [];
		$resultArr['data'] = [];

		while ($result = $select->fetch()) {
			$obj = [];
			$counter++;

			$obj['name'] = $result['name'];
			$obj['article'] = $result['article'];
			$obj['manufacturer'] = $result['manufacturer'];
			$obj['time'] = date('d.m.Y, G:i:s', $result['time']);

			$resultArr['data'][] = $obj;
		}
		$resultArr['count'] = $counter;

		return $resultArr;
	}

	public static function getUserReturnsById($user_id)
	{
		global $DP_Config;
		global $db_link;

		$select = $db_link->prepare("
                                    SELECT *, (SELECT SUM(`shop_orders_items`.`price` * `shop_orders_items`.`count_need`-`shop_orders_items`.`price` * `shop_orders_items`.`count_need`*`shop_orders_items`.`discount`/100)
                                    FROM `shop_orders_returns_items`
                                    INNER JOIN `shop_orders_items`
                                    ON `shop_orders_returns_items`.`item_id` = `shop_orders_items`.`id`
                                    WHERE `shop_orders_returns`.id = `return_id`) as `return_sum`
                                    FROM `shop_orders_returns` WHERE `user_id` = ?;
                                    ");
		$select->execute([$user_id]);
		$counter = 0;
		$total_sum = 0;

		$resultArr = [];
		$resultArr['data'] = [];

		while ($result = $select->fetch()) {
			$obj = [];
			$counter++;
			$total_sum += $result['return_sum'];

			$obj['id'] = $result['id'];
			$obj['return_sum'] = $result['return_sum'];
			$obj['return_complete'] = $result['return_complete'];

			$resultArr['data'][] = $obj;
		}

		$resultArr['count'] = $counter;
		$resultArr['total'] = $total_sum;

		return $resultArr;
	}

	public static function getUserOrdersItemsById($user_id)
	{
		global $DP_Config;
		global $db_link;

		$select = $db_link->prepare("SELECT `id`, `t2_article`, `t2_manufacturer`, `order_id` FROM `shop_orders_items` WHERE `order_id` IN (SELECT `id` FROM `shop_orders` WHERE `user_id` = ?);");
		$select->execute([$user_id]);
		$counter = 0;

		$resultArr = [];
		$resultArr['data'] = [];

		while ($result = $select->fetch()) {
			$obj = [];
			$counter++;

			$obj['id'] = $result['id'];
			$obj['article'] = $result['t2_article'];
			$obj['manufacturer'] = $result['t2_manufacturer'];
			$obj['order_id'] = $result['order_id'];

			$resultArr['data'][] = $obj;
		}

		$resultArr['count'] = $counter;
		return $resultArr;
	}

	public static function getUserMessagesById($user_id)
	{
		global $DP_Config;
		global $db_link;

		$select = $db_link->prepare("SELECT `text`, `order_id`, `return_id` FROM `shop_orders_messages` WHERE (`order_id` IN (SELECT `id` FROM `shop_orders` WHERE `user_id` = ?) OR `return_id` IN (SELECT `id` FROM `shop_orders_returns` WHERE `user_id` = ?)) AND `is_customer` = 1;");
		$select->execute([$user_id, $user_id]);
		$counter = 0;

		$resultArr = [];
		$resultArr['data'] = [];

		while ($result = $select->fetch()) {
			$obj = [];
			$counter++;

			$obj['text'] = $result['text'];
			$obj['order_id'] = $result['order_id'];
			$obj['return_id'] = $result['return_id'];

			$resultArr['data'][] = $obj;
		}

		$resultArr['count'] = $counter;
		return $resultArr;
	}

	// ---------------------------------------------------------------------------------------------------

	/*Метод получения значения ChatId телеграма пользователя*/

	public static function getChatId()
	{

		global $db_link;

		$user_id = DP_User::getUserId();
		$stmt = $db_link->prepare("SELECT chat_id FROM telegram_users WHERE user_id = :user_id");
		$stmt->bindParam(':user_id', $user_id);
		$stmt->execute();
		$chatId = $stmt->fetchColumn();

		if ($chatId > 0) {
			return $chatId;
		} else {
			return false;
		}
	}

	public static function addCashOperation($accounting_operation_code, $amount, $cashless_payment = -1, $order_id = NULL, $office_id = NULL, $comment = null)
	{

		$accounting_operation_code = (int) $accounting_operation_code;
		$amount = (int)$amount;

		$cash_comment = '';

		//Текущий менеджер
		$manager_id = DP_User::getAdminId();
		if (empty($manager_id)) {
			$manager_id = 0;
		} else {
			$session_check = DP_User::getActiveSessionManager($manager_id);
			if ($session_check == false) {
				return array('result' => false, 'message' => 'Смена не открыта. Сначала откройте смену, чтобы работать с финансовыми операциями.');
			}
		}

		//Все оплаты привязаны к магазину, если нет до используем дефолтный - 1
		if (empty($office_id) || $office_id < 1) {
			$office_id = 1;
			$cash_comment .= 'Офис выставлен по умолчанию. ';
		}

		$cash_operation_data = DP_User::getCashCodeByAccCode($accounting_operation_code);
		if ($cash_operation_data == false) {
			$cash_operation_income = 0;
			$cash_operation_id = 0;
			$cash_comment .= 'Не установлена связь с операцией ID ' . $accounting_operation_code . ' .';
		} else {
			$cash_operation_income = $cash_operation_data["income"];
			$cash_operation_id = $cash_operation_data["id"];
		}
		
		if (!is_null($comment)) {
			$cash_comment = $comment;
		}
		
		DP_User::insertCashOperation($amount, $cash_operation_income, $cash_operation_id, time(), $cash_comment, $manager_id, $office_id, $cashless_payment, $order_id);
	}

	public static function insertCashOperation($amount, $income, $operation_cash_code, $time, $comment = '-', $manager_id, $office_id, $cashless_payment, $order_id = NULL)
	{
		global $db_link;
		
		$insert_sql = $db_link->prepare("
		INSERT INTO `shop_offices_cash` 
		(`office_id`, `manager_id`, `time`, `income`, `amount`, `operation_code`, `comment`, `payment`, `order_id`) 
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$insert_sql->execute([$office_id, $manager_id, $time, $income, $amount, $operation_cash_code, $comment, $cashless_payment, $order_id]);		
	}

	public static function getCashCodeByAccCode($accounting_operation_code)
	{
		global $db_link;
		$sql = "
			SELECT `id`, `income`
			FROM `shop_offices_cash_codes`
			WHERE FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(`accounting_code`, '\"',''), '[',''),']','')) > 0 LIMIT 1;
		";
		$select_code = $db_link->prepare($sql);
		if ($select_code->execute([$accounting_operation_code])) {
			$res_select_code = $select_code->fetch(PDO::FETCH_ASSOC);
			return $res_select_code;
		} else {
			return false;
		}
	}

	public static function getDefaultSessionData()
	{
		global $db_link;

		$sql = "SELECT `id`, `users` FROM `shop_offices` ORDER BY `id` LIMIT 1";
		$select = $db_link->prepare($sql);
		$select->execute();
		$select_res = $select->fetch(PDO::FETCH_ASSOC);
		$users = json_decode($select_res["users"]);
		return ["office_id" => $select_res["id"], "manager_id" => $users[0]];
	}

	public static function getActiveSessionManager($manager_id)
	{
		global $db_link;

		$sql = "
			SELECT 
				`office_id`
			FROM `managers_cash_session`
			WHERE `is_active` = '1' AND `manager_id` = ?
			ORDER BY `time_start` DESC
			LIMIT 1;
		";

		$select_active_session = $db_link->prepare($sql);
		if ($select_active_session->execute(array($manager_id))) {
			$res = $select_active_session->fetch(PDO::FETCH_ASSOC);
			return $res;
		} else
			return false;
	}
	
	public static function isUserServiceMaster($user_id) {
		global $db_link;
		
		$sql = "SELECT `id` FROM `car_service_personal` WHERE `user_id` = ? LIMIT 1";
		
		$query = $db_link->prepare($sql);
		$query->execute([$user_id]);
		
		$res = $query->fetch(PDO::FETCH_ASSOC);
		
		return $res !== false;
	}

	public static function getAllowMinusQuantity($user_id) {
		global $db_link;
		$sql = $db_link->prepare("SELECT `allow_minus_quantity` FROM `users` WHERE `user_id` = ?;");
		$sql->execute([$user_id]);
		
		$res = $sql->fetch(PDO::FETCH_ASSOC);
		
		return $res["allow_minus_quantity"];
	}
	
	public static function getStrTime($product_object) {
		global $db_link;
		
		$storage_id = $product_object["t2_storage_id"];
		$office_id = $product_object["t2_office_id"];
		
		$json_params = json_decode($product_object["t2_json_params"], true);
		$extra_time = 0;
		
		if (isset($json_params["deliveryStart"]) && isset($json_params["deliveryEnd"])) {
			// Пример формата: 2026-01-28T07:30:00
			$delivery_start = $json_params["deliveryStart"];
			$delivery_end = $json_params["deliveryEnd"];
			
			// Получаем дополнительное время из базы данных
			$query = $db_link->prepare("SELECT * FROM `storages_offices_extra_time` WHERE `storage_id` = ?;");
			$query->execute([$storage_id]);
			
			$res = $query->fetch(PDO::FETCH_ASSOC);
			
			if ($res) {
				$res_json = json_decode($res["relationship"], true);
				foreach ($res_json as $info) {
					if ($info["office_id"] == $office_id) {
						$extra_time = $info["hours"];
						break;
					}
				}
			}
			
			// Преобразуем время с учетом дополнительного времени
			$start_timestamp = strtotime($delivery_start);
			$end_timestamp = strtotime($delivery_end);
			
			// Добавляем дополнительное время (в часах, переводим в секунды)
			if ($extra_time > 0) {
				$start_timestamp += ($extra_time * 3600);
				//$end_timestamp += ($extra_time * 3600);
			}
			
			// Извлекаем компоненты времени
			$start_hour = date('H', $start_timestamp);
			$start_minute = date('i', $start_timestamp);
			$end_hour = date('H', $end_timestamp);
			$end_minute = date('i', $end_timestamp);
			
			// Определяем, какое слово использовать для даты
			$today = date('Y-m-d');
			$tomorrow = date('Y-m-d', strtotime('tomorrow'));
			$start_date = date('Y-m-d', $start_timestamp);
			
			if ($start_date == $today) {
				$date_label = "Сегодня";
			} elseif ($start_date == $tomorrow) {
				$date_label = "Завтра";
			} else {
				// Форматируем дату как "дд.мм"
				$date_label = date('d.m', $start_timestamp);
				
				// Альтернативно: месяц текстом
				// $months = [
				//     '01' => 'января', '02' => 'февраля', '03' => 'марта',
				//     '04' => 'апреля', '05' => 'мая', '06' => 'июня',
				//     '07' => 'июля', '08' => 'августа', '09' => 'сентября',
				//     '10' => 'октября', '11' => 'ноября', '12' => 'декабря'
				// ];
				// $day = date('d', $start_timestamp);
				// $month = date('m', $start_timestamp);
				// $date_label = ltrim($day, '0') . ' ' . $months[$month];
			}
			
			// Формируем HTML-строку
			$result = sprintf(
				'%s с <span class="hh" style="display: inline-block;">%s</span><sup class="mm" style="display: inline-block;">%s</sup> до <span class="hh" style="display: inline-block;">%s</span><sup class="mm" style="display: inline-block;">%s</sup>',
				$date_label,
				$start_hour,
				$start_minute,
				$end_hour,
				$end_minute
			);
			
			return $result;
		}
		
		// Если время доставки не указано, возвращаем пустую строку или сообщение
		return "";
	}
	
} //class DP_User
