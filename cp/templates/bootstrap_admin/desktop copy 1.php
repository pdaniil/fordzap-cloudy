<?php
defined('_ASTEXE_') or die('No access');
?>
<!DOCTYPE html>
<html>
<head>
	<base href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<!-- <meta http-equiv="Content-Security-Policy" content="img-src 'self' data: blob:; default-src 'self' *.googleapis.com *.gstatic.com 'unsafe-inline' 'unsafe-eval';"> -->
	<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />

	<script src="vendor/jquery/dist/jquery.min.js"></script>
	<script src="/lib/jquery_browser/jquery.browser.js"></script>
	<script src="/lib/jquery_form/jquery.form.js"></script>
	
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/css/modal_window.css" />
   <docpart type="head" name="head" />
	
	
    <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->
    <!--<link rel="shortcut icon" type="image/ico" href="favicon.ico" />-->

    <!-- Vendor styles -->
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.css" />
    <link rel="stylesheet" href="vendor/metisMenu/dist/metisMenu.css" />
    <link rel="stylesheet" href="vendor/animate.css/animate.css" />
    <link rel="stylesheet" href="vendor/bootstrap/dist/css/bootstrap.css" />
		<link rel="stylesheet" href="vendor/fooTable/css/footable.core.min.css" />
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- App styles -->
    <link rel="stylesheet" href="fonts/pe-icon-7-stroke/css/pe-icon-7-stroke.css" />
    <link rel="stylesheet" href="fonts/pe-icon-7-stroke/css/helper.css" />
    <link rel="stylesheet" href="styles/style.css">
	
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/css/astself.css?v=1.0" />
	<link href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/css/catalogue.css" rel="stylesheet">
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/elFinder/css/theme-bootstrap-libreicons-svg.css" />
	
	
	<!-- Подключаем всплывающие подсказки -->
	<link rel="stylesheet" href="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/templates/bootstrap_admin/vendor/toastr/build/toastr.min.css" />
	<script src="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/templates/bootstrap_admin/vendor/toastr/build/toastr.min.js"></script>

	<script src="/<?php echo $DP_Config->backend_dir; ?>/templates/assets/plugins/fancyapps/fancybox/fancybox.umd.js"></script>
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/templates/assets/plugins/fancyapps/fancybox/fancybox.css" />
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/templates/assets/plugins/fancyapps/fancybox/fancybox.custom.css" />

</head>
<body class="fixed-navbar fixed-sidebar">
<?php
//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getAdminSession();


//Функция вывода кнопок для панели управления
function print_backend_button($button_params)
{
	global $DP_Config;
	global $DP_Template;
	
	
	$target = "";
	if( isset($button_params["target"]) )
	{
		if( $button_params["target"] == "_blank" )
		{
			$target = "target=\"_blank\"";
		}
	}
	
	
	$onclick = "";
	if( isset($button_params["onclick"]) )
	{
		$onclick = "onclick=\"".$button_params["onclick"]."\"";
	}
	
	if( $button_params["background_color"] == "" )
	{
		//Изображение
		$img = "/".$DP_Config->backend_dir."/templates/".$DP_Template->name."/images/".$button_params["img"];
		if(!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$img))
		{
			$img = "content/control/images/window.png";
		}
		?>
		<a class="panel_a" href="<?php echo $button_params["url"];?>" <?php echo $onclick; ?> <?php echo $target; ?>>
			<div class="panel_a_img" style="background: url('<?php echo $img; ?>') 0 0 no-repeat;"></div>
			<div class="panel_a_caption"><?php echo $button_params["caption"];?></div>
		</a>
		<?php
	}
	else
	{
		?>
		<a class="panel_a" href="<?php echo $button_params["url"]; ?>" <?php echo $onclick; ?> <?php echo $target; ?>>
			<div class="panel_a_img" style="background-color: <?php echo $button_params["background_color"]; ?>;width:96px;height:96px;display:table-cell;vertical-align:middle;"><i class="<?php echo $button_params["fontawesome_class"]; ?>" style="color:#FFF;font-size:45px"></i></div>
			<div class="panel_a_caption"><?php echo $button_params["caption"]; ?></div>
		</a>
		<?php
	}
}
?>


<?php
//В зависимости от продукта - формируем названия
$product_name = "Docpart";//По умолчанию
if($DP_Config->product == "cms")
{
	$product_name = "Intask CMS";
}
if($DP_Config->product == "expancart")
{
	$product_name = "Expancart";
}
?>



<!-- Simple splash screen-->
<div class="splash"> <div class="color-line"></div><div class="splash-title"><h1><?php echo $product_name; ?> - Панель управления</h1><p>Загрузка фреймворка, пожалуйста, подождите... </p><div class="spinner"> <div class="rect1"></div> <div class="rect2"></div> <div class="rect3"></div> <div class="rect4"></div> <div class="rect5"></div> </div> </div> </div>
<!--[if lt IE 7]>
<p class="alert alert-danger">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
<![endif]-->

