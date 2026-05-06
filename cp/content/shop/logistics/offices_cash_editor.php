<?php
/**
 * Страница управления - Коды операций для shop_offices_cash
 * 
 * Данная страница предназначена для создания приходных / расходных наименований для операций в разделе Наличные магазина
*/
defined('_ASTEXE_') or die('No access');
//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
//Проверяем право менеджера
if(!DP_User::isAdmin() )
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
$query = $db_link->prepare('SELECT `id`, `caption` FROM `shop_offices` WHERE `users` LIKE ?;');
$query->execute( array('%"'.$manager_id.'"%') );
while($row = $query->fetch(PDO::FETCH_ASSOC)){
	$manager_offices_list[$row['id']] = $row;
}
if( ! empty($_POST["action"]) )
{
	// -------------------------------------------------------------------------------
	//Защита от CSRF-атак
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
	// -------------------------------------------------------------------------------
	switch($_POST["action"]){
		case 'add' :
			if( (int)$_POST["income"] > 0 ){
				$income = 1;
			}else{
				$income = 0;
			}
			$name = trim($_POST["name"]);
			$name = htmlentities(str_replace(array("\"", "\\", "'", "\n", "\r", "\t"), "", $name), ENT_QUOTES, "UTF-8");
			$query = $db_link->prepare('SELECT `id` FROM `shop_offices_cash_codes` WHERE `income` = ? AND `name` = ?;');
			$query->execute( array($income, $name) );
			$row = $query->fetch(PDO::FETCH_ASSOC);
			if(!empty($row))
			{
				$location_url = '/'.$DP_Config->backend_dir.'/shop/cash/operations_editor';
				?>
				<script>
					location="<?=$location_url?>?error_message=<?php echo urlencode('Операция с таким названием уже существует.'); ?>";
				</script>
				<?php
				exit;
			}
			else
			{
				if( $db_link->prepare('INSERT INTO `shop_offices_cash_codes` (`id`, `income`, `name`) VALUES (NULL, ?, ?);')->execute( array($income, $name) ) != true )
				{
					$location_url = '/'.$DP_Config->backend_dir.'/shop/cash/operations_editor';
					?>
					<script>
						location="<?=$location_url?>?error_message=<?php echo urlencode('Не удалось добавить данные.'); ?>";
					</script>
					<?php
					exit;
				}
				else
				{
					$location_url = '/'.$DP_Config->backend_dir.'/shop/cash/operations_editor';
					?>
					<script>
						location="<?=$location_url?>?success_message=<?php echo urlencode('Запись добавлена.'); ?>";
					</script>
					<?php
					exit;
				}
			}
		break;
		case 'del' :
			$id = (int) trim($_POST["id"]);
			$query = $db_link->prepare('SELECT `id` FROM `shop_offices_cash_codes` WHERE `id` = ?;');
			$query->execute( array($id) );
			$row = $query->fetch(PDO::FETCH_ASSOC);
			if(empty($row))
			{
				$location_url = '/'.$DP_Config->backend_dir.'/shop/cash/operations_editor';
				?>
				<script>
					location="<?=$location_url?>?error_message=<?php echo urlencode('Операция не существует.'); ?>";
				</script>
				<?php
				exit;
			}
			else
			{
				$query = $db_link->prepare('SELECT `id` FROM `shop_offices_cash` WHERE `operation_code` = ?;');
				$query->execute( array($id) );
				$row = $query->fetch(PDO::FETCH_ASSOC);
				if(!empty($row))
				{
					$location_url = '/'.$DP_Config->backend_dir.'/shop/cash/operations_editor';
					?>
					<script>
						location="<?=$location_url?>?error_message=<?php echo urlencode('Наименование связано с созданной финансовой операцией. Удалить невозможно.'); ?>";
					</script>
					<?php
					exit;
				}else{
					if( $db_link->prepare('DELETE FROM `shop_offices_cash_codes` WHERE `id` = ?;')->execute( array($id) ) != true )
					{
						$location_url = '/'.$DP_Config->backend_dir.'/shop/cash/operations_editor';
						?>
						<script>
							location="<?=$location_url?>?error_message=<?php echo urlencode('Не удалось удалить данные.'); ?>";
						</script>
						<?php
						exit;
					}
					else
					{
						$location_url = '/'.$DP_Config->backend_dir.'/shop/cash/operations_editor';
						?>
						<script>
							location="<?=$location_url?>?success_message=<?php echo urlencode('Запись удалена.'); ?>";
						</script>
						<?php
						exit;
					}
				}
			}
		break;
		default :
			$location_url = '/'.$DP_Config->backend_dir.'/shop/cash';
			?>
			<script>
				location="<?=$location_url?>?error_message=<?php echo urlencode('Неизвестная операция.'); ?>";
			</script>
			<?php
			exit;
		break;
	}
}
else//Действий нет - выводим страницу
{
	require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий

	$select_default_codes = $db_link->prepare("SELECT * FROM `shop_accounting_codes`;");
	$select_default_codes->execute();
	$res_codes = $select_default_codes->fetchAll(PDO::FETCH_ASSOC);
	$office_id = ((int) $_GET['office_id']);
?>
	<script>
	function temporarilyDisableOnChange(id) {
		const element = document.getElementById('default_accounting_codes_' + id);
		element.onchange = null; // Отключаем событие
	}
	// Функция для восстановления события onchange
	function restoreOnChange(id) {
		const element = document.getElementById('default_accounting_codes_' + id);
		element.onchange = function() {
			updateAccountingCode(id);
		}; // Восстанавливаем событие
	}
	</script>
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<?php
				print_backend_button(array('background_color'=>'#b9babb', 'fontawesome_class'=>'fas fa-chevron-left', 'caption'=>'Назад', 'url'=>$DP_Config->domain_path.$DP_Config->backend_dir.'/shop/cash?office_id='.$office_id));
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
				Приходные операции для всех магазинов
			</div>
			<div class="panel-body">
				<div class="table-responsive" style="overflow-x: clip;">
					<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
						<thead> 
							<tr style="background:#edf1c8;">
								<th>Название</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td colspan="3">
									<form method="POST">
										<input type="hidden" name="action" value="add" />
										<input type="hidden" name="income" value="1" />
										<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
										<div class="input-group" style="margin-bottom: 30px; margin-top: 10px;">
											<input value="" type="text" class="form-control" placeholder="Название операции" name="name" />
											<span class="input-group-btn">
												<button class="btn btn-ar btn-success" type="submit">Создать</button>
											</span>
										</div>
									</form>
								</td>
							</tr>
							<?php
							$query = $db_link->prepare('SELECT * FROM `shop_offices_cash_codes` WHERE `income` = 1;');
							$query->execute();
							while($row = $query->fetch(PDO::FETCH_ASSOC))
							{
							?>
								<tr>
									<td><?php echo $row["name"]; ?></td>
									<td>
										<select multiple="multiple" style="width: 250px;" id="default_accounting_codes_<?=$row['id'];?>" onchange="updateAccountingCode('<?=$row['id'];?>');">
										<?php foreach ($res_codes as $code) : ?>
											<option value="<?=$code["id"];?>"><?=$code["name"];?></option>
										<?php endforeach; ?>
										</select>
										<?php $codes = json_decode($row["accounting_code"], true); ?>
										<script>
											$('#default_accounting_codes_<?=$row['id'];?>').multipleSelect(
												{
													placeholder: "Нажмите для выбора..."
												}
											);
											temporarilyDisableOnChange('<?=$row['id'];?>');
											<?php if (count($codes) > 0) : ?>
											$('#default_accounting_codes_<?=$row['id'];?>').multipleSelect('setSelects', <?php echo json_encode($codes); ?>);
											<?php endif; ?>
											restoreOnChange('<?=$row['id'];?>');
										</script>
									</td>
									<td class="text-right"><a onClick="del_cash_codes(<?php echo $row['id']; ?>);" class="btn btn-xs btn-danger">Удалить</a></td>
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
	<script>
		function updateAccountingCode(code_id) {
			const select_need = [].concat($("#default_accounting_codes_" + code_id).multipleSelect('getSelects'));
			fetch('https://fordzap.com/cp/content/shop/logistics/update_accounting_code.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					id: code_id,
					accounting_code: JSON.stringify(select_need)
				}),
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					console.log('Обновление прошло успешно');
				} else {
					console.error('Ошибка обновления:', data.error);
				}
			})
			.catch((error) => {
				console.error('Ошибка:', error);
			});
		}
	</script>
	<style>
		.ms-parent {
				width: 350px !important;
		}
	</style>
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Расходные операции для всех магазинов
			</div>
			<div class="panel-body">
				<div class="table-responsive" style="overflow-x: clip;">
					<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
						<thead> 
							<tr style="background:#f7d9d7;">
								<th>Название</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td colspan="3">
									<form method="POST">
										<input type="hidden" name="action" value="add" />
										<input type="hidden" name="income" value="0" />
										<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
										<div class="input-group" style="margin-bottom: 30px; margin-top: 10px;">
											<input value="" type="text" class="form-control" placeholder="Название операции" name="name" />
											<span class="input-group-btn">
												<button class="btn btn-ar btn-success" type="submit">Создать</button>
											</span>
										</div>
									</form>
								</td>
							</tr>
							<?php
							$query = $db_link->prepare('SELECT * FROM `shop_offices_cash_codes` WHERE `income` = 0;');
							$query->execute();
							while($row = $query->fetch(PDO::FETCH_ASSOC))
							{
							?>
								<tr>
									<td><?php echo $row["name"]; ?></td>
									<td>
										<select multiple="multiple" style="height: auto;" id="default_accounting_codes_<?=$row['id'];?>" onchange="updateAccountingCode('<?=$row['id'];?>');">
										<?php foreach ($res_codes as $code) : ?>
											<option value="<?=$code["id"];?>"><?=$code["name"];?></option>
										<?php endforeach; ?>
										</select>
										<?php 
											$codes = json_decode($row["accounting_code"], true); 
										?>
										<script>
											$('#default_accounting_codes_<?=$row['id'];?>').multipleSelect(
												{
													placeholder: "Нажмите для выбора..."
												}
											);
											temporarilyDisableOnChange('<?=$row['id'];?>');
											<?php if (count($codes) > 0) : ?>
											$('#default_accounting_codes_<?=$row['id'];?>').multipleSelect('setSelects', <?php echo json_encode($codes); ?>);
											<?php endif; ?>
											restoreOnChange('<?=$row['id'];?>');
										</script>
									</td>
									<td class="text-right"><a onClick="del_cash_codes(<?php echo $row['id']; ?>);" class="btn btn-xs btn-danger">Удалить</a></td>
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
	<form id="del_form" name="del_form" method="POST" class="hidden">
		<input type="hidden" name="action" value="del" />
		<input type="hidden" id="code_id_del" name="id" value="" />
		<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />
	</form>
	<script>
	function del_cash_codes(id){
		if( !confirm("Наименование операции будет удалено только при условии что нет созданных операций с данным наименованием. Продолжить?") )
		{
			return;
		}
		document.getElementById('code_id_del').value = id;
		document.forms["del_form"].submit();
	}
	</script>
<?php
}
?>