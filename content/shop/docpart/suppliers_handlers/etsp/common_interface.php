<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class etsp_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		 $login = $storage_options["login"];
		 $password = $storage_options["password"];
		/*****Учетные данные*****/
		

		try
		{
			//Создание объекта клиента
			$client = new soapclient("http://ws.etsp.ru/v2/Security.svc?wsdl");
		}
		catch (SoapFault $e)
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании soapclient для получения сессии", print_r($e, true) , $e->getMessage() );
			}
			return;
		}


		$params = array(array(
			'Login' => $login,
			'Password' => $password));

			try
			{
				//Получение сессии
				         
				$response = $client->Logon(
					array
					(
						'request' => array
							(
								'Login' => $login,
								'Password' => $password
							)
					)
				);
				

				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение сессии", "SOAP-вызов метода Logon с параметрами:<br>".print_r($params,true), "См. ответ API после обработки", htmlentities(print_r($response, true)) );
				}
			}
			catch (SoapFault $e)
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода Logon()", print_r($e, true) , $e->getMessage() );
				}
				return;
			}



		$hashSession = $response->LogonResult;

		if (!is_null($hashSession->Errors))
		{
			$error_message = $hashSession->Errors->string;
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка при получении сессии", print_r($error_message,true) );
			}
		}
		else
		{
			// Поиск по артикулу
			try
			{
				//Создаем новый SOAP-клиент для поиска по артикулу
				$client = new soapclient("http://ws.etsp.ru/v2/Search.svc?wsdl");
			}
			catch (SoapFault $e)
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании soapclient для поиска по артикулу", print_r($e, true) , $e->getMessage() );
				}
				return;
			}

			try
			{
				// Поиск по артикулу
				$response = $client->SearchNomenclature(
					array(
						'request' => array(
							'Text' => $article,
							'WithAnalogsSearch' => 1,
							'HashSession' => $hashSession->Data
						)
					)
				);
			
		
			  
				  
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Запрос остатков по артикулу", "SOAP-вызов метода SearchNomenclature", "См. ответ API после обработки", (print_r($response, true)) );
				}
			}
			catch (SoapFault $e)
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода SearchBasic()", print_r($e, true) , $e->getMessage() );
				}
				return;
			}
			
			$searchResult = $response->SearchNomenclatureResult;
		
			try
			{
				//Создаем новый SOAP-клиент для поиска по артикулу
				$client = new soapclient("http://ws.etsp.ru/v2/PartsRemains.svc?wsdl");
			}
			catch (SoapFault $e)
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании soapclient для поиска PartsRemains", print_r($e, true) , $e->getMessage() );
				}
				return;
			}
			
			if (!is_null($searchResult->Errors))
			{
				$error_message = $searchResult->Errors->string;
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Ошибка при получении сессии", print_r($error_message,true) );
				}
			}
			else
			{	
		
				
		
				
				foreach ($searchResult->Data->Items as $part)
				{	
				
			
					$client = new soapclient("http://ws.etsp.ru/v2/PartsRemains.svc?wsdl");
					$response = $client->GetPartsRemainsByManufacturerNumber(array('request' => array(
									'ManufacturerNumber' => $part->ManufacturerNumber,
									'ManufacturerName' => $part->ManufacturerName,
									'ShowRetailRemains' => 0,
									'ShowOutsideRemains' => 0,
									'ExcludeSubstandardRemains' => 0,
									'ExcludeAnalogs' => 0,
									'HashSession' => $hashSession->Data
								)));
					
					if (!is_null($response->Errors))
					{
						$error_message = $response->Errors->string;
						//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
						if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
						{
							$DocpartSuppliersAPI_Debug->log_error("Ошибка при получении сессии", print_r($error_message,true) );
						}
						
					}
					else
					{	
				
						//ЛОГ [API-запрос] (вся информация о запросе)
						if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
						{
							$DocpartSuppliersAPI_Debug->log_api_request("Запрос остатков по артикулу", "SOAP-вызов метода GetPartsRemainsByManufacturerNumber", "См. ответ API после обработки", (print_r($response, true)) );
						}
						$responseResult = $response->GetPartsRemainsByManufacturerNumberResult->Data->Remains->GoodsUnitRemains;
						foreach($responseResult as $storage) {
							
							$storage = (array)$storage;
							$detal_manufacturer = $storage["ManufacturerName"];// Производитель
							$detal_article = $storage["ManufacturerNumber"];
							$detal_exist = $storage["Quantity"];// наличие
							$time_to_exe = 0;
							$time_to_exe_guaranteed = 0;
							
							$time_to_exe = ceil((strtotime($storage["SelfDeliveryInfo"]->EstimatedDeliveryDateTime) - time()) / 86400);
							$time_to_exe_guaranteed = ceil((strtotime($storage["SelfDeliveryInfo"]->MaxOrderDateTime) - time()) / 86400);
							
							if ($time_to_exe > $time_to_exe_guaranteed)
								$time_to_exe_guaranteed = $time_to_exe;
							
							if ($time_to_exe < 0)
							{
								continue;
							}
							
							$detal_name = $part->Name;
							$price = $storage["Price"];// цена
							$price = (float)str_replace(',','.',$price);// Заменяем запятую на точку
							// Готовим объект docpart/DocpartProduct
							//Наценка
							$markup = $storage_options["markups"][(int)$price];
							if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
							{
								$markup = $storage_options["markups"][count($storage_options["markups"])-1];
							}
										
							//Создаем объек товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct(
								$detal_manufacturer,
								$detal_article,
								$detal_name,
								$detal_exist,
								$price + $price*$markup,
								$time_to_exe + $storage_options["additional_time"],
								$time_to_exe_guaranteed + $storage_options["additional_time"],
								0,
								1,
								$storage_options["probability"],
								$storage_options["office_id"],
								$storage_options["storage_id"],
								$storage_options["office_caption"],
								$storage_options["color"],
								$storage_options["storage_caption"],
								$price,
								$markup,
								2,0,0,'',array(),array("rate"=>$storage_options["rate"])
							);
							if($DocpartProduct->valid == true)
							{
								array_push($this->Products, $DocpartProduct);
							}
						}
					}
				}
			}
		}

		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}

		$this->result = 1;
	}//~function __construct($article)
};

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP-XML") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();
 

$ob = new etsp_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?> 