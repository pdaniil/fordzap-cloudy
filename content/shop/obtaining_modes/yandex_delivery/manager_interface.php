<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода графического интерфейса для менеджера в панели управления - на странице "Карта заказа" - backend

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
<p>Способ получения - <b><?=$obtain_mode["caption"];?></b></p>
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