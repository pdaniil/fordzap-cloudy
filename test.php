<?php
require_once 'config.php';
require_once dirname(__FILE__) .'/lib/mpdf/vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
$DP_Config = new DP_Config;
$html = '
<div style="margin-top:10px;">

<a style="background: #4294ff; color: #fff; text-decoration: none; padding: 7px 13px; font-size: 16px; border-radius: 5px; display: inline-block;" target="_blank" href="https://fordzap.com/shop/orders/order?order_id=3089">Перейти на страницу заказа</a>

<h4>Информация о заказе</h4>
	
<table>
	<tr> <td>Номер заказа</td> <td>3089</td> </tr>
	<tr> <td>Создан</td> <td>16.12.2024 13:33</td> </tr>
	<tr> <td>Статус</td> <td>Можно забрать</td> </tr>
	<tr> <td>Способ оплаты</td> <td></td> </tr>
</table>

<h4>Платежи по заказу</h4>

<div style="overflow: hidden; overflow-x: auto;">
	<table>
		<tr>
			<td>Состояние оплаты</td>
			<td>Сумма заказа</td>
			<td>Оплачено</td>
			<td>Осталось оплатить</td>
		</tr>
		<tr>
			<td>
				<strong>
					<div style="color:#FFF;background-color:#e74c3c;border-radius:3px;padding:6px 12px;font-weight:normal;">Не оплачен</div>				</strong>
			</td>
			<td>1700.00</td>
			<td>0.00</td>
			<td>1700.00</td>
		</tr>
	</table>
</div>

<div style="overflow-x:auto; margin-top:0px;">
<p class="lead">Способ получения - Самовывоз</p>

<table class="table">
	<tr>
		<th>Точка выдачи</th>
	</tr>
	<tr>
		<td>
			<span>Адрес: Новомосковск, Техническая 11</span> <a title="Карта" style="cursor:pointer;" data-toggle="collapse" onClick="ymaps_init();" data-target="#collapse_office_map_container" aria-expanded="false" aria-controls="collapse_office_map_container"><i class="fa fa-map-o" aria-hidden="true"></i></a>
			<div class="collapse" id="collapse_office_map_container">
				<br/>
				<div style="width: 100%;" id="map" class="office_map_container"></div>
			</div>
		</td>
	</tr>
	<tr>
		<td>Время работы: пн-сб с 9:00 до 18:00, сб 9:00 до 18:00, вс - выходной</td>
	</tr>
	<tr>
		<td>Телефон: +79107029950</td>
	</tr>
</table>



<!-- START БЛОК ДЛЯ ВЫВОДА СХЕМ РАСПОЛОЖЕНИЯ ОФИСОВ -->
<script src="https://api-maps.yandex.ru/2.0-stable/?load=package.standard&lang=ru-RU" type="text/javascript"></script>
<script type="text/javascript">
	var myMap, myPlacemark;
	var ymaps_init_flag = 0;
	// --------------------------------------------------------------------------------------
	function map_init()
	{
		myMap = new ymaps.Map ("map", {
			center: [54.005686, 38.257757],
			zoom: 16
		}); 
		
		
		myPlacemark = new ymaps.Placemark([54.005686, 38.257757], {balloonContent: "FORDZAP"}, {
        			iconImageHref: "/content/files/images/maps-marker.png",
        			iconImageSize: [27, 44],
        			iconImageOffset: [-13, -44]});
		
		myMap.geoObjects.add(myPlacemark);
		
		
		myMap.controls.add(new ymaps.control.MapTools());
		myMap.controls.add("typeSelector");
		myMap.controls.add("zoomControl");
	}
	// --------------------------------------------------------------------------------------
	function ymaps_init(){
		if(ymaps_init_flag == 0){
			ymaps.ready(map_init);
			ymaps_init_flag = 1;
		}
	}
</script>
<!-- END БЛОК ДЛЯ ВЫВОДА СХЕМ РАСПОЛОЖЕНИЯ ОФИСОВ --></div>
	

<h4>Позиции заказа</h4>

<table>
	<tr>
		<td>ID</td>
		<td>Производитель</td>
		<td>Артикул</td>
		<td>Наименование</td>
		<td>Цена</td>
		<td>Количество</td>
		<td>Сумма</td>
		<td>Статус</td>
		<td>Срок</td>
	</tr>

		
	<tr style="background: #D2F6FF;">
		<td>6602</td>
		<td>DOLZ</td>
		<td>C110</td>
		<td>Помпа, водяной насос PEUGEOT 206/307 1.1/1.4L 98-05 /Ch-&gt;Mt:3666765 DOLZ C-110</td>
		<td>1700.00</td>
		<td>1</td>
		<td>1700.00</td>
		<td>Ожидается поступление</td>
		<td>0 дн.</td>
	</tr>
	
	
	<tr>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td>Итого</td>
		<td>1</td>
		<td>1700.00</td>
		<td></td>
		<td></td>
	</tr>

</table>

</div>

';
$ch = curl_init($DP_Config->domain_path . $DP_Config->backend_dir . '/content/soc_messages/api.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
    'tech_key' => $DP_Config->tech_key,
    'api' => 'sendNotify',
    'user_id' => 1,
    'subject' => 'Тема письма',
    'message' => $html,
)));
echo curl_exec($ch);
curl_close($ch);