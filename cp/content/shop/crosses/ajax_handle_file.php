<?php
/**
	Серверный скрипт для обработки загруженного csv файла
*/
header('Content-Type: application/json;charset=utf-8;');
function prepareString($string)
{
	$sweep=array("/", "#", "\r\n", "\r", "\n", "\t", "'", '"', '`', "\\");
	$string = str_replace($sweep,"", $string);
	
	return trim($string);
}

function delete_file($file)
{
	$res = unlink($file);
}



//$f = fopen('log.txt', 'a');
//fwrite($f, $_POST["import_options"] . "\n\n");
//$_POST["import_options"] = '{"file_full_path":"/home/a/aatvgearru/public_html/cp/tmp/Лист Microsoft Excel.csv"}';



require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");



// -------------------------------------------------------------------------------
//Проверка привелегий (пользователь должен иметь доступ к следующим страницам)
$pages_to_check = array();
$pages_to_check[] = array('id'=>380, 'url'=>'shop/crosses');//Таблица кроссов
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/control/check_admin_access/check_admin_access.php");
// -------------------------------------------------------------------------------


// -------------------------------------------------------------------------------
//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
// -------------------------------------------------------------------------------



//Параметры импорта
$import_options = json_decode( $_POST["import_options"], true );


//Файл должен быть тот самый, который с кроссами.
//Имя файла:
$file_name = basename($import_options['file_full_path']);
if( $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/".$file_name != $import_options['file_full_path'] )
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Ошибка 1. Файл не найден";
	exit( json_encode($answer) );
}
//Расширение файла
$file_format = substr($file_name, strlen($file_name)-4, 4);
$file_format = strtolower($file_format);//К нижнему регистру
//Теперь полная проверка расширения
if($file_format != ".csv")
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Ошибка 2. Файл не найден";
	exit( json_encode($answer) );
}


// Проверяем наличие файла
if( !file_exists($import_options['file_full_path']) ){
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Файл не найден";
	exit( json_encode($answer) );
}

//На время загрузки отключаем индексы
if( $db_link->prepare("ALTER TABLE `shop_docpart_articles_analogs_list` DISABLE KEYS;")->execute() != true)
{
	//Удаляем файл прайс-листа
	delete_file($import_options["file_full_path"]);
	
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "SQL ошибка отключения индексов";
	exit( json_encode($answer) );
}

//Открываем исходный файл
$file = fopen($import_options['file_full_path'], "r");

//Пропускаем первую строку
$current_record = fgets($file, 4096);

//INSERT по 1500 строк
$count_per_insert = 1500;
$current_part = 0;
$SQL_INSERT_GENERAL = "INSERT IGNORE INTO `shop_docpart_articles_analogs_list` (`article`,`manufacturer_article`,`analog`,`manufacturer_analog`) VALUES ";//Общая строка
$SQL_INSERT_WORK = "";//Рабочая строка - используется в запросе
$sweep = array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t");

//Читаем файл построчно.
$binding_values = array();
while (($current_record = fgets($file, 4096)) !== false)
{	
	$current_record = explode(";", $current_record);
	
	//Получаем данные из строки
	$article = $current_record[0];
	$article = str_replace($sweep, "", $article);
	$article = strtoupper($article);
	$manufacturer = prepareString($current_record[1]);
	$manufacturer = iconv("WINDOWS-1251", "UTF-8", $manufacturer);
	$manufacturer = strtoupper($manufacturer);
	
	$analog = $current_record[2];
	$analog = str_replace($sweep, "", $analog);
	$analog = strtoupper($analog);
	$manufacturer_analog = prepareString($current_record[3]);
	$manufacturer_analog = iconv("WINDOWS-1251", "UTF-8", $manufacturer_analog);
	$manufacturer_analog = strtoupper($manufacturer_analog);
	
	
	if( (string)$article == "")
	{
		$article = "NO";
	}
	if( (string)$manufacturer == "")
	{
		$manufacturer = "NO";
	}
	if( (string)$analog == "")
	{
		$analog = "NO";
	}
	if( (string)$manufacturer_analog == "")
	{
		$manufacturer_analog = "NO";
	}
	
	
	//Формируем строку
	$SQL_INSERT_RECORD = "(?,?,?,?)";
	
	
	
	array_push($binding_values, $article);
	array_push($binding_values, $manufacturer);
	array_push($binding_values, $analog);
	array_push($binding_values, $manufacturer_analog);
	
	//Добавляем строку к запросу
	if($current_part == 0)
	{
		$SQL_INSERT_WORK = $SQL_INSERT_GENERAL.$SQL_INSERT_RECORD;
	}
	else
	{
		$SQL_INSERT_WORK = $SQL_INSERT_WORK.",".$SQL_INSERT_RECORD;
	}
	
	
	
	
	//Набрали 1500 строк - делаем запрос
	if($current_part > $count_per_insert)
	{
		if( $db_link->prepare($SQL_INSERT_WORK)->execute($binding_values) != true)
		{
			fclose($file);
			//Удаляем файл прайс-листа
			delete_file($import_options["file_full_path"]);
			
			$answer = array();
			$answer["status"] = false;
			$answer["message"] = "SQL ошибка (INSERT)";
			exit( json_encode($answer) );
		}
		
		//Обнуляем:
		$SQL_INSERT_WORK = "";
		$binding_values = array();
		$current_part = 0;
	}
	else
	{
		$current_part++;
	}
}

//Если после прочтения всего файла остались не записанные строки:
if($current_part > 0)
{
	if( $db_link->prepare($SQL_INSERT_WORK)->execute($binding_values) != true)
	{
		fclose($file);
		//Удаляем файл прайс-листа
		delete_file($import_options["file_full_path"]);
		
		//var_dump($binding_values);
		
		//var_dump($db_link->errorInfo());
		
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = "SQL ошибка (INSERT) ";
		exit( json_encode($answer) );
	}
}

//Снова включаем индексы
if( $db_link->prepare("ALTER TABLE `shop_docpart_articles_analogs_list` ENABLE KEYS;")->execute() != true)
{
	fclose($file);
	//Удаляем файл прайс-листа
	delete_file($import_options["file_full_path"]);
	
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "SQL ошибка включения индексов";
	exit( json_encode($answer) );
}

fclose($file);//Закрываем файл
//Удаляем файл прайс-листа
delete_file($import_options["file_full_path"]);

$answer = array();
$answer["status"] = true;
$answer["message"] = "Выполнено успешно";
exit( json_encode($answer) );
?>