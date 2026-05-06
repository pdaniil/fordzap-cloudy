<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Действия
		</div> 
		<div class="panel-body">
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/car_service/sto_record">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_add.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Добавить</div>
			</a>
			<a class="panel_a" href="javascript:void(0);" onclick="archive_select_records();">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/documents.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">В архив</div>
			</a>
			<a class="panel_a" href="javascript:void(0);" onclick="unarchive_select_records();">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/documents_folder.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Из архива</div>
			</a>
			<a class="panel_a" href="javascript:void(0);" onclick="delete_select_records();">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Удалить</div>
			</a>
		</div>
	</div>
</div>

<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Фильтр записей <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('На данной странице показаны учетные записи позиций. ');"><i class="fa fa-info"></i></button>
			</div>
			<div class="panel-body">	
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Дата с
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
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Дата по
						</label>
						<div class="col-lg-6">
							<div style="position:relative;height:34px;">
								<input style="position:absolute; z-index:2; opacity:0" type="text"  id="time_to" value="<?php echo $time_to; ?>" autocomplete="off" class="form-control" />
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
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер записи
						</label>
						<div class="col-lg-6">
							<input type="text"  id="order_id" value="" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Авто
						</label>
						<div class="col-lg-6">
							<input type="text"  id="auto_str" value="" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Мастер
						</label>
						<div class="col-lg-6">
							<select id="worker_id" class="form-control">
								<option id="-1">Не выбрано</option>
								<?php foreach ($worker_filter_list as $worker) : ?>
									<option id="<?=$worker["id"]?>"><?=$worker["fio"];?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Пост
						</label>
						<div class="col-lg-6">
							<select id="post_id" class="form-control">
								<option id="-1">Не выбрано</option>
								<?php foreach ($post_filter_list as $post) : ?>
									<option id="<?=$post["id"]?>"><?=$post["caption"];?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							СТО
						</label>
						<div class="col-lg-6">
							<select id="sto_id" class="form-control">
								<option id="-1">Не выбрано</option>
								<?php foreach ($sto_filter_list as $sto) : ?>
									<option id="<?=$sto["id"]?>"><?=$sto["caption"];?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Оплачен
						</label>
						<div class="col-lg-6">
							<select id="paid" class="form-control">
								<option value="-1">Все</option>
								<option value="1">Оплачен</option>
								<option value="0">Не оплачен</option>
							</select>
							<script>
								document.getElementById("paid").value = <?php echo $paid; ?>;
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							<?php
							//Для подсказки для поля Клиент
							$fields_for_customer_search = "ID, E-mail, Телефон";
							//Дополнительно сюда выводим перечень полей профиля пользователя:
							$users_profile_fields_query = $db_link->prepare("SELECT `caption` FROM `reg_fields` WHERE `to_users_table` = 1;");
							$users_profile_fields_query->execute();
							while( $users_profile_field = $users_profile_fields_query->fetch() )
							{
								$fields_for_customer_search = $fields_for_customer_search.", ".$users_profile_field["caption"];
							}
							?>
						
							Клиент <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('В это поле можно ввести одно из полей данных покупателя: <?php echo $fields_for_customer_search; ?>. Допустим поиск по частичному совпадению');"><i class="fa fa-info"></i></button>
						</label>
						<div class="col-lg-6">
							<input type="text"  id="customer" value="<?php echo $customer; ?>" class="form-control" />
						</div>
					</div> 
				</div>
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Просмотрен
						</label>
						<div class="col-lg-6">
							<select id="viewed" class="form-control">
								<option value="-1">Все</option>
								<option value="1">Просмотрен</option>
								<option value="0">Не просмотрен</option>
							</select>
							<script>
								document.getElementById("viewed").value = <?php echo $viewed; ?>;
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							В архиве
						</label>
						<div class="col-lg-6">
							<input type="checkbox" id="is_archive" class="form-control">
						</div>
					</div>
				</div>
				
			</div>
			<div class="panel-footer">
				<button class="btn btn-success" type="button" onclick="set_filter_records();"><i class="fa fa-filter"></i> Отфильтровать</button>
				<button class="btn btn-primary" type="button" onclick="unset_filter_var();"><i class="fa fa-square"></i> Снять фильры</button>
			</div>
		</div>
	</div>

 <div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Записи в автосервис
		</div>
		<div class="panel-body">
			<div class="table-responsive">
				<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
					<thead> 
						<tr> 
							<th><input type="checkbox" id="main_checkbox"></th>
							<th>ID</th>
							<th>СТО</th>
							<th>Мастер</th>
							<th>Пост</th>
							<th>Оплачен</th>
							<th>Клиент</th>
							<th>Авто</th> 
							<th>Дата</th>
						</tr>
					</thead>
					<tbody id="main_wrapper_records">
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>