<?php
/**
 * Страничный скрипт для корзины
*/
defined('_ASTEXE_') or die('No access');


//Для работы с пользователем
require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
$user_session = DP_User::getUserSession();


//Получаем данные по валюте магазина
$currency_query = $db_link->prepare('SELECT * FROM `shop_currencies` WHERE `iso_code` = ?;');
$currency_query->execute( array($DP_Config->shop_currency) );
$currency_record = $currency_query->fetch();
$currency_sign = $currency_record["sign"];
//Строка для обозначения валюты
if($DP_Config->currency_show_mode == "no")
{
	$currency_indicator = "";
}
else if($DP_Config->currency_show_mode == "sign_before" || $DP_Config->currency_show_mode == "sign_after")
{
	$currency_indicator = $currency_sign;
}
else
{
	$currency_indicator = $currency_record["caption_short"];
}




require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();

if($user_id > 0)
{
    $order_link = "/shop/checkout/how_get";//Сразу на страницу выбора способа получения
}
else
{
    $order_link = "/shop/checkout/login_offer";//Предложить авторизацию
}

if($user_id > 0)
{
	//Поля для авторизованного пользователя
	$session_id = 0;
}
else
{
	//Поля для НЕ авторизованного пользователя
	$session_record = DP_User::getUserSession();
	if($session_record == false)
	{
		$result = array();
		$result["status"] = false;
		$result["code"] = "incorrect_session";
		$result["message"] = "Ошибка сессии";
		exit(json_encode($result));
	}
	
	$session_id = $session_record["id"];
}




$cart_records = array();//Список записей корзины для этого пользователя

//Получаем содержимое его корзины из базы данных
$cart_records_query = $db_link->prepare('SELECT `id` FROM `shop_carts` WHERE `user_id` = ? AND `session_id` = ?;');
$cart_records_query->execute( array($user_id, $session_id) );
while($cart_record = $cart_records_query->fetch() )
{
	array_push($cart_records, $cart_record["id"]);
}
?>




<div id="cart_area"></div>
<style>
#cart_area .table th,
#cart_area .table td{
	vertical-align: middle;
    white-space: nowrap;
}
#cart_area .table input[type="checkbox"]{
	width: 25px;
    height: 25px;
}

/* ---------- Общие стили корзины ---------- */
#cart_area .table input[type="checkbox"] {
    width: 25px;
    height: 25px;
}

.cart-mobile-header {
    display: none;
}

/* Контейнер корзины */
.cart-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Строка (заголовок или товар) */
.cart-row {
    display: grid;
    align-items: center;
    padding: 10px 14px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    transition: background 0.2s;
}

/* Заголовок */
.cart-header {
    background: #f8f9fa;
    font-weight: 700;
    font-size: 12px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

/* Ячейки-колонки */
.cart-col {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* -------------------- Десктопная версия -------------------- */
.cart-row.has-image {
    grid-template-columns: 40px 60px 1fr 1fr 1fr 1fr 120px 1fr 1fr;
}
.cart-row.no-image {
    grid-template-columns: 40px 1fr 1fr 1fr 1fr 120px 1fr 1fr;
}

/* Чекбокс */
.cart-col-checkbox {
    justify-content: center;
}
.cart-col-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

/* Изображение */
.cart-col-image img {
    max-width: 50px;
    max-height: 70px;
    object-fit: contain;
}

/* Новая колонка "Товар" (производитель + артикул + наименование) */
.cart-col-product {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}
.cart-col-product .product-main {
    font-weight: 600;
}
.cart-col-product .product-sub {
    font-size: 0.85em;
    color: #6c757d;
}

/* Срок поставки */
.cart-col-term .hh,
.cart-col-term .mm {
    display: inline-block;
    font-weight: 600;
}
.cart-col-term .mm {
    font-size: 0.7em;
    vertical-align: super;
}

/* Количество */
.cart-col-quantity .qty-controls {
    display: flex;
    align-items: center;
    gap: 2px;
}
.cart-col-quantity .qty-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #f1f3f5;
    border: 1px solid #ced4da;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    user-select: none;
    transition: background 0.15s;
}
.cart-col-quantity .qty-btn:hover {
    background: #e9ecef;
}
.cart-col-quantity .qty-input {
    width: 44px;
    height: 28px;
    text-align: center;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    padding: 0 4px;
}

/* Действия */
.cart-col-actions {
    justify-content: flex-end;
    gap: 6px;
}
.cart-col-actions .action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: #6c757d;
    transition: all 0.15s;
    cursor: pointer;
}
.cart-col-actions .action-btn:hover {
    background: #f1f3f5;
    color: #212529;
}
.cart-col-actions .action-btn .fa {
    font-size: 18px;
}
.cart-col-actions .action-btn .fa-refresh {
    font-size: 16px;
}
.cart-col-actions .action-btn .fa-car {
    font-size: 16px;
}
.cart-col-actions .action-btn .fa-times {
    font-size: 20px;
}

/* Информационные блоки */
.cart-info-block {
    padding: 14px 18px;
    margin: 8px 0;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    background: #f8f9fa;
    font-size: 12px;
}
.cart-info-block i {
    margin-right: 8px;
}

/* Итого */
.cart-total {
    text-align: right;
    font-weight: 700;
    padding: 12px 0;
    font-size: 1.4rem;
}
.cart-total .total-label {
    font-weight: 400;
    font-size: 16px;
    color: #6c757d;
}

/* Кнопки внизу */
.cart-actions-bottom {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 16px;
}
.cart-actions-bottom .btn {
    padding: 8px 20px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.15s;
}

.cart-col-storage {
	margin-left: 10px;
}

