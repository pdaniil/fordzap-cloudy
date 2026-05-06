<?php
/*

ДОКУМЕНТАЦИЯ: https://wiki.euroauto.ru/display/base/WHLS+API

Если нет интерфейса склада, тогда создаем:

INSERT INTO `shop_storages_interfaces_types` (`id`, `product_type`, `name`, `description`, `connection_options`, `handler_folder`) VALUES (NULL, '2', 'API EuroAuto(euroauto.ru)', '', '[{"name":"login","type":"text","caption":"Логин"},{"name":"password","type":"text","caption":"Пароль"},{"name":"token","type":"text","caption":"Token - Не заполнять, будет заполнено автоматически"},{"name":"expires","type":"text","caption":"Expires - Не заполнять, будет заполнено автоматически"},{"name":"view_sh","type":"checkbox","caption":"Показывать б/у в проценке"},{"name":"probability","type":"text","caption":"Вероятность поставки в %"},{"name":"color","type":"color","caption":"Цвет"}]', 'euroauto');

Если интерфейс склада есть тогда изменяем значение поля "connection_options"

[{"name":"login","type":"text","caption":"Логин"},{"name":"password","type":"text","caption":"Пароль"},{"name":"token","type":"text","caption":"Token - Не заполнять, будет заполнено автоматически"},{"name":"expires","type":"text","caption":"Expires - Не заполнять, будет заполнено автоматически"},{"name":"view_sh","type":"checkbox","caption":"Показывать б/у в проценке"},{"name":"probability","type":"text","caption":"Вероятность поставки в %"},{"name":"color","type":"color","caption":"Цвет"}]

*/
header('Content-Type: application/json;charset=utf-8;');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class euroauto_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		$this->no_error = false;//По умолчанию
        
        
		/*****Учетные данные*****/
		$storage_id = $storage_options["storage_id"];
        $login = trim($storage_options["login"]);
        $password = trim($storage_options["password"]);
        $token = trim($storage_options["token"]);
        $expires = trim($storage_options["expires"]);
        $view_sh = trim($storage_options["view_sh"]);
		/*****Учетные данные*****/
		
		
		if(empty($token) || $expires < (time()+120)){
			//Получение токена для авторизации
			
			$postdata = http_build_query(
				array(
					'login' => $login,
					'password' => $password
				)
			);//Аргументы
			
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, "https://whls.euroauto.ru/api/auth");
			curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept: application/json"));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20); 
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			$curl_result = curl_exec($curl);
			curl_close($curl);
			
			$data = json_decode($curl_result, true);
			$token = $data['data']['token'];
			$expires = $data['data']['expires'];
			
			//ЛОГ [ПОСЛЕ API-запроса] (название запроса, ответ, обработанный ответ)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_after_api_request("Получение токена для авторизации", $curl_result, "Token: ".$token);
			}
			
			if(!empty($token) && !empty($expires)){
				//Конфигурация Treelax
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
				
				$storage_query = $db_link->prepare('SELECT `connection_options` FROM `shop_storages` WHERE `id` = ?;');
				$storage_query->execute( array($storage_id) );
				$storage_record = $storage_query->fetch();
				$connection_options = json_decode($storage_record['connection_options'], true);
				if(!empty($connection_options)){
					$connection_options['token'] = $token;
					$connection_options['expires'] = $expires;
					$connection_options = json_encode($connection_options);
					if(!empty($connection_options)){
						$storage_query = $db_link->prepare('UPDATE `shop_storages` SET `connection_options` = ? WHERE `id` = ?;');
						$storage_query->execute( array($connection_options, $storage_id) );
					}
				}
			}
		}
		
		//Получение списка производителей
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://whls.euroauto.ru/api/products/manufacturers/codes?code=".$article);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$token, "Accept: application/json"));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		$curl_result = curl_exec($curl);
		curl_close($curl);
		
		$manufacturers_all = json_decode($curl_result, true);
		
		//echo '<pre>';
		//var_dump($manufacturers_all);
		//echo '</pre>';
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка производителей по артикулу ".$article, "Вызов метода https://whls.euroauto.ru/api/products/manufacturers/codes?code=".$article, "См. ответ API после обработки", print_r($manufacturers_all, true) );
		}
		
		
		//ЛОГ [ПОСЛЕ API-запроса] (название запроса, ответ, обработанный ответ)
		$manufacturers = array();
		if(!empty($manufacturers_all['data'])){
			foreach($manufacturers_all['data'] as $item_manufacturer){
				$manufacturers[] = $item_manufacturer['manufacturer']['name'];
			}
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_after_api_request("Список производителей по артикулу ".$article, "", print_r($manufacturers, true));
			}
		}
		
		if(!empty($manufacturers_all['data'])){
			foreach($manufacturers_all['data'] as $item_manufacturer){
				$manufacturer = $item_manufacturer['manufacturer']['name'];
				$manufacturer_id = $item_manufacturer['manufacturer']['id'];
				$name = $item_manufacturer['name']['name'];
				
				//echo '<pre>';
				//var_dump($manufacturer);
				//echo '</pre>';
				
				//Получение товаров в наличии по артикулу производителя {new}
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, "https://whls.euroauto.ru/api/offers/products/manufacturers/".$manufacturer_id."/new?code=".$article."&replacements=false");
				curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$token, "Accept: application/json"));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, false);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20); 
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				$curl_result = curl_exec($curl);
				curl_close($curl);
				
				$products_all = json_decode($curl_result, true);
				
				//echo '<pre>';
				//var_dump($products_all);
				//echo '</pre>';
				//echo '<hr/>';
				
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение товаров в наличии по артикулу производителя {new} - ".$article." - ".$manufacturer, "Вызов метода https://whls.euroauto.ru/api/offers/products/manufacturers/".$manufacturer_id."/new?code=".$article."&replacements=true", "См. ответ API после обработки", print_r($products_all, true) );
				}
				
				if(!empty($products_all['data']['offers'])){
					foreach($products_all['data']['offers'] as $item_offer){
						if(!empty($item_offer['product_id'])){
							
							$product_id = $item_offer['product_id'];
							$store_id = $item_offer['store_id'];
							$price = $item_offer['price'];
							$exist = $item_offer['quantity'];
							
							$product = $products_all['data']['products'][$product_id];
							
							$name = $product['name']['name'] .' -/- ID: '. $product_id;
							$manufacturer_code = $product['manufacturer_code'];
							
							$manufacturer_codes = $products_all['data']['manufacturer_codes'][$manufacturer_code];
							
							$product_article = $manufacturer_codes['code'];
							$product_manufacturer = $manufacturer_codes['manufacturer']['name'];
							
							$storage = $products_all['data']['stores'][$store_id]['name'];
							$min_order = 1;
							$markup = 0;
							
							//Создаем объек товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct($product_manufacturer,
								$product_article,
								$name,
								$exist,
								$price,
								$storage_options["additional_time"],
								$storage_options["additional_time"],
								$storage,
								$min_order,
								$storage_options["probability"],
								$storage_options["office_id"],
								$storage_options["storage_id"],
								$storage_options["office_caption"],
								$storage_options["color"],
								$storage_options["storage_caption"],
								$price,
								$markup,
								2,0,0,'',$json,array("rate"=>$storage_options["rate"])
								);
							
							if($DocpartProduct->valid == true)
							{
								array_push($this->Products, $DocpartProduct);
							}
							
						}
					}
				}
				
				///////////////////////////////////////////////////////////////////////////////////////////////
				if($view_sh){
				//Получение товаров в наличии по артикулу производителя {used} - товары бывшие в употреблении
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, "https://whls.euroauto.ru/api/offers/products/manufacturers/".$manufacturer_id."/used?code=".$article."&replacements=true");
				curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$token, "Accept: application/json"));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, false);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20); 
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				$curl_result = curl_exec($curl);
				curl_close($curl);
				
				$products_all = json_decode($curl_result, true);
				
				//echo '<pre>';
				//var_dump($products_all);
				//echo '</pre>';
				//echo '<hr/>';
				
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение товаров в наличии по артикулу производителя {used} - ".$article." - ".$manufacturer, "Вызов метода https://whls.euroauto.ru/api/offers/products/manufacturers/".$manufacturer_id."/used?code=".$article."&replacements=true", "См. ответ API после обработки", print_r($products_all, true) );
				}
				
				if(!empty($products_all['data']['offers'])){
					foreach($products_all['data']['offers'] as $item_offer){
						if(!empty($item_offer['product_id'])){
							
							$product_id = $item_offer['product_id'];
							$store_id = $item_offer['store_id'];
							$price = $item_offer['price'];
							$exist = $item_offer['quantity'];
							
							$product = $products_all['data']['products'][$product_id];
							
							$name = 'Б.У. -/- '. $product['name']['name'] .' -/- '. $product['comment'] .' -/- ID: '. $product_id;
							$manufacturer_code = $product['manufacturer_code'];
							
							$manufacturer_codes = $products_all['data']['manufacturer_codes'][$manufacturer_code];
							
							$product_article = $manufacturer_codes['code'];
							$product_manufacturer = $manufacturer_codes['manufacturer']['name'];
							
							$storage = $products_all['data']['stores'][$store_id]['name'];
							$min_order = 1;
							$markup = 0;
							
							$images = $product['media']['images'][0]['url'];
							
							$json = array('used' => '1', 'product_id' => $product_id, 'images' => $images);// - товары бывшие в употреблении
							$json = json_encode($json);
							
							//Создаем объек товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct($product_manufacturer,
								$product_article,
								$name,
								$exist,
								$price,
								$storage_options["additional_time"],
								$storage_options["additional_time"],
								$storage,
								$min_order,
								$storage_options["probability"],
								$storage_options["office_id"],
								$storage_options["storage_id"],
								$storage_options["office_caption"],
								$storage_options["color"],
								$storage_options["storage_caption"],
								$price,
								$markup,
								2,0,0,'',$json,array("rate"=>$storage_options["rate"])
								);
							
							if($DocpartProduct->valid == true)
							{
								array_push($this->Products, $DocpartProduct);
							}
							
						}
					}
				}
				}
				///////////////////////////////////////////////////////////////////////////////////////////////
				
			}
		}
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class euroauto_enclosure

/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST["article"]."\n");
fwrite($f, $_POST["storage_options"]."\n");
*/

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

$ob = new euroauto_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>