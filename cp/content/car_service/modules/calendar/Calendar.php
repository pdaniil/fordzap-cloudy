<?php
/**
 * Классы для работы с календарем записей автосервиса
 * Версия: 1.0
 * Дата: 2026
 */

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

/**
 * Class CalendarService
 * Основной класс для работы с данными календаря
 */
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

    /**
     * Конструктор
     * @param int $serviceId ID сервиса
     */
    public function __construct($serviceId = null) {
        $this->config = new DP_Config();
        $this->connectDB();
        
        if ($serviceId) {
            $this->setService($serviceId);
        }
    }

    /**
     * Подключение к БД
     */
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

    /**
     * Установка сервиса и загрузка всех связанных данных
     * @param int $serviceId
     */
    public function setService($serviceId) {
        $this->serviceId = (int)$serviceId;
        $this->loadServiceData();
        $this->loadWorkers();
        $this->loadPosts();
        $this->loadUsers();
        $this->loadCars();
    }

    /**
     * Загрузка данных сервиса
     */
    private function loadServiceData() {
        $stmt = $this->db->prepare("SELECT * FROM `car_services` WHERE `id` = ?");
        $stmt->execute([$this->serviceId]);
        $this->serviceData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$this->serviceData) {
            $this->jsonError('Сервис не найден');
        }
        
        // Парсим рабочие дни
        $this->serviceData['operating_mode'] = json_decode($this->serviceData['operating_mode'], true);
    }

    /**
     * Загрузка сотрудников
     */
    private function loadWorkers() {
        $stmt = $this->db->prepare("SELECT * FROM `car_service_personal` ORDER BY `fio`");
        $stmt->execute();
        $this->workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Загрузка постов
     */
    private function loadPosts() {
        $stmt = $this->db->prepare("SELECT * FROM `car_service_posts` ORDER BY `id`");
        $stmt->execute();
        $this->posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Загрузка пользователей
     */
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

    /**
     * Загрузка автомобилей
     */
    private function loadCars() {
        $stmt = $this->db->prepare("SELECT * FROM `shop_docpart_garage`");
        $stmt->execute();
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cars as $car) {
            $this->cars[$car['id']] = $car;
        }
    }

    /**
     * Загрузка записей за период
     * @param string $dateFrom Начало периода (Y-m-d)
     * @param string $dateTo Конец периода (Y-m-d)
     * @param int|null $postId ID поста (опционально)
     */
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
            $sql .= " AND r.post_id = :post_id";
            $params[':post_id'] = $postId;
        }
        
        $sql .= " ORDER BY r.time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $this->records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Загружаем услуги для записей
        $this->loadRecordServices();
    }

    /**
     * Загрузка услуг для записей
     */
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
        
        // Группируем услуги по ID записи
        $this->recordServices = [];
        foreach ($services as $service) {
            $this->recordServices[$service['record_id']][] = $service;
        }
    }

    /**
     * Получение статистики за период
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getStats($dateFrom, $dateTo) {
        $this->loadRecords($dateFrom, $dateTo);
        
        $stats = [
            'total_records' => count($this->records),
            'total_sum' => 0,
            'total_hours' => 0,
            'unique_clients' => 0,
            'unique_cars' => 0,
            'by_status' => [],
            'by_worker' => []
        ];
        
        $clients = [];
        $cars = [];
        
        foreach ($this->records as $record) {
            // Сумма по услугам
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
            
            if ($record['user_id']) {
                $clients[$record['user_id']] = true;
            }
            
            if ($record['car_id']) {
                $cars[$record['car_id']] = true;
            }
            
            // Статистика по сотрудникам
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

    /**
     * Получение данных для календаря
     * @param string $view Тип отображения (year, month, week, day)
     * @param string $date Дата
     * @param int|null $postId
     * @return array
     */
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

    /**
     * Получение данных за год
     */
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

    /**
     * Получение данных за месяц
     */
    private function getMonthData(DateTime $date) {
        $start = $date->format('Y-m-01');
        $end = $date->format('Y-m-t');
        
        $this->loadRecords($start, $end);
        
        $days = [];
        $current = clone $date;
        $current->modify('first day of this month');
        
        while ($current->format('Y-m') === $date->format('Y-m')) {
            $dayStr = $current->format('Y-m-d');
            $dayRecords = array_filter($this->records, function($record) use ($dayStr) {
                return substr($record['time'], 0, 10) === $dayStr;
            });
            
            // Расчет свободных слотов
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
                'sum' => $this->calculateSumForRecords($dayRecords)
            ];
            
            $current->modify('+1 day');
        }
        
        return [
            'month' => $date->format('Y-m'),
            'month_name' => $this->getMonthName($date->format('n')),
            'year' => $date->format('Y'),
            'days' => $days,
            'total' => [
                'count' => count($this->records),
                'sum' => $this->calculateSumForRecords($this->records)
            ]
        ];
    }

    /**
     * Получение данных за неделю
     */
    private function getWeekData(DateTime $date) {
        // Находим понедельник недели
        $clone = clone $date;
        $clone->modify('monday this week');
        $start = $clone->format('Y-m-d');
        
        $clone->modify('+6 days');
        $end = $clone->format('Y-m-d');
        
        $this->loadRecords($start, $end);
        
        $days = [];
        $current = new DateTime($start);
        
        while ($current->format('Y-m-d') <= $end) {
            $dayStr = $current->format('Y-m-d');
            $dayRecords = array_filter($this->records, function($record) use ($dayStr) {
                return substr($record['time'], 0, 10) === $dayStr;
            });
            
            // Расчет свободных слотов
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
                'sum' => $this->calculateSumForRecords($dayRecords)
            ];
            
            $current->modify('+1 day');
        }
        
        return [
            'week_start' => $start,
            'week_end' => $end,
            'days' => $days,
            'total' => [
                'count' => count($this->records),
                'sum' => $this->calculateSumForRecords($this->records)
            ]
        ];
    }

    /**
     * Получение данных за день
     */
    private function getDayData(DateTime $date, $postId = null) {
        $dayStr = $date->format('Y-m-d');
        
        // Если пост не указан, берем первый
        if ($postId === null && !empty($this->posts)) {
            $postId = $this->posts[0]['id'];
        }
        
        $this->loadRecords($dayStr, $dayStr, $postId);
        
        // Генерация временных слотов
        $slots = $this->generateTimeSlots($date);
        
        // Группировка записей по временным слотам
        $slotRecords = [];
        foreach ($this->records as $record) {
            $recordTime = substr($record['time'], 11, 5);
            $slotRecords[$recordTime] = $this->enrichRecordWithDetails($record);
        }
        
        // Статистика по постам для этого дня
        $postsStats = [];
        foreach ($this->posts as $post) {
            $this->loadRecords($dayStr, $dayStr, $post['id']);
            $postsStats[$post['id']] = [
                'id' => $post['id'],
                'caption' => $post['caption'],
                'image' => $post['image'],
                'records_count' => count($this->records),
                'sum' => $this->calculateSumForRecords($this->records)
            ];
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

    /**
     * Генерация временных слотов для дня
     */
    private function generateTimeSlots(DateTime $date) {
        $step = $this->serviceData['record_step'] ?? 30; // шаг в минутах
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

    /**
     * Обогащение записи дополнительными данными
     */
    /**
	 * Обогащение записи дополнительными данными
	 */
		private function enrichRecordWithDetails($record) {
			$services = $this->recordServices[$record['id']] ?? [];
			
			// Расчет общей суммы и часов
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
			
			// Данные клиента
			$client = $this->users[$record['user_id']] ?? null;
			$clientName = $client ? trim($client['surname'] . ' ' . $client['name']) : 'Неизвестно';
			
			// Данные автомобиля
			$car = $this->cars[$record['car_id']] ?? null;
			$carInfo = '';
			if ($car) {
				$carInfo = trim($car['marka'] . ' ' . $car['model']);
				if ($record['mileage']) {
					$carInfo .= ', пробег: ' . $record['mileage'] . ' км';
				}
			}
			
			return [
				'id' => $record['id'],
				'orders_ids' => $record['orders_ids'] ?? null, // ДОБАВЛЯЕМ ЭТО ПОЛЕ
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
				'mileage' => $record['mileage'],
				'services' => $servicesList,
				'total_sum' => $totalSum,
				'total_hours' => $totalHours
			];
		}

    /**
     * Расчет количества слотов в дне
     */
    private function calculateTotalSlotsForDay(DateTime $date) {
        $step = $this->serviceData['record_step'] ?? 30;
        $timeFrom = $this->serviceData['time_from'] ?? 8;
        $timeTo = $this->serviceData['time_to'] ?? 19;
        
        $hours = $timeTo - $timeFrom;
        return ($hours * 60) / $step;
    }

    /**
     * Расчет суммы для списка записей
     */
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

    /**
     * Получение информации о сервисе
     */
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

    /**
     * Получение названия дня недели
     */
    private function getDayName($dayNum) {
        $days = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье'
        ];
        return $days[(int)$dayNum] ?? '';
    }

    /**
     * Получение названия месяца
     */
    private function getMonthName($monthNum) {
        $months = [
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь'
        ];
        return $months[(int)$monthNum] ?? '';
    }

    /**
     * Вывод ошибки в JSON и завершение скрипта
     */
    private function jsonError($message) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }

    /**
     * Получение данных для печати разнорядки
     */
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

    /**
     * Получение информации о посте
     */
    private function getPostInfo($postId) {
        foreach ($this->posts as $post) {
            if ($post['id'] == $postId) {
                return $post;
            }
        }
        return null;
    }
}