/* -------------------- Мобильная версия (≤ 768px) -------------------- */
@media (max-width: 768px) {
    .cart-header {
        display: none !important;
    }

    /* Переопределяем сетку для всех строк корзины */
    .cart-row,
    .cart-row.has-image,
    .cart-row.no-image {
        display: grid;
        grid-template-columns: 30px 1fr 1fr 1fr;  /* 4 колонки: чекбокс + 3 информационные */
        grid-template-rows: auto auto auto auto auto; /* 5 строк */
        gap: 6px 10px;
        padding: 14px 16px;
        margin-bottom: 10px;
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        align-items: center;
    }

    /* Чекбокс – первая колонка, первая строка */
    .cart-col-checkbox {
        grid-row: 1;
        grid-column: 1;
        justify-content: center;
        align-self: start;
    }
    .cart-col-checkbox input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    /* Название товара – первая строка, занимает оставшиеся 3 колонки */
    .cart-col-product {
        grid-row: 1;
        grid-column: 2 / span 3;
        align-items: flex-start;
    }

    /* Изображение – вторая строка, только во второй колонке (как было) */
    .cart-col-image {
        grid-row: 3;
        grid-column: 2;
    }

    /* Склад, срок доставки, цена – третья строка, по одной колонке */
    .cart-col-storage {
        grid-row: 2;
        grid-column: 4;
        margin-left: 0; /* убираем лишний отступ */
    }
    .cart-col-term {
        grid-row: 2;
        grid-column: 3;
    }
    .cart-col-price {
        grid-row: 2;
        grid-column: 2;
    }

    /* Количество и итого – четвёртая строка */
    .cart-col-quantity {
        grid-row: 4;
        grid-column: 2;
    }
    .cart-col-sum {
        grid-row: 4;
        grid-column: 3;
        font-weight: 600;
		width: 100px;
    }

    /* Кнопки действий – пятая строка, на всю ширину информационной части */
    .cart-col-actions {
        grid-row: 5;
        grid-column: 2 / span 3;
        justify-content: flex-start;
        gap: 8px;
        margin-top: 6px;
    }

    /* Стилизация элементов управления количеством и кнопок */
    .cart-col-quantity .qty-controls {
        justify-content: flex-start;
    }
    .cart-col-actions .action-btn {
        width: 40px;
        height: 40px;
        background: #f1f3f5;
        border-radius: 8px;
    }
    .cart-col-actions .action-btn .fa {
        font-size: 20px;
    }

    /* Изображение – без изменений */
    .cart-col-image img {
        max-width: 50px;
        max-height: 70px;
        object-fit: contain;
    }

    /* Информационные блоки */
    .cart-info-block {
        font-size: 13px;
        padding: 10px 14px;
    }

    /* Итого и кнопки внизу */
    .cart-total {
        font-size: 16px;
        padding: 8px 0;
    }
    .cart-actions-bottom {
        justify-content: center;
        gap: 8px;
    }
    .cart-actions-bottom .btn {
        width: 100%;
        text-align: center;
        justify-content: center;
    }
    .cart-info-block .btn {
        width: 100%;
        margin-top: 8px;
    }

    /* Мобильный хедер с чекбоксом "Выделить всё" */
    .cart-mobile-header {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 12px;
        font-size: 1rem;
        font-weight: 500;
    }
    .cart-mobile-header input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin-right: 10px;
        cursor: pointer;
    }
    .cart-mobile-header label {
        margin: 0;
        cursor: pointer;
    }
}  

</style>



