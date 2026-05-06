<?php 
	defined('_ASTEXE_') or die('No access');
	checkId();
?>
<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Действия
		</div>
		<div class="panel-body" >
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/car_service/sto?id=<?=$_GET["id"];?>">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/out.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">В карточку СТО</div>
			</a>
			<a class="panel_a hidden" id="loader_service">
				<img src="<?=$DP_Config->domain_path;?>content/files/images/ajax-loader-transparent.gif">
			</a>
			<a class="panel_a" id="set_data_button" onClick="setData();" href="javascript:void(0);">
				<div class="panel_a_img"  style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Сохранить</div>
			</a>
		</div>
	</div>
</div>

<div class="col-lg-6">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Список услуг
		</div>
		<div class="panel-body">
			<div class="col-lg-12" style="padding: 0;">
				<label class="col-lg-12" style="padding: 0;">Наименование услуги</label>
				<div class="col-lg-4" style="padding: 0;">
					<input type='text'  class="form-control" onkeyup="filter_tree('tree_service_webix', this.value)"/>
				</div>
			</div>
			<div class="col-lg-12" style="padding: 0;">
				<div id="tree_service" style="height: 500px; overflow: auto;">
				</div>
			</div>
		</div>
	</div>
</div>

<div class="col-lg-6" id="service_info">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Информация об услуге
		</div>
		<div class="panel-body">
			<label for="" class="col-lg-6 control-label">
				Цена
			</label>
			<div class="col-lg-6">
				<input oninput="setWebixPrice(this.value)" class="form-control" type="number" id="price" value="0"/ readonly>
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			<label for="" class="col-lg-6 control-label"> 
				Кол-во нормо-часов
			</label>
			<div class="col-lg-6">
				<input oninput="setWebixHourlyRate(this.value)" class="form-control" type="number" id="hourly_rate" value="0"/> 
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			<label for="" class="col-lg-6 control-label">
				Описание
			</label>
			<div class="col-lg-6">
				<textarea oninput="setWebixCaption(this.value)" class="form-control" type="text" id="caption" value=""></textarea>
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			<label for="" class="col-lg-6 control-label">
				Стикеры
			</label>
			<div class="col-lg-6">
				<select id="stickers" multiple="multiple" >
					<?php foreach ($stickers_arr_json as $sticker) : ?>
						<option value="<?=$sticker["id"];?>"><?=$sticker["text"];?></option>
					<?php endforeach; ?>
				</select>
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			<label for="" class="col-lg-6 control-label">
				Блок "Рекомендуем"
			</label>
			<div class="col-lg-6">
				<input oninput="setWebixRecommendation()" class="form-control" type="checkbox" id="recommendation_block" value=""/>
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			<label for="" class="col-lg-6 control-label">
				Цену рассчитает мастер
			</label>
			<div class="col-lg-6">
				<input oninput="setWebixWorkerPrice()" class="form-control" type="checkbox" id="worker_price" value=""/>
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			<label for="" class="col-lg-6 control-label">
				Услуга доступна
			</label>
			<div class="col-lg-6">
				<input oninput="setWebixActive()" class="form-control" type="checkbox" id="active" value=""/>
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			<label for="" class="col-lg-6 control-label">
				Фиксированная оплата (учитывается, если > 0)
			</label>
			<div class="col-lg-6">
				<input oninput="setWebixFixPrice(this.value)" class="form-control" type="number" id="fix_price" value=""/>  
			</div>
			
		</div>
	</div>
</div>