<label for="" class="col-lg-6 control-label">
Выберите услуги
</label>
<div class="col-lg-12">
<select class="form-control" id="services_select" name="services">
	<option value="-1">Добавьте в таблицу</option>
	<?php
		foreach ($tree_lists_services_arr as $service) {
			$services_arr = json_decode($sto_record_data["services_arr"], true);
			if (count($services_arr) < 1)
				$services_arr = [];
	?>
		<option <?= in_array($service["id"], $services_arr) ? 'selected="selected"' : "";?> 
				value="<?=$service["id"];?>"><?=$service["value"]?></option>
	<?php } ?>
</select>
</div>