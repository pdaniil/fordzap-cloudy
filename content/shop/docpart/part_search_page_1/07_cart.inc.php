function addToCart(aid)
{
    //1. По списку учетных объектов определяем, в где находится объект товара (Запрошенные/Аналоги)
    var AID_Object = Products.All[aid];
    
    //2. Получаем сам объект товара
    var Product = new Object;
    if(AID_Object.isRequired == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.Required.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.Required.ProductsTypes[i].manufacturer;
            var Article = Products.Required.ProductsTypes[i].article;
        
            //Массив объектов товаров:
            var ProductsObjects = Products.Required.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }else if(AID_Object.isSearchName == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.SearchName.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.SearchName.ProductsTypes[i].manufacturer;
            var Article = Products.SearchName.ProductsTypes[i].article;
        
            //Массив объектов товаров:
            var ProductsObjects = Products.SearchName.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }else if(AID_Object.isAnalogs == true)
    {
        //Ищем объект товара в списке аналогов
        for(var i=0; i < Products.Analogs.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.Analogs.ProductsTypes[i].manufacturer;
            var Article = Products.Analogs.ProductsTypes[i].article;
            
            //Массив объектов товаров:
            var ProductsObjects = Products.Analogs.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }else if(AID_Object.isQuickAnalogs == true)
	{
		//Ищем объект товара в списке быстрых аналогов
        for(var i=0; i < Products.Quick_Analogs.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.Quick_Analogs.ProductsTypes[i].manufacturer;
            var Article = Products.Quick_Analogs.ProductsTypes[i].article;
            
            //Массив объектов товаров:
            var ProductsObjects = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
	}else if(AID_Object.isPossibleReplacement == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.PossibleReplacement.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.PossibleReplacement.ProductsTypes[i].manufacturer;
            var Article = Products.PossibleReplacement.ProductsTypes[i].article;
        
            //Массив объектов товаров:
            var ProductsObjects = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }else if(AID_Object.isSpare_Box == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.Spare_Box.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.Spare_Box.ProductsTypes[i].manufacturer;
            var Article = Products.Spare_Box.ProductsTypes[i].article;
        
            //Массив объектов товаров:
            var ProductsObjects = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }
    
	//2.1 Добавляем пометку о количестве
	if(document.getElementById(M.prefix_count_need+aid)){
		var count_need = parseInt(document.getElementById(M.prefix_count_need+aid).value);
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
    
	log('Объект добавленного в корзину товара:');
	log(Product);
	
	
	<?php
	// IFRAME :

	// Если страница загружена в iframe - используется в панели управления, для работы проценки
	if( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe' )
	{
	?>

		if(sessionStorage.getItem('cp_order_id') > 0)
		{
			console.log(123123);
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_orders_items_add_to_cart.php",
				dataType: "json",//Тип возвращаемого значения
				data: "product_objects="+encodeURIComponent(JSON.stringify(product_objects))+"&order_id="+sessionStorage.getItem('cp_order_id')+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					if(answer.status == true)
					{
						//alert("Товар добавлен");
						//updateTempCart();
						//window.shopCart.loadCartData();  
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

