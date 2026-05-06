<?php
/**
 * API Docpart - метод "get_brends" - получение списка производителей
*/
defined('_API_') or die('No access');



//Входные параметры:
$article = $_POST["article"];



//Обработка входных параметров
$article = mb_strtoupper(preg_replace("/[^a-zA-Zа-яА-ЯёЁ0-9]+/", "", $article), "UTF-8");
if(empty($article)){
	$answer = array();
	$answer["result"] = false;
	$answer["message"] = "Incorrect article";
	exit( json_encode($answer) );
}


//Подключаем массив групп складов для опроса - $storages_groups
require_once($_SERVER["DOCUMENT_ROOT"]."/web_service/inc_storages_groups.php");



//Список производителей
$ProductsManufacturers = array();



//Функция добавляения нового производителя в общий массив
function addManufacturersToList($products_manufacturers)
{
	global $ProductsManufacturers;
	
	for($i=0; $i < count($products_manufacturers); $i++)
	{
		if(!empty($products_manufacturers[$i]['manufacturer'])){
			$ProductsManufacturers[] = $products_manufacturers[$i];
		}
	}
}



// ----------------------------------------------------------------------------------------------------------------------------------------------------



$ProductsManufacturers_All_Asked = false;//Флаг - обозначает, что все поставщики с типом протокола 2 опрошены
$manufacturersListFromPrices = false;//Флаг - Список производителей запросили из прайсов
$has_crosses_query = false;//Флаг - Список производителей запросили с сервера кроссов

//Функция получения списка производителей
function getManufacturersList()
{
	global $ProductsManufacturers_All_Asked, $manufacturersListFromPrices, $has_crosses_query, $storages_groups, $article, $customer, $DP_Config;
	
	$request_storages = array();// Список складов для запроса к скрипту ajax_asynchron.php
	
	if($ProductsManufacturers_All_Asked == false){
		
		// Если не все склады опрошены
		
		// Цикл по группам складов
		for($g=0; $g < count($storages_groups); $g++)
		{
			if($storages_groups[$g]['manufacturers_sent'] == 1)
			{
				continue;// Опрошенная группа - пропускаем
			}
				
			// Цикл по списку складов в группе
			for($i=0; $i < count($storages_groups[$g]['storages']); $i++)
			{
				//Поставщика с версией протокола 1 - пропускаем
				if($storages_groups[$g]['storages'][$i]['protocol_version'] === 1)
				{
					continue;
				}
				
				// Добавляем склад в запрос
				$request_storages[] = $storages_groups[$g]['storages'][$i];
			}
			
			// Если работаем с последней группой складов то в нее нужно добавить опрос сервера кроссов
			if($g == (count($storages_groups)-1)){
				if( !$has_crosses_query )
				{
					$has_crosses_query = true;//Опросили.
					$server = array();
					$server['protocol_version'] = 'server';
					$request_storages[] = $server;
				}
				
				// Флаг - все склады опрошены
				$ProductsManufacturers_All_Asked = true;
			}
			
			$storages_groups[$g]['manufacturers_sent'] = 1;// Отмечаем что группа опрошена и выходим из цикла
			break;
		}
		
		// Если есть данные для запроса
		if(count($request_storages) > 0){
			
			$request_object = array();
			$request_object['action'] = 'get_manufacturers';
			$request_object['article'] = $article;
			$request_object['storages'] = $request_storages;
			$request_object['user_id'] = $customer['user_id'];
			$request_object['geo_id'] = $request_storages[0]['geo_id'];
			$request_object['check'] = $DP_Config->tech_key;
			
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
				if(count($answer['data']) > 0){
					for($i = 0; $i < count($answer['data']); $i++){
						if($answer['data'][$i] != null){
							addManufacturersToList($answer['data'][$i]['ProductsManufacturers']);//Добавляем результат в общий объект
						}
					}
				}
			}
			getManufacturersList();//Делаем следующий запрос
			return;
			
		}else{
			// В группе для опроса были только склады с 1 типом интерфейса поэтому объект пуст и нужно перейти к опросу следующей группы
			getManufacturersList();//Делаем следующий запрос
			return;
		}
		
	}else{
		// Все склады опрошены
		return;
	}
}


// ----------------------------------------------------------------------------------------------------------------------------------------------------



//Получение списка производителей
getManufacturersList();

$ProductsManufacturers_Shown = array();//Конечный массив поставщиков

if(!empty($ProductsManufacturers)){
	for($i=0; $i < count($ProductsManufacturers); $i++)
	{
		//Если это первый такой производитель - создаем для него массив всех объектов
		if( !isset($ProductsManufacturers_Shown[$ProductsManufacturers[$i]['manufacturer_show']]) )
		{
			$ProductsManufacturers_Shown[$ProductsManufacturers[$i]['manufacturer_show']] = array();
			
			// Если у элемента не было указано наименование, возьмем его из последующих
			if( empty($ProductsManufacturers[$i]['name']) )
			{
				for($j=0; $j < count($ProductsManufacturers); $j++)
				{
					if($ProductsManufacturers[$i]['manufacturer_show'] === $ProductsManufacturers[$j]['manufacturer_show']){
						if( !empty($ProductsManufacturers[$j]['name']) )
						{
							$ProductsManufacturers[$i]['name'] = $ProductsManufacturers[$j]['name'];
							break;
						}
					}
				}
			}
		}
		
		//Добавляем объект
		$ProductsManufacturers_Shown[$ProductsManufacturers[$i]['manufacturer_show']][] = $ProductsManufacturers[$i];
	}
}



$brends = array();

if(!empty($ProductsManufacturers_Shown)){
	foreach($ProductsManufacturers_Shown as $k => $v){
		$manufacturers = array();
		foreach($v as $item){
			$manufacturers[] = $item;
		}
		$brends[] = array("manufacturer" => $k, "article" => $article, "name" => $v[0]['name'], "manufacturers" => $manufacturers);
	}
}


/*
echo '<pre>';
var_dump($brends);
echo '</pre>';
exit;
*/


$answer = array();
$answer["result"] = true;
$answer["message"] = "";
$answer["brends"] = $brends;
exit( json_encode($answer) );
?>