<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Меню
		</div>
		<div class="panel-body">
			<?php foreach ($navbar_arr as $navbar) : ?>
				<a class="panel_a" href="<?=str_replace("<backend>", $DP_Config->backend_dir, $navbar["url"]);?>">
					<div class="panel_a_img" style="background-color: <?=$navbar["background_color"];?>;width:96px;height:96px;display:table-cell;vertical-align:middle;">
						<i class="<?=$navbar["fontawesome_class"];?>" style="color:#FFF;font-size:45px"></i>
					</div>
					<div class="panel_a_caption"><?=$navbar["caption"]; ?></div>
				</a>
			<?php endforeach; ?>	
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/">
				<div class="panel_a_img" style="background-color: #a141b0;width:96px;height:96px;display:table-cell;vertical-align:middle;"><i class="fas fa-arrow-left" style="color:#FFF;font-size:45px"></i></div>
				<div class="panel_a_caption">Выход</div>
			</a>
		</div>
	</div>
</div>