<!-- Header -->
<div id="header">
    <div class="color-line">
    </div>
		<a href="/<?php echo $DP_Config->backend_dir; ?>">
	    <div id="logo" class="light-version">
	        <span>
	            Панель управления
	        </span>
	    </div>
		</a>
    <nav role="navigation">
        <div class="header-link hide-menu"><i class="fa fa-bars"></i></div>
		
		
        <div class="small-logo">
			<a href="/<?php echo $DP_Config->backend_dir; ?>">
				<span class="text-primary">Панель управления</span>
			</a>
        </div>
        
        <div class="mobile-menu">
            <button type="button" class="navbar-toggle mobile-menu-toggle" data-toggle="collapse" data-target="#mobile-collapse">
                <i class="fa fa-chevron-down"></i>
            </button>
            <div class="collapse mobile-navbar" id="mobile-collapse">
                <ul class="nav navbar-nav">
                    <li>
                        <a class="" href="javascript:void(0);" onclick="document.forms['logout_form'].submit();">Выйти</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="navbar-right">
            <ul class="nav navbar-nav no-borders">
										
										<?php
										if($DP_Config->show_ssl_checker == 1)
										{
											//Модуль проверки SSL сертификата
											require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/modules/check_ssl/check_ssl.php");
										}
										?>
										
										<?php
										//Индикаторы корректности настроек E-mail и Телефона
										
										//Для E-mail
										$email_state = 0;//Индикатор для E-mail-уведомлений. Состояние индикатора: Работает 1, Работает (но, давно не проверялось) 2, Не работает 3, Настройки есть, но не проверялись 4
										if( empty($DP_Config->from_name) || empty($DP_Config->from_email) || empty($DP_Config->smtp_mode) || empty($DP_Config->smtp_encryption) || empty($DP_Config->smtp_host) || empty($DP_Config->smtp_port) || empty($DP_Config->smtp_username) || empty($DP_Config->smtp_password) )
										{
											$email_state = 3;//Настройки не заданы, значит точно не работает
										}
										else//Настройки заданы, далее проверяем отладку
										{
											$email_debug_query = $db_link->prepare("SELECT * FROM `debug_results` WHERE `name` = ?;");
											$email_debug_query->execute( array('email') );
											$email_debug = $email_debug_query->fetch();
											
											if( $email_debug == false )
											{
												$email_state = 4;
											}
											else
											{
												if( $email_debug['status'] == 1 )
												{
													if( time() - $email_debug['time'] < 86400 )
													{
														$email_state = 1;
													}
													else
													{
														$email_state = 2;
													}
												}
												else
												{
													$email_state = 3;//Не работает
												}
											}
										}
										switch($email_state)
										{
											case 1:
												$email_status_text = "E-mail-уведомления<br>настроены корректно";
												$email_a_style = "";
												$email_sign_after = '';
												break;
											case 2:
												$email_status_text = "E-mail-уведомления<br>работают (перепроверьте)";
												$email_a_style = "background-color:#f5f5f5;color:#000;";
												$email_sign_after = '<i class="pe-7s-info"></i>';
												break;
											case 3:
												$email_status_text = "E-mail-уведомления<br>не работают";
												$email_a_style = "background-color:#F00;color:#FFF;";
												$email_sign_after = '<i class="pe-7s-attention"></i>';
												break;
											case 4:
												$email_status_text = "E-mail-уведомления<br>настроены, но не проверены";
												$email_a_style = "background-color:#ffde00;color:#000;";
												$email_sign_after = '<i class="pe-7s-attention"></i>';
												break;
										}
										?>
										<li class="dropdown hidden-sm hidden-xs hidden-md">
						                    <a href="/<?php echo $DP_Config->backend_dir; ?>/control/communications" title="Перейти на страницу контроля способов связи" style="<?php echo $email_a_style; ?>border-bottom:1px solid #e4e5e7;">
						                        <i class="pe-7s-mail"></i> <div style="font-size:0.5em;display:inline-block;line-height:1em;"><?php echo $email_status_text; ?></div><?echo " ".$email_sign_after; ?>
						                    </a>
						                </li>
										
										
										<?php
										//Для SMS
										$sms_state = 0;//Индикатор для SMS-уведомлений. Состояние индикатора: Работает 1, Работает (но, давно не проверялось) 2, Не работает 3, Настройки есть, но не проверялись 4
										$check_sms_query = $db_link->prepare("SELECT COUNT(*) FROM `sms_api` WHERE `active` = ?;");
										$check_sms_query->execute( array(1) );
										if( $check_sms_query->fetchColumn() == 0 )
										{
											$sms_state = 3;//Настройки не заданы, значит точно не работает
										}
										else//Настройки заданы, далее проверяем отладку
										{
											$sms_debug_query = $db_link->prepare("SELECT * FROM `debug_results` WHERE `name` = ?;");
											$sms_debug_query->execute( array('sms') );
											$sms_debug = $sms_debug_query->fetch();
											
											if( $sms_debug == false )
											{
												$sms_state = 4;
											}
											else
											{
												if( $sms_debug['status'] == 1 )
												{
													if( time() - $sms_debug['time'] < 86400 )
													{
														$sms_state = 1;
													}
													else
													{
														$sms_state = 2;
													}
												}
												else
												{
													$sms_state = 3;//Не работает
												}
											}
										}
										switch($sms_state)
										{
											case 1:
												$sms_status_text = "SMS-уведомления<br>настроены корректно";
												$sms_a_style = "";
												$sms_sign_after = '';
												break;
											case 2:
												$sms_status_text = "SMS-уведомления<br>работают (перепроверьте)";
												$sms_a_style = "background-color:#f5f5f5;color:#000;";
												$sms_sign_after = '<i class="pe-7s-info"></i>';
												break;
											case 3:
												$sms_status_text = "SMS-уведомления<br>не работают";
												$sms_a_style = "background-color:#F00;color:#FFF;";
												$sms_sign_after = '<i class="pe-7s-attention"></i>';
												break;
											case 4:
												$sms_status_text = "SMS-уведомления<br>настроены, но не проверены";
												$sms_a_style = "background-color:#ffde00;color:#000;";
												$sms_sign_after = '<i class="pe-7s-attention"></i>';
												break;
										}
										?>
										
										<li class="dropdown hidden-sm hidden-xs hidden-md">
						                    <a href="/<?php echo $DP_Config->backend_dir; ?>/control/communications" title="Перейти на страницу контроля способов связи" style="<?php echo $sms_a_style; ?>border-bottom:1px solid #e4e5e7;">
						                        <i class="pe-7s-phone"></i> <div style="font-size:0.5em;display:inline-block;line-height:1em;"><?php echo $sms_status_text; ?></div><?echo " ".$sms_sign_after; ?>
						                    </a>
						                </li>
									
										<?php
										//Вывод быстрой навигации по наиболее востребованным функциям
										$control_items_query = $db_link->prepare('SELECT * FROM `control_items` WHERE `id` IN (?,?,?,?,?) ORDER BY `order`;');
										$control_items_query->execute( array(24, 4, 11, 26, 25) );
										$template_classes_fontawesome = array("fas fa-user-alt"=>"pe-7s-user", "fas fa-user-plus"=>"pe-7s-add-user", "fas fa-shopping-bag"=>"pe-7s-shopbag", "fas fa-shapes"=>"pe-7s-keypad", "fas fa-shopping-cart"=>"pe-7s-cart", "fas fa-money-check-alt"=>"pe-7s-credit");//Сопоставление с более подходящими под шаблон пиктограммами
										while( $control_item = $control_items_query->fetch() )
										{
											$control_item["url"] = str_replace( array("<backend>"), $DP_Config->backend_dir, $control_item["url"]);
											
											//Замена fontawesome
											$control_item['fontawesome_class'] = $template_classes_fontawesome[$control_item['fontawesome_class']];
											?>

											<li class="dropdown hidden-xs hidden-sm">
												<a href="<?php echo $control_item['url']; ?>" title="<?php echo $control_item['caption']; ?>">
													<i class="<?php echo $control_item['fontawesome_class']; ?>"></i>
												</a>
											</li>
											
											<?php
										}
										?>
										
										<!-- START Модуль сообщений из социальных сетей /<?=$DP_Config->backend_dir?>/soc-messages -->
										<li class="dropdown hidden-sm hidden-xs hidden-md">
						                    <a href="/<?=$DP_Config->backend_dir?>/soc-messages" title="Сообщения социальных сетей" class="label-menu-corner">
						                        <i class="pe-7s-mail"></i>
												<span id="soc-unread-messages-count" class="label label-danger"></span>
												<!-- fas fa-message -->
						                    </a>
											<script>
												function socialCountUnseenMessages()
												{
													$.post('<?php echo $DP_Config->domain_path . $DP_Config->backend_dir . "/content/soc_messages/api.php"; ?>', {
														api: "countUnseenMessages"
													}, function(res){
														if(res.success !== undefined && res.success > 0){
															$("#soc-unread-messages-count").text(res.success).removeClass('d-none');
														}else{
															$("#soc-unread-messages-count").text('').addClass('d-none');
														}
													});
													setTimeout(socialCountUnseenMessages, 60000); // Каждую минуту обновляем непрочитанные сообщения
												}
												socialCountUnseenMessages();
											</script>
						                </li>
										<!-- END Модуль сообщений из социальных сетей -->

										<!-- START Модуль индикации непросмотренных VIN -->
										<li id="not_viewed_vin" style="display:none;">
											<a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/requests" title="Запросы">
												<i style="position: relative; top: 1px;" class="pe-7s-upload pe-7s-gleam"></i>
												<span style="right:3px;" class="label label-primary" id="not_viewed_users_vin_count"></span>
											</a>
										</li>
										<script>
											var current_not_viewed_users_vin = -1;//Текущее количество непросмотренных запросов
											var title_original = document.title;//Исходное значение заголовка страницы
											
											//Функция обновления информации по количеству не просмотренных запросов
											function update_viewed_users_vin_info()
											{
												jQuery.ajax({
													type: "POST",
													async: true,
													url: "/<?php echo $DP_Config->backend_dir; ?>/content/requests/ajax_get_vin_info.php?csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
													dataType: "json",//Тип возвращаемого значения
													success: function(answer)
													{
														if(answer.status == 1)
														{
															//Первоначальная запись текущего количества
															if( current_not_viewed_users_vin == -1 )
															{
																current_not_viewed_users_vin = parseInt(answer.count);
															}
															//----------------------
															//Обработка виджета
															if( parseInt(answer.count) > 0)
															{
																document.getElementById("not_viewed_users_vin_count").innerHTML = answer.count;
																document.getElementById("not_viewed_vin").style.display = 'block';
															}
															else
															{
																document.getElementById("not_viewed_users_vin_count").innerHTML = "";
																document.getElementById("not_viewed_vin").style.display = 'none';
															}
															//----------------------
															//Обработка добавления новых пользователей (т.е. если количество увеличилось во время просмотра страницы)
															if( parseInt(answer.count) > parseInt(current_not_viewed_users_vin) )
															{
																//Звуковой сигнал//...
																//Фавикон//...
																//Title
																document.title = "Есть новые VIN-запросы! "+title_original;
															}//~ if Индикация новых
															else if( parseInt(answer.count) == 0 )//Если новых нет (просмотрены на другой вкладке)
															{
																document.title = title_original;
															}
															//----------------------
															//Записываем новое текущее количество
															current_not_viewed_users_vin = answer.count;
														}
													}
												});
											}
											
											update_viewed_users_vin_info();//Запрос при загрузке страницы
											
											//Запускаем запросы
											var timerId_vin = setInterval(function() {
												update_viewed_users_vin_info();
											}, 600000);
										</script>
										<!-- END Модуль индикации непросмотренных VIN -->
										
										
										
										
										
										<!-- START Модуль индикации непросмотренных сообщений -->
										<li id="not_viewed_msg" class="dropdown" style="display:none;">
						                    <a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders?read=0" title="Заказы с непрочитанными сообщениями">
						                        <i style="font-size: 30px;" class="pe-7s-mail"></i>
						                        <span class="label label-warning" id="not_viewed_msg_count"></span>
						                    </a>
						                </li>
										<script>
											//Функция обновления информации о количестве непрочитанных сообщений
											function update_cnt_not_viewed_msg()
											{
												jQuery.ajax({
													type: "POST",
													async: true,
													url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_get_cnt_not_viewed_msg.php?csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
													dataType: "json",//Тип возвращаемого значения
													success: function(answer)
													{
														if(answer.status == 1)
														{
															if(answer.count > 0){
																document.getElementById("not_viewed_msg_count").innerHTML = answer.count;
																document.getElementById("not_viewed_msg").style.display = 'block';
															}else{
																document.getElementById("not_viewed_msg").style.display = 'none';
															}
														}else{
															document.getElementById("not_viewed_msg").style.display = 'none';
														}
													}
												});
											}
											update_cnt_not_viewed_msg();//Запрос при загрузке страницы
											//Запускаем запросы непросмотренных сообщений
											var timerId_orders = setInterval(function() {
												update_cnt_not_viewed_msg();
											}, 400000);
										</script>
										<!-- END Модуль индикации непросмотренных сообщений -->
										

										<?php
										// Наличие товаров с критическим количеством
										$query = $db_link->prepare("SELECT COUNT(`id`) FROM `shop_catalogue_products` WHERE `min_limit_status` = '1';");
										$query->execute();
										$limited_products_count = $query->fetchColumn();

										if((int)$limited_products_count > 0) {

											$update_datetime = 'Ошибка';
											if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/cron/cron_update_products_limit_log.txt")) {
												$update_time = htmlentities(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/content/cron/cron_update_products_limit_log.txt"));
												$update_datetime = date('H:i d.m.Y', $update_time);
											}

											?>
											<li class="dropdown">
													<a class="dropdown-toggle label-menu-corner" style="color:#ff3a3a;" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/stock?limited=1" title="Обновлено <?= $update_datetime ;?>">
														<?= $limited_products_count ;?> <i class="fas fa-exclamation"></i>
													</a>
											</li>
											<?php
										}

										// Наличие складских записей с ценой <= 0 и наличием <= 0
										$query = $db_link->prepare("SELECT `id` FROM `shop_storages_data` WHERE `price` <= 0 AND `exist` <= 0 LIMIT 1;");
										$query->execute();
										$row = $query->fetch();
										if(empty($row)){
											$query = $db_link->prepare("SELECT `id` FROM `shop_catalogue_products` WHERE `id` NOT IN(SELECT DISTINCT `product_id` FROM `shop_storages_data`) LIMIT 1;");
											$query->execute();
											$row = $query->fetch();
										}
										if(!empty($row)){
										?>
										<li class="dropdown">
											<a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/stock" title="Наличие складских записей с ценой <= 0 и наличием <= 0">
												<i style="font-size: 17px; color: #ff5722;" class="fa fa-square" aria-hidden="true"></i>
											</a>
										</li>
										<?php
										}
										?>
										
										<?php
										
										// Наличие складских записей с ценой > 0 и наличием <= 0
										$query = $db_link->prepare("SELECT `id` FROM `shop_storages_data` WHERE `price` > 0 AND `exist` <= 0 LIMIT 1;");
										$query->execute();
										$row = $query->fetch();
										if(!empty($row)){
										?>
										<li class="dropdown">
											<a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/stock" title="Наличие складских записей с ценой > 0 и наличием <= 0">
												<i style="font-size: 17px; color: #ffe500;" class="fa fa-square" aria-hidden="true"></i>
											</a>
										</li>
										<?php
										}
										?>


										<!-- START Модуль индикации непросмотренных заказов -->
										<?php
										//Для работы с пользователями
										require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
										$manager_id = DP_User::getAdminId();//ID менежера, который отображает эту страницу
										?>
										<li class="dropdown">
						                    <a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders" title="Заказы">
						                        <i class="pe-7s-shopbag"></i>
						                        <span class="label label-success" id="not_viewed_orders_count"></span>
						                    </a>
						                </li>
										<script>
											var current_not_viewed_orders = -1;//Текущее количество непросмотренных заказов
											var title_original = document.title;//Исходное значение заголовка страницы
											//Функция обновления информации по просмотренным заказам
											function update_viewed_info()
											{
												var request_object = new Object;
												request_object.user_id = <?php echo $manager_id; ?>;
												
												jQuery.ajax({
													type: "POST",
													async: true,
													url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_get_orders_info.php",
													dataType: "json",//Тип возвращаемого значения
													data: "request_object="+JSON.stringify(request_object)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
													success: function(answer)
													{
														if(answer.status == 1)
														{
															//Первоначальная запись текущего количества заказов
															if( current_not_viewed_orders == -1 )
															{
																current_not_viewed_orders = parseInt(answer.message);
															}
															//----------------------
															//Обработка виджета
															if( parseInt(answer.message) > 0)
															{
																document.getElementById("not_viewed_orders_count").innerHTML = answer.message;
															}
															else
															{
																document.getElementById("not_viewed_orders_count").innerHTML = "";
															}
															//----------------------
															//Обработка добавления новых заказов (т.е. если количество увеличилось во время просмотра страницы)
															if( parseInt(answer.message) > parseInt(current_not_viewed_orders) )
															{
																//Звуковой сигнал//...
																//Фавикон//...
																//Title
																document.title = "Есть новые заказы! "+title_original;
															}//~ if Индикация новых заказов
															else if( parseInt(answer.message) == 0 )//Если новых заказов нет (просмотрены на другой вкладке)
															{
																document.title = title_original;
															}
															//----------------------
															//Записываем новое текущее количество
															current_not_viewed_orders = answer.message;
														}
													}
												});
											}
											update_viewed_info();//Запрос при загрузке страницы
											//Запускаем запросы непросмотренных заказов 1 раз в 10 секунд
											var timerId = setInterval(function() {
												update_viewed_info();
											}, 300000);
										</script>
										<!-- END Модуль индикации непросмотренных заказов -->
										
										
										
										
										
										<!-- START Модуль индикации заказов в статусе Обрабатывается -->
										<?php
										$orders_statuses_query = $db_link->prepare("SELECT * FROM `shop_orders_statuses_ref` WHERE `for_paid` = 1 LIMIT 1;");
										$orders_statuses_query->execute();
										$orders_statuses_record = $orders_statuses_query->fetch();
										?>
										<li id="not_viewed_for_paid_orders" class="dropdown" style="display:none;">
						                    <a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders?status_id=<?php echo $orders_statuses_record['id']; ?>" title="Заказы в статусе <?php echo $orders_statuses_record['name']; ?>">
						                        <i class="pe-7s-shopbag"></i>
						                        <span class="label label-info" id="for_paid_orders_count"></span>
						                    </a>
						                </li>
										<script>
											var for_paid_orders = -1;//Текущее количество непросмотренных заказов
											var title_original = document.title;//Исходное значение заголовка страницы
											//Функция обновления информации по просмотренным заказам
											function update_for_paid_orders_info()
											{
												jQuery.ajax({
													type: "POST",
													async: true,
													url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_get_cnt_for_paid_orders.php",
													dataType: "json",//Тип возвращаемого значения
													data: "csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
													success: function(answer)
													{
														if(answer.status == 1)
														{
															//Первоначальная запись текущего количества заказов
															if( for_paid_orders == -1 )
															{
																for_paid_orders = parseInt(answer.count);
															}
															//----------------------
															//Обработка виджета
															if( parseInt(answer.count) > 0)
															{
																document.getElementById("for_paid_orders_count").innerHTML = answer.count;
																document.getElementById("not_viewed_for_paid_orders").style.display = 'block';
															}
															else
															{
																document.getElementById("for_paid_orders_count").innerHTML = "";
																document.getElementById("not_viewed_for_paid_orders").style.display = 'none';
															}
															//----------------------
															//Обработка добавления новых заказов (т.е. если количество увеличилось во время просмотра страницы)
															if( parseInt(answer.count) > parseInt(for_paid_orders) )
															{
																//Звуковой сигнал//...
																//Фавикон//...
																//Title
																document.title = "Есть заказы требующие обработки! "+title_original;
															}//~ if Индикация новых заказов
															else if( parseInt(answer.count) == 0 )//Если новых заказов нет (просмотрены на другой вкладке)
															{
																document.title = title_original;
															}
															//----------------------
															//Записываем новое текущее количество
															for_paid_orders = answer.count;
														}
													}
												});
											}
											update_for_paid_orders_info();//Запрос при загрузке страницы
											//Запускаем запросы непросмотренных заказов 1 раз в 10 секунд
											var timerId_for_paid = setInterval(function() {
												update_for_paid_orders_info();
											}, 300000);
										</script>
										<!-- END Модуль индикации заказов в статусе Обрабатывается -->
										
										
										
										
										
										<!-- START Модуль индикации заказов, в которых есть отмененная позиция -->
										<li id="not_viewed_for_orders_with_canceled_items" class="dropdown" >
						                    <a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders?cancel_flag=1" title="Заказы, в которых есть отмененные позиции">
						                        <i class="pe-7s-shopbag"></i>
						                        <span class="label label-danger" id="for_orders_with_canceled_items_count"></span>
						                    </a>
						                </li>
										<script>
											var for_orders_with_canceled_items = -1;//Текущее количество заказов, в которых есть отмененная позиция
											var title_original = document.title;//Исходное значение заголовка страницы
											//Функция обновления информации по заказам, в которых есть отмененная позиция
											function update_for_orders_with_canceled_items_info()
											{
												jQuery.ajax({
													type: "POST",
													async: true,
													url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_get_cnt_for_orders_with_canceled_items.php",
													dataType: "json",//Тип возвращаемого значения
													data: "csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
													success: function(answer)
													{
														if(answer.status == 1)
														{
															//Первоначальная запись текущего количества заказов, в которых есть отмененная позиция
															if( for_orders_with_canceled_items == -1 )
															{
																for_orders_with_canceled_items = parseInt(answer.count);
															}
															//----------------------
															//Обработка виджета
															if( parseInt(answer.count) > 0)
															{
																document.getElementById("for_orders_with_canceled_items_count").innerHTML = answer.count;
																document.getElementById("not_viewed_for_orders_with_canceled_items").style.display = 'block';
															}
															else
															{
																document.getElementById("for_orders_with_canceled_items_count").innerHTML = "";
																document.getElementById("not_viewed_for_orders_with_canceled_items").style.display = 'none';
															}
															//----------------------
															//Обработка добавления новых заказов (т.е. если количество увеличилось во время просмотра страницы)
															if( parseInt(answer.count) > parseInt(for_orders_with_canceled_items) )
															{
																//Звуковой сигнал//...
																//Фавикон//...
																//Title
																document.title = "Есть заказы требующие обработки! "+title_original;
															}//~ if Индикация новых заказов
															else if( parseInt(answer.count) == 0 )//Если новых заказов нет (просмотрены на другой вкладке)
															{
																document.title = title_original;
															}
															//----------------------
															//Записываем новое текущее количество
															for_orders_with_canceled_items = answer.count;
														}
													}
												});
											}
											update_for_orders_with_canceled_items_info();//Запрос при загрузке страницы
											//Запускаем запросы заказов, в которых есть отмененная позиция, 1 раз в 10 секунд
											var timerId_for_paid = setInterval(function() {
												update_for_orders_with_canceled_items_info();
											}, 300000);
										</script>
										<!-- END Модуль индикации заказов, в которых есть отмененная позиция -->
										
										
										
										
										
										<!-- START Модуль индикации непросмотренных сообщений возвратов -->
										<li id="not_viewed_msg_returns" class="dropdown" style="display:none;">
						                    <a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/shop/returns-manager?read=0" title="Возвраты с непрочитанными сообщениями">
						                        <i style="font-size: 30px;" class="pe-7s-mail"></i>
						                        <span class="label label-warning" id="not_viewed_msg_count_returns"></span>
						                    </a>
						                </li>
										<script>
											//Функция обновления информации о количестве непрочитанных сообщений
											function update_cnt_not_viewed_msg_returns()
											{
												jQuery.ajax({
													type: "POST",
													async: true,
													url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_get_cnt_not_viewed_msg.php?returns=1&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
													dataType: "json",//Тип возвращаемого значения
													success: function(answer)
													{
														if(answer.status == 1)
														{
															if(answer.count > 0){
																document.getElementById("not_viewed_msg_count_returns").innerHTML = answer.count;
																document.getElementById("not_viewed_msg_returns").style.display = 'block';
															}else{
																document.getElementById("not_viewed_msg_returns").style.display = 'none';
															}
														}else{
															document.getElementById("not_viewed_msg_returns").style.display = 'none';
														}
													}
												});
											}
											update_cnt_not_viewed_msg_returns();//Запрос при загрузке страницы
											//Запускаем запросы непросмотренных сообщений
											var timerId_returns = setInterval(function() {
												update_cnt_not_viewed_msg_returns();
											}, 400000);
										</script>
										<!-- END Модуль индикации непросмотренных сообщений возвратов -->
										
										
										
										
										
						                <!-- Модуль индикации возвратов-->
						                <li id="indicator_returns" class="dropdown">
						                    <a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/shop/returns-manager" title="Возвраты">
						                        <i class="pe-7s-back"></i>
						                        <span class="label label-danger" id="not_viewed_returns_count"></span>
						                    </a>
						                </li>
						                <script>
						                    var current_not_viewed_returns = -1;//Текущее количество непросмотренных заказов
						                    //Функция обновления информации по просмотренным заказам
						                    function update_viewed_info_returns()
						                    {
						                        var request_object = new Object;
						                        request_object.user_id = <?php echo $manager_id; ?>;

						                        jQuery.ajax({
						                            type: "POST",
						                            async: true,
						                            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/returns/ajax/ajax_get_returns_info.php",
						                            dataType: "json",//Тип возвращаемого значения
						                            data: "request_object="+JSON.stringify(request_object)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
						                            success: function(answer)
						                            {
						                                if(answer.status == 1)
						                                {
						                                    //Первоначальная запись текущего количества заказов
						                                    if( current_not_viewed_returns == -1 )
						                                    {
						                                        current_not_viewed_returns = parseInt(answer.message);
						                                    }
						                                    //----------------------
						                                    //Обработка виджета
						                                    if( parseInt(answer.message) > 0)
						                                    {
						                                        document.getElementById("not_viewed_returns_count").innerHTML = answer.message;
						                                    }
						                                    else
						                                    {
						                                        document.getElementById("not_viewed_returns_count").innerHTML = "";
						                                    }

						                                    //Записываем новое текущее количество
						                                    current_not_viewed_returns = answer.message;
						                                }
						                            }
						                        });
						                    }
						                    update_viewed_info_returns();//Запрос при загрузке страницы
						                    //Запускаем запросы непросмотренных заказов 1 раз в 10 секунд
						                    var timerIdReturns = setInterval(function() {
						                        update_viewed_info_returns();
						                    }, 300000);
						                </script>

						                <!-- END Модуль индикации возвратов-->				
										
										<li class="dropdown">
						                    <a href="<?php echo $DP_Config->domain_path; ?>" target="_blank" title="Перейти на сайт">
						                        <i class="pe-7s-play"></i>
						                    </a>
						                </li>
										
						                <li class="dropdown">
						                    <a href="javascript:void(0);" onclick="document.forms['logout_form'].submit();">
						                        <i class="pe-7s-upload pe-rotate-90"></i>
						                    </a>
						                </li>
          	</ul>
        </div>
    </nav>
