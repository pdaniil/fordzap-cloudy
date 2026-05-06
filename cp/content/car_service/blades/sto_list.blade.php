<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Действия
		</div>
		<div class="panel-body">
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/car_service/sto?id=0">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_add.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Добавить</div>
			</a>
		</div>
	</div>
</div>
 <div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Таблица СТО
		</div>
		<div class="panel-body">
			<?php if (count($sto_list_arr) > 0) : ?>
			<div class="table-responsive">
				<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
					<thead> 
						<tr> 
							<th></th>
							<th>ID</th>
							<th>Название</th>
							<th>Адрес</th>
							<th>Режим работы</th>
							<th>Время работы</th>
						</tr>
					</thead>
					<tbody>
						<?php
							$arr_days = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

							foreach ($sto_list_arr as $sto) : 
							$a_item = '<a href="'.$DP_Config->domain_path.$DP_Config->backend_dir.'/car_service/sto?id=' . $sto["id"] . '">';
							$arr = json_decode($sto["operating_mode"], true);
							$str_day = [];
							foreach ($arr as $day_num)
								$str_day[] = $arr_days[$day_num - 1];
							$str_day = implode(',', $str_day);
						?>
							<tr>
								<td></td>
								<td><?= $a_item .$sto["id"];?></a></td>
								<td><?=$a_item.$sto["caption"];?></a></td>
								<td><?=$a_item.$sto["address"];?></a></td>
								<td><?=$a_item.$str_day;?></a></td>
								<td><?=$a_item.$sto["time_from"] . ':00 - ' . $sto['time_to'] . ':00';?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else : ?>
				Для создания СТО нажмите кнопку "Добавить".
			<?php endif; ?>
		</div>
	</div>
</div>