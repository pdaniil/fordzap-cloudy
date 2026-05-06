<?php
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class liart
{
	
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
			$login = $storage_options["login"];
			$password = $storage_options["password"];
           
      $count = 0;

      foreach($manufacturers as $manufacturer) {
                
        // if ($count > 1) continue;
                
        $manufacturer_code = urlencode($manufacturer["manufacturer"]);
		
				$curl = curl_init();
				$url = 'http://www.li-art.ru/index.php?option=com_autoapi&task=detail.search&username=' . $login . '&userpsw=' . $password . '&brand=' . $manufacturer_code . '&oem=' . $article;
				curl_setopt_array($curl, array(
				  CURLOPT_URL => $url,
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_FOLLOWLOCATION => true,
				  CURLOPT_CUSTOMREQUEST => 'GET',
				));

				$response = curl_exec($curl);
        		
        		
        		//ЛОГ [API-запрос] (вся информация о запросе)
        		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        		{
        			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer_code, $url, $response, print_r(json_decode($response, true), true) );
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
        		  
        		$decode = json_decode($response, true);
				
        		if($decode["status"] == 200) 
        		{
					$details = $decode["data"]["details"];
        			foreach ($details as $part)
        			{	
        				// var_dump($part);
        				
        				$price = (float)$part["price"];
        	
                        //Наценка
            		    $markup = $storage_options["markups"][(int)$price];
            		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
            		    {
            		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
            		    }
        				$DocpartProduct = new DocpartProduct($part["brand"],
        					$part["oem"],
        					$part["name"],
        					$part["amount"],
        					$price+$price*$markup,
        					$part["min_days"] + $storage_options["additional_time"],
        					$part["guaranteed_days"] + $storage_options["additional_time"],
        					$storage_options["storage_caption"],
        					$part["set_amount"],
        					$storage_options["probability"],
        					$storage_options["office_id"],
        					$storage_options["storage_id"],
        					$storage_options["office_caption"],
        					$storage_options["color"],
        					$storage_options["storage_caption"],
        					$price,
        					$markup,
        					2,
        					0,
        					0,
        					'',
        					null,
        					array("rate"=>$storage_options["rate"]) );
        					
        				// var_dump($DocpartProduct);
        				
        				if($DocpartProduct->valid)
        				{
        					array_push($this->Products, $DocpartProduct);
        				}
        				
        			}
        			
        			//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
        			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        			{
        				$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
        			}
        			
        			
        		}
                
            }
            
            $count++; 
            
        
        
	}
}


$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$enclosure = new liart($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options );
$enclosure->result = 1;
exit(json_encode($enclosure));
?>