<?php
/**
 * Страница управления операциями в разделе Наличные магазина
 * 
 * Данная страница предназначена для создания приходных / расходных операций в разделе Наличные магазина
*/
defined('_ASTEXE_') or die('No access');
//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
//Проверяем право менеджера
if( ! DP_User::isAdmin() )
{
	$result["status"] = false;
	$result["message"] = "Forbidden";
	$result["code"] = 501;
	exit(json_encode($result));//Вообще не является администратором бэкенда
}
//Менеджер
$user_session = DP_User::getAdminSession();
$manager_id = DP_User::getAdminId();
//Получаем список магазинов в которых данный пользователь указан в качестве менеджера
$manager_offices_list = array();
$manager_id_offices_list = array();
$query = $db_link->prepare('SELECT `id`, `caption` FROM `shop_offices` WHERE `users` LIKE ?;');
$query->execute( array('%"'.$manager_id.'"%') );
while($row = $query->fetch(PDO::FETCH_ASSOC)){
	$manager_offices_list[$row['id']] = $row;
	$manager_id_offices_list[] = $row['id'];
}
if( ! empty($_POST["action"]))
{
	// -------------------------------------------------------------------------------
	//Защита от CSRF-атак
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
	// -------------------------------------------------------------------------------
	$office_id = ((int) $_POST['office_id']);
	if( ! isset($manager_offices_list[$office_id]) )
	{
		$location_url = '/'.$DP_Config->backend_dir.'/shop/cash';
		?>
		<script>
			location="<?=$location_url?>&error_message=<?php echo urlencode('Нет магазина, либо вы не указаны в качестве менеджера.'); ?>";
		</script>
		<?php
		exit;
	}
	else
	{
		switch($_POST["action"]){
			case 'add' :
				if( (int)$_POST["income"] > 0 ){
					$income = 1;
				}else{
					$income = 0;
				}
				$comment = trim($_POST["comment"]);
				$comment = htmlentities(str_replace(array("\"", "\\", "'", "\r", "\t"), "", $comment), ENT_QUOTES, "UTF-8");
				$comment = str_replace("\n","<br/>",$comment);
				$payment_type = $_POST["payment_type"];
				$amount = (float) $_POST["amount"];
				if($amount <= 0){
					$location_url = '/'.$DP_Config->backend_dir.'/shop/cash?office_id='.$office_id;
					?>
					<script>
						location="<?=$location_url?>&error_message=<?php echo urlencode('Сумма операции должна быть больше нуля.'); ?>";
					</script>
					<?php
					exit;
				}
				$name = (int) trim($_POST["name"]);
				$query = $db_link->prepare('SELECT `id` FROM `shop_offices_cash_codes` WHERE `income` = ? AND `id` = ?;');
				$query->execute( array($income, $name) );
				$row = $query->fetch(PDO::FETCH_ASSOC);
				if( empty($row) )
				{
					$location_url = '/'.$DP_Config->backend_dir.'/shop/cash?office_id='.$office_id;
					?>
					<script>
						location="<?=$location_url?>&error_message=<?php echo urlencode('Операция с таким названием не существует.'); ?>";
					</script>
					<?php
					exit;
				}
				else
				{
					if( $db_link->prepare('INSERT INTO `shop_offices_cash`(`id`, `office_id`, `manager_id`, `time`, `income`, `amount`, `operation_code`, `comment`, `payment`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?);')->execute( array($office_id, $manager_id, time(), $income, $amount, $name, $comment, $payment_type) ) != true )
					{
						$location_url = '/'.$DP_Config->backend_dir.'/shop/cash?office_id='.$office_id;
						?>
						<script>
							location="<?=$location_url?>&error_message=<?php echo urlencode('Не удалось добавить запись.'); ?>";
						</script>
						<?php
						exit;
					}
					else
					{
						$location_url = '/'.$DP_Config->backend_dir.'/shop/cash?office_id='.$office_id;
						?>
						<script>
							location="<?=$location_url?>&success_message=<?php echo urlencode('Запись добавлена.'); ?>";
						</script>
						<?php
						exit;
					}
				}
			break;
			default :
				$location_url = '/'.$DP_Config->backend_dir.'/shop/cash';
				?>
				<script>
					location="<?=$location_url?>&error_message=<?php echo urlencode('Неизвестная операция.'); ?>";
				</script>
				<?php
				exit;
			break;
		}
	}
}
else//Действий нет - выводим страницу
{
	require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий

	?>

			<div class="col-lg-12">
				<div class="hpanel">
					<div class="panel-heading hbuilt">
						Настройки финансовых операций
					</div>
					<div class="panel-body">
						<?php
							print_backend_button(array("background_color"=>"#3498db", "fontawesome_class"=>"fas fa-align-justify", "caption"=>"Редактор видов операций", "url"=>"/".$DP_Config->backend_dir."/shop/cash/operations_editor"));
						?>
					</div>
				</div>
			</div>
	
	<?php


	//Выбран ли магазина
	if( ((int) $_GET['office_id']) > 0 )
	{
		//var_dump(DP_User::addCashOperation('3_pay_for_order', '2500.1234'));
		//var_dump(DP_User::addCashOperation('6_pay_for_order', '1000'));
		//var_dump(DP_User::addCashOperation('3_pay_for_order', '41236.1234'));
		//var_dump(DP_User::getDefaultSessionData());
		//var_dump(DP_User::getDefaultSessionData());

		if( ! isset($manager_offices_list[$_GET['office_id']]) ){
			echo 'Нет магазина, либо вы не указаны в качестве менеджера.';
		}
		else
		{
			$office_id = ((int) $_GET['office_id']);
			$select_payment_type = $db_link->prepare("SELECT `name` FROM `shop_offices_cash_codes` WHERE `office_id` = ?;");
			$select_payment_type->execute([$_GET["office_id"]]);
			$select_payment_type_res = $select_payment_type->fetchAll(PDO::FETCH_ASSOC);
			$managers = [0 => 'Менеджер не закреплен'];
			$select_managers = $db_link->prepare("SELECT `user_id` FROM `users_groups_bind` WHERE `group_id` = ?;");
			$select_managers->execute(array(3));
			$select_managers_res = $select_managers->fetchAll(PDO::FETCH_ASSOC);

			// $select_managers_res = json_decode($select_managers_res["users"], true);
			foreach ($select_managers_res as $manager_id_info) {
				$manager_id = $manager_id_info['user_id'];
				$profile = DP_User::getUserProfileById($manager_id);
				$managers[$manager_id] = $manager_id . ', ' . $profile["surname"] . ' ' . $profile["name"] . ' ' . $profile["patronymic"];
			}

		

		?>
			<link rel="stylesheet" href="<?= $DP_Config->domain_path . $DP_Config->backend_dir; ?>/content/shop/logistics/manager_sessions/styles.css">
			<link rel="stylesheet" href="<?= $DP_Config->domain_path . $DP_Config->backend_dir; ?>/content/shop/logistics/cash_assets/main.css?v=1.0">
			<div class="col-lg-12">
				<div class="hpanel">
					<div class="panel-heading hbuilt">
						Действия
					</div>
					<div class="panel-body">
						<?php
						if(count($manager_offices_list) > 1)
						{
							print_backend_button(array('background_color'=>'#b9babb', 'fontawesome_class'=>'fas fa-chevron-left', 'caption'=>'Назад', 'url'=>$DP_Config->domain_path.$DP_Config->backend_dir.'/shop/cash'));
						}
						?>
						<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
							<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
							<div class="panel_a_caption">Выход</div>
						</a>
					</div>
				</div>
			</div>
			<div class="col-lg-12">
				<div class="hpanel">
					<div class="panel-heading hbuilt">
						Смена
					</div>
					<div class="panel-body">
						<div id="status">Проверка статуса смены...</div>
						<div id="sessionStart" style="display: none;">Начало смены: <span id="sessionStartTime"></span></div>
						<div id="timerContainer">
							<div id="timeText" class="time-display">0 ч 0 мин 0 сек</div>
						</div>
						<div style="text-align:center;">
							<button id="openSessionBtn" class="sessionButton">Открыть смену</button>
							<button id="closeSessionBtn" class="sessionButton">Закрыть смену</button>
						</div>
						<input type="hidden" id="sessionId" value="">
						<div id="hiddenBaseUrl" data-base-url="<?= $DP_Config->domain_path ?><?= $DP_Config->backend_dir ?>/content/shop/logistics"></div>
						<input type="hidden" value="<?=DP_User::getAdminId();?>" id="manager_id" />
						<input type="hidden" value="<?=$office_id;?>" id="office_id" />
					</div>
				</div>
			</div>
			<!-- Подключение JavaScript -->
			<script src="<?= $DP_Config->domain_path . $DP_Config->backend_dir; ?>/content/shop/logistics/manager_sessions/script.js?v=1.0"></script>
			<script src="<?= $DP_Config->domain_path . $DP_Config->backend_dir; ?>/content/shop/logistics/cash_assets/main.js?v=1.0"></script>
			<div class="col-lg-12">
				<div class="hpanel" data-office-id="<?= (int) $_GET['office_id']; ?>">
				<?php require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/logistics/cash_assets/cash_result_module.php"); ?>
				</div>
			</div>
			<div class="col-lg-12">
				<div class="hpanel js-cashModule" id="cash_stat_module" data-office-id="<?= (int) $_GET['office_id']; ?>">
				<?php require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/logistics/cash_assets/cash_stat_module.php"); ?>
				</div>
			</div>
			<div class="col-lg-6">
				<form method="POST">
					<div class="hpanel">
						<div class="panel-heading hbuilt">
							Приходная операция магазина
						</div>
						<div class="panel-body">
							<div class="table-responsive">
								<input type="hidden" name="office_id" value="<?php echo $office_id; ?>" />
								<input type="hidden" name="action" value="add" />
								<input type="hidden" name="income" value="1" />
								<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
								<label>Сумма</label>
								<input class="form-control" type="number" name="amount" placeholder="0" value="" />
								<br/>
								<label>Название</label>
								<select placeholder="Название операции" name="name" class="form-control">
									<?php
									$query = $db_link->prepare('SELECT * FROM `shop_offices_cash_codes` WHERE `income` = 1;');
									$query->execute( array($office_id) );
									while($row = $query->fetch(PDO::FETCH_ASSOC))
									{
										?>
										<option value="<?php echo $row['id']; ?>" ><?php echo $row["name"]; ?></option>
										<?php
									}
									?>
								</select>
								<br/>
								<label>Комментарий</label>
								<textarea class="form-control" name="comment" placeholder="Комментарий к операции"></textarea>
								<br/>
								<label>Оплата</label>
								<select class="form-control" name="payment_type">
									<option value="1">Наличные</option>
									<option value="2">Безнал</option>
								</select>
							</div>
						</div>
						<div class="panel-footer">
							<button class="btn btn-ar btn-success" type="submit">Создать</button>
						</div>
					</div>
				</form>
			</div>
			<div class="col-lg-6">
				<form method="POST">
					<div class="hpanel">
						<div class="panel-heading hbuilt">
							Расходная операция магазина
						</div>
						<div class="panel-body">
							<div class="table-responsive">
								<input type="hidden" name="office_id" value="<?php echo $office_id; ?>" />
								<input type="hidden" name="action" value="add" />
								<input type="hidden" name="income" value="0" />
								<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
								<label>Сумма</label>
								<input class="form-control" type="number" name="amount" placeholder="0" value="" />
								<br/>
								<label>Название</label>
								<select placeholder="Название операции" name="name" class="form-control">
									<?php
									$query = $db_link->prepare('SELECT * FROM `shop_offices_cash_codes` WHERE `income` = 0;');
									$query->execute( array($office_id) );
									while($row = $query->fetch(PDO::FETCH_ASSOC))
									{
										?>
										<option value="<?php echo $row['id']; ?>" ><?php echo $row["name"]; ?></option>
										<?php
									}
									?>
								</select>
								<br/>
								<label>Комментарий</label>
								<textarea class="form-control" name="comment" placeholder="Комментарий к операции"></textarea>
								<br/>
								<label>Оплата</label>
								<select class="form-control" name="payment_type">
									<option value="1">Наличные</option>
									<option value="2">Безнал</option>
								</select>
							</div>
						</div>
						<div class="panel-footer text-right">
							<button class="btn btn-ar btn-danger" type="submit">Создать</button>
						</div>
					</div>
				</form>
			</div>
			<div class="col-lg-12">
				<div class="hpanel">
					<div class="panel-heading hbuilt">
						Фильтры
					</div>
					<div class="panel-body">
					<div class="filter_wrapper">
						<div class="filter-container">
							<label for="idInput">ID:</label>
							<input type="text" id="idInput" class="form-control">
						</div>
						<div class="filter-container">
							<label for="managerInput">Менеджер:</label>
							<select id="managerInput"  multiple="multiple" >
								<?php foreach ($managers as $manager) : ?>
									<option value="<?=$manager; ?>"><?=$manager; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="filter-container">
							<label for="nameInput">Вид операции:</label>
							<select id="nameInput"  multiple="multiple" >
								<?php
									foreach ($select_payment_type_res as $payment) :
								?>
									<option value="<?=$payment["name"];?>"><?=$payment["name"]; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="filter-container">
							<label for="paymentInput">Оплата:</label>
							<select id="paymentInput" multiple="multiple">
								<option value="">Все</option>
								<option value="Наличные">Наличные</option>
								<option value="Безнал">Безнал</option>
							</select>
						</div>
						<div class="filter-container">
							<label for="dateFromInput">Дата с:</label>
							<div class="input-group date">
								<div style="position:relative;height:34px;">
									<input style="position:absolute; z-index:2; opacity:0;" type="text"  id="datetimepickerFrom" class="form-control" />
									<input style="position:absolute; z-index:1;" type="text" id="datetimepickerFrom_show" class="form-control" />
								</div>
							</div>
						</div>
						<div class="filter-container">
							<label for="dateToInput">Дата по:</label>
							<div class="input-group date">
								<div style="position:relative;height:34px; ">
									<input style="position:absolute; z-index:2; opacity:0;" type="text"  id="datetimepickerTo" class="form-control" />
									<input style="position:absolute; z-index:1;" type="text" id="datetimepickerTo_show" class="form-control" />
								</div>
							</div>
						</div>
						<div class="filter-container">
							<label for="amountInputFrom">Сумма от:</label>
							<input type="number" id="amountInputFrom" class="form-control">
						</div>
						<div class="filter-container">
							<label for="amountInputTo">Сумма до:</label>
							<input type="number" id="amountInputTo" class="form-control">
						</div>
						<div class="filter-container">
							<label for="commentInput">Комментарий:</label>
							<input type="text" id="commentInput" class="form-control">
						</div>
					</div>
					</div>
					<div class="panel-footer text-right">
						<div class="act_filter_buttons">
							<button class="btn btn-primary mt-3" onclick="filterTable(true)">Отфильтровать</button>
							<button class="btn btn-secondary mt-3" onclick="resetFilters()" style="border: 1px solid;">Снять фильтры</button>
						</div>	
					</div>
				</div>
			</div>
			<div class="col-lg-12">
				<div class="hpanel">
					<div class="panel-heading hbuilt">
						Все операции магазина
					</div>
					<div class="panel-body">
						<div class="table-responsive">
							<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped" id="operationsTable">
								<thead>
									<tr>
										<th class="td-id" onclick="sortTable(0)">ID</th>
										<th class="td-manager" onclick="sortTable(1)">Менеджер</th>
										<th class="td-date" onclick="sortTable(2)">Дата</th>
										<th class="td-name" onclick="sortTable(3)">Название</th>
										<th class="td-amount" onclick="sortTable(4)">Сумма</th>
										<th class="td-content-m" onclick="sortTable(5)">Оплата</th>
										<th class="td-amount" onclick="sortTable(6)">Заказ</th>
										<th class="td-comment" onclick="sortTable(7)">Комментарий</th>
									</tr>
								</thead>
								<tbody>
									<?php
									// Подключение к базе данных и получение данных
									$query = $db_link->prepare('SELECT *, (SELECT `name` FROM `shop_offices_cash_codes` WHERE `id` = `shop_offices_cash`.`operation_code`) AS "name" FROM `shop_offices_cash` WHERE `office_id` = ? ORDER BY `id` DESC;');
									$query->execute(array($office_id));
									while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

										$payment_str = '';
										if ($row["payment"] == '1')
											$payment_str = 'Наличные';
										else if ($row["payment"] == '2')
											$payment_str = 'Безнал';

										$background_color = $row['income'] == 1 ? '#edf1c8' : '#f7d9d7';
										?>
										<tr style="background:<?php echo $background_color; ?>">
											<td class="id td-id"><?php echo $row["id"]; ?></td>
											<td class="manager td-manager"><?php echo $managers[$row["manager_id"]]; ?></td>
											<td class="date td-date"><?php echo date('d.m.Y H:i', $row["time"]); ?></td>
											<td class="name td-name"><?php echo $row["name"]; ?></td>
											<td class="amount td-amount" data-income="<?=$row['income'];?>"><?php echo $row["amount"]; ?></td>
											<td class="payment td-content-m"><?php echo $payment_str; ?></td>
											<td class="td-amount"><?= $row["order_id"] ? "<a href=\"/" .$DP_Config->backend_dir. "/shop/orders/order?order_id=" . $row["order_id"] . "\" target=\"_blank\" >" .$row["order_id"]. "</a>" : '' ;?></td>
											<td class="comment td-comment"><?php echo $row["comment"]; ?></td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<div class="total_sum" style="font-size: 20px;" id="total_sum"></div>
						 <div class="pagination" id="pagination"></div>
					</div>
				</div>
			</div>

			<style>
				.table .td-id {
					width: 50px;
					text-align: center;
				}
				.table .td-amount {
					width: 90px;
					text-align: center;
				}
				.table .td-name {
					width: 200px;
					text-align: center;
				}
				.table .td-content-m {
					padding: 5px 20px;
					text-align: center;
				}
				.table .td-manager {
					width: 200px;
					text-align: center;
				}
				.table .td-date {
					width: 140px;
					text-align: center;
				}
				.main-cash__stats {
					width: 100%;
					display: flex;
			    flex-wrap: wrap;
			    justify-content: center;
				}
				.main-cash__stat {
				  text-align: center;
					padding: 10px;
					font-size: 14px;
					border: 2px solid #ccc;
			    border-radius: 8px;
					margin: 4px;
					display: flex;
			    flex-wrap: wrap;
			    align-items: center;
			    justify-content: center;
					min-height: 70px;
					width: 100%;
				}
				.main-cash__title {
			    font-weight: 700;
			    font-size: 14px;
					color: #838383;
			    text-align: center;
			    text-transform: uppercase;
					border: 0;
				}
				.main-cash__income .main-cash__stat {
				  border-color: #62cb31;
				}
				.main-cash__expense .main-cash__stat {
					border-color: #e74c3c;
				}
				.main_cash__typeIncome .main-cash__stat--total {
					font-weight: 700;
			    border-color: #ccc;
			    background-color: transparent;
			    border: 0;
			    border-top: 1px dashed;
			    border-radius: 0;
			    text-align: right;
					min-height: 0;
					padding: 10px 0;
				}
				.main_cash__typeIncome .main-cash__stat--total .main-cash__name {
					font-weight: 400;
				}
				.main-cash__name {
				 font-size: 12px;
				 flex: 0 0 100%;
				 margin-bottom: 6px;
				 line-height: 1.2;
				}
				.main-cash__amount {
			    font-weight: 700;
			    font-size: 16px;
					margin-top: auto;
					white-space: nowrap;
					width: 100%;
					display: block;
				}
				.main-cash__stats__outer {
			    display: flex;
			    align-items: flex-start;
					margin-bottom: 30px;
				}
				.main-cash__stats__outer .main-cash__title {
					font-weight: 900;
			    font-size: 10px;
			    color: #757575;
			    letter-spacing: 0.5px;
			    text-transform: uppercase;
				}
				.main_cash__typeIncome {
					flex: 1;
				}
				.main-cash__payment {
					width: 100%;
					font-size: 13px;
					font-weight: 700;
					margin-top: 6px;
				}
				.main-cash__total {
				  margin-bottom: 30px;
				}
			</style>

		<?php
		}
	}
	else
	{
		//Отображаем список магазинов для которых данный пользователь является менеджером
		if( empty($manager_offices_list) )
		{
			echo 'Нет магазина, для которого вы указаны в качестве менеджера.';
		}
		else
		{
		?>
			<?php
			if(count($manager_offices_list) === 1)
			{
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/cash?office_id=<?php echo array_shift($manager_offices_list)['id']; ?>";
			</script>
			<?php
			}
			?>
			<div class="col-lg-12">
				<div class="hpanel">
					<div class="panel-heading hbuilt">
						Таблица магазинов
					</div>
					<div class="panel-body">
						<div class="table-responsive">
							<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
								<thead> 
									<tr>
										<th>ID</th>
										<th>Название</th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach($manager_offices_list as $item_office)
									{
										$a_item = "<a href=\"".$DP_Config->domain_path.$DP_Config->backend_dir."/shop/cash?office_id=".$item_office["id"]."\">";
									?>
										<tr>
											<td><?php echo $a_item.$item_office["id"]; ?></a></td>
											<td><?php echo $a_item.$item_office["caption"]; ?></a></td>
										</tr>
									<?php
									}//foreach
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		<?php
		}
	}
}
?>