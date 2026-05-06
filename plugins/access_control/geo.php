<?php


$coords = isset($_POST['position']) && !empty($_POST['position']) ? json_decode($_POST['position'], true) : null;

$result = array('result' => false);

// Автоматическое определение ГЕО-узла для клиента
if(1){
	// https://ipwhois.app/json/80.82.46.136
	// https://ipwhois.io/ru/documentation
	// https://ip-api.com/docs/api:serialized_php
	
	// Соединение с БД
	if(empty($db_link)){
		require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
		$DP_Config = new DP_Config;
		try
		{
			$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
		}
		catch (PDOException $e) 
		{
			exit("No DB connect");
		}
		$db_link->query("SET NAMES utf8;");
	}

	// Определение растояния между 2-мя координатами
	function distance($lat1,$lon1,$lat2,$lon2)
	{
		$lat1=deg2rad($lat1);
		$lon1=deg2rad($lon1);
		$lat2=deg2rad($lat2);
		$lon2=deg2rad($lon2);
		$delta_lat=($lat2 - $lat1);
		$delta_lng=($lon2 - $lon1);
		return round( 6378137/1000 * acos( cos( $lat1 ) * cos( $lat2 ) * cos( $lon1 - $lon2 ) + sin( $lat1 ) * sin( $lat2 ) ),1 );
	}

	// Проверяем наличие выставленного гео-узла
	if(!empty($_COOKIE["my_city"])){
		$geo_query = $db_link->prepare('SELECT * FROM `shop_geo` WHERE `id` = ? AND `level` = 3 LIMIT 1;');
		$geo_query->execute(array($_COOKIE["my_city"]));
		$geo = $geo_query->fetch();
		if(empty($geo['id'])){
			$_COOKIE["my_city"] = null;// Такого узла нет - удяляем
		}
	}

    $geo_name = '';
	// Если гео-узел не определен - выставим гео-узел автоматически
	if(empty($_COOKIE["my_city"])){
		$geo_id = null;// ID гео-точки
		$distance = null;// Растояние до магазина
		$distance = null;// Широта клиента
		$lat_user = null;// Широта клиента
		$lon_user = null;// Долгота клиента
		$lat_office = null;// Широта магазина
		$lon_office = null;// Долгота магазина

        if(!empty($coords) && !empty($coords['latitude']) && !empty($coords['longitude'])) {
            $lat_user = $coords["latitude"];
		    $lon_user = $coords["longitude"];
        } else {
    		// Получаем информацию о местонахождении клиента по IP адресу
    		$data_url = array();
    		$data_url['ipwhois.app'] = 'https://ipwhois.app/json/'.$_SERVER['REMOTE_ADDR'];
    		$data_url['ip-api.com']  = 'http://ip-api.com/json/'.$_SERVER['REMOTE_ADDR'];
    
    		foreach($data_url as $key => $url){
    			$curl = curl_init();
    			curl_setopt($curl, CURLOPT_URL, $url);
    			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    			curl_setopt($curl, CURLOPT_POST, false);
    			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); 
    			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    			$curl_result = curl_exec($curl);
    			
    			$curl_result = json_decode($curl_result, true);
    
    			switch($key){
    				case 'ipwhois.app' :
    					if($curl_result["success"] === true)
    					{
    					   $lat_user = $curl_result["latitude"];
    					   $lon_user = $curl_result["longitude"];
    					}
    				break;
    				case 'ip-api.com' :
    					if($curl_result["status"] === "success")
    					{
    					   $lat_user = $curl_result["lat"];
    					   $lon_user = $curl_result["lon"];
    					}
    				break;
    			}
    			
    			if(!empty($lat_user))
    			{
    			   curl_close($curl);
    			   break;
    			}
    		}
        }
		
		// Если координаты получены
		if(!empty($lat_user) && !empty($lon_user)){
			$geo_arr = array();
			$geo_query = $db_link->prepare('SELECT * FROM `shop_geo`;');
			$geo_query->execute();
			while($geo_record = $geo_query->fetch()){
				$geo_arr[$geo_record['level']][] = $geo_record['id'];// Массыив id гео-узлов по уровням вложенности
			}
			
			// Цикл по магазинам
			$office_query = $db_link->prepare('SELECT * FROM `shop_offices`;');
			$office_query->execute();
			while($office_record = $office_query->fetch()){
				$office_coordinates = explode(',', $office_record["coordinates"]);
				if(!empty($office_coordinates[0]) && !empty($office_coordinates[1])){
					$lat_office = trim($office_coordinates[0]);
					$lon_office = trim($office_coordinates[1]);
					
					// Определяем расстояние от клиента до магазина
					$distance_this = distance($lat_office,$lon_office,$lat_user,$lon_user);
					
					// Цикл по привязанным гео-узлам к данному магазину
					$query = $db_link->prepare('SELECT `shop_geo`.* FROM `shop_offices_geo_map` LEFT JOIN `shop_geo` ON `shop_geo`.`id` =  `shop_offices_geo_map`.`geo_id` WHERE `office_id` = ?;');
					$query->execute(array($office_record['id']));
					while($record = $query->fetch()){
						// Если данный гео-узел 3-го уровня и он ближе чем ранее выставленный узел
						if(in_array($record['id'],$geo_arr['3'])){
							if(($distance_this <= 50) && (($distance_this < $distance) || ($distance === null))){
								$distance = $distance_this;
								$geo_id = $record['id'];
								$geo_name = $record['value'];
							}
						}
					}
					
				}
			}
		}
		
		// Если гео-узел не определен - выставим самый первый из созданных гео-узлов 3-го уровня к которому привязан первый магазин
		if(empty($geo_id)){
			$query = $db_link->prepare('SELECT `id`, `value` FROM `shop_geo` WHERE `id` IN(SELECT `geo_id` FROM `shop_offices_geo_map` WHERE `office_id` = (SELECT `id` FROM `shop_offices` ORDER BY `id` ASC LIMIT 1)) AND `level` = 3 ORDER BY `id` ASC LIMIT 1;');
			$query->execute(array($office_record['id']));
			$record = $query->fetch();
			if(!empty($record['id'])){
				$geo_id = $record['id'];
				$geo_name = $record['value'];
			}
		}
		
		// Гео-узел определен и выставлен в COOKIE
		if(!empty($geo_id)){
			$_COOKIE["my_city"] = $geo_id;
			$cookietime = time()+9999999;//на долго
			setcookie("my_city", $geo_id, $cookietime, "/");
			
			$result['result'] = true;
			$result['geo_name'] = $geo_name;
			$result['geo_id'] = $geo_id;
			
			exit(json_encode($result));
		}
	}
}

?>