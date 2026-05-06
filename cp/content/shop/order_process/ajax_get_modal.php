<?php

//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
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

//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getAdminSession();
?>


<div  style="width: 100%;">
	<div style="margin-bottom: 10px;">
		<div class="modal-title">Выберите клиента из списка</div>
		<div>
		    <select id="select_client" class="multiple-select">
			<?php
					
					//Сначала находим группу пользователей для бэкенда
					$admin_user_query = $db_link->prepare("SELECT `id` FROM groups WHERE for_backend = 1;");
					$admin_user_query->execute();
					$backend_group_fetch = $admin_user_query->fetch();
					$backend_group = $backend_group_fetch["id"];

					$SQL_SELECT_CUSTOMER = "CASE
					WHEN `users`.`reg_variant` = 1 
						THEN CONCAT('ФЛ ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'surname' AND `user_id` = `users`.`user_id`),''), ' ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'name' AND `user_id` = `users`.`user_id`),''), ' ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'patronymic' AND `user_id` = `users`.`user_id`),''), ' ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'phonen' AND `user_id` = `users`.`user_id`), ''))
					WHEN `users`.`reg_variant` = 2 
						THEN CONCAT('ИП  ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'surname' AND `user_id` = `users`.`user_id`),''), ' ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'name' AND `user_id` = `users`.`user_id`),''), ' ИНН', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'inn' AND `user_id` = `users`.`user_id`), ''))
					WHEN `users`.`reg_variant` = 3 
						THEN CONCAT(IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'company_name' AND `user_id` = `users`.`user_id`), 'Компания'), ' ИНН', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'inn' AND `user_id` = `users`.`user_id`), ''))
					ELSE ''
					END ";
					
					$SQL_SELECT_USERS = "SELECT
						`users`.`user_id`,
   						`users`.`email`,
                        `users`.`phone`,
						$SQL_SELECT_CUSTOMER as `customer`
						FROM `users`;";
	
					//Ищем всех пользователей, кроме тех у кого есть доступ в панель управления. Это менеджеры, они не нужны.
					$user_query = $db_link->prepare($SQL_SELECT_USERS);
					$user_query->execute($binding_values);
					while( $user_id_record = $user_query->fetch() )
					{
						$user_id = $user_id_record["user_id"];
						$email = $user_id_record['email'];
						$phone = $user_id_record['phone'];
						
						$customer = $user_id_record['customer'];
						
						$main_field = "";
						
						if(!empty($email)) {
						    $main_field .= $email . " ";
						}
						
						if(!empty($phone)) {
						    $main_field .= str_replace([' ', '(', ')', '-'], '', $phone) . " ";
						    // $main_field .= $phone . " ";
						}

						$main_field = trim($main_field);
						
						?>
						<option data-phone="<?php echo $phone; ?>" data-email="<?php echo $email; ?>" value="<?php echo $user_id; ?>"><?php echo "$customer $main_field (id-{$user_id})"; ?></option>
						<?php
					}
					?>
			</select>
		</div>
	</div>

	<div style="margin-bottom: 20px;">
		<div class="modal-title">Выберите магазин</div>
		<div>
			<select name="select_shop" id="select_shop" class="multiple-select">

			<?php

				//Список точек выдачи
				$offices_query = $db_link->prepare('SELECT `id`, `caption`, `address` FROM `shop_offices` ORDER BY `id`;');
				$offices_query->execute();
				while( $office = $offices_query->fetch() )
				{
					$office_id = $office["id"];
					$office_caption = $office['caption'];
					$office_address = $office['address'];
					
					?>
					<option value="<?php echo $office_id; ?>" title="<?php echo $office_address; ?>"><?php echo "$office_caption"; ?></option>
					<?php
				}

			?>
			</select>
		</div>
	</div>

	<div style="margin-bottom: 20px;">
		<div class="modal-title">Выберите способ доставки</div>
		<div>
			<select name="select_delivery" id="select_delivery" class="multiple-select">

			<?php
		
				//Список способа доставки
				$obtaining_query = $db_link->prepare('SELECT `id`, `caption` FROM `shop_obtaining_modes` WHERE `available` = 1 ORDER BY `id`;');
				$obtaining_query->execute();
				while( $obtaining_node = $obtaining_query->fetch() )
				{
					$obtaining_id = $obtaining_node["id"];
					$obtaining_caption = $obtaining_node['caption'];
					
					?>
					<option value="<?php echo $obtaining_id; ?>"><?php echo "$obtaining_caption"; ?></option>
					<?php
				}

			?>
			</select>
		</div>
	</div>

	<div style="margin-bottom: 10px;">
		<div class="modal-title">Оставьте комментарий</div>
		<div>
			<textarea style="width: 100%; border-color: #ccc;" id="modal_order_comment"></textarea>
		</div>
	</div>
	
	<div class="" style="text-align: right;">
		<span id="save_modal_addToCart_status_text"></span>
		<a class="btn btn-ar btn-primary" id="confirm_order-btn" onclick="confirm_order();">Оформить</a>
	</div>
  </div>
</div>




<script>
	function confirm_order()
	{

		jQuery('#confirm_order-btn').addClass('disabled');

		if(document.getElementById("select_client").value == "")
        {
            alert("Выберите клиента");
			jQuery('#confirm_order-btn').removeClass('disabled');
            return;
        }

		if(document.getElementById("select_shop").value == "")
        {
            alert("Выберите магазин");
			jQuery('#confirm_order-btn').removeClass('disabled');
            return;
        }

		if(document.getElementById("select_delivery").value == "")
        {
            alert("Выберите способ доставки");
			jQuery('#confirm_order-btn').removeClass('disabled');
            return;
        }
        
        user_id = document.getElementById("select_client").value;
	    user_notify = jQuery("#select_client").find(`option[value=${user_id}]`).attr('data-notify');

        if(user_id == null && user_id == "")
        {
            alert("Ошибка. Пользователь не выбран.");
			jQuery('#confirm_order-btn').removeClass('disabled');
            return;
        }
        
		let obtaining_mode = document.getElementById("select_delivery").value;
		let shop_id = document.getElementById("select_shop").value;

		let order_comment = document.getElementById("modal_order_comment").value;

		//Устанавливаем cookie (на полгода)
		let date = new Date(new Date().getTime() + 15552000 * 1000);

		//Объект способа доставки
		let how_get = new Object;
		how_get.mode = obtaining_mode;
		how_get.office_id = shop_id;
		
		document.cookie = "how_get="+JSON.stringify(how_get)+"; path=/; expires=" + date.toUTCString();

		ajaxSuccess = true;
		
		jQuery.ajax({
			type: "POST",
			async: false, //Запрос синхронный
			url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_create_order.php",
			dataType: "json",//Тип возвращаемого значения
			data: "order_message="+encodeURIComponent(order_comment)+"&user_id=" + user_id +"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
			success: function(answer)
			{
				if(answer.status == true)
				{
					console.log("Результат.", answer);
					
					location="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=" + answer.order_id;
					
				}
				else
				{
					alert(answer.message);
					
					document.getElementById("confirm_loader").style.display = 'none';
					
					ajaxSuccess = false;
				}
			}
		});

		if (!ajaxSuccess) {return false;}

		jQuery('#confirm_order-btn').removeClass('disabled');
	}
	
	jQuery(document).ready(function() {
	   $('.multiple-select').multipleSelect(
    		{ 
    			width: "100%",
    			filter: true
    		}
    	);
	});

</script>