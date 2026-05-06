<?php
defined('_ASTEXE_') or die('No access');
//Скрипт графического интерфеса покупателя
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
// Скрипт графического интерфеса покупателя. Используется на странице "Выбора способа получения товара" - frontend
// Получаем настройки системы
$parameters_values_query = $db_link->prepare('SELECT `parameters_values` FROM `shop_obtaining_modes` WHERE `handler` = ?;');
$parameters_values_query->execute( array('yandex_delivery') );
$parameters_values = $parameters_values_query->fetch();
$parameters_values = json_decode($parameters_values["parameters_values"], true);
$how_get = array();
// Ранее заполненные данные доставки
if( isset($_COOKIE["how_get_yandex_delivery"]) )
{
	$how_get = json_decode($_COOKIE["how_get_yandex_delivery"], true);
}
?>
<style>
#map {
    margin: 20px 0 35px;
}
#nwy-delivery-answer {
    display: none;
}
.nwy-delivery-button{
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-wrap: wrap;
        flex-wrap: wrap;
    -webkit-box-align: center;
        -ms-flex-align: center;
            align-items: center;
            justify-content: flex-end;
            margin-bottom: 25px;
}
.nwy-delivery {
    font-size: 15px;
    font-weight: bold;
    margin-right: 20px;
}
.nwy-delivery-info__price {
    display: inline-block;
    font-size: 22px;
}
@media screen and (max-width: 767px) {
	.simple_delivery_table, .simple_delivery_table tbody, .simple_delivery_table tr, .simple_delivery_table th, .simple_delivery_table td{
		display:block;
		border:none !important;
		padding: 5px 0px 0px 0px !important;
	}
}
.tariff_delivery_label {
    display: -webkit-box;
    display: -ms-flexbox;
    display: inline-flex;
    -webkit-box-align: center;
        -ms-flex-align: center;
            align-items: center;
}
.tariff_delivery_label:not(:last-child) {
    margin-right: 20px;
}
.tariff_delivery_label label {
    display: inline-block;
    margin: 0;
    line-height: 1;
    margin-right: 6px;
    cursor: pointer;
}
.tariff_delivery_label input {
    cursor: pointer;
}
.delivery-lead {
    margin-bottom: 5px;
    margin-top: 25px;
}
.lead-sub {
    margin-bottom: 25px;
    font-weight: 600;
}
.nwy-delivery-info {
    background: #eee;
    padding: 10px;
    margin: 10px 0px;
    border-radius: 5px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
}
.nwy-delivery-info i {
    margin-right: 10px;
}
.nwy-delivery-info__text {
    font-size: 14px;
}
.nwy-delivery-table {
    margin-bottom: 10px;
}
.delivery-tariff-list {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    padding: 0;
    margin-left: -10px;
    margin-right: -10px;
    list-style: none;
    margin-top: 10px;
}
.delivery-tariff-list__item {
    width: 100%;
    margin-bottom: 20px;
    padding-right: 10px;
    padding-left: 10px;
    flex: 0 0 33%;
}
.delivery-tariff-list__item_input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.delivery-tariff-list__item_label {
   text-align: center;
    padding: 8px 14px;
    border: 2px solid #737373;
    cursor: pointer;
    background: #fff;
    color: #0d0d0d;
    transition: .28s ease;
    border-radius: 0;
    font-weight: 400;
    height: 100%;
}
.delivery-tariff-list__item_title {
    font-size: 17px;
    font-weight: bold;
    margin-bottom: 7px;
    line-height: 1.2;
}
.delivery-tariff-list__item_text {
    font-size: 14px;
    font-weight: normal;
    line-height: 1.4;
}
.delivery-tariff-list__item_input:checked + .delivery-tariff-list__item_label,
.delivery-tariff-list__item_input:checked+ .delivery-tariff-list__item_label:hover,
.delivery-tariff-list__item_input:focus+ .delivery-tariff-list__item_label:hover {
    border-color: #df6f00;
}
@media screen and (max-width: 680px) {
	.delivery-tariff-list__item {
        width: 100%;
        margin-bottom: 20px;
        padding-right: 10px;
        padding-left: 10px;
        flex: 0 0 100%;
    }
}
</style>
<p class="lead delivery-lead">Заполните данные о доставке:</p>
<table class="table simple_delivery_table nwy-delivery-table">
<tr> <td>Область</td> <td><input class="form-control" id="region_input" type="text" value="Тульская"/></td> </tr>
<tr> <td>Город</td> <td><input class="form-control" id="city_input" type="text" 
    value="<?= (isset($how_get['city']) && !empty($how_get['city'])) ? $how_get['city'] : 'Новомосковск';?>"/></td> </tr>
