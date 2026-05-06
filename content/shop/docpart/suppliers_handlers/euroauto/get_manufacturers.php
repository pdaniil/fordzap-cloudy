<?php
header('Content-Type: application/json;charset=utf-8;');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class euroauto_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
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
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка производителей по артикулу ".$article, "Вызов метода https://whls.euroauto.ru/api/products/manufacturers/codes?code=".$article, $curl_result, print_r($manufacturers_all, true) );
		}
		
		$locale_hash = array();
		if(!empty($manufacturers_all['data'])){
			foreach($manufacturers_all['data'] as $item_manufacturer){
				$manufacturer = $item_manufacturer['manufacturer']['name'];
				$manufacturer_id = $item_manufacturer['manufacturer']['id'];
				$name = $item_manufacturer['name']['name'];
				$synonyms_single_query	= true;
				
				$hash = md5( $manufacturer );
			
				if ( ! isset( $locale_hash[$hash] ) ) {
					
					$DocpartManufacturer = new DocpartManufacturer($manufacturer,
						$manufacturer_id,
						$name,
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$synonyms_single_query
					);
					
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
					
					$locale_hash[$hash] = true;
					
				}
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


try 
{
	$ob = new euroauto_enclosure($_POST["article"], $storage_options);
	$json = json_encode($ob);
	
	if ( ! $json ) 
	{
		throw new Exception("Ошибка преобразования DocpartManufacturer в json: " . json_last_error());
	}
} 
catch (Exception $e) 
{
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	} 
}


exit($json);
?>