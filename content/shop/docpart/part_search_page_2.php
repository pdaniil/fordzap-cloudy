<?php
/**
 * Скрипт для страницы поиска автозапчастей по артикулу, вариант "Сквозная сортировка"
*/
defined('_ASTEXE_') or die('No access');
?>



<script>
//АЛГОРИТМ ПОИСКА ЗАПЧАСТЕЙ ПО АРТИКУЛУ
var search_object = new Object;//Объект запроса

//Первым делом указываем артикул и производителя:
search_object.article = "<?php echo $article; ?>";
search_object.searsch_str = "<?php echo $searsch_str; ?>";
search_object.manufacturers = null;//Задаем значение null

var group_day = 2;// Аналоги 0 - 2 дня

//ОБЪЕКТ ОПИСАНИЯ ТОВАРОВ ПРИНЯТЫХ ОТ СЕРВЕРА
var Products = new Object;
//Запрошеннын товары
Products.Required = new Array();//Объект с запрошенными товарами
//Найденные по наименованию
Products.SearchName = new Array();
//Аналоги с быстрой доставкой
Products.Quick_Analogs = new Array();
//Аналоги
Products.Analogs = new Array();
//Возможные замены
Products.PossibleReplacement = new Array();
//Дополнительный свободный блок для доработок
Products.Spare_Box = new Array();

//Индексный список для поиска нужного объекта по его клиентскому ID (AID - All ID). Т.е. каждый объект товара при примеме от сервера получает ID в рамках данной страницы.
//Этот список предназначен для получения объекта товара по его AID:
Products.All = new Array();//Список объектов 
</script>



