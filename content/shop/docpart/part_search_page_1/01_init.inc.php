//АЛГОРИТМ ПОИСКА ЗАПЧАСТЕЙ ПО АРТИКУЛУ
var search_object = new Object;//Объект запроса



//Первым делом указываем артикул и производителя:
search_object.article = "<?php echo $article; ?>";
search_object.searsch_str = "<?php echo $searsch_str; ?>";
search_object.manufacturers = null;//Задаем значение null



//Настройки связанные с этим типом отображения:
var group_day = 2;// Аналоги 0 - 2 дня
var cnt_to_hide = 1;// Количество позиций после которых начинаем сворачивать группы
var flag_one = true;// true - Флаг показывает что клиент первый раз загружает проценку
var flag_time_head = <?=(int)$DP_Config->flag_time_head?>;// Перемещать ли позиции со сроком 0-2 дня выше остальных позиций в группе при первой загрузке проценки


//ОБЪЕКТ ОПИСАНИЯ ТОВАРОВ ПРИНЯТЫХ ОТ СЕРВЕРА
var Products = new Object;

//Запрошенные товары
Products.Required = new Object;//Объект с запрошенными товарами
Products.Required.Products = new Object;//Структура для объектов товаров
Products.Required.Products.Manufacturers = new Object;//Разделение на производителей
Products.Required.ProductsTypes = new Array();//Список типов товаров

//Товары найденные по наименованию в каталоге или прайс листах
Products.SearchName = new Object;//Объект с найденными товарами
Products.SearchName.Products = new Object;//Структура для объектов товаров
Products.SearchName.Products.Manufacturers = new Object;//Разделение на производителей
Products.SearchName.ProductsTypes = new Array();//Список типов товаров

//Аналоги с быстрой доставкой 0 - 2 дня
Products.Quick_Analogs = new Object;
Products.Quick_Analogs.Products = new Object;
Products.Quick_Analogs.Products.Manufacturers = new Object;
Products.Quick_Analogs.ProductsTypes = new Array();//Список типов товаров

//Остальные Аналоги
Products.Analogs = new Object;
Products.Analogs.Products = new Object;
Products.Analogs.Products.Manufacturers = new Object;
Products.Analogs.ProductsTypes = new Array();//Список типов товаров

//Возможные замены
Products.PossibleReplacement = new Object;
Products.PossibleReplacement.Products = new Object;
Products.PossibleReplacement.Products.Manufacturers = new Object;
Products.PossibleReplacement.ProductsTypes = new Array();//Список типов товаров

//Дополнительный свободный блок для доработок
Products.Spare_Box = new Object;
Products.Spare_Box.Products = new Object;
Products.Spare_Box.Products.Manufacturers = new Object;
Products.Spare_Box.ProductsTypes = new Array();//Список типов товаров

//Индексный список для поиска нужного объекта по его клиентскому ID (AID - All ID). Т.е. каждый объект товара при примеме от сервера получает ID в рамках данной страницы.
//Этот список предназначен для получения объекта товара по его AID:
Products.All = new Array();//Список объектов


function psp1IsModernLayout()
{
	return M.template === 'modern';
}

