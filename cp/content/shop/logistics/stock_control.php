<?php
/**
 * Сктраничный скрипт для управлениям наличием товара на складах
 * 
 * Суть: на странице отображается каталог товаров на основе справочника.
 * Кладовщик выбирает конкретный товар из каталога. Затем открывается страница товара с виджетами для управления наличием товара на складах, которые может редактировать данный поставщик
*/
defined('_ASTEXE_') or die('No access');
?>

<?php
if( ! empty($_POST["action"]) )//Есть действия
{
    
}
else//Действий нет - выводим страницу
{
	// Доработка 42333 START
	global $is_stock_control;
	$is_stock_control = true;
	// Доработка 42333 END

	//Для работы с пользователем
	require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
	$user_session = DP_User::getAdminSession();
	
    $is_products_mode = true;//Флаг - страница работает в режиме отображения товаров
    $category_block_type = 3;//Тип блоков категорий - для управления наличием (используется в /content/shop/catalogue/printCategories.php)
    $parent_url = '';
	
    //ID категории для отображения
    if(!empty($_GET["category_id"]))
    {
        $category_id = $_GET["category_id"];
    }
    else
    {
        $category_id = 0;
    }
    
    if($category_id > 0)
    {
        //Есть параметр category_id - нужно понять, является ли он конечным (count = 0)
        $category_record_query = $db_link->prepare("SELECT * FROM `shop_catalogue_categories` WHERE `id` = ?;");
		$category_record_query->execute( array($category_id) );
        $category_record = $category_record_query->fetch();
        
        if($category_record["parent"] > 0)//Подкатегорий нет - значит отображаем товары
        {
            $parent_url = '?category_id='.$category_record["parent"];
        }
		if($category_record["count"] == 0)//Подкатегорий нет - значит отображаем товары
        {
            $is_products_mode = true;
            $product_block_type = 3;//Параметр для скрипта /content/shop/catalogue/printProducts.php - знать, как выводить товары
        }
        else
        {
            $is_products_mode = false;
        }
    }
    else
    {
        $is_products_mode = false;//Будем выводить категории (причем корневые)
    }
	?>
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действие
			</div>
			<div class="panel-body">
				<a class="panel_a" id="to-modal-edit-prices" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/update.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Изменить цены</div>
				</a>
				<?php if(isset($_GET["category_id"])):?>
				<a style="float: left;" class="panel_a" href="/cp/shop/logistics/stock<?=$parent_url;?>">
					<div class="panel_a_img" style="background-color: #b9babb;width:96px;height:96px;display:table-cell;vertical-align:middle;"><i class="fas fa-chevron-left" style="color:#FFF;font-size:45px"></i></div>
					<div>Назад</div>
				</a>
				<?endif;?>
			</div>
		</div>
	</div>
	<?php	
    //Решаем, что выводить:
    if($is_products_mode == false)//Подкатегории
    {
		?>
		<div class="col-lg-12">
			<div class="hpanel">
				<?php
				// Доработка 42333 START
				?>
				<div class="panel-heading hbuilt" style="display: flex; justify-content: space-between; align-items: center;">
					Выберите категорию товаров
					<label id="stock-category-select-all" class="no-select">
						<span>Выбрать все</span>
						<input type="checkbox" checked>
					</label>
				</div>
				<div class="panel-body is_stock_control">
				<?php
				//Общий скрипт вывода категорий в основную область страницы
				require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/printCategories.php");
				?>
				</div>
				<?php
				// Доработка 42333 END
				?>
			</div>
		</div>
		<?php
    }
	
	// Подключаем скрипт таблицы товаров
	require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/catalogue/all_list_products.inc.php");
}
// Доработка 4233
?>
<style>
.no-select{
	-webkit-user-select: none;
	-khtml-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}