<?php
if(count($cart_records) == 0)
{
    ?>
    <script>
        document.getElementById("cart_area").innerHTML = "Корзина пуста";
    </script>
    <?php
}
else
{
    ?>
    <script>
    // --------------------------------------------------------------------------------------
    //Переотобразить корзину
// --------------------------------------------------------------------------------------
// Переотобразить корзину
function refreshCartArea() {
    sum_total = Number(sum_total).toFixed(2);
    var sum_total_num = Number(sum_total);
    sum_total = digit(sum_total);

    <?php
    if ($DP_Config->currency_show_mode == "sign_before") {
        ?>
        sum_total = '<span class="currency"><?php echo $currency_indicator; ?></span> <span class="price">' + sum_total + '</span>';
        <?php
    } else if ($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after") {
        ?>
        sum_total = '<span class="price">' + sum_total + '</span> <span class="currency"><?php echo $currency_indicator; ?></span>';
        <?php
    }
    ?>

    // Обновление корзины снизу
    if (typeof updateCartInfo == 'function') {
        updateCartInfo();
    }

    // Обработка модуля корзины (для старых шаблонов)
    var cart_module_positions = document.getElementById("cart_module_positions");
    if (cart_module_positions != undefined) {
        cart_module_positions.innerHTML = "<b>Товаров в корзине</b> " + cart_records.length;
    }
    var cart_module_sum = document.getElementById("cart_module_sum");
    if (cart_module_sum != undefined) {
        cart_module_sum.innerHTML = "<b>На сумму</b> " + sum_total;
    }

    if (cart_records.length == 0) {
        document.getElementById("cart_area").innerHTML = "Корзина пуста";
        return;
    }

    // Определим есть ли изображения
    var there_is_images = false;
    for (var i = 0; i < cart_records.length; i++) {
        if (cart_records[i].image_src != '') {
            there_is_images = true;
            break;
        }
    }

    // Устаревшие товары
    var old_cart_items = false;
    for (var i = 0; i < cart_records.length; i++) {
        if (cart_records[i].access == 0) {
            old_cart_items = true;
            break;
        }
    }

    // Все ли выбраны
    var check_all = 'checked';
    for (var i = 0; i < cart_records.length; i++) {
        if (cart_records[i].checked_for_order == 0) {
            check_all = '';
            break;
        }
    }

    var cart_html = "";
    var rowClass = there_is_images ? 'has-image' : 'no-image';

    // Мобильный хедер (Выделить всё)
    cart_html += '<div class="cart-mobile-header">';
    cart_html += '  <input type="checkbox" ' + check_all + ' id="check_uncheck_all_mobile" onchange="on_check_uncheck_all_mobile();"/>';
    cart_html += '  <label for="check_uncheck_all_mobile">Выделить всё</label>';
    cart_html += '</div>';

    // ---- Заголовок (десктоп) ----
    cart_html += '<div class="cart-grid">';
    cart_html += '<div class="cart-row cart-header ' + rowClass + '">';
    cart_html += '  <div class="cart-col cart-col-checkbox"><input type="checkbox" ' + check_all + ' id="check_uncheck_all" onchange="on_check_uncheck_all();"/></div>';
    if (there_is_images) {
        cart_html += '  <div class="cart-col cart-col-image"></div>';
    }
    // Вместо трёх колонок – одна "Товар"
    cart_html += '  <div class="cart-col cart-col-product">Товар</div>';
    cart_html += '  <div class="cart-col cart-col-storage">Склад</div>';
    cart_html += '  <div class="cart-col cart-col-term">Срок</div>';
    cart_html += '  <div class="cart-col cart-col-price">Цена</div>';
    cart_html += '  <div class="cart-col cart-col-quantity">Кол-во</div>';
    cart_html += '  <div class="cart-col cart-col-sum">Сумма</div>';
    cart_html += '  <div class="cart-col cart-col-actions">Действия</div>';
    cart_html += '</div>';

    // ---- Товары ----
    for (var i = 0; i < cart_records.length; i++) {
        var checked_for_order = (cart_records[i].checked_for_order == 1) ? 'checked' : '';

        var price = digit(Number(cart_records[i].price).toFixed(2));
        var price_sum = digit(Number(cart_records[i].price_sum).toFixed(2));
        <?php
        if ($DP_Config->currency_show_mode == "sign_before") {
            ?>
            price = '<?php echo $currency_indicator; ?> ' + price;
            price_sum = '<?php echo $currency_indicator; ?> ' + price_sum;
            <?php
        } else if ($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after") {
            ?>
            price = price + ' <?php echo $currency_indicator; ?>';
            price_sum = price_sum + ' <?php echo $currency_indicator; ?>';
            <?php
        }
        ?>

        var style_tr = '';
        // Доработка id 34 – устаревший товар
        if (cart_records[i].access == 0) {
            style_tr = 'background:#fcf8e3;';
            price = '<s>' + price + '</s>';
            price_sum = '<s>' + price_sum + '</s>';
        }
        // Доработка О.К. – обновление цены/наличия
        if (cart_records[i].is_update == 3) {
            let update_price = cart_records[i].update_price;
            let update_exist = cart_records[i].update_exist;
            style_tr = 'background:linear-gradient(90deg, rgba(84,232,108,0.3) 40%, rgba(103,191,255,0.3) 60%);';
            price = '<span>' + price + '</span> <i data-toggle="tooltip" title="Старая цена - ' + update_price + ', требовалось - ' + update_exist + '" class="fa fa-question-circle" style="color:#0d6efd;"></i>';
        } else {
            if (cart_records[i].is_update == 1) {
                let update_price = cart_records[i].update_price;
                style_tr = 'background:#d4edda;';
                price = '<span>' + price + '</span> <i data-toggle="tooltip" title="Старая цена - ' + update_price + '" class="fa fa-question-circle" style="color:#0d6efd;"></i>';
            }
            if (cart_records[i].is_update == 2) {
                let update_exist = cart_records[i].update_exist;
                style_tr = 'background:#d1ecf1;';
                price = '<span>' + price + '</span> <i data-toggle="tooltip" title="Требовалось - ' + update_exist + '" class="fa fa-question-circle" style="color:#0d6efd;"></i>';
            }
        }

        cart_html += '<div class="cart-row cart-item ' + rowClass + '" style="' + style_tr + '">';
        // Чекбокс
        cart_html += '  <div class="cart-col cart-col-checkbox"><input type="checkbox" ' + checked_for_order + ' onclick="check_for_order(' + cart_records[i].id + ');" /></div>';
        // Изображение
        if (there_is_images) {
            if (cart_records[i].image_src !== '') {
                cart_html += '  <div class="cart-col cart-col-image"><img src="' + cart_records[i].image_src + '" alt=""/></div>';
            } else {
                cart_html += '  <div class="cart-col cart-col-image"></div>';
            }
        }
        // Новая ячейка "Товар" – производитель + артикул (основной), наименование (подпись)
        cart_html += '  <div class="cart-col cart-col-product">';
        cart_html += '    <span class="product-main">' + cart_records[i].manufacturer + ' ' + cart_records[i].article + '</span>';
        cart_html += '    <span class="product-sub">' + cart_records[i].name + '</span>';
        cart_html += '  </div>';
        // Склад
        cart_html += '  <div class="cart-col cart-col-storage">' + cart_records[i].storage_caption + '</div>';
        // Срок
        cart_html += '  <div class="cart-col cart-col-term">';
        if (cart_records[i].delivery_start || cart_records[i].delivery_end) {
            const deliveryHtml = formatDelivery(
                parseInt(cart_records[i].delivery_start),
                parseInt(cart_records[i].delivery_end)
            );
            if (deliveryHtml) {
                cart_html += deliveryHtml;
            } else {
                cart_html += cart_records[i].time_to_exe || '';
            }
        } else {
            cart_html += cart_records[i].time_to_exe || '';
        }
        cart_html += '  </div>';
        // Цена
        cart_html += '  <div class="cart-col cart-col-price">' + price + '</div>';
        // Количество
        cart_html += '  <div class="cart-col cart-col-quantity">';
        cart_html += '    <div class="qty-controls">';
        cart_html += '      <span class="qty-btn" onclick="minusCountNeed(' + cart_records[i].id + ');">−</span>';
        cart_html += '      <input class="qty-input" type="text" value="' + cart_records[i].count_need + '" onkeyup="onKeyUpCountNeed(' + cart_records[i].id + ');" id="count_need_' + cart_records[i].id + '"/>';
        cart_html += '      <span class="qty-btn" onclick="plusCountNeed(' + cart_records[i].id + ');">+</span>';
        cart_html += '    </div>';
        cart_html += '  </div>';
        // Сумма
        cart_html += '  <div class="cart-col cart-col-sum">' + price_sum + '</div>';
        // Действия
        cart_html += '  <div class="cart-col cart-col-actions">';
        cart_html += '    <span class="action-btn" onclick="RefreschRecord(' + cart_records[i].id + ');" title="Перезаказать"><i class="fa fa-refresh" aria-hidden="true"></i></span>';
        cart_html += '    <span class="action-btn" onclick="show_add_bloknot(' + cart_records[i].id + ');" title="Добавить в блокнот"><i class="fa fa-car" aria-hidden="true"></i></span>';
        cart_html += '    <span class="action-btn" onclick="deleteRecord(' + cart_records[i].id + ');" title="Удалить"><i class="fa fa-times" aria-hidden="true"></i></span>';
        cart_html += '  </div>';
        cart_html += '</div>';
    }

    // ---- Информационные блоки (устаревшие / обновления) ----
    var flag_no_access_exist = cart_records.some(item => item.access == 0);
    if (flag_no_access_exist) {
        cart_html += '<div class="cart-info-block" style="background:#fcf8e3; border-color:#faebcc;"><i class="fa fa-info-circle"></i> Некоторые позиции корзины устарели. Перед оформлением заказа выделенные позиции необходимо перезаказать – нажмите <i class="fa fa-refresh"></i></div>';
    }
    var flag_no_price_update = cart_records.some(item => item.is_update == 1 || item.is_update == 3);
    if (flag_no_price_update) {
        cart_html += '<div class="cart-info-block" style="background:#d4edda; border-color:#c3e6cb;"><i class="fa fa-info-circle"></i> Некоторые позиции корзины обновились. Чтобы узнать прежнюю стоимость, кликните на вопрос рядом с ценой.</div>';
    }
    var flag_no_exist_update = cart_records.some(item => item.is_update == 2 || item.is_update == 3);
    if (flag_no_exist_update) {
        cart_html += '<div class="cart-info-block" style="background:#d1ecf1; border-color:#bee5eb;"><i class="fa fa-info-circle"></i> Для некоторых позиций обновилось наличие товара у поставщика.</div>';
    }

    cart_html += '<div class="cart-info-block" style="background:#f8f9fa;"><i class="fa fa-info-circle"></i> Стоимость заказа в корзине указана на условиях самовывоза. Для выполнения заказа может потребоваться предоплата.</div>';

    // ---- Кнопки печати ----
    <?php
    $print_docs_buttons = "";
    $print_docs_query = $db_link->prepare("SELECT * FROM `shop_print_docs` WHERE `name` IN('user_cart') ORDER BY `id` ASC;");
    $print_docs_query->execute();
    while ($print_doc = $print_docs_query->fetch()) {
        $print_doc["parameters_values"] = json_decode($print_doc["parameters_values"], true);
        if (isset($DP_Config->wholesaler)) {
            $doc_query_2 = $db_link->prepare("SELECT * FROM `shop_print_docs_wholesaler` WHERE `doc_name` = ? AND `office_id` = (SELECT `office_id` FROM `shop_orders` WHERE `id` = ?);");
            $doc_query_2->execute(array($print_doc["name"], $order_id));
            $doc_record_2 = $doc_query_2->fetch();
            if ($doc_record_2 != false) {
                $print_doc["parameters_values"] = json_decode($doc_record_2["parameters_values"], true);
            }
        }
        if (!DP_User::isAdmin()) {
            if ((int)$print_doc["parameters_values"]["button_visible_for_user"] != 1) {
                continue;
            }
        }
        if ((int)$print_doc["parameters_values"]["button_visible_for_pages"] === 1) {
            if ($print_docs_buttons != "") $print_docs_buttons .= " ";
            if ($print_doc['name'] == 'user_cart') {
                $print_docs_buttons .= "<a class=\"btn btn-outline-primary btn-sm\" href=\"/content/shop/print_docs/service/print.php?doc_name=" . $print_doc["name"] . "&new=1&csrf_guard_key=" . $user_session["csrf_guard_key"] . "\" target=\"_blank\"><i class=\"fa fa-print\"></i> Печать корзины</a> ";
                continue;
            }
            $print_docs_buttons .= "<a class=\"btn btn-outline-primary btn-sm\" href=\"/content/shop/print_docs/service/print.php?doc_name=" . $print_doc["name"] . "&csrf_guard_key=" . $user_session["csrf_guard_key"] . "\" target=\"_blank\"><i class=\"fa fa-print\"></i> " . $print_doc["caption"] . "</a>";
        }
    }
    if ($print_docs_buttons != "") {
        ?>
        if (sum_total_num > 0) {
            cart_html += '<div class="cart-actions-bottom"><?php echo $print_docs_buttons; ?></div>';
        }
        <?php
    }
    ?>

    // ---- Кнопка обновления устаревших ----
    <?php
    $order_update_html = '';
    if ((int)$user_id > 0) {
        $order_update_html = '<span style="display:none;" id="confirm_loader">Подождите... <img src="/content/files/images/ajax-loader-transparent.gif" /></span> <a id="update_items_btn" class="btn btn-warning" onClick="update_order(cart_records, ' . $user_id . ');" href="javascript:void(0);" title="Обновить все устаревшие позиции в корзине">Обновить позиции</a>';
    }
    ?>

    // ---- Итого и кнопка заказа ----
    cart_html += '<div class="cart-total"><span class="total-label">Итого:</span> ' + sum_total + '</div>';
    cart_html += '<div class="cart-actions-bottom">';
    if (old_cart_items) {
        cart_html += '<?php echo $order_update_html; ?>';
    }
    if (sum_total_num > 0) {
        cart_html += '<a class="btn btn-primary" href="<?php echo $order_link; ?>">Оформить заказ</a>';
    }
    cart_html += '</div>';

    cart_html += '</div>'; // закрываем .cart-grid
    document.getElementById("cart_area").innerHTML = cart_html;

    // Инициализация tooltips (если используется Bootstrap)
    if (typeof jQuery !== 'undefined' && jQuery.fn.tooltip) {
        jQuery('[data-toggle="tooltip"]').tooltip({ placement: 'top', html: true });
    }
}
    // --------------------------------------------------------------------------------------
	var ajax_flag = false;
    // --------------------------------------------------------------------------------------
    //Функция добавления требуемого количества
    function plusCountNeed(cart_record_id)
    {
		if(ajax_flag){
			return;
		}
		ajax_flag = true;
        //Текущее количество
        var current_count_need = parseInt(document.getElementById("count_need_"+cart_record_id).value);
		
		var min_order = 1;
		for(var i=0; i < cart_records.length; i++)
        {
			if(cart_records[i]['id'] == cart_record_id){
				min_order = cart_records[i]['min_order'];
			}
		}
        //Объект для запроса
        var request_object = new Object;
        request_object.id = cart_record_id;
        request_object.count_need = parseInt(current_count_need) + parseInt(min_order);
        
        
        //Увеличиваем наличие на сервере и только после этого отображаем
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос синхронный
            url: "/content/shop/order_process/ajax_change_count_need.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
				//console.log(answer);
				
                if(answer.status == true)
                {
                    cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                    calculateSums();//Пересчитываем суммы по товарам и итого
                    refreshCartArea();//Отображаем товары в корзине
                }
                else
                {
                    if(answer.code == "not_enough")
                    {
                        alert("Превышено наличие на складе");
                    }
                    else
                    {
                        alert("Ошибка добавления количества");
                    }
                }
				ajax_flag = false;
            }
        });
    }
	
	function on_check_uncheck_all_mobile() {
		var state = document.getElementById("check_uncheck_all_mobile").checked;
		document.getElementById("check_uncheck_all").checked = state;
		on_check_uncheck_all(); // перерисовывает корзину и синхронизирует оба чекбокса
	}
	
    // --------------------------------------------------------------------------------------
    //Функция вычитания требуемого количества
    function minusCountNeed(cart_record_id)
    {
		if(ajax_flag){
			return;
		}
		ajax_flag = true;
        //Текущее количество
        var current_count_need = parseInt(document.getElementById("count_need_"+cart_record_id).value);
		
		var min_order = 1;
		for(var i=0; i < cart_records.length; i++)
        {
			if(cart_records[i]['id'] == cart_record_id){
				min_order = cart_records[i]['min_order'];
			}
		}
        //Проверка допустимости
        if( parseInt(current_count_need-1) > 0 )
        {
            //Объект для запроса
            var request_object = new Object;
            request_object.id = cart_record_id;
            request_object.count_need = parseInt(current_count_need) - parseInt(min_order);
            
            //Уменьшаем наличие на сервере и только после этого отображаем
            jQuery.ajax({
                type: "POST",
                async: true, //Запрос синхронный
                url: "/content/shop/order_process/ajax_change_count_need.php",
                dataType: "json",//Тип возвращаемого значения
                data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer)
                {
                    if(answer.status == true)
                    {
                        cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                        calculateSums();//Пересчитываем суммы по товарам и итого
                        refreshCartArea();//Отображаем товары в корзине
                    }
                    else
                    {
                        alert("Ошибка уменьшения количества");
                    }
					ajax_flag = false;
                }
            });
        }else{
			ajax_flag = false;
		}
    }
    // --------------------------------------------------------------------------------------
	
	// Функция для форматирования даты (аналог handleDate из PHP)
	function handleDate(timestamp) {
		if (!timestamp || timestamp == -1) return null;
		
		const currentDate = new Date();
		currentDate.setHours(0, 0, 0, 0);
		
		const dateTime = new Date(timestamp * 1000); // Умножаем на 1000, т.к. JS использует миллисекунды
		
		const timeDiff = Math.floor((dateTime - currentDate) / (1000 * 60 * 60 * 24));
		
		const result = {
			date: '',
			time: false
		};
		
		if (timeDiff > 1) {
			result.date = ('0' + dateTime.getDate()).slice(-2) + '.' + ('0' + (dateTime.getMonth() + 1)).slice(-2);
		} else if (timeDiff == 1) {
			result.date = 'Завтра';
		} else if (timeDiff == 0) {
			result.date = 'Сегодня';
		}
		
		const h = dateTime.getHours();
		const m = dateTime.getMinutes();
		
		if (h > 0 || m > 0) {
			result.time = {
				h: ('0' + h).slice(-2),
				m: ('0' + m).slice(-2)
			};
		}
		
		return result;
	}

	// Функция для формирования строки доставки
	// Измените функцию formatDelivery:
	function formatDelivery(deliveryStart, deliveryEnd) {
		let delivery = '';
		
		const start = deliveryStart ? handleDate(deliveryStart) : null;
		const end = deliveryEnd ? handleDate(deliveryEnd) : null;
		
		if (start && end) {
			if (start.date == end.date) {
				delivery = start.date;
				if (start.time) {
					delivery += ' с <span class="hh" style="display: inline-block;">' + start.time.h + '</span><sup class="mm" style="display: inline-block;">' + start.time.m + '</sup>';
				}
				if (end.time) {
					delivery += ' до <span class="hh" style="display: inline-block;">' + end.time.h + '</span><sup class="mm" style="display: inline-block;">' + end.time.m + '</sup>';
				}
			} else {
				delivery = ' с ' + start.date;
				if (start.time) {
					delivery += ' <span class="hh" style="display: inline-block;">' + start.time.h + '</span><sup class="mm" style="display: inline-block;">' + start.time.m + '</sup>';
				}
				delivery += ' до ' + end.date;
				if (end.time) {
					delivery += ' <span class="hh" style="display: inline-block;">' + end.time.h + '</span><sup class="mm" style="display: inline-block;">' + end.time.m + '</sup>';
				}
			}
		} else if (end) {
			delivery = end.date;
			if (end.time) {
				delivery += ' до <span class="hh" style="display: inline-block;">' + end.time.h + '</span><sup class="mm" style="display: inline-block;">' + end.time.m + '</sup>';
			}
		}
		
		return delivery;
	}
	
    //Функция изменения количества при ручном вводе в поле
    function onKeyUpCountNeed(cart_record_id)
    {
		if(ajax_flag){
			return;
		}
		ajax_flag = true;
		
        //Текущее количество
        var current_count_need = parseInt(document.getElementById("count_need_"+cart_record_id).value);
        
        //Если введено допустимое значение
        if(current_count_need > 0)
        {
            //Объект для запроса
            var request_object = new Object;
            request_object.id = cart_record_id;
            request_object.count_need = current_count_need;
            
            //Уменьшаем наличие на сервере и только после этого отображаем
            jQuery.ajax({
                type: "POST",
                async: true, //Запрос синхронный
                url: "/content/shop/order_process/ajax_change_count_need.php",
                dataType: "json",//Тип возвращаемого значения
                data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer)
                {
                    if(answer.status == true)
                    {
                        cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                        calculateSums();//Пересчитываем суммы по товарам и итого
                        refreshCartArea();//Отображаем товары в корзине
                    }
                    else
                    {
                        if(answer.code == "not_enough")//Превышено наличие на складе - но мы зарезервировали максимально-доступное количество
                        {
                            cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                            calculateSums();//Пересчитываем суммы по товарам и итого
                            refreshCartArea();//Отображаем товары в корзине
                            alert("Превышено наличие на складе");
                        }
                        else
                        {
                            cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                            calculateSums();//Пересчитываем суммы по товарам и итого
                            refreshCartArea();//Отображаем товары в корзине
							alert("Ошибка изменения количества");
                        }
                    }
					ajax_flag = false;
                }
            });
        }
        else//Просто исправляем обратно
        {
            alert("Введено недопустимое значение");
            document.getElementById("count_need_"+cart_record_id).value = cart_records[getElementIndex(cart_record_id)].count_need;
			ajax_flag = false;
        }
    }
    // --------------------------------------------------------------------------------------
    //Удаление из Корзины
    function deleteRecord(cart_record_id)
    {
        //Объект для запроса
        var request_object = new Object;
		request_object.records_to_del = new Array();
		request_object.records_to_del.push(cart_record_id)
    
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/content/shop/order_process/ajax_delete_cart_record.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                console.log(answer);
                if(answer.status == true)
                {
                    //Удаляем элемет из массива
                    cart_records.splice(getElementIndex(answer.records_to_del[0]), 1);
                    calculateSums();//Пересчитываем суммы по товарам и итого
                    refreshCartArea();//Отображаем товары в корзине
                }
                else
                {
                    alert("Ошибка удаления");
                }
            }
        });
    }
    // --------------------------------------------------------------------------------------
    //Получить индекс из списка по ID записи
    function getElementIndex(cart_record_id)
    {
        //Сначала определяем индекс объекта в списке javascript
        for(var i=0; i < cart_records.length; i++)
        {
            if(cart_record_id == cart_records[i].id)
            {
                return i;
            }
        }
    }
    // --------------------------------------------------------------------------------------
    //Пересчитать суммы по продуктам
    function calculateSums()
    {
        sum_total = 0;//Обнуляем общую сумму
        
        for(var i=0; i < cart_records.length; i++)
        {
			cart_records[i].price_sum = cart_records[i].price*cart_records[i].count_need;
            
			if( cart_records[i].checked_for_order == 0 )
			{
				continue;
			}
			
            sum_total = sum_total + cart_records[i].price_sum;
        }
    }
    // --------------------------------------------------------------------------------------
    //Снять / отметить для заказа
	function check_for_order(cart_record_id)
	{
		//////////////////////////////////////////////////////////////////////// - Доработка id 34
		if(cart_records[getElementIndex(cart_record_id)].access == 0 || cart_records[getElementIndex(cart_record_id)].count_need == 0){
			calculateSums();//Пересчитываем суммы по товарам и итого
			refreshCartArea();//Отображаем товары в корзине
			return;
		}
		//////////////////////////////////////////////////////////////////////// - END Доработка id 34
		
		//Объект для запроса
        var request_object = new Object;
		request_object.records = new Array();
		request_object.records.push(cart_record_id);
		
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/content/shop/order_process/ajax_check_for_order.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer)
            {
                console.log(answer);
                if(answer.status == true)
                {
                    cart_records[getElementIndex(answer.records[0].cart_record_id)].checked_for_order = answer.records[0].checked_for_order;
					
					
                    calculateSums();//Пересчитываем суммы по товарам и итого
                    refreshCartArea();//Отображаем товары в корзине
                }
                else
                {
                    alert("Серверная ошибка");
                }
            }
        });
	}
	// --------------------------------------------------------------------------------------
	//Обработка переключения Выделить все / Снять все
    function on_check_uncheck_all()
    {
        var state = document.getElementById("check_uncheck_all").checked;
        
		var request_object = new Object;
		request_object.records = new Array();
		
		for(var i=0; i < cart_records.length; i++)
        {
			if( cart_records[i].checked_for_order != state )
			{
				//////////////////////////////////////////////////////////////////////// - Доработка id 34
				if(cart_records[i].access == 0){
					continue;
				}
				if(cart_records[i].count_need == 0){
					continue;
				}
				//////////////////////////////////////////////////////////////////////// - END Доработка id 34
				
				request_object.records.push(cart_records[i].id);
			}
        }
		
        if(request_object.records.length > 0){
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/content/shop/order_process/ajax_check_for_order.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
				success: function(answer)
				{
					if(answer.status == true)
					{
						for(var i=0; i < answer.records.length; i++)
						{
							cart_records[getElementIndex(answer.records[i].cart_record_id)].checked_for_order = answer.records[i].checked_for_order;
						}
						
						calculateSums();//Пересчитываем суммы по товарам и итого
						refreshCartArea();//Отображаем товары в корзине
					}
					else
					{
						alert("Серверная ошибка");
					}
				}
			});
		}else{
			calculateSums();//Пересчитываем суммы по товарам и итого
			refreshCartArea();//Отображаем товары в корзине
		}
    }//~function on_check_uncheck_all()
	// --------------------------------------------------------------------------------------
	// Функция отделяет тысячные знаки пробелом. Используется для отображения цены
	function digit(str){
		var parts = (str + '').split('.'),
			main = parts[0],
			len = main.length,
			output = '',
			i = len - 1;
		
		while(i >= 0) {
			output = main.charAt(i) + output;
			if ((len - i) % 3 === 0 && i > 0) {
				output = ' ' + output;
			}
			--i;
		}

		if (parts.length > 1) {
			output += '.' + parts[1];
		}
		return output;
	}
	// --------------------------------------------------------------------------------------
	// Функция удаляет из корзине товар и переносит пользователя в проценку
	function RefreschRecord(id){
		var url = cart_records[getElementIndex(id)].url_refresh;
		deleteRecord(id);
		window.location = url;
	}
	// --------------------------------------------------------------------------------------
    var cart_records = new Array();
    var sum_total = 0;//Сумма заказа
    <?php

	$cart = array();// Массив объектов содержимого корзины

	//Формируем объекты содержимого корзины
    for($i=0; $i < count($cart_records); $i++)
    {
        $cart_object = array();
    
		$cart_record_query = $db_link->prepare('SELECT * FROM `shop_carts` WHERE `id` = ?;');
        $cart_record_query->execute( array($cart_records[$i]) );
        $cart_record = $cart_record_query->fetch();
		
		$select_storage_caption = $db_link->prepare("SELECT `short_name` FROM `shop_storages` WHERE `id` = ?;");
		$select_storage_caption->execute([$cart_record["t2_storage_id"]]);
		
		$res_select_storage_caption = $select_storage_caption->fetch(PDO::FETCH_ASSOC);
		
        //Заполняем поля, которые не зависят от типа продукта:
        $cart_object["id"] = $cart_records[$i];
        $cart_object["price"] = $cart_record["price"];
        $cart_object["product_type"] = $cart_record["product_type"];
        $cart_object["count_need"] = $cart_record["count_need"];
		$cart_object["checked_for_order"] = $cart_record["checked_for_order"];
		$cart_object["is_update"] = $cart_record["is_update"];
		$cart_object["t2_exist"] = $cart_record["t2_exist"];
		// В коде, где формируется $cart_object
		$cart_object["delivery"] = ""; // По умолчанию пустая строка

		$cart_object["delivery_start"] = "";
		$cart_object["delivery_end"] = "";

		// Пробуем получить delivery из t2_product_json
		if (!empty($cart_record["t2_product_json"])) {
			$product_json = json_decode($cart_record["t2_product_json"], true);
			
			if ($product_json !== null && isset($product_json['rest_params'])) {
				// Передаем timestamp'ы
				$cart_object["delivery_start"] = $product_json['rest_params']['delivery_start'] ?? "";
				$cart_object["delivery_end"] = $product_json['rest_params']['delivery_end'] ?? "";
			}
		}
			
		$cart_object["update_price"] = $cart_record["update_price"];
		$cart_object["storage_caption"] = $res_select_storage_caption["short_name"];
		
		if($cart_object["is_update"] == 2 || $cart_object["is_update"] == 3) {
			if($cart_object["count_need"] > $cart_object["t2_exist"]) {
				$cart_object["count_need"] = $cart_object["t2_exist"];
				$cart_object["checked_for_order"] = 0;
				$cart_count_need_query = $db_link->prepare('UPDATE `shop_carts` SET `count_need` = ?, `checked_for_order` = 0 WHERE `id` = ?;');
				$cart_count_need_query->execute( array($cart_object["count_need"], $cart_record["id"]) );
				$cart_count_need_query->fetch();
			} else if($cart_object["count_need"] == 0 && $cart_object["t2_exist"] > 0) {
				$cart_object["count_need"] = 1;
				$cart_object["checked_for_order"] = 0;
				$cart_count_need_query = $db_link->prepare('UPDATE `shop_carts` SET `count_need` = ?, `checked_for_order` = 0 WHERE `id` = ?;');
				$cart_count_need_query->execute( array($cart_object["count_need"], $cart_record["id"]) );
				$cart_count_need_query->fetch();
			}
		}
		
		//Срок поставки
		if($cart_record["t2_time_to_exe"] < $cart_record["t2_time_to_exe_guaranteed"]){
			$cart_object["time_to_exe"] = $cart_record["t2_time_to_exe"] .' - '. $cart_record["t2_time_to_exe_guaranteed"];
		}else{
			$cart_object["time_to_exe"] = $cart_record["t2_time_to_exe"];
		}
		if($cart_object["time_to_exe"] == 0){
			$cart_object["time_to_exe"] = 'На складе';
		}else{
			$cart_object["time_to_exe"] = $cart_object["time_to_exe"] . ' дн.';
		}
		
		//Минимальная партия
		$cart_object["min_order"] = (int) $cart_record["t2_min_order"];
		if($cart_object["min_order"] <= 0){
			$cart_object["min_order"] = 1;
		}
        
		//Получаем Наименование
		$product_name = trim($cart_record["t2_name"]);
		$product_name = str_replace( array("'", '"', "\n", "\t", "\r", "\\"), "", $product_name);
		if(!empty($product_name)){
			$cart_object["name"] = $product_name;
		}
		
		$cart_object["manufacturer"] = $cart_record["t2_manufacturer"];
		$cart_object["article"] = $cart_record["t2_article"];
		
		//Поля зависящие от типа продукта
		$cart_object["image_src"] = '';
		$cart_object["product_id"] = '';
		$cart_object["url_refresh"] = '';
		
		//////////////////////////////////////////////////////////////////////// - Доработка id 34
		if(time() < ((int)$cart_record["time"] + (3600 * 12))){
			$cart_object["access"] = 1;
		}else{
			$cart_object["access"] = 0;
			//Снимаем выделение позиции
			$product_query = $db_link->prepare("UPDATE `shop_carts` SET `checked_for_order` = 0 WHERE `id` = ?;");
			$product_query->execute( array($cart_record["id"]) );
			$cart_object["checked_for_order"] = 0;
		}
		//////////////////////////////////////////////////////////////////////// - END Доработка id 34
		
        //Заполняем поля объекта корзины в зависимости от типа продукта (1 - каталожный, 2 - docpart)
        switch($cart_record["product_type"])
        {
            case 1:
                $product_id = (int) $cart_record["product_id"];
				
				$cart_object["product_id"] = $product_id;
				
				//////////////////////////////////////////////////////////////////////// - Доработка id 34
				
					$product_query = $db_link->prepare("SELECT *, CAST((SELECT `price` FROM `shop_carts_details` WHERE `cart_record_id` = ?) AS decimal(20,2)) AS 'price_not_markup' FROM `shop_storages_data` WHERE `id` = (SELECT `storage_record_id` FROM `shop_carts_details` WHERE `cart_record_id` = ?) AND `product_id` = ?;");
					$product_query->execute( array($cart_record["id"], $cart_record["id"], $product_id) );
					$record = $product_query->fetch();
					
					// Проверяем наличие складской записи
					if(empty($record) || ($record['price'] != $record["price_not_markup"])){
						$cart_object["access"] = 0;
						
						//Снимаем выделение позиции
						$product_query = $db_link->prepare("UPDATE `shop_carts` SET `checked_for_order` = 0 WHERE `id` = ?;");
						$product_query->execute( array($cart_record["id"]) );
						$cart_object["checked_for_order"] = 0;
					}
				
				//////////////////////////////////////////////////////////////////////// - END Доработка id 34
				
                //Получаем id категории и alias продукта
                $product_query = $db_link->prepare("SELECT `category_id`, `caption`, `alias` FROM `shop_catalogue_products` WHERE `id` = $product_id;");
				$product_query->execute();
                $product_record = $product_query->fetch();
				$category_id = (int) $product_record['category_id'];
				$product_alias = trim($product_record['alias']);
				
                //Получаем изображение
				$image_query = $db_link->prepare("SELECT `id`, `file_name` FROM `shop_products_images` WHERE `product_id` = ? ORDER BY `id` ASC LIMIT 1;");
				$image_query->execute( array($product_id) );
                $image_record = $image_query->fetch();
				$image_record["file_name"] = trim($image_record["file_name"]);
				if( !empty($image_record["file_name"]) )
                {
                    if( strpos($image_record["file_name"], "/") )
					{
						$cart_object["image_src"] = $image_record["file_name"];
					}else{
						if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/products_images/".$image_record["file_name"])){
							$cart_object["image_src"] = '/content/shop/catalogue/watermark.php?image_id='.$image_record["id"];
						}
					}
                }
            
				//Получаем ссылку на продукт для переоценки
				$product_category_query = $db_link->prepare("SELECT `url` FROM `shop_catalogue_categories` WHERE `id` = $category_id");
				$product_category_query->execute();
                $product_category_record = $product_category_query->fetch();
				$product_category_url = trim($product_category_record['url']);
				
				if($DP_Config->product_url == 'id'){
					$cart_object["url_refresh"] = '/'.$product_category_url.'/'.$product_id;
				}else{
					$cart_object["url_refresh"] = '/'.$product_category_url.'/'.$product_alias;
				}
				
                break;
            case 2:
				
                //Получаем изображение
				$product_image = NULL;
				if( isset($cart_record["image"]) )
				{
					$product_image = trim($cart_record["image"]);
				}
				if( !empty($product_image) )
                {
                    if( strpos($product_image, "/") )
					{
						$cart_object["image_src"] = $product_image;
					}else{
						if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/products_images/".$product_image)){
							$cart_object["image_src"] = "/content/files/images/products_images/".$product_image;
						}
					}
                }
				
				//Получаем ссылку на продукт для переоценки
				$cart_object["url_refresh"] = "/parts/".htmlentities($cart_object["manufacturer"])."/".$cart_object["article"];
				
                break;
        }
		
		$cart[] = $cart_object;
		
    }//for($i) - формируем объект корзины
    ?>
	cart_records = JSON.parse('<?php echo json_encode($cart); ?>');
    calculateSums();//Пересчитываем суммы по товарам и итого
    refreshCartArea();//Отображаем товары в корзине
		jQuery(function () {
			jQuery('[data-toggle="tooltip"]').tooltip({
					placement: 'top',
					html: true
			})
		})
    </script>
    <?php
}//~else - в корзине есть записи
?>














