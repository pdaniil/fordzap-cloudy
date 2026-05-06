<?php


if(!isset($DP_Config)) {
	//Конфигурация
	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
	//Соединение с основной БД
	$DP_Config = new DP_Config;//Конфигурация CMS
}

if(!isset($db_link)) {
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
}

if(!isset($office_id)) {
	$office_id = isset($_POST['office_id']) ? $_POST['office_id'] : null;
}

$filterDate = isset($_POST['date']) ? json_decode($_POST['date'], true) : array();

?>


<div class="panel-heading hbuilt">
	<?php

		$date_today = new DateTime('today midnight');
		// $date_today = new DateTime('2024-10-04');
		$timestamp_today = $date_today->getTimestamp();

		//Проверяем фильтр по дате
		$filterDate_from = (!empty($filterDate['dateFrom']) && $filterDate['dateFrom'] != 0) ? $filterDate['dateFrom'] : null;
		$filterDate_to = (!empty($filterDate['dateTo']) && $filterDate['dateTo'] != 0) ? $filterDate['dateTo'] : null;

		$balance_today = 0;

		$bind_values = array();

		$bind_values_INCOME = array();
		$bind_values_ISSUE = array();

		//Сразу расчет из БД
		$INCOME_today_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `office_id` = ? AND `income` = 1 ";
		$ISSUE_today_SQL  = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `office_id` = ? AND `income` = 0 ";

		array_push($bind_values_INCOME, $office_id);
		array_push($bind_values_ISSUE, $office_id);

		if(!empty($filterDate_from) || !empty($filterDate_to)) {

			if(!empty($filterDate_from)) {
				$INCOME_today_SQL .= "AND `time` > ? ";
				$ISSUE_today_SQL .= "AND `time` > ? ";

				array_push($bind_values_INCOME, $filterDate_from);
				array_push($bind_values_ISSUE, $filterDate_from);
			}

			if(!empty($filterDate_to)) {
				$INCOME_today_SQL .= "AND `time` < ?";
				$ISSUE_today_SQL .= "AND `time` < ?";

				array_push($bind_values_INCOME, $filterDate_to);
				array_push($bind_values_ISSUE, $filterDate_to);
			}

		} else {

			$INCOME_today_SQL .= "AND `time` > ?";
			$ISSUE_today_SQL .= "AND `time` > ?";

			$filterDate_from = $timestamp_today;

			array_push($bind_values_INCOME, $timestamp_today);
			array_push($bind_values_ISSUE, $timestamp_today);
		} 
		
		$INCOME_today_SQL .= "), 0)";
		$ISSUE_today_SQL .= "), 0)";
		
		foreach($bind_values_ISSUE as $bind_values_ISSUE_after) {
			array_push($bind_values_INCOME, $bind_values_ISSUE_after);
		}

		$SQL_balance = "SELECT 
		($INCOME_today_SQL - $ISSUE_today_SQL) AS `balance_today`
		;";

		$query_balance = $db_link->prepare($SQL_balance);
		$query_balance->execute($bind_values_INCOME);
		$row_balance = $query_balance->fetch(PDO::FETCH_ASSOC);

		$balance_today = $row_balance["balance_today"];

		$today_payment_class = '';
		if($balance_today > 0) {
			$today_payment_class = 'text-success';
		}
		else
		{
			$today_payment_class = 'text-danger';
		}

		$balance_today_show = $balance_today;
		$balance_today_show = number_format($balance_today_show, 2, ',', ' ');
		$balance_today_show = str_replace(',', "<small class=\"$today_payment_class\">,", $balance_today_show).'</small>';

	?>

	Баланс средств в магазине 
	<?php if(!empty($filterDate_from)) : ?>
		от <?= date("d.m.Y H:00:00", $filterDate_from) ;?> 
	<?php endif; ?> 
	<?php if(!empty($filterDate_to)) : ?>
		до <?= date("d.m.Y H:00:00", $filterDate_to) ;?>
	<?php endif; ?> 
	
	<?php if(isset($balance_today) && !empty($balance_today)) : ?>
		- <span class="m-xs <?= $today_payment_class ;?>" style="font-weight: 600; font-size: 18px;line-height:1;vertical-align: text-top;" ><?= $balance_today; ?><span class="small" style="font-weight:300;">₽</span></span>
	<?php endif; ?> 