</div>

<!-- Navigation -->
<aside id="menu">
    <div id="navigation">
        <div class="profile-picture">
            <div class="stats-label text-color">
                <?php
				//Блок слева - профиль пользователя и форма выхода
				//Получаем данные пользователя
				$admin_profile = DP_User::getAdminProfile();
				?>
				<form id="logout_form" method="POST" name="logout_form">
					<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
					<input type="hidden" name="logout" value="logout" />
				</form>
				<span class="font-extra-bold font-uppercase"><?php echo $admin_profile["name"]." ".$admin_profile["surname"]; ?></span>

                <div class="dropdown">
					<a class="dropdown-toggle" href="#" data-toggle="dropdown">
                        <small class="text-muted">Личный кабинет <b class="caret"></b></small>
                    </a>
					
                    <ul class="dropdown-menu animated flipInX m-t-xs">
                        <!--
						<li><a href="contacts.html">Contacts</a></li>
                        <li><a href="profile.html">Profile</a></li>
                        <li><a href="analytics.html">Analytics</a></li>
                        <li class="divider"></li>
						-->
                        <li><a href="javascript:void(0);" onclick="document.forms['logout_form'].submit();">Выйти</a></li>
                    </ul>
                </div>
            </div>
        </div>
		
		
		<div class="text-center" style="padding:5px;border-top:1px solid #e4e5e7;">
			<?php
			$edit_mode = null;
			if( isset($_COOKIE["edit_mode"]) )
			{
				$edit_mode = $_COOKIE["edit_mode"];
			}
			switch($edit_mode)
			{
				case "frontend":
					$is_frontend = 1;
					break;
				case "backend":
					$is_frontend = 0;
					break;
				default:
					$is_frontend = 1;
					break;
			}
			if($is_frontend)
			{
				?>
				Режим редактирования: <b>Фронтэнд</b> <img style="height:15px; border-radius:10px" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/earth.png" />
				<?php
			}
			else
			{
				?>
				Режим редактирования: <b>Бэкэнд</b> <img style="height:15px; border-radius:10px" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/shield.png" />
				<?php
			}
			?>
		</div>
		
		<docpart type="module" name="left_cp_menu" />
    </div>
