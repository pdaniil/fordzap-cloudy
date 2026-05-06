<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt" style="display: flex; justify-content: space-between;">
            <span>Таблица услуг </span>
			<form action="/../../../<?= $DP_Config->backend_dir ?>/content/label/print_labels_services.php" method="post" target="_blank" id="print_labels_services_form">
				<input type="hidden" name="items_data" id="items_services_data_input">
				<button type="submit" class="btn btn-warning"><i class="fa fa-print"></i> Напечатать этикетки (сервис)</button>
			</form>
        </div>
        <div class="panel-body">
            <div class="form-group col-lg-12">
				<div class="col-lg-12" style="visibility: collapse;">
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">
							Предоплата 
						</label>
						<div class="col-lg-3">
							<input id="prepayment"  class="form-control" type="number" name="prepayment" value="<?=$sto_record_data["prepayment"]?>" />
						</div>
					</div>
				</div>
				

				<div id="table_services_wrapper" class="col-lg-12" style="padding: 0 30px;">
					<table class="table table-bordered">
						<thead>
							<tr>
								<th><input type="checkbox" id="check_all_services"></th>
								<th>Наименование</th>
								<th>Статус</th>
								<th>Цена</th>
								<th>Нормо-час</th>
								<th>Количество</th>
								<th>Итого со скидкой</th>
								<th>Скидка %</th>
								<th>Действие</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($current_services as $service) : ?>
							
								<?php 
									
									$price_total_discount = $service["count"] * $service["price"];
									$price_total_discount = $price_total_discount - ($price_total_discount * ($service["discount"] / 100));
									$count_total += $service["count"];
									$price_total += $price_total_discount;
									$hourly_rate_count += $service["hourly_rate"];
								?>
								
								<tr>
									<td>
										<input type="hidden" class="fix_price" name="service_fix_price[]" value="<?=$service["fix_price"];?>" readonly>
										<input class="service_one_check" type="checkbox">
									</td>
									<td>
										<?=$service["caption"];?>
									</td>
									<td>
										<select class="form-control status" name="service_status[]">
											<option value="-1">Не выбрано</option>
											<?php foreach ($res_select_statuses as $status_info) : ?>
												<option <?=  $service["status_id"] == $status_info["id"] ? "selected" : ""; ?> value="<?=$status_info["id"];?>"><?=$status_info["name"];?></option>  
											<?php endforeach; ?>
										</select>
									</td>
									<td>
										<input class="form-control price" name="service_price[]" value="<?=$service["price"];?>" readonly>
									</td>
									<td> 
										<input class="form-control hourly_rate" name="service_hourly_rate[]" value="<?=$service["hourly_rate"];?>" type="number" step="0.1">  
									</td>	 
									<td>
										<input class="form-control quantity" name="service_quantity[]" value="<?=$service["count"];?>" type="number" step="1.0">
									</td>
									<td>
										<input class="form-control total" name="service_total[]" value="<?=$price_total_discount;?>" readonly>  
									</td>
									<td>
										<input class="form-control discount" name="service_discount[]" value="<?=$service["discount"];?>" readonly>  
									</td>
									<td>
										<button type="button" class="btn btn-danger btn-remove-row">Удалить</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<th>&nbsp;</th>
								<th>&nbsp;</th>
								<th colspan="2" style="text-align:right;">ИТОГО:</th>
								<th id="total_hourly_rate"><?=$hourly_rate_count;?></th>
								<th id="total_quantity"><?=$count_total;?></th>
								<th id="total_sum"><?=$price_total;?></th>
								<th></th>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>
    </div>
</div>