</div>

<div class="panel-body">
	<?php

	if(!function_exists(cashFormat)) {
		function cashFormat($cash) {
			return number_format($cash, 2, ',', ' ');
		}
	}

	//Более подробно выводим данные
	//По умолчанию у операций -1, относим их к Наличным
	$CASH_FLAG = 1;
	$CARD_FLAG = 2;
	$DEFAULT_FLAG = -1;

	$INCOME_FLAG = 1;
	$EXPENSE_FLAG = 0;

	//Все приходные операции
	$SQL_date_balance = "SELECT 
		cc.`name`,
		c.*
		FROM `shop_offices_cash` c
		LEFT JOIN `shop_offices_cash_codes` cc ON cc.`id` = c.`operation_code`
		WHERE c.`office_id` = ? ";
	
	$bind_all_values = array();

	array_push($bind_all_values, $office_id);
	
	if(!empty($filterDate_from) || !empty($filterDate_to)) {
		if(!empty($filterDate_from)) {
			$SQL_date_balance .= "AND c.`time` > ? ";
			array_push($bind_all_values, $filterDate_from);
		}
		if(!empty($filterDate_to)) {
			$SQL_date_balance .= "AND c.`time` < ?";
			array_push($bind_all_values, $filterDate_to);
		}
	} else {
		$SQL_date_balance .= "AND c.`time` > ?";
		array_push($bind_all_values, $timestamp_today);
	}

	$SQL_date_balance .= ";";

	$query_date_balance = $db_link->prepare($SQL_date_balance);
	$query_date_balance->execute($bind_all_values);

	$balance_date_result = $query_date_balance->fetchAll(PDO::FETCH_ASSOC);

	$group_operations_data = array();

	//Перебор всех операций для группировки по типу операции и виду платежа
	foreach ($balance_date_result as $balance_date_operation) {
		$operation_payment = $balance_date_operation['payment'];
		$operation_code = $balance_date_operation['operation_code'];
		$operation_income = $balance_date_operation['income'];
		$operation_name = $balance_date_operation['name'];

		$this_amount = $balance_date_operation['amount'];

		if(!in_array($operation_payment, array($CASH_FLAG,$CARD_FLAG))) {
			$operation_payment = -1;
		}

		if(isset($group_operations_data[$operation_payment][$operation_income][$operation_code])) {
			$group_operations_data[$operation_payment][$operation_income][$operation_code]['amount_sum'] += $this_amount;
		}
		else
		{
			$group_operations_data[$operation_payment][$operation_income][$operation_code]['name'] = $operation_name;
			$group_operations_data[$operation_payment][$operation_income][$operation_code]['amount_sum'] = $this_amount;
		}

		if(!isset($group_operations_data[$operation_payment]['total'])) $group_operations_data[$operation_payment]['total'] = 0;
		if($operation_income == 1) {
			$group_operations_data[$operation_payment]['total'] += $this_amount;
		}
		else {
			$group_operations_data[$operation_payment]['total'] -= $this_amount;
		}
	}

	?>

	<?php $cols_width = 6;

		if(isset($group_operations_data[$DEFAULT_FLAG])) {
			$cols_width = 4;
		}

		$cash_data_income_total = 0;
		$cash_data_expense_total = 0;
		$card_data_income_total = 0;
		$card_data_expense_total = 0;
		$default_data_income_total = 0;
		$default_data_expense_total = 0;
	?>

	<div class="row">
		<div class="col-md-<?= $cols_width ;?>">
			<div class="main-cash__title">
				Наличные
			</div>

			<div class="main-cash__total">
				<?php
					$cash_data = array();
					$cash_data_total = '';
					if(isset($group_operations_data[$CASH_FLAG])) {
						
						$cash_data = $group_operations_data[$CASH_FLAG];

						$payment_class = '';
						if($cash_data['total'] > 0) {
							$payment_class = 'text-success';
						}
						else
						{
							$payment_class = 'text-danger';
						}

						$cash_data_total = $cash_data['total'];
						$cash_data_total = number_format($cash_data_total, 2, ',', ' ');
						$cash_data_total = str_replace(',', "<small class=\"$payment_class\">,", $cash_data_total).'₽</small>';
					}
				?>
				
				<?php if(!empty($cash_data_total)) : ?>
					<h2 class="m-xs <?= $payment_class ;?>" style="font-weight: 600; font-size: 48px;text-align:center;" ><?= $cash_data_total; ?></h2>
				<?php endif; ?>

			</div>
			<div class="main-cash__stats__outer">
				
				<div class="main_cash__typeIncome main-cash__income">
					<div class="main-cash__title">
						Доход
					</div>
					<div class="main-cash__stats">
						<?php $cash_data_income_total = 0; ?>
						<?php if(isset($cash_data[$INCOME_FLAG])) : ?>
							<?php foreach($cash_data[$INCOME_FLAG] as $cash_data_code => $cash_data_item) : ?>
								<?php $cash_data_income_total += $cash_data_item['amount_sum'] ;?>

								<div class="main-cash__stat">
									<div class="main-cash__name"><?= $cash_data_item['name'] ;?></div>
									<div class="main-cash__amount"><?= cashFormat($cash_data_item['amount_sum']) ;?></div>
								</div>

							<?php endforeach; ?>
						<?php endif; ?>

					</div>
				</div>
				<div class="main_cash__typeIncome main-cash__expense">
					<div class="main-cash__title">
						Расход
					</div>
					<div class="main-cash__stats">
						<?php $cash_data_expense_total = 0; ?>
						<?php if(isset($cash_data[$EXPENSE_FLAG])) : ?>
							<?php foreach($cash_data[$EXPENSE_FLAG] as $cash_data_code => $cash_data_item) : ?>
								<?php $cash_data_expense_total += $cash_data_item['amount_sum'] ;?>

								<div class="main-cash__stat">
									<div class="main-cash__name"><?= $cash_data_item['name'] ;?></div>
									<div class="main-cash__amount"><?= cashFormat($cash_data_item['amount_sum']) ;?></div>
								</div>

							<?php endforeach; ?>
						<?php endif; ?>

					</div>
				</div>

			</div>
		</div>
		<div class="col-md-<?= $cols_width ;?>">
			<div class="main-cash__title">
				По карте
			</div>
			<div class="main-cash__total">
				<?php
					$card_data = array();
					$card_data_total = '';
					if(isset($group_operations_data[$CARD_FLAG])) {
						
						$card_data = $group_operations_data[$CARD_FLAG];

						$payment_class = '';
						if($card_data['total'] > 0) {
							$payment_class = 'text-success';
						}
						else
						{
							$payment_class = 'text-danger';
						}

						$card_data_total = $card_data['total'];
						$card_data_total = number_format($card_data_total, 2, ',', ' ');
						$card_data_total = str_replace(',', "<small class=\"$payment_class\">,", $card_data_total).'₽</small>';
					}
				?>
				
				<?php if(!empty($card_data_total)) : ?>
					<h2 class="m-xs <?= $payment_class ;?>" style="font-weight: 600; font-size: 48px;text-align:center;" ><?= $card_data_total; ?></h2>
				<?php endif; ?>
			</div>
			<div class="main-cash__stats__outer">
				
				<div class="main_cash__typeIncome main-cash__income">
					<div class="main-cash__title">
						Доход
					</div>
					<div class="main-cash__stats">
						<?php $card_data_income_total = 0; ?>
						<?php if(isset($card_data[$INCOME_FLAG])) : ?>
							<?php foreach($card_data[$INCOME_FLAG] as $card_data_code => $card_data_item) : ?>
								<?php $card_data_income_total += $card_data_item['amount_sum'] ;?>

								<div class="main-cash__stat">
									<div class="main-cash__name"><?= $card_data_item['name'] ;?></div>
									<div class="main-cash__amount"><?= cashFormat($card_data_item['amount_sum']) ;?></div>
								</div>

							<?php endforeach; ?>
						<?php endif; ?>

					</div>
				</div>
				<div class="main_cash__typeIncome main-cash__expense">
					<div class="main-cash__title">
						Расход
					</div>
					<div class="main-cash__stats">
					<?php $card_data_expense_total = 0; ?>
						<?php if(isset($card_data[$EXPENSE_FLAG])) : ?>
							<?php foreach($card_data[$EXPENSE_FLAG] as $card_data_code => $card_data_item) : ?>
								<?php $card_data_expense_total += $card_data_item['amount_sum'] ;?>

								<div class="main-cash__stat">
									<div class="main-cash__name"><?= $card_data_item['name'] ;?></div>
									<div class="main-cash__amount"><?= cashFormat($card_data_item['amount_sum']) ;?></div>
								</div>

							<?php endforeach; ?>
						<?php endif; ?>

					</div>
				</div>
			</div>
		</div>
		<?php if(isset($group_operations_data[$DEFAULT_FLAG])) : ?>
			<div class="col-md-<?= $cols_width ;?>">
				<div class="main-cash__title">
					Другой способ
				</div>
				<div class="main-cash__total">
					<?php
						$default_data = array();
						$default_data_total = '';
							
						$default_data = $group_operations_data[$DEFAULT_FLAG];

						$payment_class = '';
						if($default_data['total'] > 0) {
							$payment_class = 'text-success';
						}
						else
						{
							$payment_class = 'text-danger';
						}

						$default_data_total = $default_data['total'];
						$default_data_total = number_format($default_data_total, 2, ',', ' ');
						$default_data_total = str_replace(',', "<small class=\"$payment_class\">,", $default_data_total).'₽</small>';

					?>
					
					<?php if(!empty($default_data_total)) : ?>
						<h2 class="m-xs <?= $payment_class ;?>" style="font-weight: 600; font-size: 48px;text-align:center;" ><?= $default_data_total; ?></h2>
					<?php endif; ?>
				</div>
				<div class="main-cash__stats__outer">
					
					<div class="main_cash__typeIncome main-cash__income">
						<div class="main-cash__title">
							Доход
						</div>
						<div class="main-cash__stats">
							<?php $default_data_income_total = 0; ?>
							<?php if(isset($default_data[$INCOME_FLAG])) : ?>
								<?php foreach($default_data[$INCOME_FLAG] as $default_data_code => $default_data_item) : ?>
									<?php $default_data_income_total += $default_data_item['amount_sum'] ;?>

									<div class="main-cash__stat">
										<div class="main-cash__name"><?= $default_data_item['name'] ;?></div>
										<div class="main-cash__amount"><?= cashFormat($default_data_item['amount_sum']) ;?></div>
									</div>

								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
					<div class="main_cash__typeIncome main-cash__expense">
						<div class="main-cash__title">
							Расход
						</div>
						<div class="main-cash__stats">
							<?php $default_data_expense_total = 0; ?>
							<?php if(isset($default_data[$EXPENSE_FLAG])) : ?>
								<?php foreach($default_data[$EXPENSE_FLAG] as $default_data_code => $default_data_item) : ?>
									<?php $default_data_expense_total += $default_data_item['amount_sum'] ;?>

									<div class="main-cash__stat">
										<div class="main-cash__name"><?= $default_data_item['name'] ;?></div>
										<div class="main-cash__amount"><?= cashFormat($default_data_item['amount_sum']) ;?></div>
									</div>

								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>

				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="row">
		<div class="col-md-<?= $cols_width ;?>">
			<div class="main-cash__stats__outer">
					
					<div class="main_cash__typeIncome main-cash__income">
						<div class="main-cash__stats">
							<div class="main-cash__stat main-cash__stat--total">
								<div class="main-cash__name">Доход за период: <span class="main-cash__amount"><?= cashFormat($cash_data_income_total) ;?></span></div>
							</div>
						</div>
					</div>
							<div class="main_cash__typeIncome main-cash__expense">
						<div class="main-cash__stats">
							<div class="main-cash__stat main-cash__stat--total">
								<div class="main-cash__name">Расход за период: <span class="main-cash__amount"><?= cashFormat($cash_data_expense_total) ;?></span></div>
							</div>
						</div>
					</div>
			</div>
		</div>
		<div class="col-md-<?= $cols_width ;?>">
			<div class="main-cash__stats__outer">
				<div class="main_cash__typeIncome main-cash__income">
					<div class="main-cash__stats">
						<div class="main-cash__stat main-cash__stat--total">
							<div class="main-cash__name">Доход за период: <span class="main-cash__amount"><?= cashFormat($card_data_income_total) ;?></span></div>
						</div>
					</div>
				</div>
				<div class="main_cash__typeIncome main-cash__expense">
					<div class="main-cash__stats">
						<div class="main-cash__stat main-cash__stat--total">
							<div class="main-cash__name">Расход за период: <span class="main-cash__amount"><?= cashFormat($card_data_expense_total) ;?></span></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php if(isset($group_operations_data[$DEFAULT_FLAG])) : ?>
			<div class="col-md-<?= $cols_width ;?>">
				<div class="main-cash__stats__outer">
					<div class="main_cash__typeIncome main-cash__income">
						<div class="main-cash__stats">
							<div class="main-cash__stat main-cash__stat--total">
								<div class="main-cash__name">Доход за период: <span class="main-cash__amount"><?= cashFormat($default_data_income_total) ;?></span></div>
							</div>
						</div>
					</div>
					<div class="main_cash__typeIncome main-cash__expense">
						<div class="main-cash__stats">
							<div class="main-cash__stat main-cash__stat--total">
								<div class="main-cash__name">Расход за период: <span class="main-cash__amount"><?= cashFormat($default_data_expense_total) ;?></span></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="row">
		<div class="col-md-6" style="text-align:center;">
			<span style="font-weight:600;font-size:16px;">Доходные операции:</span>
			<span class="text-success" style="display:inline-block;margin-left:10px;font-weight: 600; font-size: 28px;text-align:center;">
				<?php 
					$all_income = $cash_data_income_total + $card_data_income_total + $default_data_income_total;

					$all_income_show = $all_income;
					$all_income_show = number_format($all_income_show, 2, ',', ' ');
					$all_income_show = str_replace(',', "<small class=\"text-success\">,", $all_income_show).'₽</small>';
				
				;?>
				<?= $all_income_show ;?>
			</span>
		</div>
		<div class="col-md-6" style="text-align:center;">
			<span style="font-weight:600;font-size:16px;">Расходные операции:</span>
			<span class="text-danger" style="display:inline-block;margin-left:10px;font-weight: 600; font-size: 28px;text-align:center;">
				<?php 
					$all_expense = $cash_data_expense_total + $card_data_expense_total + $default_data_expense_total;

					$all_expense_show = $all_expense;
					$all_expense_show = number_format($all_expense_show, 2, ',', ' ');
					$all_expense_show = str_replace(',', "<small class=\"text-danger\">,", $all_expense_show).'₽</small>';
				
				;?>
				<?= $all_expense_show ;?>
			</span>
		</div>
	</div>

</div>	