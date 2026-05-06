<?php
/**
 * Скрипт для управления таблицей кроссов
*/
defined('_ASTEXE_') or die('No access');


//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getAdminSession();

//Определяем текущую сортировку и обозначаем ее:
$crosses_sort = null;
if( isset($_COOKIE["crosses_sort"]) )
{
	$crosses_sort = $_COOKIE["crosses_sort"];
}
$sort_field = "id";
$sort_asc_desc = "desc";
if($crosses_sort != NULL)
{
	$crosses_sort = json_decode($crosses_sort, true);
	$sort_field = $crosses_sort["field"];
	$sort_asc_desc = $crosses_sort["asc_desc"];
}

if( strtolower($sort_asc_desc) == "asc" )
{
	$sort_asc_desc = "asc";
}
else
{
	$sort_asc_desc = "desc";
}

if( array_search($sort_field, array('id', 'article', 'manufacturer_article', 'analog', 'manufacturer_analog')) === false )
{
	$sort_field = "article";
}
?>
<style>
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
	.table_crosses td:last-child, .table_crosses th:last-child{
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
	#div_table_crosses > .panel-footer{
		color: inherit;
		border: 1px solid #e4e5e7;
		border-top: none;
		font-size: 90%;
		background: #f7f9fa;
		padding: 10px 15px;
	}
	#div_table_crosses > .panel-body{
		overflow-x: auto;
	}
	.table_crosses{
		margin-bottom:0px;
	}
</style>
<div class="row" style="margin: 0;">
	<div class="col-lg-8">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Добавить данные в ручную
			</div>
			<div class="panel-body">
				<div class="col-lg-3"><label>Артикул:</label><input class="form-control" type="text" id="new_article" name="article"/></div>
				<div class="col-lg-3"><label>Производитель:</label><input class="form-control" type="text" id="new_manufacturer_article" name="manufacturer"/></div>
				<div class="col-lg-3"><label>Аналог:</label><input class="form-control" type="text" id="new_analog" name="article"/></div>
				<div class="col-lg-3"><label>Производитель:</label><input class="form-control" type="text" id="new_manufacturer_analog" name="manufacturer"/></div>
			</div>
			<div class="panel-footer text-right">
				<img id="img_crosses_add" style="height: 31px; margin-right: 5px;" class="hidden" src="/content/files/images/ajax-loader-transparent.gif"/><a id="btn_crosses_add" onclick="crosses_add();" class="btn btn-ar btn-primary"><i class="fa fa-plus"></i> Добавить</a>
			</div>
		</div>
	</div>




	<div class="col-lg-4">
		<div class="hpanel">
			<div class="panel-heading hbuilt" style="position:relative;">
				Добавить данные из .csv файла
				<a style="position:absolute; right:20px;" href="/<?=$DP_Config->backend_dir;?>/content/shop/crosses/crosses.csv" download title="Скачать шаблон .csv файла для загрузки">Скачать шаблон <i class="fa fa-file"></i></a>
			</div>
			<div class="panel-body">
				<label>Выберете файл:</label><input class="form-control" type="file" id="file_csv" name="file"/>
			</div>
			<div class="panel-footer text-right">
				<img id="img_crosses_add_of_csv" style="height: 31px; margin-right: 5px;" class="hidden" src="/content/files/images/ajax-loader-transparent.gif"/><a id="btn_crosses_add_of_csv" onclick="crosses_add_of_csv();" class="btn btn-ar btn-primary"><i class="fa fa-plus"></i> Добавить</a>
			</div>
		</div>
	</div>
</div>




