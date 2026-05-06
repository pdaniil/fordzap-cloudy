<?php
// Информация по отдельным товарам в виде HTML
defined('_UCatalog_') or die('No access');

$json = json_decode($request_object['json'], true);
$manufacturer = $request_object['manufacturer'];
$article = $request_object['article'];
$name = $request_object['name'];

ob_start();
?>
<div class="row" style="margin: 0;">
	<div class="col-lg-12" style="border-bottom: 1px solid #ddd; padding: 5px 15px; position: relative; font-weight: bold;">
		<i onClick="$('#UCatalog_modal_products_info').modal('hide');" style="position: absolute; top: 8px; right: 8px; font-size: 18px; cursor: pointer;" class="fa fa-times-circle-o" aria-hidden="true"></i>
		<span>Информация о товаре</span>
	</div>
</div>
<div style="padding: 14px 15px 15px; border-bottom: 1px solid #ddd;">
	<div class="row">
		<div style="background: #f8f8f8; border: 1px solid #ddd; padding: 5px 15px 3px 15px; margin: 0; margin-bottom: 15px;" class="col-lg-12"><p style="font-size: 14px; font-weight: 500; padding: 0; margin: 0;"><?=$name;?></p></div>
		<div class="col-sm-4 col-md-6 col-lg-6" style="text-align: center;">
			<?php
			$images = array();
			if(is_array($json['images']) && !empty($json['images'])){
				foreach($json['images'] as $item){
					$item = urlencode($item);
					$images[] = '/content/shop/docpart/ajax_get_info.php?image_path='.$item;
				}
			}
			if(empty($images)){
				$images[] = '/content/files/images/no_image.png';
			}
			?>
			<a style="display: block; border: 1px solid #ddd; border-radius: 5px; padding:15px;" href="<?=$images[0];?>" rel="lightbox-product">
				<div style="height: 200px; background-image: url(<?=$images[0];?>); background-size: contain; background-repeat: no-repeat; background-position: center center;"></div>
			</a>
			<?php
			$cnt = count($images);
			if($cnt > 1){
			?>
			<div style="text-align:left;">
				<?php
				for($i = 1; $i < $cnt; $i++){
				?>
				<a href="<?=$images[$i];?>" rel="lightbox-product" style="line-height: 0; border: 1px solid #ddd; padding: 5px; margin-right:1px; margin-top:5px; border-radius: 5px; display: inline-block;" ><span style="width: 40px; height: 30px; background: url(<?=$images[$i];?>); display: inline-block; background-size: cover; background-position: center center;"></span></a>
				<?php
				}
				?>
			</div>
			<?php
			}
			?>
		</div>
		
		<div class="col-xs-12 hidden-sm hidden-md hidden-lg" style="margin-bottom:20px;"></div>
		
		<div class="col-sm-8 col-md-6 col-lg-6">
			<table class="table" style="position: relative; top: -1px; margin-bottom: 0;">
				<tr>
					<td style="padding-top: 0; vertical-align: top; margin-top: 0; border: 0;">Производитель:</td>
					<td style="padding-top: 0; vertical-align: top; margin-top: 0; border: 0; font-size:12px; text-align:right; padding-right:20px;"><?=$manufacturer;?></td>
				</tr>
				<tr>
					<td>Артикул:</td>
					<td style="font-size:12px; text-align:right; padding-right:20px;"><?=$article;?></td>
				</tr>
			</table>
			
			<div style="text-align:right; margin-top: 10px;">
				<div>
					<span onClick="UCatalog_show_modal_add_notepad('<?php echo $manufacturer; ?>', '<?php echo $article; ?>', '<?php echo $name; ?>');" class="btn_primary"><i class="fa fa-car" aria-hidden="true"></i></span>
					<?php
					if( $DP_Config->chpu_search_config["chpu_search_on"] == true )
					{
						echo '<span onClick="window.open(\''. $DP_Config->domain_path .'parts/'. htmlentities(mb_strtoupper(trim($manufacturer), "UTF-8"), ENT_QUOTES, "UTF-8") .'/'. mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $article), "UTF-8") .'\', \'_blank\');" class="btn_primary">Цены</span>';
					}else{
						echo '<span onClick="window.open(\''. $DP_Config->domain_path .'shop/part_search?brend='. htmlentities(mb_strtoupper(trim($manufacturer), "UTF-8"), ENT_QUOTES, "UTF-8") .'&article='. mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $article), "UTF-8") .'\', \'_blank\');" class="btn_primary">Цены</span>';
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>

