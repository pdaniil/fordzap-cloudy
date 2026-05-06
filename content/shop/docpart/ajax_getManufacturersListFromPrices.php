<?php
/**
 * Серверный скрипт для получения списка производителей по артикулу от прайс листов
*/
header('Content-Type: application/json;charset=utf-8;');

//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

// --------------------------------------------------------------------------------------
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
	return;
}
$db_link->query("SET NAMES utf8;");
// --------------------------------------------------------------------------------------

class prices_enclosure
{
	public $status;
	public $time;//Время запроса
	public $ProductsManufacturers = array();//Список брендов
    public $storage = "Прайс-листы";
	
	public function __construct($query, $office_storage_bunches, $DP_Config)
	{
		global  $db_link;

		/*****Учетные данные*****/
		$article = $query["article"];
		/*****Учетные данные*****/

		// Получаем список складов что бы подставить id склада в объект найденных брендов
		$storages = array();
		$SQL = "SELECT * FROM `shop_storages` WHERE `interface_type` = 2;";
		$storages_query = $db_link->prepare( $SQL );
		$storages_query->execute();
		while($storage = $storages_query->fetch())
        {
			if($storage['interface_type'] == 2){
				$connection_options = json_decode($storage['connection_options'], true);
				$storages[(int)$connection_options['price_id']] = $storage['id'];
			}
		}
		
		//Формируем массив id прайс листов для опроса
		$prices_list = array();
		$cnt = count($office_storage_bunches);
		for($i=0; $i < $cnt; $i++)
		{
			$storage_id = $office_storage_bunches[$i]["storage_id"];
			
			//Получаем данные склада что бы выбрать id подключенных прайсов
			$storage_query = $db_link->prepare('SELECT `connection_options` FROM `shop_storages` WHERE `id` = ?;');
			$storage_query->execute( array($storage_id) );
			$storage_record = $storage_query->fetch();
			$connection_options = json_decode($storage_record["connection_options"], true);
			$price_id = $connection_options["price_id"];
			
			array_push($prices_list, (int)$price_id);
		}
		
		//Формируем SQL-запрос
		$SQL = "SELECT * FROM `shop_docpart_prices_data` WHERE `article` = ? AND `price_id` IN(" . implode( ',', array_fill( 0, count( $prices_list ), '?' ) ) .");";
		
		//Делаем запрос по артикулу
		$products_query = $db_link->prepare( $SQL );
		
		$binds = array();
		array_push($binds, $article);

		$binds = array_merge( $binds, $prices_list );
		
		$products_query->execute( $binds );
		
		//Фильтруем повторяющихся
		$hashes = array();
		
        while($product = $products_query->fetch() )
        {
			$DocpartManufacturer = new DocpartManufacturer($product["manufacturer"],
			    0,
				$product["name"],
				0,
				(int)$storages[(int)$product['price_id']],
				true,
				array('type'=>'prices')
			);
			
			if($DocpartManufacturer->valid === true){
				
				//Получаем хеш
				$hash = md5($DocpartManufacturer->manufacturer);
				
				//Поиск хеша
				if (!isset($hashes[$hash])){
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
					$hashes[$hash] = true;
				}
			}
        }
		
		
        $this->status = true;
	}//~function __construct($article)
};//~class prices_enclosure


$time_start = microtime(true);


$ManufacturersList = new prices_enclosure( 
	json_decode($_POST["query"], true), 
	json_decode($_POST["office_storage_bunches"], true ), 
	$DP_Config
);



$time_end = microtime(true);
$ManufacturersList->time = number_format(($time_end - $time_start), 3, '.', '');

if (isset($_POST["is_async_search"]) && $_POST["is_async_search"] == 1)
{
	try 
	{
		//СИНОНИМЫ
		$synonyms = array();
		$synonym_query = $db_link->prepare("SELECT `synonym`, (SELECT `name` FROM `shop_docpart_manufacturers` WHERE `id` = `shop_docpart_manufacturers_synonyms`.`manufacturer_id`) AS 'name' FROM `shop_docpart_manufacturers_synonyms`;");
		$synonym_query->execute();
		while($synonym_record = $synonym_query->fetch()){
			$synonyms[mb_strtoupper(str_replace('"',"'",$synonym_record["synonym"]), 'UTF-8')] = mb_strtoupper(str_replace('"',"'",$synonym_record["name"]), 'UTF-8');
		}

		foreach($ManufacturersList->ProductsManufacturers as &$manufacturer)
		{
			$synonym = null;
			$manufacturer = (array)$manufacturer;

			if( isset($synonyms[mb_strtoupper(str_replace('"',"'",$manufacturer["manufacturer"]), 'UTF-8')]) )
			{
				$synonym = $synonyms[mb_strtoupper(str_replace('"',"'",$manufacturer["manufacturer"]), 'UTF-8')];
			}
			else
			{
				$synonym = "";
			}
			if(!empty($synonym))
			{
				$manufacturer['manufacturer_show'] = mb_strtoupper($synonym, 'UTF-8');
			}
		}
	}
	catch (PDOException $e) 
	{	
		exit();	
	}
}

exit(json_encode($ManufacturersList));
?>