.is_stock_control .block_image{
	width: 100%;
	height: 50px;
	display: block;
	margin-top: 5px;
	margin-bottom: 5px;
}
.is_stock_control .block_caption{
	word-wrap: break-word;
}
.is_stock_control .block_image img{
	width: 100%;
	height: 100%;
	object-fit: contain;
}
#stock-category-select-all{
	border: 1px solid black;
	border-radius: 5px;
	padding: 5px;
	display: flex;
	gap: 10px;
	align-items: center;
}
#stock-category-select-all input{
	margin-top: 0px;
}
.stock-category-select{
	width: 100%;
	text-align: center;
	padding: 5px;
	margin-bottom: 0px;
}
#stock-category-select-all:hover, .stock-category-select:hover{
	cursor: pointer;
	background: rgba(0, 0, 0, 0.05);
}
#modal-confirm-edit-prices-percent{
	font-weight: bold;
}
#modal-edit-prices-crossed-out{
	float: right;
	margin-top: 0px;
}
#modal-confirm-edit-prices-message{
	margin-top: 15px;
	border-top: 1px solid #e5e5e5;
	color: red;
	margin-left: -30px;
	margin-right: -30px;
	padding: 20px 30px 0px 30px;
}
.tr-empty, .tr-loader, .tr-more, .tr-empty td, .tr-loader td, .tr-more td{
	text-align: center!important;
}
.d-none{
	display: none!important;
}
.spinning{
    animation: spinning 1s infinite linear;
    -webkit-animation: spinning-wk 1s infinite linear;
}
#modal-history-edit-prices .modal-dialog{
	width: 1000px;
}
#modal-history-edit-prices tbody{
	border: none!important;
	background: initial!important;
}
#modal-history-edit-prices .tr-percent{
	background: red;
    padding: 5px;
    border-radius: 5px;
    color: white;
}
#modal-history-edit-prices .tr-percent.tr-plus{
	background: green;
}
@keyframes spinning{
    from{
        transform: scale(1) rotate(0deg);
    }
    to{
        transform: scale(1) rotate(360deg);
    }
}

@-webkit-keyframes spinning-wk{
    from{
        -webkit-transform: rotate(0deg);
    }
    to{
        -webkit-transform: rotate(360deg);
    }
}
</style>
<div class="modal fade" id="modal-edit-prices" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="modalLabel">Процентное изменение всех цен</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label class="form-label">
						Выбрано категорий: <span id="modal-edit-prices-count-categories">0</span>
					</label>
				</div>
				<div class="form-group">
					<label class="form-label">Введите %, на который хотите изменить цены</label>
					<input id="modal-edit-prices-percent" type="number" min="-100" class="form-control">
				</div>
				<div class="form-group mt-2">
					<label class="form-label" for="modal-edit-prices-crossed-out">Применить также к зачеркнутой цене?</label>
					<input type="checkbox" id="modal-edit-prices-crossed-out" checked>
				</div>
				<div class="form-group">
					<label class="form-label">Комментарий</label>
					<input id="modal-edit-prices-comment" type="text" class="form-control">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-warning" id="to-modal-history-edit-prices">
					<span class="glyphicon glyphicon-refresh spinning d-none"></span>
					<span class="text">
						История изменений
					</span>
				</button>
				<button type="button" class="btn btn-success" disabled id="to-modal-confirm-edit-prices">
					<span class="glyphicon glyphicon-refresh spinning d-none"></span>
					<span class="text">
						Продолжить
					</span>
				</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="modal-confirm-edit-prices" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="modalLabel">Подтверждение изменения всех цен</h4>
			</div>
			<div class="modal-body">
				<div id="modal-confirm-edit-prices-result"></div>
				<div id="modal-confirm-edit-prices-message">
					Вы действительно хотите изменить все цены на <span id="modal-confirm-edit-prices-percent"></span>?
				</div>
			</div>
			<div class="modal-footer">
				<button id="modal-confirm-edit-prices-btn" type="button" class="btn btn-success">
					<span class="glyphicon glyphicon-refresh spinning d-none"></span>
					<span class="text">Да</span>
				</button>
				<button id="modal-confirm-edit-prices-btn-no" type="button" class="btn btn-secondary" data-dismiss="modal">Нет</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="modal-history-edit-prices" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="modalLabel">История изменений</h4>
			</div>
			<div class="modal-body">
				<div class="table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th width="150">Дата</th>
								<th width="250">Пользователь</th>
								<th width="50">%</th>
								<th>Комментарий</th>
							</tr>
						</thead>
						<tbody id="modal-history-edit-prices-list"></tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
			</div>
		</div>
	</div>
