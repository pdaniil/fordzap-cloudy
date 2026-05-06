<?php
/**
 * API Docpart - метод "get_products" - получение списка товаров
*/
defined('_API_') or die('No access');



//Входные параметры:
$article = $_POST["article"];
$manufacturers = json_decode($_POST["manufacturers"], true);//Может быть пустым



//Обработка входных параметров
$article = mb_strtoupper(preg_replace("/[^a-zA-Zа-яА-ЯёЁ0-9]+/", "", $article), "UTF-8");
if(empty($article)){
	$answer = array();
	$answer["result"] = false;
	$answer["message"] = "Incorrect article";
	exit( json_encode($answer) );
}

if(is_array($manufacturers)){
	foreach($manufacturers as $manufacturer){
		if(empty($manufacturer['manufacturer'])){
			$answer = array();
			$answer["result"] = false;
			$answer["message"] = "Incorrect manufacturers";
			exit( json_encode($answer) );
		}
	}
}



//Подключаем массив групп складов для опроса - $storages_groups
require_once($_SERVER["DOCUMENT_ROOT"]."/web_service/inc_storages_groups.php");



//Данные запроса
$search_object = array();
$search_object['user_id'] = $customer['user_id'];
$search_object['check'] = $DP_Config->tech_key;
$search_object['article'] = $article;
$search_object['manufacturers'] = $manufacturers;



//Общий список найденных позиций товаров
$products = array();

// ----------------------------------------------------------------------------------------------------------------------------------------------------

//Функция получения списка аналогов
function getAnalogsList()
{
	global $search_object, $DP_Config;
	
	$request_object = array();
	$request_object['article'] = $search_object['article'];
	$request_object['manufacturers'] = array();
	
	$manufacturers = $search_object['manufacturers'];
	for($m = 0, $cnt_m = count($manufacturers); $m < $cnt_m; $m++)
	{
		// Отфильтровываем строки из прайс листов, так как запрос аналогов нужно делать по производителю который был передан складом или сервером кроссов
		if( !empty($manufacturers[$m]['params']) ){
			if($manufacturers[$m]['params']['type'] === 'prices'){
				continue;
			}
		}
		$request_object['manufacturers'][] = $manufacturers[$m];
	}
	
	$postdata = http_build_query(
		array(
			'search_object' => json_encode($request_object)
		)
	);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path."content/shop/docpart/ajax_getAnalogsList.php");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30); 
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	$curl_result = curl_exec($curl);
	curl_close($curl);
	
	$answer = json_decode($curl_result, true);
	
	if($answer['result'] == 1)//Запрос выполнен успешно
	{
		$search_object['analogs'] = $answer['analogs'];
	}
}

// ----------------------------------------------------------------------------------------------------------------------------------------------------

