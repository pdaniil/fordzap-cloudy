<?php
defined('_ASTEXE_') or die('No access');


//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getUserSession();


//Для уникальности id элементов используемых в форме
if( ! isset($login_form_postfix) ){
	$login_form_postfix = '';
}
if( ! isset($login_form_count) ){
	$login_form_count = 0;
}
$login_form_count++;
$login_form_postfix = $login_form_postfix .'_'. $login_form_count;


if( DP_User::getUserId() == 0 )
{
?>
	<div class="row login_form">
		<div class="col-lg-12">
		
			<div class="panel-heading">Форма входа на сайт</div>
		
		
			<?php
			//Доступные типы авторизации
			$arr_auth_type = array();
			if($DP_Config->simple_register_available)
			{
				$arr_auth_type[] = array('key'=>'code', 'caption'=>'По коду');
			}
			$arr_auth_type[] = array('key'=>'pass', 'caption'=>'По паролю');
			?>
		
			
			<!-- Nav tabs auth -->
			<ul class="nav nav-tabs <?php echo (count($arr_auth_type) <= 1)?'hidden':''; ?>" style="padding: 0px; margin: 0; margin-top: -2px; background: #f1f3f4;">
				<?php
				$first = true;
				foreach( $arr_auth_type as $auth_type )
				{
					$active = "";
					if($first)
					{
						$active = "active";
						$first = false;
					}
					?>
					<li style="border-radius: 0; border-left: 0; border-right: 0;" class="<?php echo $active; ?>"><a style="padding: 3px 15px; margin: 0;" href="#auth_type_tab_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>" data-toggle="tab"><?php echo $auth_type["caption"]; ?></a></li>
					<?php
				}
				?>
			</ul>
			
			<!-- Tab panes auth -->
			<div class="tab-content" style="padding: 0px;">
				<?php
				$first = true;
				foreach( $arr_auth_type as $auth_type )
				{
					$active = "";
					if($first)
					{
						$active = "active";
						$first = false;
					}
					?>
					<div class="tab-pane <?php echo $active; ?>" id="auth_type_tab_<?php echo $auth_type["key"]; ?>_<?php echo $login_form_postfix; ?>">
						<div class="row">
							<div class="col-lg-12">
								<?php
								$file_path = $_SERVER["DOCUMENT_ROOT"]."/modules/login/".$auth_type["key"]."/app.php";
								if(file_exists($file_path)){
									require($file_path);
								}
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
else
{
?>
	<div class="panel-heading">Личный кабинет</div>
	<div class="panel-body" style="color:#777;">
		<form method="POST" name="auth_form<?php echo $login_form_postfix; ?>">
			<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
			<input type="hidden" name="logout" value="true"/>
			<div class="form-group">
				<?php if (DP_User::isUserServiceMaster(DP_User::getUserId())) : ?>
				<a href="/worker_salary?period=month" class="btn btn-ar btn-success" style="color: #468d46 !important; text-align: center; font-weight: bold;">Мои финансы</a>
				<a href="https://fordzap.com/content/carservice/admin_console/index.php" class="btn btn-ar btn-success" style="color: #468d46 !important; text-align: center; font-weight: bold;">Мои работы</a>
				<?php endif; ?>
				<a href="/users/profile" class="btn btn-ar btn-success btn_profile" style="color:#FFF;">Мои данные</a>
				
				<hr class="dotted margin-10">
				
				<a href="/shop/orders" class="btn btn-ar btn-warning btn_orders" style="color:#FFF; ">Заказы</a>
                <a href="/shop/returns/returns_list" class="btn btn-ar btn-warning btn_return" style="color:#FFF;">Возвраты</a>
				<a href="/shop/cart" class="btn btn-ar btn-warning btn_cart" style="color:#FFF;">Корзина</a>
				<a href="/garazh" class="btn btn-ar btn-warning btn_garazh" style="color:#FFF;">Гараж</a>
				<a href="/avtoservis" class="btn btn-ar btn-warning btn_autoservice" style="color:#FFF;">Автосервис</a>
				<a href="/requests" class="btn btn-ar btn-warning btn_requests" style="color:#FFF;">Запросы</a>
				<a href="/garazh/bloknot?garage=0" class="btn btn-ar btn-warning btn_bloknot" style="color:#FFF;">Блокнот</a>
				<a href="/shop/balans" class="btn btn-ar btn-warning btn_balans" style="color:#FFF;">Баланс</a>
				
				<hr class="dotted margin-10">
				
				<a href="javascript:void(0);" onclick="forms['auth_form<?php echo $login_form_postfix; ?>'].submit();" class="btn btn-ar btn-danger btn_exit" style="color:#FFF;">Выйти</a>
				
				<div class="clearfix"></div>
			</div>
		</form>
	</div>
<?php
}
?>
<style>
.no-auth select
{
	border-radius:0 !important;
}
.auth-contact-methods-header
{
	background: none;
	color: #999;
	border: 1px solid #999;
	border-radius: 3px;
	margin: 0px 5px;
	text-decoration: none;
	padding: 2px 20px;
	cursor: pointer;
}
.auth-contact-methods-header.active
{
	border: 1px solid #555555;
	background: #555555;
	color: #fff;
}

.login_form li > a:hover{
	background:none;
	border-radius:0;
}

@media (max-width: 767px)
{
	.login_form .nav-tabs:before{
		display:none;
	}
	.login_form .tab-content .input-group {
		background: none;
		border: none;
		border-radius: 0 !important;
		padding-left: 0px; 
		padding-right: 0px; 
		
		position: relative;
		display: table;
		border-collapse: separate;
	}
	.login_form .btn-ar {
		margin-bottom: 3px;
	}
}
</style>





<?php
if($DP_Template->id == 59){
// Expan
?>
<style>
.login_form .nav-tabs + .tab-content
{
	padding: 0px;
    border: none;
}
.dropdown-search-box, .dropdown-login-box{
	padding: 0;
}
.panel-heading 
{
    background: #fff;
}
.dropdown-menu .active > a{
	background:#fff;
}
.login_form .panel-body {
    padding: 15px;
    background: #fff;
}
.login_form .tab-content>.active {
    display: block;
    background: #fff;
}
</style>
<script>
	$(document).on('click', '.dropdown-menu', function (e) {
		e.stopPropagation();// Что бы окно формы входа не закрывалась при клике или смене типа авторизации
	});
</script>
<?php
}// end Expan
?>





<?php
if($DP_Template->id == 61){
// Limo
?>
<style>
.login_form .nav-tabs + .tab-content
{
	padding: 0px;
	padding: 15px !important;
    border: 1px solid #ddd;
	border-top:0;
	position: relative;
    top: -1px;
}
</style>
<?php
}// end Limo
?>





<?php
if($DP_Template->id == 62){
// Limo
?>
<style>
.header-user-box .login_form .nav-tabs + .tab-content
{
	padding-top: 15px !important;
}
.login_form .nav-tabs + .tab-content
{
	padding: 0px;
	border:0;
}
.login_form .nav-tabs li a
{
	border:0 !important;
}
</style>
<script>
	$(document).on('click', '.dropdown-menu', function (e) {
		e.stopPropagation();
	});
</script>
<?php
}// end Limo
?>




