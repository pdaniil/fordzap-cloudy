<?php
	//Класс пользователя, экземпляр которого формируется в social/register.php
	class UserSocial{
		
		public $id; //id аккаунта пользователя в соц сети !Обязательное поле
		public $token; //токен пользователя !Обязательное поле
		public $email; //email пользователя
		public $phone; //телефон пользователя - требуется добавить форматирование для docpart
		public $first_name;//имя пользователя
		public $last_name;//фамилия пользователя
		public $social_id;//id социальной сети из таблицы social
		
		public function __construct($id, $token, $email, $phone, $first_name, $last_name, $social_id)
		{
			$this->id 			= $id;
			$this->token 		= $token;
			$this->email 		= $email;
			$this->phone 		= $phone;
			$this->first_name	= $first_name;
			$this->last_name	= $last_name;
			$this->social_id	= $social_id;
		}
	}

?>