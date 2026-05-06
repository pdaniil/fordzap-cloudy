<?php





error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");





//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");





class armtek_enclosure

{

	public $result;

	

	public $Products = array();//Список товаров

	

	public function __construct($article, $manufacurers, $storage_options)

	{

		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА

		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

		

		

		$this->result = 0;//По умолчанию

		

		$time_now = time();//Время сейчас

		

		/*****Учетные данные*****/

     $login = $storage_options["login"];

     $password = $storage_options["password"];

		 $vkorg = !empty($storage_options["vkorg"]) ? $storage_options["vkorg"] : null;

     $kunrg = !empty($storage_options["kunrg"]) ? $storage_options["kunrg"] : null;

		/*****Учетные данные*****/

        

		// -------------------------------------------------------------------------------------------------



		//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]

		if($vkorg === null && $DocpartSuppliersAPI_Debug->suppliers_api_debug)

		{

			$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Не выбрана сбытовая организация.");

		}



		//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]

		if($kunrg === null && $DocpartSuppliersAPI_Debug->suppliers_api_debug)

		{

			$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Не выбран покупатель для сбытовой организации.");

		}



		for($m=0; $m < count($manufacurers); $m++)

		{

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_search/search");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($ch, CURLOPT_POST, true);

			curl_setopt($ch, CURLOPT_HEADER, 0);

			curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );

			curl_setopt($ch, CURLOPT_POSTFIELDS, "format=json&VKORG=".$vkorg."&KUNNR_RG=".$kunrg."&PIN=".$article."&QUERY_TYPE=2&BRAND=".$manufacurers[$m]["manufacturer"]);

			$curl_result_parts = curl_exec($ch);

			curl_close($ch);

			

			

			//ЛОГ [API-запрос] (вся информация о запросе)

			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)

			{

				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacurers[$m]["manufacturer"], "http://ws.armtek.ru/api/ws_search/search<br>Авторизация через HTTPHEADER Authorization: Basic base64_encode(".$login.":".$password.")<br>Метод: POST<br>Поля: "."format=json&VKORG=".$vkorg."&KUNNR_RG=".$kunrg."&PIN=".$article."&QUERY_TYPE=2&BRAND=".$manufacurers[$m]["manufacturer"], $curl_result_parts, print_r(json_decode($curl_result_parts, true), true) );

			}

			

			$curl_result_parts = json_decode($curl_result_parts, true);

			

			$products = $curl_result_parts["RESP"];

			

			for($p=0; $p < count($products); $p++)

			{

				$price = (float)$products[$p]["PRICE"];

			

				//Наценка

				$markup = $storage_options["markups"][(int)$price];

				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве

				{

					$markup = $storage_options["markups"][count($storage_options["markups"])-1];

				}

				

				//Срок доставки

				$time_to_exe = 0;

				$time_arrive = new DateTime( $products[$p]["DLVDT"] );//Время поступления

				$time_now = new DateTime();

				$time_now->setTime( 0, 0 ); //Устанавливаем 00:00

				$time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.

				$time_to_exe = $time_interval->days; //Получаем дни (int)

				

				$time_to_exe_guaranteed = 0;

				$time_arrive = new DateTime( $products[$p]["WRNTDT"] );//Время поступления

				$time_now = new DateTime();

				$time_now->setTime( 0, 0 ); //Устанавливаем 00:00

				$time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.

				$time_to_exe_guaranteed = (int)$time_interval->days; //Получаем дни (int)

				

				

				if ($time_to_exe_guaranteed == 0)

					$time_to_exe_guaranteed = $time_to_exe;

				//Набор параметров для SAO

				$json_params = array("VKORG"=>$vkorg, "KEYZAK"=>$products[$p]["KEYZAK"], "KUNNR_RG"=>$kunrg, "DLVDT"=>$products[$p]["DLVDT"]);

				

				//Создаем объек товара и добавляем его в список:

				$DocpartProduct = new DocpartProduct($products[$p]["BRAND"],

					$products[$p]["PIN"],

					$products[$p]["NAME"],

					$products[$p]["RVALUE"],

					$price + $price*$markup,

					$time_to_exe + $storage_options["additional_time"],

					$time_to_exe_guaranteed + $storage_options["additional_time"],

					$products[$p]["KEYZAK"],

					$products[$p]["RDPRF"],

					$products[$p]["VENSL"],

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
					json_encode($json_params),
					array(
						"rate" => $storage_options["rate"],
						"delivery_start" => -1,
						"delivery_end" => (new DateTime($json_params["DLVDT"]))->getTimestamp() + $storage_options["additional_time"] * 24 * 3600
					)

				);

				

				if($DocpartProduct->valid == true)

				{

					array_push($this->Products, $DocpartProduct);

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

};//~class armtek_enclosure





//Настройки подключения к складу

$storage_options = json_decode($_POST["storage_options"], true);

//ЛОГ - СОЗДАНИЕ ОБЪЕКТА

$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА

$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );





$ob = new armtek_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);

exit(json_encode($ob));

?>