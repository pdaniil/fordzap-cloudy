<?php 

class WorkersSalary {
    
    public static function get_total_price_services($record_id) {
        
        global $db_link;

        $select = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?;");
        $select->execute([$record_id]);
        
        $select_res = $select->fetchAll(PDO::FETCH_ASSOC);
        
        $total_sum = 0;
        
        foreach ($select_res as $service_price) {
            // Получаем стоимость услуги с учетом скидки
            $price = $service_price["price"] * $service_price["count"];
            
            // Применяем скидку, если она указана
            if (isset($service_price["discount"]) && $service_price["discount"] > 0) {
                $discount_percent = $service_price["discount"];
                $price = $price * (1 - $discount_percent / 100);
            }
            
            $total_sum += $price;
        }
        
        return $total_sum;
        
    }  
    
    /**
     * Получает сумму для начисления сотруднику по конкретной услуге
     * Учитывает фиксированную цену услуги (fix_price), если она больше 0
     */
    public static function get_service_worker_payment($service, $worker_percent) {
        // Проверяем наличие фиксированной цены для сотрудника
        if (isset($service["fix_price"]) && $service["fix_price"] > 0) {
            // Если есть fix_price, используем её как есть (умножаем на количество)
            return $service["fix_price"] * $service["count"];
        } else {
            // Иначе рассчитываем по проценту
            $price = $service["price"] * $service["count"];
            
            // Применяем скидку, если она указана
            if (isset($service["discount"]) && $service["discount"] > 0) {
                $discount_percent = $service["discount"];
                $price = $price * (1 - $discount_percent / 100);
            }
            
            return $price * ($worker_percent / 100);
        }
    }
    
