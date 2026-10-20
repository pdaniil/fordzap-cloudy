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
			<a class="panel_a" onClick="<?= $_GET["id"] > 0 ? "updateSto(".$_GET["id"].");" : "createSto();";?>" href="javascript:void(0);">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Сохранить</div>
			</a>
			
			<?php if ($_GET["id"] > 0) : ?>
				<a class="panel_a" onClick="deleteStoById(<?=$_GET["id"];?>);" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить</div>
				</a>
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/car_service/service_price?id=<?=$_GET["id"];?>">
					<div class="panel_a_img" style="background-color: #ffc500;width:96px;height:96px;display:table-cell;vertical-align:middle;"><i class="fas fa-ruble-sign" style="color:#FFF;font-size:45px"></i></div>
					<div class="panel_a_caption">Услуги</div>
				</a>
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/car_service/shift-schedule">
					<div class="panel_a_img" style="background-color: #8918FF;width:96px;height:96px;display:table-cell;vertical-align:middle;"><i class="fas fa-users-cog" style="color:#FFF;font-size:45px"></i></div>
					<div class="panel_a_caption">График смен</div>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Данные СТО
		</div>
		<div class="panel-body">
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Название*
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="text" id="sto_caption" value="<?=$sto_data["caption"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Адрес
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="text" id="sto_address" value="<?=$sto_data["address"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Режим работы
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="hidden" id="sto_operating_mode" value="<?=$sto_data["operating_mode"];?>">
					<select multiple="multiple" id="sto_operating_mode_select">
						<?php $arr_days = ['','Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб','Вс']; ?>
						<?php for ($i = 1; $i < 8; $i++) : ?>
							<option value="<?=$i;?>"><?=$arr_days[$i];?></option>
						<?php endfor; ?>
					</select>

					<script>

					</script>
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Время работы
				</label>
				<div class="col-lg-6">
					<select name="" id="time_from">
						<?php for ($i = 1; $i < 23; $i++) : ?>
							<option <?= $sto_data["time_from"] == $i ? "selected" : ""; ?> value="<?=$i;?>"><?=$i?></option>
						<?php endfor; ?>
					</select>
					-
					<select name="" id="time_to">
						<?php for ($i = 2; $i < 24; $i++) : ?>
							<option <?= $sto_data["time_to"] == $i ? "selected" : ""; ?> value="<?=$i;?>"><?=$i?></option>
						<?php endfor; ?>
					</select>
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Список услуг
					<button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Создание и заполнение на странице Древовидные списки');"><i class="fa fa-info"></i></button>
				</label>
				<div class="col-lg-6" style="display: flex; justify-content: space-between;">
					<?php 
						$services_selected = false;
						$services_selected_value = -1;
					?>
					<select class="form-control" id="sto_id_tree_list_services">
						<?php foreach ($tree_lists_arr as $tree_list) : ?>
							<option 
								value="<?=$tree_list["id"];?>"
								<?= isset($sto_data["id_tree_list_services"]) && $sto_data["id_tree_list_services"] == $tree_list["id"] ? "selected" : "" ?>
							>
								<?=$tree_list["caption"];?>
							</option>
							
							<?php 
								if (isset($sto_data["id_tree_list_services"]) && $sto_data["id_tree_list_services"] == $tree_list["id"]) {
									$services_selected = true;
									$services_selected_value = $tree_list["id"];
								}
							?>
							
						<?php endforeach; ?>
					</select>
					
					<?php if ($services_selected) : ?>
						<a class="btn btn-warning" target="_blank" href="<?=$DP_Config->domain_path?>cp/shop/catalogue/tree_lists/tree_list?id=<?=$services_selected_value?>">Редактировать</a>
					<?php endif; ?>
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Интервал записи
					<button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Укажите в минутах интервал возможной записи. 30 - запись каждые пол часа, 180 - каждые 3 часа.');"><i class="fa fa-info"></i></button>
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="number" id="record_step" value="<?=$sto_data["record_step"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Координаты через запятую
					<button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Укажите координаты через запятую.');"><i class="fa fa-info"></i></button>
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="text" id="coords" value="<?=$sto_data["coords"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Ставка нормо-часа
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="number" step="0.1" id="hourly_rate" value="<?=$sto_data["hourly_rate"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
		</div>
	</div>
</div>

<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Данные для печати
		</div>
		<div class="panel-body">
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Наименование организации
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="text" id="organization" value="<?=$sto_data["organization"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					ИНН
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="text" id="inn" value="<?=$sto_data["inn"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Телефон
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="text" id="phone_org" value="<?=$sto_data["phone"];?>">
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
		</div>
	</div>
</div>