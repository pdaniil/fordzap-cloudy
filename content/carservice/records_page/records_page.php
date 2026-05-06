<?php

$user_id = DP_User::getUserId();

$select_statuses = $db_link->prepare("SELECT * FROM `shop_orders_items_statuses_ref`;");
$select_statuses->execute();

$res_select_statuses = $select_statuses->fetchAll(PDO::FETCH_ASSOC);

$statuses_arr = [];

foreach ($res_select_statuses as $status_info) {
	$statuses_arr[$status_info["id"]] = [
		"name" => $status_info["name"],
		"color" => $status_info["color"]
	];
}

//Получаем данные сто
$sto_data_select = $db_link->prepare("SELECT * FROM `car_services`;");
$sto_data_select->execute();
$sto_data = $sto_data_select->fetchAll(PDO::FETCH_ASSOC); //

$sto_arr = [];

foreach ($sto_data as $sto) {
	$sto_arr[$sto["id"]] = [
		"address" => $sto["address"],
		"time_from" => $sto["time_from"], // 9 время работы с
		"time_to" => $sto["time_to"], // 18 время работы по
		"operating_mode" => $sto["operating_mode"], // ["1","2","3","4","5","6"] - с понедельника по субботу
		"coords" => $sto["coords"], //53.991354, 38.131750
	];
}


$arr_workers = [];
$arr_workers_select = $db_link->prepare("SELECT * FROM `car_service_personal`");
$arr_workers_select->execute();

$arr_workers = $arr_workers_select->fetchAll(PDO::FETCH_ASSOC);

$workers_arr = [];

foreach ($arr_workers as $worker) {
	$workers_arr[$worker["id"]] = [
		"fio" => $worker["fio"],
		"phone" => $worker["phone"]
	];
}

$records_info = [];

$select_cars = $db_link->prepare("SELECT * FROM `shop_docpart_garage` WHERE `user_id` = ?;");
$select_cars->execute([$user_id]);
$cars = $select_cars->fetchAll(PDO::FETCH_ASSOC);

foreach($cars as $car) {
    // Используем car_id как ключ
    $car_id = $car["id"];
    
    $records_info[$car_id]["car_info"] = [
        "caption" => $car["caption"],
        "mark" => $car["marka"],
        "model" => $car["model"],
        "mileage" => $car["mileage"],
        "vin" => $car["vin"]
    ];
    
    // 1. Ошибка: передаем неправильный параметр в execute()
    // Вместо execute($car["id"]) нужно execute([$car["id"]])
    $select_records = $db_link->prepare("SELECT * FROM `car_service_records` WHERE `car_id` = ?;");
    $select_records->execute([$car_id]); // Исправлено: массив с параметром
    
    $records = $select_records->fetchAll(PDO::FETCH_ASSOC);
    
    // Инициализируем массив записей для машины
    $records_info[$car_id]["records_info"] = [];
    
    foreach ($records as $record) {
        $record_id = $record["id"];
        
        $records_info[$car_id]["records_info"][$record_id] = [
            "time" => $record["time"], //2026-01-16 15:30:00 - пример, время заезда
            "sto_data" => $sto_arr[$record["sto_id"]],
            "worker_data" => $workers_arr[$record["worker_id"]],
            "services" => []
        ];
        
        $select_services = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?;");
        $select_services->execute([$record_id]);
        
        $services = $select_services->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($services as $service) {
            $records_info[$car_id]["records_info"][$record_id]["services"][] = [
                "caption" => $service["caption"],
				"price" => $service["price"],
                "status_info" => $statuses_arr[$service["status_id"]]
            ];
        }
    }
}

?>  


<?php
// ... ваш PHP код для получения данных ...

if (empty($records_info)): 
?>
<div class="cs-no-cars">У вас пока нет автомобилей в гараже</div>
<?php else: ?>

