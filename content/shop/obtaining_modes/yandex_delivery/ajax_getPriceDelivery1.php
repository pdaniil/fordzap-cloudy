<?php

// Получаем стоимость поездки в Яндекс.Такси

// Через ajax получаем переменные координат точек отправки и доставки, токен клиент.



ini_set('error_reporting', E_ALL);

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);





$action = $_POST["action"];



if (isset($action) && $action == "getPriceDelivery") {

    

    $token     = $_POST['token'];

    $fromPoint = $_POST['from'];

    $toPoint   = $_POST['to'];

    $tariff   = $_POST['tariff'];

		$markup   = $_POST['markup'];

    

    // Получаем координаты точек

    $lon_f = 0;

    $lat_f = 0;

    

    $lon_t = 0;

    $lat_t = 0;

    

    if(is_array($fromPoint)) {

        $lat_f = (float) $fromPoint[0];

        $lon_f = (float) $fromPoint[1];

    }

    

    if(is_array($fromPoint)) {

        $lat_t = (float) $toPoint[0];

        $lon_t = (float) $toPoint[1];

    }

    

    // echo $lat_f; echo "<br>";

    // echo $lon_f; echo "<br>";

    // echo $lat_t; echo "<br>";

    // echo $lon_t;

    

    $params = json_encode( array(

        'route'           => array(array(0 => $lon_f, 1 => $lat_f), array(0 => $lon_t, 1 => $lat_t)),

        'requirements'    => new stdClass(),

        'phone'           => '+79263820684',

        'selected_class'  => $tariff

    ) );

    

    

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://business.taxi.yandex.ru/api/1.0/estimate");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_HEADER, 0);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $token"]);

    $execute = curl_exec($ch);

    

    $result = json_decode($execute);

    

    curl_close($ch);

    

    // Формируем ответ

    

    $answer = '';

    

    // Если ответ пустой

    if (!empty($result)) {

        

        if (!empty($result->errors)) {

        

                $errors = $result->errors;

                

                if(is_array($errors)) {

                    

                    foreach($errors as $error) {

                        

                        $answer .= $error->text;

                        

                    }

                    

                }

                

            

        } else {

            

            if (!empty($result->service_levels)) {

                

                $services = $result->service_levels;

                

                foreach($services as $service) {

                    

                    $tariff_name = $service->class;

                    

                    if($tariff_name !== $tariff) continue;

                    

                    $price = $service->price_raw;

                    

                    // Делаем надбавку к сумме заказа

                    // $price = $price + $price*0.2;

										$price = $price + $price*$markup/100;

                    $price = ceil($price/10) * 10;

                    

                    $answer .= "<div class=\"nwy-delivery\">≈ <span class=\"nwy-delivery-info__price\" id=\"nwy-delivery-info__price\">$price</span> руб.</div>";

                    

                }

                

            } else {

                

                $answer = "<span class=\"nwy-delivery-error\">Сервис Яндекс.Доставка вернул пустой результат. Проверьте адрес доставки или обратитесь к менеджеру магазина.</span>";

            }

                

        }

        

    } else {

        $answer = "<span class=\"nwy-delivery-error\">Сервис Яндекс.Доставка вернул пустой результат. Проверьте адрес доставки или обратитесь к менеджеру магазина.</span>";

    }

  

    

    $result = "<div><div id=\"nwy-delivery-answer\">$answer</div></div>";

    exit($result);

    

} else {

    

    exit("Not allowed method getPriceDelivery");

    

}











?>