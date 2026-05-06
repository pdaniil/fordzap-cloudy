<?php
defined('_ASTEXE_') or die('No access');

//Скрипт конкретного типа авторизации
//Подключается внутри скрипта \modules\login\login_form_general.php
//Внутри данного скрипта доступна переменная $auth_type - с массивом свойств данного типа авторизации


//Доступные методы авторизации
$arr_auth_method = array();
$available_communications = DP_User::available_communications();//Получаем доступные способы связи
if( $available_communications["sms"] )
{
	$arr_auth_method[] = array('key'=>'phone', 'type'=>'phone', 'caption'=>'Телефон', 'icon_class'=>'fa fa-phone', 'placeholder'=>'Ваш телефон');
}
$arr_auth_method[] = array('key'=>'email', 'type'=>'email', 'caption'=>'Е-mail', 'icon_class'=>'fa fa-envelope', 'placeholder'=>'Ваш E-mail');
?>



<div class="panel-body no-auth">
	<form method="POST" name="auth_form_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>">
		
		<input type="hidden" name="form_name" value="auth_form<?php echo $login_form_postfix; ?>" />
		<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
		<input type="hidden" name="authentication" value="true"/>
		<input type="hidden" name="auth_contact" value="" id="auth_contact_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>"/>
		<input type="hidden" name="auth_contact_type" value="" id="auth_contact_type_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>"/>
		<?php
		if( ! isset($login_form_target) )
		{
			$login_form_target = "";
		}
		?>
		<input type="hidden" name="target" value="<?php echo $login_form_target; ?>"/>
		
		<div class="form-group">
			
			<!-- Селектор контакта для аутентификации -->
			<div class="<?php echo (count($arr_auth_method) <= 1)?'hidden':''; ?>">
				<div class="input-group login-input">
					<span style="padding-left: 3px; padding-right: 2px;" class="input-group-addon"><small>Через</small></span>
					<select name="auth_contact_type" class="form-control" id="auth_contact_select_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>" onchange="onChangeAuthMethod_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>();" style="height: 40px; background-color:#FFF; border: 1px solid #ccc; color: #555; padding-left: 8px;">
						<?php
						foreach($arr_auth_method as $auth_method)
						{
						?>
						<option value="<?php echo $auth_method['key']; ?>"><?php echo $auth_method['caption']; ?></option>
						<?php
						}
						?>
					</select>
				</div>
				<br/>
			</div>
			
			<?php
			foreach($arr_auth_method as $auth_method)
			{
			?>
			<div id="wrapper_<?php echo $auth_type["key"]; ?>_<?php echo $auth_method['key']; ?>_<?php echo $login_form_postfix; ?>" class="input-group login-input hidden">
				<span class="input-group-addon"><i class="<?php echo $auth_method['icon_class']; ?>"></i></span>
				<input style="height: 40px; background-color:#FFF; border: 1px solid #ccc; color: #555;" type="text" class="form-control" placeholder="<?php echo $auth_method['placeholder']; ?>" name="<?php echo $auth_method['type']; ?>" id="auth_contact_input_<?php echo $auth_type["key"]; ?>_<?php echo $auth_method['key']; ?>_<?php echo $login_form_postfix; ?>" />
			</div>
			<?php
			}
			?>
			
			<br/>
			
			<div class="input-group login-input">
				<span class="input-group-addon"><i style="padding: 0px 2px 0px 3px;" class="fa fa-lock"></i></span>
				<input style="height: 40px; background-color:#FFF; border: 1px solid #ccc; color: #555;" type="password" class="form-control" placeholder="Пароль" name="password" autocomplete="off" />
			</div>
			
			<div class="checkbox">
				<input type="checkbox" id="checkbox_remember_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>" name="rememberme" />
				<label for="checkbox_remember_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>">Запомнить меня</label>
			</div>
			
			<a href="javascript:void(0);" onclick="onAuthFormSubmit_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>();" class="btn btn-ar btn-primary btn_auth" style="color:#FFF;">Войти</a>
			<a href="/users/registration" class="btn btn-ar btn-success btn_reg" style="color:#FFF;">Регистрация</a>
			
			<hr class="dotted margin-10">
			
			<a href="/users/forgot_password" class="btn btn-ar btn-warning btn_forget" style="color:#FFF;">Не помню пароль</a>
			
			
<!--Данный скрипт добавить после поля «Не помню пароль»-->
<div  style="margin-bottom: 10px; text-align: center; width: 80%; ">
	<div style="width: 100%; text-align: center; margin-bottom: 10px;">Авторизация через соцсети</div>
	<div style=" text-align: center; width: 100%;">
		<?php
		$SQL_get_social = "SELECT * FROM `social`";
		$query = $db_link->prepare($SQL_get_social);
		$query->execute();

		while ($social = $query->fetch())
		{	
			$SQL_get_options = "SELECT * FROM `social_options` WHERE `id_social` = ?";
			$query_options = $db_link->prepare($SQL_get_options);
			$query_options->execute( array( $social["id"] ) );
			$options = $query_options->fetch();
			?>
			<a style="width: 30px; height: 30px; margin-left: 5px; margin-right: 5px; display: inline-block;" href="https://<?php echo $_SERVER["SERVER_NAME"].$options["uri_redirect"];?>"><img width="30" height="30" src="<?php echo "https://".$_SERVER["SERVER_NAME"].$social["social_img_url"];?>" /></a>
			<?php 
		}
		?>
	</div>
</div>
			
			<div class="clearfix"></div>
			
		</div>
		
	</form>
</div>



<script>
	//Смена метода авторизации
	function onChangeAuthMethod_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>()
	{
		let method = document.getElementById("auth_contact_select_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>").value;
		
		//По всем доступным методам
		<?php
		foreach($arr_auth_method as $auth_method)
		{
		?>
			//Блоки методов
			method_wrapper = $('#wrapper_<?php echo $auth_type["key"]; ?>_<?php echo $auth_method['key']; ?>_<?php echo $login_form_postfix; ?>');
			if(method_wrapper)
			{
				//Выставляем class hidden для всех блоков
				if( ! method_wrapper.hasClass('hidden') )
				{
					method_wrapper.addClass('hidden');
				}
				//Снимаем class hidden у нужного блока
				if(method == '<?php echo $auth_method['key']; ?>')
				{
					method_wrapper.removeClass('hidden');
				}
			}
		<?php
		}
		?>
	}
	
	//Обработка формы перед отправкой
	function onAuthFormSubmit_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>()
	{
		//По всем доступным методам
		<?php
		foreach($arr_auth_method as $auth_method)
		{
		?>
			//input
			method_wrapper = $('#wrapper_<?php echo $auth_type["key"]; ?>_<?php echo $auth_method['key']; ?>_<?php echo $login_form_postfix; ?>');
			if(method_wrapper)
			{
				//Находим отображаемый блок
				if( ! method_wrapper.hasClass('hidden') )
				{
					$('#auth_contact_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>').val($('#auth_contact_input_<?php echo $auth_type["key"]; ?>_<?php echo $auth_method['key']; ?>_<?php echo $login_form_postfix; ?>').val());
					$('#auth_contact_type_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>').val('<?php echo $auth_method['type']; ?>');
				}
			}
		<?php
		}
		?>
		
		//Отправка формы
		document.forms['auth_form_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>'].submit();
	}
	
	//После загрузки страницы
	$(document).ready(function()
	{
		onChangeAuthMethod_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>();
	});
</script>