<?php
/**
 * Виджет добавления товаров в заказ (из каталога и произвольно)
 * Использование: 
 *   include 'add_order_items_widget.php';
 *   // предварительно должны быть определены:
 *   // $order_id, $office_id, $group_id, $user_id
 */

// Безопасность
defined('_ASTEXE_') or die('No access');

// Получаем список складов (для произвольного добавления)
$storages_list = array();
$storage_query = $db_link->prepare("SELECT `id`, `name` FROM `shop_storages` ORDER BY `name`;");
$storage_query->execute();
while ($row = $storage_query->fetch()) {
    $storages_list[$row['id']] = $row['name'];
}
?>

<div class="col-md-12">
    <ul class="nav nav-tabs">
        <li class="active"><a href="#tab_catalog" data-toggle="tab">Из каталога</a></li>
        <li><a href="#tab_custom" data-toggle="tab">Произвольно</a></li>
    </ul>

    <div class="tab-content" style="margin-top:15px;">
        <!-- Вкладка "Из каталога" -->
        <div class="tab-pane active" id="tab_catalog">
            <div class="row">
                <div class="col-xs-12 col-md-8 col-lg-6">
                    <h5>Поиск товара:</h5>
                    <div class="input-group">
                        <input id="search_catalog" type="text" class="form-control" onInput="debouncedCatSearch();" placeholder="Штрих-код или артикул" />
                        <span class="input-group-btn">
                            <button class="btn btn-primary" type="button" onclick="cat_search();">Поиск</button>
                        </span>
                    </div>
                </div>
            </div>

            <div id="product_div" class="col-xs-12" style="margin-top:15px;"></div>
            <div id="products_div" class="col-xs-12"></div>
        </div>

        <!-- Вкладка "Произвольно" -->
        <div class="tab-pane" id="tab_custom">
            <div class="row">
                <div class="col-lg-12">
                    <h5>Данные позиции:</h5>
                    <div class="form-group">
                        <label>Артикул:</label>
                        <input id="art_item_inp" type="text" class="form-control" style="max-width:500px;" />
                    </div>
                    <div class="form-group">
                        <label>Производитель:</label>
                        <input id="man_item_inp" type="text" class="form-control" style="max-width:500px;" />
                    </div>
                    <div class="form-group">
                        <label>Наименование:</label>
                        <input id="name_item_inp" type="text" class="form-control" style="max-width:500px;" />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <table class="table table-condensed table-striped table-bordered" style="max-width:800px;">
                        <thead>
                            <tr>
                                <th>Склад</th>
                                <th>Цена</th>
                                <th>Закупочная цена</th>
                                <th>Количество</th>
                                <th>Срок (дней)</th>
                                <th>Гарант. срок</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select id="inp_storage_id" class="form-control">
                                        <?php foreach ($storages_list as $k => $v): ?>
                                            <option value="<?=$k?>"><?=$v?> (<?=$k?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input class="form-control" type="text" id="inp_price" value="0.00" /></td>
                                <td><input class="form-control" type="text" id="inp_price_zakup" value="0.00" /></td>
                                <td><input class="form-control" type="text" id="inp_count_need" value="1" /></td>
                                <td><input class="form-control" type="text" id="time_to_exe_item_inp" value="1" /></td>
                                <td><input class="form-control" type="text" id="time_to_exe_guaranteed_item_inp" value="5" /></td>
                            </tr>
                        </tbody>
                    </table>
                    <button onclick="save_action();" class="btn btn-primary">Добавить</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Скрытая форма для отправки данных (используется при AJAX) -->
<form id="save_form" style="display:none;">
    <input type="hidden" name="save_action" value="insert" />
    <input type="hidden" id="order_id" name="order_id" value="<?=$order_id?>" />
    <input type="hidden" id="office_id" name="office_id" value="<?=$office_id?>" />
    <input type="hidden" id="user_id" name="user_id" value="<?=$user_id?>" />
    <input type="hidden" id="group_id" name="group_id" value="<?=$group_id?>" />
    <!-- Поля для товара из каталога -->
    <input type="hidden" id="id_storages_data" name="id_storages_data" value="" />
    <input type="hidden" id="count_need" name="count_need" value="" />
    <!-- Поля для произвольного товара -->
    <input type="hidden" id="price" name="price" value="" />
    <input type="hidden" id="price_zakup" name="price_zakup" value="" />
    <input type="hidden" id="storage_id" name="storage_id" value="" />
    <input type="hidden" id="art" name="art" value="" />
    <input type="hidden" id="man" name="man" value="" />
    <input type="hidden" id="name" name="name" value="" />
    <input type="hidden" id="item_product_type" name="item_product_type" value="2" />
    <input type="hidden" id="time_to_exe" name="time_to_exe" value="" />
    <input type="hidden" id="time_to_exe_guaranteed" name="time_to_exe_guaranteed" value="" />
</form>

<script>
    // ---- Глобальные переменные ----
    var id_storages_data = 0;
    var exist_storages_data = 0;

    // ---- Дебаунсер для поиска ----
    function debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
    const debouncedCatSearch = debounce(cat_search, 300);

    // ---- Поиск товара (из каталога) ----
    function cat_search() {
        var value = document.getElementById("search_catalog").value;
        if (value == '') {
            document.getElementById("products_div").innerHTML = '';
            return;
        }

        document.getElementById("products_div").innerHTML = '<img src="/content/files/images/ajax-loader-transparent.gif" style="height:31px; margin-top:15px;">';

        var request_object = {
            action: 'search',
            value: encodeURIComponent(value),
            office_id: <?=$office_id?>,
            group_id: <?=$group_id?>
        };

        jQuery.ajax({
            type: "POST",
            url: "/<?=$DP_Config->backend_dir?>/content/shop/order_process/orders_items_add.php",
            data: { request_object: JSON.stringify(request_object) },
            dataType: "json",
            success: function(answer) {
                var html = '';
                if (answer.status && answer.products_list) {
                    var products = answer.products_list;
                    if (products.length) {
                        html += '<h5>Найденные товары:</h5><div><table class="table"><tr><th></th><th>Наименование</th><th>Категория</th><th>Склад</th><th>Наличие</th><th>Закуп</th><th>Цена</th><th></th></tr>';
                        for (var i = 0; i < products.length; i++) {
                            var p = products[i];
                            html += '<tr>';
                            html += '<td><a class="btn btn-sm btn-primary" onclick="add_product(' + p.id + ');">Выбрать</a></td>';
                            html += '<td>' + p.manufacturer + ' - ' + p.article + ' - ' + p.caption + '</td>';
                            html += '<td>' + p.category + '</td>';
                            html += '<td>' + p.storage_id + ' - ' + p.storage_name + '</td>';
                            html += '<td>' + p.exist + '</td>';
                            html += '<td>' + p.price + '</td>';
                            html += '<td>' + p.customer_price + '</td>';
                            html += '<td><a target="_blank" href="/<?=$DP_Config->backend_dir?>/shop/catalogue/products/product?category_id=' + p.category_id + '&product_id=' + p.product_id + '"><img src="/<?=$DP_Config->backend_dir?>/templates/bootstrap_admin/images/cargo.png" style="width:20px;"></a></td>';
                            html += '</tr>';
                        }
                        html += '</table></div>';
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
    }

    // ---- Выбор товара из результатов поиска ----
    function add_product(id) {
        var request_object = {
            action: 'get_product',
            id: encodeURIComponent(id),
            office_id: <?=$office_id?>,
            group_id: <?=$group_id?>
        };

        jQuery.ajax({
            type: "POST",
            url: "/<?=$DP_Config->backend_dir?>/content/shop/order_process/orders_items_add.php",
            data: { request_object: encodeURI(JSON.stringify(request_object)) },
            dataType: "json",
            success: function(answer) {
                if (answer.status && answer.product) {
                    var p = answer.product;
                    id_storages_data = p.id;
                    exist_storages_data = p.exist;

                    var html = '<h5>Выбранный товар:</h5><table class="table"><tr><th>Наименование</th><th>Категория</th><th>Склад</th><th>Наличие</th><th>Закуп</th><th>Цена</th></tr>';
                    html += '<tr><td>' + p.manufacturer + ' - ' + p.article + ' - ' + p.caption + '</td>';
                    html += '<td>' + p.category + '</td>';
                    html += '<td>' + p.storage_id + ' - ' + p.storage_name + '</td>';
                    html += '<td>' + p.exist + '</td>';
                    html += '<td>' + p.price + '</td>';
                    html += '<td>' + p.customer_price + '</td></tr></table>';
                    html += '<h5>Требуемое количество:</h5><div><input id="exist_inp" class="form-control" style="max-width:200px;" /><br><a href="javascript:void(0);" onClick="save_action();" class="btn btn-primary">Добавить</a></div>';
                    document.getElementById("product_div").innerHTML = html;
                } else {
                    alert('Товар не найден');
                }
            }
        });
    }

    // ---- Основная функция сохранения (добавления) ----
    function save_action() {
        var tab_active = document.querySelector('.tab-pane.active');
        var product_type = 2; // по умолчанию произвольный

        // Определяем активную вкладку
        if (tab_active.id === 'tab_catalog') {
            product_type = 1;
        }

        // Сбор данных из формы
        var data = new FormData(document.getElementById('save_form'));
        data.append('item_product_type', product_type);

        if (product_type === 1) {
            // Из каталога
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
            data.set('id_storages_data', id);
            data.set('count_need', count);
        } else {
            // Произвольный товар
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
            data.set('art', art);
            data.set('man', man);
            data.set('name', name);
            data.set('storage_id', storage_id);
            data.set('price', price);
            data.set('price_zakup', price_zakup);
            data.set('count_need', count_need);
            data.set('time_to_exe', time_to_exe);
            data.set('time_to_exe_guaranteed', time_to_exe_guaranteed);
        }

        // Отправка AJAX
        jQuery.ajax({
            type: "POST",
            url: "/<?=$DP_Config->backend_dir?>/content/shop/order_process/add_order_item.php",
            data: data,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.status) {
                    alert('Товар успешно добавлен в заказ');
                    // Очищаем форму или обновляем страницу по желанию
                    location.reload(); // или можно перезагрузить только таблицу позиций
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function() {
                alert('Произошла ошибка при отправке запроса');
            }
        });
    }

    // ---- При переходе между вкладками очищаем поля (опционально) ----
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        if (target === '#tab_catalog') {
            document.getElementById('product_div').innerHTML = '';
            document.getElementById('products_div').innerHTML = '';
        } else if (target === '#tab_custom') {
            // можно очистить поля произвольного товара
        }
    });
</script>