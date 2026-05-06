<div class="form-group">
<label for="" class="col-lg-6 control-label">
	Авто клиента
</label>
<div class="col-lg-6">
	<select class="form-control" id="user_car"></select>
</div>
</div>
<div class="hr-line-dashed col-lg-12"></div>
<div class="form-group">
<label for="" class="col-lg-6 control-label">
	Марка/Модель
</label>
<div class="col-lg-6">
	<input class="form-control" type="text" id="user_car_model" value="" readonly>
</div>
</div>
<div class="hr-line-dashed col-lg-12"></div>
<div class="form-group">
<label for="" class="col-lg-6 control-label">
	VIN/FRAME
</label>
<div class="col-lg-6">
	<input class="form-control" type="text" id="user_car_vin" value="" readonly>  
</div>
</div> 
<div class="hr-line-dashed col-lg-12"></div>
<div class="form-group">
<label for="" class="col-lg-6 control-label">
	Действия
</label>
<div class="col-lg-6" style="grid-gap: 5px;">
	<button class="btn btn-warning" onclick="carModalOpen(false);">Детали</button>
	<button class="btn btn-primary" onclick="carModalOpen(true);">Добавить</button>
	<button class="btn btn-danger" onclick="delete_car();">Удалить</button>
</div>
</div>


<?php 
	
	require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/car_service/modules/record_info/garage_module/add_car_html.php");
	
?>