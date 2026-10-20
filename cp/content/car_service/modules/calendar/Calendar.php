<?php
/**
 * Классы для работы с календарем записей автосервиса
 * Версия: 2.2
 *  - фильтр по мастеру
 *  - фильтр по статусу: all | overdue | active | completed
 *  - active_count для подсветки Месяц/Неделя
 */

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

class CalendarService {
    private $db;
    private $config;
    private $serviceId;
    private $serviceData;
    private $workers = [];
    private $posts = [];
    private $records = [];
    private $recordServices = [];
    private $users = [];
    private $cars = [];
    private $orderStatuses = [];

    // Фильтры
    private $filterStatus = null;   // 'all' | 'overdue' | 'active' | 'completed' | null
    private $filterWorkerId = null; // int | null

    public function __construct($serviceId = null) {
        $this->config = new DP_Config();
        $this->connectDB();
        if ($serviceId) {
            $this->setService($serviceId);
        }
    }

    private function connectDB() {
        try {
            $this->db = new PDO(
                'mysql:host=' . $this->config->host . ';dbname=' . $this->config->db,
                $this->config->user,
                $this->config->password
            );
            $this->db->query("SET NAMES utf8mb4;");
        } catch (PDOException $e) {
            $this->jsonError('Ошибка подключения к БД');
        }
    }

    public function setService($serviceId) {
        $this->serviceId = (int)$serviceId;
        $this->loadServiceData();
        $this->loadWorkers();
        $this->loadPosts();
        $this->loadUsers();
        $this->loadCars();
    }

    /**
     * Установить фильтры (статус + мастер).
     * $statusFilter: 'all' | 'overdue' | 'active' | 'completed' | null
     */
    public function setFilters($statusFilter = null, $workerId = null) {
        if ($statusFilter === null || $statusFilter === '' || $statusFilter === 'all') {
            $this->filterStatus = null;
        } else {
            $this->filterStatus = (string)$statusFilter;
        }
        $this->filterWorkerId = ($workerId !== null && $workerId !== '' && $workerId !== '0') ? (int)$workerId : null;
    }

