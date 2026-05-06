<?php
// DOC : https://b2b.pwrs.ru/Help/Page?url=index.html
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class tochki_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		//ЛОГ - сообщение
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Перед созданием SOAP-клиента");
		}
		
		
		try 
		{
			$client = new SoapClient("http://api-b2b.4tochki.ru/WCF/ClientService.svc?wsdl");
			$params =  array
			(
				'login' => $login,
				'password' => $password,
				'filter' => array(
					'code_list' => array(
						0 => $article, 
					),
				)
			);
			
			$answer = $client->GetGoodsPriceRestByCode($params);  
			$suppliers_items = json_decode(json_encode($answer), true);
			
			$retail_price = $storage_options['retail_price']; //Розничная цена в проценке
			
			
			//ЛОГ ПОСЛЕ API-запроса
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_after_api_request("Получение списка товаров по артикулу ".$article, print_r( $suppliers_items , true ), print_r( $suppliers_items, true ) );
			}
							
			
			//ЛОГ ПЕРЕД API-запросом
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_before_api_request("Получение информации о складах",'http://api-b2b.4tochki.ru/WCF/ClientService.svc?wsdl<br>Дополнительные параметры не передаем');
			}
			
			//Получаем инфу о скаладах( сроки логистики и прочая инфа )
			$params =  array
			(
				'login' => $login,
				'password' => $password,
			);
			
			$warehouse_info = $client->GetWarehouses($params); 
			$warehouse_info = json_decode(json_encode($warehouse_info), true); 
			
			//ЛОГ ПОСЛЕ API-запроса
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_after_api_request("Получение информации о складах", print_r( $warehouse_info , true ), print_r( $warehouse_info, true ) );
			}
			
			$warehouse_info = $warehouse_info["GetWarehousesResult"]["warehouses"]["WarehouseInfo"];//Массив складов
			
			
			//ЛОГ - сообщение
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_simple_message("Перед перекрестным циклом foreach (по товарам - по складам)");
			}
			
			
			
			
			
			$info = $suppliers_items["GetGoodsPriceRestByCodeResult"]["price_rest_list"]["price_rest"];

			if ($info["whpr"]["wh_price_rest"]["price"]) {
				$items = $info["whpr"];
			} else {
				$items = $info["whpr"]["wh_price_rest"];
			} //Массив предложений

			$code = $info["code"]; //Код товара
			
			 
			 
			 
			//Получаем производителя и наименование товара по коду 
			$params =  array
			(
			  'login' => $login,
			  'password' => $password,
			  'code_list' => array(
				  0 => $article
			  ),
			);
			  
			$answer = $client->GetGoodsInfo($params);   
			
			
			//ЛОГ ПОСЛЕ API-запроса
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_after_api_request("Получение подробной информации о товаре", print_r( $answer , true ), print_r( $answer, true ) );
			}
			$answer = json_decode(json_encode($answer), true)["GetGoodsInfoResult"];
			
			$brand = '';
			$name = '';
			foreach ($answer as $item)
				if (!empty($item) && reset($item)["brand"] != '')
				{
					$brand = reset($item)["brand"];
					$name = reset($item)["name"];
				}

			
			if ($brand == '')
			{
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("НЕ УДАЛОСЬ ПОЛУЧИТЬ ПОДРОБНУЮ ИНФОРМАЦИЮ О ТОВАРЕ", print_r('', true) );
				}
			
			}	
			
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_after_api_request("Брэнд и наименование", print_r( $brand , true ), print_r( $name, true ) );
			}			
			
				
			//END Получаем производителя и наименование товара по коду
			
			
			foreach ($items as $item ) 
			{	
				$article 				= $code;
				$manufacturer 			= $brand;
				
				
				
					
				if ( $retail_price ) {
					
					$price_purchase		= $item['price_rozn']; //Рекомендуемая цена из API
					
				} else {
					
					$price_purchase		= $item['price']; //Цена поставщика( видимо )
											
				}
				
				// $price_purchase		= $storage['price_rozn']; //Возможно нужно будет использовать этот параметр
				$exist 				= $item['rest'];
				
				$storage_id = $item["wrh"];
				
				$i = 0;
				$flag = true;
				$delivery_time = 0;
				while($i <= count($warehouse_info) && $flag)
				{	
					if ($warehouse_info[$i]["id"] == $storage_id )
					{
						$delivery_time = $warehouse_info[$i]["logisticDays"];
						$flag = false;
					}
					$i++;
				}
				
				$time_to_exe				= $delivery_time + $storage_options['additional_time'];
				$time_to_exe_guaranteed	= $time_to_exe;
				
				$storage 					= 0;
				$min_order					= 1;
				$probability				= $storage_options['probability'];
				$product_type				= 2;
				$product_id				= 0;
				$storage_record_id		= 0;
				$url						= '';
				$json_params				= '';
				$rest_params				= array( "rate"=>$storage_options["rate"] );
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price_purchase];
				//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				if( $markup == NULL ) {
					
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					
				}
				
				$price_for_customer = $price_purchase + $price_purchase * $markup;
				
				$DocpartProduct = new DocpartProduct( $manufacturer, 
														$article,
														$name,
														$exist,
														$price_for_customer,
														$time_to_exe,
														$time_to_exe_guaranteed,
														$storage,
														$min_order,
														$probability,
														$storage_options["office_id"],
														$storage_options["storage_id"],
														$storage_options["office_caption"],
														$storage_options["color"],
														$storage_options["storage_caption"],
														$price_purchase,
														$markup,
														$product_type,
														$product_id,
														$storage_record_id,
														$url,
														$json_params,
														$rest_params );
				
				if($DocpartProduct->valid) {
					
					array_push($this->Products, $DocpartProduct);
					
				}
			} // ~foreach ( $suppliers_items as $item )
			
			$this->result = 1;
			
			
			//ЛОГ результата запроса
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список товаров", print_r($this->Products, true) );
			}
		}
		catch ( SoapFault $e )
		{
			//Отладка
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение SOAP-клиента", print_r($e, true) , $e->getMessage() );
			}
		}
		catch ( Exception $e )
		{
			//ЛОГ - ИСКЛЮЧЕНИЕ
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
			}
		}
		
	} //~__construct($article, $manufacturers, $storage_options)
	
}
//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);


//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob =  new tochki_enclosure( $_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options );
$json = json_encode($ob);



exit($json);
?>