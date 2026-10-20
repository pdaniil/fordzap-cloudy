<?php 

class WorkersSalary {
    
    // Кэш заказов для избежания повторных запросов
    private static $orders_cache = [];
    
    public static function get_total_price_services($record_id) {
        
        global $db_link;

        $select = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?;");
        $select->execute([$record_id]);
        
        $select_res = $select->fetchAll(PDO::FETCH_ASSOC);
        
        $total_sum = 0;
        
        foreach ($select_res as $service_price) {
            $price = $service_price["price"] * $service_price["count"];
            if (isset($service_price["discount"]) && $service_price["discount"] > 0) {
                $discount_percent = $service_price["discount"];
                $price = $price * (1 - $discount_percent / 100);
            }
            $total_sum += $price;
        }
        return $total_sum;
    }  
    
    public static function get_service_worker_payment($service, $worker_percent) {
        if (isset($service["fix_price"]) && $service["fix_price"] > 0) {
            return $service["fix_price"] * $service["count"];
        } else {
            $price = $service["price"] * $service["count"];
            if (isset($service["discount"]) && $service["discount"] > 0) {
                $price = $price * (1 - $service["discount"] / 100);
            }
            return $price * ($worker_percent / 100);
        }
    }
    
    public static function calculate_worker_salary($worker_user_id) {
        global $db_link;

        $select_worker = $db_link->prepare("SELECT * FROM `car_service_personal` WHERE `user_id` = ? LIMIT 1;");
        $select_worker->execute([$worker_user_id]);
        $worker = $select_worker->fetch(PDO::FETCH_ASSOC);
        
        if (!$worker) return 0;
        
        $select_records = $db_link->prepare("SELECT * FROM `car_service_records` WHERE `worker_id` = ?");
        $select_records->execute([$worker["id"]]);
        $res_records = $select_records->fetchAll(PDO::FETCH_ASSOC);
        
        $unique_days = [];
        $total_percent_salary = 0;
        
        foreach ($res_records as $record) {
            $select_services = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?;");
            $select_services->execute([$record["id"]]);
            $services = $select_services->fetchAll(PDO::FETCH_ASSOC);
            
            $record_salary = 0;
            foreach ($services as $service) {
                if (($service["status_id"] ?? 0) != 21) continue;
                $record_salary += self::get_service_worker_payment($service, $record["worker_percent"]);
            }
            if ($record_salary > 0) {
                $total_percent_salary += $record_salary;
                $day_key = date('Y-m-d', strtotime($record['time']));
                $unique_days[$day_key] = true;
            }
        }
        
        $unique_days_count = count($unique_days);
        $base_salary = $worker["standart_hourly_rate"] * $unique_days_count;
        return $base_salary + $total_percent_salary;
    }
    
    /**
     * Получение детальной информации по записям сотрудника с возможностью фильтрации по датам
     * @param int $worker_id
     * @param string|null $date_from Дата начала (Y-m-d H:i:s)
     * @param string|null $date_to Дата окончания (Y-m-d H:i:s)
     * @return array
     */  
    public static function get_detal_info($worker_id, $date_from = null, $date_to = null) {
        global $db_link;
        
        $sql = "SELECT * FROM `car_service_records` WHERE `worker_id` = ?";
        $params = [$worker_id];
        if ($date_from !== null && $date_to !== null) {
			// Включаем записи с NULL, чтобы они учитывались всегда
			$sql .= " AND (`time` BETWEEN ? AND ? OR `time` IS NULL)";
			$params[] = $date_from;
			$params[] = $date_to;
		}
        $sql .= " ORDER BY `time` DESC;";
        
        $query = $db_link->prepare($sql);
        $query->execute($params);
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
            
            // Получаем статус заказа, если есть order_id
            $order_status = null;
            if (!empty($info["orders_ids"])) {
                $stmt_order = $db_link->prepare("SELECT `status` FROM `shop_orders` WHERE `id` = ? LIMIT 1");
                $stmt_order->execute([$info["orders_ids"]]);
                $order = $stmt_order->fetch(PDO::FETCH_ASSOC);
                if ($order) {
                    $order_status = (int)$order["status"];
                }
            }
            
            // Получаем данные автомобиля для этой записи
            $car_info_str = 'Не указано';
            $car_brand = '';
            $car_model = '';
            if (!empty($info["car_id"]) && $info["car_id"] > 0) {
                $car_query = $db_link->prepare("SELECT `marka`, `model`, `license_plate` FROM `shop_docpart_garage` WHERE `id` = ? LIMIT 1");
                $car_query->execute([$info["car_id"]]);
                $car_data = $car_query->fetch(PDO::FETCH_ASSOC);
                if ($car_data) {
                    $car_brand = $car_data["marka"] ?? '';
                    $car_model = $car_data["model"] ?? '';
                    $car_info_str = trim($car_brand . ' ' . $car_model);
                    if (!empty($car_data["license_plate"])) {
                        $car_info_str .= ' (' . $car_data["license_plate"] . ')';
                    }
                    if (empty(trim($car_info_str))) {
                        $car_info_str = 'Не указано';
                    }
                }
            }

            foreach ($services as $service) {
                $price = $service["price"] * $service["count"];
                if (isset($service["discount"]) && $service["discount"] > 0) {
                    $price = $price * (1 - $service["discount"] / 100);
                }
                $total_price += $price;
                
                $is_ready_for_payment = ($service["status_id"] ?? 0) == 21;
                $worker_payment = 0;
                if ($is_ready_for_payment) {
                    $worker_payment = self::get_service_worker_payment($service, $info["worker_percent"]);
                    $total_percent_salary += $worker_payment;
                }
                
                $record_services_info[] = [
                    "service_name" => $service["caption"] ?? "Услуга",
                    "car_info" => $car_info_str,
                    "price" => $price,
                    "fix_price" => $service["fix_price"] ?? 0,
                    "worker_payment" => $worker_payment,
                    "payment_type" => ($service["fix_price"] ?? 0) > 0 ? "fixed" : "percent",
                    "status_id" => $service["status_id"] ?? 0,
                    "is_ready" => $is_ready_for_payment,
                    "status_name" => self::get_status_name($service["status_id"] ?? 0)
                ];
            }
            
            $info_arr[] = [
                "price" => $total_price,
                "percent" =>  $info["worker_percent"],
                "percent_salary" => $total_percent_salary,
                "date" => self::getOrderEndDate($info["orders_ids"]) ? self::getOrderEndDate($info["orders_ids"]) : $info["time"],
                "order_id" => $info["orders_ids"],
                "order_status" => $order_status,
                "type" => "record",
                "services" => $record_services_info,
                "has_unpaid_services" => $total_percent_salary < $total_price,
                "car_brand" => $car_brand,
                "car_model" => $car_model
            ];
        }
        
