<label for="" class="col-lg-6 control-label">
	Действия
</label>
<div class="col-lg-6">
	<button class="btn btn-warning" onclick="goToRecordPage_2();">Перейти в карточку записи</button>
</div>
<div class="hr-line-dashed col-lg-12"></div>	
<div class="form-group">
	<label for="" class="col-lg-6 control-label">
		СТО
	</label>
	<div class="col-lg-6">
		<input type="hidden" id="hourly_rate_price" value="">
		<input type="hidden" id="record_id" value="">
		<select class="form-control" id="sto_id" onchange="get_data_by_sto_id();">
			<option value="-1">Не выбрано</option>
			<?php foreach ($sto_data as $sto_record) : ?>
			<option <?=$sto_record_data["sto_id"] == $sto_record["id"] ? 'selected="selected"' : "";?> value="<?=$sto_record["id"];?>"><?=$sto_record["caption"];?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
<div class="hr-line-dashed col-lg-12"></div>
<div class="form-group">
	<label for="" class="col-lg-6 control-label">
		Пост
	</label>
	<div class="col-lg-6">
		<select class="form-control" id="posts_select" name="post" >
			<option value="-1">Не выбрано</option>
			<?php
			foreach ($sto_data_posts as $post) {
			?>
			<option <?=$sto_record_data["post_id"] == $post["id"] ? 'selected="selected"' : "";?> value="<?=$post["id"];?>"><?=$post["caption"];?></option>
			<?
			}
			?>
		</select>
	</div>
</div>
<div class="hr-line-dashed col-lg-12"></div>
<div class="form-group">
	<label for="" class="col-lg-6 control-label">
		Сотрудник
	</label>
	<div class="col-lg-6">
		<select class="form-control" id="workers_select" name="workers">
			<option value="AL">Не выбрано</option>
			<?php
				foreach ($arr_workers as $worker) {
				?>
				<option <?=$sto_record_data["worker_id"] == $worker["id"] ? 'selected="selected"' : "";?> data-percent="<?=$worker["service_percent"];?>" value="<?=$worker["id"];?>"><?=$worker["fio"]?></option>
			<?
				}
			?> 
		</select>
	</div> 
</div>
<div class="hr-line-dashed col-lg-12"></div>
<div class="form-group">
  <label class="col-lg-6 control-label">Дата/время записи</label>
  <div class="col-lg-6">
	<div class="time-record-container">
	  <div id="time-record-display" class="time-record-display">
		<?= !empty($sto_record_data["time"]) ? $sto_record_data["time_formatted"] : "Выберите дату и время";?>
	  </div>
	</div>
  </div>
</div>

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
<div class="hr-line-dashed col-lg-12"></div>
<div class="form-group">
	<label for="" class="col-lg-6 control-label">
		Фактическое время заезда
	</label>  
	<div class="col-lg-6">
	
		<div style="position:relative;height:34px;">
			<input style="position:absolute; z-index:2; opacity:0" type="text"  id="time_from" value="<?php echo $time_from; ?>" class="form-control" autocomplete="off" />
			<input style="position:absolute; z-index:1;" type="text" id="time_from_show" class="form-control" />
			<script>
			//Инициализируем datetimepicker
			jQuery("#time_from").datetimepicker({
				lang:"ru",
				closeOnDateSelect:true,
				closeOnTimeSelect:false,
				dayOfWeekStart:1,
				step: 30,
				format:'unixtime',
				onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
				{
					var time_string = "";
					var date_ob = new Date(current_time);
					time_string += date_ob.getDate()+".";
					time_string += (date_ob.getMonth() + 1)+".";
					time_string += date_ob.getFullYear()+" ";
					time_string += date_ob.getHours()+":"+date_ob.getMinutes();
					document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
				}
				<?php
				if($time_from != "")
				{
					?>
					,
					onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
					{
						var time_string = "";
						var date_ob = new Date(current_time);
						time_string += date_ob.getDate()+".";
						time_string += (date_ob.getMonth() + 1)+".";
						time_string += date_ob.getFullYear()+" ";
						time_string += date_ob.getHours()+":"+date_ob.getMinutes();
						document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
					}
					<?php
				}
				?>
			});
			</script>
		</div>
	</div>
</div>
<div class="hr-line-dashed col-lg-12"></div>
<div class="form-group">
	<label for="" class="col-lg-6 control-label">
		Фактическое врема выезда
	</label>
	<div class="col-lg-6">
		<div style="position:relative;height:34px;">
			<input style="position:absolute; z-index:2; opacity:0" type="text"  id="time_to" value="<?php echo $time_to; ?>" class="form-control" autocomplete="off" />
			<input style="position:absolute; z-index:1;" type="text" id="time_to_show" class="form-control" />
			<script>
			//Инициализируем datetimepicker
			jQuery("#time_to").datetimepicker({
				lang:"ru",
				closeOnDateSelect:true,
				closeOnTimeSelect:false,
				dayOfWeekStart:1,
				format:'unixtime',
				onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
				{
					var time_string = "";
					var date_ob = new Date(current_time);
					time_string += date_ob.getDate()+".";
					time_string += (date_ob.getMonth() + 1)+".";
					time_string += date_ob.getFullYear()+" ";
					time_string += date_ob.getHours()+":"+date_ob.getMinutes();
					document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
				}
				<?php
				if($time_to != "")
				{
					?>
					,
					onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
					{
						var time_string = "";
						var date_ob = new Date(current_time);
						time_string += date_ob.getDate()+".";
						time_string += (date_ob.getMonth() + 1)+".";
						time_string += date_ob.getFullYear()+" ";
						time_string += date_ob.getHours()+":"+date_ob.getMinutes();
						document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
					}
					<?php
				}
				?>
			});
			</script>
		</div>
	</div>
</div>
<div class="hr-line-dashed col-lg-12"></div>
<div class="form-group">
	<label for="" class="col-lg-6 control-label">
		Текущий пробег, км
	</label>  
	<div class="col-lg-6">
		<input id="mileage_record" class="form-control" onkeyup="handleAutoSave();" value="<?=$mileage; ?>" />
	</div>
</div>

<script>
	
	function handleChange() {
		save_record(); // Автосохранение
	}
	
	// Общий обработчик для автосохранения
	const handleAutoSave = () => save_record();
	
	$(document).ready(function() {
		//$('#workers_select').select2();
		$('#services_select').select2();
		//$('#posts_select').select2();

		// Убираем автосохранение для user_car, т.к. оно теперь в render_data_by_user_id
		$('#workers_select, #posts_select').on('change', handleAutoSave);
		['sto_id', 'time_from', 'time_to'].forEach(id => {
			document.getElementById(id).addEventListener('change', handleAutoSave);
		});
	});
</script>