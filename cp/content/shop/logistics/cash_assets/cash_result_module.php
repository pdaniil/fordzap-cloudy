<?php

	//По умолчанию у операций -1, относим их к Наличным
	$CASH_FLAG = 1;
	$CARD_FLAG = 2;
	$DEFAULT_FLAG = -1;

	$INCOME_FLAG = 1;
	$EXPENSE_FLAG = 0;

	$manager_id_offices_list_str = implode(",", $manager_id_offices_list);
	if(isset($office_id) && !empty($office_id)) {
		$manager_id_offices_list_str = $office_id;
	}


	$INCOME_CASH_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 1 AND `payment` = ? AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";
	$ISSUE_CASH_SQL  = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 0 AND `payment` = ? AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";

	$INCOME_CARD_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 1 AND `payment` = ? AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";
	$ISSUE_CARD_SQL  = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 0 AND `payment` = ? AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";

	$INCOME_DEFAULT_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 1 AND `payment` NOT IN (?,?) AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";
	$ISSUE_DEFAULT_SQL  = "IFNULL((SELECT SUM(`amount`) FROM `shop_offices_cash` WHERE `income` = 0 AND `payment` NOT IN (?,?) AND `office_id` IN (". $manager_id_offices_list_str .")), 0)";

	$SQL_common_balance = "SELECT 
	($INCOME_CASH_SQL - $ISSUE_CASH_SQL) AS `cash_total`,
	($INCOME_CARD_SQL - $ISSUE_CARD_SQL) AS `card_total`,
	($INCOME_DEFAULT_SQL - $ISSUE_DEFAULT_SQL) AS `default_total`
	;";

	$query_common_balance = $db_link->prepare($SQL_common_balance);
	$query_common_balance->execute( array(
		$CASH_FLAG, $CASH_FLAG,
		$CARD_FLAG, $CARD_FLAG,
		$CASH_FLAG, $CARD_FLAG, $CASH_FLAG, $CARD_FLAG
		));
	$result_common_balance = $query_common_balance->fetch(PDO::FETCH_ASSOC);

	$TOTAL_BALANCE = 0;
	$TOTAL_BALANCE = $result_common_balance['cash_total'] + $result_common_balance['card_total'] + $result_common_balance['default_total'];

	$TOTAL_payment_class = '';
	if($TOTAL_BALANCE > 0) {
		$TOTAL_payment_class = 'text-success';
	}
	else
	{
		$TOTAL_payment_class = 'text-danger';
	}

	$cols_result_width = 6;

	if(!empty($result_common_balance['default_total'])) {
		$cols_result_width = 4;
	}

?>

<div class="panel-heading hbuilt">Общий баланс средств - <span class="m-xs <?= $TOTAL_payment_class ;?>" style="font-weight: 600; font-size: 18px;line-height:1;vertical-align: text-top;" ><?= $TOTAL_BALANCE; ?><span class="small" style="font-weight:300;">₽</span></span></div>
<div class="panel-body">
	<div class="row">

		<div class="col-md-<?= $cols_result_width ;?>">
			<div class="main-cash__title">
				Наличные
			</div>

			<div class="main-cash__total">
				<?php
					$cash_total = array();
					$cash_total_view = '';
					if(!empty($result_common_balance['cash_total'])) {
						
						$cash_total = $result_common_balance['cash_total'];

						$payment_class = '';
						if($cash_total > 0) {
							$payment_class = 'text-success';
						}
						else
						{
							$payment_class = 'text-danger';
						}

						$cash_total_view = $cash_total;
						$cash_total_view = number_format($cash_total_view, 2, ',', ' ');
						$cash_total_view = str_replace(',', "<small class=\"$payment_class\">,", $cash_total_view).'₽</small>';
					}
				?>
				
				<?php if(!empty($cash_total_view)) : ?>
					<h2 class="m-xs <?= $payment_class ;?>" style="font-weight: 600; font-size: 48px;text-align:center;" ><?= $cash_total_view; ?></h2>
				<?php endif; ?>

			</div>
		</div>
		<div class="col-md-<?= $cols_result_width ;?>">
			<div class="main-cash__title">
				По карте
			</div>
			<div class="main-cash__total">
				<?php
					$card_total = array();
					$card_total_view = '';
					if(!empty($result_common_balance['card_total'])) {
						
						$card_total = $result_common_balance['card_total'];

						$payment_class = '';
						if($card_total > 0) {
							$payment_class = 'text-success';
						}
						else
						{
							$payment_class = 'text-danger';
						}

						$card_total_view = $card_total;
						$card_total_view = number_format($card_total_view, 2, ',', ' ');
						$card_total_view = str_replace(',', "<small class=\"$payment_class\">,", $card_total_view).'₽</small>';
					}
				?>
				
				<?php if(!empty($card_total_view)) : ?>
					<h2 class="m-xs <?= $payment_class ;?>" style="font-weight: 600; font-size: 48px;text-align:center;" ><?= $card_total_view; ?></h2>
				<?php endif; ?>
			</div>
		</div>
		<div class="col-md-<?= $cols_result_width ;?>">
			<div class="main-cash__title">
				Другой способ
			</div>
			<div class="main-cash__total">
				<?php
					$default_total = array();
					$default_total_view = '';
					
					if(!empty($result_common_balance['default_total'])) {
						$default_total = $result_common_balance['default_total'];

						$payment_class = '';
						if($default_total > 0) {
							$payment_class = 'text-success';
						}
						else
						{
							$payment_class = 'text-danger';
						}

						$default_total_view = $default_total;
						$default_total_view = number_format($default_total_view, 2, ',', ' ');
						$default_total_view = str_replace(',', "<small class=\"$payment_class\">,", $default_total_view).'₽</small>';
					}

				?>
				
				<?php if(!empty($default_total_view)) : ?>
					<h2 class="m-xs <?= $payment_class ;?>" style="font-weight: 600; font-size: 48px;text-align:center;" ><?= $default_total_view; ?></h2>
				<?php endif; ?>
			</div>
		</div>

	</div>
</div>