</aside>

<!-- Main Wrapper -->
<div id="wrapper">
	
	<?php
	//Блок новостей - отображаем только на главной странице
	if( $DP_Content->main_flag && isset($DP_Config->intask_server) )
	{
		//Запрос через определенный промежуток времени
		if( empty($_COOKIE["dp_news_time"]) || $_COOKIE["dp_news_time"] < (time() - 3600) )
		{
			$SQL = "SELECT `time` FROM `version_control` ORDER BY `id` DESC LIMIT 1;";
			$query = $db_link->prepare($SQL);
			$query->execute();
			$row = $query->fetch();
			if($row['time'] < (time() - (86400 * 30)))
			{
				//Отображаем блок только для Администраторов с доступом к Настройкам сайта
				$adminProfile = DP_User::getAdminProfile();//Профиль администратора
				$SQL = "SELECT * FROM `content_access` WHERE `content_id` IN(SELECT `id` FROM `content` WHERE `alias` = 'config' AND `is_frontend` = 0) AND `group_id` = ?;";
				$query = $db_link->prepare($SQL);
				$query->execute(array($adminProfile["groups"][0]));
				$row = $query->fetch();
				if( !empty($row) )
				{
					$dp_news_text = @file_get_contents($DP_Config->intask_server.'intask_messages.php');
					$dp_news_hash = md5($dp_news_text);
					if( ! isset($_COOKIE["dp_news_hash"]) ){
						$_COOKIE["dp_news_hash"] = '';
					}
					if( !empty($dp_news_text) && $dp_news_hash !== $_COOKIE["dp_news_hash"] )
					{
		?>
						<div id="dp_news" class="normalheader transition animated fadeIn">
							<div class="hpanel">
								<div class="panel-body">
									<a title="Закрыть" class="small-header-action" onclick="dp_news_hidden();">
										<div class="clip-header">
											<i class="fa fa-chevron-up"></i>
										</div>
									</a>

									<?php echo $dp_news_text; ?>
									
								</div>
							</div>
						</div>
						<script>
						function dp_news_hidden(){
							let hash = '<?php echo $dp_news_hash; ?>';
							let time = '<?php echo time(); ?>';
							let date = new Date(new Date().getTime() + 15552000 * 1000);
							document.cookie = "dp_news_hash="+hash+"; path=/; expires=" + date.toUTCString();
							document.cookie = "dp_news_time="+time+"; path=/; expires=" + date.toUTCString();
							document.getElementById("dp_news").style.display = "none";
						}
						</script>
		<?php
					}
					else
					{
						//Если новость не изменилась запишем время последнего запроса
						?>
						<script>
							let time = '<?php echo time(); ?>';
							let date = new Date(new Date().getTime() + 15552000 * 1000);
							document.cookie = "dp_news_time="+time+"; path=/; expires=" + date.toUTCString();
						</script>
						<?php
					}
				}
			}
		}
	}
	?>
	
	<div class="normalheader transition animated fadeIn">
		<div class="hpanel">
			<div class="panel-body">

				<a class="small-header-action" href="">
					<div class="clip-header">
						<i class="fa fa-arrow-up"></i>
					</div>
				</a>

				<div id="hbreadcrumb" class="pull-right m-t-lg">
					<docpart type="module" name="breadcrumb" />
				</div>
				<h2 class="font-light m-b-xs">
					<?php echo $DP_Content->value; ?>
				</h2>
				<small><?php echo $DP_Content->description; ?></small>

				<div class="header-bottom" style="margin-top: 10px;">
					<?php require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/logistics/cash_assets/cash_common_widget.php"); ?>
				</div>
			</div>
		</div>
	</div>
	
	
	
	
    <div class="content animate-panel">
		<div class="row">
			
			<?php
				require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
			?>
		
			<docpart type="main" name="main" />
		</div>
    </div>

    <!-- Right sidebar -->
    <div id="right-sidebar" class="animated fadeInRight">
		
		<!--
		<div class="p-m">
			<button id="sidebar-close" class="right-sidebar-toggle sidebar-button btn btn-default m-b-md"><i class="pe pe-7s-close"></i>
            </button>
        </div>
		-->
		
		
		
		<div class="row">
			<div class="col-lg-12 text-left" style="margin:7px;">
				<button id="sidebar-close" class="right-sidebar-toggle sidebar-button btn btn-default ">
					<i class="pe pe-7s-close"></i>
				</button>
			</div>
		
			<docpart type="module" name="left_cp_menu1" />
		</div>
    </div>

    <!-- Footer-->
    <footer class="footer">
        <span class="pull-right">
            Панель управления сайтом
        </span>
        Copyright &copy <?php echo date("Y", time()); ?>, <?php echo $DP_Config->site_name; ?>
    </footer>

