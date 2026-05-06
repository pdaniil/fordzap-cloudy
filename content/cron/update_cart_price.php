<?php
ini_set('memory_limit', '500M');
ini_set("max_execution_time", 600);
// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
//Обновление цен корзины по cron.
//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/modules/debug/debug.class.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");
$whrite_log = $DP_Config->whrite_cart_log == 1 ? true : false;
if($whrite_log) {
    $debug = new Debug();
    // $debug->logger("Запуск скрипта", $_SERVER, true);
}
/********************************************************************************************************************** */
/********************************************************************************************************************** */
$cart_items = array();
$cart_items_ids = array();
$cart_user_items = array();
$result = '';
$answer = array(
    'status' => true,
    'message' => ''
);
$interval_update = $DP_Config->cart_update_time ? $DP_Config->cart_update_time : 0; //В минутах, по стандарту 3600
$tech_key = $DP_Config->tech_key;
//Получаем временную отметку для заданного интервала
$date = new DateTime();
$date->sub(new DateInterval("PT{$interval_update}M"));
$limit_items_time = $date->getTimestamp();
/********************************************************************************************************************** */
/********************************************************************************************************************** */
//1.
//При отладке оставить user_id  в запросе.
if(isset($_POST['cart_items']) && !empty($_POST['cart_items'])) {
    $cart_items_req = json_decode($_POST['cart_items'], true);
    $cart_items_ids = [];
    foreach($cart_items_req as $cart_item) {
        $cart_items_ids[] = $cart_item['id'];
    }
    $cart_items_ids_string = implode(',', $cart_items_ids);
    $CART_ITEMS_SQL = "
        SELECT
        *
        FROM
        	`shop_carts`
        WHERE
        	`id` IN ($cart_items_ids_string)
        ORDER BY `time`
        LIMIT 50
        ;";
}
else
{
    $CART_ITEMS_SQL = "
        SELECT
        *
        FROM
        	`shop_carts`
        WHERE
        	`time` < '{$limit_items_time}'
        AND `user_id` = 1
        ORDER BY `time`
        LIMIT 50
        ;";
        // AND `user_id` = 65
}
if($whrite_log) {
    $debug->logger("Запрос в БД", $CART_ITEMS_SQL, true);
}
$items_query = $db_link->prepare($CART_ITEMS_SQL);
$items_query->execute();
while( $row = $items_query->fetch() )
{
    $user_id = $row['user_id'];
    $cart_items[] = $row;
    $cart_user_items[$user_id][] = $row;
    $cart_items_ids[] = $row['id'];
}
$total_items_cart = count($cart_items);
if($whrite_log) {
    $debug->logger("Все позиции корзины ($total_items_cart), у которых истек срок обновления - $interval_update мин", $cart_user_items, true);
}
//2.
//Сначала обнуляем признак обновления перед анализом
$cart_items_ids_str = implode(',', $cart_items_ids);
$CART_ITEMS_UPDATE_SQL = "
UPDATE
    `shop_carts`
SET
	`is_update` = 0,
	`update_price` = '',
	`update_exist` = ''
WHERE
	`id` IN ($cart_items_ids_str)
