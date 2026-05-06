<?php
header('Content-Type: application/json;charset=utf-8;');

set_time_limit(0);
ini_set('display_errors', 0);



function get_patch($parent){
	
	global $db_link, $property_record, $list_property_items, $property_values;
	
	$values = array();
	
	$line_list_items = array();
	$line_list_items_query = $db_link->prepare("SELECT `id`, `value` FROM `shop_tree_lists_items` WHERE `tree_list_id` = ? AND `parent` = ".$parent." ORDER BY `order`;");
	$line_list_items_query->execute( array($property_record["list_id"]) );
	while( $list_item = $line_list_items_query->fetch() )
	{
		array_push($line_list_items, array("id"=>$list_item["id"], "value"=>$list_item["value"]) );
	}
	
	for($L=0; $L < count($line_list_items); $L++)
	{
		if(array_search((integer)$line_list_items[$L]["id"], $list_property_items) !== false)
		{
			$val = $line_list_items[$L]["value"];
			$res_values = get_patch($line_list_items[$L]["id"]);
			if(!empty($res_values)){
				foreach($res_values as $item){
					$values[] = $val.';'.$item;
				}
			}else{
				$values[] = $val;
			}
		}
	}
	
	return $values;
}

//Скрипт для формирования XML каталога
// -----------------------------------------------------------------------------------------------------
// 0. НАСТРОЙКИ ВЫПОЛНЕНИЯ СКРИПТА
$export_options = array();
$export_options["output_products_text"] = false;//Выводить текст товаров
$export_options["output_products_images"] = true;//Выводить изображения товаров
$export_options["output_products_suggestions"] = true;//Выводить предложения товаров
$export_options["output_format"] = "xml";//Формат экспорта
$export_options["offices"] = array(1);//Список магазинов, от которых выводить предложения
$export_options["data_output_mode"] = "create_file";//Способ вывода строки (оставить файл на сервере/скачать файл)
$export_options["group_id"] = 1;//Формат экспорта
if( !empty($_GET["export_options"]) )
{
	$export_options = json_decode($_GET["export_options"], true);
}
// -----------------------------------------------------------------------------------------------------
// 1. КОНСТАНТЫ
//Постфиксы таблиц значений свойств - зависят от типа свойства
$property_types_tables = array("1"=>"int", "2"=>"float", "3"=>"text", "4"=>"bool", "5"=>"list");
$products_images_dir = "content/files/images/products_images/";//Директория к изображениям товаров
// -----------------------------------------------------------------------------------------------------
// 2. БИБЛИОТЕКА ДЛЯ XML
require_once('Array2XML.php');
// -----------------------------------------------------------------------------------------------------
// 3. ПОДКЛЮЧЕНИЕ К БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");
// -----------------------------------------------------------------------------------------------------


// -------------------------------------------------------------------------------
//Проверка привелегий (пользователь должен иметь доступ к следующим страницам)
$pages_to_check = array();
$pages_to_check[] = array('id'=>342, 'url'=>'shop/perenos-dannyx/eksport-kataloga-tovarov-v-xml-i-json');//Экспорт каталога товаров в XML и JSON
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/control/check_admin_access/check_admin_access.php");
// -------------------------------------------------------------------------------


// -------------------------------------------------------------------------------
//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
// -------------------------------------------------------------------------------


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

//Проверяем доступ в панель управления
if( ! DP_User::isAdmin())
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = 'Forbidden';
	exit(json_encode($answer));
}
// -----------------------------------------------------------------------------------------------------

$file_extension = "";
if(strcasecmp($export_options["output_format"], "xml") == 0)
{
	$file_extension = "xml";
}
else if(strcasecmp($export_options["output_format"], "json") == 0)
{
	$file_extension = "json";
}

