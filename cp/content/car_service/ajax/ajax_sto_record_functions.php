<?php
ini_set("display_errors", 1);
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Соединение с основной БД
$DP_Config = new DP_Config;//Конфигурация CMS

//Подключение к БД
try
{
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e)
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

function exitFunction($result) {
    exit(json_encode($result));
}

function proccess_car_data() {
	if ($_POST["car_id"] > 0) {
		update_car_in_garage();
	}
	else {
		add_car_to_garage();
	}
	
	exitFunction(["status" => 200]);
}

function delete_car_by_id() {
	global $db_link;
	$delete = $db_link->prepare("DELETE FROM `shop_docpart_garage` WHERE `id` = ?;");
	$delete->execute([$_POST["id"]]);
	
	exitFunction(["status" => 200]);
}

function add_car_to_garage() {
    global $db_link;
    
    // Подготовка данных
    $data = [
        'caption' => $_POST['caption'] ?? '',
        'mark_id' => $_POST['mark_id'] ?? 0,
        'marka' => $_POST['marka'] ?? '',
        'model' => $_POST['model'] ?? '',
        'year' => $_POST['year'] ?? 0,
        'body_type' => $_POST['body_type'] ?? '',
        'engine_value' => $_POST['engine_value'] ?? '',
        'fuel_type' => $_POST['fuel_type'] ?? '',
        'vin' => $_POST['vin'] ?? '',
        'frame' => $_POST['frame'] ?? '',
        'color' => $_POST['color'] ?? '',
        'country' => $_POST['country'] ?? '',
        'wheel' => $_POST['wheel'] ?? '',
        'transmission' => $_POST['transmission'] ?? '',
        'note' => $_POST['note'] ?? '',
        'mileage' => $_POST['mileage'] ?? 0,
        'user_id' => $_POST['user_id'] ?? 0,
        'active' => $_POST['active'] ?? 0,
        'license_plate' => $_POST['license_plate'] ?? '',
    ];
    
    // SQL запрос с плейсхолдерами PDO
    $insert_sql = $db_link->prepare("INSERT INTO `shop_docpart_garage` 
        (`active`, `caption`, `mark_id`, `marka`, `model`, `year`, `body_type`, 
         `engine_value`, `fuel_type`, `vin`, `frame`, `color`, `country`, 
         `wheel`, `transmission`, `note`, `user_id`, `mileage`, `license_plate`) 
        VALUES (:active, :caption, :mark_id, :marka, :model, :year, :body_type, 
                :engine_value, :fuel_type, :vin, :frame, :color, :country, 
                :wheel, :transmission, :note, :user_id, :mileage, :license_plate)");
    
    // Выполнение запроса
    $insert_sql->execute([
        ':active' => $data['active'],
        ':caption' => $data['caption'],
        ':mark_id' => $data['mark_id'],
        ':marka' => $data['marka'],
        ':model' => $data['model'],
        ':year' => $data['year'],
        ':body_type' => $data['body_type'],
        ':engine_value' => $data['engine_value'],
        ':fuel_type' => $data['fuel_type'],
        ':vin' => $data['vin'],
        ':frame' => $data['frame'],
        ':color' => $data['color'],
        ':country' => $data['country'],
        ':wheel' => $data['wheel'],
        ':transmission' => $data['transmission'],
        ':note' => $data['note'],
        ':user_id' => $data['user_id'],
        ':mileage' => $data['mileage'],
        ':license_plate' => $data['license_plate']
    ]);
}

function update_car_in_garage() {
    global $db_link;
    
    // Проверяем наличие ID автомобиля
    if (empty($_POST['car_id'])) {
        return false;
    }
    
    // Подготовка данных
    $data = [
        'car_id' => $_POST['car_id'],
        'caption' => $_POST['caption'] ?? '',
        'mark_id' => $_POST['mark_id'] ?? 0,
        'marka' => $_POST['marka'] ?? '',
        'model' => $_POST['model'] ?? '',
        'year' => $_POST['year'] ?? 0,
        'body_type' => $_POST['body_type'] ?? '',
        'engine_value' => $_POST['engine_value'] ?? '',
        'fuel_type' => $_POST['fuel_type'] ?? '',
        'vin' => $_POST['vin'] ?? '',
        'frame' => $_POST['frame'] ?? '',
        'color' => $_POST['color'] ?? '',
        'country' => $_POST['country'] ?? '',
        'wheel' => $_POST['wheel'] ?? '',
        'transmission' => $_POST['transmission'] ?? '',
        'note' => $_POST['note'] ?? '',
        'mileage' => $_POST['mileage'] ?? 0,
        'active' => $_POST['active'] ?? 0,
        'license_plate' => $_POST['license_plate'] ?? ''
    ];
    
    // SQL запрос для обновления
    $update_sql = $db_link->prepare("UPDATE `shop_docpart_garage` 
        SET `active` = :active, `caption` = :caption, `mark_id` = :mark_id, 
            `marka` = :marka, `model` = :model, `year` = :year, 
            `body_type` = :body_type, `engine_value` = :engine_value, 
            `fuel_type` = :fuel_type, `vin` = :vin, `frame` = :frame, 
            `color` = :color, `country` = :country, `wheel` = :wheel, 
            `transmission` = :transmission, `note` = :note, `mileage` = :mileage, `license_plate` = :license_plate
        WHERE `id` = :car_id");
    
    // Выполнение запроса
    $update_sql->execute([
        ':active' => $data['active'],
        ':caption' => $data['caption'],
        ':mark_id' => $data['mark_id'],
        ':marka' => $data['marka'],
        ':model' => $data['model'],
        ':year' => $data['year'],
        ':body_type' => $data['body_type'],
        ':engine_value' => $data['engine_value'],
        ':fuel_type' => $data['fuel_type'],
        ':vin' => $data['vin'],
        ':frame' => $data['frame'],
        ':color' => $data['color'],
        ':country' => $data['country'],
        ':wheel' => $data['wheel'],
        ':transmission' => $data['transmission'],
        ':note' => $data['note'],
        ':mileage' => $data['mileage'],
        ':car_id' => $data['car_id'],
        ':license_plate' => $data['license_plate']
    ]);
}


function get_car_data_by_id() {
	global $db_link;
	
	$select = $db_link->prepare("SELECT * FROM `shop_docpart_garage` WHERE `id` = ?;");
	$select->execute([$_POST["car_id"]]);
	
	$res = $select->fetch(PDO::FETCH_ASSOC);
	
	exitFunction(["status" => 200, "data" => $res]);
}

/*function update_record() {
    $_POST["user"] =  str_replace('"', '', preg_replace('/((^|\s)"(\w))/um', '\2«\3',$_POST["user"]));
    global $db_link;
    $update = $db_link->prepare("UPDATE `car_service_records` SET `worker_id` = ?, `services_arr` = ?, `car_id` = ?, `color` = ?, `order_id` = ?, `user_id` = ?, `user` = ?, `gos_num` = ?, `year` = ?, `vin` = ?, `prepayment` = ?, `mileage` = ? WHERE `car_service_records`.`id` = ?");
    $update->execute([$_POST["worker_id"], json_encode($_POST["services_arr"]), $_POST["car_id"], $_POST["color"], $_POST["order_id"], $_POST["user_id"], $_POST["user"], $_POST["gos_num"], $_POST["year"],$_POST["vin"],$_POST["prepayment"],$_POST["mileage"], $_POST["record_id"]]);

    exitFunction(["status" => 200]);
}*/

function delete_record() {
    global $db_link;
    $delete = $db_link->prepare("DELETE FROM `car_service_records` WHERE `id` = ?;");
    $delete->execute([$_POST["id"]]);
    exitFunction(["status" => 200]);
}

function set_unview() {
	global $db_link;
    $unview = $db_link->prepare("UPDATE `car_service_records` SET `is_view` = 0 WHERE `id` = ?;");
    $unview->execute([$_POST["id"]]);
    exitFunction(["status" => 200]);
}

function get_data_by_sto_id() {
	global $db_link;
	global $DP_Config;
	
	$select_sto_data = $db_link->prepare("SELECT * FROM `car_services` WHERE `id` = ?;");
	$select_sto_data->execute([$_POST["id"]]);
	
	$sto_data = $select_sto_data->fetch(PDO::FETCH_ASSOC);
	
	
	$select_sto_id_by_order_id = $db_link->prepare("SELECT `id` FROM `car_service_records` WHERE `orders_ids` = ?;");
	$select_sto_id_by_order_id->execute([$_POST["order_id"]]);
	$res = $select_sto_id_by_order_id->fetch(PDO::FETCH_ASSOC);
	
	$record_sto_id = 0;
	
	if ($res["id"] > 0) {
		$record_sto_id = $res["id"];
	}
	
	//Получаем данные древовидного списка услуг (только конечные элементы)
	$tree_lists_services_arr = [];
	$tree_lists_services_arr_select = $db_link->prepare("SELECT `id`,`value`,  (SELECT `fix_price` FROM `car_service_prices` WHERE `id_tree_services_list` = `shop_tree_lists_items`.`id`) as `fix_price`  FROM `shop_tree_lists_items` WHERE `tree_list_id` = ? AND `count` = 0;");
	$tree_lists_services_arr_select->execute([$sto_data["id_tree_list_services"]]);
	$tree_lists_services_arr = $tree_lists_services_arr_select->fetchAll(PDO::FETCH_ASSOC);

	//Получаем данные древовидного списка авто (только конечные элементы)
	$tree_lists_cars_arr = [];
	$tree_lists_cars_arr_select = $db_link->prepare("SELECT `id`,`value` FROM `shop_tree_lists_items` WHERE `tree_list_id` = ? AND `count` = 0;");
	$tree_lists_cars_arr_select->execute([$sto_data["id_tree_list_cars"]]);
	$tree_lists_cars_arr = $tree_lists_cars_arr_select->fetchAll(PDO::FETCH_ASSOC);
	
	
	exitFunction(
		[
			"status" => 200, 
			"data" => 
				[
					"tree_lists_services_arr" => $tree_lists_services_arr,
					"tree_lists_cars_arr" => $tree_lists_cars_arr,
					"hourly_rate" => $sto_data["hourly_rate"],
					"time_from" => $sto_data["time_from"],
					"time_to" => $sto_data["time_to"],
					"record_id" => $record_sto_id
				]
		]
	);
	
}

function get_cars_info_by_user_id() {
	global $db_link;
	global $DP_Config;
	
	$select = $db_link->prepare("SELECT * FROM `shop_docpart_garage` WHERE `user_id` = ?;");
	$select->execute([$_POST["user_id"]]);
	
	$res = $select->fetchAll(PDO::FETCH_ASSOC);
	
	exitFunction(
		[
			"status" => 200, 
			"data" => 
				[
					"cars_info" => $res
				]
		]
	);
	
}

function generate_table_by_services_select() {
    global $db_link;

    $service_id = $_POST["service_id"];
    $sto_id = $_POST["sto_id"];
	
	$select_sto_data = $db_link->prepare("SELECT `hourly_rate` FROM `car_services` WHERE `id` = ?;");
	$select_sto_data->execute([$sto_id]);
	
	$res_select_sto_data = $select_sto_data->fetch(PDO::FETCH_ASSOC);
	
	$hourly_rate_price = $res_select_sto_data["hourly_rate"];
	
    // Получаем название услуги
    $select_service_caption = $db_link->prepare("SELECT `value` FROM `shop_tree_lists_items` WHERE `id` = ?;");
    $select_service_caption->execute([$service_id]);
    $caption = $select_service_caption->fetch(PDO::FETCH_ASSOC)["value"];

    // Получаем цену
    $select_service_price = $db_link->prepare("SELECT `hourly_rate`, `fix_price` FROM `car_service_prices` WHERE `sto_id` = ? AND `id_tree_services_list` = ?;");
    $select_service_price->execute([$sto_id, $service_id]);
    $select_service_price_res = $select_service_price->fetch(PDO::FETCH_ASSOC);
	$hourly_rate = $select_service_price_res["hourly_rate"];
	$fix_price = $select_service_price_res["fix_price"] > 0 ? $select_service_price_res["fix_price"] : 0;
	
	$select_statuses = $db_link->prepare("SELECT * FROM `shop_orders_items_statuses_ref`;");
	$select_statuses->execute();
	
	$res_select_statuses = $select_statuses->fetchAll(PDO::FETCH_ASSOC);
	
    ob_start();
    ?>
        <tr>
			<td>
				<input type="hidden" class="fix_price" name="service_fix_price[]" value="<?=$fix_price;?>" readonly>
				<input type="checkbox" class="service_one_check">
			</td>
            <td>
                <?=htmlspecialchars($caption)?>
            </td>
			<td>
				<select class="form-control status" name="service_status[]">
					<option value="-1">Не выбрано</option>
					<?php foreach ($res_select_statuses as $status_info) : ?>
						<option value="<?=$status_info["id"];?>"><?=$status_info["name"];?></option>  
					<?php endforeach; ?>
				</select>
			</td>
            <td>
                <input class="form-control price" name="service_price[]" value="<?=$hourly_rate_price * $hourly_rate;?>" readonly>
            </td>
			<td>
                <input class="form-control hourly_rate" name="service_hourly_rate[]" type="number" step="0.5" value="<?=htmlspecialchars($hourly_rate)?>">
            </td>
            <td>
                <input class="form-control quantity" name="service_quantity[]" value="1" type="number" step="1.0">  
            </td>
            <td>
                <input class="form-control total" name="service_total[]" value="<?=$hourly_rate_price * $hourly_rate;?>" readonly>
            </td>
			 <td>
                <input class="form-control discount" name="service_discount[]" value="0" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-remove-row">Удалить</button>
            </td>
        </tr>
    <?php
    $html = ob_get_clean();

    exitFunction([
        "status" => 200,
        "data" => [
            "html" => $html
        ]
    ]);
}
 
function add_pay() {
	global $db_link;
	
	$record_id = (int)$_POST['record_id'];

	$update = $db_link->prepare("UPDATE `car_service_records` SET `is_paid` = 1 WHERE `id` = ?;");
	$update->execute([$record_id]);
	
	
	$try_to_select_user_id = $db_link->prepare("SELECT `user_json` FROM `car_service_records` WHERE `id` = ?;");
	$try_to_select_user_id->execute([$record_id]);
	
	$user_json = $try_to_select_user_id->fetch(PDO::FETCH_ASSOC);
	
	$user_json = json_decode($user_json, true);
	
	$user_id = 0;
	
	if ($user_json["user_id"] > 0) {
		$user_id = 	$user_json["user_id"];
	}
	
	
	/*$insert = $db_link->prepare("INSERT INTO `shop_users_accounting` (`id`, `user_id`, `time`, `income`, `amount`, `operation_code`, `active`, `pay_orders`, `tech_value_text`, `order_id`, `office_id`, `sto_record_id`) VALUES (NULL, ?, ?, ?, ?, ?, ?, '', '', '0', '0', ?)");
	
	$insert->execute([$user_id, time(), 1, $_POST["sum"], 9, 1, $record_id]);
	
	$insert->execute([$user_id, time(), 0, $_POST["sum"], 10, 1, $record_id]);*/
	exitFunction(["status" => 200]);
}

function cancel_pay() {
	global $db_link;
	
	$record_id = (int)$_POST['record_id'];

	$update = $db_link->prepare("UPDATE `car_service_records` SET `is_paid` = 0 WHERE `id` = ?;");
	$update->execute([$record_id]);
	
	
	$try_to_select_user_id = $db_link->prepare("SELECT `user_json` FROM `car_service_records` WHERE `id` = ?;");
	$try_to_select_user_id->execute([$record_id]);
	
	$user_json = $try_to_select_user_id->fetch(PDO::FETCH_ASSOC);
	
	$user_json = json_decode($user_json, true);
	
	$user_id = 0;
	
	if ($user_json["user_id"] > 0) {
		$user_id = 	$user_json["user_id"];
	}
	exitFunction(["status" => 200]);
}

function get_slots_by_date() { 
    global $db_link;

    $sto_id = (int)$_POST['sto_id'];
    $post_id = (int)$_POST['post_id'];
    $date = $_POST['date']; // '2025-08-22'
	
    // Получаем параметры сервиса
    $stmt = $db_link->prepare("
        SELECT operating_mode, time_from, time_to, record_step 
        FROM car_services 
        WHERE id = ?
    ");
    $stmt->execute([$sto_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        exitFunction(["status" => 400, "message" => "Сервис не найден"]);
    }

    $operating_mode = array_map('intval', json_decode($service['operating_mode'], true));
    $time_from = (int)$service['time_from'];
    $time_to = (int)$service['time_to'];
    $step = (int)$service['record_step'];

    // Проверяем, работает ли сервис в этот день недели
    $dayOfWeek = (int)date('N', strtotime($date)); // 1..7 (Пн..Вс)
    if (!in_array($dayOfWeek, $operating_mode, true)) {
        exitFunction(["status" => 200, "data" => ["slots" => []]]);
    }

    // Получаем занятые записи с time_to
    $busyRecords = getRecordsByDate($db_link, $sto_id, $date, $post_id);
    
    // Преобразуем в массив интервалов для удобства
    $busyIntervals = [];
    foreach ($busyRecords as $record) {
        $busyIntervals[] = [
            'start' => strtotime($record['time']), // Конвертируем '2025-11-10 10:00:00' в timestamp
            'end' => (int)$record['time_to']       // time_to уже в формате timestamp (1763123527)
        ];
    }

    // Генерируем и проверяем слоты
    $availableSlots = [];
    $startTime = strtotime("$date $time_from:00");
    $endTime = strtotime("$date $time_to:00");

    // Если это сегодняшняя дата, корректируем начальное время
    $today = date('Y-m-d');
    if ($date === $today) {
        $currentTime = time();
        // Если текущее время позже начального времени сервиса, начинаем с текущего времени
        if ($currentTime > $startTime) {
            // Округляем до ближайшего шага записи
            $roundedTime = ceil($currentTime / ($step * 60)) * ($step * 60);
            $startTime = max($startTime, $roundedTime);
        }
    }

    // Проверяем каждый потенциальный слот
    for ($slotStart = $startTime; $slotStart < $endTime; $slotStart += $step * 60) {
        $slotEnd = $slotStart + ($step * 60);
        
        // Если слот выходит за время закрытия - пропускаем
        if ($slotEnd > $endTime) {
            continue;
        }
        
        $isSlotAvailable = true;
        
        // Проверяем пересечение с занятыми интервалами
        foreach ($busyIntervals as $interval) {
            // Проверяем пересечение интервалов: 
            // слот пересекается с занятым интервалом, если:
            // - начало слота внутри занятого интервала ИЛИ
            // - конец слота внутри занятого интервала ИЛИ  
            // - слот полностью содержит занятый интервал
            if ($slotStart < $interval['end'] && $slotEnd > $interval['start']) {
                $isSlotAvailable = false;
                break;
            }
        }
        
        if ($isSlotAvailable) {
            $availableSlots[] = date('H:i', $slotStart);
        }
    }

    exitFunction(["status" => 200, "data" => ["slots" => $availableSlots]]);
}

function getRecordsByDate($db_link, $sto_id, $date, $post_id) {
    // Начало и конец дня в формате TIMESTAMP
    $start = "$date 00:00:00";
    $end   = "$date 23:59:59";

    $stmt = $db_link->prepare("
        SELECT time, time_to 
        FROM car_service_records 
        WHERE sto_id = ? AND post_id = ? AND time >= ? AND time <= ?
    ");
    $stmt->execute([$sto_id, $post_id, $start, $end]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function save_record() {
    global $db_link;

    try {
        if (!isset($_POST['json'])) {
            exitFunction(array("status" => 400, "message" => "Нет данных"));
        }

        $data = json_decode($_POST['json'], true);
        if (!$data || !is_array($data)) {
            exitFunction(array("status" => 400, "message" => "Ошибка парсинга JSON"));
        }

        if (!isset($data['tables']) || !is_array($data['tables'])) {
            exitFunction(array("status" => 400, "message" => "Неверная структура данных"));
        }

        $tables = $data['tables'];
        $recordTable = isset($tables['car_service_records']) ? $tables['car_service_records'] : null;
        $servicesTable = isset($tables['car_service_record_services']) ? $tables['car_service_record_services'] : null;

        if (!$recordTable || !is_array($recordTable)) {
            exitFunction(array("status" => 400, "message" => "Данные по записи отсутствуют"));
        }

        // Минимальная валидация
        if (empty($recordTable['sto_id'])) {
            exitFunction(array("status" => 400, "message" => "Не указан сервис (sto_id)"));
        }
        if (empty($recordTable['time'])) {
            exitFunction(array("status" => 400, "message" => "Не указано время записи"));
        }
		
		 // Валидация sto_id - ДОБАВЛЕННАЯ ПРОВЕРКА
        if (!isset($recordTable['sto_id']) || empty($recordTable['sto_id'])) {
            exitFunction(array("status" => 400, "message" => "Не указан сервис (sto_id)"));
        }
        
        $sto_id = filter_var($recordTable['sto_id'], FILTER_VALIDATE_INT);
        if ($sto_id === false || $sto_id < 1) {
            exitFunction(array(
                "status" => 400, 
                "message" => "Некорректный идентификатор сервиса (sto_id должен быть положительным целым числом)"
            ));
        }
		
		
		 // === ВАЛИДАЦИЯ ДЛЯ НОВОЙ ЗАПИСИ ===
        // Если это создание новой записи (id пустой или 0)
        $recordId = isset($recordTable['id']) ? (int)$recordTable['id'] : 0;
        
        if ($recordId < 1) {  
            // Проверяем orders_ids - если есть заказ, проверяем существование записи
            if (!empty($recordTable['orders_ids']) && $recordTable['orders_ids'] > 0) {
                $orders_ids = (int)$recordTable['orders_ids'];
                
                // Проверяем, есть ли уже запись с таким order_id
                $check_sql = "SELECT id, orders_ids FROM car_service_records 
                             WHERE orders_ids = ? 
                             AND sto_id = ?";
                
                $check_stmt = $db_link->prepare($check_sql);
                $check_stmt->execute([$orders_ids, $sto_id]);
                $existing_record = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_record) {
                    exitFunction(array(
                        "status" => 409, // Conflict
                        "message" => "Запись с заказом #" . $orders_ids . " уже существует (ID записи: " . $existing_record['id'] . ")",
                        "data" => array(
                            "existing_record_id" => $existing_record['id'],
                            "orders_ids" => $orders_ids
                        )
                    ));
                }
            }
            
            // Дополнительная проверка: если нет услуг, не создаем запись
            if (empty($servicesTable) || !is_array($servicesTable) || count($servicesTable) == 0) {
                exitFunction(array(
                    "status" => 400,
                    "message" => "Нельзя создать запись без услуг"
                ));
            }
            
            // Проверка обязательных полей для новой записи
            if (empty($recordTable['car_id']) || $recordTable['car_id'] <= 0) {
                exitFunction(array(
                    "status" => 400,
                    "message" => "Для создания новой записи необходимо выбрать автомобиль"
                ));
            }
            
            if (empty($recordTable['user_id']) || $recordTable['user_id'] <= 0) {
                exitFunction(array(
                    "status" => 400,
                    "message" => "Для создания новой записи необходимо указать клиента"
                ));
            }
        }
		
		
        // === Транзакция ===
        $db_link->beginTransaction();

        // === Обновление или вставка записи ===
        $recordId = saveDynamicRow('car_service_records', $recordTable);

        // === Работа с сервисами ===
        if ($recordId && is_array($servicesTable)) {
            $delete = $db_link->prepare("DELETE FROM `car_service_record_services` WHERE `record_id` = ?");
            $delete->execute(array($recordId));

            foreach ($servicesTable as $serviceRow) {
                $serviceRow['record_id'] = $recordId;
                saveDynamicRow('car_service_record_services', $serviceRow);
            }
        }

        $db_link->commit();

        exitFunction(array(
            "status" => 200,
            "message" => "Запись успешно сохранена",
            "data" => array("id" => $recordId)
        ));

    } catch (Exception $e) {
        $db_link->rollBack();
        exitFunction(array("status" => 500, "message" => "Ошибка: " . $e->getMessage()));
    }
}



function saveDynamicRow($table, $data) {
    global $db_link;

    $id = isset($data['id']) ? $data['id'] : null;
    unset($data['id']);

    if ($id) {
        // UPDATE
        $columns = array_keys($data);
        $set = implode(", ", array_map(function($col) {
            return "`" . $col . "` = ?";
        }, $columns));

        $sql = "UPDATE `" . $table . "` SET " . $set . " WHERE `id` = ?";
        $stmt = $db_link->prepare($sql);
        $stmt->execute(array_merge(array_values($data), array($id)));
        return $id;
    } else {
        // INSERT
        $columns = array_keys($data);
        $placeholders = rtrim(str_repeat("?,", count($columns)), ",");
        $sql = "INSERT INTO `" . $table . "` (`" . implode("`,`", $columns) . "`) VALUES (" . $placeholders . ")";
        $stmt = $db_link->prepare($sql);
        $stmt->execute(array_values($data));
        return $db_link->lastInsertId();
    }
}

function set_status() {
	global $db_link;
	$update = $db_link->prepare("UPDATE `car_service_records` SET `status_id` = ? WHERE `id` = ?;");
	$update->execute([$_POST["status_id"], $_POST["record_id"]]);
	
	exitFunction(array(
		"status" => 200,
		"message" => "Статус обновлён",
		"data" => array("id" => $_POST["record_id"])
	));	
}

//$_POST = json_decode(file_get_contents("php://input"), true);

if (isset($_POST["action"]) && function_exists($_POST["action"]))
    $_POST["action"]();
else
    exitFunction(["status" => 404, "message" => "Метод не определён" . $_POST["action"]]);
?>