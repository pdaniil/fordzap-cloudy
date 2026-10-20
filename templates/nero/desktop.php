<?php
defined('_ASTEXE_') or die('No access');

//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getUserSession();

//Переменные для подстановки в input модулей поисковых строк
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/search_strs_for_inputs.php");
//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");



// Получаем информацию об офисе
$customer_office_info = array();
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
if($customer_offices[0] > 0){
	$customer_office_query = $db_link->prepare('SELECT * FROM `shop_offices` WHERE `id` = ?;');
	$customer_office_query->execute(array($customer_offices[0]));
	$customer_office_info = $customer_office_query->fetch(PDO::FETCH_ASSOC);
}



//Получаем данные по валюте магазина
$stmt = $db_link->prepare('SELECT * FROM `shop_currencies` WHERE `iso_code` = :iso_code;');
$stmt->bindValue(':iso_code', $DP_Config->shop_currency);
$stmt->execute();
$currency_record = $stmt->fetch(PDO::FETCH_ASSOC);
$currency_sign = $currency_record["sign"];
//Строка для обозначения валюты
if($DP_Config->currency_show_mode == "no")
{
	$currency_indicator = "";
}
else if($DP_Config->currency_show_mode == "sign_before" || $DP_Config->currency_show_mode == "sign_after")
{
	$currency_indicator = $currency_sign;
}
else
{
	$currency_indicator = $currency_record["caption_short"];
}
?>
<!DOCTYPE html>
<html>
<head>
	<base href="/templates/nero/"/>

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    
	<meta charset="UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>


    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

	
    <!-- CSS -->
	<link href="assets/css/style_all.css?v=<?=(int)$DP_Template->data_value->version;?>" rel="stylesheet" type="text/css" title="default"/>
	
	<link href="css/catalogue/catalogue.css" rel="stylesheet" type="text/css"/>
	<link href="/modules/slider/css/style.css" rel="stylesheet" type="text/css"/>
	
	<link href="css/astself.css" rel="stylesheet" type="text/css"/>

	<?php
	if( ! $DP_Content->main_flag || isset($_COOKIE["session"])){
	?>
	<!-- JS -->
	<script src="assets/js/vendors.js"></script>
	<?php
	}else{
	?>
	<script src="assets/js/vendors_main.js"></script>
	<?php
	}
	?>

	<docpart type="head" name="head" />
	
	<?php
	if( ! $DP_Content->main_flag ){
	?>
	<link rel="stylesheet" href="css/docpart/style.css" type="text/css" />
	<script src="/lib/jQuery_ui/jquery-ui.js"></script>
	<link href="/lib/jQuery_ui/jquery-ui.css" rel="stylesheet">
	<?php
	}
	?>
	
	<?php
	// IFRAME :

	// Если страница загружена в iframe - используется в панели управления, для работы проценки
	if( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe' )
	{
		
	}
	else
	{
	?>
	<script src="//code.jivo.ru/widget/iO2io0JOgH" async></script>
	<?php
	}
	?>
	
	
</head>
<body>


<?php
if(isset($_COOKIE["session"])){
?>
<!-- Preloader -->
<div id="preloader" class="">
    <div id="status">&nbsp;</div>
</div>
<?php
}
?>


<div class="container">
<?php
if (isset($_GET['success_message_code']) && $_GET['success_message_code'] == 'tinkoff_success_message') {
	$_REQUEST["success_message"] = "Заявка успешно оформлена, ожидайте смену статуса заказа";
}
require_once($_SERVER["DOCUMENT_ROOT"]."/content/general/actions_alert.php");//Вывод сообщений о результатах выполнения действий
?>
</div>



<?php
if(!empty($DP_Template->data_value->message_header)){
    if($DP_Template->data_value->message_header != "telegram"){
    ?>
    <div class="alert alert-info" style="background-color: #f5f5f5; border: solid 1px #ddd; margin: 0; border-left: 0; border-right: 0; border-top: 0;">
    	<div class="container">
    		<h4><strong><i class="fa fa-bullhorn"></i> Объявление</strong></h4>
    		<div><?=$DP_Template->data_value->message_header;?></div>
    	</div>
    </div>
    <?php
    }
    else{
        //Для работы с пользователем
        require_once( $_SERVER['DOCUMENT_ROOT']."/modules/telegram/telegram_link.php" );
}
}
?>



<header class="hidden-xs">
    <div class="top-menu-line" style="background: <?=$DP_Template->data_value->top_menu_color;?>;">
		<div class="container">
			<table>
				<tr>
					<td>
						<nav class="navbar navbar-default navbar-header-full yamm navbar-static-top" role="navigation">
							<docpart type="module" name="top_menu" />
						</nav>
					</td>
					<td>
						<?php
						//Баланс покупателя
						if(DP_User::getUserId() > 0)
						{
						?>
							<!-- Модуль индикации непросмотренных сообщений -->
							<div id="not_viewed_msg" class="new-header-user-box" style="display:none;">
								<a href="/shop/orders?read=0">
									<i class="fa fa-envelope"></i>
									<small><span id="not_viewed_msg_count"></span></small>
								</a>
								<script>
									//Функция обновления информации о количестве непрочитанных сообщений
									function update_cnt_not_viewed_msg()
									{
										jQuery.ajax({
											type: "POST",
											async: true,
											url: "/content/shop/order_process/ajax_get_cnt_not_viewed_msg.php?csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
											dataType: "json",//Тип возвращаемого значения
											success: function(answer)
											{
												if(answer.status == 1)
												{
													if(answer.count > 0){
														document.getElementById("not_viewed_msg_count").innerHTML = answer.count;
														document.getElementById("not_viewed_msg").style.display = 'inline-block';
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
									var timerId_cnt_not_viewed_msg = setInterval(function() {
										update_cnt_not_viewed_msg();
									}, 400000);
								</script>
							</div>
							
							<!-- Модуль индикации непросмотренных сообщений по VIN запросам -->
							<div id="not_viewed_msg_vin" class="new-header-user-box" style="display:none;">
								<a href="/requests">
									<i class="fa fa-rocket"></i>
									<small><span id="header_vin_count"></span></small>
								</a>
							</div>
							<script>
								//Функция обновления информации по корзине
								function update_cnt_not_viewed_msg_vin()
								{
									jQuery.ajax({
										type: "POST",
										async: true,
										url: "/content/requests/ajax_get_vin_info.php?csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
										dataType: "json",
										success: function(answer){
											if(answer.count > 0){
												document.getElementById("header_vin_count").innerHTML = answer.count;
												document.getElementById("not_viewed_msg_vin").style.display = 'inline-block';
											}else{
												document.getElementById("not_viewed_msg_vin").style.display = 'none';
											}
										}
									});
								}

								update_cnt_not_viewed_msg_vin();//Запрос при загрузке страницы
								//Запускаем запросы непросмотренных сообщений
								var timerId_cnt_not_viewed_msg_vin = setInterval(function() {
									update_cnt_not_viewed_msg_vin();
								}, 400000);
							</script>
							
							<!-- Модуль индикации непросмотренных сообщений по возвратам -->
							<div id="not_viewed_msg_returns" class="new-header-user-box" style="display:none;">
								<a href="/shop/returns/returns_list?read=0">
									<i class="fa fa-reply"></i>
									<small><span id="not_viewed_msg_count_returns"></span></small>
								</a>
								<script>
									//Функция обновления информации о количестве непрочитанных сообщений
									function update_cnt_not_viewed_msg_returns()
									{
										jQuery.ajax({
											type: "POST",
											async: true,
											url: "/content/shop/order_process/ajax_get_cnt_not_viewed_msg.php?returns=1&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
											dataType: "json",//Тип возвращаемого значения
											success: function(answer)
											{
												if(answer.status == 1)
												{
													if(answer.count > 0){
														document.getElementById("not_viewed_msg_count_returns").innerHTML = answer.count;
														document.getElementById("not_viewed_msg_returns").style.display = 'inline-block';
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
									var timerId_cnt_not_viewed_msg_returns = setInterval(function() {
										update_cnt_not_viewed_msg_returns();
									}, 400000);
								</script>
							</div>
							
							<div class="new-header-user-box">
								<a href="/shop/balans" class="user_balance">
									<i><span class="balance_indicator"><?=$currency_indicator;?></span></i>
									<span class="balance_text">
										<?php
										$stmt = $db_link->prepare('SELECT *,( IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=1 AND `active` = 1), 0) - IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=0 AND `active` = 1),0) ) AS `balance` FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `active` = 1;');
										$stmt->bindValue(':user_id', DP_User::getUserId());
										$stmt->execute();
										$balance_record = $stmt->fetch(PDO::FETCH_ASSOC);
										if($balance_record !== false){
											$balance = (float) $balance_record["balance"];
										}else{
											$balance = 0;
										}
										$balance = number_format($balance, 2, '.', ' ');
										echo $balance;
										?>
									</span>
								</a>
							</div>
							
						<?php
						}
						?>
						
						
						<div class="new-header-user-box dropdown" id="loginDropdown">
							<?php
							$user_tab_caption = "Войти";
							$user_tab_icon = '<i class="fa fa-sign-in" aria-hidden="true"></i> ';
							if( DP_User::getUserId() != 0 )
							{
								$userProfile = DP_User::getUserProfile();
								$user_tab_caption = $userProfile["name"]." ".$userProfile["surname"];
								$user_tab_icon = '<i class="fa fa-user" aria-hidden="true"></i> ';
							}
							?>
							<span class="dropdown-toggle" data-toggle="dropdown">
								<a><?php echo $user_tab_icon . $user_tab_caption; ?></a>
							</span>
							<div class="dropdown-menu dropdown-menu-right dropdown-login-box animated flipCenter" id="onDropDownProc">
								<?php
								$login_form_postfix = "header_top_tab";
								require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
								?>
							</div>
						</div>

						<script>
							$('#loginDropdown').on('hide.bs.dropdown', function (event) {
								if (document.querySelector('#onDropDownProc').contains(window.event.target)) {
									event.stopImmediatePropagation()
									return false;
								}
							});
						</script>
						
						
						<?php
						if( ((int) DP_User::getUserId() == 0) && ((int) $DP_Config->simple_register_available == 0) )
						{
						?>
						<div class="new-header-user-box">
							<a href="/users/registration"><i class="fa fa-user-plus" aria-hidden="true"></i> Регистрация</a>
						</div>
						<?php
						}
						?>
						
					</td>
				</tr>
			</table>
		</div>
	</div>
	

	
	<div class="logo-line" style="background: <?=$DP_Template->data_value->header_color;?>;">
		<div class="container">
			<div class="table-group">
				
				<div class="table-control">
					<a class="header-logo" href="<?php echo $DP_Config->domain_path; ?>">
						<img src="<?=$DP_Template->data_value->logo_file;?>?v=<?=(int)$DP_Template->data_value->version;?>" alt="logotype"/>
					</a>
				</div>
				
				<div class="table-control text-right">
					<div class="geo-point-box text-left">
						<?php
						$geo_point_class = 'hidden';
						$query_geo = $db_link->prepare("SELECT `activated` FROM `modules` WHERE `id` = ?;");
						$query_geo->execute( array(38) );
						$module_geo = $query_geo->fetch();
						if($module_geo['activated'] == 1)
						{
							$geo_point_class = '';
						}
						?>
						<div class="<?=$geo_point_class;?>">
							<table>
								<tr>
									<td><i class="fa fa-location-arrow" aria-hidden="true"></i></td>
									<td><span><docpart type="module" name="geo_point" /></span></td>
								</tr>
							</table>
						</div>
						<?php
						if($module_geo['activated'] == 0)
						{
						?>
							<table>
								<tr>
									<td><i class="fa fa-location-arrow" aria-hidden="true"></i></td>
									<td><span><?=trim($customer_office_info['city']).'<br/>'.trim($customer_office_info['address']);?></span></td>
								</tr>
							</table>
						<?php
						}
						?>
					</div>
				</div>
				
				<div class="table-control text-right">
					<div class="timetable-box text-left">
					<table>
						<tr>
							<td><i class="fa fa-clock-o" aria-hidden="true"></i></td>
							<td><span><?=trim(html_entity_decode($customer_office_info['timetable']));?></span></td>
						</tr>
					</table>
					</div>
				</div>
				
				<div class="table-control text-right">
					<div class="header-phone-box"><a href="tel:<?=str_replace(array(' ','-','(',')'),'',$customer_office_info['phone']);?>" class="phone call-me"><?=$customer_office_info['phone'];?></a></div>
					<div class="header-call-box"><a href="/zapros-prodavczu">Заказать обратный звонок</a></div>
				</div>
				
			</div>
		</div>
	</div>
	


	<div class="schearch-line">
		<div class="container">
			<div class="row">
				<div class="col-sm-7 col-md-8">
					<table>
						<tr>
							<td>
								<a class="header-home-btn" href="/"><i class="fa fa-home" aria-hidden="true"></i></a>
								
								<?php
								$stmt = $db_link->prepare('SELECT COUNT(`id`) AS `count_id` FROM `shop_catalogue_categories` WHERE `published_flag` = ? AND `parent` = ?;');
								$stmt->execute(array(1,0));
								$check_categories_exist_record = $stmt->fetch(PDO::FETCH_ASSOC);
								if( $check_categories_exist_record["count_id"] > 0 )
								{
								?>
								<a class="header-cat-btn" onClick="showCatalogMenu();"><i class="fa fa-bars" aria-hidden="true"></i> Каталог <span class="hidden-sm">товаров</span></a>
								<?php
								}
								?>
							</td>
							<td class="search-table-td">
								<div class="header-search-box">
									<div class="dropdown">
										<span class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>
										<ul class="dropdown-menu dropdown-menu-left dropdown-login-box animated flipCenter">
											<li><a href="javascript:void(0);" onClick="change_header_search_form(1);">Поиск по артикулу</a></li>
											<li><a href="javascript:void(0);" onClick="change_header_search_form(2);">Поиск по наименованию</a></li>
										</ul>
									</div>
									<?php
									$header_search_form_1_hidden = '';
									$header_search_form_2_hidden = 'hidden';
									if( $DP_Content->content_type == "category" || $DP_Content->url =="shop/search" )
									{
										$header_search_form_1_hidden = 'hidden';
										$header_search_form_2_hidden = '';
									}
									?>
									<form action="/shop/part_search" method="GET" class="header_search_form_1 <?=$header_search_form_1_hidden;?>">
										<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
										<div class="input-group">
											<input value="<?=$value_for_input_search;?>" type="text" class="form-control" placeholder="Поиск по артикулу" name="article" />
											<span class="input-group-btn">
												<button class="btn btn-ar btn-primary" type="submit">Поиск</button>
											</span>
										</div>
									</form>
									<form action="/shop/search" method="GET" class="header_search_form_2 <?=$header_search_form_2_hidden;?>">
										<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
										<div class="input-group">
											<input value="<?=$value_for_input_search_string;?>" type="text" class="form-control" placeholder="По каталогу наличия" name="search_string" />
											<span class="input-group-btn">
												<button class="btn btn-ar btn-primary" type="submit">Поиск</button>
											</span>
										</div>
									</form>
								</div>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="col-sm-5 col-md-4">
					<div class="menu-box">
						
						<div class="menu-box-item">
							<a title="Заказы" href="/shop/orders" class="orders-i">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#orders-i"></use></svg>
							</a>
						</div>
						
						<div class="menu-box-item">
							<a title="Сравнения" href="/shop/sravneniya">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#compare-i"></use></svg>
								<span class="badge badge-primary badge-round" id="header_compare_count"></span>
							</a>
						</div>
						
						<div class="menu-box-item">
							<a title="Закладки" href="/shop/zakladki">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#bookmarks-i"></use></svg>
								<span class="badge badge-primary badge-round" id="header_bookmarks_count"></span>
							</a>
						</div>
						
						<div class="menu-box-item">
							<a title="Гараж" href="/garazh">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#garage-i"></use></svg>
							</a>
						</div>
						
						<div class="menu-box-item">
							<a title="Баланс" href="/shop/balans">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#balance-i"></use></svg>
							</a>
						</div>
						
						<div class="menu-box-item" id="shop_cart_notify_block">
							<a title="Корзина" href="/shop/cart">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#cart-i"></use></svg>
								<span class="" id="header_cart_items_count"></span>
							</a>
						</div>
						
					</div>
				</div>
			</div>
		</div>
		<div id="dp_menu">
			<div class="container">
				<div class="vertical-tabs-right">
					<?php
					include($_SERVER["DOCUMENT_ROOT"]."/modules/shop/catalogue/dp_menu.php");
					?>
				</div>
			</div>
		</div>
	</div>
</header>



<!-- header box for mobile -->
<div class="header-box-mobile hidden-sm hidden-md hidden-lg">
  <div class="geo-point-user-box" onclick="openPopupWindow_CityList();">
							<table>
								<tr>
									<td class="geo-td-icon"><i class="fa fa-location-arrow" aria-hidden="true"></i></td>
									<td class="geo-td-text"><span><?=trim($customer_office_info['city']).'<br/>'.trim($customer_office_info['address']);?></span></td>
								</tr>
							</table>
						</div>
	<nav class="navbar navbar-default navbar-header-full yamm navbar-static-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<a id="ar-brand" class="logo_min" href="<?php echo $DP_Config->domain_path; ?>">
					<img src="<?=$DP_Template->data_value->logo_file;?>?v=<?=(int)$DP_Template->data_value->version;?>" alt="logotype" />
				</a>
				
				<a class="mobile-box-phone" href="tel:<?=str_replace(array(' ','-','(',')'),'',$customer_office_info['phone']);?>"><?=$customer_office_info['phone'];?></a>
				
				<button id="mobile_hide_user" type="button" class="navbar-toggle header_fa_user_btn" data-toggle="collapse" data-target="#bs-example-navbar-collapse-2"><i class="fa fa-user"></i></button>
			
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1"><i class="fa fa-bars"></i></button>
			</div>
			
			<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
				<docpart type="module" name="top_menu_catalog" />
				<docpart type="module" name="top_menu" />
			</div>
			
			<div class="row">
				<div class="collapse" id="bs-example-navbar-collapse-2">
				<div class="header-user-box">
					<div class="new-header-user-box">
						<?php
						if($module_geo['activated'] == 1)
						{
						?>
						
						<?php
						}
						//Единый механизм формы авторизации
						$login_form_postfix = "header_top_tab_mob";
						require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
						?>
					</div>
				</div>
				</div>
			</div>
		</div>
	</nav>
	
	<div class="col-xs-12 mobile-search-div">
		<table>
			<tr>
				<td>
					<div class="header-search-box">
						<div class="dropdown">
							<span class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>
							<ul class="dropdown-menu dropdown-menu-left dropdown-login-box animated flipCenter">
								<li><a href="javascript:void(0);" onClick="change_header_search_form(1);">Поиск по артикулу</a></li>
								<li><a href="javascript:void(0);" onClick="change_header_search_form(2);">Поиск по наименованию</a></li>
							</ul>
						</div>
						<form action="/shop/part_search" method="GET" class="header_search_form_1 <?=$header_search_form_1_hidden;?>">
							<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
							<div class="input-group">
								<input value="<?=$value_for_input_search;?>" type="text" class="form-control" placeholder="Поиск по артикулу" name="article" />
								<span class="input-group-btn">
									<button class="btn btn-ar" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
								</span>
							</div>
						</form>
						<form action="/shop/search" method="GET" class="header_search_form_2 <?=$header_search_form_2_hidden;?>">
							<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
							<div class="input-group">
								<input value="<?=$value_for_input_search_string;?>" type="text" class="form-control" placeholder="По каталогу наличия" name="search_string" />
								<span class="input-group-btn">
									<button class="btn btn-ar" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
								</span>
							</div>
						</form>
					</div>
				</td>
				<td id="mobile_hide_cart">
					<a href="/shop/cart" class="header-cart-box">
						<i class="fa fa-shopping-cart" aria-hidden="true"></i>
						<span class="" id="header_cart_items_count_mobile"></span>
					</a>
				</td>
			</tr>
		</table>
	</div>
</div>
<div class="row"></div>
<!-- end header box for mobile -->



<?php
if($DP_Content->main_flag)
{
?>
<div class="slider-line">
<div class="container">
<div class="row">

	<div class="row"></div>
	<div class="<?=(!empty($DP_Template->data_value->banner_name_1))?'col-md-8 col-lg-8':'col-lg-12';?>">
		<?php
		//Подключение встроенного слайдера с редактором из ПУ
		require_once($_SERVER["DOCUMENT_ROOT"]."/modules/slider/slider.php");
		?>
	</div>
	
	<div class="<?=(!empty($DP_Template->data_value->banner_name_1))?'col-md-4 col-lg-4':'hidden';?>">
		<div class="row">
			<div class="col-sm-6 col-md-12 col-lg-12">
				<a href="<?=$DP_Template->data_value->banner_url_1;?>" class="cat-tile cat-tile-img-1">
					<span class="cat-tile-text">
					<?=$DP_Template->data_value->banner_name_1;?>
					</span>
				</a>
			</div>
			<div class="col-sm-6 col-md-12  col-lg-12">
				<a href="<?=$DP_Template->data_value->banner_url_2;?>" class="cat-tile cat-tile-img-2">
					<span class="cat-tile-text">
					<?=$DP_Template->data_value->banner_name_2;?>
					</span>
				</a>
			</div>
		</div>
	</div>
	
</div>
</div>
</div>
<?php
}
?>

<?php
if($DP_Content->main_flag)
{
?>
<div class="container">
	<div class="row">
		<div class="col-lg-12">
			<h2 class="section-title">Подбор запчастей</h2>  
			<iframe
			  src="https://api.fordzap.com/parts-catalogue-ui/?embed=1"
			  style="width:100%; height:650px; border:0; border-radius:16px; overflow:hidden;"
			  loading="lazy"
			  referrerpolicy="no-referrer-when-downgrade"
			></iframe>
		</div>
	</div>
</div>
<?php } ?>

<?php
if($DP_Content->main_flag)
{
?>
<div class="slider-line tile-line-box" style="background: <?=$DP_Template->data_value->ucats_line_bg;?>;">
<div class="container">
<div class="row">

	<?php
	if($DP_Config->ucats_shiny != '' || 
	$DP_Config->ucats_disks != '' || 
	$DP_Config->ucats_accessories != '' || 
	$DP_Config->ucats_to != '' || 
	$DP_Config->ucats_oil != '' || 
	$DP_Config->ucats_akb != '' || 
	$DP_Config->ucats_caps != '' || 
	$DP_Config->ucats_bolty != '')
	{
	?>
		<div class="tile-line">
		<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 tile_box">
		
			
			<?php
			if( $DP_Config->ucats_shiny != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/shiny" class="tile tile-img-3">
						<span class="tile-text">
						Шины
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_disks != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/kolesnye-diski" class="tile tile-img-4">
						<span class="tile-text">
						Диски
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_accessories != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/avtoaksessuary" class="tile tile-img-5">
						<span class="tile-text">
						Аксессуары
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_oil != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/avtoximiya" class="tile tile-img-6">
						<span class="tile-text">
						Автохимия
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_akb != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/akkumulyatory" class="tile tile-img-7">
						<span class="tile-text">
						Аккумуляторы
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_caps != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/kolpaki" class="tile tile-img-8">
						<span class="tile-text">
						Колпаки
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_bolty != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/kolesnye-gajki-bolty-prostavki" class="tile tile-img-9">
						<span class="tile-text">
						Болты, гайки
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_to != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/katalog-texnicheskogo-obsluzhivaniya" class="tile tile-img-10">
						<span class="tile-text">
						Каталог ТО
						</span>
					</a>
				</div>
				<?php
			}
			/*
			if( true )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-2">
					<a href="/katalog-lamp" class="tile tile-img-11">
						<span class="tile-text">
						Каталог ламп
						</span>
					</a>
				</div>
				<?php
			}
			*/
			?>
		</div>
		</div>
		</div>
		<?php
	}
	?>
</div>
</div>
</div>
<?php
}
?>



<?php
if($DP_Content->main_flag)
{
	//API UCatalog
	echo '<div class="container">';
	require_once($_SERVER["DOCUMENT_ROOT"]."/api/UCatalog/ucatalog_index.php");
	echo '</div>';

	//Секция "Отправить запрос продавцу"
	if(isset($DP_Config->section_send_request) && $DP_Config->section_send_request)
	{
		echo '<br/>';
		require_once($_SERVER["DOCUMENT_ROOT"]."/content/general_pages/vin_zapros/section_vin_request.php");
	}
}
?>



<div id="sb-site">
<div class="boxed">



<?php
if( ! $DP_Content->main_flag)
{
?>
<div class="main-header">
	<div class="container">
		<div class="row">
			<div class="col-sm-12">
				<h1 class="page-title"><?php echo $DP_Content->value; ?></h1>
			</div>
			<div class="col-sm-12">
				<docpart type="module" name="bread_crumbs" />
			</div>
		</div>
	</div>
</div>
<?php
}
?>



<?php
if( ! isset($product_id) )
{
	$product_id = null;
}
if( $DP_Content->content_type == "category" || $DP_Content->url =="shop/search" || $DP_Content->id == 324 || $DP_Content->id == 326 || $DP_Content->id == 328 || $DP_Content->id == 330 || $DP_Content->id == 332 || $DP_Content->id == 334 || isset( $DP_Content->service_data["sp"] ))
{
	$left_col_class = " class=\"hidden-xs hidden-sm col-md-3\"";
	$right_col_class = " class=\"col-md-9\"";
	$btn_show_hide_left_coll = " class=\"hidden-md hidden-lg\"";
}
else
{
	$left_col_class = " class=\"hidden-xs hidden-sm hidden-md hidden-lg\"";
	$right_col_class = " class=\"col-md-12\"";
	$btn_show_hide_left_coll = " class=\"hidden-xs hidden-sm hidden-md hidden-lg\"";
}

// Для некоторых страниц нужно дополнительно добавить обертку
$add_row_div = 'style="margin:0;"';
if( $product_id > 0 || $DP_Content->content_type == "category" || $DP_Content->id == 298 || $DP_Content->id == 302 || $DP_Content->id == 376 || $DP_Content->id == 385 || $DP_Content->id == 385 || isset($DP_Content->service_data["article_search_chpu"]))
{
	$add_row_div = '';
}
?>

<div class="container">
    <div class="row">
		
		<div <?=$btn_show_hide_left_coll;?>>
			<div class="row" style="margin: 0px 0px 15px 0px;">
			<div class="col-xs-12">
				<a onClick="show_hide_left_coll();" style="text-decoration: none; background-color: #f9f9f9; border: 1px solid #ddd; color: #222; position: relative; padding: 5px 10px;"><i class="fa fa-filter" aria-hidden="true"></i> <span>Отобразить фильтры</span></a>
			</div>
			</div>
			<script>
			function show_hide_left_coll(){
				if ( $('#left_col').hasClass('hidden-xs')) {
					$('#left_col').removeClass('hidden-xs');
					$('#left_col').removeClass('hidden-sm');
				}else{
					$('#left_col').addClass('hidden-sm');
					$('#left_col').addClass('hidden-xs');
				}
			}
			</script>
		</div>
		
		<div <?php echo $left_col_class;?> id="left_col">
			<docpart type="module" name="left_menu" />
		</div>
		
		<div <?php echo $right_col_class;?> id="right_col">
            <div class="row mainContainer" id="Container">
				<?php
				//Получаем дополнительный текст для URL
				$text_before_main = "";//Если текст нужен до основного содержимого
				$text_after_main = "";//Если текст нужен после основного содержимого
				$url = getPageUrl();
				
				$stmt = $db_link->prepare('SELECT * FROM `text_for_url` WHERE `url` = :url;');
				$stmt->bindValue(':url', $url);
				$stmt->execute();
				$url_text_record = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if( $url_text_record != false )
				{
					if($url_text_record["before_main"] == 1)
					{
						$text_before_main = $url_text_record["content"];
					}
					else
					{
						$text_after_main = $url_text_record["content"];
					}
				}
				
				// Дополнительный текст страницы
				if($text_before_main != ''){
					echo "<div class=\"col-lg-12\"><br/>".$text_before_main."</div>";
				}
				?>
				
				<div class="row" <?=$add_row_div;?>>
				<div class="col-lg-12">	
				<docpart type="main" name="main" />
				</div>
				</div>
				
				<?php
				//Отображаем блок новостей на главной
				if($DP_Content->main_flag){
					
					$news_access = 1;
					$root_content = 311;// id корневого материала раздела Новости
					$news_count = 4;// Количество новостей для отображения
					
					// Проверим включен ли модуль новостей
					$query_news_module = $db_link->prepare("SELECT `activated` FROM `modules` WHERE `id` = ?;");
					$query_news_module->execute( array(49) );
					$news_module_row = $query_news_module->fetch();
					if($news_module_row['activated'] == 0)
					{
						$news_access = 0;
					}
					
					// Проверим что корневой материал новостей опубликован
					$stmt = $db_link->prepare('SELECT `published_flag` FROM `content` WHERE `id` = ?;');
					$stmt->execute(array($root_content));
					$news = $stmt->fetch(PDO::FETCH_ASSOC);
					if($news['published_flag'] == 0){
						$news_access = 0;
					}
					
					if($news_access === 1){
						
						$news_arr = array();
						
						//Получаем новости из БД
						$stmt = $db_link->prepare('SELECT `id`, `value`, `time_created`, `description_tag`, `url` FROM `content` WHERE `parent` = :parent AND `published_flag` = 1 ORDER BY `id` DESC LIMIT :limit;');
						$stmt->bindValue(':parent', (int)$root_content);
						$stmt->bindValue(':limit', (int)$news_count, PDO::PARAM_INT);
						$stmt->execute();
						while($news = $stmt->fetch(PDO::FETCH_ASSOC))
						{
							$news_arr[] = $news;
						}
						
						if(!empty($news_arr)){
							?>
							<div class="col-lg-12">
								<h2 class="section-title" onClick="location='/novosti';">Новости</h2>
							</div>
							
							<div class="news_box col-lg-12">
							<div class="row">
							<?php
							foreach($news_arr as $news){
								$news["img"] = '';
								if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/news/".$news["id"].".jpg")){
									$news["img"] = "/content/files/news/".$news["id"].".jpg";
								}else if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/news/".$news["id"].".png")){
									$news["img"] = "/content/files/news/".$news["id"].".png";
								}
							?>
								<div class="col-sm-6 col-md-3">
									<div class="news_item_box">
										<a href="<?php echo "/".$news["url"]; ?>">
											<div>
												<?php
												if($news["img"] == ''){
												?>
												<div class="news_item_img"><i style="color: <?=$DP_Template->data_value->news_color;?>; font-size: 85px; padding-top: 33px;" class="fa fa-picture-o" aria-hidden="true"></i></div>
												<?php
												}else{
												?>
												<div class="news_item_img" style="background:url('<?=$news["img"];?>') no-repeat; background-position: center;"></div>
												<?php
												}
												?>
												<div class="news_item_name"><?php echo $news["value"]; ?></div>
												<div class="news_item_text"><?php echo $news["description_tag"]; ?></div>
												<small class="news_item_clock"><i class="fa fa-clock-o" aria-hidden="true"></i> <?php echo date("d.m.Y", $news["time_created"]); ?></small>
											</div>
										</a>
									</div>
								</div>
							<?php
							}
							?>
							</div>
							</div>
							<?php
						}
					}
				}
				?>
				
				<?php
				// Дополнительный текст страницы
				if($text_after_main != ''){
					echo "<div class=\"col-lg-12\"><br/>".$text_after_main."</div>";
				}
				?>
				
			</div>
		</div>
	</div>
</div>



<aside id="footer-widgets" style="background: <?=$DP_Template->data_value->footer_bg;?>;">
    <div class="container">
        <div class="row">
			<div class="col-md-7">
               
				<div class="row">
					<div class="col-sm-4">
						<docpart type="module" name="footer-menu-1" />
					</div>
					<div class="col-sm-4">
						<docpart type="module" name="footer-menu-2" />
					</div>
					<div class="col-sm-4">
						<docpart type="module" name="footer-menu-3" />
					</div>
				</div>
				
				<h3 class="footer-widget-title">Отправить запрос</h3>
                <p>Если Вы не нашли нужные запчасти, или Вам требуется помощь в подборе,<br/>отправьте нам запрос - мы Вам поможем</p>
				<div class="input-group">
					<a class="btn btn-block btn-ar btn-primary" href="/zapros-prodavczu">Отправить запрос продавцу</a>
                </div>
				
            </div>
            
			<div class="col-md-1"></div>
            
			<div class="col-md-4">
				<div class="row">
					
					<div class="col-sm-6 col-md-12">
						<h3 class="footer-widget-title">Контакты</h3>
						<div><?='г. '.trim($customer_office_info['city']).' ул. '.trim($customer_office_info['address']);?></div>
						<div><?=trim($customer_office_info['phone']);?></div>
						<div><?=trim($customer_office_info['email']);?></div>
					</div>
					<div class="col-sm-6 col-md-12">
						<h3 class="footer-widget-title">Режим работы</h3>
						<div><?=trim(html_entity_decode($customer_office_info['timetable']));?></div>
					</div>
					
					<?php
					$data_value = (array) $DP_Template->data_value;
					if(!empty($data_value)){
						$pay_arr = array();
						foreach($data_value as $item_data_value_key => $item_data_value){
							if(strpos($item_data_value_key, 'pay_') === 0){
								if($item_data_value == 1){
									$pay_arr[] = str_replace('pay_','',$item_data_value_key);
								}
							}
						}
						if(!empty($pay_arr)){
							?>
							<div class="col-xs-12">
								<h3 class="footer-widget-title">Принимаем к оплате</h3>
								<div style="line-height:1em;">
									<?php
									foreach($pay_arr as $item_pay_name){
									?>
									<div class="footer_pay_box">
										<div class="footer_pay_logo" style="background:url('/content/files/images/icons/pay/<?=$item_pay_name;?>.jpg') no-repeat; background-position:center;"></div>
									</div>
									<?php
									}
									?>
								</div>
							</div>
							<?php
						}
					}
					?>
					
				</div>
            </div>
			
        </div> <!-- row -->
    </div> <!-- container -->
</aside> <!-- footer-widgets -->



<footer id="footer" style="position: relative; background: <?=$DP_Template->data_value->footer_bg;?>;">
	
	<p>&copy; <?php echo date("Y", time()); ?> <?php echo $DP_Config->site_name; ?></p>
	
	<div style="text-align: left; position: absolute; left: 6px; top: 2px;">
		<?php
		//Получаем ссылку на сайт разработчика (если хотите убрать ссылку, то, уберите тег A ниже)
		require_once($_SERVER['DOCUMENT_ROOT'].'/content/general/docpart_href.php');
		?>
		<a class="hidden-xs" title="<?php echo $docpart_title; ?>" target="_blank" href="<?php echo $docpart_href; ?>">
			<img style="background-color: #fff; border-radius: 2px; padding: 0px; position: absolute; top: 5px; left: 0px; height: 35px; border: 1px solid #ccc;" src="img/logo_footer_transparent.png" border="0"/>
		</a>
	</div>

	<div class="icons-holder hidden">

	</div>

	
</footer>

</div> <!-- boxed -->
</div> <!-- sb-site -->


<?php
// IFRAME :

// Если страница загружена в iframe - используется в панели управления, для работы проценки
if( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe' )
{
	
}
else
{
?>


<?php if($DP_Config->show_cookie) : ?>
<div id="cookie-message">
	<div class="cookie-inner">
		<p>Мы используем cookie. Это позволяет нам анализировать взаимодействие посетителей с сайтом и делать его лучше. Продолжая пользоваться сайтом, вы соглашаетесь с использованием файлов cookie.</p>
		<button class="btn btn-sm cookie-btn">Принять</button>
	</div>
</div>
<?php endif; ?>


<?php
}
?>




<?php
if($DP_Content->id == 349)
{
?>
<!-- Модальное Окно для страницы vin-запроса -->
<link href="/content/general_pages/vin_zapros/hystmodal.min.css" rel="stylesheet" type="text/css"/>
<script src="/content/general_pages/vin_zapros/hystmodal.min.js" ></script>
<div class="hystmodal" id="vinModal" aria-hidden="true">
	<div class="hystmodal__wrap">
		<div class="hystmodal__window" role="dialog" aria-modal="true">
			<button data-hystclose class="hystmodal__close">Закрыть</button>
			<!-- Ваш HTML код модального окна -->
			<div class="body_modal">
				<div class="text">
					<h4>Где найти?</h4>
					<p>Госномер вы найдёте в Свидетельстве о регистрации (СТС) в строке «Регистрационный знак», а VIN в строке «Идентификационный номер».</p>
					<p>Также эти данные можно найти в Паспорте транспортного средства (ПТС) и полисе ОСАГО.</p>
				</div>
				<div class="img"><img src="/content/general_pages/vin_zapros/vin.png" alt=""></div>
			</div>
		</div>
	</div>
</div>
<?php
}
?>




<?php
//Подключение скрипта нижней панели
require_once($_SERVER["DOCUMENT_ROOT"]."/modules/shop/bottom_panel/bottom_panel.php");
?>

<div id="back-top">
    <a href="#header"><i class="fa fa-chevron-up"></i></a>
</div>

<!-- Иконка корзины -->
<div id="shopCartIcon" class="shop-cart-icon" style="display: none;">
    <div class="shop-cart-icon__wrapper">
        <svg class="shop-cart-icon__svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0020 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
        </svg>
        <span id="shopCartCounter" class="shop-cart-icon__counter">0</span>
    </div>
</div>

<!-- Модальное окно корзины -->
<div id="shopCartModal" class="shop-cart-modal" style="display: none;">
    <div class="shop-cart-modal__overlay"></div>
    <div class="shop-cart-modal__content">
        <!-- Заголовок -->
        <div class="shop-cart-modal__header">
            <h2 class="shop-cart-modal__title">Корзина заказов</h2>
            <button id="shopCartClose" class="shop-cart-modal__close-btn">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                    <path d="M14 1.41L12.59 0 7 5.59 1.41 0 0 1.41 5.59 7 0 12.59 1.41 14 7 8.41 12.59 14 14 12.59 8.41 7z"/>
                </svg>
            </button>
        </div>

        <!-- Тело модального окна -->
        <div class="shop-cart-modal__body">
            <!-- Таблица позиций -->
            <div class="shop-cart-table">
                <table class="shop-cart-table__wrapper">
                    <thead class="shop-cart-table__header">
                        <tr class="shop-cart-table__header-row">
                            <th class="shop-cart-table__header-cell shop-cart-table__header-cell--name">Наименование</th>
                            <th class="shop-cart-table__header-cell shop-cart-table__header-cell--price">Цена</th>
                            <th class="shop-cart-table__header-cell shop-cart-table__header-cell--quantity">Количество</th>
                            <th class="shop-cart-table__header-cell shop-cart-table__header-cell--total">Итого</th>
                            <th class="shop-cart-table__header-cell shop-cart-table__header-cell--actions">Действия</th>
                        </tr>
                    </thead>
                    <tbody id="shopCartTableBody" class="shop-cart-table__body">
                        <!-- Строки будут добавляться динамически -->
                        <tr class="shop-cart-table__empty-row">
                            <td colspan="4" class="shop-cart-table__empty-cell">Корзина пуста</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Панель управления -->
            <div class="shop-cart-controls">
                <div class="shop-cart-controls__wrapper">
                    <button id="shopCartAddToOrder" class="shop-cart-controls__btn shop-cart-controls__btn--primary">
                        Добавить в заказ
                    </button>
                    <button id="shopCartContinueSearch" class="shop-cart-controls__btn shop-cart-controls__btn--secondary">
                        Продолжить поиск
                    </button>
                    <button id="shopCartModalClose" class="shop-cart-controls__btn shop-cart-controls__btn--tertiary">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/styleswitcher.js"></script>
<script>SyntaxHighlighter = {all: function(){return;}};</script>

<script async src="assets/js/app.js"></script>

<?php
if( ! $DP_Content->main_flag ){
?>

<script src="assets/js/DropdownHover.js"></script>
<script src="assets/js/holder.js"></script>
<script src="assets/js/commerce.js"></script>
<script src="assets/js/e-commerce_product.js"></script>

<?php
}
?>


<div id="calendar-overlay" class="calendar-overlay">
  <div id="calendar-modal" class="calendar-modal">
	<div style="flex: 1;">
	  <div class="calendar-header">
		<button id="prev-month" class="btn btn-light btn-sm">&lt;</button>
		<div id="calendar-month" style="font-weight:bold;"></div>
		<button id="next-month" class="btn btn-light btn-sm">&gt;</button>
	  </div>
	  <div class="calendar-grid"></div>
	</div>
	<div class="slots-container">
	  <h5 id="slots-header">Выберите день</h5>
	  <div id="slots-list" class="slots-list"></div>
	</div>
  </div>
</div> 
<style> 
 .calendar-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.3s ease forwards;
  }

  .calendar-modal {
    background: #fff;
    border-radius: 10px;
    width: 90%;
    max-width: 800px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: row;
    gap: 20px;
    animation: scaleIn 0.3s ease forwards;
  }
</style>


<!-- Модальное окно -->
<div class="cs-modal" id="csRecordModal">
  <div class="cs-modal-overlay"></div>
  <div class="cs-modal-content">
    <button class="cs-modal-close">&times;</button>
    <div class="cs-modal-header">
      <h3 class="cs-modal-title">Детали записи</h3>
      <div class="cs-modal-date" id="csModalDate"></div>
    </div>
    
    <div class="cs-modal-body">
      <!-- Информация о СТО -->
      <div class="cs-modal-section">
        <h4 class="cs-modal-section-title">СТО</h4>
        <div class="cs-modal-sto">
          <div class="cs-modal-sto-address" id="csModalAddress"></div>
          <div class="cs-modal-sto-hours">
            <span id="csModalHours"></span>
            <span id="csModalDays"></span>
          </div>
          <!-- Ссылка на карту -->
          <a class="cs-map-link" id="csMapLink" target="_blank" rel="noopener noreferrer">
            Посмотреть на карте
          </a>
        </div>
      </div>
      
      <!-- Информация о мастере -->
      <div class="cs-modal-section">
        <h4 class="cs-modal-section-title">Мастер</h4>
        <div class="cs-modal-master">
          <div class="cs-modal-master-name" id="csModalMasterName"></div>
          <div class="cs-modal-master-phone" id="csModalMasterPhone"></div>
        </div>
      </div>
      
      <!-- Услуги -->
      <div class="cs-modal-section">
        <h4 class="cs-modal-section-title">Услуги</h4>
        <div class="cs-modal-services" id="csModalServices"></div>
      </div>
    </div>
  </div>
</div>

<style>
.cs-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
}

.cs-modal.active {
  display: block;
}

.cs-modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
}

.cs-modal-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.cs-modal-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  color: #333;
  font-size: 24px;
  line-height: 1;
  cursor: pointer;
  z-index: 1;
  transition: background-color 0.2s;
}

.cs-modal-close:hover {
  background: rgba(0, 0, 0, 0.2);
}

.cs-modal-header {
  padding: 24px 24px 16px;
  border-bottom: 1px solid #e0e0e0;
}

.cs-modal-title {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 600;
  color: #1a1a1a;
}

.cs-modal-date {
  font-size: 14px;
  color: #666;
}

.cs-modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}
</style>

</body>
</html>