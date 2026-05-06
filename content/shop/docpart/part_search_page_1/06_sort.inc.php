//ВНУТРЕННЯЯ СОРТИРОВКА
var innerSortState = new Object;//Объект описания внутренней сортировки
innerSortState.field = '<?=$DP_Config->inner_sort_field?>';
innerSortState.asc_desc = '<?=$DP_Config->sort_asc_desc?>';
max_time_to_exe = <?=(int)$DP_Config->max_time_to_exe?>;

function innerSort()
{
    //Сортируем каждый набор видов товаров по полю (Запрошенный)
    for(var i=0; i < Products.Required.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.Required.ProductsTypes[i].manufacturer;
        var Article = Products.Required.ProductsTypes[i].article;
        // Сортируем группу позиций
        Products.Required.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.Required.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= max_time_to_exe && ProductsObjects[p].time_to_exe_guaranteed <= max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > max_time_to_exe || ProductsObjects[p].time_to_exe_guaranteed > max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.Required.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.Required.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.Required.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.Required.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		var storage_id = Products.Required.Products.Manufacturers[Manufacturer][Article][0].storage_id * 1;
		
		Products.Required.ProductsTypes[i].exist = exist;
		Products.Required.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.Required.ProductsTypes[i].price = price;
		Products.Required.ProductsTypes[i].storage = storage_id;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	//Сортируем каждый набор видов товаров в группе Найденных по наименованию
    for(var i=0; i < Products.SearchName.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.SearchName.ProductsTypes[i].manufacturer;
        var Article = Products.SearchName.ProductsTypes[i].article;
        // Сортируем группу позиций
        Products.SearchName.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.SearchName.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.SearchName.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.SearchName.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		var storage_id = Products.SearchName.Products.Manufacturers[Manufacturer][Article][0].storage_id * 1;
		
		Products.SearchName.ProductsTypes[i].exist = exist;
		Products.SearchName.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.SearchName.ProductsTypes[i].price = price;
		Products.SearchName.ProductsTypes[i].storage = storage_id;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	//Сортируем каждый набор видов товаров по полю (Быстрые Аналоги)
    for(var i=0; i < Products.Quick_Analogs.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.Quick_Analogs.ProductsTypes[i].manufacturer;
        var Article = Products.Quick_Analogs.ProductsTypes[i].article;
        Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= max_time_to_exe && ProductsObjects[p].time_to_exe_guaranteed <= max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > max_time_to_exe || ProductsObjects[p].time_to_exe_guaranteed > max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		var storage_id = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article][0].storage_id * 1;
		
		Products.Quick_Analogs.ProductsTypes[i].exist = exist;
		Products.Quick_Analogs.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.Quick_Analogs.ProductsTypes[i].price = price;
		Products.Quick_Analogs.ProductsTypes[i].storage = storage_id;
    }
    
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
    //Сортируем каждый набор видов товаров по полю (Аналоги)
	for(var i=0; i < Products.Analogs.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.Analogs.ProductsTypes[i].manufacturer;
        var Article = Products.Analogs.ProductsTypes[i].article;
        Products.Analogs.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.Analogs.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= max_time_to_exe && ProductsObjects[p].time_to_exe_guaranteed <= max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > max_time_to_exe || ProductsObjects[p].time_to_exe_guaranteed > max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.Analogs.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.Analogs.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.Analogs.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.Analogs.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		var storage_id = Products.Analogs.Products.Manufacturers[Manufacturer][Article][0].storage_id * 1;
		
		Products.Analogs.ProductsTypes[i].exist = exist;
		Products.Analogs.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.Analogs.ProductsTypes[i].price = price;
		Products.Analogs.ProductsTypes[i].storage = storage_id;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	//Сортируем каждый набор видов товаров по полю (PossibleReplacement)
    for(var i=0; i < Products.PossibleReplacement.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.PossibleReplacement.ProductsTypes[i].manufacturer;
        var Article = Products.PossibleReplacement.ProductsTypes[i].article;
        // Сортируем группу позиций
        Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= max_time_to_exe && ProductsObjects[p].time_to_exe_guaranteed <= max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > max_time_to_exe || ProductsObjects[p].time_to_exe_guaranteed > max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		var storage_id = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article][0].storage_id * 1;
		
		Products.PossibleReplacement.ProductsTypes[i].exist = exist;
		Products.PossibleReplacement.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.PossibleReplacement.ProductsTypes[i].price = price;
		Products.PossibleReplacement.ProductsTypes[i].storage = storage_id;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	//Сортируем каждый набор видов товаров по полю (Spare_Box)
    for(var i=0; i < Products.Spare_Box.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.Spare_Box.ProductsTypes[i].manufacturer;
        var Article = Products.Spare_Box.ProductsTypes[i].article;
        // Сортируем группу позиций
        Products.Spare_Box.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		

		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= max_time_to_exe && ProductsObjects[p].time_to_exe_guaranteed <= max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > max_time_to_exe || ProductsObjects[p].time_to_exe_guaranteed > max_time_to_exe)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.Spare_Box.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		var storage_id = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article][0].storage_id * 1;
		
		Products.Spare_Box.ProductsTypes[i].exist = exist;
		Products.Spare_Box.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.Spare_Box.ProductsTypes[i].price = price;
		Products.Spare_Box.ProductsTypes[i].storage = storage_id;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	// Производим внешнюю сортировку с учетом внутренней что бы отсортировать группы
	if(flag_one == true){
		outerSortState.field = '<?php echo $DP_Config->outer_sort_field;?>';
	}else{
		outerSortState.field = innerSortState.field;
	}
	outerSortState.asc_desc = innerSortState.asc_desc;

	outerSort();
}




// ------------------------------------------------------------------------------------------------------------------------------




// Сортировка числительных значений внутри группы
function compareNumbers(x, y)
{
    if(innerSortState.asc_desc == "asc")
    {
        if(parseFloat(x[innerSortState.field]) > parseFloat(y[innerSortState.field]))
        {
            if((my_storages_id.indexOf( y['storage_id'] ) != -1) && (my_storages_id.indexOf( x['storage_id'] ) != -1)){
				return 1;
			}
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
        else if(parseFloat(x[innerSortState.field]) < parseFloat(y[innerSortState.field]))
        {
            if((my_storages_id.indexOf( y['storage_id'] ) != -1) && (my_storages_id.indexOf( x['storage_id'] ) != -1)){
				return -1;
			}
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
            
			if(my_storages_id.indexOf( x['storage_id'] ) != -1)
			{
				return -1;
			}
			
			// При равных значениях сортируем либо по цене либо по сроку
			if(innerSortState.field == 'price'){
				if(parseFloat(x['time_to_exe']) > parseFloat(y['time_to_exe']))
				{
					return 1;
				}
				else if(parseFloat(x['time_to_exe']) < parseFloat(y['time_to_exe']))
				{
					return -1;
				}else{
					return 0;
				}
			}else{
				if(parseFloat(x['price']) > parseFloat(y['price']))
				{
					return 1;
				}
				else if(parseFloat(x['price']) < parseFloat(y['price']))
				{
					return -1;
				}else{
					return 0;
				}
			}
        }
    }
    else
    {
        if(parseFloat(x[innerSortState.field]) < parseFloat(y[innerSortState.field]))
        {
            if((my_storages_id.indexOf( y['storage_id'] ) != -1) && (my_storages_id.indexOf( x['storage_id'] ) != -1)){
				return 1;
			}
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
        else if(parseFloat(x[innerSortState.field]) > parseFloat(y[innerSortState.field]))
        {
            if((my_storages_id.indexOf( y['storage_id'] ) != -1) && (my_storages_id.indexOf( x['storage_id'] ) != -1)){
				return -1;
			}
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
            if(my_storages_id.indexOf( y['storage_id'] ) != -1)
			{
				return 1;
			}
			
			// При равных значениях сортируем либо по цене либо по сроку
			if(innerSortState.field == 'price'){
					
				if(parseFloat(x['time_to_exe']) > parseFloat(y['time_to_exe']))
				{
					return 1;
				}
				else if(parseFloat(x['time_to_exe']) < parseFloat(y['time_to_exe']))
				{
					return -1;
				}
				else
				{
					return 0;
				}
				
			}else{
				
				if(parseFloat(x['price']) > parseFloat(y['price']))
				{
					return 1;
				}
				else if(parseFloat(x['price']) < parseFloat(y['price']))
				{
					return -1;
				}
				else
				{
					return 0;
				}
			}
        }
    }
}




// ------------------------------------------------------------------------------------------------------------------------------




// Сортировка числительных значений между группами (для внешней сортировки)
function compareNumbers_2(x, y)
{
	if(outerSortState.asc_desc == "asc")
    {
        if(parseFloat(x[outerSortState.field]) > parseFloat(y[outerSortState.field]))
        {
			if(my_storages_id.indexOf( y['storage'] ) != -1)
			{
				return 1;
			}
			if(my_storages_id.indexOf( x['storage'] ) != -1)
			{
				return -1;
			}
			return 1;
        }
        else if(parseFloat(x[outerSortState.field]) < parseFloat(y[outerSortState.field]))
        {
            if(my_storages_id.indexOf( x['storage'] ) != -1)
			{
				return -1;
			}
			if(my_storages_id.indexOf( y['storage'] ) != -1)
			{
				return 1;
			}
			return -1;
        }
        else
        {
            // При равных значениях сортируем либо по цене либо по сроку
			if(outerSortState.field == 'price'){
				if(parseFloat(x['time_to_exe']) > parseFloat(y['time_to_exe']))
				{
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					if(my_storages_id.indexOf( x['storage'] ) != -1)
					{
						return -1;
					}
					return 1;
				}
				else if(parseFloat(x['time_to_exe']) < parseFloat(y['time_to_exe']))
				{
					if(my_storages_id.indexOf( x['storage'] ) != -1)
					{
						return -1;
					}
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					return -1;
				}else{
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					else
					{
						return -1;
					}
				}
			}else{
				if(parseFloat(x['price']) > parseFloat(y['price']))
				{
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					if(my_storages_id.indexOf( x['storage'] ) != -1)
					{
						return -1;
					}
				}
				else if(parseFloat(x['price']) < parseFloat(y['price']))
				{
					if(my_storages_id.indexOf( x['storage'] ) != -1)
					{
						return -1;
					}
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					return -1;
				}else{
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					else
					{
						return -1;
					}
				}
			}
        }
    }
    else
    {
        if(parseFloat(x[outerSortState.field]) < parseFloat(y[outerSortState.field]))
        {
			if(my_storages_id.indexOf( y['storage'] ) != -1)
			{
				return 1;
			}
			if(my_storages_id.indexOf( x['storage'] ) != -1)
			{
				return -1;
			}
			return 1;
        }
        else if(parseFloat(x[outerSortState.field]) > parseFloat(y[outerSortState.field]))
        {
			if(my_storages_id.indexOf( x['storage'] ) != -1)
			{
				return -1;
			}
			if(my_storages_id.indexOf( y['storage'] ) != -1)
			{
				return 1;
			}
			return -1;
        }
        else
        {
            // При равных значениях сортируем либо по цене либо по сроку
			if(outerSortState.field == 'price'){
				
				if(parseFloat(x['time_to_exe']) > parseFloat(y['time_to_exe']))
				{
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					if(my_storages_id.indexOf( x['storage'] ) != -1)
					{
						return -1;
					}
					return 1;
				}
				else if(parseFloat(x['time_to_exe']) < parseFloat(y['time_to_exe']))
				{
					if(my_storages_id.indexOf( x['storage'] ) != -1)
					{
						return -1;
					}
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					return -1;
				}
				else
				{
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						if(my_storages_id.indexOf( y['storage'] ) != -1)
						{
							return 1;
						}
						if(my_storages_id.indexOf( x['storage'] ) != -1)
						{
							return -1;
						}
						return 1;
					}
					else
					{
						if(my_storages_id.indexOf( x['storage'] ) != -1)
						{
							return -1;
						}
						if(my_storages_id.indexOf( y['storage'] ) != -1)
						{
							return 1;
						}
						return -1;
					}
				}
				
			}else{
				if(parseFloat(x['price']) > parseFloat(y['price']))
				{
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					if(my_storages_id.indexOf( x['storage'] ) != -1)
					{
						return -1;
					}
					return 1;
				}
				else if(parseFloat(x['price']) < parseFloat(y['price']))
				{
					if(my_storages_id.indexOf( x['storage'] ) != -1)
					{
						return -1;
					}
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					return -1;
				}
				else
				{
					if(my_storages_id.indexOf( y['storage'] ) != -1)
					{
						return 1;
					}
					else
					{
						return -1;
					}
				}
			}
        }
    }
}




// ------------------------------------------------------------------------------------------------------------------------------




//Смена состояния внутренней сортировки
function innerSortChange(field)
{
	if(flag_one == true && field == 'price'){
		flag_one = false;
		innerSortState.asc_desc = "asc";
		innerSortState.field = field;
	}else{
		//Если тоже поле - меняем только направление
		if(innerSortState.field == field)
		{
			if(innerSortState.asc_desc == "asc")
			{
				innerSortState.asc_desc = "desc";
			}
			else
			{
				innerSortState.asc_desc = "asc";
			}
		}
		else//Если поле другое - ставим это поле и направление asc
		{
			flag_one = false;
			innerSortState.asc_desc = "asc";
			innerSortState.field = field;
		}
    }
   
    //Производим саму сортировку
    innerSort()
	
    //Обновляем отображние результата
	if(this_filter != ''){
		productsCountRequest(this_filter.replace('_blok', ''));
	}else{
		resultReview();
	}
}




// ------------------------------------------------------------------------------------------------------------------------------




//ВНЕШНЯЯ СОРТИРОВКА
var outerSortState = new Object;//Объект описания внешней сортировки
function outerSort()
{
	if(outerSortState.field == "exist" || outerSortState.field == "time_to_exe" || outerSortState.field == "price"){
		Products.Required.ProductsTypes.sort(compareNumbers_2);
		Products.SearchName.ProductsTypes.sort(compareNumbers_2);
		Products.Quick_Analogs.ProductsTypes.sort(compareNumbers_2);
		Products.Analogs.ProductsTypes.sort(compareNumbers_2);
		Products.PossibleReplacement.ProductsTypes.sort(compareNumbers_2);
		Products.Spare_Box.ProductsTypes.sort(compareNumbers_2);
	}else{
		Products.Required.ProductsTypes.sort(compareStrings);
		Products.SearchName.ProductsTypes.sort(compareStrings);
		Products.Quick_Analogs.ProductsTypes.sort(compareStrings);
		Products.Analogs.ProductsTypes.sort(compareStrings);
		Products.PossibleReplacement.ProductsTypes.sort(compareStrings);
		Products.Spare_Box.ProductsTypes.sort(compareStrings);
	}
	
	/*
	// При первоначальной загрузке аналоги сортируем по бренду
	if(flag_one == true){
		outerSortState.field = 'manufacturer';
		outerSortState.asc_desc = 'asc';
		Products.Analogs.ProductsTypes.sort(compareStrings);
	}
	if(flag_one == true){
		outerSortState.field = 'price';
		outerSortState.asc_desc = 'asc';
		Products.Quick_Analogs.ProductsTypes.sort(compareNumbers_2);
	}
	*/
}




// ------------------------------------------------------------------------------------------------------------------------------




//Функция сравнения строковых значений
function compareStrings(x, y)
{
    if(outerSortState.asc_desc == "asc")
    {
        if(String(x[outerSortState.field]) > String(y[outerSortState.field]))
        {
            return 1;
        }
        else if(String(x[outerSortState.field]) < String(y[outerSortState.field]))
        {
            return -1;
        }
        else
        {
            return 0;
        }
    }
    else
    {
        if(String(x[outerSortState.field]) < String(y[outerSortState.field]))
        {
            return 1;
        }
        else if(String(x[outerSortState.field]) > String(y[outerSortState.field]))
        {
            return -1;
        }
        else
        {
            return 0;
        }
    }
}




// ------------------------------------------------------------------------------------------------------------------------------




//Смена ВНЕШНЕЙ сортировки
function outerSortChange(field)
{
    if(flag_one == true){
		flag_one = false;
		outerSortState.asc_desc = "asc";
		outerSortState.field = field;
	}else{
		//Если тоже поле - меняем только направление
		if(outerSortState.field == field)
		{
			if(outerSortState.asc_desc == "asc")
			{
				outerSortState.asc_desc = "desc";
			}
			else
			{
				outerSortState.asc_desc = "asc";
			}
		}
		else//Если поле другое - ставим это поле и направление asc
		{
			outerSortState.field = field;
			outerSortState.asc_desc = "asc";
		}
    }
	
    //Производим саму сортировку
    outerSort();
	
    //Обновляем отображние результата
	if(this_filter != ''){
		productsCountRequest(this_filter.replace('_blok', ''));
	}else{
		resultReview();
	}
}




// ------------------------------------------------------------------------------------------------------------------------------




//Добавление в корзину
