<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //Предотвратить вывод сообщений об ошибках
ini_set('display_errors', 0);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autotrade
{
	public $status;
	public $ProductsManufacturers = array();

	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

		$this->status = 0; // По умолчанию
		$hash_auth = $storage_options["api_key"]; // Учетные данные
		$address = "https://api2.autotrade.su/?json"; // Адрес для запроса

		//Получаем список брендов
		$query_string = 'data={"auth_key":"'. $hash_auth .'","method":"GetItemsByQuery","params":{"q":["'. $article .'"],"strict":"1","replace":"1","cross":"1","withSubs":"1"}}';

		$ch = curl_init($address);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);

		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед CURL-запросом на получение артикулов с аналогами. Метод GetItemsByQuery. Далее последует цикл do-while с выполнением curl_exec(), т.к. у поставщика могут возникать превышения лимитов на количество запросов");

		// UPD: Добавляем проверку на ошибку "Превышен лимит на число запросов за n секунд.".
		// В случаи необходимости, можно добавить timeout
		$result_string = "";
		do {
			$result_string = curl_exec($ch);
			$result = $result_string;
			$result = json_decode($result, true);
			/*
			echo '<pre>';
			var_dump($result);
			echo '</pre>';
			*/
		} while ($result['code'] == 7);

		$a = array();
		if (count($result["items"]) <= 0) {
			foreach ($result["brands"] as $key => $val) {
				$DocpartManufacturer = new DocpartManufacturer(
					$val,
					$key,
					"",
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true,
					null
				);
				if (!$DocpartManufacturer->valid) {
					continue;
				}
				if (!empty($a[md5($val["brand_name"])])) {
					continue;
				}
				$a[md5($val["brand_name"])] = 1;

				array_push($this->ProductsManufacturers, $DocpartManufacturer);
			}
		} else {
			foreach ($result["items"] as $key => $val) {
				$DocpartManufacturer = new DocpartManufacturer(
					$val["brand_name"],
					$val["part_type_id"],
					$val["part_type"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true,
					null
				);
				if (!$DocpartManufacturer->valid) {
					continue;
				}
				if (!empty($a[md5($val["brand_name"])])) {
					continue;
				}
				$a[md5($val["brand_name"])] = 1;

				array_push($this->ProductsManufacturers, $DocpartManufacturer);
			}
		}


		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Прошли цикл do-while. Далее лог запроса");

		//ЛОГ [API-запрос] (вся информация о запросе)
		if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка артикулов с аналогами по артикулу " . $article, "https://api2.autotrade.su/?json<br>Метод POST<br>Поля: " . $query_string, $result_string, print_r(json_decode($result_string, true), true));
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if ($DocpartSuppliersAPI_Debug->suppliers_api_debug) {
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->ProductsManufacturers, true));
		}

		$this->status = 1;
	}
}


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
$DocpartSuppliersAPI_Debug->init_object(array("storage_id" => $storage_options["storage_id"], "api_script_name" => __FILE__, "api_type" => "CURL-HTTP-JSON"));
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new autotrade($_POST["article"], $storage_options);
exit(json_encode($ob));
?>