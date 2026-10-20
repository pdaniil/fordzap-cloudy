<?php
/**
 * Адаптированный виджет добавления товаров для модалки записи
 * Используется только когда есть order_id, office_id, user_id, group_id
 * Переменные: $orderId, $officeId, $userId, $groupId должны быть определены
 */

if (!isset($orderId) || !isset($officeId) || !isset($userId) || !isset($groupId)) {
    echo '<p style="color: #EF4444;">Ошибка: недостаточно данных для добавления товаров.</p>';
    return;
}

// Получаем список складов, привязанных к данному офису (фильтрация по shop_offices_storages_map)
$storages_list = array();
$storage_query = $db_link->prepare("
    SELECT s.id, s.name 
    FROM shop_storages s 
    INNER JOIN shop_offices_storages_map m ON s.id = m.storage_id 
    WHERE m.office_id = ? AND s.interface_type = 1 
    GROUP BY s.id 
    ORDER BY s.name
");
$storage_query->execute([$officeId]);
while ($row = $storage_query->fetch()) {
    $storages_list[$row['id']] = $row['name'];
}
?>

<div class="cw-products-widget">
    <!-- Верхняя панель: табы -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item active">
            <a class="nav-link active" href="#tab_catalog" data-toggle="tab">Из каталога</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#tab_custom" data-toggle="tab">Произвольно</a>
        </li>
    </ul>

    <div class="tab-content" style="margin-top:15px;">
        <!-- Вкладка "Из каталога" -->
        <div class="tab-pane active" id="tab_catalog">
            <div class="row">
                <div class="col-xs-12 col-md-8 col-lg-6">
                    <h5>Поиск товара:</h5>
                    <div style="display: flex; grid-gap: 10px; justify-content: space-between; align-items: center;">
                        <input id="search_catalog" type="text" class="form-control" onInput="window.debouncedCatSearch();" placeholder="Штрих-код или артикул" />
						<button class="btn btn-primary" type="button" onclick="window.cat_search();">Поиск</button>
                        <a href="javascript:void(0);" onClick="window.openScanner();"
						   style="border-radius: 0 3px 3px 0; text-align: center; font-size: 24px; background: none; border: none;"
						   class="btn btn-as btn-secondary" title="Сканировать">
							📸
						</a>
                    </div>
                </div>
            </div>
            <div id="product_div" class="col-xs-12" style="margin-top:15px;"></div>
            <div id="products_div" class="col-xs-12"></div>
        </div>

        <!-- Вкладка "Произвольно" -->
        <div class="tab-pane" id="tab_custom">
            <div class="item-form">

                <h4 class="item-form-title">Данные позиции</h4>

                <div class="item-form-section-title">Идентификация товара</div>
                <div class="item-form-grid item-form-grid--3">
                    <div class="form-field">
                        <label for="art_item_inp">Артикул</label>
                        <input id="art_item_inp" type="text" class="form-control cw-modal-input" />
                    </div>
                    <div class="form-field">
                        <label for="man_item_inp">Производитель</label>
                        <input id="man_item_inp" type="text" class="form-control cw-modal-input" />
                    </div>
                    <div class="form-field">
                        <label for="name_item_inp">Наименование</label>
                        <input id="name_item_inp" type="text" class="form-control cw-modal-input" />
                    </div>
                </div>

                <div class="item-form-section-title">Склад и цены</div>
                <div class="item-form-grid item-form-grid--4">
                    <div class="form-field">
                        <label for="inp_storage_id">Склад</label>
                        <select id="inp_storage_id" class="form-control cw-modal-select">
                            <?php foreach ($storages_list as $k => $v): ?>
                                <option value="<?=(int)$k?>"><?=htmlspecialchars($v, ENT_QUOTES, 'UTF-8')?> (<?=(int)$k?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="inp_price">Цена</label>
                        <input class="form-control cw-modal-input" type="text" id="inp_price" value="0.00" />
                    </div>
                    <div class="form-field">
                        <label for="inp_price_zakup">Закупочная цена</label>
                        <input class="form-control cw-modal-input" type="text" id="inp_price_zakup" value="0.00" />
                    </div>
                    <div class="form-field">
                        <label for="inp_count_need">Количество</label>
                        <input class="form-control cw-modal-input" type="text" id="inp_count_need" value="1" />
                    </div>
                </div>

                <div class="item-form-section-title">Сроки поставки</div>
                <div class="item-form-grid item-form-grid--2">
                    <div class="form-field">
                        <label for="time_to_exe_item_inp">Ожидаемый срок, дней</label>
                        <input class="form-control cw-modal-input" id="time_to_exe_item_inp" type="text" value="1" />
                    </div>
                    <div class="form-field">
                        <label for="time_to_exe_guaranteed_item_inp">Гарантированный срок, дней</label>
                        <input class="form-control cw-modal-input" id="time_to_exe_guaranteed_item_inp" type="text" value="5" />
                    </div>
                </div>

                <div class="item-form-actions">
                    <button onclick="window.save_action();" class="btn btn-primary" type="button">Добавить</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Скрытая форма для отправки данных -->
    <form id="save_form" style="display:none;">
        <input type="hidden" name="save_action" value="insert" />
        <input type="hidden" id="order_id" name="order_id" value="<?=$orderId?>" />
        <input type="hidden" id="office_id" name="office_id" value="<?=$officeId?>" />
        <input type="hidden" id="user_id" name="user_id" value="<?=$userId?>" />
        <input type="hidden" id="group_id" name="group_id" value="<?=$groupId?>" />
        <input type="hidden" id="id_storages_data" name="id_storages_data" value="" />
        <input type="hidden" id="count_need" name="count_need" value="" />
        <input type="hidden" id="price" name="price" value="" />
        <input type="hidden" id="price_zakup" name="price_zakup" value="" />
        <input type="hidden" id="storage_id" name="storage_id" value="" />
        <input type="hidden" id="art" name="art" value="" />
        <input type="hidden" id="man" name="man" value="" />
        <input type="hidden" id="name" name="name" value="" />
        <input type="hidden" id="item_product_type" name="item_product_type" value="2" />
        <input type="hidden" id="time_to_exe" name="time_to_exe" value="" />
        <input type="hidden" id="time_to_exe_guaranteed" name="time_to_exe_guaranteed" value="" />
        <input type="hidden" name="ajax" value="1" />
    </form>
</div>

<!-- Модальное окно проценки -->
<div class="modal fade" id="modal_part_search_valuation" role="dialog" style="z-index: 1050;">
    <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content" style="height: 100%; border-radius: 0;">
            <div class="modal-header" style="border-bottom: 1px solid #ddd; background: #f5f5f5; padding: 10px 15px;">
                <h3 class="modal-title"><b>Проценка</b></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 0; height: calc(100% - 56px);">
                <iframe id="iframe_modal_part_search" style="border: none; width: 100%; height: 100%;" loading="lazy" src="about:blank"></iframe>
            </div>
        </div>
    </div>
</div>

<style>
    #modal_part_search_valuation .modal-fullscreen {
        width: 100vw;
        height: 100vh;
        margin: 0;
        max-width: none;
    }
    #modal_part_search_valuation .modal-content {
        border-radius: 0;
        height: 100%;
    }
    @media (max-width: 767px) {
        #modal_part_search_valuation .modal-fullscreen {
            width: 100vw;
            height: 100vh;
            margin: 0;
        }
        #modal_part_search_valuation .modal-body {
            height: calc(100% - 50px) !important;
        }
    }
    .cw-products-widget .table td, .cw-products-widget .table th {
        vertical-align: middle;
    }
    .cw-products-widget .table img {
        max-width: 50px;
        max-height: 50px;
        border-radius: 4px;
    }

    /* ================== Item form (grid + dark) ================== */
    .cw-products-widget .item-form{
        --it-text:#e8ecf4;
        --it-muted:#8b93a7;
        --it-label:#9aa3b8;
        --it-bg:#1f2233;
        --it-bg-hover:#252a3d;
        --it-border:#343a52;
        --it-border-hover:#434a68;
        --it-focus:#6366f1;
        --it-focus-ring:rgba(99,102,241,.28);
        --it-accent-from:#6366f1;
        --it-accent-to:#3b82f6;
        font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
        color:var(--it-text);
    }
    .cw-products-widget .item-form-title{
        margin:0 0 18px;
        font-size:16px;
        font-weight:600;
        color:var(--it-text);
        display:flex;
        align-items:center;
        gap:10px;
        line-height:1.2;
    }
    .cw-products-widget .item-form-title::before{
        content:"";
        display:inline-block;
        width:4px;
        height:18px;
        background:linear-gradient(180deg,var(--it-accent-from),var(--it-accent-to));
        border-radius:2px;
    }
    .cw-products-widget .item-form-section-title{
        margin:18px 0 10px;
        font-size:12px;
        font-weight:600;
        text-transform:uppercase;
        letter-spacing:.6px;
        color:var(--it-muted);
    }
    .cw-products-widget .item-form-section-title:first-of-type{ margin-top:0; }

    .cw-products-widget .item-form-grid{
        display:grid;
        gap:14px 16px;
        margin-bottom:16px;
    }
    .cw-products-widget .item-form-grid:last-child{ margin-bottom:0; }
    .cw-products-widget .item-form-grid--2{ grid-template-columns:repeat(2,minmax(0,1fr)); }
    .cw-products-widget .item-form-grid--3{ grid-template-columns:repeat(3,minmax(0,1fr)); }
    .cw-products-widget .item-form-grid--4{ grid-template-columns:repeat(4,minmax(0,1fr)); }

    .cw-products-widget .item-form .form-field{
        display:flex;
        flex-direction:column;
        gap:6px;
        min-width:0;
    }
    .cw-products-widget .item-form .form-field label{
        display:block;
        margin:0;
        font-size:11px;
        font-weight:600;
        color:var(--it-label);
        text-transform:uppercase;
        letter-spacing:.4px;
        line-height:1.2;
    }

    /* Общий стиль тёмных контролов (cw-modal-input / cw-modal-select) */
    .cw-products-widget .cw-modal-input,
    .cw-products-widget .cw-modal-select{
        width:100%;
        height:42px;
        padding:9px 14px;
        font-size:14px;
        line-height:1.4;
        color:var(--it-text);
        background:var(--it-bg);
        border:1px solid var(--it-border);
        border-radius:10px;
        box-shadow:inset 0 1px 0 rgba(255,255,255,.03);
        transition:border-color .15s ease,box-shadow .15s ease,background .15s ease;
        box-sizing:border-box;
        outline:none;
        -webkit-appearance:none;
        -moz-appearance:none;
        appearance:none;
    }
    .cw-products-widget .cw-modal-input::placeholder{
        color:var(--it-muted);
        opacity:.9;
    }
    .cw-products-widget .cw-modal-input:hover,
    .cw-products-widget .cw-modal-select:hover{
        background:var(--it-bg-hover);
        border-color:var(--it-border-hover);
    }
    .cw-products-widget .cw-modal-input:focus,
    .cw-products-widget .cw-modal-select:focus{
        outline:none;
        background:var(--it-bg-hover);
        border-color:var(--it-focus);
        box-shadow:0 0 0 3px var(--it-focus-ring);
    }
    /* Стрелка у селекта */
    .cw-products-widget .cw-modal-select{
        background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238b93a7' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat:no-repeat;
        background-position:right 14px center;
        padding-right:38px;
    }
    .cw-products-widget .cw-modal-select option{
        background:#1f2233;
        color:#e8ecf4;
    }

    /* Кнопка */
    .cw-products-widget .item-form-actions{
        margin-top:18px;
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    }
    .cw-products-widget .item-form-actions .btn{
        height:42px;
        padding:0 22px;
        font-size:14px;
        font-weight:600;
        border-radius:10px;
        border:none;
        color:#fff;
        background:linear-gradient(180deg,var(--it-accent-from),var(--it-accent-to));
        box-shadow:0 4px 12px rgba(59,130,246,.35);
        transition:transform .12s ease,box-shadow .12s ease,filter .12s ease;
    }
    .cw-products-widget .item-form-actions .btn:hover{
        transform:translateY(-1px);
        box-shadow:0 6px 16px rgba(59,130,246,.45);
        filter:brightness(1.05);
    }
    .cw-products-widget .item-form-actions .btn:active{
        transform:translateY(0);
        filter:brightness(.97);
    }

    @media (max-width:991px){
        .cw-products-widget .item-form-grid--3,
        .cw-products-widget .item-form-grid--4{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
    }
    @media (max-width:575px){
        .cw-products-widget .item-form-grid--2,
        .cw-products-widget .item-form-grid--3,
        .cw-products-widget .item-form-grid--4{
            grid-template-columns:minmax(0,1fr);
        }
        .cw-products-widget .cw-modal-input,
        .cw-products-widget .cw-modal-select{
            height:46px;
            font-size:15px;
        }
        .cw-products-widget .item-form-actions .btn{
            width:100%;
        }
    }
</style>

<script>
    var id_storages_data = 0;
    var exist_storages_data = 0;
    var office_id = <?=$officeId?>;
    var group_id = <?=$groupId?>;
    var order_id = <?=$orderId?>;
    var backend_dir = "<?=$DP_Config->backend_dir?>";
    // ---- Дебаунсер для поиска ----
    function debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
	
	window.openScanner = function () {
		document.getElementById('search_catalog').value = "";

		// Показываем модальное окно
		document.getElementById('overlay').style.display = 'block';
		document.getElementById('scanner-container').style.display = 'block';

		// Инициализация html5-qrcode
		html5QrCode = new Html5Qrcode("reader");

		// Опции
		const config = {
			fps: 10,
			qrbox: { width: 250, height: 250 },
			experimentalFeatures: {
				useBarCodeDetectorIfSupported: true
			},
			videoConstraints: {
				width: 300,
				height: 300,
				facingMode: "environment",
				focusMode: "continuous"
			},
		};

		// Запуск сканера
		html5QrCode.start(
			{ facingMode: "environment" },
			config,
			(decodedText) => {
				console.log(`Сканированный текст: ${decodedText}`);
				document.getElementById('search_catalog').value = decodedText;
				window.cat_search();
				closeScanner();
			},
			(errorMessage) => {
				console.warn(`Ошибка сканирования: ${errorMessage}`);
			}
		).catch((err) => {
			console.error(`Ошибка запуска камеры: ${err}`);
		});
	}

	window.closeScanner = function () {
		if (html5QrCode) {
			html5QrCode.stop().then(() => {
				document.getElementById('overlay').style.display = 'none';
				document.getElementById('scanner-container').style.display = 'none';
			}).catch((err) => {
				console.error(`Ошибка остановки камеры: ${err}`);
			});
		} else {
			document.getElementById('overlay').style.display = 'none';
			document.getElementById('scanner-container').style.display = 'none';
		}
	}
	
    // ---- Поиск товара (из каталога) ----
    window.cat_search = function() {
        var value = document.getElementById("search_catalog").value;
        if (value == '') {
            document.getElementById("products_div").innerHTML = '';
            return;
        }

        document.getElementById("products_div").innerHTML = '<img src="/content/files/images/ajax-loader-transparent.gif" style="height:31px; margin-top:15px;">';

        var request_object = {
            action: 'search',
            value: encodeURIComponent(value),
            office_id: office_id,
            group_id: group_id
        };

        jQuery.ajax({
            type: "POST",
            url: "/content/carservice/admin_console/orders_items_add.php",
            data: { request_object: JSON.stringify(request_object) },
            dataType: "json",
            success: function(answer) {
                var html = '';
                if (answer.status && answer.products_list) {
                    var products = answer.products_list;
                    if (products.length) {
                        html += '<h5>Найденные товары:</h5>';
                        html += '<div class="table-responsive">';
                        html += '<table class="table table-striped table-condensed">';
                        html += '<thead><tr>';
                        html += '<th>Фото</th>';
                        html += '<th>Наименование</th>';
                        html += '<th>Склад</th>';
                        html += '<th>Наличие</th>';
                        html += '<th>Цена</th>';
                        html += '<th style="width:90px;">Действие</th>';
                        html += '</tr></thead><tbody>';

                        for (var i = 0; i < products.length; i++) {
                            var p = products[i];
                            var fullName = (p.manufacturer ? p.manufacturer + ' - ' : '') +
                                           (p.article ? p.article + ' - ' : '') +
                                           p.caption;
                            var imageHtml = (p.image && p.image != '') ? '<img src="' + p.image + '">' : '—';
                            html += '<tr>';
                            html += '<td data-label="Фото">' + imageHtml + '</td>';
                            html += '<td data-label="Наименование">' + escapeHtml(fullName) + '</td>';
                            html += '<td data-label="Склад">' + escapeHtml(p.storage_name) + '</td>';
                            html += '<td data-label="Наличие">' + p.exist + '</td>';
                            html += '<td data-label="Цена">' + Number(p.customer_price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</td>';
                            html += '<td data-label="Действие"><a class="btn btn-sm btn-primary" onclick="window.add_product(' + p.id + ');">Выбрать</a></td>';
                            html += '</tr>';
                        }

                        html += '</tbody></table></div>';
                    } else {
                        html = 'Товары не найдены';
                    }
                } else {
                    html = 'Ошибка поиска';
                }
                document.getElementById("products_div").innerHTML = html;
            },
            error: function() {
                document.getElementById("products_div").innerHTML = 'Произошла ошибка при поиске';
            }
        });
    };

    window.debouncedCatSearch = debounce(window.cat_search, 300);

    // ---- Выбор товара из результатов поиска ----
  window.add_product = function(id) {
    var request_object = {
        action: 'get_product',
        id: encodeURIComponent(id),
        office_id: office_id,
        group_id: group_id
    };

    jQuery.ajax({
        type: "POST",
        url: "/content/carservice/admin_console/orders_items_add.php",
        data: { request_object: JSON.stringify(request_object) },
        dataType: "json",
        success: function(answer) {
            if (answer.status && answer.product) {
                var p = answer.product;
                id_storages_data = p.id;
                exist_storages_data = p.exist;

                var imageHtml = (p.image && p.image != '') ? '<img src="' + p.image + '">' : '—';

                var html = '<h5>Выбранный товар:</h5>';
                html += '<table class="table table-condensed">';
                html += '<tr><td>' + imageHtml + '</td><td>' + escapeHtml(p.manufacturer + ' - ' + p.article + ' - ' + p.caption) + '</td>';
                html += '<td>' + escapeHtml(p.storage_name) + '</td>';
                html += '<td>' + p.exist + '</td>';
                html += '<td>' + Number(p.customer_price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</td></tr>';
                html += '</table>';
                html += '<h5>Требуемое количество:</h5>';
                html += '<div class="row"><div class="col-xs-6 col-sm-4"><input id="exist_inp" class="form-control cw-modal-input" type="number" min="1" max="' + p.exist + '" value="1" /></div>';
                html += '<div class="col-xs-6 col-sm-4"><a href="javascript:void(0);" onClick="window.save_action();" class="btn btn-primary">Добавить</a></div></div>';
                document.getElementById("product_div").innerHTML = html;

                // === ПРОКРУТКА К БЛОКУ ===
                var target = document.getElementById('product_div');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                alert('Товар не найден');
            }
        }
    });
};

    // ---- Основная функция сохранения (добавления) ----
    window.save_action = function() {
        var widget = document.querySelector('.cw-products-widget');
        if (!widget) {
            alert('Ошибка: виджет не найден');
            return;
        }
        var tab_active = widget.querySelector('.tab-pane.active');
        var product_type = 2; // по умолчанию произвольный

        if (tab_active && tab_active.id === 'tab_catalog') {
            product_type = 1;
        }

        var form = document.getElementById('save_form');
        var formData = new FormData(form);
        formData.append('item_product_type', product_type);
        formData.append('ajax', '1');

        if (product_type === 1) {
            var id = id_storages_data;
            var count = document.getElementById('exist_inp').value;
            if (id == 0 || !count || count <= 0) {
                alert('Выберите товар и укажите количество');
                return;
            }
            if (parseInt(count) > parseInt(exist_storages_data)) {
                alert('Недостаточно товара на складе');
                return;
            }
            formData.set('id_storages_data', id);
            formData.set('count_need', count);
        } else {
            var art = document.getElementById('art_item_inp').value.trim();
            var man = document.getElementById('man_item_inp').value.trim();
            var name = document.getElementById('name_item_inp').value.trim();
            var storage_id = document.getElementById('inp_storage_id').value;
            var price = document.getElementById('inp_price').value;
            var price_zakup = document.getElementById('inp_price_zakup').value;
            var count_need = document.getElementById('inp_count_need').value;
            var time_to_exe = document.getElementById('time_to_exe_item_inp').value;
            var time_to_exe_guaranteed = document.getElementById('time_to_exe_guaranteed_item_inp').value;

            if (!name || !price || count_need <= 0) {
                alert('Заполните обязательные поля (наименование, цена, количество)');
                return;
            }
            formData.set('art', art);
            formData.set('man', man);
            formData.set('name', name);
            formData.set('storage_id', storage_id);
            formData.set('price', price);
            formData.set('price_zakup', price_zakup);
            formData.set('count_need', count_need);
            formData.set('time_to_exe', time_to_exe);
            formData.set('time_to_exe_guaranteed', time_to_exe_guaranteed);
        }

        jQuery.ajax({
            type: "POST",
            url: "/content/carservice/admin_console/orders_items_add.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.status) {   
                    window.loadOrderProducts(order_id);
                    clearProductForm();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Произошла ошибка при отправке запроса');
                console.error(xhr.responseText);
            }
        });
    };
    
    // ---- Загрузка и отображение товаров заказа ----
    window.loadOrderProducts = function(orderId) {
        if (!orderId) return;
        jQuery.ajax({
            type: "GET",
            url: "/content/carservice/ajax_get_order_products.php",
            data: { order_id: orderId },
            dataType: "json",
            success: function(response) {
                if (response.status) {
                    window.renderProductsTable(response.items, response.total);
                } else {
                    console.error('Ошибка загрузки товаров:', response.message);
                }
            },
            error: function() {
                console.error('Ошибка AJAX запроса');
            }
        });
    };

    window.renderProductsTable = function(items, total) {
        var container = document.getElementById('products_table_container');
        if (!container) return;

        if (!items || items.length === 0) {
            container.innerHTML = '<p style="color: #A0A0B0;">В заказе пока нет товаров.</p>';
            return;
        }

        var html = `
            <table id="products_table" class="table table-dark table-striped" style="color: #FFFFFF; background: transparent;">
                <thead>
                    <tr>
                        <th>Наименование</th>
                        <th>Производитель</th>
                        <th>Артикул</th>
                        <th style="width:80px;">Кол-во</th>
                        <th style="width:100px;">Цена</th>
                        <th style="width:100px;">Сумма</th>
                    </tr>
                </thead>
                <tbody>
        `;
        items.forEach(function(item) {
            var sum = item.sum || (item.price * item.count_need);
            html += `
                <tr>
                    <td>${escapeHtml(item.t2_name || '—')}</td>
                    <td>${escapeHtml(item.t2_manufacturer || '—')}</td>
                    <td>${escapeHtml(item.t2_article || '—')}</td>
                    <td data-label="Кол-во">${parseInt(item.count_need)}</td>
                    <td data-label="Цена">${Number(item.price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} ₽</td>
                    <td data-label="Сумма">${Number(sum).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} ₽</td>
                </tr>
            `;
        });
        html += `
                </tbody>
            </table>
            <div style="margin-top: 10px; text-align: right; color: #A0A0B0; font-size: 14px;">
                Итого по заказу: <span style="color: #FFFFFF; font-weight: 600;">${Number(total).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} ₽</span>
            </div>
        `;
        container.innerHTML = html;
    };

    // ---- Экранирование HTML ----
    function escapeHtml(unsafe) {
        if (!unsafe) return '—';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    // ---- Очистка формы после добавления ----
    function clearProductForm() {
        document.getElementById('search_catalog').value = '';
        document.getElementById('product_div').innerHTML = '';
        document.getElementById('products_div').innerHTML = '';
        document.getElementById('art_item_inp').value = '';
        document.getElementById('man_item_inp').value = '';
        document.getElementById('name_item_inp').value = '';
        document.getElementById('inp_price').value = '0.00';
        document.getElementById('inp_price_zakup').value = '0.00';
        document.getElementById('inp_count_need').value = '1';
        document.getElementById('time_to_exe_item_inp').value = '1';
        document.getElementById('time_to_exe_guaranteed_item_inp').value = '5';
        id_storages_data = 0;
        exist_storages_data = 0;
        document.getElementById('id_storages_data').value = '';
        document.getElementById('count_need').value = '';
        document.getElementById('price').value = '';
        document.getElementById('price_zakup').value = '';
        document.getElementById('storage_id').value = '';
        document.getElementById('art').value = '';
        document.getElementById('man').value = '';
        document.getElementById('name').value = '';
        document.getElementById('time_to_exe').value = '';
        document.getElementById('time_to_exe_guaranteed').value = '';
    }

    // ---- Обработка переключения вкладок ----
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        if (target === '#tab_catalog') {
            // Можно очистить результаты при переключении
        } else if (target === '#tab_custom') {
            // ...
        }
    });

    // ---- Открытие модалки проценки и загрузка iframe с передачей record_id ----
    $('#modal_part_search_valuation').on('show.bs.modal', function() {
        var iframe = document.getElementById('iframe_modal_part_search');
        if (iframe && iframe.src === 'about:blank') {
            var parentDoc = window.parent.document;
            var recordIdInput = parentDoc.getElementById('modal_record_id');
            var recordId = recordIdInput ? recordIdInput.value : '';

            var chpu = <?= json_encode($DP_Config->chpu_search_on) ?>;
            var order_id = <?= $orderId ?>;
            var src;
            if (chpu) {
                src = '/parts/brands/XPLGSU1FQWVVQNFSYSJRUUI8LEYUF?cp=1&cp_order_id=' + order_id;
            } else {
                src = '/shop/part_search?article=XPLGSU1FQWVVQNFSYSJRUUI8LEYUF&cp=1&cp_order_id=' + order_id;
            }
            if (recordId) {
                src += '&record_id=' + encodeURIComponent(recordId);
            }
            iframe.src = src;
        }
    });

    $('#modal_part_search_valuation').on('hidden.bs.modal', function() {
        // опционально
    });
</script>