<div style="background:#f5f5f5; padding: 5px 15px; border-bottom: 1px solid #ddd; margin-bottom:0px; white-space: nowrap; overflow: hidden; overflow-x: auto;">
	
	<?php
	if(is_array($json['product_status']) && !empty($json['product_status'])){
	?>
	<a style="text-decoration: none; font-size: 12px; background:#fff; color: #444; border: 1px solid #ddd; padding: 4px 10px; border-radius: 4px; margin-right:4px;" href="javascript:void(0);" onclick="show_product_info_tab(1);">Описание</a>
	<?php
	}
	?>
	
	<?php
	if(is_array($json['properties']) && !empty($json['properties'])){
	?>
	<a style="text-decoration: none; font-size: 12px; background:#fff; color: #444; border: 1px solid #ddd; padding: 4px 10px; border-radius: 4px; margin-right:4px;" href="javascript:void(0);" onclick="show_product_info_tab(2);">Характеристики</a>
	<?php
	}
	?>
	
	<?php
	if(is_array($json['original_parts']) && !empty($json['original_parts'])){
	?>
	<a style="text-decoration: none; font-size: 12px; background:#fff; color: #444; border: 1px solid #ddd; padding: 4px 10px; border-radius: 4px; margin-right:4px;" href="javascript:void(0);" onclick="show_product_info_tab(3);">Оригиналы</a>
	<?php
	}
	?>
	
	<?php
	if(is_array($json['applicability']) && !empty($json['applicability'])){
	?>
	<a style="text-decoration: none; font-size: 12px; background:#fff; color: #444; border: 1px solid #ddd; padding: 4px 10px; border-radius: 4px; margin-right:4px;" href="javascript:void(0);" onclick="show_product_info_tab(4);">Применимость</a>
	<?php
	}
	?>
	
	<?php
	if(is_array($json['replacements']) && !empty($json['replacements'])){
	?>
	<a style="text-decoration: none; font-size: 12px; background:#fff; color: #444; border: 1px solid #ddd; padding: 4px 10px; border-radius: 4px; margin-right:4px;" href="javascript:void(0);" onclick="show_product_info_tab(5);">Замены</a>
	<?php
	}
	?>
	
	<?php
	if(is_array($json['crosses']) && !empty($json['crosses'])){
	?>
	<a style="text-decoration: none; font-size: 12px; background:#fff; color: #444; border: 1px solid #ddd; padding: 4px 10px; border-radius: 4px; margin-right:4px;" href="javascript:void(0);" onclick="show_product_info_tab(6);">Аналоги</a>
	<?php
	}
	?>
	
	<?php
	if(is_array($json['parts']) && !empty($json['parts'])){
	?>
	<a style="text-decoration: none; font-size: 12px; background:#fff; color: #444; border: 1px solid #ddd; padding: 4px 10px; border-radius: 4px;" href="javascript:void(0);" onclick="show_product_info_tab(7);">Части</a>
	<?php
	}
	?>
	
</div>

