<?php
/**
 * Скрипт разбиения складов на группы опроса, подключается внутрь скриптов api проценки
*/



//Магазины
$offices_all = array();
$query = $db_link->prepare("SELECT * FROM `shop_offices`;");
$query->execute();
while( $office = $query->fetch() )
{
	$geo_id_query = $db_link->prepare('SELECT * FROM `shop_offices_geo_map` INNER JOIN `shop_geo` ON `shop_geo`.`id` = `shop_offices_geo_map`.`geo_id` WHERE `office_id` = ? ORDER BY `level` DESC LIMIT 1;');
	$geo_id_query->execute( array($office['id']) );
	$geo_id_record = $geo_id_query->fetch();
	$office['geo_id'] = $geo_id_record["geo_id"];
	
	$offices_all[(int)$office['id']] = $office;
}



//Список магазинов участвующих в опросе
$customer_offices = array();

if(!empty($_POST["offices"])){
	$offices = json_decode($_POST["offices"], true);
	if(is_array($offices)){
		foreach($offices as $office_id){
			$office_id = (int) $office_id;
			if(isset($offices_all[$office_id])){
				$customer_offices[] = $office_id;
			}
		}
	}
}else{
	foreach($offices_all as $office){
		$customer_offices[] = (int)$office['id'];
	}
}



$office_storage_bunches = array();//Список всех связок всех офисов обслуживания со своими складами. По этому списку будет осуществляться опрос складов

$office_storage_bunches_prices = array();//Такой же точно массив, только для складов типа Docpart-Price - для возможности из одновременного запроса

//Для каждого магазина получить список складов (не Treelax складов) и опросить каждый склад
for($i=0; $i < count($customer_offices); $i++)
{
    $offices_storages_map[$customer_offices[$i]] = array();//ID точки обслуживания => список складов
    
    //Получаем список складов для данной точки обслуживания у которых product_type = 2 (т.е. автозапчасти)
	if($_POST["no_api"]){
		$storages_query = $db_link->prepare('SELECT DISTINCT(storage_id) AS storage_id, (SELECT `handler_folder` FROM `shop_storages_interfaces_types` WHERE `id` = (SELECT `interface_type` FROM `shop_storages` WHERE `id` = `shop_offices_storages_map`.`storage_id`) ) AS `handler_folder` FROM shop_offices_storages_map WHERE office_id = ? AND storage_id IN (SELECT id FROM shop_storages WHERE interface_type IN(2,6) AND `hidden` = 0);');
	}else{
		$storages_query = $db_link->prepare('SELECT DISTINCT(storage_id) AS storage_id, (SELECT `handler_folder` FROM `shop_storages_interfaces_types` WHERE `id` = (SELECT `interface_type` FROM `shop_storages` WHERE `id` = `shop_offices_storages_map`.`storage_id`) ) AS `handler_folder` FROM shop_offices_storages_map WHERE office_id = ? AND storage_id IN (SELECT id FROM shop_storages WHERE interface_type > 1 AND `hidden` = 0);');
	}
	$storages_query->execute( array($customer_offices[$i]) );
    while( $storage = $storages_query->fetch() )
    {
		//Определяем версию протокола (1 шаг/2 шага)
		$protocol_version = 1;//По умолчанию
		//Если в папке обработчика присутствует скрипт get_manufacturers.php, значит версия протокола - 2 шаговый
		if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/".$storage["handler_folder"]."/get_manufacturers.php") )
		{
			$protocol_version = 2;
		}
		
		//Добавляем связку только, если склад не прайсовый
		if($storage["handler_folder"] != "prices")
		{
			
			// Определим склад каталога товаров
			$treelax_catalogue = false;
			if($storage["handler_folder"] === 'treelax_catalogue'){
				$treelax_catalogue = true;
			}
			
			//API-поставщиков добавляем в основной список
			array_push($office_storage_bunches, array("geo_id"=>$offices_all[(int)$customer_offices[$i]]['geo_id'], "office_id"=>(int)$customer_offices[$i], "storage_id"=>(int)$storage["storage_id"], "sent" => 0, "protocol_version"=>$protocol_version, "manufacturers_sent" => 0, "treelax_catalogue" => $treelax_catalogue));
		}
		else
		{
			//Прайсовых поставщиков добавляем во вспомогательный список
			array_push($office_storage_bunches_prices, array("geo_id"=>$offices_all[(int)$customer_offices[$i]]['geo_id'], "office_id"=>(int)$customer_offices[$i], "storage_id"=>(int)$storage["storage_id"], "sent" => 0, "protocol_version"=>$protocol_version, "manufacturers_sent" => 0));
		}
    }
	
	//После наполнения списка связок, вспомогательный список для прайсовых поствщиков добавляем первым элементом в основной список - для того, чтобы сначала опросить прайс-листы
	/*
	Версия протокола - ставим 3
	Добавляем еще один параметр office_storage_bunches - используется на сервере для понимания, какие связки складов и магазинов опросить
	*/
	if( count($office_storage_bunches_prices) > 0 )
	{
		array_unshift($office_storage_bunches, array("geo_id"=>$offices_all[(int)$customer_offices[$i]]['geo_id'], "office_id"=>0, "storage_id"=>0, "sent" => 0, "protocol_version"=>3, "manufacturers_sent" => 0, "office_storage_bunches"=>$office_storage_bunches_prices) );
		
		//Обнуляем массив для следующей итерации офисов
		$office_storage_bunches_prices = array();
	}
	
}



