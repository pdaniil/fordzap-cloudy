// Единая функция формирования HTML-кода для одной записи товара.
function getProductRecordHTML(Product, index, quantity, ProductType, blok)
{
	var __psp1Row = (M.template === 'modern') ? 'div' : 'tr';
	var __psp1Cell = (M.template === 'modern') ? 'div' : 'td';
    var manufacturer = "", article_show = "", name = "";

	var time_to_exe = Product.time_to_exe;
    if(Product.time_to_exe != Product.time_to_exe_guaranteed)
    {
        time_to_exe = Product.time_to_exe + "-" + Product.time_to_exe_guaranteed +" дн.";
    }else{
        if(time_to_exe == 0)
        {
            if(all_storages_short_text[Product.storage_id]){
                time_to_exe = "<div title=\""+ all_storages_short_text[Product.storage_id] +"\" style=\""+M.style_time_exe_stock_highlight+"\">"+ all_storages_short_text[Product.storage_id] +"</div>";
            }else{
                time_to_exe = "На складе";
            }
        }else{
            time_to_exe = time_to_exe + " дн.";
        }
    }
    var color = Product.color;
    
	
	
	
	//Строка для показа цены
	var price = Product.price;
	// Отделяем разрядность
	price = digit(price)
	<?php
	//Индикатор валюты перед ценой
	if($DP_Config->currency_show_mode == "sign_before")
	{
		?>
		price = '<?php echo $currency_indicator; ?> ' + price;
		<?php
	}
	//Индикатор валюты после цены
	else if($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after")
	{
		?>
		price = price + ' <?php echo $currency_indicator; ?>';
		<?php
	}
	?>
	
	
	
	<?php
	if((int)$group_info['for_percentage'] === 1){
	?>
	let price_purchase = Product.price_purchase;//Закупочная цена для блока тех. информации
	price_purchase = digit(price_purchase)
	price += "<div class='"+M.show_data_class+"' style='font-size: 10px; color: #959393; position: relative; top: 1px;'>"+ price_purchase +"</div>";
	<?php
	}
	?>
	
	
	
    //ФОРМИРОВАНИЕ КОЛОНКИ ИНФО
	var info = '';
	
    info += "<a title=\"Фотографии товара\" href=\"https://www.google.ru/search?q="+encodeURIComponent(ProductType.manufacturer)+"+"+ProductType.article+"&newwindow=1&biw=1366&bih=667&tbm=isch&tbo=u&source=univ&sa=X&ved=0CC8QsARqFQoTCMDCoO70jMkCFQGFLAodrT0GFw\" target=\"_blank\"><span><i style=\"font-size: .8em;\" class=\"fa fa-camera\"></i></span></a>";
	
	if(Product.storage_caption != "")
    {
		var storage_caption = '<br>Поставщик: '+Product.storage_caption;
			storage_caption = storage_caption + '<br>Склад: '+all_storages[Product.storage_id];
			storage_caption = storage_caption + '<br>ID: '+Product.storage_id;
    }else{
		var storage_caption = '<br>Склад: '+all_storages[Product.storage_id];
	}
	
	info += "<a title=\"Где забрать\" href=\"javascript:void(0);\"><span onclick=\"openInfoWindow('Где забрать', '"+Product.office_caption+storage_caption+"');\" ><i class=\"fa fa-home\"></i></span></a>";
    
	<?php
	if($user_id){
	?>
	info += "<a href=\"javascript:void(0);\" title=\"Добавить в блокнот\" onclick=\"show_add_bloknot("+Product.aid+");\"><span><i style=\"font-size: .9em;\" class=\"fa fa-car\"></i></span></a>";
	<?php
	}else{
	?>
	info += "<a href=\"javascript:void(0);\" title=\"Добавить в блокнот\" onclick=\"alert('Для добавления позиций в блокнот необходимо авторизоваться на сайте');\"><span><i style=\"font-size: .9em;\" class=\"fa fa-car\"></i></span></a>";
	<?php
	}
	?>
	
	info += "<br/>";
	
	if(Product.min_order > 1)
    {
        info += "<a title=\"Минимальный заказ\" href=\"javascript:void(0);\"><span onclick=\"openInfoWindow('Внимание!', 'Минимальный заказ "+Product.min_order+" шт.');\"><i class=\"fa fa-warning\"></i></span></a>";
    }
	
    if(Product.product_type == 1)//Для товара из каталога Treelax - выводим ссылку на страницу товара
    {
        info += "<a title=\"Открыть карточку товара\" href=\""+Product.url+"\" target=\"_blank\"><i class=\"fa fa-file-image-o\"></i></a>";
    }
	
	info = '<span class="'+M.info_box+'">'+ info +'</span>';
	
	<?php
	if((int)$group_info['for_percentage'] === 1){
	?>
	info += "<div class='"+M.show_data_class+"' style=\"margin: 3px 0px; white-space: nowrap; max-width: 80px; font-size: 11px; position: relative;\" title=\""+ Product.storage_caption +"\"><span style=\"overflow: hidden; text-overflow: ellipsis;\" class=\""+M.info_box+"\">"+ Product.storage_caption +"</span></div>";
	<?php
	}
	?>
    
	info = '<div style="'+M.style_info_wrap_margin+'">'+ info +'</div>';
	
	// С подсказкой - этот вариант основной
	if(Product.check_return !== "") {

		let return_value = '';
		if(Product.json_params !== '' && Product.json_params !== null) {
			return_value_obj = JSON.parse(Product.json_params);
			return_value = return_value_obj.return_value || '';
		}

		if(Product.check_return == "return") {
			if(return_value !== undefined && return_value != '' ) {
				info += "<span class=\""+M.product_return_icon_value+" "+M.product_return_success+"\"><span data-tooltip=\"Процент удержания за возврат товара "+return_value+"%\" class=\""+M.product_return_icon_tooltip+"\"></span>"+return_value+"%</span>";
			}
			else
			{
				info += "<span class=\""+M.product_return_icon+" "+M.product_return_success+"\"><span data-tooltip=\"Возврат возможен\" class=\""+M.product_return_icon_tooltip+"\"></span></span>";
			}
		} else if(Product.check_return == "no_return") {
			info += "<span class=\""+M.product_return_icon+" "+M.product_return_error+"\"><span data-tooltip=\"Внимание! Эта деталь не подлежит возврату или обмену!\" class=\""+M.product_return_icon_tooltip+"\"></span></span>";
		}
	}
	
	info = '<span class="'+M.info_box+'">'+ info +'</span>';
	
    //Формирование колонки "Наличие"
    //Объект с описанием наличия - для отображения в информационном окне
    var supply_info_json = "{&quot;exist&quot;:"+Product.exist+",&quot;time_to_exe&quot;:"+Product.time_to_exe+",&quot;time_to_exe_guaranteed&quot;:"+Product.time_to_exe_guaranteed+",&quot;probability&quot;:"+Product.probability+"}";
    //Колонка
    var exist = "<span onclick=\"openInfoWindow(null, null, 1, '"+supply_info_json+"');\">" + Product.exist + "<img src=\"/lib/TreelaxCharts/sectors.php?number=2&value0="+Product.probability+"&value1="+(100-Product.probability)+"&start_angle=30&size=50&inside_size=1&slope=1.1\" /></span>";
    
	
    
	
    // Сворачиваем строки, если их больше чем указано в настройках для блока
    if(Product.hidden){
		var start_wrap_div = "<"+__psp1Row+" style='background:"+Product.hidden_color+";'>";
	}else{
		if(all_storages_info[Product.storage_id]['bg_line_color'] == 1){
        	var start_wrap_div = "<"+__psp1Row+" style='background:"+color+";'>";
        }else{
        	var start_wrap_div = "<"+__psp1Row+">";
        }
	}



	
    var end_wrap_div = "</"+__psp1Row+">";
    var show_hide_button = "";//Кнопка "Показать/Скрыть"
	var tmp_cnt_to_hide = cnt_to_hide;
	if(blok == 'required'){
		tmp_cnt_to_hide = 7;
	}
    if(index >= tmp_cnt_to_hide)
    {
        //В зависимости от текущего состояния - задаем значение атрибута style:
        var row_style = "";
        if(wrap_states[wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]] == false)
        {
            row_style = "display:none;";
        }
		var foldHide = '';
		if (M.template === 'modern' && row_style === 'display:none;') {
			foldHide = ' ' + M.hidden_row_class;
			row_style = '';
		}
		
		if(Product.hidden){
			start_wrap_div = "<"+__psp1Row+" style=\""+row_style+" background:"+Product.hidden_color+";\" class=\""+M.hide_row+" "+M.prefix_hide_row_by_id+wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]+foldHide+"\">";
		}else{
			if(all_storages_info[Product.storage_id]['bg_line_color'] == 1){
    			start_wrap_div = "<"+__psp1Row+" style=\""+row_style+" background:"+color+";\" class=\""+M.hide_row+" "+M.prefix_hide_row_by_id+wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]+foldHide+"\">";
    		}else{
    			start_wrap_div = "<"+__psp1Row+" style=\""+row_style+"\" class=\""+M.hide_row+" "+M.prefix_hide_row_by_id+wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]+foldHide+"\">";
    		}
		}
    }else{
		if(index > 0){
		    if(Product.hidden){
				start_wrap_div = "<"+__psp1Row+" style='background:"+Product.hidden_color+";' class=\""+M.hide_row+"\">";
			}else{
				if(all_storages_info[Product.storage_id]['bg_line_color'] == 1){
        			start_wrap_div = "<"+__psp1Row+" style='background:"+color+";' class=\""+M.hide_row+"\">";
        		}else{
        			start_wrap_div = "<"+__psp1Row+" class=\""+M.hide_row+"\">";
        		}
			}
		}
	}
   
   
    if(index == 0 && quantity > tmp_cnt_to_hide)//Если это первый элемент, но не единственный - приделываем кнопку Показать/Скрыть. Работаем с индексным списком для таких блоков
    {
        //Добавляем ID
        if(wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok] == undefined)
        {
            wrap_blocks_index[wrap_blocks_index.length] = Product.manufacturer+Product.article+"_"+blok;//Добавляем Производитель+Артикул в индексный массив
            wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok] = wrap_blocks_index.length-1;//Добавляем Производитель+Артикул как ключ в ассоциативный массив
            wrap_states[wrap_blocks_index.length-1] = false;//По умолчанию блок закрыт
        }
        
        var show_hide_text = M.txt_show_more_rows;
        if(wrap_states[wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]] == true)
        {
            show_hide_text = M.txt_hide_rows;
        }
        
        show_hide_button = "<div class=\""+M.show_hide_button+"\" onclick=\"show_hide_block("+wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]+", false);\"><span style=\"line-height:1.4em;\" id=\""+M.prefix_show_hide_button+wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]+"\">"+show_hide_text+"</span></div>";
    }
    
    
	
	
    //Колонки Производитель, Артикул, Наименование
    if(index == 0)//Если это первый товар в блоке
    {
		manufacturer = '<span title="'+ ProductType.manufacturer +'">'+ ProductType.manufacturer +'</span>';
        article_show = "<a title='Искать: "+ ProductType.article_show +"' class=\""+M.bread_crumbs_a+"\" style=\""+M.style_article_link+"\" href=\"/shop/part_search?article="+Product.article+"\">"+ProductType.article_show+"</a>";
        name = "<span title=\""+Product.name+"\">"+ProductType.name+"</span>";
    }
    
	
	
	
	// Кнопки увеличения количества товара добавляемого в корзину //////////////////////////////////////////////////////////////////////////////////////////////
	var p_min_order = Product.min_order * 1;
	var p_exist = Product.exist * 1;
	
	if(p_min_order == 0){
		p_min_order = 1;
	}
	if(p_exist == 0){
		p_exist = 1;
	}
	
	cart_html = '<div style="'+M.style_cart_wrap+'"><table>';
		cart_html += '<tr><td><a style="'+M.style_cart_icon_link+'" href="javascript:void(0);" onclick="addToCart('+Product.aid+');"><span><i class="fa fa-shopping-cart"></i></span></a></td></tr>';
		cart_html += '<tr><td><div style="'+M.style_cart_counter_box+'"><table><tr>';
			cart_html += "<td><a style='width:12px;' onclick=\"minusCountNeed("+Product.aid+", "+p_exist+", "+p_min_order+");\" class=\""+M.count_need_minus+"\" href=\"javascript:void(0);\"><i class=\"fa fa-minus\"></i></a></td>";
			cart_html += "<td><input style=\""+M.style_cart_qty_input+"\" type=\"text\" value=\""+p_min_order+"\" onChange=\"onKeyUpCountNeed("+Product.aid+", "+p_exist+", "+p_min_order+");\" id=\""+M.prefix_count_need+Product.aid+"\" /></td>";
			cart_html += "<td><a style='width:12px;' onclick=\"plusCountNeed("+Product.aid+", "+p_exist+", "+p_min_order+");\" class=\""+M.count_need_plus+"\" href=\"javascript:void(0);\"><i class=\"fa fa-plus\"></i></a></td>";
		cart_html += '</tr></table></div></td></tr>';
	cart_html += '</table></div>';
	//////////////////////////////////////////////////////////////////////////////////////////////
	
	
	// <!-------------------------------------------- Картинки в проценке -------------------------------------------->

	let key = list_products_info.findIndex(item => item.manufacturer == Product.manufacturer && item.article == Product.article);

	if(key === -1){
		let tmp = new Object;
			tmp.ajax = false;
			tmp.json = false;
			tmp.manufacturer = Product.manufacturer;
			tmp.article = Product.article;
		key = list_products_info.push(tmp) - 1;
	}

	exist = '<table style="'+M.style_thumb_table_float+'"><tr><td><div style="'+M.style_thumb_link_wrap+'"><a class="'+M.prefix_product_img+key+'" onClick="show_modal_product_info('+key+', '+Product.aid+')" style="'+M.style_thumb_link+'" ><span style="'+M.style_thumb_span+'"></span></a></div></td><td><div style="'+M.style_thumb_exist_cell+'">'+exist+'</div></td></tr></table>';
	
	name = "<span title=\""+Product.name+"\">"+Product.name+"</span>";
	
	// <!-------------------------------------------- End Картинки в проценке ---------------------------------------->
	
	
	// Товары Б\У - Добавляем картинку товара, если она есть
	if(typeof Product.json_params == "string"){
		if(Product.json_params != ""){
			let json_params = JSON.parse(Product.json_params);
			if(json_params.used == 1){
				let images = '';
				if(json_params.images){
					for(let i=0; i < json_params.images.length; i++)
					{
						if(i==0){
							images += '<a href="'+json_params.images[i]['url']+'" rel="'+M.prefix_lightbox+Product.aid+'"><img style="'+M.style_used_thumb+'" src="'+json_params.images[i]['url']+'" /></a>';
						}else{
							images += '<a class="'+M.hidden+'" href="'+json_params.images[i]['url']+'" rel="'+M.prefix_lightbox+Product.aid+'"><img style="'+M.style_used_thumb+'" src="'+json_params.images[i]['url']+'" /></a>';
						}
					}
				}
				name += images;
			}
		}
	}
	let timeToExe = '<span onclick="openInfoWindow(null, null, 1, \''+ supply_info_json +'\');">'+ time_to_exe +'</span>';
	if(Product.delivery.length){
		timeToExe = Product.delivery;
	}
	
    return start_wrap_div + '<'+__psp1Cell+' class="'+M.td_manufacturer+'">'+ manufacturer + show_hide_button +'</'+__psp1Cell+'><'+__psp1Cell+' class="'+M.td_article+'">'+ article_show +'</'+__psp1Cell+'><'+__psp1Cell+' class="'+M.td_name+'">'+ name +'</'+__psp1Cell+'><'+__psp1Cell+' class="'+M.td_exist+'">'+ exist +'</'+__psp1Cell+'><'+__psp1Cell+' class="'+M.td_time_to_exe+'">'+timeToExe+'</'+__psp1Cell+'><'+__psp1Cell+' class="'+M.td_info+'">'+ info +'</'+__psp1Cell+'><'+__psp1Cell+' class="'+M.td_price+'">'+ price +'</'+__psp1Cell+'><'+__psp1Cell+' class="'+M.td_add_to_cart+'">'+ cart_html +'</'+__psp1Cell+'>'+ '<'+__psp1Cell+' class="'+M.td_color+'" style="background:'+color+'"></'+__psp1Cell+'>' + end_wrap_div;
}


