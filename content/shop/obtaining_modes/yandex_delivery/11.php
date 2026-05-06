<?php

$params = json_encode(array(
        'source' => array('platform_station_id' => '00120266-3448-4986-b8db-28efea7c34ff'),
        'tariff' => 'time_interval',
        'destination' => array('address' => "Московская область, Москва, Парковая улица, дом 5"),
        'total_weight' => 1000
        
        ));
        $token = "y0__xCBs46LCBix9Bwg_LuF6xK-mb4ny-ZLy3wccyqW7eK1N9CNwA";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://b2b-authproxy.taxi.yandex.net/api/b2b/platform/pricing-calculator?is_oversized=0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept-Language: ru", "Content-Type: application/json"]);
        $execute = curl_exec($ch);
  
  
  /*
$params = json_encode(array(
        'source' => array('platform_station_id' => 'fbed3aa1-2cc6-4370-ab4d-59c5cc9bb924'),
        'tariff' => 'time_interval',
        'destination' => array('address' => "Московская область, Москва, Парковая улица, дом 5"),
        'total_weight' => 1000,
        'delivery_options' => array('"from": 0,
            "to": 0,
            "delivered_by_post": false')
        ));
        $token = "y0__xCBs46LCBix9Bwg_LuF6xK-mb4ny-ZLy3wccyqW7eK1N9CNwA";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://b2b-authproxy.taxi.yandex.net/api/b2b/platform/pickup-points/list");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept-Language: ru", "Content-Type: application/json"]);
        $execute = curl_exec($ch);
  */
        echo "<pre>";
        echo date('Y-m-d H:i:s');
        echo "</pre>";
        echo "</br>";
        echo "<pre>";
        print_r($execute);
        echo "</pre>";
        echo "</br>";
        echo "<pre>";
        print_r(curl_getinfo($ch));
        echo "</pre>";
        echo "</br>";
        echo "<pre>";
        print_r(curl_error($ch));
        echo "</pre>";
        echo "</br>";
        
?>