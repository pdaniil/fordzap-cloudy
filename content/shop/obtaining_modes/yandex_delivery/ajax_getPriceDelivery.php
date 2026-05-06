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
  	$address = $_POST['address'];
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
  
    $execute = "";
    //$token = "y0__xCBs46LCBix9Bwg_LuF6xK-mb4ny-ZLy3wccyqW7eK1N9CNwA";
    $platform_station_id = '00120266-3448-4986-b8db-28efea7c34ff';
    $total_weight = 1000;//вес посылки в граммах
  	if($tariff == "for_russia")
    {
        $params = json_encode(array(
        'source' => array('platform_station_id' => $platform_station_id),
        'tariff' => 'time_interval',
        'destination' => array('address' => $address),
        'total_weight' => $total_weight
        ));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://b2b-authproxy.taxi.yandex.net/api/b2b/platform/pricing-calculator?is_oversized=0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept-Language: ru", "Content-Type: application/json"]);
        $execute = curl_exec($ch);
    }
  	else
    {
      $params = json_encode( array(
        'route_points' => array(
            0 => array("coordinates" => array(0 => $lon_f, 1 => $lat_f)),
            1 => array("coordinates" => array(0 => $lon_t, 1 => $lat_t))
        ),
        'requirements'    => array('taxi_class' => $tariff)
    	) );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://b2b.taxi.yandex.net/b2b/cargo/integration/v2/check-price");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept-Language: ru"]);
        $execute = curl_exec($ch);
    }
    
    
    $result = json_decode($execute);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    echo "</br>";
    
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
            
            if (!empty($result->price)) {
                
                
                $price = $result->price;
                
                // Делаем надбавку к сумме заказа
                // $price = $price + $price*0.2;
				
				$price = $price + $price*$markup/100;
                $price = ceil($price/10) * 10;
                
                $answer .= "<div class=\"nwy-delivery\">≈ <span class=\"nwy-delivery-info__price\" id=\"nwy-delivery-info__price\">$price</span> руб.</div>";

                
            } 
            elseif (!empty($result->pricing_total)) {
                
                
                $price = $result->pricing_total;
                $price = preg_replace("/[^0-9]/", "", $price);
                // Делаем надбавку к сумме заказа
                // $price = $price + $price*0.2;
				
				$price = $price + $price*$markup/100;
                $price = ceil($price/10) * 10;
                
                $answer .= "<div class=\"nwy-delivery\">≈ <span class=\"nwy-delivery-info__price\" id=\"nwy-delivery-info__price\">$price</span> руб.</div>";

                
            }
            else {
                
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