<div class="cs-container">
  <?php foreach ($records_info as $car_id => $car_data): 
    $car = $car_data['car_info'];
    $records = $car_data['records_info'] ?? [];
    
    // Определяем ближайшую будущую запись
    $cs_upcoming_date = null;
    foreach ($records as $record) {
        if ($record['time'] !== '0000-00-00 00:00:00' && strtotime($record['time']) > time()) {
            if (!$cs_upcoming_date || strtotime($record['time']) < strtotime($cs_upcoming_date)) {
                $cs_upcoming_date = $record['time'];
            }
        }
    }
    
    // Сортируем записи по дате (новые сверху)
    uasort($records, function($a, $b) {
        if ($a['time'] === '0000-00-00 00:00:00') return 1;
        if ($b['time'] === '0000-00-00 00:00:00') return -1;
        return strtotime($b['time']) <=> strtotime($a['time']);
    });
    
    // Разделяем записи на актуальную (первую) и остальные
    $cs_first_record = !empty($records) ? [key($records) => current($records)] : [];
    $cs_other_records = !empty($records) ? array_slice($records, 1, null, true) : [];
    $cs_has_other_records = count($cs_other_records) > 0;
  ?>
  
  <div class="cs-car-card" data-car-id="<?= $car_id ?>">
    <!-- Заголовок карточки -->
    <div class="cs-car-header">
      <div class="cs-car-title">
        <h3 class="cs-car-name">
          <?= htmlspecialchars($car['mark']) ?> <?= htmlspecialchars($car['model']) ?>
          <?php if (!empty($car['caption']) && $car['caption'] !== $car['mark'] . ' ' . $car['model']): ?>
            <span class="cs-car-nickname">"<?= htmlspecialchars($car['caption']) ?>"</span>
          <?php endif; ?>
        </h3>
        <div class="cs-car-details">
          <?php if (!empty($car['vin'])): ?>
            <span class="cs-car-vin">VIN: <?= htmlspecialchars($car['vin']) ?></span>
          <?php endif; ?>
          <?php if (!empty($car['mileage'])): ?>
            <span class="cs-car-mileage">Пробег: <?= number_format($car['mileage'], 0, '', ' ') ?> км</span>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Ближайшая запись (только дата) -->
      <?php if ($cs_upcoming_date): ?>
      <div class="cs-next-visit">
        <div class="cs-next-visit-icon">📅</div>
        <div class="cs-next-visit-date">
          <?= date('d.m.Y H:i', strtotime($cs_upcoming_date)) ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    
    <!-- Актуальная запись (последняя по дате) -->
  <?php if (!empty($cs_first_record)): ?>
	<div class="cs-records" id="cs-records-<?= $car_id ?>">
	  <?php foreach ($cs_first_record as $record_id => $record): 
		$cs_record_date = $record['time'] !== '0000-00-00 00:00:00' 
		  ? date('d.m.Y H:i', strtotime($record['time'])) 
		  : 'Дата не назначена';
		  
		$cs_record_services = $record['services'] ?? [];
	  ?>
	  
	  <div class="cs-record open" data-record-id="<?= $record_id ?>">
		<div class="cs-record-header">
		  <div class="cs-record-date"><?= $cs_record_date ?></div>
		  <div class="cs-record-toggle">
			<span class="cs-record-toggle-icon">▲</span> <!-- Иконка всегда вверх для актуальной -->
		  </div>
		</div>
        
        <div class="cs-record-preview">
          <?php if (!empty($record['sto_data']['address'])): ?>
          <div class="cs-record-location">
            <span class="cs-record-location-icon">📍</span>
            <?= htmlspecialchars($record['sto_data']['address']) ?>
          </div>
          <?php endif; ?>
          
          <?php if (!empty($cs_record_services)): ?>
          <div class="cs-record-services-preview">
            <?php 
            $displayed_services = 0;
            foreach ($cs_record_services as $index => $service): 
                if ($displayed_services >= 2) break;
            ?>
            <div class="cs-service-preview">
              <span class="cs-service-status-indicator" 
                    style="background-color: <?= $service['status_info']['color'] ?? '#CCCCCC' ?>"></span>
              <span class="cs-service-name"><?= htmlspecialchars($service['caption']) ?></span>
            </div>
            <?php 
                $displayed_services++;
            endforeach; 
            
            if (count($cs_record_services) > 2): ?>
            <div class="cs-more-services">
              +<?= count($cs_record_services) - 2 ?> ещё
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
           
        <!-- Детали записи (открыты по умолчанию) -->
        <div class="cs-record-details">
          <?php if (!empty($record['worker_data']['fio'])): ?>
          <div class="cs-record-master">
            <div class="cs-record-master-title">Мастер:</div>
            <div class="cs-record-master-info">
              <div class="cs-record-master-name"><?= htmlspecialchars($record['worker_data']['fio']) ?></div>
              <?php if (!empty($record['worker_data']['phone'])): ?>
              <div class="cs-record-master-phone"><?= htmlspecialchars($record['worker_data']['phone']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          
          <?php if (!empty($cs_record_services)): ?>
          <div class="cs-record-services">
            <div class="cs-record-services-title">Услуги:</div>
            <?php foreach ($cs_record_services as $service): ?>
            <div class="cs-service">
              <div class="cs-service-header">
                <span class="cs-service-status-indicator" 
                      style="background-color: <?= $service['status_info']['color'] ?? '#CCCCCC' ?>"></span>
                <span class="cs-service-name" style="display: flex; justify-content: space-between;"><span><?= htmlspecialchars($service['caption']) ?></span> <span><?=$service['price'];?>&nbsp;₽</span></span>
              </div>
              <?php if (!empty($service['status_info']['name'])): ?>
              <div class="cs-service-status" style="color: <?= $service['status_info']['color'] ?>">
                <?= $service['status_info']['name'] ?>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          
          <button class="cs-record-more-btn" 
                  data-record-info='<?= json_encode($record, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
            Подробнее о записи
          </button>
        </div>
      </div>
      <?php endforeach; ?>
      
      <!-- Остальные записи (скрыты) -->
      <?php if (!empty($cs_other_records)): ?>
      <div class="cs-other-records" id="cs-other-records-<?= $car_id ?>" style="display: none;">
        <?php foreach ($cs_other_records as $record_id => $record): 
          $cs_record_date = $record['time'] !== '0000-00-00 00:00:00' 
            ? date('d.m.Y H:i', strtotime($record['time'])) 
            : 'Дата не назначена';
            
          $cs_record_services = $record['services'] ?? [];
        ?>
        
        <div class="cs-record" data-record-id="<?= $record_id ?>">
          <div class="cs-record-header">
            <div class="cs-record-date"><?= $cs_record_date ?></div>
            <div class="cs-record-toggle">
              <span class="cs-record-toggle-icon">▼</span>
            </div>
          </div>
          
          <div class="cs-record-preview">
            <?php if (!empty($record['sto_data']['address'])): ?>
            <div class="cs-record-location">
              <span class="cs-record-location-icon">📍</span>
              <?= htmlspecialchars($record['sto_data']['address']) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($cs_record_services)): ?>
            <div class="cs-record-services-preview">
              <?php 
              $displayed_services = 0;
              foreach ($cs_record_services as $index => $service): 
                  if ($displayed_services >= 2) break;
              ?>
              <div class="cs-service-preview">
                <span class="cs-service-status-indicator" 
                      style="background-color: <?= $service['status_info']['color'] ?? '#CCCCCC' ?>"></span>
                <span class="cs-service-name"><?= htmlspecialchars($service['caption']) ?></span>
              </div>
              <?php 
                  $displayed_services++;
              endforeach; 
              
              if (count($cs_record_services) > 2): ?>
              <div class="cs-more-services">
                +<?= count($cs_record_services) - 2 ?> ещё
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          
          <!-- Детали записи (скрыты по умолчанию) -->
          <div class="cs-record-details">
            <?php if (!empty($record['worker_data']['fio'])): ?>
            <div class="cs-record-master">
              <div class="cs-record-master-title">Мастер:</div>
              <div class="cs-record-master-info">
                <div class="cs-record-master-name"><?= htmlspecialchars($record['worker_data']['fio']) ?></div>
                <?php if (!empty($record['worker_data']['phone'])): ?>
                <div class="cs-record-master-phone"><?= htmlspecialchars($record['worker_data']['phone']) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($cs_record_services)): ?>
            <div class="cs-record-services">
              <div class="cs-record-services-title">Услуги:</div>
              <?php foreach ($cs_record_services as $service): ?>
              <div class="cs-service">
                <div class="cs-service-header">
                  <span class="cs-service-status-indicator" 
                        style="background-color: <?= $service['status_info']['color'] ?? '#CCCCCC' ?>"></span>
                  <span class="cs-service-name"><?= htmlspecialchars($service['caption']) ?></span>
                </div>
                <?php if (!empty($service['status_info']['name'])): ?>
                <div class="cs-service-status" style="color: <?= $service['status_info']['color'] ?>">
                  <?= $service['status_info']['name'] ?>
                </div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <button class="cs-record-more-btn" 
                    data-record-info='<?= json_encode($record, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
              Подробнее о записи
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Кнопка для показа остальных записей -->
      <button class="cs-show-other-btn" data-car-id="<?= $car_id ?>">
        Показать все записи (<?= count($cs_other_records) ?>)
      </button>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="cs-no-records">
      <div class="cs-no-records-icon">🚗</div>
      <div class="cs-no-records-text">Записей на обслуживание нет</div>
    </div>
    <?php endif; ?>
  </div>
  
  <?php endforeach; ?>
