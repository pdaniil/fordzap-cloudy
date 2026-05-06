<?php
/**
 * Скрипт редактирования позиций прайс листов
*/
defined('_ASTEXE_') or die('No access');

// Список прайс листов
$sql = 'SELECT * FROM `shop_docpart_prices`;';	
$query = $db_link->prepare($sql);
$query->execute();

$prices_list = array();
while($rov = $query->fetch())
{
	$prices_list[$rov['id']] = $rov['name'];
}

//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getAdminSession();
?>





<div class="row row-add" style="margin: 0;">
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Поиск по неточному совпадению
			</div>
			<div class="panel-body">
				<div class="col-lg-6">
					<label>Поиск:</label>
					<input class="form-control" type="text" id="search_text"/>
				</div>
			</div>
			<div class="panel-heading hbuilt" style="border-top: 0;">
				Фильтр по точному совпадению
			</div>
			<div class="panel-body">
				<div class="col-lg-3">
					<label>Прайс-лист:</label>
					<select id="search_price_id" class="form-control">
					<option value="0">Все</option>
					<?php
					foreach($prices_list as $price_id => $price_name){
						?>
						<option value="<?=$price_id;?>"><?='id ' . $price_id .' - '. $price_name;?></option>
						<?php
					}
					?>
					</select>
				</div>
				<div class="col-lg-3">
					<label>Артикул:</label>
					<div style="position:relative;">
						<input class="form-control" type="text" id="search_article"/>
						<input title="Искать позиции с пустым артикулом" class="form-control" type="checkbox" id="search_no_article" style="position: absolute; bottom: 0; right: 3px; width: 26px; cursor:pointer;"/>
					</div>
				</div>
				<div class="col-lg-3">
					<label>Производитель:</label>
					<div style="position:relative;">
						<input class="form-control" type="text" id="search_manufacturer"/>
						<input title="Искать позиции с пустым производителем" class="form-control" type="checkbox" id="search_no_manufacturer" style="position: absolute; bottom: 0; right: 3px; width: 26px; cursor:pointer;"/>
					</div>
				</div>
			</div>
			<div class="panel-footer text-right">
				<a onclick="clear_search();" class="btn btn-ar btn-default"><i class="fa fa-eraser"></i> Сбросить</a>
				<a onclick="search_items();" class="btn btn-ar btn-primary"><i class="fa fa-search"></i> Поиск</a>
			</div>
		</div>
	</div>
</div>





<div class="row row-add" style="margin: 0;">
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Добавить данные в ручную
			</div>
			<div class="panel-body">
				<div class="col-lg-3">
					<label>Прайс-лист:</label>
					<select id="new_price_id" class="form-control">
					<?php
					foreach($prices_list as $price_id => $price_name){
						?>
						<option value="<?=$price_id;?>"><?='id ' . $price_id .' - '. $price_name;?></option>
						<?php
					}
					?>
					</select>
				</div>
				<div class="col-lg-9"><label>Наименование:</label><input class="form-control" type="text" id="new_name"/></div>
				
				<div class="col-lg-3"><label>Артикул:</label><input class="form-control" type="text" id="new_article"/></div>
				<div class="col-lg-3"><label>Производитель:</label><input class="form-control" type="text" id="new_manufacturer"/></div>
				<div class="col-lg-3 hidden"><label>Артикул без обработки:</label><input class="form-control" type="text" id="new_article_show"/></div>
				
				<div class="col-lg-3"><label>Количество:</label><input class="form-control" type="number" id="new_exist" value="1"/></div>
				<div class="col-lg-3"><label>Цена:</label><input class="form-control" type="number" id="new_price"/></div>
				<div class="col-lg-3"><label>Срок:</label><input class="form-control" type="number" id="new_time_to_exe" value="0"/></div>
				<div class="col-lg-3 hidden"><label>Внутренний склад:</label><input class="form-control" type="text" id="new_storage"/></div>
				<div class="col-lg-3"><label>Мин. партия:</label><input class="form-control" type="number" id="new_min_order" value="1"/></div>
			</div>
			<div class="panel-footer text-right">
				<img id="img_add" style="height: 31px; margin-right: 5px;" class="hidden" src="/content/files/images/ajax-loader-transparent.gif"/><a id="btn_add" onclick="add();" class="btn btn-ar btn-primary"><i class="fa fa-plus"></i> Добавить</a>
			</div>
		</div>
	</div>
</div>





<div class="row" style="margin: 0;">
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt" style="position:relative;">
				Таблица позиций
			</div>
			<div id="div_table"></div>
		</div>
	</div>
</div>





