<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
} catch (PDOException $e) {
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");
$user_session = DP_User::getAdminSession();

$date = isset($_POST['date']) ? $_POST['date'] : '';
$time = isset($_POST['time']) ? $_POST['time'] : '';
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;

// Данные сервиса
$service_query = $db_link->prepare("SELECT caption, address, phone, id_tree_list_services FROM car_services WHERE id = ?");
$service_query->execute([$service_id]);
$service = $service_query->fetch(PDO::FETCH_ASSOC);

// Данные поста
$post_query = $db_link->prepare("SELECT caption, image FROM car_service_posts WHERE id = ?");
$post_query->execute([$post_id]);
$post = $post_query->fetch(PDO::FETCH_ASSOC);

// Мастера
$workers_query = $db_link->prepare("SELECT id, fio FROM car_service_personal ORDER BY fio");
$workers_query->execute();
$workers = $workers_query->fetchAll(PDO::FETCH_ASSOC);
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* --- Тёмная тема для формы --- */
    .cw-modal-dark {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #FFFFFF;
        background: transparent;
    }

    .cw-service-card {
        background: #2B2B3B;
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid #3A3A50;
    }
    .cw-service-icon { font-size: 32px; }
    .cw-service-info { flex: 1; }
    .cw-service-name { font-size: 18px; font-weight: 700; color: #FFFFFF; margin-bottom: 4px; }
    .cw-service-address, .cw-service-phone { font-size: 13px; color: #A0A0B0; display: flex; align-items: center; gap: 6px; margin-top: 4px; }

    .cw-post-badge {
        background: #222236;
        border-radius: 12px;
        padding: 10px 16px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-left: 4px solid #3B82F6;
        color: #D1D5DB;
    }
    .cw-post-icon { font-size: 20px; }
    .cw-post-name { font-size: 15px; font-weight: 500; }

    .cw-datetime-block {
        background: #222236;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid #3A3A50;
        color: #D1D5DB;
    }
    .cw-datetime-icon { font-size: 20px; }
    .cw-datetime-text { font-size: 15px; font-weight: 500; }

    .cw-field {
        margin-bottom: 18px;
    }
    .cw-field-label {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 6px;
        color: #A0A0B0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .cw-field-label i { font-style: normal; }

    .cw-modal-select,
    .cw-search-input,
    textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #3A3A50;
        border-radius: 10px;
        font-size: 14px;
        background: #2B2B3B;
        color: #FFFFFF;
        transition: border-color 0.2s;
        outline: none;
    }
    .cw-modal-select:focus,
    .cw-search-input:focus,
    textarea:focus {
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    }
    textarea {
        resize: vertical;
        font-family: inherit;
    }
    .cw-modal-select option {
        background: #2B2B3B;
        color: #FFFFFF;
    }

    .cw-submit-btn {
        background: #3B82F6;
        color: white;
        border: none;
        padding: 14px 24px;
        border-radius: 30px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: background 0.2s, transform 0.1s;
        margin-top: 8px;
    }
    .cw-submit-btn:hover { background: #2563eb; }
    .cw-submit-btn:active { transform: scale(0.97); }
    .cw-submit-btn:disabled { opacity: 0.6; transform: none; cursor: not-allowed; }

    /* --- Стилизация Select2 (single & multiple) под тёмную тему --- */
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        background: #2B2B3B;
        border: 1px solid #3A3A50;
        border-radius: 10px;
        min-height: 44px;
        padding: 0 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #FFFFFF;
        line-height: 42px;
        padding-left: 0;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        padding: 4px 0;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #3A3A50;
        border: none;
        color: #FFFFFF;
        border-radius: 12px;
        padding: 4px 12px;
        margin: 4px 6px 4px 0;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #A0A0B0;
        margin-right: 6px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px;
        right: 10px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #A0A0B0 transparent transparent transparent;
    }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent #A0A0B0 transparent;
    }

    .select2-dropdown {
        background: #2B2B3B;
        border: 1px solid #3A3A50;
        border-radius: 10px;
        z-index: 1060 !important;
    }
    .select2-search--dropdown {
        padding: 8px;
        background: #222236;
        border-bottom: 1px solid #3A3A50;
    }
    .select2-search--dropdown .select2-search__field {
        background: #2B2B3B;
        border: 1px solid #3A3A50;
        border-radius: 8px;
        color: #FFFFFF;
        padding: 8px 12px;
        width: 100%;
        outline: none;
    }
    .select2-search--dropdown .select2-search__field:focus {
        border-color: #3B82F6;
    }
    .select2-results__options {
        background: #2B2B3B;
        max-height: 200px;
        overflow-y: auto;
    }
    .select2-results__option {
        color: #D1D5DB;
        padding: 8px 12px;
        background: transparent;
    }
    .select2-results__option--highlighted {
        background: #3A3A50 !important;
        color: #FFFFFF !important;
    }
    .select2-results__option[aria-selected="true"] {
        background: #3B82F6 !important;
        color: #FFFFFF !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #A0A0B0;
    }

    .modal { z-index: 1050; }
    .modal-backdrop { z-index: 1040; }
</style>

<div class="cw-modal-dark">
    <div class="cw-service-card">
        <div class="cw-service-icon">🔧</div>
        <div class="cw-service-info">
            <div class="cw-service-name"><?php echo htmlspecialchars($service['caption'] ?? 'Автосервис'); ?></div>
            <div class="cw-service-address">📍 <?php echo htmlspecialchars($service['address'] ?? ''); ?></div>
            <div class="cw-service-phone">📞 <?php echo htmlspecialchars($service['phone'] ?? ''); ?></div>
        </div>
    </div>

    <div class="cw-post-badge">
        <div class="cw-post-icon">🛠️</div>
        <div class="cw-post-name">Пост: <?php echo htmlspecialchars($post['caption'] ?? 'Не указан'); ?></div>
    </div>

    <div class="cw-datetime-block">
        <div class="cw-datetime-icon">📅</div>
        <div class="cw-datetime-text"><?php echo htmlspecialchars($date); ?> в <?php echo htmlspecialchars($time); ?></div>
    </div>

    <input type="hidden" id="modal_record_date" value="<?php echo htmlspecialchars($date); ?>">
    <input type="hidden" id="modal_record_time" value="<?php echo htmlspecialchars($time); ?>">
    <input type="hidden" id="modal_record_post_id" value="<?php echo $post_id; ?>">
    <input type="hidden" id="modal_record_service_id" value="<?php echo $service_id; ?>">
    <input type="hidden" id="modal_service_tree_id" value="<?php echo (int)$service['id_tree_list_services']; ?>">

    <!-- Клиент -->
    <div class="cw-field">
        <div class="cw-field-label"><i>👤</i> Клиент</div>
        <select id="select_client" style="width: 100%;">
            <option value="">-- Выберите клиента --</option>
            <?php
                $admin_user_query = $db_link->prepare("SELECT `id` FROM groups WHERE for_backend = 1;");
                $admin_user_query->execute();
                $backend_group_fetch = $admin_user_query->fetch();
                $backend_group = $backend_group_fetch["id"];

                $SQL_SELECT_CUSTOMER = "CASE
                WHEN `users`.`reg_variant` = 1 
                    THEN CONCAT('ФЛ ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'surname' AND `user_id` = `users`.`user_id`),''), ' ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'name' AND `user_id` = `users`.`user_id`),''), ' ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'patronymic' AND `user_id` = `users`.`user_id`),''), ' ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'phonen' AND `user_id` = `users`.`user_id`), ''))
                WHEN `users`.`reg_variant` = 2 
                    THEN CONCAT('ИП  ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'surname' AND `user_id` = `users`.`user_id`),''), ' ', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'name' AND `user_id` = `users`.`user_id`),''), ' ИНН', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'inn' AND `user_id` = `users`.`user_id`), ''))
                WHEN `users`.`reg_variant` = 3 
                    THEN CONCAT(IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'company_name' AND `user_id` = `users`.`user_id`), 'Компания'), ' ИНН', IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'inn' AND `user_id` = `users`.`user_id`), ''))
                ELSE ''
                END ";
                
                $SQL_SELECT_USERS = "SELECT
                    `users`.`user_id`,
                    `users`.`email`,
                    `users`.`phone`,
                    $SQL_SELECT_CUSTOMER as `customer`
                    FROM `users`;";

                $user_query = $db_link->prepare($SQL_SELECT_USERS);
                $user_query->execute();
                while($user_record = $user_query->fetch()) {
                    $uid = $user_record["user_id"];
                    $email = $user_record['email'];
                    $phone = $user_record['phone'];
                    $customer = $user_record['customer'];
                    $main_field = "";
                    if(!empty($email)) $main_field .= $email . " ";
                    if(!empty($phone)) $main_field .= str_replace([' ', '(', ')', '-'], '', $phone) . " ";
                    $main_field = trim($main_field);
                    $display_text = $customer . ' ' . $main_field . ' (id-' . $uid . ')';
                    echo '<option value="'.$uid.'">'.htmlspecialchars($display_text).'</option>';
                }
            ?>
        </select>
    </div>

    <!-- Автомобиль -->
    <div class="cw-field">
        <div class="cw-field-label"><i>🚗</i> Автомобиль</div>
        <select id="select_car" class="cw-modal-select">
            <option value="">-- Сначала выберите клиента --</option>
        </select>
    </div>

    <!-- Мастер -->
    <div class="cw-field">
        <div class="cw-field-label"><i>👨‍🔧</i> Мастер</div>
        <select id="select_worker" class="cw-modal-select">
            <option value="">-- Не выбран --</option>
            <?php foreach ($workers as $worker): ?>
                <option value="<?php echo $worker['id']; ?>"><?php echo htmlspecialchars($worker['fio']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Магазин -->
    <div class="cw-field">
        <div class="cw-field-label"><i>🏪</i> Магазин</div>
        <select id="select_shop" class="cw-modal-select">
            <?php
                $offices_query = $db_link->prepare('SELECT `id`, `caption` FROM `shop_offices` ORDER BY `id`;');
                $offices_query->execute();
                while($office = $offices_query->fetch()) {
                    echo '<option value="'.$office['id'].'">'.htmlspecialchars($office['caption']).'</option>';
                }
            ?>
        </select>
    </div>

    <!-- Способ получения -->
    <div class="cw-field">
        <div class="cw-field-label"><i>📦</i> Способ получения</div>
        <select id="select_delivery" class="cw-modal-select">
            <?php
                $obtaining_query = $db_link->prepare('SELECT `id`, `caption` FROM `shop_obtaining_modes` WHERE `available` = 1 ORDER BY `id`;');
                $obtaining_query->execute();
                while($obtaining = $obtaining_query->fetch()) {
                    echo '<option value="'.$obtaining['id'].'">'.htmlspecialchars($obtaining['caption']).'</option>';
                }
            ?>
        </select>
    </div>

    <!-- Услуги (мультиселект) -->
    <div class="cw-field">
        <div class="cw-field-label"><i>🛠️</i> Услуги</div>
        <select id="select_services" multiple="multiple" style="width: 100%;">
            <!-- Загружаются через AJAX при открытии модалки -->
        </select>
    </div>

    <!-- Комментарий -->
    <div class="cw-field">
        <div class="cw-field-label"><i>💬</i> Комментарий</div>
        <textarea id="modal_order_comment" rows="3" placeholder="Дополнительная информация..."></textarea>
    </div>

    <button class="cw-submit-btn" id="confirm_order-btn" onclick="confirm_order();">✅ Оформить запись</button>
</div>

<script>
(function() {
    // Инициализация Select2 с повторными попытками, если библиотека ещё не загружена
    function initSelect2(retries) {
        retries = retries || 0;
        if (typeof jQuery.fn.select2 !== 'function') {
            if (retries < 5) {
                setTimeout(function() { initSelect2(retries + 1); }, 300);
            } else {
                console.warn('Select2 не загрузился после 5 попыток');
            }
            return;
        }

        var $client = jQuery('#select_client');
        if ($client.data('select2')) $client.select2('destroy');
        $client.select2({
            placeholder: "Поиск клиента по имени, телефону, email или ID...",
            allowClear: true,
            width: '100%',
            dropdownParent: jQuery('#modal_create_order .modal-body')
        });

        var $services = jQuery('#select_services');
        if ($services.data('select2')) $services.select2('destroy');
        $services.select2({
            placeholder: "Выберите услуги...",
            allowClear: true,
            width: '100%',
            dropdownParent: jQuery('#modal_create_order .modal-body'),
            closeOnSelect: false
        });
    }

    // Загрузка списка услуг
	function loadServices() {
		var treeId = document.getElementById('modal_service_tree_id').value;
		if (!treeId) return;
		var $services = jQuery('#select_services');
		// Если уже есть опции (кроме первого пустого), не перезагружаем
		if ($services.find('option').length > 1) return;

		jQuery.ajax({
			type: "GET",
			url: "/content/carservice/ajax_get_services.php",
			data: { tree_id: treeId },
			dataType: "json",
			success: function(data) {
				if (data && data.services && data.services.length) {
					var html = '';
					data.services.forEach(function(service) {
						// Отображаем только название услуги
						html += '<option value="' + service.id + '">' + service.caption + '</option>';
					});
					$services.html(html);
					$services.trigger('change');
				}
			},
			error: function() {
				console.error('Ошибка загрузки услуг');
			}
		});
	}

    // Закрытие Select2 при закрытии модалки
    jQuery('#modal_create_order').on('hidden.bs.modal', function() {
        if (jQuery('#select_client').data('select2')) {
            jQuery('#select_client').select2('close');
        }
        if (jQuery('#select_services').data('select2')) {
            jQuery('#select_services').select2('close');
        }
    });

    // При открытии модалки - инициализация Select2 и загрузка услуг
    jQuery('#modal_create_order').on('shown.bs.modal', function() {
        initSelect2();
        loadServices();
    });

    // Обработчик изменения клиента
    jQuery('#select_client').on('change', function() {
        var userId = jQuery(this).val();
        if (userId) {
            jQuery.ajax({
                type: "POST",
                url: "/<?php echo $DP_Config->backend_dir; ?>/content/car_service/ajax_get_user_cars.php",
                data: { user_id: userId },
                dataType: "json",
                success: function(cars) {
                    var carSelect = document.getElementById('select_car');
                    carSelect.innerHTML = '<option value="">-- Выберите автомобиль --</option>';
                    if (cars.length) {
                        cars.forEach(function(car) {
                            var option = document.createElement('option');
                            option.value = car.id;
                            option.textContent = car.caption + ' (' + car.marka + ' ' + car.model + ')';
                            carSelect.appendChild(option);
                        });
                    } else {
                        carSelect.innerHTML = '<option value="">Нет автомобилей</option>';
                    }
                }
            });
        } else {
            document.getElementById('select_car').innerHTML = '<option value="">-- Сначала выберите клиента --</option>';
        }
    });
})();

function confirm_order() {
    var btn = document.getElementById('confirm_order-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    btn.innerHTML = '⏳ Оформление...';

    var date = document.getElementById("modal_record_date").value;
    var time = document.getElementById("modal_record_time").value;
    var post_id = document.getElementById("modal_record_post_id").value;
    var service_id = document.getElementById("modal_record_service_id").value;
    var user_id = jQuery('#select_client').val();
    var car_id = document.getElementById("select_car").value;
    var worker_id = document.getElementById("select_worker").value;
    var shop_id = document.getElementById("select_shop").value;
    var delivery_mode = document.getElementById("select_delivery").value;
    var order_comment = document.getElementById("modal_order_comment").value;

    // Получаем выбранные услуги
    var services = jQuery('#select_services').val() || [];

    if (!user_id) { alert("Выберите клиента"); resetButton(); return; }
    if (!car_id) { alert("Выберите автомобиль"); resetButton(); return; }
    if (!shop_id) { alert("Выберите магазин"); resetButton(); return; }
    if (!delivery_mode) { alert("Выберите способ получения"); resetButton(); return; }
    if (!date || !time || !post_id || !service_id) { alert("Не хватает данных о записи"); resetButton(); return; }

    var record_datetime = date + ' ' + time;
    var how_get = { mode: parseInt(delivery_mode), office_id: parseInt(shop_id) };
    var cookie_date = new Date(new Date().getTime() + 15552000 * 1000);
    document.cookie = "how_get=" + JSON.stringify(how_get) + "; path=/; expires=" + cookie_date.toUTCString();

    jQuery.ajax({
        type: "POST",
        url: "/<?php echo $DP_Config->backend_dir; ?>/content/car_service/ajax_create_order_record.php",
        dataType: "json",
        data: {
            user_id: user_id,
            order_message: order_comment,
            csrf_guard_key: "<?php echo $user_session["csrf_guard_key"]; ?>",
            service_record_date: record_datetime,
            service_post_id: post_id,
            service_sto_id: service_id,
            service_car_id: car_id,
            service_worker_id: worker_id,
            shop_id: shop_id,
            delivery_mode: delivery_mode,
            services: services
        },
        success: function(answer) {
            if (answer.status == true) {
                jQuery("#modal_create_order").modal('hide');
                if (typeof window.parent.cwLoadData === 'function') {
                    window.parent.cwLoadData();
                } else if (typeof cwLoadData === 'function') {
                    cwLoadData();
                } else {
                    location.reload();
                }
            } else {
                alert(answer.message);
                resetButton();
            }
        },
        error: function() {
            alert("Ошибка при оформлении");
            resetButton();
        }
    });
    
    function resetButton() {
        btn.disabled = false;
        btn.innerHTML = '✅ Оформить запись';
    }
}
</script>