//Функция получения списка найденных позиций товаров
function getStoragesData()
{
	global $search_object, $storages_groups, $products, $DP_Config;
	
	$request_storages = array();// Список складов для запроса к скрипту ajax_asynchron.php
	
	$article = $search_object['article'];
	$manufacturers = $search_object['manufacturers'];
	
	// Цикл по группам
	for($g = 0, $cnt_g = count($storages_groups); $g < $cnt_g; $g++)
	{
		if($storages_groups[$g]['sent'] == 1){
			continue;// Пропускаем опрошенную группу
		}
		
		// Делаем группу опрошенной
		$storages_groups[$g]['sent'] = 1;
		
		// Клонируем объект search_object потому что он будет у каждого склада свой
		$search_object_clone = $search_object;
		
		for($i = 0, $cnt_i = count($storages_groups[$g]['storages']); $i < $cnt_i; $i++)
		{
			$search_object_clone['office_storage_bunches'] = array();
			$search_object_clone['manufacturers'] = array();
			
			//Если это поставщик с версией протокола 2 - создаем для него список нужных производителей
			if( $storages_groups[$g]['storages'][$i]['protocol_version'] == 2 )
			{
				if( empty($manufacturers) )
				{
					// Если запрос производителя не дал результата то пропускаем склад если он api поставщик
					// В каталоге нужно сделать поиск так как запрос может быть по наименованию и могут найтись аналоги
					if($storages_groups[$g]['storages'][$i]['treelax_catalogue'] === false){
						continue;
					}
				}
				else
				{
					for($m = 0, $cnt_m = count($manufacturers); $m < $cnt_m; $m++)
					{
						if( 
							((int)$manufacturers[$m]['storage_id'] == (int)$storages_groups[$g]['storages'][$i]['storage_id']) &&
							((int)$manufacturers[$m]['office_id'] == (int)$storages_groups[$g]['storages'][$i]['office_id'])
						)
						{
							$search_object_clone['manufacturers'][] = $manufacturers[$m];//Добавляем сюда строку именно из API поставщика
						}
					}
				}
				
				//Если список нужных производителей пуст - значит у данного поставщика нет таких производителей - пропускаем, чтобы не тратить время
				if( empty($search_object_clone['manufacturers']) )
				{
					if($storages_groups[$g]['storages'][$i]['treelax_catalogue'] === false){
						continue;
					}else{
						if( !empty($manufacturers) ){
							$search_object_clone['manufacturers'][] = $manufacturers[0];
						}
					}
				}
			}
			else if($storages_groups[$g]['storages'][$i]['protocol_version'] == 3)//Для прайс-листов
			{
				if( !empty($manufacturers) ){
					for($m = 0, $cnt_m = count($manufacturers); $m < $cnt_m; $m++)
					{
						if( 
							((int)$manufacturers[$m]['storage_id'] == (int)$storages_groups[$g]['storages'][$i]['storage_id']) &&
							((int)$manufacturers[$m]['office_id'] == (int)$storages_groups[$g]['storages'][$i]['office_id'])
						)
						{
							// Только найденные бренды от прайсов
							if( !empty($manufacturers[$m]['params']) ){
								if($manufacturers[$m]['params']['type'] == 'prices'){
									$search_object_clone['manufacturers'][] = $manufacturers[$m];//Добавляем сюда строку именно из прайса
								}
							}
						}
					}
					// Добавляем хоть что то что бы в прайсах не было поиска только по артикулу, так как возможно что то найдется в аналогах.
					if( empty($search_object_clone['manufacturers']) )
					{
						$search_object_clone['manufacturers'][] = $manufacturers[0];
					}
				}
				
				//В объект запроса добавляем связки складов и магазинов для прайс-листов
				$search_object_clone['office_storage_bunches'] = $storages_groups[$g]['storages'][$i]['office_storage_bunches'];
			}
			
			// Убираем из запроса аналоги для складов которым они не нужных
			if($storages_groups[$g]['storages'][$i]['protocol_version'] != 3 && $storages_groups[$g]['storages'][$i]['treelax_catalogue'] === false){
				$search_object_clone['analogs'] = array();
			}
			
			// Добавляем к складу список его производителей
			$storages_groups[$g]['storages'][$i]['search_object'] = $search_object_clone;
			
			
			// Добавляем склад в запрос
			$request_storages[] = $storages_groups[$g]['storages'][$i];
		}
		
		// Делаем запрос
		
		// Если есть данные для запроса
		if( !empty($request_storages) ){
			
			$request_object = array();
			$request_object['action'] = 'get_articles';
			$request_object['article'] = $article;
			$request_object['storages'] = $request_storages;
			$request_object['check'] = $search_object['check'];
			$request_object['user_id'] = $search_object['user_id'];
			$request_object['geo_id'] = $request_storages[0]['geo_id'];
			
			$postdata = http_build_query(
				array(
					'request_object' => json_encode($request_object)
				)
			);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path."content/shop/docpart/ajax_asynchron.php");
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30); 
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			$curl_result = curl_exec($curl);
			curl_close($curl);
			
			$answer = json_decode($curl_result, true);
			
			if($answer['result'] == 1)//Запрос выполнен успешно
			{
				if( !empty($answer['data']) ){
					if( is_array($answer['data']) ){
						for($i = 0, $cnt_i = count($answer['data']); $i < $cnt_i; $i++){
							if( !empty($answer['data'][$i]) ){
								if(count($answer['data'][$i]['Products']) > 0){
									foreach($answer['data'][$i]['Products'] as $item){
										$products[] = $item;// Позиция товара
									}
								}
							}
						}
					}
				}
			}
			
			getStoragesData();//Делаем следующий запрос
			return;
		}else{
			// В группе для опроса не было складов - все склады были типа 2 и для них не было производителей, поэтому их все пропустили
			
			getStoragesData();//Делаем следующий запрос
			return;
		}
	}
	// Все склады опрошены
}
// ---------------------------------------------------------------------------------------------------------------------

getAnalogsList();//Запрос аналогов
getStoragesData();//Запрос позиций товаров

/*
echo '<pre>';
var_dump($products);
echo '</pre>';
exit;
*/

//Убираем из объекта позиции лишние данные если запрос идет не из 1С а от обычного пользователя
if(trim($_POST["check"]) !== $DP_Config->tech_key){
	if( !empty($products) ){
		$products_tmp = array();
		
		foreach($products as $item){
			unset($item['storage_caption']);//Наименование склада
			unset($item['price_purchase']);//Закупочная цена
			unset($item['markup']);//Наценка
			unset($item['product_type']);//Тип продукта
			unset($item['product_id']);//ID продукта
			unset($item['storage_record_id']);//ID складской записи продукта
			unset($item['product_url']);
			unset($item['json_params']);
			unset($item['valid']);
			unset($item['check_hash']);
			unset($item['url']);//Адрес страницы продукта каталога
			unset($item['manufacturer_transferred']);//Наименование бренда как передал склад
			
			$products_tmp[] = $item;
		}
		
		$products = $products_tmp;
	}
}


/*
echo '<pre>';
var_dump($products);
echo '</pre>';
exit;
*/


$answer = array();
$answer["result"] = true;
$answer["message"] = "";
$answer["products"] = $products;
exit( json_encode($answer) );
?>