    public static function calculate_worker_salary($worker_user_id) {
        
        global $db_link;

        // Получаем данные конкретного сотрудника по user_id
        $select_worker = $db_link->prepare("SELECT * FROM `car_service_personal` WHERE `user_id` = ? LIMIT 1;");
        $select_worker->execute([$worker_user_id]);
        $worker = $select_worker->fetch(PDO::FETCH_ASSOC);
        
        if (!$worker) {
            return 0;
        }
        
        // Получаем все записи для этого сотрудника
        $select_records = $db_link->prepare("
            SELECT *
            FROM `car_service_records`
            WHERE `worker_id` = ?
        ");
        $select_records->execute([$worker["id"]]);
        $res_records = $select_records->fetchAll(PDO::FETCH_ASSOC);
        
        $unique_days = [];
        $total_percent_salary = 0; // Сумма от всех услуг
        
        foreach ($res_records as $record) {
            // Получаем услуги для данной записи
            $select_services = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?;");
            $select_services->execute([$record["id"]]);
            $services = $select_services->fetchAll(PDO::FETCH_ASSOC);
            
            $record_salary = 0;
            
            foreach ($services as $service) {
				// Проверяем статус услуги (только статус 21 идет в расчет)
				if (($service["status_id"] ?? 0) != 21) {
					continue; // Пропускаем услуги, не готовые к оплате
				}
				
				// Рассчитываем сумму для каждой услуги с учетом fix_price
				$record_salary += self::get_service_worker_payment($service, $record["worker_percent"]);
			}
            
            if ($record_salary > 0) {
                $total_percent_salary += $record_salary;
                
                $day_key = date('Y-m-d', strtotime($record['time']));
                $unique_days[$day_key] = true;
            }
        }
        
        $unique_days_count = count($unique_days);
        
        // Считаем базовую зарплату (почасовая)
        $base_salary = $worker["standart_hourly_rate"] * $unique_days_count;
        
        return $base_salary + $total_percent_salary;
        
    }
    
	public static function get_detal_info($worker_id) {
		global $db_link;
		
		$query = $db_link->prepare("SELECT * FROM `car_service_records` WHERE `worker_id` = ? ORDER BY `time` DESC;");
		$query->execute([$worker_id]);
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		
		$info_arr = [];
		
		foreach ($res as $info) {
			// Получаем услуги для данной записи
			$select_services = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?;");
			$select_services->execute([$info["id"]]);
			$services = $select_services->fetchAll(PDO::FETCH_ASSOC);
			
			$record_services_info = [];
			$total_price = 0;
			$total_percent_salary = 0;
			
			foreach ($services as $service) {
				$price = $service["price"] * $service["count"];
				
				// Применяем скидку, если она указана
				if (isset($service["discount"]) && $service["discount"] > 0) {
					$discount_percent = $service["discount"];
					$price = $price * (1 - $discount_percent / 100);
				}
				
				$total_price += $price;
				
				// Проверяем статус услуги (21 - выполнена, готова к оплате)
				$is_ready_for_payment = ($service["status_id"] ?? 0) == 21;
				
				// Рассчитываем выплату сотруднику только если статус 21
				$worker_payment = 0;
				if ($is_ready_for_payment) {
					$worker_payment = self::get_service_worker_payment($service, $info["worker_percent"]);
					$total_percent_salary += $worker_payment;
				}
				
				$record_services_info[] = [
					"service_name" => $service["caption"] ?? "Услуга",
					"price" => $price,
					"fix_price" => $service["fix_price"] ?? 0,
					"worker_payment" => $worker_payment,
					"payment_type" => ($service["fix_price"] ?? 0) > 0 ? "fixed" : "percent",
					"status_id" => $service["status_id"] ?? 0,
					"is_ready" => $is_ready_for_payment,
					"status_name" => self::get_status_name($service["status_id"] ?? 0) // добавить метод для получения названия статуса
				];
			}
			
			$info_arr[] = [
				"price" => $total_price,
				"percent" => $info["worker_percent"],
				"percent_salary" => $total_percent_salary,
				"date" => $info["time"],
				"order_id" => $info["orders_ids"],
				"type" => "record",
				"services" => $record_services_info,
				"has_unpaid_services" => $total_percent_salary < $total_price // Флаг, что есть неоплаченные услуги
			];
		}
		
		return $info_arr;
	}

	// Добавить вспомогательный метод для получения названия статуса
	public static function get_status_name($status_id) {
		global $db_link;
		
		$select_statuses = $db_link->prepare("SELECT `id`, `name` FROM `shop_orders_items_statuses_ref` WHERE `for_service` = 1;");
		$select_statuses->execute();
		
		$res_select_statuses = $select_statuses->fetchAll(PDO::FETCH_ASSOC);
		
		$statuses = [];
		
		foreach ($res_select_statuses as $status_info) {
			$statuses[$status_info["id"]] = $status_info["name"];
		}
		
		return $statuses[$status_id] ?? "Неизвестный статус";
	}
    
    public static function paySalary($worker_id, $salary) {
        global $db_link;
        
        $query = $db_link->prepare("INSERT INTO `car_service_personal_salary` (`worker_id`, `time`, `salary`) VALUES (?, NOW(), ?);");
        $query->execute([$worker_id, $salary]);
    }
    
    public static function sendToBalance($worker_id, $salary, $office_id) {
        global $db_link;
        
        $select_user_id = $db_link->prepare("SELECT `user_id` FROM `car_service_personal` WHERE `id` = ?");
        $select_user_id->execute([$worker_id]);
        $res = $select_user_id->fetch(PDO::FETCH_ASSOC);
        
        if (!$res || $res["user_id"] <= 0) {
            return false;
        }
        
        $sql = "INSERT INTO `shop_users_accounting` 
                (`id`, `user_id`, `time`, `income`, `amount`, `operation_code`, `active`, `pay_orders`, `tech_value_text`, `order_id`, `office_id`) 
                VALUES (NULL, ?, ?, '1', ?, '5', '1', NULL, NULL, '0', ?)";
        
        $query = $db_link->prepare($sql);
        return $query->execute([$res["user_id"], time(), $salary, $office_id]);
    } 
    
    public static function get_salary_info($worker_id) {
        global $db_link;
        
        $query = $db_link->prepare("SELECT * FROM `car_service_personal_salary` WHERE `worker_id` = ? ORDER BY `time` DESC;");
        $query->execute([$worker_id]);
        
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        
        $info_arr = [];
        foreach ($res as $info) {
            $info_arr[] = [
                "salary" => $info["salary"],
                "date" => $info["time"],
                "type" => "payment"
            ];
        }
        
        return $info_arr;
    }
    
    public static function get_bill_info($worker_id) {
        $records_info = self::get_detal_info($worker_id);
        $salary_info = self::get_salary_info($worker_id);
        
        // Объединяем оба массива
        $bill_info = array_merge($records_info, $salary_info);
        
        // Сортируем по дате в обратном порядке (самые новые первыми)
        usort($bill_info, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $bill_info;
    }
    
    // Новый метод для получения сводной статистики по зарплате
    public static function get_salary_summary($worker_id) {
        $records = self::get_detal_info($worker_id);
        $payments = self::get_salary_info($worker_id);
        
        $total_earned = 0;
        $total_paid = 0;
        
        foreach ($records as $record) {
            $total_earned += $record["percent_salary"] ?? 0;
        }
        
        foreach ($payments as $payment) {
            $total_paid += $payment["salary"];
        }
        
        return [
            'total_earned' => $total_earned,
            'total_paid' => $total_paid,
            'balance' => $total_earned - $total_paid
        ];
    }
    
}  
?>