    private function loadServiceData() {
        $stmt = $this->db->prepare("SELECT * FROM `car_services` WHERE `id` = ?");
        $stmt->execute([$this->serviceId]);
        $this->serviceData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$this->serviceData) {
            $this->jsonError('Сервис не найден');
        }
        $this->serviceData['operating_mode'] = json_decode($this->serviceData['operating_mode'], true);
    }

    private function loadWorkers() {
        $stmt = $this->db->prepare("SELECT * FROM `car_service_personal` ORDER BY `fio`");
        $stmt->execute();
        $this->workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadPosts() {
        $stmt = $this->db->prepare("SELECT * FROM `car_service_posts` ORDER BY `id`");
        $stmt->execute();
        $this->posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadUsers() {
        $stmt = $this->db->prepare("
            SELECT 
                u.*,
                (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'name' AND `user_id` = u.`user_id`) as `name`,
                (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'surname' AND `user_id` = u.`user_id`) as `surname`
            FROM `users` u
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $user) {
            $this->users[$user['user_id']] = $user;
        }
    }

    private function loadCars() {
        $stmt = $this->db->prepare("SELECT * FROM `shop_docpart_garage`");
        $stmt->execute();
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cars as $car) {
            $this->cars[$car['id']] = $car;
        }
    }

    public function loadRecords($dateFrom, $dateTo, $postId = null) {
        $sql = "
            SELECT r.*, 
                   p.fio as worker_name,
                   p.standart_hourly_rate,
                   p.service_percent
            FROM `car_service_records` r
            LEFT JOIN `car_service_personal` p ON r.worker_id = p.id
            WHERE r.sto_id = :sto_id 
              AND DATE(r.time) BETWEEN :date_from AND :date_to
        ";
        $params = [
            ':sto_id' => $this->serviceId,
            ':date_from' => $dateFrom,
            ':date_to' => $dateTo
        ];
        if ($postId !== null) {
            if ($postId == -1) {
                $sql .= " AND r.post_id = -1";
            } else {
                $sql .= " AND r.post_id = :post_id";
                $params[':post_id'] = $postId;
            }
        }
        // Фильтр по мастеру на уровне SQL
        if ($this->filterWorkerId) {
            $sql .= " AND r.worker_id = :worker_id";
            $params[':worker_id'] = $this->filterWorkerId;
        }
        $sql .= " ORDER BY r.time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $this->records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->loadRecordServices();
        $this->loadOrderStatuses();
        $this->applyStatusFilter();
    }

    /**
     * Применяет фильтр по статусу.
     * null / 'all' — без фильтра (стандартный вид, подсветка «просрочена» делается на фронте).
     * 'overdue'   — оставляем только просроченные.
     * 'active'    — оставляем только активные (future).
     * 'completed' — оставляем только выполненные.
     */
    private function applyStatusFilter() {
        if (!$this->filterStatus) {
            return;
        }
        $statusFilter = $this->filterStatus;
        $this->records = array_values(array_filter($this->records, function($record) use ($statusFilter) {
            $orderStatus = isset($this->orderStatuses[$record['orders_ids']]) ? $this->orderStatuses[$record['orders_ids']] : null;
            $status = $this->getRecordStatus($record['time_to'], $orderStatus);
            if ($statusFilter === 'overdue')   return $status === 'overdue';
            if ($statusFilter === 'active')    return $status === 'future';
            if ($statusFilter === 'completed') return $status === 'completed';
            return true;
        }));
    }

    private function loadRecordServices() {
        if (empty($this->records)) {
            $this->recordServices = [];
            return;
        }
        $recordIds = array_column($this->records, 'id');
        $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
        $stmt = $this->db->prepare("
            SELECT * FROM `car_service_record_services` 
            WHERE `record_id` IN ($placeholders)
        ");
        $stmt->execute($recordIds);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->recordServices = [];
        foreach ($services as $service) {
            $this->recordServices[$service['record_id']][] = $service;
        }
    }

    private function loadOrderStatuses() {
        $this->orderStatuses = [];
        if (empty($this->records)) return;
        $orderIds = array_filter(array_unique(array_column($this->records, 'orders_ids')));
        if (empty($orderIds)) return;
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = $this->db->prepare("SELECT id, status FROM shop_orders WHERE id IN ($placeholders)");
        $stmt->execute($orderIds);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->orderStatuses[(int)$row['id']] = (int)$row['status'];
        }
    }

    private function getRecordStatus($timeTo, $orderStatus) {
        $currentTimestamp = time();
        if ($timeTo <= $currentTimestamp) {
            if (in_array($orderStatus, [3,6,9])) {
                return 'completed';
            }
            return 'overdue';
        }
        return 'future';
    }

    private function getLightRecordsForPeriod($dateFrom, $dateTo, $postId = null) {
        // Сохраняем текущие фильтры, чтобы не потерять
        $savedStatus = $this->filterStatus;
        $savedWorker = $this->filterWorkerId;

        $this->loadRecords($dateFrom, $dateTo, $postId);

        $this->filterStatus = $savedStatus;
        $this->filterWorkerId = $savedWorker;

        $recordsByDay = [];
        foreach ($this->records as $record) {
            $date = substr($record['time'], 0, 10);
            $car = $this->cars[$record['car_id']] ?? null;
            $carInfo = '';
            $vin = '';
            if ($car) {
                $carInfo = trim($car['marka'] . ' ' . $car['model']);
                $vin = $car['vin'] ?? '';
            }
            $servicesCaptions = [];
            if (isset($this->recordServices[$record['id']])) {
                foreach ($this->recordServices[$record['id']] as $service) {
                    $servicesCaptions[] = $service['caption'];
                }
            }
            $recordsByDay[$date][] = [
                'id' => $record['id'],
                'vin' => $vin,
                'car_info' => $carInfo,
                'services_captions' => $servicesCaptions
            ];
        }
        return $recordsByDay;
    }

    public function getStats($dateFrom, $dateTo) {
        $this->loadRecords($dateFrom, $dateTo);
        $stats = [
            'total_records' => count($this->records),
            'total_sum' => 0,
            'total_hours' => 0,
            'unique_clients' => 0,
            'unique_cars' => 0,
            'overdue_count' => 0,
            'completed_count' => 0,
            'active_count' => 0,
            'unconfirmed_count' => 0,
            'by_status' => [],
            'by_worker' => []
        ];
        $clients = [];
        $cars = [];
        foreach ($this->records as $record) {
            $recordSum = 0;
            $recordHours = 0;
            if (isset($this->recordServices[$record['id']])) {
                foreach ($this->recordServices[$record['id']] as $service) {
                    $price = $service['price'] ?? 0;
                    $discount = $service['discount'] ?? 0;
                    $finalPrice = $price * (1 - $discount/100);
                    $recordSum += $finalPrice * ($service['count'] ?? 1);
                    $recordHours += ($service['hourly_rate'] ?? 0) * ($service['count'] ?? 1);
                }
            }
            $stats['total_sum'] += $recordSum;
            $stats['total_hours'] += $recordHours;
            if ($record['user_id']) $clients[$record['user_id']] = true;
            if ($record['car_id']) $cars[$record['car_id']] = true;
            if ($record['post_id'] == -1) $stats['unconfirmed_count']++;
            $orderStatus = isset($this->orderStatuses[$record['orders_ids']]) ? $this->orderStatuses[$record['orders_ids']] : null;
            $status = $this->getRecordStatus($record['time_to'], $orderStatus);
            if ($status === 'overdue') $stats['overdue_count']++;
            if ($status === 'completed') $stats['completed_count']++;
            if ($status === 'future') $stats['active_count']++;
            if ($record['worker_id']) {
                if (!isset($stats['by_worker'][$record['worker_id']])) {
                    $stats['by_worker'][$record['worker_id']] = [
                        'name' => $record['worker_name'] ?? 'Неизвестно',
                        'count' => 0,
                        'sum' => 0
                    ];
                }
                $stats['by_worker'][$record['worker_id']]['count']++;
                $stats['by_worker'][$record['worker_id']]['sum'] += $recordSum;
            }
        }
        $stats['unique_clients'] = count($clients);
        $stats['unique_cars'] = count($cars);
        return $stats;
    }

    public function getCalendarData($view, $date, $postId = null) {
        $dateObj = new DateTime($date);
        $result = [
            'view' => $view,
            'current_date' => $date,
            'service' => $this->getServiceInfo(),
            'posts' => $this->posts,
            'workers' => $this->workers,
            'data' => []
        ];
        switch ($view) {
            case 'year':
                $result['data'] = $this->getYearData($dateObj);
                break;
            case 'month':
                $result['data'] = $this->getMonthData($dateObj);
                break;
            case 'week':
                $result['data'] = $this->getWeekData($dateObj);
                break;
            case 'day':
                $result['data'] = $this->getDayData($dateObj, $postId);
                break;
        }
        return $result;
    }

    private function getYearData(DateTime $date) {
        $year = $date->format('Y');
        $start = $year . '-01-01';
        $end = $year . '-12-31';
        $this->loadRecords($start, $end);
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthStart = sprintf('%s-%02d-01', $year, $i);
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            $monthRecords = array_filter($this->records, function($record) use ($monthStart, $monthEnd) {
                $recordDate = substr($record['time'], 0, 10);
                return $recordDate >= $monthStart && $recordDate <= $monthEnd;
            });
            $months[$i] = [
                'count' => count($monthRecords),
                'sum' => $this->calculateSumForRecords($monthRecords)
            ];
        }
        return [
            'year' => $year,
            'months' => $months,
            'total' => [
                'count' => count($this->records),
                'sum' => $this->calculateSumForRecords($this->records)
            ]
        ];
    }

    private function getMonthData(DateTime $date) {
        $start = $date->format('Y-m-01');
        $end = $date->format('Y-m-t');
        $this->loadRecords($start, $end);
        $recordsByDay = $this->getLightRecordsForPeriod($start, $end);
        $days = [];
        $current = clone $date;
        $current->modify('first day of this month');
        while ($current->format('Y-m') === $date->format('Y-m')) {
            $dayStr = $current->format('Y-m-d');
            $dayRecords = array_filter($this->records, function($record) use ($dayStr) {
                return substr($record['time'], 0, 10) === $dayStr;
            });
            $overdueCount = 0;
            $completedCount = 0;
            $activeCount = 0;
            $unconfirmedCount = 0;
            foreach ($dayRecords as $rec) {
                $orderStatus = isset($this->orderStatuses[$rec['orders_ids']]) ? $this->orderStatuses[$rec['orders_ids']] : null;
                $status = $this->getRecordStatus($rec['time_to'], $orderStatus);
                if ($status === 'overdue') $overdueCount++;
                if ($status === 'completed') $completedCount++;
                if ($status === 'future') $activeCount++;
                if ($rec['post_id'] == -1) $unconfirmedCount++;
            }
            $isWorkingDay = in_array($current->format('N'), $this->serviceData['operating_mode']);
            $freeSlots = 0;
            if ($isWorkingDay) {
                $totalSlots = $this->calculateTotalSlotsForDay($current);
                $freeSlots = $totalSlots - count($dayRecords);
            }
            $days[$dayStr] = [
                'date' => $dayStr,
                'day_of_week' => (int)$current->format('N'),
                'is_working' => $isWorkingDay,
                'records_count' => count($dayRecords),
                'free_slots' => max(0, $freeSlots),
                'sum' => $this->calculateSumForRecords($dayRecords),
                'overdue_count' => $overdueCount,
                'completed_count' => $completedCount,
                'active_count' => $activeCount,
                'unconfirmed_count' => $unconfirmedCount
            ];
            $current->modify('+1 day');
        }
        return [
            'month' => $date->format('Y-m'),
            'month_name' => $this->getMonthName($date->format('n')),
            'year' => $date->format('Y'),
            'days' => $days,
            'records_by_day' => $recordsByDay,
            'total' => [
                'count' => count($this->records),
                'sum' => $this->calculateSumForRecords($this->records)
            ]
        ];
    }

    private function getWeekData(DateTime $date) {
        $clone = clone $date;
        $clone->modify('monday this week');
        $start = $clone->format('Y-m-d');
        $clone->modify('+6 days');
        $end = $clone->format('Y-m-d');
        $this->loadRecords($start, $end);
        $recordsByDay = $this->getLightRecordsForPeriod($start, $end);
        $days = [];
        $current = new DateTime($start);
        while ($current->format('Y-m-d') <= $end) {
            $dayStr = $current->format('Y-m-d');
            $dayRecords = array_filter($this->records, function($record) use ($dayStr) {
                return substr($record['time'], 0, 10) === $dayStr;
            });
            $overdueCount = 0;
            $completedCount = 0;
            $activeCount = 0;
            $unconfirmedCount = 0;
            foreach ($dayRecords as $rec) {
                $orderStatus = isset($this->orderStatuses[$rec['orders_ids']]) ? $this->orderStatuses[$rec['orders_ids']] : null;
                $status = $this->getRecordStatus($rec['time_to'], $orderStatus);
                if ($status === 'overdue') $overdueCount++;
                if ($status === 'completed') $completedCount++;
                if ($status === 'future') $activeCount++;
                if ($rec['post_id'] == -1) $unconfirmedCount++;
            }
            $isWorkingDay = in_array($current->format('N'), $this->serviceData['operating_mode']);
            $freeSlots = 0;
            if ($isWorkingDay) {
                $totalSlots = $this->calculateTotalSlotsForDay($current);
                $freeSlots = $totalSlots - count($dayRecords);
            }
            $days[$dayStr] = [
                'date' => $dayStr,
                'day_of_week' => (int)$current->format('N'),
                'day_name' => $this->getDayName($current->format('N')),
                'is_working' => $isWorkingDay,
                'records_count' => count($dayRecords),
                'free_slots' => max(0, $freeSlots),
                'sum' => $this->calculateSumForRecords($dayRecords),
                'overdue_count' => $overdueCount,
                'completed_count' => $completedCount,
                'active_count' => $activeCount,
                'unconfirmed_count' => $unconfirmedCount
            ];
            $current->modify('+1 day');
        }
        return [
            'week_start' => $start,
            'week_end' => $end,
            'days' => $days,
            'records_by_day' => $recordsByDay,
            'total' => [
                'count' => count($this->records),
                'sum' => $this->calculateSumForRecords($this->records)
            ]
        ];
    }

    private function getDayData(DateTime $date, $postId = null) {
        $dayStr = $date->format('Y-m-d');

        $this->loadRecords($dayStr, $dayStr, null);
        $allRecords = $this->records;

        $postsStats = [];
        foreach ($this->posts as $post) {
            $postRecords = array_filter($allRecords, function($rec) use ($post) {
                return $rec['post_id'] == $post['id'];
            });
            $postsStats[$post['id']] = [
                'id' => $post['id'],
                'caption' => $post['caption'],
                'image' => $post['image'],
                'records_count' => count($postRecords),
                'sum' => $this->calculateSumForRecords($postRecords)
            ];
        }
        $unconfirmedRecords = array_filter($allRecords, function($rec) {
            return $rec['post_id'] == -1;
        });
        if (count($unconfirmedRecords) > 0) {
            $postsStats[-1] = [
                'id' => -1,
                'caption' => 'Не подтверждённые',
                'image' => '',
                'records_count' => count($unconfirmedRecords),
                'sum' => $this->calculateSumForRecords($unconfirmedRecords)
            ];
        }

        if ($postId === null) {
            $postId = !empty($this->posts) ? $this->posts[0]['id'] : -1;
        }
        $this->loadRecords($dayStr, $dayStr, $postId);

        $slots = $this->generateTimeSlots($date);
        $slotRecords = [];
        foreach ($this->records as $record) {
            $recordTime = substr($record['time'], 11, 5);
            $recordData = $this->enrichRecordWithDetails($record);
            $recordData['status'] = $recordData['_status'];
            $slotRecords[$recordTime] = $recordData;
        }

        return [
            'date' => $dayStr,
            'day_name' => $this->getDayName($date->format('N')),
            'month_name' => $this->getMonthName($date->format('n')),
            'year' => $date->format('Y'),
            'current_post' => $postId,
            'posts_stats' => $postsStats,
            'slots' => $slots,
            'records' => $slotRecords,
            'total' => [
                'count' => count($this->records),
                'sum' => $this->calculateSumForRecords($this->records),
                'hours' => array_sum(array_column($this->records, 'total_hours') ?: [0])
            ]
        ];
    }

    private function generateTimeSlots(DateTime $date) {
        $step = $this->serviceData['record_step'] ?? 30;
        $timeFrom = $this->serviceData['time_from'] ?? 8;
        $timeTo = $this->serviceData['time_to'] ?? 19;
        $slots = [];
        $current = new DateTime($date->format('Y-m-d') . ' ' . $timeFrom . ':00:00');
        $end = new DateTime($date->format('Y-m-d') . ' ' . $timeTo . ':00:00');
        while ($current <= $end) {
            $timeStr = $current->format('H:i');
            $slots[$timeStr] = [
                'time' => $timeStr,
                'timestamp' => $current->getTimestamp(),
                'is_working' => true,
                'has_record' => false,
                'record' => null
            ];
            $current->modify('+' . $step . ' minutes');
        }
        return $slots;
    }

    private function enrichRecordWithDetails($record) {
        $services = $this->recordServices[$record['id']] ?? [];
        $totalSum = 0;
        $totalHours = 0;
        $servicesList = [];
        foreach ($services as $service) {
            $price = $service['price'] ?? 0;
            $discount = $service['discount'] ?? 0;
            $finalPrice = $price * (1 - $discount/100);
            $count = $service['count'] ?? 1;
            $totalSum += $finalPrice * $count;
            $totalHours += ($service['hourly_rate'] ?? 0) * $count;
            $servicesList[] = [
                'caption' => $service['caption'],
                'count' => $count,
                'price' => $price,
                'discount' => $discount,
                'final_price' => $finalPrice
            ];
        }
        $client = $this->users[$record['user_id']] ?? null;
        $clientName = $client ? trim($client['surname'] . ' ' . $client['name']) : 'Неизвестно';
        $car = $this->cars[$record['car_id']] ?? null;
        $carInfo = '';
        $vin = '';
        if ($car) {
            $carInfo = trim($car['marka'] . ' ' . $car['model']);
            $vin = $car['vin'] ?? '';
            if ($record['mileage']) {
                $carInfo .= ', пробег: ' . $record['mileage'] . ' км';
            }
        }
        $orderStatus = isset($this->orderStatuses[$record['orders_ids']]) ? $this->orderStatuses[$record['orders_ids']] : null;
        $status = $this->getRecordStatus($record['time_to'], $orderStatus);
        $isUnconfirmed = ($record['post_id'] == -1);
        return [
            'id' => $record['id'],
            'orders_ids' => $record['orders_ids'],
            'time' => $record['time'],
            'time_from' => $record['time_from'],
            'time_to' => $record['time_to'],
            'worker_id' => $record['worker_id'],
            'worker_name' => $record['worker_name'] ?? 'Не назначен',
            'client_id' => $record['user_id'],
            'client_name' => $clientName,
            'client_phone' => $client['phone'] ?? '',
            'car_id' => $record['car_id'],
            'car_info' => $carInfo,
            'vin' => $vin,
            'mileage' => $record['mileage'],
            'services' => $servicesList,
            'total_sum' => $totalSum,
            'total_hours' => $totalHours,
            '_status' => $status,
            'is_unconfirmed' => $isUnconfirmed
        ];
    }
	
	/**
	 * Возвращает список уже загруженных отфильтрованных записей
	 * (использует $this->records, заполненный после getCalendarData()).
	 * Каждая запись — плоский массив для вывода в виде строки.
	 */
	public function getRecordsForList() {
		$result = [];
		foreach ($this->records as $record) {
			$enriched = $this->enrichRecordWithDetails($record);

			// Список услуг (только названия)
			$servicesList = [];
			foreach ($enriched['services'] as $s) {
				$servicesList[] = $s['caption'];
			}

			$result[] = [
				'id'             => (int)$enriched['id'],
				'orders_ids'     => (int)$record['orders_ids'],
				'date'           => substr($record['time'], 0, 10),  // YYYY-MM-DD
				'time'           => substr($record['time'], 11, 5),  // HH:MM
				'post_id'        => (int)$record['post_id'],
				'worker_id'      => (int)$record['worker_id'],
				'worker_name'    => $enriched['worker_name'],
				'client_name'    => $enriched['client_name'],
				'client_phone'   => $enriched['client_phone'],
				'car_info'       => $enriched['car_info'],
				'vin'            => $enriched['vin'],
				'services'       => $servicesList,
				'total_sum'      => (float)$enriched['total_sum'],
				'total_hours'    => (float)$enriched['total_hours'],
				'status'         => $enriched['_status'],   // overdue|completed|future
				'is_unconfirmed' => (bool)$enriched['is_unconfirmed'],
			];
		}

		// Сортировка по дате и времени (хронологическая)
		usort($result, function($a, $b) {
			$cmp = strcmp($a['date'] . ' ' . $a['time'], $b['date'] . ' ' . $b['time']);
			if ($cmp === 0) return $a['id'] - $b['id'];
			return $cmp;
		});

		return $result;
	}
	
    private function calculateTotalSlotsForDay(DateTime $date) {
        $step = $this->serviceData['record_step'] ?? 30;
        $timeFrom = $this->serviceData['time_from'] ?? 8;
        $timeTo = $this->serviceData['time_to'] ?? 19;
        $hours = $timeTo - $timeFrom;
        return ($hours * 60) / $step;
    }

    private function calculateSumForRecords($records) {
        $sum = 0;
        foreach ($records as $record) {
            if (isset($this->recordServices[$record['id']])) {
                foreach ($this->recordServices[$record['id']] as $service) {
                    $price = $service['price'] ?? 0;
                    $discount = $service['discount'] ?? 0;
                    $sum += $price * (1 - $discount/100) * ($service['count'] ?? 1);
                }
            }
        }
        return $sum;
    }

    public function getServiceInfo() {
        return [
            'id' => $this->serviceData['id'],
            'caption' => $this->serviceData['caption'],
            'address' => $this->serviceData['address'],
            'phone' => $this->serviceData['phone'],
            'time_from' => $this->serviceData['time_from'],
            'time_to' => $this->serviceData['time_to'],
            'record_step' => $this->serviceData['record_step'],
            'operating_mode' => $this->serviceData['operating_mode']
        ];
    }

    private function getDayName($dayNum) {
        $days = [1 => 'Понедельник', 2 => 'Вторник', 3 => 'Среда', 4 => 'Четверг', 5 => 'Пятница', 6 => 'Суббота', 7 => 'Воскресенье'];
        return $days[(int)$dayNum] ?? '';
    }

    private function getMonthName($monthNum) {
        $months = [1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'];
        return $months[(int)$monthNum] ?? '';
    }

    private function jsonError($message) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }

    public function getPrintData($date, $postId = null) {
        $dayData = $this->getDayData(new DateTime($date), $postId);
        $printData = [
            'service_name' => $this->serviceData['caption'],
            'date' => $dayData['date'],
            'day_name' => $dayData['day_name'],
            'post' => $this->getPostInfo($postId),
            'records' => []
        ];
        foreach ($dayData['records'] as $time => $record) {
            if ($record) {
                $printData['records'][] = [
                    'time' => $time,
                    'client' => $record['client_name'],
                    'phone' => $record['client_phone'],
                    'car' => $record['car_info'],
                    'worker' => $record['worker_name'],
                    'services' => $record['services'],
                    'total_sum' => $record['total_sum']
                ];
            }
        }
        return $printData;
    }

    private function getPostInfo($postId) {
        foreach ($this->posts as $post) {
            if ($post['id'] == $postId) {
                return $post;
            }
        }
        return null;
    }

    public function getFreeSlots($date, $postId = null) {
        // Для расчёта свободных слотов фильтры не применяем
        $savedStatus = $this->filterStatus;
        $savedWorker = $this->filterWorkerId;
        $this->filterStatus = null;
        $this->filterWorkerId = null;

        $dayData = $this->getDayData(new DateTime($date), $postId);

        $this->filterStatus = $savedStatus;
        $this->filterWorkerId = $savedWorker;

        $slots = array_keys($dayData['slots']);
        $occupied = array_keys($dayData['records']);
        return array_values(array_diff($slots, $occupied));
    }
}

