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
	$arr_auth_method[] = array('key'=>'sms', 'type'=>'phone', 'caption'=>'Телефон', 'icon_class'=>'fa fa-phone', 'placeholder'=>'Ваш телефон');
}
$arr_auth_method[] = array('key'=>'smtp', 'type'=>'email', 'caption'=>'Е-mail', 'icon_class'=>'fa fa-envelope', 'placeholder'=>'Ваш E-mail');


//Настройки данного метода
$startTimeAfterReload = null;
if(isset($user_session["data"]))
{
	$timerCheck = json_decode($user_session["data"], true);
	if( isset($timerCheck["timeSendFaCode"]) && ((time() - $timerCheck["timeSendFaCode"]) < 30000) )
	{
		$startTimeAfterReload = 30 - (time() - $timerCheck["timeSendFaCode"]);
	}
}
?>



<div id="panel_auth_code_<?php echo $login_form_postfix; ?>" class="panel-body panel-body-<?php echo $login_form_postfix; ?> no-auth">
    
	<div class="simple-register-wrapper form-group">
		
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
		<div id="wrapper_<?php echo $auth_type["key"]; ?>_<?php echo $auth_method['key']; ?>_<?php echo $login_form_postfix; ?>" class="hidden">
			<div class="input-group login-input">
				<span class="input-group-addon"><i class="<?php echo $auth_method['icon_class']; ?>"></i></span>
				<input style="height: 40px; background-color:#FFF; border: 1px solid #ccc; color: #555;" type="text" class="form-control" placeholder="<?php echo $auth_method['placeholder']; ?>" name="<?php echo $auth_method['type']; ?>" id="auth_contact_input_<?php echo $auth_type["key"]; ?>_<?php echo $auth_method['key']; ?>_<?php echo $login_form_postfix; ?>" />
			</div>
			<br/>
			<input style="padding: 6px 12px;" type="button" class="btn btn-ar btn-primary" value="Отправить код" onclick="funcSend($('#auth_contact_input_<?php echo $auth_type["key"]; ?>_<?php echo $auth_method['key']; ?>_<?php echo $login_form_postfix; ?>').val(), '<?php echo $auth_method['key']; ?>', '<?php echo $login_form_postfix; ?>')" />
		</div>
		<?php
		}
		?>
		
        <div class="simple-register-wrapper-body hidden" id="wrapper-body-check-<?php echo $login_form_postfix; ?>">
            <p id="description-message-<?php echo $login_form_postfix; ?>"></p>
            <input class="form-control phone-simple-register-check" type="text" id="input-code-<?php echo $login_form_postfix; ?>" placeholder="Введите код" />
            <br/>
			<div>
				<input style="padding: 6px 12px;" type="button" class="btn btn-ar btn-default" value="Назад" onClick="goBack('<?php echo $login_form_postfix; ?>');" />
				<input style="padding: 6px 12px;" type="button" class="btn btn-ar btn-primary" value="Войти" onClick="checkCode('<?php echo $login_form_postfix; ?>');" />
			</div>
			<br/>
			<p id="timer-send-code-<?php echo $login_form_postfix; ?>"></p>
        </div>
		
    </div>
	<?php
	// Получаем значение reg_variant
	$reg_variant_query = $db_link->prepare("SELECT `id` FROM `reg_variants` ORDER BY `order`, `id` ASC LIMIT 1;");
	$reg_variant_query->execute();
	$reg_variant_record = $reg_variant_query->fetch();
	$reg_variant = (int) $reg_variant_record['id'];
	?>
    <form id="formAuthenticate_<?php echo $login_form_postfix; ?>" action="/users/register" method="post">
        <input type="hidden" id="reg_input_contact_<?php echo $login_form_postfix; ?>" name="reg_contact" value="">
        <input type="hidden" id="reg_input_contact_type_<?php echo $login_form_postfix; ?>" name="reg_contact_type" value="">
        <input type="hidden" name="reg_variant" value="<?php echo $reg_variant; ?>">
        <input type="hidden" id="reg_input_code_<?php echo $login_form_postfix; ?>" name="code" value="">
        <input type="hidden" name="simple_register" value="true">
        <input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"] ;?>">
    </form>
</div>

<div style="padding-bottom: 15px;"></div>

<script>
    var csrf_guard_key = '<?php echo $user_session["csrf_guard_key"] ;?>';
</script>
<script>

if (typeof funcSend == 'undefined')
    var funcSend = async function (contact, method, id) {
        let contact_string = contact;

        if(method == 'sms'){
            if (contact == ''){
                alert('Заполните телефон');
                return false;
            }
        }
        else if(method == 'smtp'){
            if (contact == ''){
                alert('Заполните E-mail');
                return false;
            }
        }
        else{
            alert('Неизвестная операция');
            return false;
        }

        await sendNotify(contact, method, id, contact_string);
    }

