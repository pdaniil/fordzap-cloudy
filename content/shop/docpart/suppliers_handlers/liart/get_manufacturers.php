<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class liart
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'http://www.li-art.ru/index.php?option=com_autoapi&task=brand.search&username=' . $login . '&userpsw=' . $password . '&oem='. $article,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		));

		$response = curl_exec($curl);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, 'http://www.li-art.ru/index.php?option=com_autoapi&task=brand.search&username=' . $login . '&userpsw=' . $password . '&oem='. $article, $response, print_r(json_decode($response, true), true) );
		}
		
		if(curl_errno($curl))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($curl), true) );
			}
		}
		curl_close($curl);
		
		$brands = json_decode($response, true);
		
		if($brands["status"] == 200)
		{
			for( $i = 1; $i < count($brands["data"]); $i++ )
			{
				$manufacturer	= $brands["data"][$i]["brand_name"];
				$name			= "";
				$storage_id		= $storage_options["storage_id"];
				$office_id		= $storage_options["office_id"];
				
				$DocpartManufacturer = new DocpartManufacturer($manufacturer, 
					0,
					$name,
					$office_id,
					$storage_id,
					true
				);
				
				$this->appedeManufacturer( $DocpartManufacturer );
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
	}
	
	public function appedeManufacturer( $manufacturer )
	{
		if( $manufacturer->valid)
		{
			array_push($this->ProductsManufacturers, $manufacturer);
		}
	}
	
}
/*
$f = fopen('log.txt','w');
fwrite($f, $_POST["storage_options"]."\n");
fwrite($f, $_POST["article"]."\n");
fclose($f);

$_POST["storage_options"] = '{"login":"garage6k2@mail.ru","password":"ioZmyV","color":"#d33636","probability":"95","office_id":"1","storage_id":"13"}';
$_POST["article"] = 'C110';
*/
//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

$enclosure = new liart( $_POST["article"], $storage_options );
exit(json_encode($enclosure));
?>