;";
$items_update_query = $db_link->prepare($CART_ITEMS_UPDATE_SQL);
$items_update_query->execute();
$counter = 0;
foreach ($cart_items as $item_data) {
    $counter += 1;
    $item_article = '';
    $item_manufacturer = '';
    $office_id = 0;
    $storage_id = 0;
    $storage_record_id = 0;
    $item_price = $item_data['price'];
    $item_id = $item_data['id'];
    $item_user_id = $item_data['user_id'];
    $item_count_need = $item_data['count_need'];
    $item_product_id = $item_data['product_id'];
    $item_time_to_exe = $item_data['t2_time_to_exe'];
		$product_type = 0;
    if($item_data['product_type'] == 1) {
				$product_type = 1;
        //Получаем Наименование, id категории и alias продукта
        $product_query = $db_link->prepare("SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = $item_product_id;");
        $product_query->execute();
        $product_record = $product_query->fetch();
        $category_id = (int) $product_record['category_id'];
        $item_data['t1_category_id'] = $category_id;
        //Получаем свойство Производитель
        $product_manufacturer_query = $db_link->prepare("SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = $item_product_id AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = $category_id AND `value` = 'Производитель' AND `property_type_id` = 5))");
        $product_manufacturer_query->execute();
        $product_manufacturer_record = $product_manufacturer_query->fetch();
        $product_manufacturer = trim($product_manufacturer_record['value']);
        if(!empty($product_manufacturer)){
            $item_manufacturer = $product_manufacturer;
        }
        $item_data['t1_manufacturer'] = $item_manufacturer;
        //Получаем свойство Артикул
        $product_article_query = $db_link->prepare("SELECT `value` FROM `shop_properties_values_text` WHERE `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = $category_id AND `value` = 'Артикул' AND `property_type_id` = 3) AND `product_id` = $item_product_id");
        $product_article_query->execute();
        $product_article_record = $product_article_query->fetch();
        $product_article = trim($product_article_record['value']);
        if(!empty($product_article)){
            $item_article = $product_article;
        }
        $item_data['t1_article'] = $item_article;
        //Получаем склад и магазин Продукта
        $details_query = $db_link->prepare("SELECT `office_id`, `storage_id`, `storage_record_id` FROM `shop_carts_details` WHERE `cart_record_id` = $item_id;");
        $details_query->execute();
        $detail_record = $details_query->fetch();
        $office_id = (int) $detail_record['office_id'];
        $storage_id = (int) $detail_record['storage_id'];
        $storage_record_id = (int) $detail_record['storage_record_id'];
        $item_data['t1_office_id'] = $office_id;
        $item_data['t1_storage_id'] = $storage_id;
        $item_data['t1_storage_record_id'] = $storage_record_id;
    } else {
				$product_type = 2;
        $item_article = $item_data['t2_article'];
        $item_manufacturer = $item_data['t2_manufacturer'];
        $office_id = $item_data['t2_office_id'];
        $storage_id = $item_data['t2_storage_id'];
    }
    if($whrite_log) {
			$debug->logger_collapse_start("$counter Работаем с позицией - $item_id; Бренд - $item_manufacturer; Артикул - $item_article; Склад - $storage_id; Магазин - $office_id; Тип товара - $product_type; Пользователь - $item_user_id");
			$debug->logger("$counter Данные позиции", $item_data, true);
    }
    $target_product = null;
    $data = array(
        'action' => "get_brends",
        'user_id' => $item_user_id,
        'offices' => json_encode(array($office_id)),
        'article' => $item_article,
        'check' => $tech_key,
    );
    $postdata = http_build_query($data);
    if($whrite_log) {
        $debug->logger("$counter Данные запроса поставщиков", $data, true);
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "{$DP_Config->domain_path}web_service/api.php");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 300); 
    curl_setopt($curl, CURLOPT_TIMEOUT, 300);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
    $curl_result = curl_exec($curl);
    $curl_answer_brends = json_decode($curl_result, true);
    if($whrite_log) {
        $debug->logger("$counter Запрос для получения поставщиков", curl_getinfo($curl), true);
        $debug->logger("$counter Ошибка сервера", curl_error($curl), true);
    }
    curl_close($curl);
    $answer_brands = array();
    $answer_brands = $curl_answer_brends['brends'];
    if($whrite_log) {
        $debug->logger("$counter Получили поставщиков по этой позиции", $answer_brands, true);
    }
    $manufacturers_json = '';
    //Получаем бренды по артикулу
    if(is_array($answer_brands) && !empty($answer_brands)) {
        foreach($answer_brands as $brand) {
            // if($brand['manufacturer'] == $item_manufacturer) {
                if(is_array($brand['manufacturers']) && !empty($brand['manufacturers'])) {
                    foreach($brand['manufacturers'] as $brand_storage) {
                        if($manufacturers_json != '') break;
                        if ($item_data['product_type'] == 1 && $brand_storage['storage_id'] == 0 && mb_strtolower($brand_storage['manufacturer'],'utf-8') == mb_strtolower($item_manufacturer,'utf-8')) {
                            $manufacturers_json = json_encode(array($brand_storage));
                        } else if(mb_strtolower($brand_storage['manufacturer'],'utf-8') == mb_strtolower($item_manufacturer,'utf-8') && $brand_storage['storage_id'] == $storage_id)  {
                            $manufacturers_json = json_encode(array($brand_storage));
                        }
                    }
                }
            // }
        }
    }
    //Запрашиваем товары
    $data = array(
        'action' => "get_products",
        'offices' => json_encode(array($office_id)),
        'article' => $item_article,
        'manufacturers' => $manufacturers_json,
        'check' => $tech_key,
        'user_id' => $item_user_id
    );
    if ($item_data['product_type'] == 2) $data['storage_id'] = $storage_id;
    $postdata = http_build_query($data);
    if($whrite_log) {
        $debug->logger("$counter Данные запроса товаров", $data, true);
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "{$DP_Config->domain_path}web_service/api.php");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 300); 
    curl_setopt($curl, CURLOPT_TIMEOUT, 300);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
    $curl_result = curl_exec($curl);
    $curl_answer_products = json_decode($curl_result, true);
    if($whrite_log) {
        $debug->logger("$counter Запрос для получения товаров", curl_getinfo($curl), true);
        $debug->logger("$counter Ошибка сервера", curl_error($curl), true);
    }
    curl_close($curl);
    $answer_products = array();
    $answer_products = $curl_answer_products['products'];
    if($whrite_log) {
        $debug->logger("$counter Получили все товары", $answer_products, true);
    }
    $filtered_products = array();
    //Фильтруем товары
    if(!empty($answer_products)) {
        //Фильтруем по артикулу, бренду и складу
        foreach($answer_products as $answer_product) {
					if(((mb_strtolower($answer_product['manufacturer'],'utf-8') == mb_strtolower($item_manufacturer,'utf-8')) || (mb_strtolower($answer_product['manufacturer_transferred'],'utf-8') == mb_strtolower($item_manufacturer,'utf-8'))) && (mb_strtolower($answer_product['article'], 'utf-8') == mb_strtolower($item_article, 'utf-8')) && ($answer_product['storage_id'] == $storage_id)) {
						$filtered_products[] = $answer_product;
					}
        }
    }
    $filtered_to_time_products = array();
    if($whrite_log) {
        $debug->logger("$counter Отобрали товары по артикулу, производителю и складу", $filtered_products, true);
    }
    if(!empty($filtered_products)) {
        if(count($filtered_products) > 1) {
            //Фильтруем по сроку доставки
            foreach($filtered_products as $filtered_product) {
                if($filtered_product['time_to_exe'] == $item_time_to_exe) {
                    $filtered_to_time_products[] = $filtered_product;
                }
            }
            if($whrite_log) {
                $debug->logger("$counter Отобрали товары по времени доставки.", $filtered_to_time_products, true);
            }
            if(!empty($filtered_to_time_products)) {
                if(count($filtered_to_time_products) > 1) {
                    //Последний этап отбора по наличию и цене
                    //Уточнение по времени не дало результат, работает с первым массивом
                    $target_product = closestProduct($filtered_to_time_products, $item_price);
                } else {
                    $target_product = $filtered_to_time_products[0];
                }
            } else {
                //Уточнение по времени не дало результат, работает с первым массивом
                $target_product = closestProduct($filtered_products, $item_price);
            }
        } else {
            $target_product = $filtered_products[0];
        }
    }
    if($whrite_log) {
        $debug->logger("$counter Нашли искомый товар", $target_product, true);
    }
    if($item_data['product_type'] == 1) {
        //Проверям зарезервирован ли этот товар в корзине у текущего пользователя
        $details_query = $db_link->prepare("SELECT `shop_carts_details`.`count_reserved` FROM `shop_carts_details` LEFT JOIN `shop_carts` ON `shop_carts`.`id` =  `shop_carts_details`.`cart_record_id`  WHERE `shop_carts_details`.`storage_record_id` = ? AND `shop_carts`.`user_id` = ? ;");
        $details_query->execute(array($storage_record_id, $item_user_id));
        $detail_record = $details_query->fetch();
        $cart_user_reserved = 0;
        if(isset($detail_record['count_reserved'])) {
            $cart_user_reserved = (int)$detail_record['count_reserved'];
        }
        //Проверяем по складской записи наличие
        $details_query1 = $db_link->prepare("SELECT `exist`, `reserved` FROM `shop_storages_data` WHERE `id` = ?;");
        $details_query1->execute(array($storage_record_id));
        $detail_record1 = $details_query1->fetch();
        $storage_record_exist = 0;
        $storage_record_reserved = 0;
        if(isset($detail_record1['exist'])) {
            $storage_record_exist = $detail_record1['exist'];
        }
        if(isset($detail_record1['reserved'])) {
            $storage_record_reserved = $detail_record1['reserved'];
        }
        if($whrite_log) {
            $debug->logger("$counter Товар каталожный", "Наличие в складской записи: {$storage_record_exist}, {$storage_record_reserved}. Резерв у покупателя - $cart_user_reserved", true);
        }
    }
    //Товара нет, либо он весь зарезервирован. Нужно проверить, может он весь находится у пользователя в корзине
    if($target_product !== null) {
        $data_result = array(
            'В корзине по товару необходимо' => $item_count_need,
            'На складе поставщика' => (int)$target_product['exist'],
            'Текущая цена' => (int)$item_price,
            'Цена поставщика' => (int)$target_product['price']            
        );
        if($whrite_log) {
            $debug->logger("$counter Сравниваем значения", $data_result, true);
        }
        //Уменьшилось наличие
        if($item_count_need > (int)$target_product['exist']) {
            //Если изменилось количество и изменилась цена
            if((int)$item_price !== (int)$target_product['price']) {
                $text_result = updateItemCart('update_price_exist');
                if($whrite_log) {
                    $debug->logger("$counter Результирующее действие", $text_result, true);
                }
            //Изменилось только количество
            } else {
                $text_result = updateItemCart('update_exist');
                if($whrite_log) {
                    $debug->logger("$counter Результирующее действие", $text_result, true);
                }
            }
        //Наличие не изменилось, или больше чем нужно
        } else {
            //Изменилась цена
            if((int)$item_price !== (int)$target_product['price']) {
                //Проверяем если наличие в корзине было 0, но у поставщика появилось количество
                if($item_count_need == 0 && (int)$target_product['exist'] > 0) {
                    $text_result = updateItemCart('update_price_exist');
                    if($whrite_log) {
                        $debug->logger("$counter Результирующее действие", $text_result, true);
                    }
                //Изменилась только цена
                } else {
                    $text_result = updateItemCart('update_price');
                    if($whrite_log) {
                        $debug->logger("$counter Результирующее действие", $text_result, true);
                    }                    
                }
            //Цена не изменилась
            } else {
                //Проверяем если наличие в корзине было 0, но у поставщика появилось количество
                if($item_count_need == 0 && (int)$target_product['exist'] > 0) {
                    $text_result = updateItemCart('update_exist');
                    if($whrite_log) {
                        $debug->logger("$counter Результирующее действие", $text_result, true);
                    }
                //Ничего не изменилось, количество и стоимость актуальные
                } else {
                    $text_result = updateItemCart('update_time');
                    if($whrite_log) {
                        $debug->logger("$counter Результирующее действие", $text_result, true);
                    }
                }
            }
        }
    //Если товар не найден, проверяем текущую складскую запись
    } else {
        if($whrite_log) {
            $debug->logger("$counter Результирующее действие", 'Товар не найден. Наличие 0 или зарезервировано.', true);
        }
        if($item_data['product_type'] == 1) {
            //Если товар не найден и весь зарезервирован, складская запись не изменилась     
            if(((int)$storage_record_exist + (int)$storage_record_reserved) >= $cart_user_reserved) {
                $CART_ITEM_UPDATE_SQL = "
                UPDATE
                    `shop_carts`
                SET
                    `time` = ?
                WHERE
                    `id` = $item_id
                ;";
                $item_update_query = $db_link->prepare($CART_ITEM_UPDATE_SQL);
                if($item_update_query->execute(array(time())) !== true) {
                    $text_error =  print_r($db_link->errorInfo());
                    $answer['status'] = false;
                    $answer['message'] = "Ошибка обновления базы данных. Корзина - $item_id. $text_error";
                }
                if($whrite_log) {
                    $debug->logger("$counter Товар каталожный", "Обновляем время. Товар в наличии и в резерве.", true);
                }
            }
            //Товара нет в наличии
            else
            {
                if($whrite_log) {
                        $debug->logger("$counter Товар каталожный", "Проверяем сколько требовалось - $item_count_need.", true);
                }
                if($item_count_need != 0) {
                    $CART_ITEM_UPDATE_SQL = "
                    UPDATE
                        `shop_carts`
                    SET
                        `is_update` = 2,
                        `t2_exist` = '0',
                        `update_exist` = '$item_count_need',
                        `time` = ?
                    WHERE
                        `id` = $item_id
                    ;";
                    $item_update_query = $db_link->prepare($CART_ITEM_UPDATE_SQL);
                    if($item_update_query->execute(array(time())) !== true) {
                        $text_error =  print_r($db_link->errorInfo());
                        $answer['status'] = false;
                        $answer['message'] = "Ошибка обновления базы данных. Обновление наличия на ноль - Товар не найден. Корзина - $item_id. $text_error";
                    }
                    if($whrite_log) {
                        $debug->logger("$counter Товар каталожный", "Обновляем наличие 0.", true);
                    }
                }
                else
                {
                    $CART_ITEM_UPDATE_SQL = "
                        UPDATE
                            `shop_carts`
                        SET
                            `time` = ?
                        WHERE
                            `id` = $item_id
                        ;";
                    $item_update_query = $db_link->prepare($CART_ITEM_UPDATE_SQL);
                    if($item_update_query->execute(array(time())) !== true) {
                        $text_error =  print_r($db_link->errorInfo());
                        $answer['status'] = false;
                        $answer['message'] = "Ошибка обновления базы данных. Обновление времени. Корзина - $item_id. $text_error";
                    }
                    if($whrite_log) {
                        $debug->logger("$counter Товар каталожный", "Обновляем время в Базе Данных.", true);
                    }
                }
            }
        }
        //Товар не найден, и это товар от API поставщика. Резерва нет.
        else
        {
            //Проверяем, если до этого у товара было уже наличие 0, то ничего не меняем
            if($item_count_need !== 0) {
                $CART_ITEM_UPDATE_SQL = "
                UPDATE
                    `shop_carts`
                SET
                    `is_update` = 2,
                    `t2_exist` = '0',
                    `update_exist` = '$item_count_need',
                    `time` = ?
                WHERE
                    `id` = $item_id
                ;";
                $item_update_query = $db_link->prepare($CART_ITEM_UPDATE_SQL);
                if($item_update_query->execute(array(time())) !== true) {
                    $text_error =  print_r($db_link->errorInfo());
                    $answer['status'] = false;
                    $answer['message'] = "Ошибка обновления базы данных. Обновление наличия на ноль - Товар не найден. Корзина - $item_id. $text_error";
                }
                if($whrite_log) {
                    $debug->logger("$counter Результирующие действие", "Обновляем наличие 0.", true);
                }
            }
        }
    }
    if($whrite_log) {
        $debug->logger_collapse_end();
    }
 }