class CalendarAPI {
    private $service;
    public function __construct() {
        header('Content-Type: application/json');
    }
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        try {
            switch ($action) {
                case 'get_services':
                    $this->getServicesList();
                    break;
                case 'get_calendar':
                    $this->getCalendar();
                    break;
                case 'get_stats':
                    $this->getStats();
                    break;
                case 'get_print_data':
                    $this->getPrintData();
                    break;
                case 'get_slots':
                    $this->getSlots();
                    break;
                default:
                    $this->jsonResponse(['error' => 'Неизвестное действие']);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()]);
        }
    }
    private function getServicesList() {
        $db = $this->getDBConnection();
        $stmt = $db->query("SELECT id, caption FROM car_services ORDER BY caption");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse(['services' => $services]);
    }
    private function getCalendar() {
        $serviceId = $_POST['service_id'] ?? $_GET['service_id'] ?? 0;
        $view = $_POST['view'] ?? $_GET['view'] ?? 'week';
        $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
        $postId = $_POST['post_id'] ?? $_GET['post_id'] ?? null;
        $filterStatus = $_POST['filter_status'] ?? $_GET['filter_status'] ?? null;
        $filterWorkerId = $_POST['filter_worker_id'] ?? $_GET['filter_worker_id'] ?? null;

        if (!$serviceId) {
            $this->jsonResponse(['error' => 'Не указан ID сервиса']);
        }
        $this->service = new CalendarService($serviceId);
        $this->service->setFilters($filterStatus, $filterWorkerId);

        $data = $this->service->getCalendarData($view, $date, $postId);
		// Список отфильтрованных записей — только если активны фильтры
		$hasStatusFilter = ($filterStatus !== null && $filterStatus !== '' && $filterStatus !== 'all');
		$hasWorkerFilter = ($filterWorkerId !== null && $filterWorkerId !== '' && $filterWorkerId !== '0');
		if ($hasStatusFilter || $hasWorkerFilter) {
			$data['filtered_records_list'] = $this->service->getRecordsForList();
		}
        $dateObj = new DateTime($date);
        switch ($view) {
            case 'year':
                $stats = $this->service->getStats($date . '-01-01', $date . '-12-31');
                break;
            case 'month':
                $stats = $this->service->getStats($dateObj->format('Y-m-01'), $dateObj->format('Y-m-t'));
                break;
            case 'week':
                $clone = clone $dateObj;
                $clone->modify('monday this week');
                $start = $clone->format('Y-m-d');
                $clone->modify('+6 days');
                $end = $clone->format('Y-m-d');
                $stats = $this->service->getStats($start, $end);
                break;
            case 'day':
                $stats = $this->service->getStats($date, $date);
                break;
            default:
                $stats = [];
        }
        $data['stats'] = $stats;
        $data['filter_status'] = $filterStatus;
        $data['filter_worker_id'] = $filterWorkerId;
        $this->jsonResponse($data);
    }
    private function getStats() {
        $serviceId = $_POST['service_id'] ?? $_GET['service_id'] ?? 0;
        $dateFrom = $_POST['date_from'] ?? $_GET['date_from'] ?? date('Y-m-d');
        $dateTo = $_POST['date_to'] ?? $_GET['date_to'] ?? date('Y-m-d');
        $filterStatus = $_POST['filter_status'] ?? $_GET['filter_status'] ?? null;
        $filterWorkerId = $_POST['filter_worker_id'] ?? $_GET['filter_worker_id'] ?? null;
        if (!$serviceId) {
            $this->jsonResponse(['error' => 'Не указан ID сервиса']);
        }
        $this->service = new CalendarService($serviceId);
        $this->service->setFilters($filterStatus, $filterWorkerId);
        $stats = $this->service->getStats($dateFrom, $dateTo);
        $this->jsonResponse($stats);
    }
    private function getPrintData() {
        $serviceId = $_POST['service_id'] ?? $_GET['service_id'] ?? 0;
        $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
        $postId = $_POST['post_id'] ?? $_GET['post_id'] ?? null;
        if (!$serviceId) {
            $this->jsonResponse(['error' => 'Не указан ID сервиса']);
        }
        $this->service = new CalendarService($serviceId);
        $data = $this->service->getPrintData($date, $postId);
        $this->jsonResponse($data);
    }
    private function getSlots() {
        $serviceId = $_POST['service_id'] ?? $_GET['service_id'] ?? 0;
        $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
        $postId = $_POST['post_id'] ?? $_GET['post_id'] ?? null;
        if (!$serviceId) {
            $this->jsonResponse(['error' => 'Не указан ID сервиса']);
        }
        $this->service = new CalendarService($serviceId);
        // Для свободных слотов фильтры не применяем
        $this->service->setFilters(null, null);
        $slots = $this->service->getFreeSlots($date, $postId);
        $this->jsonResponse(['slots' => $slots]);
    }
    private function getDBConnection() {
        $config = new DP_Config();
        try {
            $db = new PDO('mysql:host=' . $config->host . ';dbname=' . $config->db, $config->user, $config->password);
            $db->query("SET NAMES utf8;");
            return $db;
        } catch (PDOException $e) {
            $this->jsonResponse(['error' => 'Ошибка подключения к БД']);
        }
    }
    private function jsonResponse($data) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$api = new CalendarAPI();
$api->handleRequest();
?>