<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода детальной информации по выбранному способу получения. Используется на странице "Мой заказ" - frontend

$tariff_info = array(
    "cargo" => "Грузовой",
    "express" => "Доставка",
    "courier" => "Курьер",
    );
    
$tariff = $how_get_json["tariff"];
$tariff_text = "Не выбран";
    
if(isset($tariff_info[$tariff])) {
    $tariff_text = $tariff_info[$tariff];
}


if($how_get_json["block"] != "")
{
	$how_get_json["block"] = "корп. ".$how_get_json["block"].", ";
}
//$address = $how_get_json["street"].", д.".$how_get_json["house"].", ".$how_get_json["block"]." кв(офис). ".$how_get_json["flat_office"];
$address = $how_get_json["street"].", д.".$how_get_json["house"];
?>
<p class="lead">Способ получения - <?=$obtain_mode["caption"];?></p>
<table class="table">
	<tr>
		<th>Информация о доставке</th>
	</tr>
	<tr>
		<td>Регион: <?=$how_get_json["region"]." обл, г. ".$how_get_json["city"];?></td>
	</tr>
	<tr>
		<td>Адрес: <?=$address;?></td>
	</tr>
	<tr>
		<td>Телефон: <?=$how_get_json["phone"];?></td>
	</tr>
	<tr>
		<td>Предварительный расчет: <?=$how_get_json["price"];?> руб.</td>
	</tr>
	<tr>
		<td>Тариф доставки: <?=$tariff_text;?></td>
	</tr>
</table>