        return $info_arr;
    }
      
	private function getOrderEndDate($order_id) {
		global $db_link;
		
		$sql = $db_link->prepare("SELECT `time` FROM `shop_orders_logs` WHERE `order_id` = ? AND `text` = 'Заказу присвоен статус <b>Завершен</b>'");
		$sql->execute([$order_id]);
		
		$res = $sql->fetch(PDO::FETCH_ASSOC);
		
		if ($res["time"] > 0) return date('d.m.Y H:i', $res["time"]); 
		else return false;
	}
	
    /**
     * Получение информации о заказе (статус, авто) с кэшированием
     */
    private static function get_order_info($order_id) {
        global $db_link;
        if (!$order_id) {
            return ['status' => null, 'car_brand' => '', 'car_model' => ''];
        }
        if (isset(self::$orders_cache[$order_id])) {
            return self::$orders_cache[$order_id];
        }
        $query = $db_link->prepare("SELECT `status`, `car_brand`, `car_model` FROM `shop_orders` WHERE `id` = ? LIMIT 1");
        $query->execute([$order_id]);
        $order = $query->fetch(PDO::FETCH_ASSOC);
        $result = [
            'status' => $order ? $order['status'] : null,
            'car_brand' => $order ? ($order['car_brand'] ?? '') : '',
            'car_model' => $order ? ($order['car_model'] ?? '') : ''
        ];
        self::$orders_cache[$order_id] = $result;
        return $result;
    }
    
    public static function get_status_name($status_id) {
        global $db_link;
        static $statuses = null;
        if ($statuses === null) {
            $select_statuses = $db_link->prepare("SELECT `id`, `name` FROM `shop_orders_items_statuses_ref` WHERE `for_service` = 1;");
            $select_statuses->execute();
            $res = $select_statuses->fetchAll(PDO::FETCH_ASSOC);
            $statuses = [];
            foreach ($res as $status_info) {
                $statuses[$status_info["id"]] = $status_info["name"];
            }
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
        if (!$res || $res["user_id"] <= 0) return false;
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
    
    /**
     * Получение выплат за период
     */
    public static function get_salary_info_by_period($worker_id, $date_from, $date_to) {
        global $db_link;
        $sql = "SELECT * FROM `car_service_personal_salary` WHERE `worker_id` = ? AND `time` >= ? AND `time` <= ? ORDER BY `time` DESC;";
        $query = $db_link->prepare($sql);
        $query->execute([$worker_id, $date_from, $date_to]);
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
        $bill_info = array_merge($records_info, $salary_info);
        usort($bill_info, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        return $bill_info;
    }
    
    /**
     * Получение объединённой информации за период
     */
    public static function get_bill_info_by_period($worker_id, $date_from, $date_to) {
        $records_info = self::get_detal_info($worker_id, $date_from, $date_to);
        $salary_info = self::get_salary_info_by_period($worker_id, $date_from, $date_to);
        $bill_info = array_merge($records_info, $salary_info);
        usort($bill_info, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        return $bill_info;
    }
    
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