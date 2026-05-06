<?php
defined('_ASTEXE_') or die('No access');

require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий

//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getAdminSession();
?>

<div class="row" style="margin: 0;">
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Добавить фильтр <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('<p>Данный функционал предназначен для удаления со страницы проценки определенного товара либо всех товаров определенного бренда. <br>Если указать только «Производитель», тогда абсолютно все товары указанного бренда будут удалены со страницы проценки.<br>Если дополнительно указать «Артикул» то соответственно будет удален конкретный товар.<br>Можно указать часть текста который присутствует в наименовании товара, Подстроку в наименовании товара можно указать без уточнения бренда и артикула, тогда фильтр применится ко всем товарам проценки имеющим в наименовании указанную подстроку.<br>Можно все три поля оставить пустыми, тогда вы сможете настроить фильтр по сроку и цене абсолютно для всех товаров проценки без уточнения.<br>После создания записи с фильтром ее можно настроить, дополнительно указать склады, для которых выполнять данную проверку. Если вы укажите склады, тогда только с этих складов будет происходить удаление товара, если склады не указаны, тогда удаление происходит со всех складов без ограничения.<br>Если дополнительно указать диапазоны цены или срока, то если товар попадает в диапазон, то для него данное ограничение не будет действовать, он будет показан в проценке, а вот если не попадает в диапазон, тогда будет удален.</p>');"><i class="fa fa-info"></i></button>
			</div>
			<div class="panel-body">
				<div class="col-md-4"><label>Производитель:</label><input class="form-control" type="text" id="new_manufacturer" name="manufacturer" placeholder="Не обязательно" /></div>
				<div class="col-md-4"><label>Артикул:</label><input class="form-control" type="text" id="new_article" name="article" placeholder="Не обязательно" /></div>
				<div class="col-md-4"><label>Наименование:</label><input class="form-control" type="text" id="new_name" name="name" placeholder="Не обязательно" /></div>
			</div>
			<div class="panel-footer text-right">
				<img id="img_add" style="height: 31px; margin-right: 5px;" class="hidden" src="/content/files/images/ajax-loader-transparent.gif"/><a id="btn_add" onclick="add();" class="btn btn-ar btn-success"><i class="fa fa-plus"></i> Добавить</a>
			</div>
		</div>
	</div>
</div>





<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt" style="position:relative;">
			Таблица фильтров
		</div>
		<div id="div_table"></div>
	</div>
</div>





<script>
	var page = 1;// Текущая страница таблицы
	
	// Функция перехода по страницам таблицы 
	function go_to_page(p){
		page = p;
		show_table();
	}
	
	// Функция отображает таблицу
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

		// Отправляем запрос
		jQuery.ajax({
            type: "POST",
            async: true,
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/filter/ajax_operations.php",
            dataType: "text",//Тип возвращаемого значения
            data: "request_object="+encodeURIComponent(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
				// Вставляем сформированный html на страницу
				document.getElementById('div_table').innerHTML = answer;
		    }
        });
	}
	
	
	
	// Функция ручного добавления
	function add(){
		var manufacturer = document.getElementById('new_manufacturer').value;
		var article 	 = document.getElementById('new_article').value;
		var name 	 	 = document.getElementById('new_name').value;
		
		/*
		if( manufacturer === '' ){
			alert("Заполните поле бренда");
			return;
		}
		*/
		
		// Очищаем форму
		document.getElementById("new_article").value = '';
		document.getElementById("new_manufacturer").value = '';
		document.getElementById("new_name").value = '';
		
		$('#btn_add').addClass('disabled');// Блокируем кнопку
		$('#img_add').removeClass('hidden');// Отображаем индикатор загрузки
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'add';
		request_object.article = article;
		request_object.manufacturer = manufacturer;
		request_object.name = name;
    
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос синхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/filter/ajax_operations.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURIComponent(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                $('#btn_add').removeClass('disabled');// Разблокируем кнопку
				$('#img_add').addClass('hidden');// Убираем индикатор загрузки
				
				//console.log(answer);
                if(answer.status == true)
                {
                   page = 1;
				   show_table();
                }
                else
                {
					alert("Ошибка добавления. Возможно добавляемый фильтр с указанными вами настройками уже существует.");
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

	
	// Функция сохранения редактируемого элемента
	function edit_save(id){
		var manufacturer = document.getElementById('manufacturer_edit_'+id).value;
		var article = document.getElementById('article_edit_'+id).value;
		var name = document.getElementById('name_edit_'+id).value;
		
		/*
		if(manufacturer === ''){
			alert("Заполните данные о бренде");
			return;
		}
		*/
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'save';
		request_object.id = id;
		request_object.article = article;
		request_object.manufacturer = manufacturer;
		request_object.name = name;
		
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/filter/ajax_operations.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURIComponent(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                //console.log(answer);
                if(answer.status == true)
                {
                    show_table();
                }
                else
                {
					alert("Ошибка сохранения. Возможно редактируемый бренд уже существует");
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
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/filter/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURIComponent(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					//console.log(answer);
					if(answer.status == true)
					{
						page = 1;
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
	
	// Функция активации одного
	function active(id){
        if(true){
			var flag = 0;
			if(document.getElementById('active_'+id).checked){
				flag = 1;
			}
			
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'active';
			request_object.id = id;
			request_object.flag = flag;
		
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/filter/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURIComponent(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					//console.log(answer);
					if(answer.status == true)
					{
						//page = 1;
						//show_table();
					}
					else
					{
						alert("Ошибка");
					}
				}
			});
		}
	}
	
	// Функция актифиции всех
	function active_all(flag){
		if(flag == 1)
		{
			if( ! confirm('Вы действительно хотите активировать все созданные записи?') ){
				return false;
			}
		}
		else
		{
			if( ! confirm('Вы действительно хотите отключить все созданные записи?') ){
				return false;
			}
		}
		
        if(true){
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'active_all';
			request_object.flag = flag;
		
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/filter/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURIComponent(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					//console.log(answer);
					if(answer.status == true)
					{
						page = 1;
						show_table();
					}
					else
					{
						alert("Ошибка");
					}
				}
			});
		}
	}
	
	
	// После открытия страницы отображаем таблицу
	show_table();
</script>