<script>
// -------------------------------------------------------------------------------------------------------------------------------
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
	resultSort();//Сортировка
	resultReview();//Обновляем отображние результата
}
// -------------------------------------------------------------------------------------------------------------------------------
//Метод соединения полученного от сервера результата по одной связке с общим объектом описания найденных товаров
function bindBunchResult(answer)
{
	for(var i=0; i < answer.Products.length; i++)
	{
	    //Если склад находится в списке складов для проверки брендов и товаров, тогда добавляем наименование бренда и артикул в список разрешенных
		if(my_storages_id_for_checking_analogues.indexOf(answer.Products[i]["storage_id"]) !== -1 )
		{
			if(arr_allowed_products.indexOf(answer.Products[i]["manufacturer"] +'_'+ answer.Products[i]["article"]) === -1){
				arr_allowed_products.push(answer.Products[i]["manufacturer"] +'_'+ answer.Products[i]["article"]);
			}
		}
		else
		{
			if(arr_allowed_products.length > 0 && arr_allowed_products.indexOf(answer.Products[i]["manufacturer"] +'_'+ answer.Products[i]["article"]) === -1)
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
        
        var exist 					= answer.Products[i]["exist"];
        var time_to_exe 			= answer.Products[i]["time_to_exe"];
		var price 					= answer.Products[i]["price"];
		var storage 				= answer.Products[i]["storage_id"] * 1;
		var storage_color 			= answer.Products[i]["color"];
		
		var search_name 			= answer.Products[i]["search_name"] * 1;
		
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
		
		
		if(by_flag == true){
			// Дополнительный свободный блок
			Products.Spare_Box.push(answer.Products[i]);
				
			//Для учетного объекта - указываем, что товар находится в списке аналогов
			AID_Object.isRequired 				= false;				
			AID_Object.isQuickAnalogs 			= false;
			AID_Object.isAnalogs 				= false;
			AID_Object.isPossibleReplacement 	= false;
			AID_Object.isSpare_Box			 	= true;
		}
		else
		{
			if(search_name === 1){
				
				Products.SearchName.push(answer.Products[i]);
				//Для учетного объекта - указываем, что товар находится в списке найденных по наименованию
				AID_Object.isSearchName 			= true;//Флаг - найденный по наименованию
				AID_Object.isRequired  				= false;
				AID_Object.isQuickAnalogs 			= false;
				AID_Object.isAnalogs 				= false;
				AID_Object.isPossibleReplacement	= false;
				AID_Object.isSpare_Box				= false;
			
			}else{
				//Продукт считает Запрошенным, если совпал Артикул и Производитель (если мы его учитываем)
				if( article == search_object.article && (manufacturer == SelectedManufacturer || SelectedManufacturer == null) )
				{
					Products.Required.push(answer.Products[i]);

					//Для учетного объекта - указываем, что товар находится в списке запрошенных
					AID_Object.isRequired  				= true;//Флаг - Запрошенный
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
						}
					}
					
					// наименьший срок доставки
					if(min_time_to_exe_block.time_to_exe === undefined ){
						min_time_to_exe_block = answer.Products[i];
					}else{
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
				
				    // КАРТОЧКИ ПОЗИЦИЙ END -------------------------------
				}
				else//Товар распределяем в Аналоги и делем на 2 категории:
				{
					if(article == search_object.article){
						// Возможные замены
						Products.PossibleReplacement.push(answer.Products[i]);
							
						//Для учетного объекта - указываем, что товар находится в списке аналогов
						AID_Object.isRequired 				= false;				
						AID_Object.isQuickAnalogs 			= false;
						AID_Object.isAnalogs 				= false;
						AID_Object.isPossibleReplacement 	= true;
						AID_Object.isSpare_Box			 	= false;
					}else{
						if(time_to_exe <= group_day){
							// быстрые аналоги
							Products.Quick_Analogs.push(answer.Products[i]);
							
							//Для учетного объекта - указываем, что товар находится в списке аналогов
							AID_Object.isRequired 				= false;
							AID_Object.isQuickAnalogs 			= true;
							AID_Object.isAnalogs 				= false;
							AID_Object.isPossibleReplacement 	= false;
							AID_Object.isSpare_Box			 	= false;
						}else{
							// Остальные аналоги
							//Добавляем сам объект товара
							Products.Analogs.push(answer.Products[i]);
							
							//Для учетного объекта - указываем, что товар находится в списке аналогов
							AID_Object.isRequired 				= false;				
							AID_Object.isQuickAnalogs 			= false;
							AID_Object.isAnalogs 				= true;
							AID_Object.isPossibleReplacement 	= false;
							AID_Object.isSpare_Box			 	= false;
						}
						// КАРТОЧКИ ПОЗИЦИЙ -----------------------------------
				
    					// самая низкая цена аналог
    					if(min_price_analog_block.price === undefined ){
    						min_price_analog_block = answer.Products[i];
    					}else{
    						if( parseFloat(answer.Products[i]["price"]) < parseFloat(min_price_analog_block.price) ){
    							min_price_analog_block = answer.Products[i];
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
		
	}
		
		
	// После того как мы опросили данного поставщика и получили текущий список найденных складов и производителей, нужно обновить фильтр складов и брендов
	
	// Бренды
	filter['manufacturer_blok'].list_options = new Array;
	arr_manufacturers.sort(sortFunction);// Сортировка
	for(var i = 0; i < arr_manufacturers.length; i++)
	{
		filter['manufacturer_blok'].list_options[i] = new Object;
		filter['manufacturer_blok'].list_options[i].id = i;
		filter['manufacturer_blok'].list_options[i].value = false;
		filter['manufacturer_blok'].list_options[i].text = arr_manufacturers[i];
		filter['manufacturer_blok'].list_options[i].search = arr_manufacturers[i];
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
// ----------------------------------------------------------------------------------------------------------------------------------
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
    //Ставим обозначение текущей внутренней сортровки
    headlines[sortState.field].caption += " <img src=\"/content/files/images/"+sortState.asc_desc+".png\" />";
    headlines[sortState.field].subclass += " sorted";
    
	
    var head_block = '<tr><th class="th_manufacturer'+headlines.manufacturer.subclass+'" onclick="sortChange(\'manufacturer\');">'+headlines.manufacturer.caption+'</th><th class="th_article'+headlines.article.subclass+'" onclick="sortChange(\'article\');">'+headlines.article.caption+'</th><th class="th_name'+headlines.name.subclass+'" onclick="sortChange(\'name\');">'+headlines.name.caption+'</th><th class="th_exist'+headlines.exist.subclass+'" onclick="sortChange(\'exist\');">'+headlines.exist.caption+'</th><th class="th_time_to_exe'+headlines.time_to_exe.subclass+'" onclick="sortChange(\'time_to_exe\');">'+headlines.time_to_exe.caption+'</th><th class="th_info">Инфо</th><th class="th_price'+headlines.price.subclass+'" onclick="sortChange(\'price\');">'+headlines.price.caption+'</th><th class="th_add_to_cart"></th><th class="th_color"></th></tr>';
    

    //HTML для запрошенного артикула
    var required_block = "";
    var SearchName_block = "";
	//HTML для быстрых аналогов
	var quick_analogs_block = "";
    //HTML для аналогов
    var analogs_block = "";
    var PossibleReplacement_block = "";
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
	
	
	
	var Required = Products.Required;
	var SearchName = Products.SearchName;
	var Quick_Analogs = Products.Quick_Analogs;
	var Analogs = Products.Analogs;
	var PossibleReplacement = Products.PossibleReplacement;
	var Spare_Box = Products.Spare_Box;
	
	
	// Фильтрация позиций
	if(this_filter != ''){Required = filtering_items(Required);}
	// Если установлены флаги самых быстрых и дешевых позиций в группе
	if(sam_price_time != ''){Required = sam_price_time_fanc(Required);}
	
	
	if(Required.length > 0){
		
		// Общий массив позиций, по нему формируются новые значения фильтра
		ALL_ProductsObjects.push(Required);
   
		// Формируем html позиций блока
		for(var p=0; p < Required.length; p++){
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Required_count++;
			if( (cnt_on_page + start_page_Required) <= Products_Required_Show_count ){continue;}
			Products_Required_Show_count++;
			
			required_block += getProductRecordHTML(Required[p]);
		}
	}
	
	
	
	
	
	// Фильтрация позиций
	if(this_filter != ''){SearchName = filtering_items(SearchName);}
	// Если установлены флаги самых быстрых и дешевых позиций в группе
	if(sam_price_time != ''){SearchName = sam_price_time_fanc(SearchName);}
	
	
	if(SearchName.length > 0){
		
		// Общий массив позиций, по нему формируются новые значения фильтра
		ALL_ProductsObjects.push(SearchName);
   
		// Формируем html позиций блока
		for(var p=0; p < SearchName.length; p++){
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_SearchName_count++;
			if( (cnt_on_page + start_page_SearchName) <= Products_SearchName_Show_count ){continue;}
			Products_SearchName_Show_count++;
			
			SearchName_block += getProductRecordHTML(SearchName[p]);
		}
	}
	
	
	
	
	
	
	
	// Фильтрация позиций
	if(this_filter !== ''){Quick_Analogs = filtering_items(Quick_Analogs);}
	// Если установлены флаги самых быстрых и дешевых позиций в группе
	if(sam_price_time != ''){Quick_Analogs = sam_price_time_fanc(Quick_Analogs);}
	
	if(Quick_Analogs.length > 0){
		
		// Общий массив позиций, по нему формируются новые значения фильтра
		ALL_ProductsObjects.push(Quick_Analogs);
   
		// Формируем html позиций блока
		for(var p=0; p < Quick_Analogs.length; p++){
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Quick_Analogs_count++;
			if( (cnt_on_page + start_page_Quick_Analogs) <= Products_Quick_Analogs_Show_count ){continue;}
			Products_Quick_Analogs_Show_count++;
			
			quick_analogs_block += getProductRecordHTML(Quick_Analogs[p]);
		}
	}
	
	
	
	
	
	// Фильтрация позиций
	if(this_filter !== ''){Analogs = filtering_items(Analogs);}
	// Если установлены флаги самых быстрых и дешевых позиций в группе
	if(sam_price_time != ''){Analogs = sam_price_time_fanc(Analogs);}
	
	if(Analogs.length > 0){
		
		// Общий массив позиций, по нему формируются новые значения фильтра
		ALL_ProductsObjects.push(Analogs);
   
		// Формируем html позиций блока
		for(var p=0; p < Analogs.length; p++){
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Analogs_count++;
			if( (cnt_on_page + start_page_Analogs) <= Products_Analogs_Show_count ){continue;}
			Products_Analogs_Show_count++;
			
			analogs_block += getProductRecordHTML(Analogs[p]);
		}
	}
	
	
	
	
	// Фильтрация позиций PossibleReplacement
	if(this_filter !== ''){PossibleReplacement = filtering_items(PossibleReplacement);}
	// Если установлены флаги самых быстрых и дешевых позиций в группе
	if(sam_price_time != ''){PossibleReplacement = sam_price_time_fanc(PossibleReplacement);}
	
	if(PossibleReplacement.length > 0){
		
		// Общий массив позиций, по нему формируются новые значения фильтра
		ALL_ProductsObjects.push(PossibleReplacement);
   
		// Формируем html позиций блока
		for(var p=0; p < PossibleReplacement.length; p++){
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_PossibleReplacement_count++;
			if( (cnt_on_page + start_page_PossibleReplacement) <= Products_PossibleReplacement_Show_count ){continue;}
			Products_PossibleReplacement_Show_count++;
			
			PossibleReplacement_block += getProductRecordHTML(PossibleReplacement[p]);
		}
	}
	
	
	
	
	// Фильтрация позиций Spare_Box
	if(this_filter !== ''){Spare_Box = filtering_items(Spare_Box);}
	// Если установлены флаги самых быстрых и дешевых позиций в группе
	if(sam_price_time != ''){Spare_Box = sam_price_time_fanc(Spare_Box);}
	
	if(Spare_Box.length > 0){
		
		// Общий массив позиций, по нему формируются новые значения фильтра
		ALL_ProductsObjects.push(Spare_Box);
   
		// Формируем html позиций блока
		for(var p=0; p < Spare_Box.length; p++){
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Spare_Box_count++;
			if( (cnt_on_page + start_page_Spare_Box) <= Products_Spare_Box_Show_count ){continue;}
			Products_Spare_Box_Show_count++;
			
			Spare_Box_block += getProductRecordHTML(Spare_Box[p]);
		}
	}
	
	
	
	
	
	
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
		required_block = '<tr><td colspan="9" class="products_table_block_caption">Запрошенный артикул</td></tr>' + required_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'Required\');" id="next_page_Required"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}else{
		required_block = '<tr><td colspan="9" class="products_table_block_caption">Запрошенный артикул не найден</td></tr>';
	}
	
	if(quick_analogs_block != "")
	{
		quick_analogs_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Аналоги с быстрой доставкой</td></tr>' + quick_analogs_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'Quick_Analogs\');" id="next_page_Quick_Analogs"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	if(analogs_block != ''){
		analogs_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Аналоги и заменители</td></tr>' + analogs_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'Analogs\');" id="next_page_Analogs"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	if(SearchName_block != ''){
		SearchName_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Товары найденные по наименованию</td></tr>' + SearchName_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'SearchName\');" id="next_page_SearchName"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	if(PossibleReplacement_block != ''){
		PossibleReplacement_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Возможные замены</td></tr>' + PossibleReplacement_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'PossibleReplacement\');" id="next_page_PossibleReplacement"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	if(Spare_Box_block != ''){
		Spare_Box_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Товары Б\\У</td></tr>' + Spare_Box_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'Spare_Box\');" id="next_page_Spare_Box"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	// Оюъединяем блоки позиций
	products_html = required_block + PossibleReplacement_block + quick_analogs_block + analogs_block + SearchName_block + Spare_Box_block;
	
	if(products_html != ''){
		
		let for_percentage = '';
		<?php
		if((int)$group_info['for_percentage'] === 1){
		?>
		let show_data_checked = '';
		if(flag_show_data){
			show_data_checked = 'checked';
		}
		for_percentage += '<div class="div_show_data_checked" style="text-align: right; position: relative;"><input '+show_data_checked+' style="margin: 0; padding: 0; margin-right: 0px; height: 20px; width: 20px; position: absolute; top: 1px; cursor: pointer; right: 0px;" type="checkbox" id="show_data" onChange="show_data();"/></div><div class="show_data_class" style="text-align: right; padding-right: 25px;"><select id="select_the_groups_margin" onChange="changing_the_groups_margin();">';
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
		
		products_html = for_percentage + '<table id="all_table_products"><thead>' + head_block + '</thead><tbody>' + products_html + '</tbody></table>';
	}
	
	
	
	// Если после фильтрации все же не осталось позиций
	if(this_filter != '' && products_html == ''){
		products_html = '<div>Товары не найдены</div>';
	}
    
	// Отображаем проценку
    document.getElementById("products_area").innerHTML = products_html;
	
	// Отображаем фильтр
	showPropertiesWidgets();
	
	// Работаем с кнопками "показать еще" вконце блоков
	var next_page_Required = document.getElementById('next_page_Required');
	var next_page_SearchName = document.getElementById('next_page_SearchName');
	var next_page_Quick_Analogs = document.getElementById('next_page_Quick_Analogs');
	var next_page_Analogs = document.getElementById('next_page_Analogs');
	var next_page_PossibleReplacement = document.getElementById('next_page_PossibleReplacement');
	var next_page_Spare_Box = document.getElementById('next_page_Spare_Box');
	
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
	document.getElementById("select_the_groups_margin").value = this_group_id_margin;
	show_data();
	<?php
	}
	?>
	// КАРТОЧКИ ПОЗИЦИЙ -----------------------------------
			
	// Отображаем карточки после отображения проценки
	set_html_cart_block();
		
	// КАРТОЧКИ ПОЗИЦИЙ END -------------------------------
}
// -------------------------------------------------------------------------------------------------------------------------------
//Единая функция формирования HTML-кода для одной записи товара. Эта функция работает для запрошенных товаров, так и для аналогов
function getProductRecordHTML(Product)
{
    var manufacturer = "", article_show = "", name = "";
            	
	var time_to_exe = Product.time_to_exe;
    if(Product.time_to_exe != Product.time_to_exe_guaranteed)
    {
        time_to_exe = Product.time_to_exe + "-" + Product.time_to_exe_guaranteed +" дн.";
    }else{
        if(time_to_exe == 0)
        {
            if(all_storages_short_text[Product.storage_id]){
                time_to_exe = "<div title=\""+ all_storages_short_text[Product.storage_id] +"\" style=\"background: #fff700; padding: 0px 5px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;\">"+ all_storages_short_text[Product.storage_id] +"</div>";
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
	price += "<div class='show_data_class' style='font-size: 10px; color: #959393; position: relative; top: 1px;'>"+ price_purchase +"</div>";
	<?php
	}
	?>
	
	
	
    //ФОРМИРОВАНИЕ КОЛОНКИ ИНФО
    var info = '';
	
    info += "<a title=\"Фотографии товара\" href=\"https://www.google.ru/search?q="+encodeURIComponent(Product.manufacturer)+"+"+Product.article+"&newwindow=1&biw=1366&bih=667&tbm=isch&tbo=u&source=univ&sa=X&ved=0CC8QsARqFQoTCMDCoO70jMkCFQGFLAodrT0GFw\" target=\"_blank\"><span><i style=\"font-size: .8em;\" class=\"fa fa-camera\"></i></span></a>";
	
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
	
	info = '<span class="info_box">'+ info +'</span>';
	
	<?php
	if((int)$group_info['for_percentage'] === 1){
	?>
	info += "<div class='show_data_class' style=\"margin: 3px 0px; white-space: nowrap; max-width: 80px; font-size: 11px; position: relative;\" title=\""+ Product.storage_caption +"\"><span style=\"overflow: hidden; text-overflow: ellipsis;\" class=\"info_box\">"+ Product.storage_caption +"</span></div>";
	<?php
	}
	?>
    
	info = '<div style="margin: 3px 0px;">'+ info +'</div>';
	
	
    // С подсказкой - этот вариант основной
	if(Product.check_return !== "") {

		let return_value = '';
		if(Product.json_params !== '' && Product.json_params !== null) {
			return_value_obj = JSON.parse(Product.json_params);
			return_value = return_value_obj.return_value || '';
		}

		if(Product.check_return == "return") {
			if(return_value !== undefined && return_value != '' ) {
				info += "<span class=\"product-return-icon-value success\"><span data-tooltip=\"Процент удержания за возврат товара "+return_value+"%\" class=\"product-return-icon-tooltip\"></span>"+return_value+"%</span>";
			}
			else
			{
				info += "<span class=\"product-return-icon success\"><span data-tooltip=\"Возврат возможен\" class=\"product-return-icon-tooltip\"></span></span>";
			}
		} else if(Product.check_return == "no_return") {
			info += "<span class=\"product-return-icon error\"><span data-tooltip=\"Внимание! Эта деталь не полежит возврату или обмену!\" class=\"product-return-icon-tooltip\"></span></span>";
		}
	}
	
	info = '<span class="info_box">'+ info +'</span>';
	
    //Формирование колонки "Наличие"
    //Объект с описанием наличия - для отображения в информационном окне
    var supply_info_json = "{&quot;exist&quot;:"+Product.exist+",&quot;time_to_exe&quot;:"+Product.time_to_exe+",&quot;time_to_exe_guaranteed&quot;:"+Product.time_to_exe_guaranteed+",&quot;probability&quot;:"+Product.probability+"}";
    //Колонка
    var exist = "<span onclick=\"openInfoWindow(null, null, 1, '"+supply_info_json+"');\">" + Product.exist + "<img src=\"/lib/TreelaxCharts/sectors.php?number=2&value0="+Product.probability+"&value1="+(100-Product.probability)+"&start_angle=30&size=50&inside_size=1&slope=1.1\" /></span>";
    
	
	
	manufacturer = '<span title="'+ Product.manufacturer +'">'+ Product.manufacturer +'</span>';
	article_show = "<a title='Искать: "+ Product.article_show +"' class=\"bread_crumbs_a\" style=\"text-decoration:underline; color:#000;\" href=\"/shop/part_search?article="+Product.article+"\">"+Product.article_show+"</a>";
	name = "<span title=\""+Product.name+"\">"+Product.name+"</span>";
	
	
	
	// Кнопки увеличения количества товара добавляемого в корзину //////////////////////////////////////////////////////////////////////////////////////////////
	var p_min_order = Product.min_order * 1;
	var p_exist = Product.exist * 1;
	
	if(p_min_order == 0){
		p_min_order = 1;
	}
	if(p_exist == 0){
		p_exist = 1;
	}
	
	cart_html = '<div style="text-align: center; line-height: 1em;"><table>';
		cart_html += '<tr><td><a style="font-size: 20px; padding-right: 3px;" href="javascript:void(0);" onclick="addToCart('+Product.aid+');"><span><i class="fa fa-shopping-cart"></i></span></a></td></tr>';
		cart_html += '<tr><td><div style="text-align: center; background: #f9f9f9; border: 1px solid #ddd; padding: 2px 1px; border-radius: 3px; font-size: 11px; margin-bottom: 1px;"><table><tr>';
			cart_html += "<td><a style='width:12px;' onclick=\"minusCountNeed("+Product.aid+", "+p_exist+", "+p_min_order+");\" class=\"count_need_minus\" href=\"javascript:void(0);\"><i class=\"fa fa-minus\"></i></a></td>";
			cart_html += "<td><input style=\"text-align:center; width:24px; font-size: 10px; height: 14px; margin: 0px 2px; vertical-align: middle; border: 1px solid #ddd; border-radius: 2px !important; box-shadow: none; -webkit-appearance: none; -moz-appearance: none; appearance: none;\" type=\"text\" value=\""+p_min_order+"\" onChange=\"onKeyUpCountNeed("+Product.aid+", "+p_exist+", "+p_min_order+");\" id=\"count_need_"+Product.aid+"\" /></td>";
			cart_html += "<td><a style='width:12px;' onclick=\"plusCountNeed("+Product.aid+", "+p_exist+", "+p_min_order+");\" class=\"count_need_plus\" href=\"javascript:void(0);\"><i class=\"fa fa-plus\"></i></a></td>";
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

	exist = '<table style="float: right;"><tr><td><div style="text-align: right;"><a class="product_img_'+key+'" onClick="show_modal_product_info('+key+', '+Product.aid+')" style="display:none; line-height: 0; border: 1px solid #ddd; padding: 2px; margin-right: 2px; border-radius: 3px; cursor:pointer;" ><span style="width: 40px; height: 28px; display: inline-block; background-size: cover; background-position: center center; cursor:pointer;"></span></a></div></td><td><div style="width: 45px; text-align: right;">'+exist+'</div></td></tr></table>';
	
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
							images += '<a href="'+json_params.images[i]['url']+'" rel="lightbox-product-'+Product.aid+'"><img style="width: 20px; height: 17px; border: 1px solid #ddd; border-radius: 4px; margin-left: 5px;" src="'+json_params.images[i]['url']+'" /></a>';
						}else{
							images += '<a class="hidden" href="'+json_params.images[i]['url']+'" rel="lightbox-product-'+Product.aid+'"><img style="width: 20px; height: 17px; border: 1px solid #ddd; border-radius: 4px; margin-left: 5px;" src="'+json_params.images[i]['url']+'" /></a>';
						}
					}
				}
				name += images;
			}
		}
	}
	
	let bg_color = '';
	if(Product.hidden){
		bg_color = ' style="background: '+Product.hidden_color+';" ';
	}

	
	if(Product.hidden){
		return '<tr '+bg_color+'><td class="td_manufacturer">'+ manufacturer +'</td><td class="td_article">'+ article_show +'</td><td class="td_name">'+ name +'</td><td class="td_exist">'+ exist +'</td><td class="td_time_to_exe"><span onclick="openInfoWindow(null, null, 1, \''+ supply_info_json +'\');">'+ time_to_exe +'</span></td><td class="td_info">'+ info +'</td><td class="td_price">'+ price +'</td><td class="td_add_to_cart">'+ cart_html +'</td>'+ '<td class="td_color" style="background:'+color+'"></td></tr>';
	}
	else{
	    if(all_storages_info[Product.storage_id]['bg_line_color'] == 1){
    		return '<tr style="background:'+color+';"><td class="td_manufacturer">'+ manufacturer +'</td><td class="td_article">'+ article_show +'</td><td class="td_name">'+ name +'</td><td class="td_exist">'+ exist +'</td><td class="td_time_to_exe"><span onclick="openInfoWindow(null, null, 1, \''+ supply_info_json +'\');">'+ time_to_exe +'</span></td><td class="td_info">'+ info +'</td><td class="td_price">'+ price +'</td><td class="td_add_to_cart">'+ cart_html +'</td>'+ '<td class="td_color" style="background:'+color+'"></td></tr>';
    	}else{
    		return '<tr><td class="td_manufacturer">'+ manufacturer +'</td><td class="td_article">'+ article_show +'</td><td class="td_name">'+ name +'</td><td class="td_exist">'+ exist +'</td><td class="td_time_to_exe"><span onclick="openInfoWindow(null, null, 1, \''+ supply_info_json +'\');">'+ time_to_exe +'</span></td><td class="td_info">'+ info +'</td><td class="td_price">'+ price +'</td><td class="td_add_to_cart">'+ cart_html +'</td>'+ '<td class="td_color" style="background:'+color+'"></td></tr>';
    	}
	}
	
}
// --------------------------------------------------------------------------------------------------------------------------------
var sortState = new Object;//Объект описания сортировки
sortState.field = '<?=$DP_Config->outer_sort_field?>';;
sortState.asc_desc = '<?=$DP_Config->sort_asc_desc?>';
//Функция сортировки
function resultSort()
{
	Products.Required.sort(compareFields);
	Products.SearchName.sort(compareFields);
	Products.Quick_Analogs.sort(compareFields);
    Products.Analogs.sort(compareFields);
    Products.PossibleReplacement.sort(compareFields);
    Products.Spare_Box.sort(compareFields);
}
// ------------------------------------------------------------------------------------------------------------
function compareFields(x, y)
{
	//Для Чисел
	if(sortState.field == "manufacturer" || sortState.field == "article" || sortState.field == "name" )
	{
		if(sortState.asc_desc == "asc")
		{
			if(String(x[sortState.field]) > String(y[sortState.field]))
			{
				if(my_storages_id.indexOf( y['storage_id'] ) != -1)
				{
					return 1;
				}
				if(my_storages_id.indexOf( x['storage_id'] ) != -1)
				{
					return -1;
				}
				return 1;
			}
			else if(String(x[sortState.field]) < String(y[sortState.field]))
			{
				if(my_storages_id.indexOf( y['storage_id'] ) != -1)
				{
					return 1;
				}
				if(my_storages_id.indexOf( x['storage_id'] ) != -1)
				{
					return -1;
				}
				return -1;
			}
			else
			{
				if(my_storages_id.indexOf( y['storage_id'] ) == -1)
				{
					return -1;
				}
				return 0;
			}
		}
		else
		{
			if(String(x[sortState.field]) < String(y[sortState.field]))
			{
				if(my_storages_id.indexOf( y['storage_id'] ) != -1)
				{
					return 1;
				}
				if(my_storages_id.indexOf( x['storage_id'] ) != -1)
				{
					return -1;
				}
				return 1;
			}
			else if(String(x[sortState.field]) > String(y[sortState.field]))
			{
				if(my_storages_id.indexOf( y['storage_id'] ) != -1)
				{
					return 1;
				}
				if(my_storages_id.indexOf( x['storage_id'] ) != -1)
				{
					return -1;
				}
				return -1;
			}
			else
			{
				if(my_storages_id.indexOf( y['storage_id'] ) == -1)
				{
					return -1;
				}
				return 0;
			}
		}
	}
	else
	{
		if(sortState.asc_desc == "asc")
		{
			if(parseFloat(x[sortState.field]) > parseFloat(y[sortState.field]))
			{
				if(my_storages_id.indexOf( y['storage_id'] ) != -1)
				{
					return 1;
				}
				if(my_storages_id.indexOf( x['storage_id'] ) != -1)
				{
					return -1;
				}
				return 1;
			}
			else if(parseFloat(x[sortState.field]) < parseFloat(y[sortState.field]))
			{
				if(my_storages_id.indexOf( y['storage_id'] ) != -1)
				{
					return 1;
				}
				if(my_storages_id.indexOf( x['storage_id'] ) != -1)
				{
					return -1;
				}
				return -1;
			}
			else
			{
				if(my_storages_id.indexOf( y['storage_id'] ) == -1)
				{
					return -1;
				}
				return 0;
			}
		}
		else
		{
			if(parseFloat(x[sortState.field]) < parseFloat(y[sortState.field]))
			{
				if(my_storages_id.indexOf( y['storage_id'] ) != -1)
				{
					return 1;
				}
				if(my_storages_id.indexOf( x['storage_id'] ) != -1)
				{
					return -1;
				}
				return 1;
			}
			else if(parseFloat(x[sortState.field]) > parseFloat(y[sortState.field]))
			{
				if(my_storages_id.indexOf( y['storage_id'] ) != -1)
				{
					return 1;
				}
				if(my_storages_id.indexOf( x['storage_id'] ) != -1)
				{
					return -1;
				}
				return -1;
			}
			else
			{
				if(my_storages_id.indexOf( y['storage_id'] ) == -1)
				{
					return -1;
				}
				return 0;
			}
		}
	}
}

// ------------------------------------------------------------------------------------------------------------
//Смена сортировки
function sortChange(field)
{
    //Если тоже поле - меняем только направление
    if(sortState.field == field)
    {
        if(sortState.asc_desc == "asc")
        {
            sortState.asc_desc = "desc";
        }
        else
        {
            sortState.asc_desc = "asc";
        }
    }
    else//Если поле другое - ставим это поле и направление asc
    {
        sortState.field = field;
        sortState.asc_desc = "asc";
    }
    
    //Производим саму сортировку
    resultSort();
    //Обновляем отображние результата
    //Обновляем отображние результата
	if(this_filter != ''){
		productsCountRequest(this_filter.replace('_blok', ''));
	}else{
		resultReview();
	}
}
</script>











<!-------------------------------------------- Start Добавление в корзину -------------------------------------------->
<script>
//Добавление в корзину
function addToCart(aid)
{
    //1. По списку учетных объектов определяем, в где находится объект товара (Запрошенные/Аналоги)
    var AID_Object = Products.All[aid];
    
    //2. Получаем сам объект товара
    var Product = new Object;
    if(AID_Object.isRequired == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.Required.length; i++)
        {
			if( parseInt(Products.Required[i].aid) == parseInt(aid))
			{
				Product = Object.assign({}, Products.Required[i]);
                break;
			}
        }
    }
    else if(AID_Object.isSearchName == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.SearchName.length; i++)
        {
			if( parseInt(Products.SearchName[i].aid) == parseInt(aid))
			{
				Product = Object.assign({}, Products.SearchName[i]);
                break;
			}
        }
    }
    else if(AID_Object.isQuickAnalogs == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.Quick_Analogs.length; i++)
        {
			if( parseInt(Products.Quick_Analogs[i].aid) == parseInt(aid))
			{
				Product = Object.assign({}, Products.Quick_Analogs[i]);
                break;
			}
        }
    }
	else if (AID_Object.isAnalogs == true)
    {
        //Ищем объект товара в списке аналогов
        for(var i=0; i < Products.Analogs.length; i++)
        {
			if( parseInt(Products.Analogs[i].aid) == parseInt(aid))
			{
				Product = Object.assign({}, Products.Analogs[i]);
                break;
			}
        }
    }
    else if (AID_Object.isPossibleReplacement == true)
    {
        //Ищем объект товара в списке аналогов
        for(var i=0; i < Products.PossibleReplacement.length; i++)
        {
			if( parseInt(Products.PossibleReplacement[i].aid) == parseInt(aid))
			{
				Product = Object.assign({}, Products.PossibleReplacement[i]);
                break;
			}
        }
    }
    else if (AID_Object.isSpare_Box == true)
    {
        //Ищем объект товара в списке аналогов
        for(var i=0; i < Products.Spare_Box.length; i++)
        {
			if( parseInt(Products.Spare_Box[i].aid) == parseInt(aid))
			{
				Product = Object.assign({}, Products.Spare_Box[i]);
                break;
			}
        }
    }
    
	//2.1 Добавляем пометку о количестве
	if(document.getElementById("count_need_"+aid)){
		var count_need = parseInt(document.getElementById("count_need_"+aid).value);
		Product['count_need'] = count_need;
	}else{
		Product['count_need'] = Product['min_order'];
	}

	//2.2 Заменяем синоним на имя производителя переданное поставщиком
	if(Product['manufacturer_transferred']){
		var manufacturer_tmp = Product['manufacturer'];
		Product['manufacturer'] = Product['manufacturer_transferred'];
		Product['manufacturer_transferred'] = manufacturer_tmp;
	}
	
    //3. Данные в корзину можно класть сразу целым перечнем - поэтому приводим к массиву
    var product_objects = new Array;
    product_objects.push(Product);
    
	//log('Объект добавленного в корзину товара:');
	//log(Product);
	
	
	<?php
	// IFRAME :
	
	// Если страница загружена в iframe - используется в панели управления, для работы проценки
	if( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe' )
	{
	?>
		
		if(sessionStorage.getItem('cp_order_id') > 0)
		{
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_orders_items_add.php",
				dataType: "json",//Тип возвращаемого значения
				data: "product_objects="+encodeURIComponent(JSON.stringify(product_objects))+"&order_id="+sessionStorage.getItem('cp_order_id')+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					if(answer.status == true)
					{
						alert("Товар добавлен");
					}
					else
					{
						alert("Ошибка");
					}
				}
			});
			
			return false;
		}
		
	<?php
	}
	?>
	
	
    //4. Добавляем его в корзину
    jQuery.ajax({
        type: "POST",
        async: false, //Запрос синхронный
        url: "/content/shop/order_process/ajax_add_to_basket.php",
        dataType: "json",//Тип возвращаемого значения
        data: "product_objects="+encodeURIComponent(JSON.stringify(product_objects)),
        success: function(answer)
        {
            if(answer.status == true)
            {
				updateCartInfo();//Обновление корзины снизу
				showAdded();//Показываем лэйбл снизу
            }
            else
            {
                if(answer.code == "already")
                {
                    alert("Товар уже был добавлен ранее");
                }
                else
                {
                    alert(answer.message);
                }
            }
        }
    });
}//~function addToCart(aid)
</script>
<!-------------------------------------------- End Добавление в корзину -------------------------------------------->