/* НАСТРОЙКИ РАБОТЫ АСИНХРОННОГО ОПРОСА ПОСТАВЩИКОВ */

// Получаем список групп складов
$storages_groups = array();

if(!empty($office_storage_bunches)){
	
	// Склады первой группы - то что в базе сайта: прайс листы, каталог товаров
	$storages_arr = array();
	foreach($office_storage_bunches as $item_bunches){
		// Если прайс или treelax_catalogue
		if($item_bunches['protocol_version'] == 3 || $item_bunches['treelax_catalogue'] == true){
			$storages_arr[] = $item_bunches;
		}
	}
	if(!empty($storages_arr)){
		$storages_groups[] = array('storages' => $storages_arr, 'sent' => 0, 'manufacturers_sent' => 0);
	}
	
	
	
	// Склады пользовательских групп
	$query = $db_link->prepare('SELECT * FROM `shop_storages_groups` ORDER BY `order`;');
	$query->execute();
	while($record = $query->fetch()){
		$storages = explode(',', $record['storages']);
		$storages_arr = array();
		if(!empty($storages)){
			foreach($storages as $storage_id){
				$storage_id = (int) trim($storage_id);
				foreach($office_storage_bunches as $item_bunches){
					// Если id склада есть в группе
					if($item_bunches['storage_id'] == $storage_id){
						$storages_arr[] = $item_bunches;
						break 1;// Выходим на один уровень
					}
				}
			}
		}
		if(!empty($storages_arr)){
			$storages_groups[] = array('storages' => $storages_arr, 'sent' => 0, 'manufacturers_sent' => 0);
		}
	}

	
	
	// Заполняем последнею группу, в которой будут оставшиеся склады
	$storages_arr = array();
	foreach($office_storage_bunches as $item_bunches){
		if($item_bunches['storage_id'] == 0 || $item_bunches['treelax_catalogue'] == true){
			continue;// Пропускаем потому что эти склады находятся в 0 группе
		}
		
		$storage_id = $item_bunches['storage_id'];
		$flag_none_group = true;
		
		// Ищем id склада в группах
		foreach($storages_groups as $item_group){
			if(!empty($item_group['storages'])){
				foreach($item_group['storages'] as $this_bunches){
					if($this_bunches['storage_id'] == $storage_id){
						$flag_none_group = false;// Склад уже добавлен в группу
						break 2;// Выходим из обоих циклов
					}
				}
			}
		}
		
		// Если id склада нет в группах
		if($flag_none_group == true){
			$storages_arr[] = $item_bunches;
		}
	}
	if(!empty($storages_arr)){
		// Если вне групп слишком много складов то разабьем их на несколько групп
		$cnt = count($storages_arr);
		$cnt_group = 15;// Максимальное количество складов в группе
		if($cnt > $cnt_group){
			$n = 0;
			$k = 0;
			$storages_arr_tmp = array();
			for($i = 0; $i < $cnt; $i++){
				$storages_arr_tmp[$k][] = $storages_arr[$i];
				$n++;
				if($n == $cnt_group){
					$n = 0;
					$k++;
				}
			}
			if(!empty($storages_arr_tmp)){
				foreach($storages_arr_tmp as $storages_arr){
					$storages_groups[] = array('storages' => $storages_arr, 'sent' => 0, 'manufacturers_sent' => 0);
				}
			}
		}else{
			$storages_groups[] = array('storages' => $storages_arr, 'sent' => 0, 'manufacturers_sent' => 0);
		}
	}
}



?>