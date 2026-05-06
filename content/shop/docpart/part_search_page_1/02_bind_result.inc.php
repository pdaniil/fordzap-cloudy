function bindBunchResult(answer)
{
	//Полученный результат распределяем по структуре результата
	var result_length = answer.Products.length;
    for(var i=0; i < result_length; i++)
    {
        //Если склад находится в списке складов для проверки брендов и товаров, тогда добавляем наименование бренда и артикул в список разрешенных
		if(my_storages_id_for_checking_analogues.indexOf(answer.Products[i]["storage_id"]) !== -1)
		{
			if(arr_allowed_products.indexOf(answer.Products[i]["manufacturer"] +'_'+ answer.Products[i]["article"]) === -1){
				arr_allowed_products.push(answer.Products[i]["manufacturer"] +'_'+ answer.Products[i]["article"]);
			}
		}
		else
		{
			if(arr_allowed_products.length > 0 && arr_allowed_products.indexOf(answer.Products[i]["manufacturer"] +'_'+ answer.Products[i]["article"]) === -1 && ((answer.Products[i]["article"] != search_object.article) || (answer.Products[i]["article"] == search_object.article && (answer.Products[i]["manufacturer"] != SelectedManufacturer && SelectedManufacturer != null))) )
			{
				<?php
				if(DP_User::isAdmin()){
				?>
				answer.Products[i]["hidden"] = 1;//Помечаем этот товар как заблокированный
				answer.Products[i]["hidden_color"] = bg_color_checking_analogues;
				<?php
				}else{
				?>
				continue;//Пропускаем этот товар, его нет в списке разрешенных
				<?php
				}
				?>
			}
		}
					
        // Преобразование типов
		answer.Products[i]["exist"] = answer.Products[i]["exist"] * 1;
		answer.Products[i]["time_to_exe"] = answer.Products[i]["time_to_exe"] * 1;
		answer.Products[i]["price"] = answer.Products[i]["price"] * 1;
		answer.Products[i]["storage_id"] = answer.Products[i]["storage_id"] * 1;
		
		var manufacturer 			= String(answer.Products[i]["manufacturer"]);
		manufacturer = $('<textarea />').html(manufacturer).text();
		
        var article 				= String(answer.Products[i]["article"]);
        var name 					= String(answer.Products[i]["name"]);
        var article_show 			= String(answer.Products[i]["article_show"]);
        var exist 					= answer.Products[i]["exist"];
        var time_to_exe 			= answer.Products[i]["time_to_exe"];
		var price 					= answer.Products[i]["price"];
		var storage 				= answer.Products[i]["storage_id"] * 1;
		var storage_color 			= answer.Products[i]["color"];
		
		var search_name 			= answer.Products[i]["search_name"] * 1;// Флаг товара найденного по наименованию
		
		// Список найденных брендов
		if(arr_manufacturers.indexOf(manufacturer) === -1)
		{
			arr_manufacturers.push(manufacturer);
		}
		
		// Список найденных складов
		if(arr_storages.indexOf(storage) === -1)
		{
			arr_storages.push(storage);
			// Список фона складов
			arr_storages_color[storage] = storage_color;
		}
		
        //Добавляем объект в список всех объектов (AID)
        var AID_Object = new Object;//Учетный объект данного объекта товара, который будет добавлен в список Products.All
        AID_Object.aid = Products.All.length;//AID данного объекта товара
        answer.Products[i].aid = Products.All.length;//AID данного объекта товара
        
		//Определяем Б\У
		let by_flag = false;
		if(typeof(answer.Products[i].json_params) == 'string'){
			if(answer.Products[i].json_params){
				let json_params = JSON.parse(answer.Products[i].json_params);
				if(json_params.used == 1){
					by_flag = true;
				}
			}
		}
		const allowedStorageIds = [1, 23, 28];
		if(allowedStorageIds.includes(answer.Products[i].storage_id)){
			//Если такого производителя еще не было - создаем для него ячейку
			if(Products.Spare_Box.Products.Manufacturers[manufacturer] == undefined)
			{
				Products.Spare_Box.Products.Manufacturers[manufacturer] = new Object;
			}
			//Если такого артикула еще не было у данного производителя
			if(Products.Spare_Box.Products.Manufacturers[manufacturer][article] == undefined)
			{
				Products.Spare_Box.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
				
				//Создаем новый тип товара и добавляем его в список типов
				var ProductType = new Object;
				ProductType.manufacturer = manufacturer;
				ProductType.article = article;
				ProductType.name = name;
				ProductType.article_show = article_show;
				ProductType.exist = exist;
				ProductType.time_to_exe = time_to_exe;
				ProductType.price = price;
				ProductType.storage = storage;
				
				Products.Spare_Box.ProductsTypes.push(ProductType);
			}
			
			//Добавляем сам объект товара
			Products.Spare_Box.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
			
			//Для учетного объекта - указываем, что товар находится в списке аналогов
			AID_Object.isRequired 				= false;
			AID_Object.isSearchName 			= false;						
			AID_Object.isQuickAnalogs 			= false;
			AID_Object.isAnalogs 				= false;
			AID_Object.isPossibleReplacement 	= false;
			AID_Object.isSpare_Box	 			= true;
		}
		else
		{
			// Если товар найден по наименованию
			if(search_name === 1){
				
				//Если такого производителя еще не было - создаем для него ячейку
				if(Products.SearchName.Products.Manufacturers[manufacturer] == undefined)
				{
					Products.SearchName.Products.Manufacturers[manufacturer] = new Object;
				}
				//Если такого артикула еще не было у данного производителя
				if(Products.SearchName.Products.Manufacturers[manufacturer][article] == undefined)
				{
					Products.SearchName.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
					
					//Создаем новый тип товара и добавляем его в список типов
					var ProductType = new Object;
					ProductType.manufacturer = manufacturer;
					ProductType.article = article;
					ProductType.name = name;
					ProductType.article_show = article_show;
					ProductType.exist = exist;
					ProductType.time_to_exe = time_to_exe;
					ProductType.price = price;
					ProductType.storage = storage;
					
					Products.SearchName.ProductsTypes.push(ProductType);
				}
				
				//Добавляем сам объект товара
				Products.SearchName.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
				
				//Для учетного объекта - указываем, что товар находится в списке аналогов
				AID_Object.isRequired 				= false;
				AID_Object.isSearchName 			= true;
				AID_Object.isQuickAnalogs 			= false;
				AID_Object.isAnalogs 				= false;
				AID_Object.isPossibleReplacement 	= false;
				AID_Object.isSpare_Box			 	= false;
				
				
			}else{
				
				
				//Продукт считает Запрошенным, если совпал Артикул
				if( article == search_object.article && (manufacturer == SelectedManufacturer || SelectedManufacturer == null) )
				{
					//Если такого производителя еще не было - создаем для него ячейку
					if(Products.Required.Products.Manufacturers[manufacturer] == undefined)
					{
						Products.Required.Products.Manufacturers[manufacturer] = new Object;
						Products.Required.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для самих объектов товаров
						
						//Создаем новый тип товара и добавляем его в список типов
						var ProductType = new Object;
						ProductType.manufacturer = manufacturer;
						ProductType.article = article;
						ProductType.name = name;
						ProductType.article_show = article_show;
						ProductType.exist = exist;
						ProductType.time_to_exe = time_to_exe;
						ProductType.price = price;
						ProductType.storage = storage;
						
						Products.Required.ProductsTypes.push(ProductType);
					}
					
					//Добавляем сам объект товара в ячейку
					Products.Required.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
					
					//Для учетного объекта - указываем, что товар находится в списке запрошенных
					AID_Object.isRequired 				= true;//Флаг - Запрошенный
					AID_Object.isSearchName 			= false;
					AID_Object.isQuickAnalogs 			= false;
					AID_Object.isAnalogs 				= false;
					AID_Object.isPossibleReplacement 	= false;
					AID_Object.isSpare_Box			 	= false;
					
										
					// КАРТОЧКИ ПОЗИЦИЙ -----------------------------------
				
					// самая низкая цена оригинал
					if(min_price_original_block.price === undefined ){
						min_price_original_block = answer.Products[i];
					}else{
						if( parseFloat(answer.Products[i]["price"]) < parseFloat(min_price_original_block.price) ){
							min_price_original_block = answer.Products[i];
						}else{
							if( parseFloat(answer.Products[i]["price"]) == parseFloat(min_price_original_block.price) ){
								if( parseInt(answer.Products[i]["time_to_exe"]) < parseInt(min_price_original_block.time_to_exe) ){
									min_price_original_block = answer.Products[i];
								}
							}
						}
					}
					
					// наименьший срок доставки
					if(min_time_to_exe_block.time_to_exe === undefined ){
						min_time_to_exe_block = answer.Products[i];
					}else{
						if( parseInt(answer.Products[i]["time_to_exe"]) < parseInt(min_time_to_exe_block.time_to_exe) ){
							min_time_to_exe_block = answer.Products[i];
						}else{
							if( parseInt(answer.Products[i]["time_to_exe"]) == parseInt(min_time_to_exe_block.time_to_exe) ){
							    if(my_storages_id.indexOf(parseInt(min_time_to_exe_block.storage_id)) == -1 && my_storages_id.indexOf(parseInt(answer.Products[i]["storage_id"])) != -1)
							    {
							        min_time_to_exe_block = answer.Products[i];
							    }
							    else if(my_storages_id.indexOf(parseInt(min_time_to_exe_block.storage_id)) != -1 && my_storages_id.indexOf(parseInt(answer.Products[i]["storage_id"])) == -1)
							    {
							        min_time_to_exe_block = min_time_to_exe_block;
							    }
							    else if( parseFloat(answer.Products[i]["price"]) < parseFloat(min_time_to_exe_block.price) )
							    {
									min_time_to_exe_block = answer.Products[i];
								}
							}
						}
					}
				    // КАРТОЧКИ ПОЗИЦИЙ END -------------------------------
				
				}
				else//Товар распределяем в Аналоги
				{
					if(article == search_object.article){
						// Возможные замены
						
						//Если такого производителя еще не было - создаем для него ячейку
							if(Products.PossibleReplacement.Products.Manufacturers[manufacturer] == undefined)
							{
								Products.PossibleReplacement.Products.Manufacturers[manufacturer] = new Object;
							}
							//Если такого артикула еще не было у данного производителя
							if(Products.PossibleReplacement.Products.Manufacturers[manufacturer][article] == undefined)
							{
								Products.PossibleReplacement.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
								
								//Создаем новый тип товара и добавляем его в список типов
								var ProductType = new Object;
								ProductType.manufacturer = manufacturer;
								ProductType.article = article;
								ProductType.name = name;
								ProductType.article_show = article_show;
								ProductType.exist = exist;
								ProductType.time_to_exe = time_to_exe;
								ProductType.price = price;
								ProductType.storage = storage;
								
								Products.PossibleReplacement.ProductsTypes.push(ProductType);
							}
							
							//Добавляем сам объект товара
							Products.PossibleReplacement.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
							
							//Для учетного объекта - указываем, что товар находится в списке аналогов
							AID_Object.isRequired 				= false;
							AID_Object.isSearchName 			= false;
							AID_Object.isQuickAnalogs 			= false;
							AID_Object.isAnalogs 				= false;
							AID_Object.isPossibleReplacement 	= true;
							AID_Object.isSpare_Box	 			= false;
							
					}else{
						// Аналоги делем на 2 категории:
						// - Те что со сроком 0-2 дня
						// - Остальные
						
						if(time_to_exe <= group_day){
							// быстрые аналоги
							//Если такого производителя еще не было - создаем для него ячейку
							if(Products.Quick_Analogs.Products.Manufacturers[manufacturer] == undefined)
							{
								Products.Quick_Analogs.Products.Manufacturers[manufacturer] = new Object;
							}
							//Если такого артикула еще не было у данного производителя
							if(Products.Quick_Analogs.Products.Manufacturers[manufacturer][article] == undefined)
							{
								Products.Quick_Analogs.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
								
								//Создаем новый тип товара и добавляем его в список типов
								var ProductType = new Object;
								ProductType.manufacturer = manufacturer;
								ProductType.article = article;
								ProductType.name = name;
								ProductType.article_show = article_show;
								ProductType.exist = exist;
								ProductType.time_to_exe = time_to_exe;
								ProductType.price = price;
								ProductType.storage = storage;
								
								Products.Quick_Analogs.ProductsTypes.push(ProductType);
							}
							
							//Добавляем сам объект товара
							Products.Quick_Analogs.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
							
							//Для учетного объекта - указываем, что товар находится в списке аналогов
							AID_Object.isRequired 				= false;
							AID_Object.isSearchName 			= false;
							AID_Object.isQuickAnalogs 			= true;
							AID_Object.isAnalogs 				= false;
							AID_Object.isPossibleReplacement 	= false;
							AID_Object.isSpare_Box	 			= false;
							
						}else{
							
							// Остальные аналоги
							//Если такого производителя еще не было - создаем для него ячейку
							if(Products.Analogs.Products.Manufacturers[manufacturer] == undefined)
							{
								Products.Analogs.Products.Manufacturers[manufacturer] = new Object;
							}
							//Если такого артикула еще не было у данного производителя
							if(Products.Analogs.Products.Manufacturers[manufacturer][article] == undefined)
							{
								Products.Analogs.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
								
								//Создаем новый тип товара и добавляем его в список типов
								var ProductType = new Object;
								ProductType.manufacturer = manufacturer;
								ProductType.article = article;
								ProductType.name = name;
								ProductType.article_show = article_show;
								ProductType.exist = exist;
								ProductType.time_to_exe = time_to_exe;
								ProductType.price = price;
								ProductType.storage = storage;
								
								Products.Analogs.ProductsTypes.push(ProductType);
							}
							
							//Добавляем сам объект товара
							Products.Analogs.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
							
							//Для учетного объекта - указываем, что товар находится в списке аналогов
							AID_Object.isRequired 				= false;
							AID_Object.isSearchName 			= false;						
							AID_Object.isQuickAnalogs 			= false;
							AID_Object.isAnalogs 				= true;
							AID_Object.isPossibleReplacement 	= false;
							AID_Object.isSpare_Box	 			= false;
						}
					// КАРТОЧКИ ПОЗИЦИЙ -----------------------------------
				
					// самая низкая цена аналог
					if(min_price_analog_block.price === undefined ){
						min_price_analog_block = answer.Products[i];
					}else{
						if( parseFloat(answer.Products[i]["price"]) < parseFloat(min_price_analog_block.price) ){
							min_price_analog_block = answer.Products[i];
						}else{
							if( parseFloat(answer.Products[i]["price"]) == parseFloat(min_price_analog_block.price) ){
								if( parseInt(answer.Products[i]["time_to_exe"]) < parseInt(min_price_analog_block.time_to_exe) ){
									min_price_analog_block = answer.Products[i];
								}
							}
						}
					}
				
				    // КАРТОЧКИ ПОЗИЦИЙ END -------------------------------
					}
				}
				
			}
		}
		
		// мин. цена бренда
		if(arr_manufacturers_prices[manufacturer] == undefined){
			arr_manufacturers_prices[manufacturer] = new Object;
			arr_manufacturers_prices[manufacturer]['price'] = price;
			arr_manufacturers_prices[manufacturer]['aid'] = answer.Products[i].aid;
		}else{
			if(arr_manufacturers_prices[manufacturer]['price'] > price){
				arr_manufacturers_prices[manufacturer]['price'] = price;
				arr_manufacturers_prices[manufacturer]['aid'] = answer.Products[i].aid;
			}
		}
		
        //Добавляем учетный объект в список учетных объектов
        Products.All.push(AID_Object);
        
    }//for(i)
		
	// После того как мы опросили данного поставщика и получили текущий список найденных складов и производителей, нужно обновить фильтр складов и брендов
	
	// Бренды - оригинальный вариант
	/*filter['manufacturer_blok'].list_options = new Array;
	arr_manufacturers.sort(sortFunction);// Сортировка
	for(var i = 0; i < arr_manufacturers.length; i++)
	{
		filter['manufacturer_blok'].list_options[i] = new Object;
		filter['manufacturer_blok'].list_options[i].id = i;
		filter['manufacturer_blok'].list_options[i].value = false;
		filter['manufacturer_blok'].list_options[i].text = arr_manufacturers[i];
		filter['manufacturer_blok'].list_options[i].search = arr_manufacturers[i];
	}*/
	
	// Преобразуем arr_manufacturers в массив объектов с ценами
	var manufacturers_sorted_by_price = [];

	// Сначала создаем массив всех производителей с их данными
	for(var i = 0; i < arr_manufacturers.length; i++){
		var manufacturer = arr_manufacturers[i];
		if(arr_manufacturers_prices[manufacturer]){
			manufacturers_sorted_by_price.push({
				name: manufacturer,
				price: arr_manufacturers_prices[manufacturer]['price'],
				aid: arr_manufacturers_prices[manufacturer]['aid'],
				original_id: i // Сохраняем оригинальный ID из arr_manufacturers
			});
		} else {
			// Для производителей без цены (если такие есть)
			manufacturers_sorted_by_price.push({
				name: manufacturer,
				price: Infinity, // или очень большое число
				aid: null,
				original_id: i
			});
		}
	}

	// Сортировка по цене
	manufacturers_sorted_by_price.sort(function(a, b) {
		return a.price - b.price; // по возрастанию цены
		// return b.price - a.price; // по убыванию цены
	});

	// Создание фильтра из отсортированного массива
	filter['manufacturer_blok'].list_options = new Array;
	for(var i = 0; i < manufacturers_sorted_by_price.length; i++)
	{
		filter['manufacturer_blok'].list_options[i] = new Object;
		
		// ВАЖНО: Используем original_id, а не текущий индекс i
		filter['manufacturer_blok'].list_options[i].id = manufacturers_sorted_by_price[i].original_id;
		
		filter['manufacturer_blok'].list_options[i].value = false;
		filter['manufacturer_blok'].list_options[i].text = manufacturers_sorted_by_price[i].name;
		filter['manufacturer_blok'].list_options[i].search = manufacturers_sorted_by_price[i].name;
		
		// Дополнительные данные (опционально)
		filter['manufacturer_blok'].list_options[i].price = manufacturers_sorted_by_price[i].price;
		filter['manufacturer_blok'].list_options[i].original_index = manufacturers_sorted_by_price[i].original_id;
	}
	
	// Склады
	filter['storages_blok'].list_options = new Array;
	arr_storages.sort(sortFunction);// Сортировка
	for(var i = 0; i < arr_storages.length; i++)
	{
		filter['storages_blok'].list_options[i] = new Object;
		filter['storages_blok'].list_options[i].id = i;
		filter['storages_blok'].list_options[i].value = false;
		
		var table = "<table><tr><td><div style=\"width: 30px; height: 10px; border:1px solid #eee;  display: inline-block; margin-right: 5px; margin-left: 3px; background:"+ arr_storages_color[arr_storages[i]] +";\"></div></td><td>"+ all_storages[arr_storages[i]] +"</td></tr></table>";
		
		filter['storages_blok'].list_options[i].text = table;
		filter['storages_blok'].list_options[i].search = String(arr_storages[i]);
	}

}




// ------------------------------------------------------------------------------------------------------------------------------




