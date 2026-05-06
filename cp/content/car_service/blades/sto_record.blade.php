<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Действия
        </div>
        <div class="panel-body">
            <a class="panel_a" onClick="<?= "save_record();";?>" href="javascript:void(0);">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Сохранить</div>
            </a>
            <a class="panel_a"  href="<?=$DP_Config->domain_path . $DP_Config->backend_dir . '/car_service/sto_list_records';?>">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/out.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Все записи</div>
            </a>
            <?php if ($_GET["id"] > 0) : ?>
            <a class="panel_a" onClick="deleteRecordById(<?=$_GET["id"];?>);" href="javascript:void(0);">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Удалить</div>
            </a>
			
			<a class="panel_a" onClick="set_unview(<?=$_GET["id"];?>);" href="javascript:void(0);">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/documents.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Не прочитано</div>
            </a>
			
            <a class="panel_a" target="_blank"  href="<?=$DP_Config->domain_path . $DP_Config->backend_dir . '/content/car_service/print.php?id=' . $_GET["id"];?>">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/menu_manager.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Заказ-наряд</div>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Информация о записи
        </div>
        <div class="panel-body">
            <div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Действия
				</label>
				<div class="col-lg-6">
					<button class="btn btn-warning" onclick="goToRecordPage_3();">Перейти в карточку заказа</button>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
                <label for="" class="col-lg-6 control-label">
                    СТО
                </label>
                <div class="col-lg-6">
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
                            <option <?=$sto_record_data["worker_id"] == $worker["id"] ? 'selected="selected"' : "";?> value="<?=$worker["id"];?>"><?=$worker["fio"]?></option>
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
                    Фактическое врема заезда
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
        </div>
    </div>
</div>

<div class="col-lg-6">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Данные клиента
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Зарегистрированный клиент
                </label>
                <div class="col-lg-12">
                    <select class="form-control" id="users_select" name="users" oninput="fill_options_by_selected_user();">
                        <option value="-1">Не выбрано</option>
                        <?php
                            foreach ($arr_users as $user) {
								?>
								<option 
									data-id="<?=$user["user_id"]?>"
									data-email="<?=$user["email"]?>"
									data-phone="<?=$user["phone"]?>"
									data-name="<?=$user["name"]?>"
									data-surname="<?=$user["surname"]?>"
									<?=$user_json["user_id"] == $user["user_id"] ? 'selected="selected"' : "";?> value="<?=$user["user_id"];?>"><?=$user["data_str"];?></option>
								<?
                            }
                        ?>
                    </select>
                </div>  
            </div>
			<div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
					E-mail
                </label>
                <div class="col-lg-6">
                    <input class="form-control" id="user_email" type="text" value="<?=$user_json["email"];?>">
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Телефон
                </label>
                <div class="col-lg-6">
                    <input class="form-control" id="user_phone" type="text" value="<?=$user_json["phone"];?>">
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    ФИО
                </label>
                <div class="col-lg-6">
                    <input class="form-control" id="user_fio" type="text" value="<?=$user_json["fio"];?>">
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
        </div>
    </div>
</div>

<div class="col-lg-6">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Данные авто
        </div>
        <div class="panel-body">
			<div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Авто клиента
                </label>
                <div class="col-lg-6">
                    <select class="form-control" id="user_car">
						<?php foreach ($cars_arr as $car) : ?>
						<option <?=$car_json["car_id"] == $car["id"] ? 'selected="selected"' : "";?>  value="<?=$car["id"];?>"><?= $car["caption"] . " ( ".$car["model"]." )" ?></option>
						<?php endforeach; ?>
					</select>
                </div>
            </div>
			<div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Название
                </label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" id="user_car_caption" value="<?=$car_json["caption"];?>">
                </div>
            </div>
			<div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Модель
                </label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" id="user_car_model" value="<?=$car_json["model"];?>">
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Гос номер
                </label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" id="user_car_gos_num" value="<?=$car_json["gos_num"];?>">
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    VIN/FRAME
                </label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" id="user_car_vin" value="<?=$car_json["vin"];?>">
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Год выпуска
                </label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" id="user_car_year" value="<?=$car_json["year"];?>">
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Пробег
                </label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" id="user_car_mileage" value="<?=$car_json["mileage"];?>">
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
        </div>
    </div>
</div>

<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Таблица услуг
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
				
				
				<div class="col-lg-6">
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
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div id="table_services_wrapper" class="col-lg-12" style="padding: 0 30px;">
					<table class="table table-bordered">
						<thead>
							<tr>
								<th>Наименование</th>
								<th>Статус</th>
								<th>Цена</th>
								<th>Нормо-час</th>
								<th>Количество</th>
								<th>Итого</th>
								<th>Действие</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($current_services as $service) : ?>
							
								<?php 
									
									$count_total += $service["count"];
									$price_total += $service["price"];
									$hourly_rate_count += $service["hourly_rate"];
								?>
								
								<tr>
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
										<input class="form-control total" name="service_total[]" value="<?=$price_total;?>" readonly>  
									</td>
									<td>
										<button type="button" class="btn btn-danger btn-remove-row">Удалить</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
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

<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Связанный заказ
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    ID заказа
                </label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" id="orders_ids" name="orders_ids" value="<?=$sto_record_data["orders_ids"];?>" />
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
            <div class="col-lg-12">
            <?php
                if (count($order_items) > 0) {
                    ?>
                    <div class="table-responsive">
                        <table class="table table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th>Цена</th>
                                    <th>Количество</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item) : 
                                    $status_id = $item["status"];
                                    $status_name = isset($item_statuses[$status_id]) ? $item_statuses[$status_id]["name"] : "Неизвестный статус";
                                    $status_color = isset($item_statuses[$status_id]) ? $item_statuses[$status_id]["color"] : "#cccccc";
                                ?>
                                    <tr style="background-color: <?=$status_color;?>;">
                                        <td><?=$item["caption"]?></td>
                                        <td><?=$item["price"]?></td>
                                        <td><?=$item["count_need"]?></td>
                                        <td>
											<?=$status_name?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                }
            ?>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#workers_select').select2();
        $('#services_select').select2();
        $('#posts_select').select2();
        $('#users_select').select2();
    });
</script>