//Пишем в файл:
$export_file = fopen($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/catalogue_export_".$export_options["group_id"].".".$file_extension, "w");

// -----------------------------------------------------------------------------------------------------

// Рекурсивная функция запроса вложенных подкатегорий подкатегориий указанной категории
function get_categories_children($parent_id = 0, $level = 0)
{
	
	global $db_link, $export_options, $export_file, $property_types_tables, $products_images_dir, $DP_Config;
	
	$categories = array();//Список вложенных подкатегорий данного уровня
	
	$categories_query = $db_link->prepare("SELECT * FROM `shop_catalogue_categories` WHERE `parent` = ? ORDER BY `order`;");
	$categories_query->execute( array($parent_id) );
	while( $record = $categories_query->fetch() )
	{
		$categories[] = $record;
	}
	
	if(!empty($categories)){
		
		if($parent_id != 0)
		{
			//Пишем в файл
			if(strcasecmp($export_options["output_format"], "xml") == 0)
			{
				$i=0;
				do{
					$i++;
					fwrite($export_file, "\t");
				}while($level > $i);
				
				fwrite($export_file, "\t<categories>\n");
			}
			else if(strcasecmp($export_options["output_format"], "json") == 0)
			{
				fwrite($export_file, ','."\n");
				
				$i=0;
				do{
					$i++;
					fwrite($export_file, "\t");
				}while($level > $i);
				
				fwrite($export_file, '"categories":['."\n");
			}
		}
		
		$n=0;
		
		foreach($categories as $category_record){
			
			if(strcasecmp($export_options["output_format"], "json") == 0)
			{
				$n++;
				if($n > 1){
					fwrite($export_file, ",\n");
				}
			}
			
			//Обрабатываем данную категорию
			reader_category($category_record['id']);
		}
		
		
		if($parent_id != 0)
		{
			//Пишем в файл
			if(strcasecmp($export_options["output_format"], "xml") == 0)
			{
				$i=0;
				do{
					$i++;
					fwrite($export_file, "\t");
				}while($level > $i);
				
				fwrite($export_file, "\t</categories>\n");
			}
			else if(strcasecmp($export_options["output_format"], "json") == 0)
			{
				fwrite($export_file, "\n");
				
				$i=0;
				do{
					$i++;
					fwrite($export_file, "\t");
				}while($level > $i);
				
				fwrite($export_file, "]\n");
			}
		}
	}
}

// -----------------------------------------------------------------------------------------------------

// Функция обработки одной конкретной категории
function reader_category($category_id)
{
	
	global $db_link, $export_options, $export_file, $property_types_tables, $products_images_dir, $DP_Config;
	
	
	$categories_query = $db_link->prepare("SELECT * FROM `shop_catalogue_categories` WHERE `id` = ? LIMIT 1;");
	$categories_query->execute( array($category_id) );
	while( $category_record = $categories_query->fetch() )
	{
		//Формируем объект категории
		$category = array(
			"category" => array(
				"id"=>$category_record["id"],
				"caption"=>$category_record["value"],
				"count"=>$category_record["count"],
				"level"=>$category_record["level"],
				"parent"=>$category_record["parent"],
				"order"=>$category_record["order"],
				"published_flag"=>$category_record["published_flag"]
			)
		);
		
		//Заполняем поля в случае их наличия
		if($category_record["title_tag"] != "" && $category_record["title_tag"] != NULL)
		{
			$category["category"]["title_tag"] = $category_record["title_tag"];
		}
		if($category_record["alias"] != "" && $category_record["alias"] != NULL)
		{
			$category["category"]["alias"] = $category_record["alias"];
		}
		if($category_record["url"] != "" && $category_record["url"] != NULL)
		{
			$category["category"]["url"] = $category_record["url"];
		}
		if($category_record["description_tag"] != "" && $category_record["description_tag"] != NULL)
		{
			$category["category"]["description_tag"] = $category_record["description_tag"];
		}
		if($category_record["keywords_tag"] != "" && $category_record["keywords_tag"] != NULL)
		{
			$category["category"]["keywords_tag"] = $category_record["keywords_tag"];
		}
		if($category_record["image"] != "" && $category_record["image"] != NULL)
		{
			$category["category"]["image"] = $category_record["image"];
		}
		
		
		
		/*
		//Добавляем массив для подкатегорий, если есть вложенные категории
		if($category_record["count"] > 0)
		{
			$category["category"]["categories"] = array();
		}
		*/
		
		
		
		//Заполняем список свойств категории
		if($category_record["count"] == 0)
		{
			$properties = array();
			
			$properties_list_query = $db_link->prepare("SELECT *, (SELECT `caption` FROM `shop_properties_types` WHERE `id` = `shop_categories_properties_map`.`property_type_id`) AS `type_name` FROM `shop_categories_properties_map` WHERE `category_id` = ? ORDER BY `order`");
			$properties_list_query->execute( array($category["category"]["id"]) );
			while( $property_record = $properties_list_query->fetch() )
			{
				$property = array(
					"property"=>array(
						"id"=>$property_record["id"],
						"caption"=>$property_record["value"],
						"type"=>$property_record["property_type_id"],
						"type_name"=>$property_record["type_name"],
						"order"=>$property_record["order"]
					)
				);
				
				if((integer)$property["property"]["type"] == 5)
				{
					$property["property"]["list_id"] = $property_record["list_id"];
					
					//Получаем тип множественности списка
					$plurality_query = $db_link->prepare("SELECT `type` FROM `shop_line_lists` WHERE `id` = ?;");
					$plurality_query->execute( array($property_record["list_id"]) );
					$plurality_record = $plurality_query->fetch();
					$property["property"]["plurality"] = $plurality_record["type"];
					
					//Заполняем значения для списков
					$property["property"]["items"] = array();
					/*
					$shop_line_lists_items_query = $db_link->prepare("SELECT * FROM `shop_line_lists_items` WHERE `line_list_id` = ? ORDER BY `order`;");
					$shop_line_lists_items_query->execute( array($property_record["list_id"]) );
					while( $item_record = $shop_line_lists_items_query->fetch() )
					{
						$item = array(
							"item"=>array(
								"id"=>$item_record["id"],
								"caption"=>$item_record["value"]
							)
						);
						array_push($property["property"]["items"], $item);
					}
					*/
				}
				
				if((integer)$property["property"]["type"] == 6)
				{
					$property["property"]["list_id"] = $property_record["list_id"];
				}
				
				array_push($properties, $property);
			}
			
			if( count($properties) > 0 )
			{
				$category["category"]["properties"] = $properties;
			}
		}
		
		/*
		echo '<pre>';
		var_dump($category);
		exit;
		*/
		
		//Пишем в файл: (Начало категории)
		$i=0;
		do{
			$i++;
			fwrite($export_file, "\t");
		}while($category_record['level'] > $i);
		
		if(strcasecmp($export_options["output_format"], "xml") == 0)
		{
			$converter = new Array2XML();
			$converter->rootName = "";
			$export_str = $converter->convert($category);
			$export_str = str_replace('<?xml version="1.0" encoding="UTF-8"?>'."\n", '', $export_str);
			$export_str = str_replace('</category>', '', $export_str);
			
			fwrite($export_file, "\t".$export_str."\n");
		}
		else if(strcasecmp($export_options["output_format"], "json") == 0)
		{
			$export_str = json_encode($category);
			$export_str = mb_substr($export_str, 0, (mb_strlen($export_str, 'UTF-8')-2), 'UTF-8');
			fwrite($export_file, "".$export_str);
		}
		
		
		
		
		//Проверяем наличе вложенных подкатегорий
		get_categories_children($category_record['id'], $category_record['level']);
		
		
		
		
		
		
		//Заполняем товарами
		if($category_record["count"] == 0)
		{
			//Пишем в файл:
			if(strcasecmp($export_options["output_format"], "xml") == 0)
			{
				$i=0;
				do{
					$i++;
					fwrite($export_file, "\t");
				}while($category_record['level'] > $i);
				
				fwrite($export_file, "\t\t<products>\n");
			}
			else if(strcasecmp($export_options["output_format"], "json") == 0)
			{
				fwrite($export_file, ','."\n");
				
				$i=0;
				do{
					$i++;
					fwrite($export_file, "\t");
				}while($category_record['level'] > $i);
				
				fwrite($export_file, "\t".'"products":['."\n");
			}
			
			
				$n = 0;
				$products_query = $db_link->prepare("SELECT * FROM `shop_catalogue_products` WHERE `category_id` = ?;");
				$products_query->execute( array($category["category"]["id"]) );
				while( $product_record = $products_query->fetch() )
				{
					$n++;//Счетчик товаров, нужен для json представления
					
					$product = array(
						"product" => array(
							"id"=>$product_record["id"],
							"category_id"=>$product_record["category_id"],
							"category_caption"=>$category["category"]["caption"],
							"caption"=>$product_record["caption"],
							"alias"=>$product_record["alias"],
							"url"=>$category["category"]["url"]."/".$product_record["alias"],
							"published_flag"=>$product_record["published_flag"]
						)
					);
					
					
					//Создаем поля, если они заполнены
					if($product_record["title_tag"] != "" && $product_record["title_tag"] != NULL)
					{
						$product["product"]["title_tag"] = $product_record["title_tag"];
					}
					if($product_record["description_tag"] != "" && $product_record["description_tag"] != NULL)
					{
						$product["product"]["description_tag"] = $product_record["description_tag"];
					}
					if($product_record["keywords_tag"] != "" && $product_record["keywords_tag"] != NULL)
					{
						$product["product"]["keywords_tag"] = $product_record["keywords_tag"];
					}
					
					
					//Получаем изображения товара
					if($export_options["output_products_images"])
					{
						$product_images_query = $db_link->prepare("SELECT SQL_CALC_FOUND_ROWS `file_name` FROM `shop_products_images` WHERE `product_id` = ?;");
						$product_images_query->execute( array($product["product"]["id"]) );
						
						$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
						$elements_count_rows_query->execute();
						$elements_count_rows = $elements_count_rows_query->fetchColumn();
						
						if( $elements_count_rows > 0) $product["product"]["images"] = array();
						while( $image_record = $product_images_query->fetch() )
						{
							if(strpos($image_record["file_name"],'http') === 0){
								$image = array("image"=>$image_record["file_name"]);
							}else{
								$image = array("image"=>$image_record["file_name"]);
							}
							array_push($product["product"]["images"], $image);
						}
					}
					
					
					//Получаем текстовое описание товара
					if($export_options["output_products_text"])
					{
						$product_text_query = $db_link->prepare("SELECT `content` FROM `shop_products_text` WHERE `product_id` = ?;");
						$product_text_query->execute( array($product["product"]["id"]) );
						while($product_text_record = $product_text_query->fetch() )
						{
							if($product_text_record["content"] != "")
							{
								$product["product"]["text"] = $product_text_record["content"];
							}
						}
					}
					
					
					//Получаем значения свойств товара
					$product_properties = array();
					
					$properties_query = $db_link->prepare("SELECT *, (SELECT `caption` FROM `shop_properties_types` WHERE `id` = `shop_categories_properties_map`.`property_type_id`) AS `type_name` FROM `shop_categories_properties_map` WHERE `category_id` = ? ORDER BY `order`");
					$properties_query->execute( array($category["category"]["id"]) );
					while($property_record = $properties_query->fetch() )
					{
						$property = array(
							"property"=>array(
								"id"=>$property_record["id"],
								"caption"=>$property_record["value"],
								"type"=>$property_record["property_type_id"],
								"type_name"=>$property_record["type_name"]
							)
						);
						
						if((integer)$property["property"]["type"] == 5)
						{
							$property["property"]["list_id"] = $property_record["list_id"];
						}
						
						if((integer)$property["property"]["type"] == 6)
						{
							$property["property"]["list_id"] = $property_record["list_id"];
						}
						
						//Получаем значение данного свойства для товара:
						$table_postfix = $property_types_tables[(string)$property_record["property_type_id"]];//Постфикс таблицы
						
						if($property_record["property_type_id"] == '6'){
							$table_postfix = 'tree_list';
						}
						
						$property_value_query = $db_link->prepare("SELECT SQL_CALC_FOUND_ROWS `id`, `value` FROM `shop_properties_values_$table_postfix` WHERE `product_id` = ? AND `property_id` = ?;");
						$property_value_query->execute( array($product["product"]["id"], $property_record["id"]) );
						
						$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
						$elements_count_rows_query->execute();
						$elements_count_rows = $elements_count_rows_query->fetchColumn();
						
						if( $elements_count_rows > 0)
						{
							//Задаем значение
							switch($property_record["property_type_id"])
							{
								case 1:
								case 2:
									$property_value_record = $property_value_query->fetch();
									$property["property"]["value"] = $property_value_record["value"];
									break;
								case 3:
									$property_value_record = $property_value_query->fetch();
									if($property_value_record["value"] != "" && $property_value_record["value"] != NULL)
									{
										$property["property"]["value"] = $property_value_record["value"];
									}
									break;
								case 4:
									$property_value_record = $property_value_query->fetch();
									$property["property"]["value"] = $property_value_record["value"];
									break;
								case 5:
									$property_values = array();
									
									//Свойство списковое - значений может быть несколько
									$list_property_items = array();
									while($property_value_record = $property_value_query->fetch())
									{
										array_push($list_property_items, (integer)$property_value_record["value"]);
									}
									//Теперь получаем названия значений свойств из линейных списков
									$line_list_items = array();
									
									$line_list_items_query = $db_link->prepare("SELECT `id`, `value` FROM `shop_line_lists_items` WHERE `line_list_id` = ? ORDER BY `order`;");
									$line_list_items_query->execute( array($property_record["list_id"]) );
									while( $list_item = $line_list_items_query->fetch() )
									{
										array_push($line_list_items, array("id"=>$list_item["id"], "value"=>$list_item["value"]) );
									}
									$line_list_values_text = "";//Текстовая строка для вывода значений линейного списка
									for($L=0; $L < count($line_list_items); $L++)
									{
										if(array_search((integer)$line_list_items[$L]["id"], $list_property_items) !== false)
										{
											$property_value = array(
												"value"=>array(
													"id"=>$line_list_items[$L]["id"],
													"caption"=>$line_list_items[$L]["value"]
												)
											);

											array_push($property_values, $property_value);
										}
									}
									if( count($property_values) > 0)
									{
										$property["property"]["values"] = $property_values;
									}
									break;
								case 6:
									$property_values = array();
									
									//Свойство списковое - значений может быть несколько
									$list_property_items = array();
									while($property_value_record = $property_value_query->fetch())
									{
										array_push($list_property_items, (integer)$property_value_record["value"]);
									}
									
									$values = get_patch(0);
									if(!empty($values)){
										foreach($values as $item){
											$property_value = array(
												"value"=>array(
													"caption"=>$item
												)
											);
											array_push($property_values, $property_value);
										}
									}
									
									if( count($property_values) > 0)
									{
										$property["property"]["values"] = $property_values;
									}
									break;
							}
						}//~if() - есть значение свойства
						array_push($product_properties, $property);
					}
					if( count($product_properties) > 0 )
					{
						$product["product"]["properties"] = $product_properties;
					}
					
					
					
					//ПОЛУЧАЕМ ДАННЫЕ СКЛАДОВ
					if($export_options["output_products_suggestions"])
					{
						$suggestions = array();
						
						$group_id = $export_options["group_id"];
						
						//Для каждого магазина получить список складов и опросить каждый склад по ДАННОМУ товару
						for($o=0; $o < count($export_options["offices"]); $o++)
						{
							$office_id = $export_options["offices"][$o];//ID точки выдачи
							
							$storages_query = $db_link->prepare("SELECT DISTINCT(`storage_id`), `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = ?;");
							$storages_query->execute( array($office_id) );
							while($storage = $storages_query->fetch() )
							{
								$storage_id = $storage["storage_id"];
								$additional_time = $storage["additional_time"];
								
								$storage_product_records_query = $db_link->prepare("SELECT *, (SELECT markup/100 as markup FROM shop_offices_storages_map WHERE office_id = ? AND storage_id = ? AND group_id = ? AND min_point <= shop_storages_data.price AND max_point > shop_storages_data.price) AS markup FROM `shop_storages_data` WHERE `product_id` = ? AND `storage_id` = ?;");
								$storage_product_records_query->execute( array($office_id, $storage_id, $group_id, $product["product"]["id"], $storage_id) );
								
								while($storage_product_record = $storage_product_records_query->fetch() )
								{
									//1. Данные товара со склада:
									$record_id = $storage_product_record["id"];
									$price = $storage_product_record["price"];
									$price_crossed_out = $storage_product_record["price_crossed_out"];
									$arrival_time = $storage_product_record["arrival_time"];
									$exist = $storage_product_record["exist"];
									$reserved = $storage_product_record["reserved"];
									
									
									$suggestion = array(
										"suggestion"=>array(
											"id"=>$storage_product_record["id"],
											"customer_price"=>$storage_product_record["price"] + $storage_product_record["price"]*$storage_product_record["markup"],
											"markup"=>$storage_product_record["markup"]*100,
											"price"=>$storage_product_record["price"],
											"price_crossed_out"=>$storage_product_record["price_crossed_out"],
											"price_purchase"=>$storage_product_record["price_purchase"],
											"arrival_time"=>$storage_product_record["arrival_time"],
											"exist"=>$storage_product_record["exist"],
											"reserved"=>$storage_product_record["reserved"],
											"issued"=>$storage_product_record["issued"]
										)
									);
									
									array_push($suggestions, $suggestion);
									
								}//while() - по учетным записям товара с данного склада

							}
						}//По магазинам - опрашиваем для данного товара
						
						if( count($suggestions) > 0 )
						{
							$product["product"]["suggestions"] = $suggestions;
						}
					}
					
					//Пишем в файл:
					if(strcasecmp($export_options["output_format"], "json") == 0)
					{
						if($n > 1){
							fwrite($export_file, ','."\n");
						}
					}
					
					$i=0;
					do{
						$i++;
						fwrite($export_file, "\t");
					}while($category_record['level'] > $i);
					
					if(strcasecmp($export_options["output_format"], "xml") == 0)
					{
						$converter = new Array2XML();
						$converter->rootName = "";
						$export_str = $converter->convert($product);
						$export_str = str_replace('<?xml version="1.0" encoding="UTF-8"?>'."\n", '', $export_str);
						
						fwrite($export_file, "\t\t\t".$export_str."\n");
					}
					else if(strcasecmp($export_options["output_format"], "json") == 0)
					{
						$export_str = json_encode($product);
						fwrite($export_file, "\t\t".$export_str."");
					}
					
				}
			
			
			//Пишем в файл: (Конец категории)
			if(strcasecmp($export_options["output_format"], "xml") == 0)
			{
				$i=0;
				do{
					$i++;
					fwrite($export_file, "\t");
				}while($category_record['level'] > $i);
				
				fwrite($export_file, "\t\t</products>\n");
			}
			else if(strcasecmp($export_options["output_format"], "json") == 0)
			{
				fwrite($export_file, "\n");
				
				$i=0;
				do{
					$i++;
					fwrite($export_file, "\t");
				}while($category_record['level'] > $i);
				
				fwrite($export_file, "\t]\n");
			}
		}
		
		
		
		//Пишем в файл: (Конец категории)
		$i=0;
		do{
			$i++;
			fwrite($export_file, "\t");
		}while($category_record['level'] > $i);
		
		if(strcasecmp($export_options["output_format"], "xml") == 0)
		{
			fwrite($export_file, "\t"."</category>\n");
		}
		else if(strcasecmp($export_options["output_format"], "json") == 0)
		{
			fwrite($export_file, "}}");
		}
		
		
	}
}

// -----------------------------------------------------------------------------------------------------
//4. ФОРМИРОВАНИЕ МАССИВА ДЛЯ ВЫВОДА

if(strcasecmp($export_options["output_format"], "xml") == 0)
{
	fwrite($export_file, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	fwrite($export_file, '<shop>'."\n");
	fwrite($export_file, "\t".'<categories>'."\n");
}
else if(strcasecmp($export_options["output_format"], "json") == 0)
{
	fwrite($export_file, '{"categories":['."\n");
}


get_categories_children();


if(strcasecmp($export_options["output_format"], "xml") == 0)
{
	fwrite($export_file, "\t".'</categories>'."\n");
	fwrite($export_file, '</shop>');
}
else if(strcasecmp($export_options["output_format"], "json") == 0)
{
	fwrite($export_file, "\n".']}');
}


// -----------------------------------------------------------------------------------------------------


fclose($export_file);


// -----------------------------------------------------------------------------------------------------

// Результат выполнения
$answer = array();
$answer["status"] = true;
$answer["filename"] = "catalogue_export_".$export_options["group_id"].".".$file_extension;
exit(json_encode($answer));

// -----------------------------------------------------------------------------------------------------
?>