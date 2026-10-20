<?php

/*
if($_SERVER['REMOTE_ADDR'] !== '80.82.46.136' && empty($_POST["request_object"])){
	require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_items_add_old.php");
}else{
*/
/*
if(!empty($_POST) && empty($_POST["request_object"])){
echo '<pre>';
var_dump($_POST);
echo '</pre>';
exit;
}*/

/**
 * Страница для добавления позиции в заказ
*/

ini_set("display_errors",0);

// Функция возвращает email пользователя с переданным user_id
function get_email_user($user_id){
	global $db_link, $DP_Config;
	
	$query = $db_link->prepare("SELECT `email` FROM `users` WHERE `user_id` = ?;");
	$query->execute( array($user_id) );
	$record = $query->fetch();
	$email = $record["email"];
	return $email;
}

//Функция замены первого вхождения строки
function str_replace_once($search, $replace, $text) 
{ 
   $pos = strpos($text, $search); 
   return $pos!==false ? substr_replace($text, $replace, $pos, strlen($search)) : $text; 
}

//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
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

//Подстрока для умножение цены на курс валюты склада
$SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";

//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

if(isset($_GET['id'])){
$order_id = (int)$_GET['id'];

// Получаем id пользователя из заказа
$sql = 'SELECT `user_id`, `office_id` FROM `shop_orders` WHERE `id` = ?;';

$query = $db_link->prepare($sql);
$query->execute( array($order_id) );
$record = $query->fetch();
$customer_id = $record['user_id'];
$userProfile = DP_User::getUserProfileById($customer_id);
$group_id = $userProfile["groups"][0];
$office_id = $record['office_id'];
}