/**
 * Class CalendarAPI
 * Обработка API запросов
 */
class CalendarAPI {
    private $service;
    
    public function __construct() {
        header('Content-Type: application/json');
    }
    
    /**
     * Обработка запроса
     */
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
                    
                default:
                    $this->jsonResponse(['error' => 'Неизвестное действие']);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Получение списка сервисов
     */
    private function getServicesList() {
        $db = $this->getDBConnection();
        $stmt = $db->query("SELECT id, caption FROM car_services ORDER BY caption");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse(['services' => $services]);
    }
    
    /**
     * Получение данных календаря
     */
    private function getCalendar() {
        $serviceId = $_POST['service_id'] ?? $_GET['service_id'] ?? 0;
        $view = $_POST['view'] ?? $_GET['view'] ?? 'week';
        $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
        $postId = $_POST['post_id'] ?? $_GET['post_id'] ?? null;
        
        if (!$serviceId) {
            $this->jsonResponse(['error' => 'Не указан ID сервиса']);
        }
        
        $this->service = new CalendarService($serviceId);
        $data = $this->service->getCalendarData($view, $date, $postId);
        
        // Добавляем статистику
        $dateObj = new DateTime($date);
        switch ($view) {
            case 'year':
                $stats = $this->service->getStats($date . '-01-01', $date . '-12-31');
                break;
            case 'month':
                $stats = $this->service->getStats(
                    $dateObj->format('Y-m-01'),
                    $dateObj->format('Y-m-t')
                );
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
        $this->jsonResponse($data);
    }
    
    /**
     * Получение статистики
     */
    private function getStats() {
        $serviceId = $_POST['service_id'] ?? $_GET['service_id'] ?? 0;
        $dateFrom = $_POST['date_from'] ?? $_GET['date_from'] ?? date('Y-m-d');
        $dateTo = $_POST['date_to'] ?? $_GET['date_to'] ?? date('Y-m-d');
        
        if (!$serviceId) {
            $this->jsonResponse(['error' => 'Не указан ID сервиса']);
        }
        
        $this->service = new CalendarService($serviceId);
        $stats = $this->service->getStats($dateFrom, $dateTo);
        $this->jsonResponse($stats);
    }
    
    /**
     * Получение данных для печати
     */
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
    
    /**
     * Подключение к БД
     */
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
    
    /**
     * JSON ответ
     */
    private function jsonResponse($data) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Обработка запроса
$api = new CalendarAPI();
$api->handleRequest();