<tr> <td>Улица</td> <td><input class="form-control" id="street_input" type="text" value="<?=$how_get['street'];?>"/></td> </tr>
<tr> <td>Дом</td> <td><input class="form-control" id="house_input" type="text" value="<?=$how_get['house'];?>"/></td> </tr>
<?php /* <tr> <td>Корпус</td> <td><input class="form-control" id="block_input" type="text" value="<?=$how_get['block'];?>"/></td> </tr>
<tr> <td>Квартира/Офис</td> <td><input class="form-control" id="flat_office_input" type="text" value="<?=$how_get['flat_office'];?>"/></td> </tr> */ ?>
<tr> <td>Телефон</td> <td><input class="form-control" id="phone_input" type="text" value="<?=$how_get['phone'];?>"/></td> </tr>
<!--<tr><td>Тариф</td>-->
<!--    <td>-->
  
<!--    	<label class="tariff_delivery_label"><label for="tariff_delivery_for_russia">По России</label><input name="tariff_delivery" id="tariff_delivery_for_russia" type="radio" value="for_russia"/></label>-->
<!--        <label class="tariff_delivery_label"><label for="tariff_delivery_cargo">Грузовой</label><input name="tariff_delivery" id="tariff_delivery_cargo" type="radio" value="cargo"/></label>-->
<!--        <label class="tariff_delivery_label"><label for="tariff_delivery_express">Доставка</label><input name="tariff_delivery" id="tariff_delivery_express" checked type="radio" value="express"/></label>-->
<!--        <label class="tariff_delivery_label"><label for="tariff_delivery_courier">Курьер</label><input name="tariff_delivery" id="tariff_delivery_courier" type="radio" value="courier"/></label>-->
<!--    </td>-->
<!--</tr>-->
</table>
<ul class="delivery-tariff-list">
  
<!--   <li class="delivery-tariff-list__item">
       <input type="radio" id="tariff_delivery_for_russia" class="delivery-tariff-list__item_input" checked name="tariff_delivery" value="for_russia">
        <label for="tariff_delivery_for_russia" class="delivery-tariff-list__item_label">
         <div class="delivery-tariff-list__item_title">Доставка по России</div>
          <div class="delivery-tariff-list__item_text">Доставка отлично подойдет для передачи посылок по России.</div>
        </label> 
   </li>  -->
          
    <li class="delivery-tariff-list__item">
        <input type="radio" id="tariff_delivery_express" class="delivery-tariff-list__item_input" checked name="tariff_delivery" value="express">
        <label for="tariff_delivery_express" class="delivery-tariff-list__item_label">
          <div class="delivery-tariff-list__item_title">Доставка</div>
          <div class="delivery-tariff-list__item_text">Доставка отлично подойдет для передачи небольших (до 20 кг) срочных посылок по городу. Важно чтобы запчасти помещались в обычный автомобиль такси. 				Минимальная стоимость 100 ₽.</div>
        </label>
    </li>
    <li class="delivery-tariff-list__item">
        <input type="radio" id="tariff_delivery_courier" class="delivery-tariff-list__item_input" name="tariff_delivery" value="courier">
        <label for="tariff_delivery_courier" class="delivery-tariff-list__item_label">
          <div class="delivery-tariff-list__item_title">Курьер</div>
          <div class="delivery-tariff-list__item_text">Курьерская доставка - это доставка до двери. Условия те же -  небольшие запчасти, помещающиеся в автомобиль и вес до 20 кг. Минимальная стоимость 130 ₽.</div>
        </label>
    </li>
    <li class="delivery-tariff-list__item">
        <input type="radio" id="tariff_delivery_cargo" class="delivery-tariff-list__item_input" name="tariff_delivery" value="cargo">
        <label for="tariff_delivery_cargo" class="delivery-tariff-list__item_label">
          <div class="delivery-tariff-list__item_title">Грузовой</div>
          <div class="delivery-tariff-list__item_text">Тариф для доставки крупногабаритных деталей (ДВС, КПП, кузовные детали) или весом более 20 кг. Минимальная стоимость 500 ₽.</div>
        </label>
    </li>
    
</ul>
<div class="nwy-delivery-button">
	<div id="nwy-delivery-answer"></div> <a class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="calculateDelivery();">Рассчитать доставку</a>