<div class="row row-add" style="margin: 0;">
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Удалить найденные записи с учетом фильтра и поиска 
			</div>
			<div class="panel-body">
				<div class="col-lg-12">
					<a onclick="del_search();" class="btn btn-ar btn-danger">Удалить записи</a>
				</div>
			</div>
		</div>
	</div>
</div>





<script>
	var page = 1;// Текущая страница таблицы
	var where_object = new Object;// Объект фильтра
	
	
	
	// Функция перехода по страницам таблицы
	function go_to_page(p){
		page = p;
		show_table();
	}
	
	
	
	// Функция приминения фильтров
	function search_items(){
		where_object = new Object;
		page = 1;
		
		where_object.search_text = encodeURIComponent(document.getElementById("search_text").value);
		
		var n = document.getElementById("search_price_id").options.selectedIndex;
		where_object.price_id = encodeURIComponent(document.getElementById("search_price_id").options[n].value);
		
		where_object.article = encodeURIComponent(document.getElementById("search_article").value);
		where_object.manufacturer = encodeURIComponent(document.getElementById("search_manufacturer").value);
		
		if(document.getElementById("search_no_article").checked){where_object.no_article = 1;}
		if(document.getElementById("search_no_manufacturer").checked){where_object.no_manufacturer = 1;}
		
		show_table();
	}
	
	
	
	// Функция отображает таблицу с условиями фильтрации
	function show_table(){
		document.getElementById('div_table').innerHTML = '';
		
		setTimeout(function(){ 
			if(document.getElementById('div_table').innerHTML == ''){
				// Отображаем индикатор загрузки
				document.getElementById('div_table').innerHTML = '<div class="panel-body text-center"><img src="/content/files/images/ajax-loader-transparent.gif"/></div>';
			}
		}, 500)

		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'get_table';
		request_object.page = page;
		request_object.where_object = where_object;

		// Отправляем запрос
		jQuery.ajax({
            type: "POST",
            async: true,
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/prices_edit/ajax_operations.php",
            dataType: "text",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
				// Вставляем сформированный html на страницу
				document.getElementById('div_table').innerHTML = answer;
		    }
        });
	}
	
	
	
	// Функция ручного добавления
	function add(){
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'add';
		var n = document.getElementById("new_price_id").options.selectedIndex;
		request_object.price_id = encodeURIComponent(document.getElementById("new_price_id").options[n].value);
		request_object.name = encodeURIComponent(document.getElementById("new_name").value);
		request_object.article = encodeURIComponent(document.getElementById("new_article").value);
		request_object.manufacturer = encodeURIComponent(document.getElementById("new_manufacturer").value);
		request_object.article_show = encodeURIComponent(document.getElementById("new_article_show").value);
		request_object.exist = encodeURIComponent(document.getElementById("new_exist").value);
		request_object.price = encodeURIComponent(document.getElementById("new_price").value);
		request_object.time_to_exe = encodeURIComponent(document.getElementById("new_time_to_exe").value);
		request_object.storage = encodeURIComponent(document.getElementById("new_storage").value);
		request_object.min_order = encodeURIComponent(document.getElementById("new_min_order").value);
		
		if(request_object.article === '' || request_object.manufacturer === '' || (request_object.exist*1) <= 0  || (request_object.price*1) <= 0){
			alert("Заполните все поля");
			return;
		}
		
		// Очищаем форму
		document.getElementById("new_price_id").options[0].selected = true;
		document.getElementById("new_name").value = '';
		document.getElementById("new_article").value = '';
		document.getElementById("new_manufacturer").value = '';
		document.getElementById("new_article_show").value = '';
		document.getElementById("new_exist").value = '1';
		document.getElementById("new_price").value = '';
		document.getElementById("new_time_to_exe").value = '0';
		document.getElementById("new_storage").value = '';
		document.getElementById("new_min_order").value = '1';
		
		$('#btn_add').addClass('disabled');// Блокируем кнопку
		$('#img_add').removeClass('hidden');// Отображаем индикатор загрузки
		
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос синхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/prices_edit/ajax_operations.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                $('#btn_add').removeClass('disabled');// Разблокируем кнопку
				$('#img_add').addClass('hidden');// Убираем индикатор загрузки
				
				//console.log(answer);
                if(answer.status == true)
                {
                   show_table();
                }
                else
                {
					alert("Ошибка добавления.");
                }
            }
        });
	}
	
	
	
	// Функция отображает форму редактирования
	function edit(id){
        $('#show_line_'+id).addClass('hidden');
        $('#edit_line_'+id).removeClass('hidden');
	}

	
	
	// Функция отменяет редактирование
	function edit_otmena(id){
        $('#edit_line_'+id).addClass('hidden');
		$('#show_line_'+id).removeClass('hidden');
	}

	
	
	// Функция сохранения редактируемой позиции
	function edit_save(id){
		var price_id = document.getElementById('price_id_edit_'+id).value;
		var article = document.getElementById('article_edit_'+id).value;
		var manufacturer = document.getElementById('manufacturer_edit_'+id).value;
		var article_show = document.getElementById('article_show_edit_'+id).value;
		var name = document.getElementById('name_edit_'+id).value;
		var exist = document.getElementById('exist_edit_'+id).value;
		var price = document.getElementById('price_edit_'+id).value;
		var time_to_exe = document.getElementById('time_to_exe_edit_'+id).value;
		var storage = document.getElementById('storage_edit_'+id).value;
		var min_order = document.getElementById('min_order_edit_'+id).value;
		
		if(price_id === '' || article === '' || manufacturer === '' || exist === '' || price === ''){
			alert("Заполните все поля");
			return;
		}
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'save';
		request_object.id = id;
		request_object.price_id = encodeURIComponent(price_id);
		request_object.article = encodeURIComponent(article);
		request_object.manufacturer = encodeURIComponent(manufacturer);
		request_object.article_show = encodeURIComponent(article_show);
		request_object.name = encodeURIComponent(name);
		request_object.exist = encodeURIComponent(exist);
		request_object.price = encodeURIComponent(price);
		request_object.time_to_exe = encodeURIComponent(time_to_exe);
		request_object.storage = encodeURIComponent(storage);
		request_object.min_order = encodeURIComponent(min_order);
    
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/prices_edit/ajax_operations.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                if(answer.status == true)
                {
                    show_table();
                }
                else
                {
					alert("Ошибка сохранения.");
                }
            }
        });
	}
	
	
	
	// Функция удаления
	function del(id){
        if(confirm('Вы действительно хотите удалить запись?')){
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'del';
			request_object.id = id;
		
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/prices_edit/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					if(answer.status == true)
					{
						show_table();
					}
					else
					{
						alert("Ошибка удаления");
					}
				}
			});
		}
	}
	
	
	
	// Функция удаления с учетом поиска
	function del_search(){
        if(confirm('Вы действительно хотите удалить найденные записи?')){
			
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'del_search';
			request_object.page = page;
			request_object.where_object = where_object;
			
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/prices_edit/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					if(answer.status == true)
					{
						show_table();
					}
					else
					{
						alert("Ошибка удаления");
					}
				}
			});
		}
	}
	
	
	
	// Функция сбрасывает фильтры поиска
	function clear_search(){
		where_object = new Object;
		page = 1;
		document.getElementById('search_text').value = '';
		document.getElementById("search_price_id").options[0].selected = true;
		document.getElementById('search_article').value = '';
		document.getElementById('search_manufacturer').value = '';
		document.getElementById('search_no_article').checked = false;
		document.getElementById('search_no_manufacturer').checked = false;
		show_table();
	}

	
	
	// После открытия страницы отображаем таблицу
	show_table();