function psp1BuildHeadBlock(headlines)
{
	if (!psp1IsModernLayout()) {
		return '<tr><th class="'+M.th_manufacturer+headlines.manufacturer.subclass+'" onclick="outerSortChange(\'manufacturer\');">'+headlines.manufacturer.caption+'</th><th class="'+M.th_article+headlines.article.subclass+'" onclick="outerSortChange(\'article\');">'+headlines.article.caption+'</th><th class="'+M.th_name+headlines.name.subclass+'" onclick="outerSortChange(\'name\');">'+headlines.name.caption+'</th><th class="'+M.th_exist+headlines.exist.subclass+'" onclick="innerSortChange(\'exist\');">'+headlines.exist.caption+'</th><th class="'+M.th_time_to_exe+headlines.time_to_exe.subclass+'" onclick="innerSortChange(\'time_to_exe\');">'+headlines.time_to_exe.caption+'</th><th class="'+M.th_info+'">'+M.txt_th_info+'</th><th class="'+M.th_price+headlines.price.subclass+'" onclick="innerSortChange(\'price\');">'+headlines.price.caption+'</th><th class="'+M.th_add_to_cart+'"></th><th class="'+M.th_color+'"></th></tr>';
	}
	return '<div class="'+M.head_row_class+'" role="row">'
		+'<div class="'+M.th_manufacturer+headlines.manufacturer.subclass+'" role="columnheader" onclick="outerSortChange(\'manufacturer\');">'+headlines.manufacturer.caption+'</div>'
		+'<div class="'+M.th_article+headlines.article.subclass+'" role="columnheader" onclick="outerSortChange(\'article\');">'+headlines.article.caption+'</div>'
		+'<div class="'+M.th_name+headlines.name.subclass+'" role="columnheader" onclick="outerSortChange(\'name\');">'+headlines.name.caption+'</div>'
		+'<div class="'+M.th_exist+headlines.exist.subclass+'" role="columnheader" onclick="innerSortChange(\'exist\');">'+headlines.exist.caption+'</div>'
		+'<div class="'+M.th_time_to_exe+headlines.time_to_exe.subclass+'" role="columnheader" onclick="innerSortChange(\'time_to_exe\');">'+headlines.time_to_exe.caption+'</div>'
		+'<div class="'+M.th_info+'" role="columnheader">'+M.txt_th_info+'</div>'
		+'<div class="'+M.th_price+headlines.price.subclass+'" role="columnheader" onclick="innerSortChange(\'price\');">'+headlines.price.caption+'</div>'
		+'<div class="'+M.th_add_to_cart+'" role="columnheader"></div>'
		+'<div class="'+M.th_color+'" role="columnheader" aria-hidden="true"></div>'
		+'</div>';
}

function psp1FormatSectionCaption(styleAttr, captionHtml)
{
	if (psp1IsModernLayout()) {
		return '<div class="'+M.section_block_class+'"><h3 class="'+M.products_table_block_caption+'">'+captionHtml+'</h3></div>';
	}
	return '<tr><td colspan="'+M.colspan+'" class="'+M.products_table_block_caption+'" style="'+styleAttr+'">'+captionHtml+'</td></tr>';
}

function psp1FormatSectionPager(nextPageKey)
{
	if (psp1IsModernLayout()) {
		return '<div class="'+M.next_page_box+'"><a class="'+M.btn_load_more+'" href="javascript:void(0);" onclick="next_page(\''+nextPageKey+'\');" id="'+M.prefix_next_page+nextPageKey+'"><i class="'+M.fa_arrow_down+'" aria-hidden="true"></i>'+M.txt_show_more_paging+'</a></div>';
	}
	return '<tr><td style="'+M.style_next_page_cell+'" class="'+M.next_page_box+'" colspan="'+M.colspan+'"><a style="'+M.style_next_page_link+'" class="'+M.btn_load_more+'" href="javascript:void(0);" onclick="next_page(\''+nextPageKey+'\');" id="'+M.prefix_next_page+nextPageKey+'"><i class="'+M.fa_arrow_down+'" aria-hidden="true"></i>'+M.txt_show_more_paging+'</a></td></tr>';
}


// ------------------------------------------------------------------------------------------------------------------------------




//Обработка полученного результата
function onGetStoragesData()
{
	<?php
	if((int)$group_info['for_percentage'] === 1){
	?>
	recalculate_groups_margin();//Функция смены наценки группы
	<?php
	}
	?>
	innerSort();//Внутренняя сортировка (Наличие-Срок-Цена) / внешняя вход в внутреннею
	resultReview();//Обновляем отображние результата
}




// ------------------------------------------------------------------------------------------------------------------------------




//Метод соединения полученного от сервера результата с общим объектом описания найденных товаров