function updateItemCart($type, $data = array()) {
    global $db_link;
    global $item_count_need;
    global $item_id;
    global $item_data;
    global $target_product;
    global $cart_user_reserved;
    global $storage_record_reserved;
    global $storage_record_exist;
    global $item_price;
    global $storage_record_id;
    global $answer;
    $result = "";
    $exist_update_need = true;
    $count_need_reserved_from_exist = 0;
    $count_need_reserved = 0;
    $new_storage_record_id = null;
    $update_time_check = true;
    //Проверяем находится ли этот товар у пользователя в корзине
    if($item_data['product_type'] == 1) {
        $result .= "Каталожный товар. ";
        //Сначала проверяем есть ли складская запись
        if(((int)$storage_record_exist + (int)$storage_record_reserved) < 1) {
            $result .= "Складская запись отсутствует. ";
            $target_storage_id = $target_product['storage_record_id'];
            $target_exist = $target_product['exist'];
            //id складской записи изменился, но наличия хватает
            if($target_exist > $cart_user_reserved) {
                $result .= "Наличие найденного товара полностью покрывает необходимость покупателя. ";
                //Проверяем по складской записи наличие
                $details_query1 = $db_link->prepare("SELECT `exist`, `reserved` FROM `shop_storages_data` WHERE `id` = ?;");
                $details_query1->execute(array($target_storage_id));
                $detail_record1 = $details_query1->fetch();
                $target_storage_record_exist = 0;
                $target_storage_record_reserved = 0;
                if(isset($detail_record1['exist'])) {
                    $target_storage_record_exist = $detail_record1['exist'];
                }
                if(isset($detail_record1['reserved'])) {
                    $target_storage_record_reserved = $detail_record1['reserved'];
                }
                $count_need_reserved_from_exist = $cart_user_reserved;
                $new_storage_record_id = $target_storage_id;
                $storage_record_id = $new_storage_record_id;
                $storage_record_exist = $target_storage_record_exist;
                $storage_record_reserved = $target_storage_record_reserved;
                //Тут обновление наличия не нужно
                $exist_update_need = false; 
            }
            else
            {
                $result .= "Наличие найденного не хватает для текущего резерва. Обновляем резерв и наличие. ";
                $count_need_reserved_from_exist = $target_exist;
                $new_storage_record_id = $target_storage_id;
                $count_need_reserved = $target_exist;
            }
        }
        else
        {
            //Проверяем резерв текущего пользователя
            if($cart_user_reserved > 0) {
                $result .= "Есть резерв покупателя в корзине. ";
                //Необходимое количество уже зарезервированно
                if($item_count_need == $cart_user_reserved) {
                    //Резервированное количество уже есть в резерве на складе
                    if($cart_user_reserved <= $storage_record_reserved) {
                        //Тут обновление наличия не нужно
                        $exist_update_need = false; 
                    }
                    //Если складского резерва недостаточно
                    else
                    {
                        //Наличия хватает, дополнительно резервируем товар на складе из наличия
                        if($cart_user_reserved <= ($storage_record_reserved + $storage_record_exist)) {
                            $count_need_reserved_from_exist =  $cart_user_reserved - $storage_record_reserved;
                            $exist_update_need = false; 
                        }
                        //Товара не хватает
                        else
                        {
                            $result .= "Товара по резерву покупателя не хватает. Обновляем наличие в резерве. ";
                            $count_need_reserved = $target_product['exist'];
                        }
                    }
                }
            }
            //Значит товар зарезервирован у другого пользователя
            else
            {
            }
        }
    }
    switch($type) {
        case 'update_time':
            $update_time_check = false;
            $result .= 'Обновляем время в Базе Данных. ';
            $CART_ITEM_UPDATE_SQL = "
                UPDATE
                    `shop_carts`
                SET
                    `time` = ?
                WHERE
                    `id` = $item_id
                ;";
            $item_update_query = $db_link->prepare($CART_ITEM_UPDATE_SQL);
            if($item_update_query->execute(array(time())) !== true) {
                $text_error =  print_r($db_link->errorInfo());
                $answer['status'] = false;
                $result .= "Ошибка обновления базы данных. Корзина - $item_id. $text_error ";
            }
        break;
        case 'update_exist':
            if($exist_update_need) {
                $result .= 'Обновляем наличие. ';
                $target_product_exist = $target_product['exist'];
                $CART_ITEM_UPDATE_SQL = "
                    UPDATE
                        `shop_carts`
                    SET
                        `is_update` = 2,
                        `t2_exist` = '$target_product_exist',
                        `update_exist` = '$item_count_need',
                        `time` = ?
                    WHERE
                        `id` = $item_id
                    ;";
                $item_update_query = $db_link->prepare($CART_ITEM_UPDATE_SQL);
                if($item_update_query->execute(array(time())) !== true) {
                    $text_error =  print_r($db_link->errorInfo());
                    $answer['status'] = false;
                    $result .= "Ошибка обновления базы данных. Обновление наличия. Корзина - $item_id. $text_error ";
                }
            }
            else
            {
                //Тогда просто обновляем время
                $result .= 'Обновляем время в Базе Данных. ';
                $CART_ITEM_UPDATE_SQL = "
                    UPDATE
                        `shop_carts`
                    SET
                        `time` = ?
                    WHERE
                        `id` = $item_id
                    ;";
                $item_update_query = $db_link->prepare($CART_ITEM_UPDATE_SQL);
                if($item_update_query->execute(array(time())) !== true) {
                    $text_error =  print_r($db_link->errorInfo());
                    $answer['status'] = false;
                    $result .= "Ошибка обновления базы данных. Корзина - $item_id. $text_error ";
                }
            }
            break;
        case 'update_price':
            $result .= 'Обновляем стоимость. ';
            $target_product_price = $target_product['price'];
            $CART_ITEM_UPDATE_SQL = "
                UPDATE
                    `shop_carts`
                SET
                    `price` = $target_product_price,
                    `is_update` = 1,
                    `update_price` = '$item_price',
                    `time` = ?
                WHERE
                    `id` = $item_id
                ;";
            $item_update_query = $db_link->prepare($CART_ITEM_UPDATE_SQL);
            if($item_update_query->execute(array(time())) !== true) {
                $text_error =  print_r($db_link->errorInfo());
                $answer['status'] = false;
                $result .= "Ошибка обновления базы данных. Обновление цены. Корзина - $item_id. $text_error ";
            }
            break;
        case 'update_price_exist':
            $result .= 'Обновляем наличие и стоимость. ';
            $target_product_price = $target_product['price'];
            $target_product_exist = $target_product['exist'];
            $CART_ITEM_UPDATE_SQL = "
                UPDATE
                    `shop_carts`
                SET
                    `price` = $target_product_price,
                    `is_update` = 3,
                    `t2_exist` = '$target_product_exist',
                    `update_price` = '$item_price',
                    `update_exist` = '$item_count_need',
                    `time` = ?
                WHERE
                    `id` = $item_id
                ;";
            $item_update_query = $db_link->prepare($CART_ITEM_UPDATE_SQL);
            if($item_update_query->execute(array(time())) !== true) {
                $text_error =  print_r($db_link->errorInfo());
                $answer['status'] = false;
                $result .= "Ошибка обновления базы данных. Обновление наличия и стоимости. Корзина - $item_id. $text_error ";
            }
            break;
    }
    //Меняем в складской записи количества резерва из наличия
    if(!empty($count_need_reserved_from_exist)) {
        $result .= "Меняем в складской записи количества резерва из наличия. ";
        if($db_link->prepare("UPDATE `shop_storages_data` SET `exist` = ?, `reserved` = ? WHERE `id` =  ?;")->execute( array(($storage_record_exist-$count_need_reserved_from_exist),($storage_record_reserved+$count_need_reserved_from_exist),$storage_record_id) ) != true)
        {
          $text_error =  print_r($db_link->errorInfo());
          $answer['status'] = false;
          $result .= "Ошибка резервирования складской записи $storage_record_id. $text_error ";
        }
    }
    //Записываем новый ID складской записи
    if(!empty($new_storage_record_id)) {
        $result .= "Записываем новый ID складской записи. ";
        if($db_link->prepare("UPDATE `shop_carts_details` SET `storage_record_id` = ? WHERE `cart_record_id` =  ?;")->execute( array($new_storage_record_id, $item_id) ) != true)
        {
          $text_error =  print_r($db_link->errorInfo());
          $answer['status'] = false;
          $result .= "Ошибка записи нового ID складской записи в корзине $item_id. $text_error ";
        }
    }
    //Записываем новый резерв в корзине
    if(!empty($count_need_reserved)) {
        $result .= "Записываем новый резерв в корзине. ";
        if($db_link->prepare("UPDATE `shop_carts_details` SET `count_reserved` = ? WHERE `cart_record_id` =  ?;")->execute( array($count_need_reserved, $item_id) ) != true)
        {
          $text_error =  print_r($db_link->errorInfo());
          $answer['status'] = false;
          $result .= "Ошибка записи нового количества резерва в корзине $item_id. $text_error ";
        }
    }
    //Обновляем время
    if($update_time_check) {
    }
    return $result;
}
function closestProduct($products, $item_price){
    $negative_ = array();
    $positive_ = array();
    $result = null;
    $p_key = 0;
    if(!empty($products) && is_array($products)) {
        foreach($products as $key => $product_data) {
            $diff = (int)$product_data['price'] - (int)$item_price;
            ($diff < 0) ? $negative_[$key] = $diff : $positive_[$key] = $diff;
        }
        asort($negative_);
        asort($positive_);
        $val_n = abs(end($negative_));
        $key_n = key($negative_);
        $val_p = abs(reset($positive_));
        $key_p = key($positive_);
        if (!empty($negative_) && ($val_n == $val_p || $val_n < $val_p)) {
            $p_key = $key_n;
        } else if(!empty($positive_)) {
            $p_key = $key_p;
        } else {
           $products[0];
        }
        $result = $products[$p_key];
    }
    return $result;
}
exit(json_encode($answer));
?>