</div>
<div class="nwy-delivery-info"><i class="fa fa-info-circle" aria-hidden="true"></i> <span class="nwy-delivery-info__text">Заказ доставки осуществляет менеджер магазина. После получения Вашего заказа на склад отгрузки, наш менеджер свяжется с Вами для согласования времени доставки.</span></div>
<!-- Создаём контейнер для карты -->
<div id="map" style="width: 100%; height: 400px"></div>
<script src="https://api-maps.yandex.ru/2.1/?apikey=<?php echo $parameters_values['api_key']; ?>&lang=ru_RU" type="text/javascript"></script>
<!-- Создаём карту -->
<script type="text/javascript">
    // Функция ymaps.ready() будет вызвана, когда
    // загрузятся все компоненты API, а также когда будет готово DOM-дерево.
    ymaps.ready(init);
    let deliveryMap = new Object;
    let fromPoint = [54.00574925510126, 38.25754945582414];
    function init() {
        // Создание карты.
        // https://tech.yandex.ru/maps/doc/jsapi/2.1/dg/concepts/map-docpage/
        
        deliveryMap = new ymaps.Map("map", {
            // Координаты центра карты.
            // Порядок по умолчнию: «широта, долгота».
            center: fromPoint,
            // Уровень масштабирования. Допустимые значения:
            // от 0 (весь мир) до 19.
            zoom: 16,
            // Элементы управления
            // https://tech.yandex.ru/maps/doc/jsapi/2.1/dg/concepts/controls/standard-docpage/
            controls: [
 
                'zoomControl', // Ползунок масштаба
                //'rulerControl', // Линейка
                //'routeButtonControl', // Панель маршрутизации
                //'trafficControl', // Пробки
                //'typeSelector', // Переключатель слоев карты
                'fullscreenControl', // Полноэкранный режим
 
                // Поисковая строка
                // new ymaps.control.SearchControl({
                //     options: {
                //         // вид - поисковая строка
                //         size: 'large',
                //         // Включим возможность искать не только топонимы, но и организации.
                //         provider: 'yandex#search'
                //     }
                // })
 
            ]
        }, {
            searchControlProvider: 'yandex#search',
            yandexMapDisablePoiInteractivity: true
          });
 
        // Добавление метки
        let myPlacemark = new ymaps.Placemark(fromPoint, {
            // Хинт показывается при наведении мышкой на иконку метки.
            hintContent: 'Пункт отправки заказа',
            // Балун откроется при клике по метке.
            balloonContent: 'Тульская область, г. Новомосковск, Техническая 11'
        });
 
        // После того как метка была создана, добавляем её на карту.
        deliveryMap.geoObjects.add(myPlacemark);
        
    }
</script>
<?php
//Подключаем общий модуль принятия пользовательского соглашения
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/users_agreement_module.php");
?>
<div class="text-center">
	<a class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="nextStep();">Продолжить</a>