<!---------------------------------------------- ГАРАЖ ---------------------------------------------->
<style>
body {
   padding: 0 !important;
}
#my_modal_box_for_garage .modal {
	z-index:99999999;
	padding-right: 0px !important;
}
#my_modal_box_for_garage .modal-header {
  text-align: center;
  font-size: 14px;
  background: #fff;
  color:#000;
  border-bottom: 1px solid #999;
}
#my_modal_box_for_garage .close{
	color:#000;
}
#my_modal_box_for_garage .modal-footer {
	border-top: 1px solid #999;
	text-align: center;
}
#my_modal_box_for_garage .modal-dialog {
    max-width: 601px;
    margin: 30px auto;
    width: 100%;
}
</style>
<div id="my_modal_box_for_garage">
  <div class="modal fade" id="modal_garage" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="padding:10px 15px;">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <b>Выберите автомобиль, в блокнот которого будет добавлена позиция</b>
        </div>
        <div class="modal-body" style="color:#000; padding:40px 50px;">
			<div id="add_bloknot_content">
				<?php
				$query = $db_link->prepare('SELECT *, (SELECT `caption` FROM `shop_docpart_cars` WHERE `id` = `shop_docpart_garage`.`mark_id`) AS `mark` FROM `shop_docpart_garage` WHERE `user_id` = ?;');
				$query->execute( array($user_id) );
				echo '<select id="garage_auto" class="form-control">';
				echo '<option value="0">Общий блокнот</option>';
				while($car = $query->fetch())
				{
					echo '<option value="'.$car['id'].'">'. $car["mark"]." ".$car["model"]." ".$car["year"]." года - ". $car["caption"] .'</option>';
				}
				echo '</select>';
				?>
			</div>
			<div id="add_bloknot_msg"></div>
        </div>
        <div id="add_bloknot_btn" class="modal-footer">
			<a style="margin-bottom: 5px;" class="btn btn-ar btn-primary" onclick="add_bloknot();">Добавить в блокнот</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