</div>
<script>
let stockCategorySelectItems = $(".stock-category-select input[type=checkbox]");
let toModalConfirmEditPrices = $("#to-modal-confirm-edit-prices");
let modalEditPricesPercent = $("#modal-edit-prices-percent");
let modalEditPricesComment = $("#modal-edit-prices-comment");
let modalEditPrices = $("#modal-edit-prices");
let modalConfirmEditPrices = $("#modal-confirm-edit-prices");
let modalConfirmEditPricesBtn = $("#modal-confirm-edit-prices-btn");
let modalEditPricesCrossedOut = $("#modal-edit-prices-crossed-out");
let modalProductsEditPrices = $("#modal-products-edit-prices");
let modalHistoryEditPrices = $("#modal-history-edit-prices");
let toModalHistoryEditPrices = $("#to-modal-history-edit-prices");
let modalHistoryEditPricesList = $("#modal-history-edit-prices-list");
let modalEditPricesPercentValue = 0;
modalEditPricesPercent.on('input', function(){
	checkEditPricesInputs();
});
modalEditPricesComment.on('input', function(){
	checkEditPricesInputs();
});
function checkEditPricesInputs()
{
	let percent = parseFloat(modalEditPricesPercent.val());
	let comment = modalEditPricesComment.val();
	if(isNaN(percent) || percent < -100 || !comment.length){
		toModalConfirmEditPrices.attr('disabled','');
	}else{
		toModalConfirmEditPrices.removeAttr('disabled');
	}
}
$("#stock-category-select-all input[type=checkbox]").on('change', function(){
	let self = $(this);
	let label = self.parent().find('span');
	stockCategorySelectItems.prop('checked', self.prop('checked'));
	if(self.prop('checked')){
		label.text('Снять все');
	}else{
		label.text('Выбрать все');
	}
});
function selectedCategories()
{
	let list = [];
	stockCategorySelectItems.each(function(i, e){
		let el = $(this);
		if(el.prop('checked')){
			list.push(el.data('category-id'));
		}
	});
	return list;
}
$("#to-modal-edit-prices").click(function(){
	let countSelected = selectedCategories().length;
	if(countSelected){
		modalEditPricesPercent.val("");
		modalEditPricesComment.val("");
		modalEditPricesCrossedOut.prop('checked', true);
		toModalConfirmEditPrices.attr('disabled', '');
		let searchText = $("#search_text").val();
		$("#modal-edit-prices-count-categories").html(countSelected + (searchText ? '<br>Подстрока поиска: '+ searchText : ''));
		modalEditPrices.modal('show');
	}else{
		alert('Выберите хотя бы одну категорию')
	}
	return false;
});
modalConfirmEditPricesBtn.click(function(){
	let comment = modalEditPricesComment.val();
	if(!comment.length){
		modalConfirmEditPricesBtn.removeAttr('disabled').find('.spinning').addClass('d-none');
		alert('Введите комментарий');
		return false;
	}
	modalConfirmEditPricesBtn.attr('disabled','').find('.spinning').removeClass('d-none');
	$.post('<?php echo $DP_Config->domain_path . $DP_Config->backend_dir?>/content/shop/logistics/ajax.stock_control.php', {
		action: 'update_prices',
		csrf_guard_key: '<?php echo $user_session["csrf_guard_key"]; ?>',
		percent: !isNaN(modalEditPricesPercentValue) ? modalEditPricesPercentValue : 0,
		comment: comment,
		crossed_out: modalEditPricesCrossedOut.prop('checked') ? 1 : 0,
		categories: selectedCategories().join(","),
		search_text: $("#search_text").val(),
		manufacturers: $("#manufacturers-list").multipleSelect('getSelects').join(",")
	}, function(res){
		modalConfirmEditPricesBtn.removeAttr('disabled').find('.spinning').addClass('d-none');
		modalEditPricesPercent.val("");
		modalEditPricesComment.val("");
		modalEditPricesCrossedOut.prop('checked', true);
		toModalConfirmEditPrices.attr('disabled', '');
		modalConfirmEditPrices.modal('hide');
		modalEditPrices.modal('show');
		alert(res.message);
	}).fail(function(){
		modalConfirmEditPricesBtn.removeAttr('disabled').find('.spinning').addClass('d-none');
		alert('Ошибка выполнения запроса');
	});
});
$("#modal-confirm-edit-prices-btn-no").click(function(){
	modalEditPrices.modal('show');
})
toModalConfirmEditPrices.click(function(){
	toModalConfirmEditPrices.attr('disabled','').find('.spinning').removeClass('d-none');
	$.post('<?php echo $DP_Config->domain_path . $DP_Config->backend_dir?>/content/shop/logistics/ajax.stock_control.php', {
		action: 'search_products',
		csrf_guard_key: '<?php echo $user_session["csrf_guard_key"]; ?>',
		categories: selectedCategories().join(","),
		search_text: $("#search_text").val()
	}, function(res){
		if(res.status){
			modalEditPricesPercentValue = parseFloat(modalEditPricesPercent.val());
			$("#modal-confirm-edit-prices-percent").text((modalEditPricesPercentValue > 0 ? '+' + modalEditPricesPercentValue : modalEditPricesPercentValue)+'%');
			$("#modal-confirm-edit-prices-result").html(res.message);

			modalEditPrices.modal('hide');
			modalConfirmEditPrices.modal('show');
		}else{
			alert('Ошибка получения списка товаров');
		}
		toModalConfirmEditPrices.removeAttr('disabled').find('.spinning').addClass('d-none');
	}).fail(function(){
		toModalConfirmEditPrices.removeAttr('disabled').find('.spinning').addClass('d-none');
		alert('Ошибка выполнения запроса');
	});
});
toModalHistoryEditPrices.click(function(){
	modalEditPrices.modal('hide');
	modalHistoryEditPrices.modal('show');
	getPricesEditHistory(0);
});
function getPricesEditHistory(offset)
{
	let list = modalHistoryEditPricesList;
	offset = parseInt(offset)||0;
	if(offset === 0){
		list.html(`
			<tr class="tr-loader">
				<td colspan="4">
					<span class="glyphicon glyphicon-refresh spinning"></span>
				</td>
			</tr>
		`);	
	}else{
		list.find('.tr-more').find('.spinning').removeClass('d-none').attr('disabled', '');
	}
	$.post('<?php echo $DP_Config->domain_path . $DP_Config->backend_dir?>/content/shop/logistics/ajax.stock_control.php', {
		action: 'get_history',
		offset: offset,
		csrf_guard_key: '<?php echo $user_session["csrf_guard_key"]; ?>'
	}, function(res){
		if(res.status){
			let html = [];
			for(let i = 0; i < res.message.items.length; i++){
				let item = res.message.items[i];
				let percent = item.percent;
				html.push(`
					<tr item-id="${item.id}">
						<td>${item.created_ex}</td>
						<td>${item.user} (ID: ${item.user_id})</td>
						<td>
							<span class="tr-percent`+(percent >= 0 ? ' tr-plus': '')+`">`+(percent >= 0 ? '+' + percent : percent)+`</span>
						</td>
						<td>${item.comment}</td>
					</tr>
				`);
			}
			if(!res.message.items.length && offset === 0){
				html.push(`
					<tr class="tr-empty">
						<td colspan="4">Нет записей</td>
					</tr>
				`);
			}
			list.find('.tr-more, .tr-loader, .tr-empty').remove();
			list.append(html.join(""));
			if(res.message.has_more){
				list.append(`
					<tr class="tr-more">
						<td colspan="4">
							<button class="btn btn-primary" onclick="getHistoryEditPricesMore(this)">
								<span class="glyphicon glyphicon-refresh spinning d-none"></span>
								<span class="text">Показать ещё</span>
							</button>
						</td>
					</tr>
				`);
			}
		}else{
			alert('Ошибка получения списка товаров');
			list.find('.tr-more').find('.spinning').addClass('d-none').removeAttr('disabled');
		}
	}).fail(function(){
		list.find('.tr-more').find('.spinning').addClass('d-none').removeAttr('disabled');
		alert('Ошибка выполнения запроса');
	});
}
function getHistoryEditPricesMore(self)
{
	self = $(self);
	self.find('.spinning').removeClass('d-none').attr('disabled', '');
	getPricesEditHistory(self.parent().parent().prev().attr('item-id'));
}
</script>