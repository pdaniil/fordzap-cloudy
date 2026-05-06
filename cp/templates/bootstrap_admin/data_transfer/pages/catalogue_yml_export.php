<?php
/**
*	Страничный скрипт для страницы экспорта каталога в xml
*/
defined('_ASTEXE_') or die('No access');

//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getAdminSession();
?>



<?php

// Настройки сохраненные при прошлом запуске
$settings_yml = null;
if( file_exists($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir.'/content/shop/data_transfer/ajax/settings_yml.php') ){
	$file_handle = @fopen($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir.'/content/shop/data_transfer/ajax/settings_yml.php', "r");
	if($file_handle) 
	{
		$json = fgets($file_handle, 4096);
		$export_options = json_decode($json, true);
		if(!empty($export_options['export_options'])){
			$settings_yml = json_decode($export_options['export_options'], true);
		}
		
	}
}

?>



<link rel="stylesheet" href="/lib/webix/codebase/webix.css" type="text/css" />
<script src="/lib/webix/codebase/webix.js" type="text/javascript"></script>
<link rel="stylesheet" href="/<backend_dir>/templates/<template_dir>/css/control/control.css" type="text/css" />
<script src="/lib/iso_9_js_master_translit/translit.js" type="text/javascript"></script>



<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Действия
		</div>
		<div class="panel-body">
			
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/perenos-dannyx">
				<div class="panel_a_img" style="background-color: #b9babb;width:96px;height:96px;display:table-cell;vertical-align:middle;"><i class="fas fa-chevron-left" style="color:#FFF;font-size:45px"></i></div>
				<div class="panel_a_caption">Назад</div>
			</a>
			
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Выход</div>
			</a>
			
		</div>
	</div>
</div>



<div class="col-md-12">
	<div class="hpanel">
		<div class="panel-body">

			<button style="margin-right:2px;" onclick="exec_export(0);" class="btn btn-success " type="button"><i class="fa fa-download"></i> <span class="bold">Выгрузить для модели DBS</span></button>
			<button onclick="exec_export(1);" class="btn btn-warning " type="button"><i class="fa fa-download"></i> <span class="bold">Выгрузить для модели FBY и FBS</span></button>
			<span id="ink" style="display:none;"><img style="height: 31px; margin-right: 5px;" src="/content/files/images/ajax-loader-transparent.gif"/> Подождите, идет формирование файла</span>
			<span id="result_box" style="display:none;"></span>
			<div id="result_info_box" style="display:none; padding-top: 15px;"></div>

		</div>
	</div>
</div>