var id_in_bloknot = -1;// id позиции корзины которую будем добавлять в блокнот
// Функция отображения блока добавления позиции в блокнот
function show_add_bloknot(id){
	<?php
	if(empty($user_id)){
	?>
	alert('Для добавления позиций в блокнот необходимо авторизоваться на сайте');
	return;
	<?php
	}
	?>
	id_in_bloknot = id;
	$("#modal_garage").modal();
}
// Функция добавления позиции в блокнот гаража
function add_bloknot(){
	if(id_in_bloknot >= 0){
		var n = document.getElementById("garage_auto").options.selectedIndex;
		var garage_id = document.getElementById("garage_auto").options[n].value;
		
		var Products = cart_records[getElementIndex(id_in_bloknot)];
		
		var request_object = new Object;
		request_object.manufacturer = encodeURIComponent(Products.manufacturer);
		request_object.article = encodeURIComponent(Products.article);
		request_object.name = encodeURIComponent(Products.name);
		request_object.exist = encodeURIComponent(Products.count_need);
		request_object.price = encodeURIComponent(Products.price);
		
		jQuery.ajax({
			type: "POST",
			async: true,
			url: "/content/shop/docpart/garage/ajax_add_to_notepad.php",
			dataType: "json",
			data: "garage="+garage_id+"&product="+JSON.stringify(request_object)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
			success: function(answer)
			{
				var icon = '<i style="font-size: 30px; color: green;" class="fa fa-check"></i> ';
				if(answer.status != true){
					icon = '<i style="font-size: 30px; color: red;" class="fa fa-times"></i> ';
				}
				
				document.getElementById('add_bloknot_content').style.display = "none";
				document.getElementById('add_bloknot_btn').style.display = "none";
				document.getElementById('add_bloknot_msg').innerHTML = '<table><tr><td style="padding-right:5px;">'+icon+'</td><td>'+answer.message+'</td></tr></table>';
				
				setTimeout(function(){
					$("#modal_garage").modal('hide');
					
				}, 2000);
				
				setTimeout(function(){
					document.getElementById('add_bloknot_content').style.display = "block";
					document.getElementById('add_bloknot_btn').style.display = "block";
					document.getElementById('add_bloknot_msg').innerHTML = '';
				}, 2300);
			},
			error: function (e, ajaxOptions, thrownError){
				alert('Ошибка');
			}
		});
	}
}