<div class="row" style="margin: 0;">
	<div class="col-lg-4 col-lg-push-8">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Фильтр кроссов
			</div>
			<div class="panel-body">
				<div class="col-lg-6">
					<label>Введите артикул:</label>
					<input class="form-control" type="text" id="search_article" onchange="get_search_manufacturer();" onkeyup="get_search_manufacturer();" name="article"/>
				</div>
				<div class="col-lg-6">
					<label>Выберете производителя:</label>
					<select id="search_manufacturer" class="form-control"><option id="all">Все производители</option></select>
				</div>
				<div class="col-lg-12">
					<table>
						<tr>
							<td><label for="search_null" style="margin: 0; padding-right: 10px;">Запись с пустым значением</label></td>
							<td><input style="width:25px;" class="form-control" type="checkbox" id="search_null" name="null"/></td>
						</tr>
					</table>
				</div>
				<div class="col-lg-12">
					<table>
						<tr>
							<td><br/><label style="margin: 0; padding-right: 10px;">ID</label></td>
							<td>от<input style="width:125px;" class="form-control" type="number" id="search_id_from" name="null"/></td>
							<td> &nbsp; </td>
							<td>до<input style="width:125px;" class="form-control" type="number" id="search_id_before" name="null"/></td>
						</tr>
					</table>
				</div>
			</div>
			
			<div class="panel-footer text-right">
				<a onclick="clear_search();" class="btn btn-ar btn-default"><i class="fa fa-eraser"></i> Сбросить</a>
				<a onclick="show_table_crosses();" class="btn btn-ar btn-primary"><i class="fa fa-search"></i> Поиск</a>
			</div>
		</div>
		
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Удаление кроссов
			</div>
			<div class="panel-body">
				<div class="col-lg-12">
					<div class="">
						<div class="text-left">
							<a onclick="del_search_crosses();" class="btn btn-ar btn-danger">Удалить записи с учетом поиска</a> <img id="img_crosses_del" style="height: 31px; margin-right: 5px;" class="hidden" src="/content/files/images/ajax-loader-transparent.gif"/>
							<br/>
							<br/>
							<small>Будут удалены все отображаемые записии кроссов которые удовлетворяют выставленному фильтру. <br/> Если фильтр не задан тогда будут удалены абсолютно все записии.</small>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Выставленные настройки
			</div>
			<div class="panel-body">
				<div class="col-lg-12">
					<div class="">
						<div class="text-left">
							<table>
								<tr>
									<td><input <?php echo ($DP_Config->local_crosses == 1)?'checked':''; ?> style="width:25px;" class="form-control" type="checkbox" disabled /></td>
									<td><label style="margin: 0; padding-left: 10px;">Использовать локальную таблицу кроссов<br/><small>Таблица отображаемая на данной странице</small></label></td>
								</tr>
								<tr>
									<td colspan="2"><div class="hr-line-dashed col-lg-12"></div></td>
								</tr>
								<tr>
									<td><input <?php echo ($DP_Config->ucats_crosses == 1)?'checked':''; ?> style="width:25px;" class="form-control" type="checkbox" disabled /></td>
									<td><label style="margin: 0; padding-left: 10px;">Использовать таблицу кроссов от Ucats<br/><small>Необходим доступ к каталогу Ucats</small></label></td>
								</tr>
								<tr>
									<td colspan="2"><div class="hr-line-dashed col-lg-12"></div></td>
								</tr>
								<tr>
									<td><input <?php echo (isset($DP_Config->storages_of_crosses) && $DP_Config->storages_of_crosses != '')?'checked':''; ?> style="width:25px;" class="form-control" type="checkbox" disabled /></td>
									<td><label style="margin: 0; padding-left: 10px;">Использовать таблицу кроссов от API складов<br/><small>Для подключения модуля, обратитесь на сайт тех. поддержки</small></label></td>
								</tr>
								<tr>
									<td colspan="2"><div class="hr-line-dashed col-lg-12"></div></td>
								</tr>
								<tr>
									<td><input <?php echo ($DP_Config->list_brends_crosses == 1)?'checked':''; ?> style="width:25px;" class="form-control" type="checkbox" disabled /></td>
									<td><label style="margin: 0; padding-left: 10px;">Использовать таблицу кроссов для формирования производителей<br/><small>Список на странице проценки при запросе артикула</small></label></td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="panel-footer text-left">
				<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Параметры настраиваются в разделе: Панель управления - Настройки
			</div>
		</div>
	</div>




	<div class="col-lg-8 col-lg-pull-4">
		<div class="hpanel">
			<div class="panel-heading hbuilt" style="position:relative;">
				Таблица кроссов
				<button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Локальная таблица кроссов используется исключительно для поиска аналогов в загруженных прайс-листах. На выдачу аналогов API складов повлиять нельзя, поставщик сам решает какой список аналогов отдавать на ваш запрос. Таблица кроссов нужна для формирования списка аналогов при поиске по загруженным вами прайс-листам и каталогу наличия на странице проценки по артикулу.');"><i class="fa fa-info"></i></button>
				
				<a style="position:absolute; right:20px;" onclick="download_crosses();" title="Скачать кроссы в .csv файле">Скачать список <i class="fa fa-file"></i></a>
			</div>
			<div id="div_table_crosses"></div>
		</div>
	</div>
