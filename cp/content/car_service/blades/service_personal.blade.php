<?php 
	defined('_ASTEXE_') or die('No access');
	checkId();
?>
<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Действия
		</div>
		<div class="panel-body">
			<a class="panel_a" onClick="<?= $_GET["id"] > 0 ? "updatePersonal(".$_GET["id"].");" : "createPersonal();";?>" href="javascript:void(0);">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Сохранить</div>
			</a>
			
			<?php if ($_GET["id"] > 0) : ?>
			<a class="panel_a" onClick="deletePersonalById(<?=$_GET["id"];?>);" href="javascript:void(0);">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Удалить</div>
			</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Данные сотрудника
		</div>
		<div class="panel-body">
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					ФИО*
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="text" id="personal_fio" value="<?=$service_personal_data["fio"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Телефон
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="text" id="personal_phone" value="<?=$service_personal_data["phone"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Дневная ставка
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="text" id="personal_standart_hourly_rate" value="<?=$service_personal_data["standart_hourly_rate"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Процент со стоимости работ
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="number" id="personal_service_percent" value="<?=$service_personal_data["service_percent"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					ID из таблицы пользователей
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="number" id="personal_user_id" value="<?=$service_personal_data["user_id"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Отображать цену за работу
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="checkbox" id="show_price_info" <?php echo $service_personal_data["show_price_info"] == 1 ? "checked" : ""; ?> > 
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
		</div>
	</div>
</div>