</script>





<style>
	.bgtd{
		background:#eee;
		font-weight:bold;
	}
	.center{
		text-align:center;
	}
	.right{
		text-align:right;
	}
	.row-add .panel-body > div{
		margin-bottom:5px;
	}
	.panel-body > div{
		padding-left:0px;
		padding-right:10px;
	}
	.panel-body > div:last-child{
		padding-right:0px;
	}
	@media screen and (max-width: 1200px) {
		.panel-body > div{
			padding-right:0px;
		}
	}
	.panel-footer .btn{
		margin-right: 5px;
	}
	.table td:last-child, .table th:last-child{
		text-align:right;
	}
	.pagination_box{
		text-align:center;
	}
	.pagination_box a{
		font-size: 14px;
		display: inline-block;
		background: #eee;
		border-radius: 2px;
		color: #333;
		padding: 2px 8px;
		margin-right:2px;
		border:1px solid #333;
	}
	.pagination_active{
		background: #34495e !important;
		color: #fff !important;
	}
	#div_table table{
		font-size: 12px;
	}
	#div_table > .panel-footer{
		color: inherit;
		border: 1px solid #e4e5e7;
		border-top: none;
		font-size: 90%;
		background: #f7f9fa;
		padding: 10px 15px;
	}
	#div_table > .panel-body{
		overflow-x: auto;
	}
	.table{
		margin-bottom:0px;
	}
	.table td{
		vertical-align: middle !important;
	}
</style>