<div class="col-md-6">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Настройки
		</div>
		<div class="panel-body">
		<div class="row">
			
			<div class="form-group col-lg-6">
				<label for="" class="col-lg-12 control-label">
					Магазины
				</label>
				<div class="col-lg-12">
					<select multiple="multiple" id="offices">
						<?php
						$offices_query = $db_link->prepare("SELECT * FROM `shop_offices`;");
						$offices_query->execute();
						$flag = true;
						while( $office = $offices_query->fetch() )
						{
							$selected = '';
							if(!empty($settings_yml['offices'])){
								if(in_array($office["id"], $settings_yml['offices'])){
									$selected = 'selected';
								}
							}else{
								if($flag){
									$selected = 'selected';
								}
							}
							?>
							<option <?php echo $selected; ?> value="<?php echo $office["id"]; ?>"><?php echo $office["caption"]." (ID ".$office["id"].")"; ?></option>
							<?php
							$flag = false;
						}
						?>
					</select>
					<script>
						//Делаем из селектора виджет с чекбоками
						$('#offices').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});
						//$("#offices").multipleSelect('checkAll');
					</script>
					<style>
					.ms-choice {
						height: 34px !important;
						line-height: 34px !important;
						border: 1px solid #e4e5e7 !important;
						font-size: 14px !important;
					}
					</style>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-6">
				<label for="" class="col-lg-12 control-label">
					Склады
				</label>
				<div class="col-lg-12">
					<select multiple="multiple" id="storages">
						<?php
						$storages_query = $db_link->prepare("SELECT * FROM `shop_storages` WHERE `interface_type` = 1;");
						$storages_query->execute();
						$flag = true;
						while( $storage = $storages_query->fetch() )
						{
							$selected = '';
							if(!empty($settings_yml['storages'])){
								if(in_array($storage["id"], $settings_yml['storages'])){
									$selected = 'selected';
								}
							}else{
								if($flag){
									$selected = 'selected';
								}
							}
							?>
							<option <?php echo $selected; ?> value="<?php echo $storage["id"]; ?>"><?php echo $storage["name"]." (ID ".$storage["id"].")"; ?></option>
							<?php
							$flag = false;
						}
						?>
					</select>
					<script>
						//Делаем из селектора виджет с чекбоками
						$('#storages').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});
					</script>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-6">
				<label for="" class="col-lg-12 control-label">
					Группа пользователей от которой будут браться наценки
				</label>
				<div class="col-lg-12">
					<select id="groups" class="form-control">
						<?php
						$groups_query = $db_link->prepare("SELECT * FROM groups;");
						$groups_query->execute();
						while( $group = $groups_query->fetch() )
						{
							if($group["id"] == 1){
								continue;
							}
							$selected = '';
							if(!empty($settings_yml['group_id'])){
								if($settings_yml['group_id'] == $group["id"]){
									$selected = 'selected';
								}
							}else{
								if($group['for_guests']){
									$selected = 'selected';
								}
							}
							?>
							<option <?php echo $selected; ?> value="<?php echo $group["id"]; ?>"><?php echo $group["value"]; ?> (ID <?php echo $group["id"]; ?>)</option>
							<?php
						}
						?>
					</select>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			
			<div class="form-group col-lg-6">
				<label for="" class="col-lg-12 control-label">
					Фильтр цены, пропустить позиции не входящие в диапазон
				</label>
				<div class="col-lg-6">
					<input class="form-control" type="number" id="price_min_property" value="" placeholder="Min" />
					<?php
					if(isset($settings_yml['price_min_property'])){
					?>
					<script>
						document.getElementById("price_min_property").value = "<?php echo $settings_yml['price_min_property']; ?>";
					</script>
					<?php
					}
					?>
				</div>
				<div class="col-lg-6">
					<input class="form-control" type="number" id="price_max_property" value="" placeholder="Max" />
					<?php
					if(isset($settings_yml['price_max_property'])){
					?>
					<script>
						document.getElementById("price_max_property").value = "<?php echo $settings_yml['price_max_property']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			
			<div class="form-group col-lg-6">
				<label for="" class="col-lg-12 control-label">
					Способ выгрузки данных
				</label>
				<div class="col-lg-12">
					<select id="data_output_mode" class="form-control">
						<option value="create_file">Сохранить файл в папке на сервере</option>
						<option selected value="download_file">Скачать в виде файла XML</option>
						<option value="open_file_browser">Открыть в отдельной вкладке браузера</option>
					</select>
					<?php
					if(!empty($settings_yml['data_output_mode'])){
					?>
					<script>
						document.getElementById("data_output_mode").value = "<?php echo $settings_yml['data_output_mode']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-6">
				<label for="" class="col-lg-12 control-label">
					Выгрузка свойств товаров
				</label>
				<div class="col-lg-12">
					<select id="properties_all_flag" class="form-control">
						<option value="1">Выгружать все свойства товаров</option>
						<option selected value="0">Тлько обязательные свойства товаров</option>
					</select>
					<?php
					if(isset($settings_yml['properties_all_flag'])){
					?>
					<script>
						document.getElementById("properties_all_flag").value = "<?php echo $settings_yml['properties_all_flag']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-6">
				<label for="" class="col-lg-12 control-label">
					Выгрузка всех категорий товаров, включая не опубликованные
				</label>
				<div class="col-lg-12">
					<select id="no_published_category_upload_flag" class="form-control">
						<option value="1">Выгружать все категории</option>
						<option selected value="0">Тлько опубликованные категории</option>
					</select>
					<?php
					if(isset($settings_yml['no_published_category_upload_flag'])){
					?>
					<script>
						document.getElementById("no_published_category_upload_flag").value = "<?php echo $settings_yml['no_published_category_upload_flag']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-6">
				<label for="" class="col-lg-12 control-label">
					Выгрузка всех товаров, включая не опубликованные
				</label>
				<div class="col-lg-12">
					<select id="no_published_product_upload_flag" class="form-control">
						<option value="1">Выгружать все товары</option>
						<option selected value="0">Тлько опубликованные товары</option>
					</select>
					<?php
					if(isset($settings_yml['no_published_product_upload_flag'])){
					?>
					<script>
						document.getElementById("no_published_product_upload_flag").value = "<?php echo $settings_yml['no_published_product_upload_flag']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
		</div>
		</div>
	</div>
	
	<div class="row">
	<div class="col-md-6">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Настройки для модели DBS
		</div>
		<div class="panel-body">
		<div class="row">
			
			<div class="form-group col-lg-12">
				<label for="" class="col-lg-12 control-label">
					Выгружать тег count - Наличие
				</label>
				<div class="col-lg-12">
					<select id="count_flag" class="form-control">
						<option selected value="1">Да</option>
						<option value="0">Нет</option>
					</select>
					<?php
					if(isset($settings_yml['count_flag'])){
					?>
					<script>
						document.getElementById("count_flag").value = "<?php echo $settings_yml['count_flag']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-12">
				<label for="" class="col-lg-12 control-label">
					Выгружать тег currencyId - Валюта
				</label>
				<div class="col-lg-12">
					<select id="currencyId_flag" class="form-control">
						<option selected value="1">Да</option>
						<option value="0">Нет</option>
					</select>
					<?php
					if(isset($settings_yml['currencyId_flag'])){
					?>
					<script>
						document.getElementById("currencyId_flag").value = "<?php echo $settings_yml['currencyId_flag']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
		</div>
		</div>
	</div>
	
	<div class="row"></div>
	
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Дополнительно
		</div>
		<div class="panel-body" style="padding-bottom: 58px;">
		<div class="row">
			
			<div class="form-group col-lg-12">
				<label for="" class="col-lg-12 control-label">
					Запомнить текущие настройки после нажатия кнопки
				</label>
				<div class="col-lg-12">
					<select id="save_flag" class="form-control">
						<option selected value="1">Да</option>
						<option value="0">Нет</option>
						<option value="3">Сбросить настройки после выгрузки</option>
					</select>
					<?php
					if(isset($settings_yml['save_flag'])){
					?>
					<script>
						document.getElementById("save_flag").value = "<?php echo $settings_yml['save_flag']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
		</div>
		</div>
	</div>
	
	</div>
		
	<div class="col-md-6">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Настройки для модели FBY и FBS
		</div>
		<div class="panel-body">
		<div class="row">
			
			<div class="form-group col-lg-12">
				<label for="" class="col-lg-12 control-label">
					Доступна ли курьерская доставка
				</label>
				<div class="col-lg-12">
					<select id="delivery_flag" class="form-control">
						<option value="1">Да</option>
						<option selected value="0">Нет</option>
					</select>
					<?php
					if(isset($settings_yml['delivery_flag'])){
					?>
					<script>
						document.getElementById("delivery_flag").value = "<?php echo $settings_yml['delivery_flag']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-12">
				<label for="" class="col-lg-12 control-label">
					Название свойства "Вес" в ваших категориях
				</label>
				<div class="col-lg-12">
					<input class="form-control" type="text" id="weight_property" value="" />
					<?php
					if(isset($settings_yml['weight_property'])){
					?>
					<script>
						document.getElementById("weight_property").value = "<?php echo $settings_yml['weight_property']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-12">
				<label for="" class="col-lg-12 control-label">
					Название свойства "Длина" в ваших категориях
				</label>
				<div class="col-lg-12">
					<input class="form-control" type="text" id="length_property" value="" />
					<?php
					if(isset($settings_yml['length_property'])){
					?>
					<script>
						document.getElementById("length_property").value = "<?php echo $settings_yml['length_property']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-12">
				<label for="" class="col-lg-12 control-label">
					Название свойства "Высота" в ваших категориях
				</label>
				<div class="col-lg-12">
					<input class="form-control" type="text" id="height_property" value="" />
					<?php
					if(isset($settings_yml['height_property'])){
					?>
					<script>
						document.getElementById("height_property").value = "<?php echo $settings_yml['height_property']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12 hidden-lg"></div>
			
			<div class="form-group col-lg-12">
				<label for="" class="col-lg-12 control-label">
					Название свойства "Ширина" в ваших категориях
				</label>
				<div class="col-lg-12">
					<input class="form-control" type="text" id="width_property" value="" />
					<?php
					if(isset($settings_yml['width_property'])){
					?>
					<script>
						document.getElementById("width_property").value = "<?php echo $settings_yml['width_property']; ?>";
					</script>
					<?php
					}
					?>
				</div>
			</div>
			
		</div>
		</div>
	</div>
	</div>
	</div>
	
</div>










<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/dp_category_record.php");//Определение класса записи категории
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/get_catalogue_tree.php");//Получение объекта иерархии существующих категорий для вывода в дерево-webix
?>

<div class="col-md-6">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
		Категории
		</div>
		<div class="panel-body">
			
			<div>
				<div style="padding:0 0 10px 0;">
				<button style="margin-right:2px;" onclick="catalogue_tree.checkAll();" class="btn btn-ar btn-success" title="Отметить все"><i class="fa fa-check-square-o"></i></button>
				<button onclick="catalogue_tree.uncheckAll();" class="btn btn-ar btn-primary2" title="Снять все"><i class="fa fa-square-o"></i></button>
				
				<button style="float: right; margin-left:5px;" onclick="catalogue_tree.openAll();" class="btn btn-ar btn-danger" title="Развернуть элементы"><i class="fa fa-long-arrow-down"></i></button>
				<button style="float: right;" onclick="catalogue_tree.closeAll();" class="btn btn-ar btn-info" title="Свернуть элементы"><i class="fa fa-long-arrow-up"></i></button>
				</div>
			</div>
			
			<div id="container_A" style="height:717px;"></div>
			
		</div>
	</div>
</div>

<script>
//Массив ID не опубликованных категорий с их вложенными подкатегориями что бы не выводить у них возможность выбора
var no_published = new Array();

/*ДЕРЕВО КАТАЛОГА ТОВАРОВ*/
//Для редактируемости дерева
webix.protoUI({
	name:"edittree"
}, webix.EditAbility, webix.ui.tree);
//Формирование дерева
catalogue_tree = new webix.ui({
	editable:false,//не редактируемое
	container:"container_A",//id блока div для дерева
	view:"tree",
	select:false,//можно выделять элементы
	drag:false,//можно переносить
	//Шаблон элемента дерева
	template:function(obj, common)//Шаблон узла дерева
		{
			var folder = common.folder(obj, common);
			var value_text = "<span>" + obj.value + "</span>";//Вывод текста
			var checkbox = common.checkbox(obj, common);
			
			//Индикация материала, снятого с публикации
			var icon_system = "";
			if(no_published.indexOf(obj.$parent) !== -1){
				no_published.push(obj.id);//Добавляем ID в массив
				value_text = "<span title=\"Категория находится внутри неопубликованной  категории\" style=\"color:#AAA\">" + obj.value + "</span>";//Вывод текста
				//checkbox = '';
			}
			if(obj.published_flag == false)
			{
				
				console.log(obj);
				
				no_published.push(obj.id);//Добавляем ID в массив
				
				icon_system += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
				value_text = "<span title=\"Категория не опубликована\" style=\"color:#AAA\">" + obj.value + "</span>";//Вывод текста
				//checkbox = '';
				
			}
			
			
			return common.icon(obj, common) + checkbox + folder + icon_system + value_text;
		},//~template
});
webix.event(window, "resize", function(){ catalogue_tree.adjust(); });

var saved_catalogue = <?php echo $catalogue_tree_dump_JSON; ?>;
catalogue_tree.parse(saved_catalogue);
catalogue_tree.openAll();

<?php
if(!empty($settings_yml['arr_category']))
{
	foreach($settings_yml['arr_category'] as $item){
		?>
		if(catalogue_tree.exists(<?php echo $item; ?>)){
			catalogue_tree.checkItem(<?php echo $item; ?>);
		}
		<?php
	}
}
else
{
	?>
	catalogue_tree.checkAll();
	<?php
}
?>

/*~ДЕРЕВО*/

catalogue_tree.attachEvent("onItemCheck", function(id){
	onTreeListItemCheck(id);
});

// ---------------------------------------------------------------------------------------------
//ОБРАБОТКА выделения вложенных элементов
var auto_check = false;//Для предотвращения обработки программного выставления чекбоксов (семафор)
function onTreeListItemCheck(item_id)
{
	if(auto_check)
	{
		return;
	}
	
	//Получаем состояние отмеченного элемента:
	var is_checked = eval("catalogue_tree").isChecked(item_id);
	
	//Массив вложенных элементов
	var childItems = getChildItems(item_id);
	
	auto_check = true;//Начинаем обработку чекбоксов
	
	
	//Далее логика
	if( is_checked )
	{
		
		//Выставляются все элементы, вложенные в него (рекурсивно, т.е. до упора), а также, отмечаются все элементы, находящиеся выше него по цепочке - до самого верхнего
		//Обработка вложенных элементов
		var childItems = getChildItems(item_id);
		for(var i=0; i < childItems.length; i++)
		{
			eval("catalogue_tree").checkItem( childItems[i] );
		}
		
		//Обработка элементов родительской ветви
		var parent_brunch = getUpperBrunch(item_id);
		for(var i=0; i < parent_brunch.length; i++)
		{
			eval("catalogue_tree").checkItem( parent_brunch[i] );
		}
	}
	else
	{
		if( eval("catalogue_tree").getItem(item_id).$count != eval("catalogue_tree").getItem(item_id).webix_kids && eval("catalogue_tree").getItem(item_id).webix_kids != undefined )
		{
			webix.message("В узле " + eval("catalogue_tree").getItem(item_id).value + " ("+item_id+") не загружены вложенные узлы. Раскройте узел для управления вложенными узлами");
		}

		
		//Снимаются все элементы, вложенные в него (рекурсивно, т.е. до упора). При этом, элементы, находящиеся выше, остаются отмеченными
		//Обработка вложенных элементов
		var childItems = getChildItems(item_id);
		for(var i=0; i < childItems.length; i++)
		{
			eval("catalogue_tree").uncheckItem( childItems[i] );
			
			if( eval("catalogue_tree").getItem(childItems[i]).$count != eval("catalogue_tree").getItem(childItems[i]).webix_kids && eval("catalogue_tree").getItem(childItems[i]).webix_kids != undefined )
			{
				webix.message("В узле " + eval("catalogue_tree").getItem(childItems[i]).value + " ("+childItems[i]+") не загружены вложенные узлы. Раскройте узел для управления вложенными узлами");
			}
		}
	}
	auto_check = false;//Прекращаем обработку чекбоксов
}
// ---------------------------------------------------------------------------------------------
//Рекурсивная функция получения всех вложенных элементов указанного узла дерева
function getChildItems(item_id)
{
	var childItems = new Array();//Массив вложенных элеметов

	var first = true;
	var nextItem = undefined;
	
	while(true)
	{
		if(first)
		{
			nextItem = eval("catalogue_tree").getFirstChildId( item_id );//Первый вложенный элемент
			
			first = false;
		}
		else
		{
			nextItem = eval("catalogue_tree").getNextSiblingId( nextItem );//Следующий вложенный элемент
		}
		
		
		if( nextItem == null ){break;}
		childItems.push(nextItem);//Добавляем первый вложенный элемент в массив
		
		
		if( eval("catalogue_tree").getFirstChildId( nextItem ) != null )
		{	
			childItems = childItems.concat(getChildItems(nextItem));
		}
	}
	
	return childItems;
}
// ---------------------------------------------------------------------------------------------
//Рекурсивная функция получения всей родительской ветви к верху дерева
function getUpperBrunch(item_id)
{
	var parent_brunch = new Array();//Массив ветви
	
	var parent_id = eval("catalogue_tree").getParentId(item_id);//ID родительского узла
	
	//console.log(parent_id);
	
	if(parent_id != 0)
	{
		parent_brunch.push(parent_id);
		
		parent_brunch = parent_brunch.concat(getUpperBrunch(parent_id));
	}
	
	return parent_brunch;
}
// ---------------------------------------------------------------------------------------------
</script>










<a href="" id="a_download" target="_blank" download></a>
<a href="" id="a_open_tab" target="_blank"></a>

<script>
var flag = false;
//Функция запроса на экспорт
function exec_export(FBY_model)
{	
	if(flag){
		return false;
	}
	
	var request = new Object;
	request.data_output_mode = document.getElementById("data_output_mode").value;
	request.offices = [].concat( $("#offices").multipleSelect('getSelects') );
	request.storages = [].concat( $("#storages").multipleSelect('getSelects') );
	request.group_id = document.getElementById("groups").value;
	request.price_min_property = document.getElementById("price_min_property").value;
	request.price_max_property = document.getElementById("price_max_property").value;
	
	request.FBY_flag = FBY_model;//Если магазин работает по модели FBY или FBS
	
	request.properties_all_flag = document.getElementById("properties_all_flag").value;// Выгружать все свойства товаров (0 / 1)
	request.no_published_category_upload_flag = document.getElementById("no_published_category_upload_flag").value;// Выгрузка всех категорий товаров, включая не опубликованные (0 / 1)
	request.no_published_product_upload_flag = document.getElementById("no_published_product_upload_flag").value;// Выгрузка всех товаров, включая не опубликованные (0 / 1)
	
	// DBS
	request.count_flag = document.getElementById("count_flag").value;// count_flag
	request.currencyId_flag = document.getElementById("currencyId_flag").value;// currencyId_flag
	
	// FBS
	request.delivery_flag = document.getElementById("delivery_flag").value;// Флаг доступна ли курьерская доставка (0 / 1)
	request.weight_property = document.getElementById("weight_property").value;// Вес
	request.length_property = document.getElementById("length_property").value;// Длина
	request.height_property = document.getElementById("height_property").value;// Высота
	request.width_property = document.getElementById("width_property").value;// Ширина
	
	if(FBY_model == 1){
		if(request.weight_property == '' || request.length_property == '' || request.height_property == '' || request.width_property == ''){
			alert('Необходимо указать наименования свойст размеров как они указаны в ваших категориях, для того что бы при формировании обязательного тега dimensions корректно выбрать данные у товаров.');
			flag = false;
			return false;
		}
	}
	
	if(request.offices.length == 0){
		alert('Необходимо указать магазин от которого будут браться цены.');
		flag = false;
		return false;
	}
	
	if(request.storages.length == 0){
		alert('Необходимо указать склад от которого будут браться цены.');
		flag = false;
		return false;
	}
	
	request.save_flag = document.getElementById("save_flag").value;

	var arr_category = catalogue_tree.getChecked();
	if(arr_category.length <= 0){
		alert('Выберете категории');
		flag = false;
		return false;
	}
	request.arr_category = arr_category;
	
	flag = true;
	document.getElementById('result_box').style.display = 'none';
	document.getElementById('result_info_box').style.display = 'none';
	document.getElementById('result_box').innerHTML = '';
	document.getElementById('result_info_box').innerHTML = '';
	document.getElementById('ink').style.display = 'inline-block';
	
	jQuery.ajax({
		type: "GET",
		async: true, //Запрос синхронный
		url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/data_transfer/ajax/ajax_export_to_yml.php",
		dataType: "json",//Тип возвращаемого значения
		data: "export_options="+encodeURI(JSON.stringify(request))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
		success: function(answer)
		{
			flag = false;
			document.getElementById('ink').style.display = 'none';
			document.getElementById('result_box').style.display = 'inline-block';
			console.log(answer);
			if(answer.status == true)
			{
				if(document.getElementById("data_output_mode").value == "create_file")
				{
					document.getElementById('result_box').innerHTML = "Файл создан в папке на сервере, и доступен по адресу: <b><?php echo $DP_Config->domain_path; ?><?php echo $DP_Config->backend_dir; ?>/tmp/"+answer.filename+"</b>";
				}
				else if(document.getElementById("data_output_mode").value == "download_file")
				{
					document.getElementById('result_box').innerHTML = "Ok";
					document.getElementById("a_download").setAttribute("href", '/<?php echo $DP_Config->backend_dir; ?>/tmp/'+answer.filename);
					document.getElementById("a_download").click();
				}
				else if(document.getElementById("data_output_mode").value == "open_file_browser")
				{
					document.getElementById('result_box').innerHTML = "Ok";
					document.getElementById("a_open_tab").setAttribute("href", '/<?php echo $DP_Config->backend_dir; ?>/tmp/'+answer.filename);
					document.getElementById("a_open_tab").click();
				}
				
				let html  = "Сформированный файл: <b><?php echo $DP_Config->domain_path; ?><?php echo $DP_Config->backend_dir; ?>/tmp/"+answer.filename+"</b><br/>";
					html += "<table>";
					html += "<tr><td><b>"+ answer['_COUNT_products_categoryes_all'] +"</b></td><td style='padding-left: 5px;'>"+ "Количество товаров в указанных категориях</td></tr>";
					html += "<tr><td><b>"+ answer['_COUNT_products_read_all'] +"</b></td><td style='padding-left: 5px;'>"+ "Количество зачитанных и добавленных в YML товаров</td></tr>";
					html += "<tr><td><b>"+ answer['_COUNT_products_blocked_no_storage_record'] +"</b></td><td style='padding-left: 5px;'>"+ "Количество заблокированных товаров по причине отсутствия складской записи (цены или количества)</td></tr>";
					html += "<tr><td><b>"+ answer['_COUNT_products_blocked_yandex'] +"</b></td><td style='padding-left: 5px;'>"+ "Количество заблокированных товаров по причинам не удовлетворяющим обязательным условиям Яндекс (наличие таких свойств как Производитель, Изображение, Габариты, Вес, Фильтр цены)</td></tr>";
					html += "<tr><td><b>"+ answer['_COUNT_products_blocked_no_published'] +"</b></td><td style='padding-left: 5px;'>"+ "Количество пропущенных НЕ опубликованных товаров</td></tr>";
					html += "<tr><td><b>"+ answer['time'] +" сек.</b></td><td style='padding-left: 5px;'>"+ "Общее время выполнения SQL запросов к базе данных</td></tr>";
					html += "</table>";
				document.getElementById('result_info_box').innerHTML = html;
				document.getElementById('result_info_box').style.display = 'block';
			}
			else
			{
				alert("Ошибка");
				document.getElementById('result_box').innerHTML = answer.message;
			}
		},
		error: function (e, ajaxOptions, thrownError){
			document.getElementById('ink').style.display = 'none';
			flag = false;
			alert('Ошибка');
			document.getElementById('result_box').innerHTML = 'Ошибка: '+ e.status +' - '+ thrownError;
			document.getElementById('result_box').style.display = 'inline-block';
		}
	});
}
</script>