if(!empty($_POST["request_object"]))
{

	$answer = array('status'=>false);
	$request_object = json_decode($_POST['request_object'], true);
	if($request_object['office_id'] > 0){
		$office_id = (int) $request_object['office_id'];
	}
	
	if($request_object['group_id'] > 0){
		$group_id = (int) $request_object['group_id'];
	}
	
	switch($request_object['action'])
	{
		case 'search':
			
			$search_string = trim(urldecode($request_object['value']));
			
			require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/text_search_algorithm.php");
			
			if(!empty($products_list)){
				$sql = "
				SELECT *,
				`price` * $SQL_currency_rate + `price` * $SQL_currency_rate * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = $office_id AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = $group_id AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price`, 
				(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`) AS 'caption', 
				
				(SELECT `value` FROM `shop_properties_values_text` WHERE `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_storages_data`.`category_id` AND `value` = 'Артикул' AND `property_type_id` = 3) AND `product_id` = `shop_storages_data`.`product_id`) AS article, 
				(SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `shop_storages_data`.`product_id` AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_storages_data`.`category_id` AND `value` = 'Производитель' AND `property_type_id` = 5))) AS manufacturer,
				
				(SELECT `value` FROM `shop_catalogue_categories` WHERE `id` = (SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`)) AS 'category', 
				(SELECT `file_name` FROM `shop_products_images` WHERE `product_id` = `shop_storages_data`.`product_id` LIMIT 1) AS 'image', 
				(SELECT `name` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) AS 'storage_name' 
				FROM `shop_storages_data` WHERE `product_id` IN(".implode(',',$products_list).") AND `storage_id` IN(SELECT DISTINCT `storage_id` FROM `shop_offices_storages_map` WHERE `office_id` = $office_id AND `storage_id` IN(SELECT `id` FROM `shop_storages` WHERE `interface_type` = 1))
				";
				
				$products_list = array();
				$query = $db_link->prepare($sql);
				$query->execute();
				while( $product_record = $query->fetch() )
				{
					$work_price = $product_record['customer_price'];
					
					//Округление цены
					if($DP_Config->price_rounding == '1')//Без копеечной части
					{
						if($work_price > (int)$work_price)
						{
							$work_price = (int)$work_price+1;
						}
						else
						{
							$work_price = (int)$work_price;
						}
					}
					else if($DP_Config->price_rounding == '2')//До 5 руб
					{
						$work_price = (integer)$work_price;
						$price_str = (string)$work_price;
						$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
						if($price_str_last_char > 0 && $price_str_last_char < 5)
						{
							$work_price = $work_price + (5 - $price_str_last_char);
						}
						else if($price_str_last_char > 5 && $price_str_last_char <= 9)
						{
							$work_price = $work_price + (10 - $price_str_last_char);
						}
					}
					else if($DP_Config->price_rounding == '3')//До 10 руб
					{
						$work_price = (integer)$work_price;
						$price_str = (string)$work_price;
						$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
						if($price_str_last_char != 0)
						{
							$work_price = $work_price + (10 - $price_str_last_char);
						}
					}
					
					$product_record['customer_price'] = $work_price;
					if ($product_record['image'] != '')
						$product_record['image'] = $DP_Config->domain_path . 'content/files/images/products_images/' . $product_record['image'];
					
					array_push($products_list, $product_record);
				}
				
				if(!empty($products_list)){
					$answer['status'] = true;
					$answer['products_list'] = $products_list;
				}
			}
			
			break;
		case 'get_product':
			
			$id = (int) $request_object['id'];
			
			if(!empty($id)){
				$sql = "
				SELECT *,
				`price` * $SQL_currency_rate + `price` * $SQL_currency_rate * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = $office_id AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = $group_id AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price`, 
				(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`) AS 'caption', 
				
				(SELECT `value` FROM `shop_properties_values_text` WHERE `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_storages_data`.`category_id` AND `value` = 'Артикул' AND `property_type_id` = 3) AND `product_id` = `shop_storages_data`.`product_id`) AS article, 
				(SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `shop_storages_data`.`product_id` AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_storages_data`.`category_id` AND `value` = 'Производитель' AND `property_type_id` = 5))) AS manufacturer,
				
				(SELECT `value` FROM `shop_catalogue_categories` WHERE `id` = (SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`)) AS 'category', 
				(SELECT `file_name` FROM `shop_products_images` WHERE `product_id` = `shop_storages_data`.`product_id` LIMIT 1) AS 'image', 
				(SELECT `name` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) AS 'storage_name' 
				FROM `shop_storages_data` WHERE `id` = $id
				";
				
				$query = $db_link->prepare($sql);
				$query->execute();
				$product = $query->fetch();
				
				if(!empty($product)){
					
					$work_price = $product['customer_price'];
					
					//Округление цены
					if($DP_Config->price_rounding == '1')//Без копеечной части
					{
						if($work_price > (int)$work_price)
						{
							$work_price = (int)$work_price+1;
						}
						else
						{
							$work_price = (int)$work_price;
						}
					}
					else if($DP_Config->price_rounding == '2')//До 5 руб
					{
						$work_price = (integer)$work_price;
						$price_str = (string)$work_price;
						$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
						if($price_str_last_char > 0 && $price_str_last_char < 5)
						{
							$work_price = $work_price + (5 - $price_str_last_char);
						}
						else if($price_str_last_char > 5 && $price_str_last_char <= 9)
						{
							$work_price = $work_price + (10 - $price_str_last_char);
						}
					}
					else if($DP_Config->price_rounding == '3')//До 10 руб
					{
						$work_price = (integer)$work_price;
						$price_str = (string)$work_price;
						$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
						if($price_str_last_char != 0)
						{
							$work_price = $work_price + (10 - $price_str_last_char);
						}
					}
					
					$product['customer_price'] = $work_price;
					if ($product['image'] != '')
						$product['image'] = $DP_Config->domain_path . 'content/files/images/products_images/' . $product['image'];
					
					$answer['status'] = true;
					$answer['product'] = $product;
				}
			}
			
			break;

		case 'add_count_to_product':

			$data_storage_id = (int) $request_object['id'];
			$product_count_need = (int) $request_object['count_need'];
			
			if(!empty($data_storage_id) || !empty($product_count_need)){
				$sql = "
				UPDATE `shop_storages_data` SET `exist` = `exist` + ?
				WHERE `id` = ?
				";
				
				$query_add_cat = $db_link->prepare($sql);
				
				if($query_add_cat->execute(array($product_count_need, $data_storage_id)) != true){
										
					$answer['status'] = false;
				}
				else
				{
					$answer['status'] = true;
				}
			}
			else
			{
				$answer['status'] = false;
			}

			break;

		case 'change_item_status':

			$order_position_id = (int) $request_object['item_id'];

			if(!empty($order_position_id)){
				$sql = "
				UPDATE `shop_orders_items` SET `in_storage_data` = 1
				WHERE `id` = ?
				";
				
				$query_item_in_storage = $db_link->prepare($sql);
				
				if($query_item_in_storage->execute(array($order_position_id)) != true){
										
					$answer['status'] = false;
				}
				else
				{
					$answer['status'] = true;
				}
			}
			else
			{
				$answer['status'] = false;
			}

			break;
	}
	exit(json_encode($answer));
}
else if(!empty($_POST["save_action"]))
{
	// ========== ИЗМЕНЕНИЕ: Определяем AJAX-запрос ==========
	$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';
	
	if($_POST["save_action"] != 'insert')
	{
		$error_message = 'Неизвесная операция';
		if ($is_ajax) {
			header('Content-Type: application/json');
			echo json_encode(['status' => false, 'message' => $error_message]);
			exit;
		}
	}
	else
	{
		//Технические данные для работы с заказами
		require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");



		$AdminId = (int) DP_User::getAdminId();
		
		$order_id = (int)$_POST['order_id'];
		$product_type = (int)$_POST['item_product_type'];
		
		$error_message = '';
		$success_message = '';
		$order_item_id = 0;
		
		
		if($product_type === 1){
			
			$id_storages_data = $_POST['id_storages_data'];
			$count_need = (int)$_POST['count_need'];
			
			$user_id = (int)$_POST['user_id'];
			$group_id = (int)$_POST['group_id'];
			$office_id = (int)$_POST['office_id'];
			
			$sql = "
			SELECT *,
			`price` * $SQL_currency_rate + `price` * $SQL_currency_rate * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = $office_id AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = $group_id AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price`, 
			(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`) AS 'caption', 
			
			(SELECT `value` FROM `shop_properties_values_text` WHERE `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_storages_data`.`category_id` AND `value` = 'Артикул' AND `property_type_id` = 3) AND `product_id` = `shop_storages_data`.`product_id`) AS article, 
			(SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `shop_storages_data`.`product_id` AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_storages_data`.`category_id` AND `value` = 'Производитель' AND `property_type_id` = 5))) AS manufacturer,
			
			(SELECT `value` FROM `shop_catalogue_categories` WHERE `id` = (SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`)) AS 'category', 
			(SELECT `name` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) AS 'storage_name' 
			FROM `shop_storages_data` WHERE `id` = $id_storages_data
			";
			
			$query = $db_link->prepare($sql);
			$query->execute();
			$product = $query->fetch();
			
			if(!empty($product)){
				
				
				/////////////////////////
				
				$work_price = $product['customer_price'];
				
				//Округление цены
				if($DP_Config->price_rounding == '1')//Без копеечной части
				{
					if($work_price > (int)$work_price)
					{
						$work_price = (int)$work_price+1;
					}
					else
					{
						$work_price = (int)$work_price;
					}
				}
				else if($DP_Config->price_rounding == '2')//До 5 руб
				{
					$work_price = (integer)$work_price;
					$price_str = (string)$work_price;
					$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
					if($price_str_last_char > 0 && $price_str_last_char < 5)
					{
						$work_price = $work_price + (5 - $price_str_last_char);
					}
					else if($price_str_last_char > 5 && $price_str_last_char <= 9)
					{
						$work_price = $work_price + (10 - $price_str_last_char);
					}
				}
				else if($DP_Config->price_rounding == '3')//До 10 руб
				{
					$work_price = (integer)$work_price;
					$price_str = (string)$work_price;
					$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
					if($price_str_last_char != 0)
					{
						$work_price = $work_price + (10 - $price_str_last_char);
					}
				}
				
				$product['customer_price'] = $work_price;
				
				/////////////////////////
				
				$name = $product['caption'];
				
				$product_id = $product["product_id"];
				$price = $product["customer_price"];
				if($count_need > 0 && $count_need <= $product["exist"]){
					
					$article = $product['article'];
					$manufacturer = $product['manufacturer'];
					$name = $product['caption'];
					
					$storage_id = (int)$product['storage_id'];
					$price_zakup = (float)$product['price_purchase'];
					
					$time_to_exe = 0;
					$time_to_exe_guaranteed = 0;
					$t2_exist = 0;
					
				}
			}
			
		}else{
			
			$article = $_POST['art'];
			$manufacturer = $_POST['man'];
			$name = $_POST['name'];
			
			$storage_id = (int)$_POST['storage_id'];
			$user_id = (int)$_POST['user_id'];
			
			$count_need = (int)$_POST['count_need'];
			$price = (float)$_POST['price'];//$t2_price_purchase + (($t2_price_purchase / 100) * ((float)$_POST['markup'])); 
			$price_zakup = (float)$_POST['price_zakup'];
			
			$time_to_exe = (int)$_POST['time_to_exe'];
			$time_to_exe_guaranteed = (int)$_POST['time_to_exe_guaranteed'];
			
			$office_id = (int)$_POST['office_id'];
			
			/******************/
			
			$product_id = 0;
			$t2_exist = 100;
			
			/******************/
			
		}
		
		//Получаем статус позиции заказа, который присваивается для вновь-созданной позиции
		$for_created_status_query = $db_link->prepare("SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `for_created`=1;");
		$for_created_status_query->execute();
		$for_created_status_record = $for_created_status_query->fetch();
		$for_created_status = $for_created_status_record["id"];
		
		
		$SQL_INSERT_ORDER_ITEM = "INSERT INTO `shop_orders_items` 
			(`order_id`, 
			`product_type`, 
			`price`, 
			`count_need`, 
			`product_id`, 
			`status`,
			`t2_manufacturer`,
			`t2_article`,
			`t2_article_show`,
			`t2_name`,
			`t2_exist`,
			`t2_time_to_exe`,
			`t2_time_to_exe_guaranteed`,
			`t2_storage`,
			`t2_min_order`,
			`t2_probability`,
			`t2_markup`,
			`t2_price_purchase`,
			`t2_office_id`,
			`t2_storage_id`,
			`t2_product_json`,
			`sao_state`,
			`sao_robot`,
			`t2_json_params`
			) 
			VALUES 
			(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
			
			if($product_type == 1)
			{
			    $binding_values = array($order_id, $product_type, $price, $count_need, $product_id, $for_created_status, $manufacturer, $article, $article, $name, $t2_exist, $time_to_exe, $time_to_exe_guaranteed, '', 1, 100, 0, 0, $office_id, $storage_id, '', 0, 0, '');
			}
			else
			{
			    $binding_values = array($order_id, $product_type, $price, $count_need, $product_id, $for_created_status, $manufacturer, $article, $article, $name, $t2_exist, $time_to_exe, $time_to_exe_guaranteed, '', 1, 100, 0, $price_zakup, $office_id, $storage_id, '', 0, 0, '');
			}
		
		
		////////////
		/*
		//Боевой SQL-запрос присваем в $SQL_bebug, чтобы боевой остался без изменений, т.к. он будет далее использоваться в скрипте
		$SQL_bebug = $SQL_INSERT_ORDER_ITEM;
		//Цикл по массиву значений, которые нужно биндить
		for( $i=0 ; $i < count($binding_values) ; $i++ )
		{
			$SQL_bebug = str_replace_once('?', $binding_values[$i], $SQL_bebug);
		}
		*/
		//$f = fopen('lllll.txt', 'w');
		//fwrite($f, $SQL_bebug);
		
		////////////
		
		
		if( $db_link->prepare($SQL_INSERT_ORDER_ITEM)->execute($binding_values) != true)
		{
			$error_message = "Ошибка: <br/> Не удалось добавить данные.";
		}
		else
		{
			$success_message = 'Успешно';
			$order_item_id = $db_link->lastInsertId(); // Получаем ID созданной позиции
			
			if($product_type == 1)
			{
				$order_item_id =$db_link->lastInsertId();//ID добавленной записи (позиция заказа)
				
				$storage_record_id = $id_storages_data;
				$count_reserved = $count_need;
				$price_purchase = $price_zakup;
				
				//3.2 Создаем детальную запись позиции заказа
				if( $db_link->prepare('INSERT INTO `shop_orders_items_details` (`order_id`, `order_item_id`, `office_id`, `storage_id`, `storage_record_id`, `count_reserved`, `price_purchase`) VALUES (?,?,?,?,?,?,?);')->execute( array($order_id, $order_item_id, $office_id, $storage_id, $storage_record_id, $count_reserved, $price_purchase) ) == true)
				{
					//3.1.Резервируем товар на складе
					$db_link->prepare('UPDATE `shop_storages_data` SET `exist` = (`exist`-'.$count_need.'), `reserved` = (`reserved`+'.$count_need.') WHERE `id`= ?;')->execute( array($storage_record_id) );
				}
			}
			
		}
	}
	
	// ========== ИЗМЕНЕНИЕ: Если AJAX-запрос, возвращаем JSON ==========
	if ($is_ajax) {
		header('Content-Type: application/json');
		if (!empty($error_message)) {
			echo json_encode(['status' => false, 'message' => strip_tags($error_message)]);
		} else {
			echo json_encode([
				'status' => true,
				'message' => 'Товар успешно добавлен',
				'order_id' => $order_id,
				'item_id' => $order_item_id
			]);
		}
		exit;
	}
	
	// ========== Не AJAX - редирект как раньше ==========
	$location_url = '/'.$DP_Config->backend_dir.'/shop/orders/order?order_id='.$order_id;
	?>
	<script>
		location="<?=$location_url?>&error_message=<?=$error_message;?>&success_message=<?=$success_message;?>";
	</script>
	<?php
	exit;
}
else
{
	//Для работы с пользователями
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
	//Технические данные для работы с заказами
	require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");



	$AdminId = (int) DP_User::getAdminId();
	
	require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
	$order_id = (int)$_GET['id'];

	// ========== ИЗМЕНЕНИЕ: получение списка складов, привязанных к офису ==========
	$storages_list = array();
	$storage_query = $db_link->prepare("
		SELECT s.id, s.name 
		FROM shop_storages s 
		INNER JOIN shop_offices_storages_map m ON s.id = m.storage_id 
		WHERE m.office_id = ? AND s.interface_type = 1 
		GROUP BY s.id 
		ORDER BY s.name
	");
	$storage_query->execute([$office_id]);
	while ($row = $storage_query->fetch()) {
		$storages_list[$row['id']] = $row['name'];
	}
	?>
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" href="javascript:void(0);" onclick="save_action();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/orders_items.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">К позициям заказов</div>
				</a>
				<a id="order_id_a" class="panel_a" href="">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/store.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">К заказу</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	
	
	
<div class="">
	<div class="">
		<!-- Nav tabs -->
		<div class="col-md-12">
		<ul class="nav nav-tabs">
			<li class="active" style="margin-bottom: -2px;"><a href="#tab_1" data-toggle="tab" style="z-index: 1;">Из каталога</a></li>
			<li style="margin-bottom: -2px;"><a href="#tab_2" data-toggle="tab" style="z-index: 1;">Произвольно</a></li>
		</ul>
		</div>
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane active" id="tab_1">
				<div class="col-lg-12">
					<div class="table-responsive">
					<div class="hpanel">
					<div class="panel-body">

					<div class="col-xs-12 col-md-8 col-lg-6">
						<h4>Поиск товара:</h4>
						<table style="width:100%;">
							<tr>
								<td><input id="search" type="text" class="form-control" autocomplete="off" /></td>
								<td><a href="javascript:void(0);" onClick="cat_search();" style="position: relative; left: -5px; border-radius: 0 3px 3px 0;" class="btn btn-as btn-primary">Поиск</a></td>
							</tr>
						</table>
					</div>
					
					<div class="col-xs-12">
						<div id="product_div"></div>
					</div>
					
					<div class="col-xs-12">
						<div id="products_div"></div>
					</div>
					
					
					
					<style>
					.tab-content h5{
						margin-top:20px;
					}
					#products_div > div{
						max-height: 500px;
						overflow-y: auto;
					}
					#products_div .table tr:hover{
						background:#f3e7e7;
					}
					#products_div .table tr{
						color:#000;
					}
					#products_div .table img {
						max-width: 50px;
						max-height: 50px;
						border-radius: 4px;
					}
					</style>
					
					<script>
					var id_storages_data = 0;
					var exist_storages_data = 0;
					function add_product(id){
						
						id_storages_data = 0;
						exist_storages_data = 0;
						
						//Объект для запроса
						var request_object = new Object;
						request_object.action = 'get_product';
						request_object.id = encodeURIComponent(id);
						request_object.office_id = <?=(int)$office_id;?>;
						request_object.group_id = <?=(int)$group_id;?>;
						
						jQuery.ajax({
							type: "POST",
							async: false, //Запрос синхронный
							url: "/content/carservice/admin_console/orders_items_add.php",
							dataType: "json",//Тип возвращаемого значения
							data: "request_object="+encodeURI(JSON.stringify(request_object)),
							success: function(answer)
							{
								//console.log(answer);
								var html = '';
								
								if(answer.status == true)
								{
									var product = answer.product;
									if(product){
										var imageHtml = (product.image && product.image != '') ? '<img src="'+product.image+'">' : '—';
										html += '<tr>';
										html += '<td>'+imageHtml+'</td>';
										html += '<td>'+product['manufacturer']+' - '+product['article']+' - '+product['caption']+'</td>';
										html += '<td>'+product['category']+'</td>';
										html += '<td>'+product['storage_id']+' - '+product['storage_name']+'</td>';
										html += '<td>'+product['exist']+'</td>';
										html += '<td>'+product['price']+'</td>';
										html += '<td>'+product['customer_price']+'</td>';
										html += '</tr>';
										
										id_storages_data = product['id'];
										exist_storages_data = product['exist'];
									}
								}
								
								if(html != ''){
									html = '<h4>Выбранный товар:</h4><table class="table"><tr><th>Фото</th><th>Наименование</th><th>Категория</th><th>Склад</th><th>Наличие</th><th>Закуп</th><th>Цена</th></tr>'+html+'</table>';
									html += '<h4>Требуемое количество:</h4><div><input id="exist_inp" class="form-control" style="max-width:200px;" /></div>';
								}else{
									html = 'Товар не найден в базе';
								}
								
								document.getElementById('product_div').innerHTML = html;
							},
							error: function(answer)
							{
								var html = 'Произошла ошибка';
								document.getElementById('product_div').innerHTML = html;
							}
						});
					}
					
					function cat_search(){
						document.getElementById("products_div").innerHTML = '';
						var value = document.getElementById("search").value;
						if(value != ''){
							//Объект для запроса
							var request_object = new Object;
							request_object.action = 'search';
							request_object.value = encodeURIComponent(value);
							request_object.office_id = <?=(int)$office_id;?>;
							request_object.group_id = <?=(int)$group_id;?>;
							
							document.getElementById("products_div").innerHTML = '<img id="img_crosses_add" style="height: 31px; margin-right: 5px; margin-top: 15px;" src="/content/files/images/ajax-loader-transparent.gif"/>';
							
							jQuery.ajax({
								type: "POST",
								async: false, //Запрос синхронный
								url: "/content/carservice/admin_console/orders_items_add.php",
								dataType: "json",//Тип возвращаемого значения
								data: "request_object="+encodeURI(JSON.stringify(request_object)),
								success: function(answer)
								{
									//console.log(answer);
									var html = '';
									var color_bg = '';
									if(answer.status == true)
									{
										var products_list = answer.products_list;
										if(products_list){
											for(var i = 0; products_list.length > i; i++){
												color_bg = '';
												if(products_list[i]['exist'] <= 0 || products_list[i]['price'] <= 0){
													color_bg = ' style="color: #ccc;" ';
												}
												var imageHtml = (products_list[i]['image'] && products_list[i]['image'] != '') ? '<img src="'+products_list[i]['image']+'">' : '—';
												html += '<tr '+color_bg+' >';
												html += '<td>';
												if(color_bg == ''){
												html += '<a class="btn btn-sm btn-primary" onclick="add_product('+products_list[i]['id']+');">Выбрать</a>';
												}
												html += '</td>';
												html += '<td>'+imageHtml+'</td>';
												html += '<td>'+products_list[i]['manufacturer']+' - '+products_list[i]['article']+' - '+products_list[i]['caption']+'</td>';
												html += '<td>'+products_list[i]['category']+'</td>';
												html += '<td>'+products_list[i]['storage_id']+' - '+products_list[i]['storage_name']+'</td>';
												html += '<td>'+products_list[i]['exist']+'</td>';
												html += '<td>'+products_list[i]['price']+'</td>';
												html += '<td>'+products_list[i]['customer_price']+'</td>';
												html += '<td style="white-space: nowrap;"><a title="Редактор товара" target="_blank" href="/<?php echo $DP_Config->backend_dir; ?>/shop/catalogue/products/product?category_id='+products_list[i]['category_id']+'&product_id='+products_list[i]['product_id']+'"><img src="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/cargo.png" style="width:20px;"></a><a style="margin-left:5px;" title="Управление наличием" target="_blank" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/stock/product?product_id='+products_list[i]['product_id']+'"><img src="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/cargo_control.png" style="width:20px;"></a></td>';
												html += '</tr>';
											}
										}
									}
									
									if(html != ''){
										html = '<h4>Найденные товары:</h4><div><table class="table"><tr><th></th><th>Фото</th><th>Наименование</th><th>Категория</th><th>Склад</th><th>Наличие</th><th>Закуп</th><th>Цена</th><th></th></tr>'+html+'</table></div>';
									}else{
										html = 'Товары не найдены';
									}
									
									document.getElementById('products_div').innerHTML = html;
								},
								error: function(answer)
								{
									var html = 'Произошла ошибка, попробуйте запросить другое наименование';
									document.getElementById('products_div').innerHTML = html;
								}
							});
						}
					}
					</script>


					


					</div>
					</div>
					</div>
				</div>
			</div>
			<div class="tab-pane" id="tab_2">
				<div class="col-lg-12">
					<div class="table-responsive">
					<div class="hpanel">
					<div class="panel-body">

					<div>
						<h4>Данные позиции:</h4>
						<label>Артикул:</label><br/>
						<input style="width:500px;" id="art_item_inp" type="text" value="" class="form-control" />
						<br/><label>Производитель:</label><br/>
						<input style="width:500px;" id="man_item_inp" type="text" value="" class="form-control" /><br/>
						<label>Наименование:</label><br/>
						<input style="width:500px;" id="name_item_inp" type="text" value="" class="form-control" />
					</div>


					<br>
					

					<table>
						<tr>
							<td>
							<div class="row">
								<div class="col-lg-12">
									<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped table-bordered">
										<thead>
											<th>Склад</th>
											<th>Цена</th>
											<th>Закупочная Цена</th>
											<th>Количество</th>
											<th>Ожидаемый срок в днях</th>
											<th>Гарантированный срок в днях</th>
										</thead>
										<tbody>
										
											<tr>
												<td>
													<select id="inp_storage_id" class="form-control">
												<?php 
													foreach($storages_list as $k => $v){
														echo '<option value="'.$k.'">'.$v.' ('.$k.')</option>';
													}
												?>
													</select>
												<td><input class="form-control" type="text" id="inp_price" value="<?php echo number_format(0, 2, '.', ''); ?>" /> </td>
												<td><input class="form-control" type="text" id="inp_price_zakup" value="<?php echo number_format(0, 2, '.', ''); ?>" /> </td>
												<td><input class="form-control" type="text" id="inp_count_need" value="1" /></td>
												
												
												<td><input class="form-control" id="time_to_exe_item_inp" type="text" value="1"/></td>
												<td><input class="form-control" id="time_to_exe_guaranteed_item_inp" type="text" value="5"/></td>
												
											</tr>
										
											
										</tbody>
									</table>
								</div>
							</div>
							</td>
						</tr>
					</table>


					
					</div>
					</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>










	
	


