<?php
require_once __DIR__ . '/init.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запись в автосервис</title>
<style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .booking-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .step { display: none; }
        .step.active { display: block; }
        .step-header { font-size: 18px; font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select, input[type="text"], input[type="number"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; background: #007bff; color: #fff; }
        .btn-secondary { background: #6c757d; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .step-nav { display: flex; justify-content: space-between; margin-top: 20px; }

        .services-search { margin-bottom: 10px; }
        .services-search input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }

        /* Категории в строку */
        .category-block {
            display: inline-block;
            margin: 5px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            vertical-align: top;
            min-width: 150px;
        }
        .category-title {
            background: #f8f9fa;
            padding: 8px 12px;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
            border-bottom: 1px solid #e0e0e0;
            text-align: center;
        }
        .category-services {
            padding: 10px;
            display: none; /* по умолчанию скрыты */
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .category-block.active .category-services,
        .category-block.search-active .category-services {
            display: flex;
        }

        .service-item {
            background: #f1f1f1;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .service-item:hover { background: #d1ecf1; }
        .service-item.selected { background: #007bff; color: #fff; }

        .selected-services-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .selected-services-table th, .selected-services-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .selected-services-table .remove-btn { color: red; cursor: pointer; }

        .calendar-container { border: 1px solid #ddd; padding: 15px; margin-top: 10px; background: #fff; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
        .calendar-grid .day { padding: 8px; text-align: center; border-radius: 4px; cursor: pointer; }
        .calendar-grid .day.available { background: #e8f5e9; }
        .calendar-grid .day.available:hover { background: #a5d6a7; }
        .calendar-grid .day.selected { background: #007bff; color: #fff; }
        .calendar-grid .day.disabled { color: #ccc; cursor: not-allowed; }
        .slots-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .slot-btn { padding: 6px 14px; border: 1px solid #007bff; background: #fff; border-radius: 4px; cursor: pointer; }
        .slot-btn:hover { background: #007bff; color: #fff; }
        .slot-btn.selected { background: #007bff; color: #fff; }

        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 6px; border-bottom: 1px solid #eee; }

        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
        #message { margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
<div class="booking-container">
    <h2>Запись в автосервис</h2>

    <!-- Шаг 1: СТО -->
    <div class="step active" id="step-sto">
        <div class="step-header">Выберите СТО</div>
        <div class="form-group">
            <label>СТО</label>
            <select id="sto_id" name="sto_id">
                <?php foreach ($sto_list as $sto): ?>
                    <option value="<?= $sto['id'] ?>" <?= $sto['id'] == $default_sto_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sto['caption']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="step-nav">
            <span></span>
            <button type="button" class="btn" id="next-step-1">Далее →</button>
        </div>
    </div>

    <!-- Шаг 2: Услуги -->
    <div class="step" id="step-services">
        <div class="step-header">Выберите услуги</div>
		<div class="form-group">
            <label>Выбранные услуги</label>
            <table class="selected-services-table" id="selectedServicesTable">
                <thead><tr><th>Услуга</th><th>Цена</th><th></th></tr></thead>
                <tbody id="selectedServicesBody"></tbody>
                <tfoot><tr><td colspan="3" style="text-align:right;">Итого: <span id="totalAmount">0.00</span> ₽</td></tr></tfoot>
            </table>
        </div>
        <div class="form-group">
            <div class="services-search">
                <input type="text" id="serviceSearch" placeholder="Поиск услуг..." oninput="filterServices()">
            </div>
            <div id="servicesContainer">
                <!-- категории и услуги загружаются через JS -->
            </div>
        </div>
        <div class="step-nav">
            <button type="button" class="btn btn-secondary" id="prev-step-2">← Назад</button>
            <button type="button" class="btn" id="next-step-2">Далее →</button>
        </div>
    </div>

    <!-- Шаг 4: Дата и время (шаг 3 удалён) -->
    <div class="step" id="step-datetime">
        <div class="step-header">Выберите дату и время</div>
        <div class="form-group">
            <label>Дата и время</label>
            <input type="text" id="selectedDateTime" readonly placeholder="Выберите дату и время" style="background:#f9f9f9; cursor:pointer;" onclick="toggleCalendar()">
            <div id="calendarContainer" class="calendar-container" style="display:none;">
                <div class="calendar-header">
                    <button type="button" id="prevMonthBtn">&lt;</button>
                    <span id="calendarMonth"></span>
                    <button type="button" id="nextMonthBtn">&gt;</button>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
                <div id="slotsContainer">
                    <h5>Доступное время</h5>
                    <div class="slots-list" id="slotsList"></div>
                </div>
            </div>
        </div>
        <div class="step-nav">
            <button type="button" class="btn btn-secondary" id="prev-step-4">← Назад</button>
            <button type="button" class="btn" id="next-step-4">Далее →</button>
        </div>
    </div>

    <!-- Шаг 5: Подтверждение -->
    <div class="step" id="step-confirm">
        <div class="step-header">Подтверждение записи</div>
        <div id="confirmationData">
            <!-- динамически заполняется -->
        </div>
        <div class="form-group">
            <label>Ваше имя</label>
            <input type="text" id="clientName" value="<?= htmlspecialchars($client_data['fio'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Телефон</label>
            <input type="text" id="clientPhone" value="<?= htmlspecialchars($client_data['phone'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" id="clientEmail" value="<?= htmlspecialchars($client_data['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Автомобиль</label>
            <select id="carSelect">
                <option value="">Выберите авто (или введите новое)</option>
                <?php foreach ($cars as $car): ?>
                    <option value="<?= $car['id'] ?>" data-model="<?= htmlspecialchars($car['model']) ?>" data-plate="<?= htmlspecialchars($car['license_plate'] ?? $car['gos_num'] ?? '') ?>" data-vin="<?= htmlspecialchars($car['vin'] ?? '') ?>" data-year="<?= $car['year'] ?>" data-mileage="<?= $car['mileage'] ?>">
                        <?= htmlspecialchars($car['caption'] . ' (' . $car['model'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Марка/Модель</label>
            <input type="text" id="carModel">
        </div>
        <div class="form-group">
            <label>Госномер</label>
            <input type="text" id="carPlate">
        </div>
        <div class="form-group">
            <label>VIN</label>
            <input type="text" id="carVin">
        </div>
        <div class="form-group">
            <label>Год выпуска</label>
            <input type="number" id="carYear">
        </div>
        <div class="form-group">
            <label>Пробег (км)</label>
            <input type="number" id="carMileage">
        </div>

        <div class="step-nav">
            <button type="button" class="btn btn-secondary" id="prev-step-5">← Назад</button>
            <button type="submit" class="btn btn-success" id="submitBtn">Записаться</button>
        </div>
        <div id="message"></div>
    </div>
</div>

<script>
    var apiBase = '<?= $apiBase ?>';
    var stoListCount = <?= count($sto_list) ?>;
</script>

<script src="/booking/js/common.js"></script>
<script src="/booking/js/services.js"></script>
<script src="/booking/js/calendar.js"></script>
<script src="/booking/js/booking.js"></script>

</body>
</html>