<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Действия
		</div>
		<div class="panel-body">
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/car_service/service_personal?id=0">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_add.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Добавить</div>
			</a>
		</div>
	</div>
</div>
 <div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Таблица сотрудников
		</div>
		<div class="panel-body">
			<?php if (count($service_personal_list_arr) > 0) : ?>
			<div class="table-responsive">
				<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
					<thead> 
						<tr> 
							<th></th>
							<th>ID</th>
							<th>ФИО</th>
							<th>Телефон</th>
							<th>Ставка</th>
						</tr>
					</thead>
					<tbody>
						<?php 
							foreach ($service_personal_list_arr as $personal) : 
							$a_item = '<a href="'.$DP_Config->domain_path.$DP_Config->backend_dir.'/car_service/service_personal?id=' . $personal["id"] . '">';
						?>
							<tr>
								<td></td>
								<td><?= $a_item .$personal["id"];?></a></td>
								<td><?=$a_item.$personal["fio"];?></a></td>
								<td><?=$a_item.$personal["phone"];?></a></td>
								<td><?=$a_item.$personal["standart_hourly_rate"];?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else : ?>
				Для добавления сотрудника нажмите кнопку "Добавить".
			<?php endif; ?>
		</div>
	</div>
</div>