<form id="save_form" name="save_form" method="POST" style="display:none;">
	<input type="hidden" name="save_action" id="save_action" value="insert" />
	<input type="hidden" id="order_id" name="order_id" value="<?=$order_id;?>" />
	<input type="hidden" id="price" name="price" value="" />
	<input type="hidden" id="price_zakup" name="price_zakup" value="" />
	<input type="hidden" id="count_need" name="count_need" value="" />
	<input type="hidden" id="user_id" name="user_id" value="<?=$customer_id;?>" />
	<input type="hidden" id="group_id" name="group_id" value="<?=$group_id;?>" />
	<input type="hidden" id="storage_id" name="storage_id" value="" />
	<input type="hidden" id="art" name="art" value="" />
	<input type="hidden" id="man" name="man" value="" />
	<input type="hidden" id="name" name="name" value="" />
	<input type="hidden" id="item_product_type" name="item_product_type" value="2" />
	
	<input type="hidden" id="time_to_exe" name="time_to_exe" value="" />
	<input type="hidden" id="time_to_exe_guaranteed" name="time_to_exe_guaranteed" value="" />
	
	<input type="hidden" id="id_storages_data" name="id_storages_data" value="" />
	
	<input type="hidden" id="office_id" name="office_id" value="<?=$office_id;?>" />