</div>




<script>
	var page = 1;// Текущая страница таблицы кроссов
	
	// Функция перехода по страницам таблицы кроссов
	function go_to_page(p){
		page = p;
		show_table_crosses(1);
	}
	
	// Функция отображает таблицу кроссов с условиями фильтрации
	function show_table_crosses(flag){
		document.getElementById('div_table_crosses').innerHTML = '';
		
		setTimeout(function(){ 
			if(document.getElementById('div_table_crosses').innerHTML == ''){
				// Отображаем индикатор загрузки
				document.getElementById('div_table_crosses').innerHTML = '<div class="panel-body text-center"><img src="/content/files/images/ajax-loader-transparent.gif"/></div>';
			}
		}, 500)

		// Если заданы ограничения фильтрации
		var article = document.getElementById("search_article").value;
		var manufacturer = '';
		if(article){
			var n = document.getElementById("search_manufacturer").options.selectedIndex;
			if(n > 0){
				var val = document.getElementById("search_manufacturer").options[n].value;
				manufacturer = val;
			}
			if(flag != 1){
			page = 1;
			}
		}
		
		let search_null = 0;
		if(document.getElementById("search_null").checked){
			search_null = 1;
		}
		
		let search_id_from = 'null';
		if(document.getElementById("search_id_from").value){
			search_id_from = document.getElementById("search_id_from").value;
		}
		
		let search_id_before = 'null';
		if(document.getElementById("search_id_before").value){
			search_id_before = document.getElementById("search_id_before").value;
		}
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'get_table_crosses';
		request_object.page = page;
		request_object.article = encodeURIComponent(article);
		request_object.manufacturer = encodeURIComponent(manufacturer);
		request_object.null = search_null;
		request_object.id_from = search_id_from;
		request_object.id_before = search_id_before;

		// Отправляем запрос
		jQuery.ajax({
            type: "POST",
            async: true,
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
            dataType: "text",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
				// Вставляем сформированный html на страницу
				document.getElementById('div_table_crosses').innerHTML = answer;
				
				let sort_field = "article";
				let sort_asc_desc = "desc";
				
				//Берем из куки текущий вариант сортировки
				var current_sort_cookie = getCookie("crosses_sort");
				if(current_sort_cookie != undefined)
				{
					current_sort_cookie = JSON.parse(getCookie("crosses_sort"));
					sort_field = current_sort_cookie.field;
					sort_asc_desc = current_sort_cookie.asc_desc;
				}
				
				if(document.getElementById(sort_field +"_sorter"))
				{
					document.getElementById(sort_field +"_sorter").innerHTML += "<img src=\"/content/files/images/sort_"+ sort_asc_desc +".png\" style=\"width:15px\" />";
				}
		    }
        });
	}
	
	
	
	// Функция ручного добавления кросса
	function crosses_add(){
		var article 			 = document.getElementById('new_article').value;
		var manufacturer_article = document.getElementById('new_manufacturer_article').value;
		var analog 				 = document.getElementById('new_analog').value;
		var manufacturer_analog  = document.getElementById('new_manufacturer_analog').value;

		if(article === '' || manufacturer_article === '' || analog === '' || manufacturer_analog === ''){
			alert("Заполните все поля");
			return;
		}
		
		// Очищаем форму
		document.getElementById("new_article").value = '';
		document.getElementById("new_manufacturer_article").value = '';
		document.getElementById("new_analog").value = '';
		document.getElementById("new_manufacturer_analog").value = '';
		
		$('#btn_crosses_add').addClass('disabled');// Блокируем кнопку
		$('#img_crosses_add').removeClass('hidden');// Отображаем индикатор загрузки
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'add_crosses';
		request_object.article = encodeURIComponent(article);
		request_object.manufacturer_article = encodeURIComponent(manufacturer_article);
		request_object.analog = encodeURIComponent(analog);
		request_object.manufacturer_analog = encodeURIComponent(manufacturer_analog);
    
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос синхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                $('#btn_crosses_add').removeClass('disabled');// Разблокируем кнопку
				$('#img_crosses_add').addClass('hidden');// Убираем индикатор загрузки
				
				//console.log(answer);
                if(answer.status == true)
                {
                   show_table_crosses();
                }
                else
                {
					alert("Ошибка добавления. Возможно добавляемый аналог уже существует");
                }
            }
        });
	}
	
	

	// Функция загрузки кроссов из csv файла
	function crosses_add_of_csv(){
		
		//Проверка наличия файла
		var csv_file = document.getElementById("file_csv").value;
		if( csv_file == "" )
		{
			alert("Выберите .csv файл для загрузки");
			return;
		}
		
		//Отправляем файл на сервер
		var csv_file = $("#file_csv");//Input с файлом
		var formData = new FormData;//Объект данных формы
		formData.append('csv_file', csv_file.prop('files')[0]);//Добавляем в объект формы - файл
		formData.append('csrf_guard_key', '<?php echo $user_session["csrf_guard_key"]; ?>');
		
		document.getElementById("file_csv").value = '';// Очищаем форму
		$('#btn_crosses_add_of_csv').addClass('disabled');// Блокируем кнопку
		$('#img_crosses_add_of_csv').removeClass('hidden');// Отображаем индикатор загрузки
		
		$.ajax({
			url: '/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_upload_file_to_tmp.php',
			data: formData,
			processData: false,
			contentType: false,
			type: 'POST',
			dataType: "json",//Тип возвращаемого значения
			success: function (result) 
			{
				if( result.status != true )
				{
					alert("Не удалось загрузить файл");
					$('#btn_crosses_add_of_csv').removeClass('disabled');// Разблокируем кнопку
					$('#img_crosses_add_of_csv').addClass('hidden');// Убираем индикатор загрузки
				}
				else//Файл загружен, запускаем импорт
				{
					//Создаем объект с параметрами импорта
					var import_options = new Object;
					import_options.file_full_path = result.file_full_path;//Полный путь к файлу
					
					//Передаем запрос на сервер для запуска процесса
					jQuery.ajax({
						type: "POST",
						async: true,
						url: '/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_handle_file.php',
						dataType: "json",//Тип возвращаемого значения
						data: "import_options="+encodeURI( JSON.stringify(import_options) )+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
						success: function(result)
						{
							$('#btn_crosses_add_of_csv').removeClass('disabled');// Разблокируем кнопку
							$('#img_crosses_add_of_csv').addClass('hidden');// Убираем индикатор загрузки
							
							if(result.status != true)
							{
								alert("Ошибка. "+result.message);
							}
							else
							{
								alert("Успешно");
								go_to_page(1);
							}
						}
					});
				}
			}
		});
	}


	
	
	// Функция отображает форму редактирования
	function crosses_edit(id){
        $('#show_line_'+id).addClass('hidden');
        $('#edit_line_'+id).removeClass('hidden');
	}

	// Функция отменяет редактирование
	function crosses_edit_otmena(id){
        $('#edit_line_'+id).addClass('hidden');
		$('#show_line_'+id).removeClass('hidden');
	}

	// Функция сохранения редактируемого кросса
	function crosses_edit_save(id){
		var article = document.getElementById('article_edit_'+id).value;
		var manufacturer_article = document.getElementById('manufacturer_article_edit_'+id).value;
		var analog = document.getElementById('analog_edit_'+id).value;
		var manufacturer_analog = document.getElementById('manufacturer_analog_edit_'+id).value;
		
		if(article === '' || manufacturer_article === '' || analog === '' || manufacturer_analog === ''){
			alert("Заполните все поля");
			return;
		}
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'save_crosses';
		request_object.id = id;
		request_object.article = encodeURIComponent(article);
		request_object.manufacturer_article = encodeURIComponent(manufacturer_article);
		request_object.analog = encodeURIComponent(analog);
		request_object.manufacturer_analog = encodeURIComponent(manufacturer_analog);
    
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                //console.log(answer);
                if(answer.status == true)
                {
                    show_table_crosses();
                }
                else
                {
					alert("Ошибка сохранения. Возможно редактируемый аналог уже существует");
                }
            }
        });
	}
	
	// Функция удаления
	function crosses_del(id){
        if(confirm('Вы действительно хотите удалить запись?')){
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'del_crosses';
			request_object.id = id;
		
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					//console.log(answer);
					if(answer.status == true)
					{
						show_table_crosses();
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
	function del_search_crosses(){
        if(confirm('Вы действительно хотите удалить найденные записи?')){
			
			// Если заданы ограничения фильтрации
			var article = document.getElementById("search_article").value;
			var manufacturer = '';
			if(article){
				var n = document.getElementById("search_manufacturer").options.selectedIndex;
				if(n > 0){
					var val = document.getElementById("search_manufacturer").options[n].value;
					manufacturer = val;
				}
				page = 1;
			}
			
			let search_null = 0;
			if(document.getElementById("search_null").checked){
				search_null = 1;
			}
			
			let search_id_from = 'null';
			if(document.getElementById("search_id_from").value){
				search_id_from = document.getElementById("search_id_from").value;
			}
			
			let search_id_before = 'null';
			if(document.getElementById("search_id_before").value){
				search_id_before = document.getElementById("search_id_before").value;
			}
			
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'del_search_crosses';
			request_object.page = page;
			request_object.article = encodeURIComponent(article);
			request_object.manufacturer = encodeURIComponent(manufacturer);
			request_object.null = search_null;
			request_object.id_from = search_id_from;
			request_object.id_before = search_id_before;
			
			$('#img_crosses_del').removeClass('hidden');// Отображаем индикатор
			
			jQuery.ajax({
				type: "POST",
				async: true, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					//console.log(answer);
					if(answer.status == true)
					{
						show_table_crosses();
					}
					else
					{
						alert("Ошибка удаления");
					}
					
					$('#img_crosses_del').addClass('hidden');// Убираем индикатор
				}
			});
		}
	}
	
	
	
	
	// Функция запроса списка производителей
	function get_search_manufacturer(){
		var article = document.getElementById('search_article').value;
		if(article.length >= 2){
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'get_search_manufacturer';
			request_object.article = encodeURIComponent(article);
			
			jQuery.ajax({
				type: "POST",
				async: true,
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					//console.log(answer);
					if(answer.status == true)
					{
						var select = '<option id="all">Все производители</option>';
						var list_manufacturer = JSON.parse(answer.list_manufacturer);
						if(list_manufacturer){
							for(var i = 0; list_manufacturer.length > i; i++){
								select += '<option id="'+ list_manufacturer[i] +'">'+ list_manufacturer[i] +'</option>';
							}
						}
						document.getElementById('search_manufacturer').innerHTML = select;
					}
				}
			});
		}else{
			document.getElementById('search_manufacturer').innerHTML = '<option id="all">Все производители</option>';
		}
	}
	
	
	
	// Функция сбрасывает фильтры поиска
	function clear_search(){
		page = 1;
		document.getElementById('search_article').value = '';
		document.getElementById('search_id_from').value = '';
		document.getElementById('search_id_before').value = '';
		document.getElementById('search_null').checked = false;
		document.getElementById('search_manufacturer').innerHTML = '<option id="all">Все производители</option>';
		show_table_crosses();
	}

	
	
	// Скачать файл кроссов
	function download_crosses(){
		window.open('/<?=$DP_Config->backend_dir;?>/content/shop/crosses/download_crosses.php?csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>', '_blank');
	}
	
	
	 // ------------------------------------------------------------------------------------------------
    //Установка куки сортировки заказов
    function sortCrosses(field)
    {
        var asc_desc = "asc";//Направление по умолчанию
        
        //Берем из куки текущий вариант сортировки
        var current_sort_cookie = getCookie("crosses_sort");
        if(current_sort_cookie != undefined)
        {
            current_sort_cookie = JSON.parse(getCookie("crosses_sort"));
            //Если поле это же - обращаем направление
            if(current_sort_cookie.field == field)
            {
                if(current_sort_cookie.asc_desc == "asc")
                {
                    asc_desc = "desc";
                }
                else
                {
                    asc_desc = "asc";
                }
            }
        }
        
        
        var crosses_sort = new Object;
        crosses_sort.field = field;//Поле, по которому сортировать
        crosses_sort.asc_desc = asc_desc;//Направление сортировки
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "crosses_sort="+JSON.stringify(crosses_sort)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        go_to_page(1);
    }
    // ------------------------------------------------------------------------------------------------
	
	// возвращает cookie с именем name, если есть, если нет, то undefined
    function getCookie(name) 
    {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }
	
    // ------------------------------------------------------------------------------------------------
	

	// После открытия страницы отображаем таблицу кроссов
	show_table_crosses();
</script>