</div>

<!-- Модальное окно -->  

<style>
/* Основные стили */
.cs-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

/* Карточка автомобиля */
.cs-car-card {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 24px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.cs-car-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

/* Заголовок карточки */
.cs-car-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 16px;
}

.cs-car-title {
  flex: 1;
  min-width: 250px;
}

.cs-car-name {
  font-size: 24px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}

.cs-car-nickname {
  font-size: 18px;
  color: #666;
  font-weight: normal;
}

.cs-car-details {
  display: flex;
  gap: 16px;
  font-size: 14px;
  color: #666;
  flex-wrap: wrap;
}

.cs-car-vin,
.cs-car-mileage {
  padding: 4px 8px;
  background: #f5f5f5;
  border-radius: 4px;
}

/* Ближайший визит (только дата) */
.cs-next-visit {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.cs-next-visit-icon {
  font-size: 16px;
  opacity: 0.7;
}

.cs-next-visit-date {
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

/* Список записей */
.cs-records {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cs-record {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.2s;
  border-left: 4px solid #666; /* Единый нейтральный цвет */
}

.cs-record:hover {
  border-color: #bbbbbb;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Заголовок записи */
.cs-record-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #fafafa;
  cursor: pointer;
  user-select: none;
}

.cs-record-date {
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.cs-record-toggle {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  background: #ffffff;
  border: 1px solid #e0e0e0;
  transition: transform 0.3s;
}

.cs-record.open .cs-record-toggle {
  transform: rotate(180deg);
}

/* Превью записи */
.cs-record-preview {
  padding: 0 16px 16px 16px;
}

.cs-record-location {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #666;
  margin-bottom: 12px;
}

.cs-record-location-icon {
  opacity: 0.7;
}

.cs-record-services-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.cs-service-preview {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  background: #f8f9fa;
  border-radius: 4px;
  font-size: 13px;
  color: #555;
}

.cs-service-status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.cs-more-services {
  font-size: 12px;
  color: #888;
  padding: 4px 8px;
}

/* Детали записи (скрыты) */
.cs-record-details {
  max-height: 0;
  overflow: hidden;
  opacity: 0;
  transition: max-height 0.3s, opacity 0.3s, padding 0.3s;
}

.cs-record.open .cs-record-details {
  max-height: 1000px;
  opacity: 1;
  padding: 0 16px 16px 16px;
}

.cs-record-master,
.cs-record-services {
  margin-top: 16px;
}

.cs-record-master-title,
.cs-record-services-title {
  font-size: 14px;
  font-weight: 500;
  color: #666;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.cs-record-master-info {
  background: #f8f9fa;
  padding: 12px;
  border-radius: 6px;
}

.cs-record-master-name {
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
}

.cs-record-master-phone {
  font-size: 14px;
  color: #666;
}

/* Услуги в деталях */
.cs-service {
  background: #ffffff;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 12px;
  margin-bottom: 8px;
}

.cs-service:last-child {
  margin-bottom: 0;
}

.cs-service-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.cs-service-name {
  flex: 1;
  font-weight: 500;
  color: #333;
}

.cs-service-status {
  font-size: 13px;
  font-weight: 500;
  padding: 2px 8px;
  border-radius: 10px;
  background: rgba(0, 0, 0, 0.05);
  display: inline-block;
}

/* Кнопка подробнее */
.cs-record-more-btn {
  width: 100%;
  margin-top: 16px;
  padding: 12px;
  background: #2196f3;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.cs-record-more-btn:hover {
  background: #1976d2;
}

/* Кнопка для показа остальных записей */
.cs-show-other-btn {
  margin-top: 16px;
  padding: 12px;
  background: #f5f5f5;
  color: #666;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
  width: 100%;
}

.cs-show-other-btn:hover {
  background: #e0e0e0;
  color: #333;
}

.cs-show-other-btn.active {
  background: #e0e0e0;
  color: #333;
}

/* Нет записей */
.cs-no-records {
  text-align: center;
  padding: 40px 20px;
  color: #888;
}

.cs-no-records-icon {
  font-size: 40px;
  margin-bottom: 12px;
  opacity: 0.5;
}

.cs-no-records-text {
  font-size: 16px;
}

/* Нет автомобилей */
.cs-no-cars {
  text-align: center;
  padding: 60px 20px;
  color: #666;
  font-size: 18px;
  background: #fafafa;
  border-radius: 12px;
  max-width: 600px;
  margin: 40px auto;
}

/* Модальное окно */
.cs-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
}

.cs-modal.active {
  display: block;
}

.cs-modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
}

.cs-modal-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.cs-modal-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  color: #333;
  font-size: 24px;
  line-height: 1;
  cursor: pointer;
  z-index: 1;
  transition: background-color 0.2s;
}

.cs-modal-close:hover {
  background: rgba(0, 0, 0, 0.2);
}

.cs-modal-header {
  padding: 24px 24px 16px;
  border-bottom: 1px solid #e0e0e0;
}

.cs-modal-title {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 600;
  color: #1a1a1a;
}

.cs-modal-date {
  font-size: 14px;
  color: #666;
}

.cs-modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

/* Секции модального окна */
.cs-modal-section {
  margin-bottom: 24px;
}

.cs-modal-section:last-child {
  margin-bottom: 0;
}

.cs-modal-section-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin: 0 0 12px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #f0f0f0;
}