</form>
<script>
//Функция сохранения (отправка формы)

document.getElementById('order_id_a').href = '/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?=$order_id;?>';
function save_action(){
	
	// Определяем какая вкладка активна
	var access = 0;
	var tab = 0;
	
	if ( $('#tab_1').hasClass('active')) {
		tab = 1;
	}
	if ( $('#tab_2').hasClass('active')) {
		tab = 2;
	}
	
	
	
	if(tab == 1){
		
		if(id_storages_data == 0){
			alert('Не выбран товар');
			return false;
		}
		
		var exist = document.getElementById('exist_inp').value;
		
		if(exist_storages_data < exist || exist <= 0){
			alert('Не верно указано количество товара');
			return false;
		}
		
		document.getElementById('id_storages_data').value = id_storages_data;
		document.getElementById('count_need').value = exist;
		document.getElementById('item_product_type').value = 1;
		
		access = 1;
	}
	
	if(tab == 2){
		document.getElementById('price').value = document.getElementById('inp_price').value;
		document.getElementById('price_zakup').value = document.getElementById('inp_price_zakup').value;
		document.getElementById('count_need').value = document.getElementById('inp_count_need').value;

		document.getElementById('storage_id').value = document.getElementById('inp_storage_id').value;
		
		document.getElementById('art').value = document.getElementById('art_item_inp').value;
		document.getElementById('man').value = document.getElementById('man_item_inp').value;
		document.getElementById('name').value = document.getElementById('name_item_inp').value;
		
		document.getElementById('time_to_exe').value = document.getElementById('time_to_exe_item_inp').value;
		document.getElementById('time_to_exe_guaranteed').value = document.getElementById('time_to_exe_guaranteed_item_inp').value;
		
		access = 1;
	}
	
	if(access == 1){
		// ========== ИЗМЕНЕНИЕ: Добавляем параметр ajax=1, если запрос выполняется из модалки ==========
		// Определяем, что запрос AJAX (можно по наличию специальной переменной)
		// Просто добавим скрытое поле, которое будем устанавливать в виджете
		// Для обратной совместимости добавим параметр ajax=1 в форму
		var form = document.forms["save_form"];
		var ajaxInput = document.createElement('input');
		ajaxInput.type = 'hidden';
		ajaxInput.name = 'ajax';
		ajaxInput.value = '1';
		form.appendChild(ajaxInput);
		
		document.forms["save_form"].submit();
	}
}
</script>
	
	
<?php
}
?>  