if (typeof sendNotify == 'undefined')
    var sendNotify = async function (contact, method, id, contact_string, toggleWrapper = true) {
        let obj = {
            csrf_guard_key: csrf_guard_key,
            type: 'code',
            method: method,
            contact_string: contact_string,
            contact: contact
        };

		console.log(obj);
		
        let response = await fetch("/modules/login/code/frontAjax/ajax_sendCode.php", {
            method: "POST",
            body: JSON.stringify(obj)
        });
        try {
            let answer = await response.json();
            if (answer.status == 200)
            {
                toggleSimpleRegisterWrapper(method, id, contact_string, null, toggleWrapper);
                startInterval(id, null, [contact, method, id, contact_string, false]);
            }
            else
                alert(answer.message);
        }
        catch (e) {
            console.log("Ошибка на стороне сервера: \n", e);
        }
    }
if (typeof toggleSimpleRegisterWrapper == 'undefined')
    var toggleSimpleRegisterWrapper = function (method, id, contact_string, startTimeSec = null, toggleWrapper = null) {
        if(startTimeSec == null)
		{
            var startTimeSec = 30;
		}
		
		if(toggleWrapper) 
		{
            document.querySelector("#wrapper-body-check-" + id).classList.toggle("hidden");
			
			if(method == 'sms'){
				document.querySelector("#wrapper_code_"+ method +"_" + id).classList.toggle("hidden");
				document.querySelector("#description-message-" + id).innerHTML = "На ваш номер "+contact_string+"<br>отправлено CMC c кодом.";
				document.querySelector('#reg_input_contact_' + id).value = contact_string;
				document.querySelector('#reg_input_contact_type_' + id).value = 'phone';
			}
			else if(method == 'smtp'){
				document.querySelector("#wrapper_code_"+ method +"_" + id).classList.toggle("hidden");
				document.querySelector("#description-message-" + id).innerHTML = "На ваш E-mail "+contact_string+"<br>отправлено письмо c кодом.";
				document.querySelector('#reg_input_contact_' + id).value = contact_string;
				document.querySelector('#reg_input_contact_type_' + id).value = 'email';
			}
        }
        
        document.querySelector("#timer-send-code-" + id).innerHTML = `Повторно отправить код можно через <span id="timerSendId_${id}">${startTimeSec}</span> сек.`;
    }

if (typeof checkCode == 'undefined')
    var checkCode = async function (id) {
        let code = document.querySelector('#input-code-' + id).value;
        let obj = {
            csrf_guard_key: csrf_guard_key,
            code: code,
        };

        let response = await fetch("/modules/login/code/frontAjax/ajax_checkCode.php", {
            method: "POST",
            body: JSON.stringify(obj)
        });

        try {
            let answer = await response.json();
            if (answer.status == 200)
			{
                document.querySelector('#reg_input_code_' + id).value = code;
                document.querySelector('#formAuthenticate_' + id).submit();
			}
            else
                alert(answer.message);
        }
        catch (e) {
            console.log("Ошибка на стороне сервера: \n", e);
        }

    }

if (typeof goBack == 'undefined')
    var goBack = function(id) {
        document.querySelector("#wrapper-body-check-" + id).classList.toggle("hidden");
		let method = document.getElementById("auth_contact_select_code_"+ id).value;
		if (method == 'smtp'){
			document.querySelector("#wrapper_code_smtp_"+ id).classList.toggle("hidden");
		}else if (method == 'sms'){
			document.querySelector("#wrapper_code_sms_"+ id).classList.toggle("hidden");
		}
    }

if (typeof startInterval == 'undefined')
    var startInterval = function (id, startTimeFromDB = null, callbackParams = null) {
        clearInterval(timer);
        if (startTimeFromDB == null)
            var timeCounter = 30;
        else
            var timeCounter = startTimeFromDB;

        var timer = setInterval(() => {
            timeCounter--;
            if (timeCounter > 0)
                document.querySelector("#timerSendId_" + id).textContent = timeCounter;
            else
            {
                clearInterval(timer);

                document.querySelector("#timer-send-code-" + id).innerHTML = `<input style="padding: 6px 12px;" type="button" class="btn btn-ar btn-warning" value="Отправить повторно" onClick="sendNotify('${callbackParams[0]}', '${callbackParams[1]}', '${callbackParams[2]}', '${callbackParams[3]}', false);" />`;
            }
        }, 1000);
    }
</script>







<script>
	//Смена метода авторизации
	function onChangeAuthMethod_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>(method = '')
	{
		if(method == ''){
			method = document.getElementById("auth_contact_select_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>").value;
		}
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
		
		<?php
		if( ! is_null($startTimeAfterReload) )
		{
		?>
			onChangeAuthMethod_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>('<?php echo $timerCheck["method"]; ?>');
			
			toggleSimpleRegisterWrapper('<?php echo $timerCheck["method"]; ?>','<?php echo $login_form_postfix; ?>','<?php echo $timerCheck["contact_string"]; ?>', <?php echo $startTimeAfterReload; ?>, true);
			startInterval('<?php echo $login_form_postfix; ?>', <?php echo $startTimeAfterReload; ?>, JSON.parse('<?php echo json_encode([$timerCheck["contact"], $timerCheck["method"], $login_form_postfix, $timerCheck["contact_string"], false]);?>'));
		<?php
		}
		?>
	});
</script>