</div>
<script>
function calculateDelivery()
{
    //Записываем параметра способа получения в куки
	//Объект способа доставки
    let how_get = new Object;
		how_get.mode = <?php echo $current_obtain_mode; ?>;
		how_get.region = document.getElementById("region_input").value;
		how_get.city   = document.getElementById("city_input").value;
		how_get.street = document.getElementById("street_input").value;
		how_get.house  = document.getElementById("house_input").value;
		how_get.tariff = $("input[name=tariff_delivery]:checked").val();
		
		if(how_get.region == "" || how_get.region == null)
    	{
    		alert("Поле Область - обязательно для заполнения");
    		return;
    	}
		if(how_get.city == "" || how_get.city == null)
    	{
    		alert("Поле Город - обязательно для заполнения");
    		return;
    	}
    	if(how_get.street == "" || how_get.street == null)
    	{
    		alert("Поле Улица - обязательно для заполнения");
    		return;
    	}
    	if(how_get.house == "" || how_get.house == null)
    	{
    		alert("Поле Дом - обязательно для заполнения");
    		return;
    	}
    	if(how_get.tariff == "" || how_get.tariff == null)
    	{
    		alert("Поле Тариф - обязательно для заполнения");
    		return;
    	}
		
        // Строка с адресом, который необходимо геокодировать
        let address = how_get.region + ' обл., г.' + how_get.city + ', ул. ' + how_get.street + ' ' + how_get.house;
        
        console.log(address);
        
        let coordinates = '';
        
        let geocoder = ymaps.geocode(address);
        
            // После того, как поиск вернул результат, вызывается callback-функция
            geocoder.then(
                function (res) {
                    
                    // координаты объекта
                    coordinates = res.geoObjects.get(0).geometry.getCoordinates();
                    
                    
                    if (coordinates !== '') {
                        
                        $(document).ready(function() {
                            
                            token_api = "<?php echo $parameters_values['token']; ?>";
                            
                            console.log("Отправка данных для получения стоимости поездки.");
                            console.log("Координаты от: " + fromPoint + ", Координаты до: " + coordinates);
                            $.ajax({
                                type: "POST",
                                url: '/content/shop/obtaining_modes/yandex_delivery/ajax_getPriceDelivery.php',
                                dataType : 'html',
                                data: {
                                    action: 'getPriceDelivery',
                                    token: token_api,
                                    from: fromPoint,
                                    to: coordinates,
                                    tariff: how_get.tariff,
                                  
                                  	address: address,
                                    markup: <?= (int) $parameters_values['markup']; ?>
                                },
                                success: function(response)
                                {
                                    console.log("response", response);
                                    
                                    let result_html = $(response).find("#nwy-delivery-answer");
                                    
                     				$('#nwy-delivery-answer').fadeIn().html(result_html.html());
                                }
                           });
                           
                           return;
                        });
            
                        let multiRoute = new ymaps.multiRouter.MultiRoute({
                            // Описание опорных точек мультимаршрута.
                            referencePoints: [
                                fromPoint,
                                coordinates
                            ],
                            // Параметры маршрутизации.
                            params: {
                                // Ограничение на максимальное количество маршрутов, возвращаемое маршрутизатором.
                                results: 1
                            }
                            }, {
                                // Автоматически устанавливать границы карты так, чтобы маршрут был виден целиком.
                                boundsAutoApply: true
                          });
                    
                         deliveryMap.geoObjects.removeAll();
                    
                        // Добавляем мультимаршрут на карту.
                        deliveryMap.geoObjects.add(multiRoute);
                    
                    } else {
                    
                        alert("Ошибка при получении координат адреса доставки. Попробуйте позже или свяжитесь с менеджером магазина.");
                		return;
                    
                     }
         
            }
        );
            
        
}
function nextStep()
{
	if( !check_user_agreement() )
	{
		return;
	}
	
	//Записываем параметра способа получения в куки
	//Объект способа доставки
    let how_get = new Object;
		how_get.mode = <?php echo $current_obtain_mode; ?>;
		how_get.region = encodeURIComponent(document.getElementById("region_input").value);
		how_get.city   = encodeURIComponent(document.getElementById("city_input").value);
		how_get.street = encodeURIComponent(document.getElementById("street_input").value);
		how_get.house  = encodeURIComponent(document.getElementById("house_input").value);
		how_get.phone = encodeURIComponent(document.getElementById("phone_input").value);
		how_get.tariff = encodeURIComponent($("input[name=tariff_delivery]:checked").val());
		
		how_get.price = encodeURIComponent($("#nwy-delivery-info__price").text());
	
	if(how_get.region == "" || how_get.region == null)
	{
		alert("Поле Область - обязательно для заполнения");
		return;
	}
	if(how_get.city == "" || how_get.city == null)
	{
		alert("Поле Город - обязательно для заполнения");
		return;
	}
	if(how_get.street == "" || how_get.street == null)
	{
		alert("Поле Улица - обязательно для заполнения");
		return;
	}
	if(how_get.house == "" || how_get.house == null)
	{
		alert("Поле Дом - обязательно для заполнения");
		return;
	}
	if(how_get.phone == "" || how_get.phone == null)
	{
		alert("Поле Телефон - обязательно для заполнения");
		return;
	}
	if(how_get.tariff == "" || how_get.tariff == null)
	{
		alert("Поле Тариф - обязательно для заполнения");
		return;
	}
	if(how_get.price == "" || how_get.price == null)
	{
		alert("Перед тем как продолжить, рассчитайте стоимость поездки!");
		return;
	}
	
	//Устанавливаем cookie (на полгода)
    var date = new Date(new Date().getTime() + 15552000 * 1000);
    document.cookie = "how_get="+JSON.stringify(how_get)+"; path=/; expires=" + date.toUTCString();
	
	//Запишем данные о доставке для отображения при следующем оформлении заказа
    document.cookie = "how_get_yandex_delivery="+JSON.stringify(how_get)+"; path=/; expires=" + date.toUTCString();
	location = "/shop/checkout/confirm";
}
</script>