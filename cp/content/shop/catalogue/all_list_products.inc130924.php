<?php
// Подключаемый скрипт на страницу управления наличием, для вывода всех товаров в таблице
defined('_ASTEXE_') or die('No access');


// -------------------------------------------------------------------------------
//Защита от CSRF-атак
//$csrf_check_admin = 1;
//require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
// -------------------------------------------------------------------------------


// Удаляем мусорные данные - складские записи для складов которых нет
$sql = "DELETE FROM `shop_storages_data` WHERE `storage_id` NOT IN(SELECT `id` FROM `shop_storages` WHERE `interface_type` = 1) LIMIT 10000;";
do{
	$query = $db_link->prepare($sql);
	$query->execute();
}while($query->rowCount() > 0);

// Удаляем мусорные данные - складские записи для товаров которых нет
$sql = "DELETE FROM `shop_storages_data` WHERE `product_id` NOT IN(SELECT `id` FROM `shop_catalogue_products`) LIMIT 10000;";
do{
	$query = $db_link->prepare($sql);
	$query->execute();
}while($query->rowCount() > 0);

//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$admin_id = DP_User::getAdminId();

// Список складов
$sql = 'SELECT * FROM `shop_storages` WHERE `interface_type` = 1 AND `users` LIKE \'%' . $admin_id . '%\';';
$query = $db_link->prepare($sql);
$query->execute();

$storages_list = array();
while($row = $query->fetch())
{
	$storages_list[$row['id']] = $row['name'];
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
					<label>Подстрока:</label>
					<input class="form-control" type="text" id="search_text"/>
				</div>
			</div>
			<div class="panel-heading hbuilt" style="border-top: 0;">
				Фильтр по точному совпадению
			</div>
			<div class="panel-body">
				<div class="col-lg-3">
					<label>Склад:</label>
					<select id="search_storage_id" class="form-control">
					<option value="0">Все</option>
					<?php
					foreach($storages_list as $storage_id => $storage_name){
						?>
						<option value="<?=$storage_id;?>"><?='id ' . $storage_id .' - '. $storage_name;?></option>
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



<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Товары
		</div>
		<div id="div_table"></div>
	</div>
</div>



<script>
	var page = 1;// Текущая страница таблицы
	var where_object = new Object;// Объект фильтра
	var ajax_operations_url = "/<?php echo $DP_Config->backend_dir; ?>/content/shop/catalogue/ajax_operations_products.php";
	
	// Функция перехода по страницам таблицы
	function go_to_page(p){
		page = p;
		show_table();
	}
	
	// Возвращает cookie с именем name, если есть, если нет, то undefined
    function getCookie(name) 
    {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }
	
	// Установка куки сортировки
	function sort(field){
		var asc_desc = "asc";//Направление по умолчанию
        
        //Берем из куки текущий вариант сортировки
        var current_sort_cookie = getCookie("stock_sort");
        if(current_sort_cookie != undefined)
        {
            current_sort_cookie = JSON.parse(getCookie("stock_sort"));
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
        
        var stock_sort = new Object;
        stock_sort.field = field;//Поле, по которому сортировать
        stock_sort.asc_desc = asc_desc;//Направление сортировки
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "stock_sort="+JSON.stringify(stock_sort)+"; path=/; expires=" + date.toUTCString();
		
		page = 1;
		show_table();
	}
	
	// Функция приминения фильтров
	function search_items(){
		where_object = new Object;
		page = 1;
		
		where_object.search_text = encodeURIComponent(document.getElementById("search_text").value);
		
		var n = document.getElementById("search_storage_id").options.selectedIndex;
		where_object.storage_id = encodeURIComponent(document.getElementById("search_storage_id").options[n].value);
		
		where_object.article = encodeURIComponent(document.getElementById("search_article").value);
		where_object.manufacturer = encodeURIComponent(document.getElementById("search_manufacturer").value);
		
		if(document.getElementById("search_no_article").checked){where_object.no_article = 1;}
		if(document.getElementById("search_no_manufacturer").checked){where_object.no_manufacturer = 1;}
		
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
		
		<?php
		if($category_id > 0){
		?>
		where_object.category_id = '<?=$category_id;?>';
		<?php
		}
		?>

		//Объект для запроса
		var request_object = new Object;
		request_object.action = 'get_table';
		request_object.page = page;
		request_object.where_object = where_object;

		// Отправляем запрос
		jQuery.ajax({
			type: "POST",
			async: true,
			url: ajax_operations_url,
			dataType: "text",//Тип возвращаемого значения
			data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
			success: function(answer)
			{
				// Вставляем сформированный html на страницу
				document.getElementById('div_table').innerHTML = answer;
			}
		});
	}
	
	// Функция сбрасывает фильтры поиска
	function clear_search(){
		where_object = new Object;
		page = 1;
		document.getElementById('search_text').value = '';
		document.getElementById("search_storage_id").options[0].selected = true;
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
	.table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td {
		vertical-align: middle;
		color:#000;
		white-space: nowrap;
	}
	.table td:last-child, .table th:last-child{
		text-align:right;
	}
	.table > thead > tr > th{
		
	}
	.table .no_published_flag{
		background: #fbfbfb;
	}
	
	.table > tbody {
		border-left: 2px solid transparent;
		border-right: 2px solid transparent;
	}
	.table > tbody + tbody {
		border-top: 2px solid transparent;
	}
	.table tbody {
		border-top: 2px solid #ddd;
	}
	.table tbody + tbody{
		border-top: 2px solid #ddd;
	}
	.table tbody:hover {
		background: #d7e9fb;
		border: 2px solid #228bf5;
	}
	.table > tbody + tbody:hover {
		border-top: 2px solid #228bf5;
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
		border: 2px solid transparent;
	}
	
	.breadcrumbs_category{
		color:#a98d8d;
		font-size: .8em;
	}
	.breadcrumbs_category a{
		color:#a98d8d;
	}
	.shop_storages_data td{
		background:#fdfbfb;
		color:#a98d8d !important;
		font-size: .9em;
	}
</style>