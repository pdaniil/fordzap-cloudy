//Отображение/Переотображение результата запроса
function resultReview()
{
	//Общее содержимое
    var products_html = "";
    
    //HTML для блока с заголовками колонок
    var headlines = new Object;
    headlines.manufacturer = new Object;
    headlines.manufacturer.caption = "Производитель";
    headlines.manufacturer.subclass = "";
    headlines.article = new Object;
    headlines.article.caption = "Артикул";
    headlines.article.subclass = "";
    headlines.name = new Object;
    headlines.name.caption = "Наименование";
    headlines.name.subclass = "";
    headlines.exist = new Object;
    headlines.exist.caption = "Наличие";
    headlines.exist.subclass = "";
    headlines.price = new Object;
    headlines.price.caption = "Цена, <?php echo str_replace('"', '\"', $currency_sign); ?>";
    headlines.price.subclass = "";
    headlines.time_to_exe = new Object;
    headlines.time_to_exe.caption = "Срок";
    headlines.time_to_exe.subclass = "";
	if(flag_one != true){
		//Ставим обозначение текущей внутренней сортировки
		if(
			outerSortState.field == innerSortState.field
		){
			headlines[innerSortState.field].caption += " <img src=\""+M.sort_icon_base+innerSortState.asc_desc+".png\" />";
			headlines[innerSortState.field].subclass += M.sorted;
		}
		
		//Ставим обозначение текущей внешней сортировки, т.е. по полям Производитель, Артикул, Наименование
		if( (outerSortState.field != 'exist' && 
			outerSortState.field != 'time_to_exe' && 
			outerSortState.field != 'price') || outerSortState.field != innerSortState.field
		){
			headlines[outerSortState.field].caption += " <img src=\""+M.sort_icon_base+outerSortState.asc_desc+".png\" />";
			headlines[outerSortState.field].subclass += M.sorted;
		}
	}
    
	
    var head_block = psp1BuildHeadBlock(headlines);
    
    
    //HTML для запрошенного артикула
    var required_block = "";
	//HTML для найденных по наименованию
    var SearchName_block = "";
	//HTML для быстрых аналогов
	var quick_analogs_block = "";
    //HTML для аналогов
    var analogs_block = "";
	//HTML для блока Возможные замены
	var PossibleReplacement_block = "";
	//HTML для дополнительного блока
	var Spare_Box_block = "";
	
	
	// Найденные бренды после фильтрации = сбрасываем список перед фильтрацией
	arr_manufacturers_posle_filter =  new Array();
	// Найденные склады после фильтрации
	arr_storages_posle_filter =  new Array();
	
	
	// Массив всех найденных позиций после фильтрации. Нужен для формирования фильтра
	var ALL_ProductsObjects = new Array;
    

	// Если текущий выбранный фильтр производители или склады то сбрасываем фильтры диапазонов на предыдущие значения, что бы можно было отменить фильтр убрав checkbox
	if(this_filter == 'manufacturer_blok' || this_filter == 'storages_blok' || this_filter == 'sam_price_time_blok'){
	// Цена
		filter['price_blok'].min_need = filter['price_blok'].old_min_need;
		filter['price_blok'].max_need = filter['price_blok'].old_max_need;
	// Срок
		filter['time_to_exe_blok'].min_need = filter['time_to_exe_blok'].old_min_need;
		filter['time_to_exe_blok'].max_need = filter['time_to_exe_blok'].old_max_need;
	// Наличие
		filter['exist_blok'].min_need = filter['exist_blok'].old_min_need;
		filter['exist_blok'].max_need = filter['exist_blok'].old_max_need;
	}
	
	
	// Ограничение количества отображаемых позиций (10, 20, 50)
	// Счетчик отфильтрованных групп
	var Products_Required_Show_count = 0;
	var Products_SearchName_Show_count = 0;
	var Products_Quick_Analogs_Show_count = 0;
	var Products_Analogs_Show_count = 0;
	var Products_PossibleReplacement_Show_count = 0;
	var Products_Spare_Box_Show_count = 0;
	// Количество групп после фильтрации (нужно для определения кнопки Показать еще)
	var Products_Required_count = 0;
	var Products_SearchName_count = 0;
	var Products_Quick_Analogs_count = 0;
	var Products_Analogs_count = 0;
	var Products_PossibleReplacement_count = 0;
	var Products_Spare_Box_count = 0;
	
	
	
	
	
	
	
	
	// Работаем с Запрошенным
    for(var i=0; i < Products.Required.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.Required.ProductsTypes[i].manufacturer;
        var Article = Products.Required.ProductsTypes[i].article;
		//Массив объектов товаров:
        var ProductsObjects = Products.Required.Products.Manufacturers[Manufacturer][Article];

		ProductsObjects = ProductsObjects.sort(function(a, b){
			return a.time_to_exe < b.time_to_exe ? -1 : (a.time_to_exe < b.time_to_exe ? 1 : 0);
		});

		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
	
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Required_count++;
			if( (cnt_on_page + start_page_Required) <= Products_Required_Show_count ){continue;}
			Products_Required_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				required_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.Required.ProductsTypes[i], 'required');
			}
		}
    }// END Работаем с Запрошенным
    
	
	
	
    
	
	
	
	// Работаем с найденными по НАИМЕНОВАНИЮ
    for(var i=0; i < Products.SearchName.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.SearchName.ProductsTypes[i].manufacturer;
        var Article = Products.SearchName.ProductsTypes[i].article;
        //Массив объектов товаров:
        var ProductsObjects = Products.SearchName.Products.Manufacturers[Manufacturer][Article];

		ProductsObjects = ProductsObjects.sort(function(a, b){
			return a.time_to_exe < b.time_to_exe ? -1 : (a.time_to_exe < b.time_to_exe ? 1 : 0);
		});
		
		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
		
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_SearchName_count++;
			if( (cnt_on_page + start_page_SearchName) <= Products_SearchName_Show_count ){continue;}
			Products_SearchName_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				SearchName_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.SearchName.ProductsTypes[i], 'SearchName');
			}
		}
    }// END
	
	
	
	
	
	
	
	
	// Работаем с Быстрыми Аналогами
	for(var i=0; i < Products.Quick_Analogs.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.Quick_Analogs.ProductsTypes[i].manufacturer;
        var Article = Products.Quick_Analogs.ProductsTypes[i].article;
        //Массив объектов товаров:
        var ProductsObjects = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article];
		
		ProductsObjects = ProductsObjects.sort(function(a, b){
			return a.time_to_exe < b.time_to_exe ? -1 : (a.time_to_exe < b.time_to_exe ? 1 : 0);
		});
		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
		
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Quick_Analogs_count++;
			if( (cnt_on_page + start_page_Quick_Analogs) <= Products_Quick_Analogs_Show_count ){continue;}
			Products_Quick_Analogs_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				quick_analogs_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.Quick_Analogs.ProductsTypes[i], 'quick_analogs');
			}
		}
    }// END Работаем с Быстрыми Аналогами
	
	
	
	
	
	
	
	
   // Работаем с Аналогами
    for(var i=0; i < Products.Analogs.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.Analogs.ProductsTypes[i].manufacturer;
        var Article = Products.Analogs.ProductsTypes[i].article;
        //Массив объектов товаров:
        var ProductsObjects = Products.Analogs.Products.Manufacturers[Manufacturer][Article];

		ProductsObjects = ProductsObjects.sort(function(a, b){
			return a.time_to_exe < b.time_to_exe ? -1 : (a.time_to_exe < b.time_to_exe ? 1 : 0);
		});
		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Analogs_count++;
			if( (cnt_on_page + start_page_Analogs) <= Products_Analogs_Show_count ){continue;}
			Products_Analogs_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				analogs_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.Analogs.ProductsTypes[i], 'analogs');
			}
		}
    }// END Работаем с Аналогами
	
	
	
	
	
	
	
	
	// Работаем с PossibleReplacement
    for(var i=0; i < Products.PossibleReplacement.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.PossibleReplacement.ProductsTypes[i].manufacturer;
        var Article = Products.PossibleReplacement.ProductsTypes[i].article;
		//Массив объектов товаров:
        var ProductsObjects = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article];

		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
	
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_PossibleReplacement_count++;
			if( (cnt_on_page + start_page_PossibleReplacement) <= Products_PossibleReplacement_Show_count ){continue;}
			Products_PossibleReplacement_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				PossibleReplacement_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.PossibleReplacement.ProductsTypes[i], 'PossibleReplacement');
			}
		}
    }// END Работаем с PossibleReplacement
	
	
	
	
	
	
	
	
	// Работаем с Spare_Box
    for(var i=0; i < Products.Spare_Box.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.Spare_Box.ProductsTypes[i].manufacturer;
        var Article = Products.Spare_Box.ProductsTypes[i].article;
		//Массив объектов товаров:
        var ProductsObjects = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article];

		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
	
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Spare_Box_count++;
			if( (cnt_on_page + start_page_Spare_Box) <= Products_Spare_Box_Show_count ){continue;}
			Products_Spare_Box_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				Spare_Box_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.Spare_Box.ProductsTypes[i], 'Spare_Box');
			}
		}
    }// END Работаем с Spare_Box
	
	
	
	
	
	
	
	// После обработки всех позиций формируем новый фильтр диапазонов
	// Изменяем крайние значения фильтра, если фильтр диапазона не является текущим.
	if(ALL_ProductsObjects.length > 0){
		// Цена
		if(this_filter !== 'price_blok'){
			filter['price_blok'].min_value = undefined;
			filter['price_blok'].max_value = undefined;
		}
		// Срок
		if(this_filter !== 'time_to_exe_blok'){
			filter['time_to_exe_blok'].min_value = undefined;
			filter['time_to_exe_blok'].max_value = undefined;
		}
		// Наличие
		if(this_filter !== 'exist_blok'){
			filter['exist_blok'].min_value = undefined;
			filter['exist_blok'].max_value = undefined;
		}
	}
	
	
	// Устанавливаем новые значения фильтра
	for(var p=0; p < ALL_ProductsObjects.length; p++){
		var ProductsObjects_array = ALL_ProductsObjects[p];
		for(var i=0; i < ProductsObjects_array.length; i++){
			
			var ProductsObjects = ProductsObjects_array[i];
			
			// Цена
			if(filter['price_blok'].min_value == undefined){
				filter['price_blok'].min_value = ProductsObjects["price"];
			}
			if(filter['price_blok'].max_value == undefined){
				filter['price_blok'].max_value = ProductsObjects["price"];
			}
			
			if(ProductsObjects["price"] < filter['price_blok'].min_value){
				filter['price_blok'].min_value = ProductsObjects["price"];
			}
			if(ProductsObjects["price"] > filter['price_blok'].max_value){
				filter['price_blok'].max_value = ProductsObjects["price"];
			}
			
			// Срок
			if(filter['time_to_exe_blok'].min_value == undefined){
				filter['time_to_exe_blok'].min_value = ProductsObjects["time_to_exe"];
			}
			if(filter['time_to_exe_blok'].max_value == undefined){
				filter['time_to_exe_blok'].max_value = ProductsObjects["time_to_exe"];
			}
			
			if(ProductsObjects["time_to_exe"] < filter['time_to_exe_blok'].min_value){
				filter['time_to_exe_blok'].min_value = ProductsObjects["time_to_exe"];
			}
			if(ProductsObjects["time_to_exe"] > filter['time_to_exe_blok'].max_value){
				filter['time_to_exe_blok'].max_value = ProductsObjects["time_to_exe"];
			}
			
			// Наличие
			if(filter['exist_blok'].min_value == undefined){
				filter['exist_blok'].min_value = ProductsObjects["exist"];
			}
			if(filter['exist_blok'].max_value == undefined){
				filter['exist_blok'].max_value = ProductsObjects["exist"];
			}
			
			if(ProductsObjects["exist"] < filter['exist_blok'].min_value){
				filter['exist_blok'].min_value = ProductsObjects["exist"];
			}
			if(ProductsObjects["exist"] > filter['exist_blok'].max_value){
				filter['exist_blok'].max_value = ProductsObjects["exist"];
			}
			
        }
	}
	
	
	
	
	
	
	// Формируем HTML проценки
	
	if(required_block != ''){
		required_block = psp1FormatSectionCaption(M.style_block_caption_padding_top, M.txt_block_required_ok) + required_block + psp1FormatSectionPager('Required');
	}else if (Spare_Box_block == ''){
		required_block = psp1FormatSectionCaption('', M.txt_block_required_missing);
	}
	
	if(quick_analogs_block != "")
	{
		quick_analogs_block = psp1FormatSectionCaption(M.style_block_caption_border_top_none, M.txt_block_quick_analogs) + quick_analogs_block + psp1FormatSectionPager('Quick_Analogs');
	}
	
	if(analogs_block != ''){
		analogs_block = psp1FormatSectionCaption(M.style_block_caption_border_top_none, M.txt_block_analogs) + analogs_block + psp1FormatSectionPager('Analogs');
	}
	
	if(SearchName_block != ''){
		SearchName_block = psp1FormatSectionCaption(M.style_block_caption_border_top_none, M.txt_block_search_name) + SearchName_block + psp1FormatSectionPager('SearchName');
	}
	
	if(PossibleReplacement_block != ''){
		PossibleReplacement_block = psp1FormatSectionCaption(M.style_block_caption_border_top_none, M.txt_block_possible_replacement) + PossibleReplacement_block + psp1FormatSectionPager('PossibleReplacement');
	}
	
	if(Spare_Box_block != ''){
		Spare_Box_block = psp1FormatSectionCaption(M.style_block_caption_spare, M.txt_block_spare_box) + Spare_Box_block + psp1FormatSectionPager('Spare_Box');
	}
	
	// Оюъединяем блоки позиций
	products_html = Spare_Box_block + required_block + PossibleReplacement_block + quick_analogs_block + analogs_block + SearchName_block;
	
	if(products_html != ''){
		
		let for_percentage = '';
		<?php
		if((int)$group_info['for_percentage'] === 1){
		?>
		let show_data_checked = '';
		if(flag_show_data){
			show_data_checked = 'checked';
		}
		for_percentage += '<div class="'+M.div_show_data_checked+'" style="text-align: right; position: relative;"><input '+show_data_checked+' style="margin: 0; padding: 0; margin-right: 0px; height: 20px; width: 20px; position: absolute; top: 1px; cursor: pointer; right: 0px;" type="checkbox" id="'+M.id_show_data+'" onChange="show_data();"/></div><div class="'+M.show_data_class+'" style="text-align: right; padding-right: 25px;"><select id="'+M.id_select_the_groups_margin+'" onChange="changing_the_groups_margin();">';
			<?php
			$group_query = $db_link->prepare('SELECT * FROM `groups` ORDER BY `order`;');
			$group_query->execute();
			while($group_record = $group_query->fetch()){
				$selected = "";
				if($group_record['id'] === '1'){
					continue;
				}
				if($group_id == $group_record['id'])
				{
					$selected = "selected=\"selected\"";
				}
				echo "for_percentage += '<option ".$selected." value=\"".$group_record['id']."\">".$group_record['value']."</option>';";
			}
			?>
		for_percentage += '</select></div>';
		<?php
		}
		?>
		
		if (psp1IsModernLayout()) {
			products_html = for_percentage + '<div class="'+M.root_class+'" id="'+M.id_all_table_products+'"><div class="'+M.wrap_offers_head_class+'">' + head_block + '</div><div class="'+M.wrap_offers_body_class+'">' + products_html + '</div></div>';
		} else {
			products_html = for_percentage + '<table id="'+M.id_all_table_products+'"><thead>' + head_block + '</thead><tbody>' + products_html + '</tbody></table>';
		}
	}
	
    
	
	
	
	
	
	// Если после фильтрации все же не осталось позиций
	if(this_filter != '' && products_html == ''){
		products_html = '<div class="'+M.empty_filter_class+'">'+M.txt_empty_after_filter+'</div>';
	}
    
	// Отображаем проценку
    document.getElementById(M.id_products_area).innerHTML = products_html;
	
	// Отображаем фильтр
	showPropertiesWidgets();
	
	// Работаем с кнопками "показать еще" вконце блоков
	var next_page_Required = document.getElementById(M.prefix_next_page+'Required');
	var next_page_SearchName = document.getElementById(M.prefix_next_page+'SearchName');
	var next_page_Quick_Analogs = document.getElementById(M.prefix_next_page+'Quick_Analogs');
	var next_page_Analogs = document.getElementById(M.prefix_next_page+'Analogs');
	var next_page_PossibleReplacement = document.getElementById(M.prefix_next_page+'PossibleReplacement');
	var next_page_Spare_Box = document.getElementById(M.prefix_next_page+'Spare_Box');
	
	if(next_page_Required){
		if((start_page_Required + cnt_on_page) >= Products_Required_Show_count){
			next_page_Required.style.display = 'none';
		}
		if((start_page_Required + cnt_on_page) < Products_Required_count){
			next_page_Required.style.display = 'inline-block';
		}
	}
	
	if(next_page_SearchName){
		if((start_page_SearchName + cnt_on_page) >= Products_SearchName_Show_count){
			next_page_SearchName.style.display = 'none';
		}
		if((start_page_SearchName + cnt_on_page) < Products_SearchName_count){
			next_page_SearchName.style.display = 'inline-block';
		}
	}
	
	if(next_page_Quick_Analogs){
		if((start_page_Quick_Analogs + cnt_on_page) >= Products_Quick_Analogs_Show_count){
			next_page_Quick_Analogs.style.display = 'none';
		}
		if((start_page_Quick_Analogs + cnt_on_page) < Products_Quick_Analogs_count){
			next_page_Quick_Analogs.style.display = 'inline-block';
		}
	}

	if(next_page_Analogs){
		if((start_page_Analogs + cnt_on_page) >= Products_Analogs_Show_count){
			next_page_Analogs.style.display = 'none';
		}
		if((start_page_Analogs + cnt_on_page) < Products_Analogs_count){
			next_page_Analogs.style.display = 'inline-block';
		}
	}
	
	if(next_page_PossibleReplacement){
		if((start_page_PossibleReplacement + cnt_on_page) >= Products_PossibleReplacement_Show_count){
			next_page_PossibleReplacement.style.display = 'none';
		}
		if((start_page_PossibleReplacement + cnt_on_page) < Products_PossibleReplacement_count){
			next_page_PossibleReplacement.style.display = 'inline-block';
		}
	}
	
	if(next_page_Spare_Box){
		if((start_page_Spare_Box + cnt_on_page) >= Products_Spare_Box_Show_count){
			next_page_Spare_Box.style.display = 'none';
		}
		if((start_page_Spare_Box + cnt_on_page) < Products_Spare_Box_count){
			next_page_Spare_Box.style.display = 'inline-block';
		}
	}
	
	
	
	show_pictures_products();// Отображаем миниатюры товаров

	<?php
	if((int)$group_info['for_percentage'] === 1){
	?>
	document.getElementById(M.id_select_the_groups_margin).value = this_group_id_margin;
	show_data();
	<?php
	}
	?>
	
	// КАРТОЧКИ ПОЗИЦИЙ -----------------------------------
		
	// Отображаем карточки после отображения проценки
	set_html_cart_block();
	
	// КАРТОЧКИ ПОЗИЦИЙ END -------------------------------
}




// ------------------------------------------------------------------------------------------------------------------------------




