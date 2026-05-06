<?php

    function print_price($price)
    {
        return number_format($price, 2, '.', ' ');
    }
	
	function get_total_price_services($record_id) {
		global $db_link;
		
		$select = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?;");
		$select->execute([$record_id]);
		
		$select_res = $select->fetchAll(PDO::FETCH_ASSOC);
		
		$total_sum = 0;
		
		foreach ($select_res as $service_price) {
			$total_sum += $service_price["price"] * $service_price["count"];
		}
		
		return $total_sum;
	}
	
    $time_to = $_COOKIE['time_to_records'] > 0 ? $_COOKIE['time_to_records'] : '';
    $time_from = $_COOKIE['time_from_records'] > 0 ? $_COOKIE['time_from_records'] : '';

    $select_data_services = $db_link->prepare("SELECT * FROM `car_services`;");
    $select_data_services->execute();
    $res_sto = $select_data_services->fetchAll(2);

    $select_data_workers = $db_link->prepare("SELECT * FROM `car_service_personal`;");
    $select_data_workers->execute();
    $res_workers = $select_data_workers->fetchAll(2);

    $arr_sto_data = [];

    foreach ($res_sto as $sto)
    {
        $select = $db_link->prepare("SELECT * FROM `car_service_records` WHERE `sto_id` = ? AND `time` BETWEEN ? AND ?;");
		$time_from_dt = date('Y-m-d H:i:s', floor($time_from));
		$time_to_dt = date('Y-m-d H:i:s', floor($time_to));
		
		
		
        $select->execute([$sto["id"], $time_from_dt, $time_to_dt]); 
        $res_services_sto = $select->fetchAll(2);

        $total_price = 0;  
        $total_count = 0;
        $total_count_valid = 0;
        //Получаем цену каждой услуги
        foreach ($res_services_sto as $service) {
            $price = get_total_price_services($service["id"]);
            if ($price > 0)
            {
                $total_price += $price;
                $total_count_valid++;
            }
            $total_count++;
        }
        $arr_sto_data[] = [
            'sto_id' => $sto['id'],
            'sto_caption' => $sto['caption'],
            'count_records' => $total_count,
            'valid_records' => $total_count_valid,
            'sum_price_records' => $total_price
        ];
    }

    $arr_workers_data = [];

    foreach ($res_workers as $worker) {
        // Преобразуем миллисекунды в формат DATETIME
		$time_from_dt = date('Y-m-d H:i:s', floor($time_from));
		$time_to_dt = date('Y-m-d H:i:s', floor($time_to));

		$select_records = $db_link->prepare("
			SELECT *
			FROM `car_service_records`
			WHERE `worker_id` = ?
			AND `time` BETWEEN ? AND ?
		");
		$select_records->execute([$worker["id"], $time_from_dt, $time_to_dt]);

        $res_records = $select_records->fetchAll(2);

        $total_price = 0;
        $total_count = 0;
        $total_hourly_rate = 0;
		
		
		
		$unique_days = [];

		foreach ($res_records as $record) {
			$price = get_total_price_services($record["id"]);
			if ($price > 0)
			{
				$total_price += $price;
				$total_count++;

				// считаем уникальные дни
				$day_key = date('Y-m-d', strtotime($record['time']));
				$unique_days[$day_key] = true;

				$services = json_decode($record['services_arr'], true);
				if (is_array($services)) {
					foreach ($services as $service_id)
					{
						$select_hourly_rate_fact = $db_link->prepare("
							SELECT `hourly_rate_count`
							FROM `car_service_prices`
							WHERE `sto_id` = ? AND `id_tree_cars_list` = ? AND `id_tree_services_list` = ?
						");
						$select_hourly_rate_fact->execute([$record["sto_id"], $record['car_id'], $service_id]);
						$res_hourly_rate = $select_hourly_rate_fact->fetch(PDO::FETCH_ASSOC);
						if ($res_hourly_rate && isset($res_hourly_rate['hourly_rate_count'])) {
							$total_hourly_rate += $res_hourly_rate['hourly_rate_count'];
						}
					}
				}
			}
		}

		$unique_days_count = count($unique_days);


        $arr_workers_data[] = [
            "id" => $worker["id"],
            "fio" => $worker["fio"],
            'hours_work' => $total_hourly_rate,  
            'salary' => $worker["standart_hourly_rate"] * $unique_days_count,
            'percent' => ($total_price * ($worker["service_percent"] / 100)),
            'record_count' => $total_count,
            'total_service_price' => $total_price,
            'standart_hourly_rate' => $worker["standart_hourly_rate"],
            'service_percent' => $worker["service_percent"]
        ];
    }