/* Стили для контента модального окна */
.cs-modal-sto,
.cs-modal-master {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 8px;
}

.cs-modal-sto-address {
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.cs-modal-sto-hours {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
  color: #666;
  margin-bottom: 12px;
}

/* Ссылка на карту */
.cs-map-link {
  display: inline-block;
  padding: 8px 16px;
  background: #2196f3;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
  margin-top: 8px;
}

.cs-map-link:hover {
  background: #1976d2;
  color: white;
  text-decoration: none;
}

.cs-modal-master-name {
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
}

.cs-modal-master-phone {
  font-size: 14px;
  color: #666;
}

/* Услуги в модальном окне */
.cs-modal-services {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.cs-modal-service {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #ffffff;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
}

.cs-modal-service-status {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.cs-modal-service-name {
  flex: 1;
  font-weight: 500;
  color: #333;
}

.cs-modal-service-status-text {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 10px;
  background: rgba(0, 0, 0, 0.05);
}

/* Адаптивность */
@media (max-width: 768px) {
  .cs-container {
    padding: 12px;
  }
  
  .cs-car-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .cs-next-visit {
    align-self: flex-start;
  }
  
  .cs-car-details {
    flex-direction: column;
    gap: 8px;
  }
  
  .cs-modal-content {
    width: 95%;
    max-height: 85vh;
  }
  
  .cs-modal-body {
    padding: 16px;
  }
  
  .cs-modal-sto-hours {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .cs-car-name {
    font-size: 20px;
  }
  
  .cs-record-header {
    padding: 12px;
  }
  
  .cs-record-date {
    font-size: 14px;
  }
  
  .cs-next-visit-date {
    font-size: 14px;
  }
  
 /* Замените стили для аккордеона */

.cs-record .cs-record-details {
  max-height: 0;
  opacity: 0;
  padding: 0 16px;
  overflow: hidden;
  transition: max-height 0.3s ease, opacity 0.3s ease, padding 0.3s ease;
}

.cs-record.open .cs-record-details {
  max-height: 2000px;
  opacity: 1;
  padding: 0 16px 16px 16px;
}

/* Для актуальной записи */
.cs-car-card > .cs-records > .cs-record.open .cs-record-details {
  max-height: 2000px;
  opacity: 1;
  padding: 0 16px 16px 16px;
}
  
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Простой и понятный обработчик для аккордеона
  function setupRecordAccordion() {
    document.querySelectorAll('.cs-record-header').forEach(header => {
      // Убираем старые обработчики
      header.removeEventListener('click', handleRecordClick);
      // Добавляем новый обработчик
      header.addEventListener('click', handleRecordClick);
    });
  }
  
  function handleRecordClick(e) {
    const header = e.currentTarget;
    const record = header.closest('.cs-record');
    const toggleIcon = header.querySelector('.cs-record-toggle-icon');
    
    // Переключаем класс open
    record.classList.toggle('open');
    
    // Обновляем иконку
    if (record.classList.contains('open')) {
      toggleIcon.textContent = '▲';
    } else {
      toggleIcon.textContent = '▼';
    }
    
    // Для актуальной записи всегда показываем ▲ и не закрываем
    const isFirstRecord = record.parentElement?.classList?.contains('cs-records') && 
                         !record.parentElement?.classList?.contains('cs-other-records');
    
    if (isFirstRecord) {
      // Актуальную запись не закрываем, всегда оставляем открытой
      record.classList.add('open');
      toggleIcon.textContent = '▲';
    }
  }
  
  // Инициализируем аккордеон
  setupRecordAccordion();
  
  // Модальное окно
  const modal = document.getElementById('csRecordModal');
  const modalClose = modal.querySelector('.cs-modal-close');
  const modalOverlay = modal.querySelector('.cs-modal-overlay');
  const mapLink = document.getElementById('csMapLink');
  
  // Функция для создания ссылки на Яндекс.Карты
  function createYandexMapsLink(coords, address) {
    const [lat, lon] = coords.split(',').map(c => c.trim());
    const encodedAddress = encodeURIComponent(address || '');
    return `https://yandex.ru/maps/?pt=${lon},${lat}&z=16&l=map&text=${encodedAddress}`;
  }
  
  // Функция открытия модального окна
  function openModal(recordInfo) {
    // Парсим JSON
    const record = JSON.parse(recordInfo);
    
    // Заполняем данные
    document.getElementById('csModalDate').textContent = 
      record.time !== '0000-00-00 00:00:00' 
        ? formatDateTime(record.time) 
        : 'Дата не назначена';
    
    // Информация о СТО
    const sto = record.sto_data || {};
    const address = sto.address || '';
    document.getElementById('csModalAddress').textContent = address;
    
    const hoursElement = document.getElementById('csModalHours');
    const daysElement = document.getElementById('csModalDays');
    
    if (sto.time_from && sto.time_to) {
      hoursElement.textContent = `Время работы: ${sto.time_from}:00 - ${sto.time_to}:00`;
    } else {
      hoursElement.textContent = '';
    }
    
    if (sto.operating_mode) {
      try {
        const days = JSON.parse(sto.operating_mode);
        const dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        const workingDays = days.map(day => dayNames[day-1]).join(', ');
        daysElement.textContent = `Дни: ${workingDays}`;
      } catch(e) {
        daysElement.textContent = sto.operating_mode;
      }
    } else {
      daysElement.textContent = '';
    }
    
    // Ссылка на карту
    if (sto.coords) {
      mapLink.href = createYandexMapsLink(sto.coords, address);
      mapLink.style.display = 'inline-block';
    } else {
      mapLink.style.display = 'none';
    }
    
    // Информация о мастере
    const worker = record.worker_data || {};
    document.getElementById('csModalMasterName').textContent = worker.fio || 'Мастер не назначен';
    document.getElementById('csModalMasterPhone').textContent = worker.phone || 'Телефон не указан';
    
    // Услуги
    const servicesContainer = document.getElementById('csModalServices');
    servicesContainer.innerHTML = '';
    
    if (record.services && record.services.length > 0) {
      record.services.forEach(service => {
        const serviceEl = document.createElement('div');
        serviceEl.className = 'cs-modal-service';
        
        const statusColor = service.status_info?.color || '#CCCCCC';
        const statusName = service.status_info?.name || 'Статус не указан';
        
        serviceEl.innerHTML = `
          <span class="cs-modal-service-status" style="background-color: ${statusColor}"></span>
          <span class="cs-modal-service-name">${escapeHtml(service.caption)}</span>
          <span class="cs-modal-service-status-text" style="color: ${statusColor}">
            ${escapeHtml(statusName)}
          </span>
		  <span class="cs-modal-service-name" style="flex: 0.2;">${service.price}&nbsp;₽</span>
        `;
        
        servicesContainer.appendChild(serviceEl);
      });
    } else {
      servicesContainer.innerHTML = '<div style="text-align: center; color: #888; padding: 20px;">Услуги не указаны</div>';
    }
    
    // Показываем модальное окно
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  
  // Закрытие модального окна
  function closeModal() {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
  
  // Обработчики кнопок "Подробнее"
  document.querySelectorAll('.cs-record-more-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const recordInfo = this.getAttribute('data-record-info');
      openModal(recordInfo);
    });
  });
  
  // Показать остальные записи
  document.querySelectorAll('.cs-show-other-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const carId = this.getAttribute('data-car-id');
      const otherRecords = document.getElementById(`cs-other-records-${carId}`);
      
      if (otherRecords && otherRecords.style.display === 'none') {
        // Показываем остальные записи
        otherRecords.style.display = 'block';
        
        // Добавляем анимацию появления
        Array.from(otherRecords.children).forEach((record, index) => {
          setTimeout(() => {
            record.style.opacity = '0';
            record.style.transform = 'translateY(10px)';
            record.style.display = 'block';
            
            setTimeout(() => {
              record.style.transition = 'opacity 0.3s, transform 0.3s';
              record.style.opacity = '1';
              record.style.transform = 'translateY(0)';
            }, 10);
          }, index * 50);
        });
        
        // Меняем текст кнопки
        this.textContent = 'Скрыть старые записи';
        this.classList.add('active');
        
        // Инициализируем аккордеон для новых записей
        setupRecordAccordion();
        
        // Добавляем обработчики для кнопок "Подробнее" в новых записях
        otherRecords.querySelectorAll('.cs-record-more-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            const recordInfo = this.getAttribute('data-record-info');
            openModal(recordInfo);
          });
        });
        
      } else if (otherRecords) {
        // Скрываем записи
        Array.from(otherRecords.children).forEach((record, index) => {
          setTimeout(() => {
            record.style.opacity = '0';
            record.style.transform = 'translateY(10px)';
            
            setTimeout(() => {
              record.style.display = 'none';
            }, 300);
          }, index * 50);
        });
        
        // Возвращаем кнопку в исходное состояние
        setTimeout(() => {
          this.textContent = `Показать все записи (${otherRecords.children.length})`;
          this.classList.remove('active');
          otherRecords.style.display = 'none';
        }, 300 + otherRecords.children.length * 50);
      }
    });
  });
  
  // Закрытие по клику на крестик, оверлей или ESC
  modalClose.addEventListener('click', closeModal);
  modalOverlay.addEventListener('click', closeModal);
  
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.classList.contains('active')) {
      closeModal();
    }
  });
  
  // Вспомогательные функции
  function formatDateTime(dateTimeStr) {
    const date = new Date(dateTimeStr);
    return date.toLocaleDateString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
});
</script>
<?php endif; ?>