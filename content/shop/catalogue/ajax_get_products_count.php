<?php
/**
 * Серверный скрипт для получения количества товаров без учета лимита по страницам
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
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


//ДЛЯ РАБОТЫ С ПОЛЬЗОВАТЕЛЕМ
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$userProfile = DP_User::getUserProfile();
$group_id = $userProfile["groups"][0];//Берем первую группу пользователя


//Получить список магазинов покупателя
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");


//Получаем объект запроса
$propucts_request = json_decode($_POST["propucts_request"], true);


// --- // --- --- // --- --- // --- --- // --- --- // --- --- // --- --- // --- --- // --- --- // --- --- // ---
// ---
$sw_list = array("'", '"', '`', '%', '--', '..', '\\', '/', "\n", "\r", "\t", "UNION", "SELECT", "AND", "FROM", "information_schema", "script", "passwd");

if(isset($propucts_request["products_ids_str"]))
{
	$products_ids_str = $propucts_request["products_ids_str"];
	$arr = explode(',', $products_ids_str);
	$products_ids_str = '';
	$propucts_request["products_ids_str"] = '';
	foreach($arr as $itm){
		if($products_ids_str != ''){
			$products_ids_str .= ', ';
		}
		$products_ids_str .= (int) trim($itm) * 1;
	}
	$propucts_request["products_ids_str"] = $products_ids_str;
	unset($products_ids_str);
}

if(isset($propucts_request["search_string"])){
	$propucts_request["search_string"] = trim(str_replace($sw_list, '', $propucts_request["search_string"]));
}
if(isset($propucts_request["search"])){
	$propucts_request["search"] = trim(str_replace($sw_list, '', $propucts_request["search"]));
}
if(isset($propucts_request["category_id"])){
	$propucts_request["category_id"] = (int) $propucts_request["category_id"] * 1;
}
if(isset($propucts_request["product_block_type"])){
	$propucts_request["product_block_type"] = (int) $propucts_request["product_block_type"] * 1;
}
if(isset($propucts_request["productsPerPage"])){
	$propucts_request["productsPerPage"] = (int) $propucts_request["productsPerPage"] * 1;
}
if(isset($propucts_request["needPagesCount"])){
	$propucts_request["needPagesCount"] = (int) $propucts_request["needPagesCount"] * 1;
}
if(isset($propucts_request["startFrom"])){
	$propucts_request["startFrom"] = (int) $propucts_request["startFrom"] * 1;
}
if(isset($propucts_request["page_style"])){
	$propucts_request["page_style"] = (int) $propucts_request["page_style"] * 1;
}
// ---
// --- // --- --- // --- --- // --- --- // --- --- // --- --- // --- --- // --- --- // --- --- // --- --- // ---


$category_id = $propucts_request["category_id"];
$properties_list = $propucts_request["properties_list"];
$product_block_type = $propucts_request["product_block_type"];//Тип страницы (1 - отображения для покупателя; 2 - для администратора каталога; 3 - для кладовщика; 4 - для покупателя при поиске через текстовую строку)


//Сначала получаем список товаров, которые подходят по запросу:
$search_string = '';
if( isset($propucts_request["search_string"]) )
{
	$search_string = trim(htmlspecialchars(strip_tags($propucts_request["search_string"])));
}
if($search_string !== ''){
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/text_search_algorithm.php");//ЕДИНЫЙ АЛГОРИТМ ПОИСКА ТОВАРА ПО ТЕКСТОВОЙ СТРОКЕ
	
	//Составим строку с id товаров вида (1,2,3). $products_list - массив с id товаров, который заполнен в скрипте единого алгоритма
	$products_ids_str = "";
	for($i=0; $i < count($products_list); $i++)
	{
		$products_list[$i] = (int)$products_list[$i];
		
		if($products_ids_str != "") $products_ids_str = $products_ids_str.",";
		$products_ids_str = $products_ids_str.$products_list[$i];
	}
	
	if($products_ids_str === "")
	{
		$products_ids_str = "0";
	}
}

//Подключение построение запроса
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_all.php");


$SQL = 'SELECT COUNT(DISTINCT(`id`)) AS `count_total` FROM ('.$SQL.') AS `all`;';

$count_total_query = $db_link->prepare($SQL);
$count_total_query->execute($sql_args_array);
$count_total_record = $count_total_query->fetch();

echo (int) $count_total_record["count_total"];
?>