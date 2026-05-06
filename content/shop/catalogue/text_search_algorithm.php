<?php
/**
 * Шаблон скрипта для подбора товаров по текстовой строке
 * 
 * Данный скрипт заполняет массив $products_list номерами товаров, которые удовлетворяют поисковому запросу.
 * 
 * Этот скрипт нужно включать в соответствующие места скриптов, где должен использоваться этот массив
 * 
*/
$searsch_str = $search_string;
$heavy_search = true;
if (isset($request_object['quick_search']) && $request_object['quick_search'] == true)
	$heavy_search = false;

$like = '1 = 1';
$like_2 = '1 = 1';
$bind_array = array();
$iter = 0;
if(!empty($searsch_str))
{
	$searsch_str = trim($searsch_str);
	$searsch_str = explode(' ',$searsch_str);
	if(!empty($searsch_str))
	{
		$tmp_str = '';
		$tmp_str_2 = '';
		foreach($searsch_str as $item_str)
		{
			$item_str = trim($item_str);
			if(mb_strlen($item_str, 'utf-8') < 2)
			{
				continue;
			}
			
			$param_name = ':name'.$iter;
			$iter++;
			array_push($bind_array, array('value' => '%'.$item_str.'%', 'name'=>$param_name) );
			
			// Поиск по названию
			if($tmp_str != '')
			{
				$tmp_str .= ' AND ';
			}
			$tmp_str .= '((`caption` LIKE '.$param_name.')';
			//$tmp_str .= '((`caption` LIKE \'%OZ%\')';
			
			// Поиск по описанию
			if($tmp_str_2 != '')
			{
				$tmp_str_2 .= ' AND ';
			}
			$tmp_str_2 .= '((`content` LIKE '.$param_name.')';
			
			
			
			$tmp_str .= ')';
			$tmp_str_2 .= ')';
		}
		$like = '('.$tmp_str.')';
		$like_2 = '('.$tmp_str_2.')';
	}
}


$products_list = array();



//Поиск по наименованию
$sql = "SELECT `id` FROM `shop_catalogue_products` WHERE ".$like.";";
$products_list_query = $db_link->prepare($sql);
for( $i=0; $i < count($bind_array); $i++ )
{
	$products_list_query->bindValue($bind_array[$i]['name'], $bind_array[$i]['value'], PDO::PARAM_STR);
}
$products_list_query->execute();
while( $product_record = $products_list_query->fetch() )
{
	array_push($products_list, (int)$product_record["id"]);
}

if ($heavy_search) {

	//Поиск по текстовому описанию
	$sql = "SELECT `product_id` FROM `shop_products_text` WHERE ".$like_2.";";
	$products_list_query = $db_link->prepare($sql);
	for( $i=0; $i < count($bind_array); $i++ )
	{
		$products_list_query->bindValue($bind_array[$i]['name'], $bind_array[$i]['value'], PDO::PARAM_STR);
	}
	$products_list_query->execute();
	while( $product_record = $products_list_query->fetch() )
	{
		array_push($products_list, (int)$product_record["product_id"]);
	}
}

//Поиск по артикулу
$sql = "SELECT `product_id` FROM `shop_properties_values_text` WHERE `property_id` IN(SELECT `id` FROM `shop_categories_properties_map` WHERE `value` = 'Артикул' AND `property_type_id` = 3) AND `value` = '". mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $search_string), "UTF-8") ."';";
$products_list_query = $db_link->prepare($sql);
$products_list_query->execute();
while( $product_record = $products_list_query->fetch() )
{
    array_push($products_list, (int)$product_record["product_id"]);
}

//Поиск по Штрих-коду
$sql = "SELECT `product_id` FROM `shop_properties_values_text` WHERE `property_id` IN(SELECT `id` FROM `shop_categories_properties_map` WHERE `value` = 'Штрих-код' AND `property_type_id` = 3) AND `value` = '". mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $search_string), "UTF-8") ."';";
$products_list_query = $db_link->prepare($sql);
$products_list_query->execute();
while( $product_record = $products_list_query->fetch() )
{
    array_push($products_list, (int)$product_record["product_id"]);
}

?>