<?php
if(is_array($json['product_status']) && !empty($json['product_status'])){
	?>
	<div id="product_info_tab_1" class="product_info_tab" style="padding: 15px;">
		<b>Описание производителя</b>
		<div style="overflow: hidden; overflow-x: auto;">
			<table class="table" style="font-size:12px;">
				<tbody>
				<?php
				foreach($json['product_status'] as $item){
					?>
					<tr>
						<td><?=$item['description'];?></td>
					</tr>
					<tr>
						<td><strong>Статус: </strong><?=$item['status_value'];?></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
?>



<?php
if(is_array($json['properties']) && !empty($json['properties'])){
	?>
	<div id="product_info_tab_2" class="product_info_tab" style="display:none; padding: 15px;">
		<b>Характеристики</b>
		<div style="overflow: hidden; overflow-x: auto;">
			<table class="table" style="font-size:12px;">
				<tbody>
				<?php
				foreach($json['properties'] as $item){
					if(empty($item['title'])){
						$item['title'] = 'Свойство';
					}
					?>
					<tr>
						<td><?=$item['title'];?></td>
						<td><?=$item['value'];?></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
?>



<?php
if(is_array($json['original_parts']) && !empty($json['original_parts'])){
	?>
	<div id="product_info_tab_3" class="product_info_tab" style="display:none; padding: 15px;">
		<b>Оригинальные номера</b>
		<div style="overflow: hidden; overflow-x: auto;">
			<table class="table" style="font-size:12px;">
				<thead>
					<tr>
						<th>Производитель</th>
						<th>Артикул</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach($json['original_parts'] as $item){
					?>
					<tr>
						<td><?=$item['manufacturer'];?></td>
						<td><?=$item['article'];?></td>
						<td style="text-align:right;"><a target="_blank" style="color:#222; white-space: nowrap;" href="/parts/<?=$item['manufacturer'];?>/<?=$item['article'];?>"><i class="fa fa-search" aria-hidden="true"></i> Поиск</a></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
?>


<?php
if(is_array($json['applicability']) && !empty($json['applicability'])){
	?>
	<div id="product_info_tab_4" class="product_info_tab" style="display:none; padding: 15px;">
		<b>Применимость запрошенного товара к автомобилям</b>
		<div style="overflow: hidden; overflow-x: auto;">
			<table class="table" style="font-size:12px;">
				<thead>
					<tr>
						<th>Марка</th>
						<th>Модель</th>
						<th>Годы производства</th>
						<th style="text-align:right;">Описание</th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach($json['applicability'] as $item){
					?>
					<tr>
						<td><?=$item['mark'];?></td>
						<td><?=$item['model'];?></td>
						<td><?=$item['years'];?></td>
						<td style="text-align:right;"><?=$item['description'];?></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
?>


<?php
if(is_array($json['replacements']) && !empty($json['replacements'])){
	?>
	<div id="product_info_tab_5" class="product_info_tab" style="display:none; padding: 15px;">
		<b>Замены запрошенного товара</b><br/>
		<small>Товары, которые этот же производитель выпустил на замену запрошенного товара</small>
		<div style="overflow: hidden; overflow-x: auto;">
			<table class="table" style="font-size:12px;">
				<thead>
					<tr>
						<th>Производитель</th>
						<th>Артикул</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach($json['replacements'] as $item){
					?>
					<tr>
						<td><?=$item['manufacturer'];?></td>
						<td><?=$item['article'];?></td>
						<td style="text-align:right;"><a target="_blank" style="color:#222; white-space: nowrap;" href="/parts/<?=$item['manufacturer'];?>/<?=$item['article'];?>"><i class="fa fa-search" aria-hidden="true"></i> Поиск</a></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
?>


<?php
if(is_array($json['crosses']) && !empty($json['crosses'])){
	?>
	<div id="product_info_tab_6" class="product_info_tab" style="display:none; padding: 15px;">
		<b>Аналоги запрошенного товара</b>
		<div style="overflow: hidden; overflow-x: auto;">
			<table class="table" style="font-size:12px;">
				<thead>
					<tr>
						<th>Производитель</th>
						<th>Артикул</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach($json['crosses'] as $item){
					?>
					<tr>
						<td><?=$item['manufacturer'];?></td>
						<td><?=$item['article'];?></td>
						<td style="text-align:right;"><a target="_blank" style="color:#222; white-space: nowrap;" href="/parts/<?=$item['manufacturer'];?>/<?=$item['article'];?>"><i class="fa fa-search" aria-hidden="true"></i> Поиск</a></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
?>


<?php
if(is_array($json['parts']) && !empty($json['parts'])){
	?>
	<div id="product_info_tab_7" class="product_info_tab" style="display:none; padding: 15px;">
		<b>Составные части запрошенного товара</b>
		<div style="overflow: hidden; overflow-x: auto;">
			<table class="table" style="font-size:12px;">
				<thead>
					<tr>
						<th>Производитель</th>
						<th>Артикул</th>
						<th>Количество в составе</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach($json['parts'] as $item){
					?>
					<tr>
						<td><?=$item['manufacturer'];?></td>
						<td><?=$item['article'];?></td>
						<td><?=$item['count'];?></td>
						<td style="text-align:right;"><a target="_blank" style="color:#222; white-space: nowrap;" href="/parts/<?=$item['manufacturer'];?>/<?=$item['article'];?>"><i class="fa fa-search" aria-hidden="true"></i> Поиск</a></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
?>


<?php

$html = ob_get_contents();
ob_end_clean();

$answer["status"] = true;
$answer["html"] = $html;
$answer["key"] = $request_object['key'];
?>