</div>

<!-- Vendor scripts -->
<script src="vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="vendor/slimScroll/jquery.slimscroll.min.js"></script>
<script src="vendor/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="vendor/jquery-flot/jquery.flot.js"></script>
<script src="vendor/jquery-flot/jquery.flot.resize.js"></script>
<script src="vendor/jquery-flot/jquery.flot.pie.js"></script>
<script src="vendor/flot.curvedlines/curvedLines.js"></script>
<script src="vendor/jquery.flot.spline/index.js"></script>
<script src="vendor/metisMenu/dist/metisMenu.min.js"></script>
<script src="vendor/iCheck/icheck.min.js"></script>
<script src="vendor/peity/jquery.peity.min.js"></script>
<script src="vendor/sparkline/index.js"></script>
<script src="vendor/fooTable/dist/footable.all.min.js"></script>


<!-- App scripts -->
<script src="scripts/homer.js"></script>
<script src="scripts/charts.js"></script>


<script>
	if( typeof webix != 'undefined' )
	{
		webix.Touch.disable();
	}
</script>




<script>
// -------------------------------------------------
//Настройка высплывающих подсказок
toastr.options = {
  "closeButton": true,
  "debug": false,
  "newestOnTop": false,
  "progressBar": false,
  "positionClass": "toast-top-center",
  "preventDuplicates": false,
  "onclick": null,
  "showDuration": "300",
  "hideDuration": "1000",
  "timeOut": "10000",
  "extendedTimeOut": "1000",
  "showEasing": "swing",
  "hideEasing": "linear",
  "showMethod": "fadeIn",
  "hideMethod": "fadeOut"
}
// -------------------------------------------------
//Показать подсказку
function show_hint(hint_text)
{
	toastr.info(hint_text);
}
// -------------------------------------------------
</script>

<?php
//Подключение модального окна для создания кассовых чеков
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/kkt/check_create_modal_window.php");
?>


<?php
//Маска ввода телефона
require_once($_SERVER["DOCUMENT_ROOT"]."/lib/inputmask/phone_mask.php");
?>


</body>
</html>