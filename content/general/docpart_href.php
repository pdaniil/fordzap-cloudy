<?php
/**
 * Скрипт для вывода ссылки Docpart
*/
defined('_ASTEXE_') or die('No access');

//По умолчанию
$docpart_href = 'https://docpart.ru/';
$docpart_title = 'Сайт работает на платформе Docpart';

//Альтернативный вариант (для нечетных лицензий)
$lic_file = @fopen($_SERVER["DOCUMENT_ROOT"]."/license/license.lic", "r");
if($lic_file)
{
	$lic_id = 0;

	while (!feof($lic_file)) 
	{
		$record = fgets($lic_file, 4096);//Очередная запись
		$record = str_replace("\n", "", $record);
		$record = explode(":", $record);
		
		if( $record[0] == "license" )
		{
			$lic_id = $record[1];
			break;
		}
	}
	fclose($lic_file);
	
	if( $lic_id % 2 > 0 )
	{
		$docpart_href = 'https://docpart.net/';
		$docpart_title = 'Powered by Docpart';
	}
}
?>