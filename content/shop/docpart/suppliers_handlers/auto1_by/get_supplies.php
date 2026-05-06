<?php

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set("memory_limit", "256M");

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
//require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class auto1_by_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		$guids = array($storage_options["point"]); //пункт выдачи, до которого расчитывается доставка
		$analogs = $storage_options["analogs"];
		$analogs = true;
		$only_ac_storage = $storage_options["only_ac_storage"];
		/*****Учетные данные*****/
		 
		$header = array("Accept: application/json", "User-Agent: Server");

		$ch = curl_init("https://auto1.by/WebApi/GetRequestParameters?login=$login&password=$password");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $curl_result = curl_exec($ch);
		curl_close($ch);
		$curl_result = json_decode($curl_result, 1);

		// цикл по найденным организациям
		$Organizations = $curl_result['Organizations'];
		$DeliveryAddresses = $curl_result['DeliveryAddress'];

		if (empty($guids)) //Если пункт отсутствует, расчитываем для всех
		{	
			$guids = array();
			foreach($DeliveryAddresses as $DeliveryAddress)
			{
				$guids[] = $DeliveryAddress['Guid'];
			}
		}

		foreach($Organizations as $Organization)
		{
			foreach($guids as $guid)
			{
				foreach($manufacturers as $manufacturer)
				{
					$orgId = $Organization['OrgId'];
					$orderType = $Organization['OrderType'];
					
					$query = array(
						'orgId' => $orgId,
						'orderType' => $orderType,
						'article' => $article,
						'brand' => $manufacturer['manufacturer'],
						'searchType' => $only_ac_storage ? 'as' : '',
						'point' => $guid,
						'withAnalogues' => 'true',
						'login' => $login,
						'password' => $password
					);

					$url = 'https://auto1.by/WebApi/SearchByArticle?' . http_build_query($query);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					$curl_results = curl_exec($ch);
					curl_close($ch);
					$curl_results = json_decode($curl_results, true);

					//var_dump($curl_result);
					
				
					if (!empty($curl_results))
					{
						//Сначала проверяем корректность товара:
						/*if(empty($curl_result['Article']) || empty($curl_result["Brand"]) || empty($curl_result["Designation"]))
						{
							continue;
						}*/

						foreach($curl_results as $curl_result)
						{
							if (!empty($curl_result['Stores']))
							{
								foreach($curl_result['Stores'] as $store)
								{
									//Обработка времени доставки:
									$time = time();
									$timeToExe = strtotime($store['DeliveryInfo']) > $time ? round((strtotime($store['DeliveryInfo']) - $time) / 86400) : 0;
									
									//Наценка
									$markup = $storage_options["markups"][(int)$store['Price']];
									if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
									{
										$markup = $storage_options["markups"][count($storage_options["markups"])-1];
									}

									$DocpartProduct = new DocpartProduct
									(
										$curl_result['Brand'],
										$curl_result['Article'],
										$curl_result['Designation'],

										$store['Quantity'],
										$store['Price'] + $store['Price'] * $markup,
										$timeToExe + $storage_options["additional_time"],
										$timeToExe + $storage_options["additional_time"],
										NULL,
										1,
										$storage_options["probability"],
										$storage_options["office_id"],
										$storage_options["storage_id"],
										$storage_options["office_caption"],
										$storage_options["color"],
										$storage_options["storage_caption"],
										$store['Price'],
										$markup,
										2,0,0,'',null,array("rate"=>$storage_options["rate"])
									);

									if($DocpartProduct->valid == true)
									{
										array_push($this->Products, $DocpartProduct);
									}
								}
							}

							if ($analogs && !empty($curl_result['Analogs']))
							{
								foreach($curl_result['Analogs'] as $analog)
								{
									//Обработка времени доставки:
									$time = time();
									$timeToExe = strtotime($store['DeliveryInfo']) > $time ? round((strtotime($store['DeliveryInfo']) - $time) / 86400) : 0;

									//Наценка
									$markup = $storage_options["markups"][(int)$store['Price']];
									if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
									{
										$markup = $storage_options["markups"][count($storage_options["markups"])-1];
									}
									
									if (!empty($analog['Stores']))
									{
										foreach($analog['Stores'] as $store)
										{
											//Обработка времени доставки:
											$time = time();
											$timeToExe = strtotime($store['DeliveryInfo']) > $time ? round((strtotime($store['DeliveryInfo']) - $time) / 86400) : 0;
											
											//Наценка
											$markup = $storage_options["markups"][(int)$store['Price']];
											if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
											{
												$markup = $storage_options["markups"][count($storage_options["markups"])-1];
											}

											$DocpartProduct = new DocpartProduct
											(
												$analog['Brand'],
												$analog['Article'],
												$analog['Designation'],

												$store['Quantity'],
												$store['Price'] + $store['Price'] * $markup,
												$timeToExe + $storage_options["additional_time"],
												$timeToExe + $storage_options["additional_time"],
												NULL,
												$store['Multiplicity'],
												$storage_options["probability"],
												$storage_options["office_id"],
												$storage_options["storage_id"],
												$storage_options["office_caption"],
												$storage_options["color"],
												$storage_options["storage_caption"],
												$store['Price'],
												$markup,
												2,0,0,'',null,array("rate"=>$storage_options["rate"])
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
					}
				}	
			}
		}

		$this->result = 1;
	}
}

$ob = new auto1_by_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), json_decode($_POST["storage_options"], true));
exit(json_encode($ob));
?>