function update_order(cartIems = [], userId = 0) {
	    
	    let request_object = new Object;
	    
	    document.getElementById("confirm_loader").style.display = 'inline-block';
	    jQuery('#update_items_btn').addClass('disabled');

	    
	    setTimeout(function() {
               if(userId !== 0 && cartIems.length > 0) {
	        
        	        cart_items = cartIems.filter(item => item.access == 0);
        	        
									if(cart_items.length > 0) {

	                  jQuery.ajax({
	                		type: "POST",
	                		async: true, //Запрос синхронный
	                		url: "/content/cron/update_cart_price.php",
	                		dataType: "json",//Тип возвращаемого значения
	                		data: "cart_items="+encodeURIComponent(JSON.stringify(cart_items)),
	                        complete:function(){
	                            document.getElementById("confirm_loader").style.display = 'none';
	                            jQuery('#update_items_btn').removeClass('disabled');
	                        },
	                		success: function(answer)
	                		{
	                			if(answer.status == true)
	                			{
	            			        setTimeout(function() {
	                                    location.reload();
	                                }, 2000);
	                			}
	                			else
	                			{
	                			    console.log('answer', answer);
	                                alert("Серверная ошибка. Обратитесь к вашему менеджеру.");
	                			}
	                		}
	                	});

									}
									else
									{

										document.getElementById("confirm_loader").style.display = 'none';
	                  jQuery('#update_items_btn').removeClass('disabled');
									}
        	    
        	    }
        }, 2000)
        
    }
		
</script>
<!-------------------------------------------- End ГАРАЖ -------------------------------------------->