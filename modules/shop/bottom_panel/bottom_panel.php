<?php
/*
Модуль нижней панели (современный вид, PHP 5.4+, только мобильная версия)
+ Умный тост "Товар добавлен в корзину" (с наименованием, брендом, артикулом, сроком)
    - мобильная версия  → всплывает над нижней панелью
    - десктоп           → привязан к #shop_cart_notify_block (иконка корзины в шапке)
*/
defined('_ASTEXE_') or die('No access');

require_once($_SERVER["DOCUMENT_ROOT"]."/lib/inputmask/phone_mask.php");
require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");
$user_session = DP_User::getUserSession();

// ------------------------------------------------------------------------------------------------
// Текущий URL для подсветки активного пункта меню
// ------------------------------------------------------------------------------------------------
$dp_current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$dp_current_url = '/' . trim($dp_current_url, '/');

function dp_bottom_is_active($link_url)
{
    global $dp_current_url;
    $path = parse_url($link_url, PHP_URL_PATH);
    if (!$path) return false;
    $path = '/' . trim($path, '/');
    if ($path === '/') return $dp_current_url === '/';
    if ($dp_current_url === $path) return true;
    if (strpos($dp_current_url, $path . '/') === 0) return true;
    return false;
}

// ------------------------------------------------------------------------------------------------
// Рекурсивная функция получения линейного списка групп для бэкэнда
// ------------------------------------------------------------------------------------------------
function getBackendGroups($parent_group_id, $backend_groups_list)
{
    global $db_link;
    global $DP_Config;

    if ($parent_group_id == NULL)
    {
        $stmt = $db_link->prepare('SELECT * FROM `groups` WHERE `for_backend` = :for_backend;');
        $stmt->bindValue(':for_backend', 1);
        $stmt->execute();
        $group_for_backend_record = $stmt->fetch(PDO::FETCH_ASSOC);
        array_push($backend_groups_list, $group_for_backend_record["id"]);

        if ($group_for_backend_record["count"] == 0)
        {
            return $backend_groups_list;
        }
        else
        {
            return getBackendGroups($group_for_backend_record["id"], $backend_groups_list);
        }
    }
    else
    {
        $stmt = $db_link->prepare('SELECT * FROM `groups` WHERE `parent` = :parent;');
        $stmt->bindValue(':parent', $parent_group_id);
        $stmt->execute();
        while ($group_record = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            array_push($backend_groups_list, $group_record["id"]);
            if ($group_record["count"] > 0)
            {
                $backend_groups_list = getBackendGroups($group_record["id"], $backend_groups_list);
            }
        }
    }
    return $backend_groups_list;
}

// ------------------------------------------------------------------------------------------------
// Проверка доступа к редактированию текста URL (админ)
// ------------------------------------------------------------------------------------------------
$dp_can_edit_url_text = false;
$user_id = DP_User::getUserId();
if ($user_id > 0)
{
    $backend_groups_list = getBackendGroups(NULL, array());

    $user_groups_list = array();
    $stmt = $db_link->prepare('SELECT * FROM `users_groups_bind` WHERE `user_id` = :user_id;');
    $stmt->bindValue(':user_id', $user_id);
    $stmt->execute();
    while ($user_group_record = $stmt->fetch(PDO::FETCH_ASSOC))
    {
        array_push($user_groups_list, $user_group_record["group_id"]);
    }

    for ($i = 0; $i < count($backend_groups_list); $i++)
    {
        for ($j = 0; $j < count($user_groups_list); $j++)
        {
            if ($backend_groups_list[$i] == $user_groups_list[$j])
            {
                $dp_can_edit_url_text = true;
                break 2;
            }
        }
    }
}

// ------------------------------------------------------------------------------------------------
// Иконка и подпись пользователя
// ------------------------------------------------------------------------------------------------
$user_tab_caption = "Войти";
$user_tab_icon = 'fa-sign-in';
if (DP_User::getUserId() != 0)
{
    $user_tab_caption = "Профиль";
    $user_tab_icon = 'fa-user';
}
?>

<!-- ======================= СТИЛИ ======================= -->
<style>
.dp-bottom-panel {
    --grey-text: #6C7380;
    --active-text: #4294ff;
    --active-shadow: rgba(66, 148, 255, .35);
    position: fixed;
    left: 0; right: 0; bottom: 0;
    width: 100%;
    z-index: 10000000000;
    margin: 0; padding: 0;
    -webkit-tap-highlight-color: transparent;
}
.dp-bottom-panel * { box-sizing: border-box; }

.dp-bottom-panel__inner {
    padding: 6px 10px;
    background: #fdfdfd;
    -webkit-box-shadow: 0 -2px 12px -6px #6C7380;
    box-shadow: 0 -2px 12px -6px #6C7380;
    border-top: 1px solid #f0f1f3;
}
.dp-bottom-panel__menu {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    -ms-flex-pack: distribute;
    justify-content: space-around;
    margin: 0; padding: 0; list-style: none;
}
.dp-bottom-panel__button {
    position: relative;
    -webkit-box-flex: 1;
    -ms-flex: 1 1 auto;
    flex: 1 1 auto;
    max-width: 90px;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    justify-content: center;
    -webkit-box-align: end;
    -ms-flex-align: end;
    align-items: flex-end;
}
.dp-bottom-panel__link {
    position: relative;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    -ms-flex-direction: column;
    flex-direction: column;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none !important;
    padding: 4px 6px;
    min-width: 50px;
    color: var(--grey-text);
    -webkit-transition: color .2s;
    -o-transition: color .2s;
    transition: color .2s;
    outline: none !important;
}
.dp-bottom-panel__link:hover,
.dp-bottom-panel__link:focus,
.dp-bottom-panel__link:active {
    text-decoration: none !important;
    color: var(--active-text);
    outline: none !important;
}
.dp-bottom-panel__iconText {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    -ms-flex-direction: column;
    flex-direction: column;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
}
.dp-bottom-panel__icon {
    position: relative;
    display: inline-block;
    width: 26px;
    height: 26px;
    line-height: 26px;
    text-align: center;
}
.dp-bottom-panel__icon > i {
    font-size: 22px;
    line-height: 26px;
    color: var(--grey-text);
    -webkit-transition: color .2s;
    -o-transition: color .2s;
    transition: color .2s;
}
.dp-bottom-panel__link:hover .dp-bottom-panel__icon > i {
    color: var(--active-text);
}
.dp-bottom-panel__text {
    color: var(--grey-text);
    font-size: 11px;
    line-height: 14px;
    margin: 2px 0 0;
    text-align: center;
    white-space: nowrap;
    -webkit-transition: color .2s;
    -o-transition: color .2s;
    transition: color .2s;
}

.dp-bottom-panel__button.active .dp-bottom-panel__text,
.dp-bottom-panel__button.active .dp-bottom-panel__icon > i {
    color: var(--active-text);
}
.dp-bottom-panel__button.active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    margin-left: -10px;
    width: 20px;
    height: 2px;
    background: var(--active-text);
    border-radius: 0 0 2px 2px;
    -webkit-box-shadow: 0 0 8px var(--active-shadow);
    box-shadow: 0 0 8px var(--active-shadow);
}

.dp-bottom-panel__badge {
    position: absolute;
    top: -2px;
    right: -8px;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 8px;
    background: var(--active-text);
    color: #fff !important;
    font-size: 10px;
    line-height: 16px;
    font-weight: 600;
    text-align: center;
    z-index: 2;
    pointer-events: none;
    -webkit-box-shadow: 0 2px 6px var(--active-shadow);
    box-shadow: 0 2px 6px var(--active-shadow);
}
.dp-bottom-panel__badge.badge-default { display: none !important; }
.dp-bottom-panel__badge.badge-primary { display: inline-block !important; }

.dp-bottom-panel #cart_items_sum {
    color: var(--active-text);
    font-size: 10px;
    line-height: 12px;
    margin-top: 1px;
    white-space: nowrap;
}
.dp-bottom-panel #cart_items_sum:empty { display: none; }
.dp-bottom-panel #dp_user_balance {
    color: var(--active-text);
    font-size: 10px;
    line-height: 12px;
    margin-top: 1px;
    white-space: nowrap;
}
.dp-bottom-panel #dp_user_balance:empty { display: none; }

.dp-bottom-panel #mark_popup_added,
.dp-bottom-panel #mark_bookmarks_popup_added,
.dp-bottom-panel #mark_compare_popup_added {
    display: none !important;
}

.dp-bottom-panel .new-header-user-box {
    position: relative;
    width: 100%;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    justify-content: center;
}
.dp-bottom-panel .new-header-user-box .dropdown-toggle {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    -ms-flex-direction: column;
    flex-direction: column;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    cursor: pointer;
}
.dp-bottom-panel .new-header-user-box .dropdown-toggle > a {
    color: inherit;
    text-decoration: none !important;
}
.dp-bottom-panel .dropdown-menu {
    top: auto !important;
    bottom: 100% !important;
    left: auto !important;
    right: 0 !important;
    margin: 0 0 8px 0 !important;
    min-width: 260px;
    max-width: calc(100vw - 20px);
    border-radius: 8px;
    border: 1px solid #eee;
    -webkit-box-shadow: 0 6px 24px rgba(0,0,0,.12);
    box-shadow: 0 6px 24px rgba(0,0,0,.12);
    z-index: 100000;
}

/* ============================================================
   ВСПЛЫВАЮЩЕЕ ОКНО "ТОВАР ДОБАВЛЕН В КОРЗИНУ"
   ============================================================ */
.dp-cart-toast {
    position: fixed;
    z-index: 2147483648;

    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    gap: 12px;

    min-width: 280px;
    max-width: 360px;
    padding: 12px 38px 12px 14px;

    background: #fff;
    border-radius: 14px;
    -webkit-box-shadow: 0 12px 40px rgba(0, 0, 0, .18), 0 0 0 1px rgba(0, 0, 0, .04);
    box-shadow: 0 12px 40px rgba(0, 0, 0, .18), 0 0 0 1px rgba(0, 0, 0, .04);

    opacity: 0;
    pointer-events: none;
    -webkit-transition: opacity .3s ease, -webkit-transform .3s cubic-bezier(.16, 1, .3, 1);
    transition: opacity .3s ease, -webkit-transform .3s cubic-bezier(.16, 1, .3, 1);
    -o-transition: opacity .3s ease, transform .3s cubic-bezier(.16, 1, .3, 1);
    transition: opacity .3s ease, transform .3s cubic-bezier(.16, 1, .3, 1);
    transition: opacity .3s ease, transform .3s cubic-bezier(.16, 1, .3, 1), -webkit-transform .3s cubic-bezier(.16, 1, .3, 1);
}
@media (max-width: 991px) {
    .dp-cart-toast {
        left: 50%;
        bottom: 90px;
        -webkit-transform: translate(-50%, 30px) scale(.95);
        -ms-transform: translate(-50%, 30px) scale(.95);
        transform: translate(-50%, 30px) scale(.95);
    }
    .dp-cart-toast--show {
        opacity: 1;
        pointer-events: auto;
        -webkit-transform: translate(-50%, 0) scale(1);
        -ms-transform: translate(-50%, 0) scale(1);
        transform: translate(-50%, 0) scale(1);
    }
}
@media (min-width: 992px) {
    .dp-cart-toast {
        -webkit-transform: translateY(-12px) scale(.95);
        -ms-transform: translateY(-12px) scale(.95);
        transform: translateY(-12px) scale(.95);
        -webkit-transform-origin: top right;
        -ms-transform-origin: top right;
        transform-origin: top right;
    }
    .dp-cart-toast--show {
        opacity: 1;
        pointer-events: auto;
        -webkit-transform: translateY(0) scale(1);
        -ms-transform: translateY(0) scale(1);
        transform: translateY(0) scale(1);
    }
}

.dp-cart-toast__icon {
    -ms-flex-negative: 0;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: -o-linear-gradient(315deg, #55a8ff 0%, #4294ff 100%);
    background: linear-gradient(135deg, #55a8ff 0%, #4294ff 100%);
    color: #fff;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    justify-content: center;
    font-size: 20px;
    -webkit-box-shadow: 0 6px 16px rgba(66, 148, 255, .45);
    box-shadow: 0 6px 16px rgba(66, 148, 255, .45);
    -webkit-animation: dpCartCheckIn .45s cubic-bezier(.16, 1.3, .3, 1) both;
    animation: dpCartCheckIn .45s cubic-bezier(.16, 1.3, .3, 1) both;
}
.dp-cart-toast__icon::before {
    content: "\f00c";
    font-family: FontAwesome;
    font-weight: normal;
    font-style: normal;
}

.dp-cart-toast__body {
    -webkit-box-flex: 1;
    -ms-flex: 1 1 auto;
    flex: 1 1 auto;
    min-width: 0;
}
.dp-cart-toast__title {
    font-size: 14px;
    font-weight: 600;
    color: #222;
    line-height: 18px;
}
.dp-cart-toast__link {
    display: inline-block;
    margin-top: 3px;
    font-size: 12px;
    font-weight: 500;
    color: #4294ff !important;
    text-decoration: none !important;
    -webkit-transition: opacity .15s;
    -o-transition: opacity .15s;
    transition: opacity .15s;
}
.dp-cart-toast__link:hover { opacity: .75; text-decoration: none !important; }
.dp-cart-toast__link::after {
    content: "\f105";
    font-family: FontAwesome;
    margin-left: 4px;
    font-size: 13px;
}

.dp-cart-toast__close {
    position: absolute;
    top: 6px;
    right: 8px;
    width: 24px;
    height: 24px;
    padding: 0;
    background: none;
    border: none;
    color: #b9bcc3;
    font-size: 18px;
    line-height: 24px;
    text-align: center;
    cursor: pointer;
    border-radius: 50%;
    -webkit-transition: color .15s, background-color .15s;
    -o-transition: color .15s, background-color .15s;
    transition: color .15s, background-color .15s;
}
.dp-cart-toast__close:hover {
    color: #6C7380;
    background-color: #f2f3f5;
}

/* ---------- Блок товара внутри тоста ---------- */
.dp-cart-toast__product {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #f0f1f3;
}
.dp-cart-toast__name {
    font-size: 13px;
    font-weight: 500;
    color: #2a2d33;
    line-height: 16px;
    margin-bottom: 3px;
    word-break: break-word;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.dp-cart-toast__meta {
    font-size: 11px;
    line-height: 14px;
    color: #9aa0a9;
    margin-bottom: 4px;
}
.dp-cart-toast__brand {
    font-weight: 600;
    color: #6C7380;
}
.dp-cart-toast__brand:not(:empty) + .dp-cart-toast__article:not(:empty)::before {
    content: '•';
    color: #c5c9cf;
    margin: 0 5px;
}
.dp-cart-toast__delivery {
    display: -webkit-inline-box;
    display: -ms-inline-flexbox;
    display: inline-flex;
    -webkit-box-align: center;
        -ms-flex-align: center;
            align-items: center;
    gap: 5px;
    padding: 3px 9px;
    margin-top: 2px;
    font-size: 11px;
    font-weight: 500;
    color: #4294ff;
    background: #eaf6ff;
    border-radius: 6px;
    line-height: 14px;
}
.dp-cart-toast__delivery i { font-size: 11px; }

@-webkit-keyframes dpCartCheckIn {
    0%   { -webkit-transform: scale(0.3); transform: scale(0.3); opacity: 0; }
    60%  { -webkit-transform: scale(1.15); transform: scale(1.15); opacity: 1; }
    100% { -webkit-transform: scale(1); transform: scale(1); opacity: 1; }
}
@keyframes dpCartCheckIn {
    0%   { -webkit-transform: scale(0.3); transform: scale(0.3); opacity: 0; }
    60%  { -webkit-transform: scale(1.15); transform: scale(1.15); opacity: 1; }
    100% { -webkit-transform: scale(1); transform: scale(1); opacity: 1; }
}

@media (max-width: 400px) {
    .dp-bottom-panel__link { min-width: 40px; padding: 4px; }
    .dp-bottom-panel__icon > i { font-size: 20px; }
    .dp-bottom-panel__text { font-size: 10px; }
    .dp-cart-toast { min-width: 260px; max-width: calc(100vw - 20px); bottom: 86px; }
}

/* Нижняя панель — только мобильная версия */
@media (min-width: 768px) {
    .dp-bottom-panel { display: none !important; }
}

/* ============================================================
   Сдвиг сторонних виджетов над нижней панелью (мобильная версия)
   ============================================================ */
@media (max-width: 768px) {
    #jvLabelWrap, #jcont {
        bottom: 76px !important;
        left: 35px !important;
        float: left;
        right: auto !important;
    }
    .wrap__HGZ4V.__jivoMobileButton {
        bottom: 60px !important;
        left: 2px !important;
        float: left;
        right: auto !important;
    }
    #back-top a {
        bottom: 77px !important;
    }
    #mobile_hide_user { display: none !important; }
    #mobile_hide_cart { display: none !important; }
}

/* ============================================================
   DROPDOWN ПРОФИЛЯ — современные стили
   ============================================================ */
.dp-bottom-panel .dropdown-menu.dropdown-login-box {
    padding: 0;
    overflow: hidden;
    min-width: 300px;
    border: none !important;
    border-radius: 16px !important;
    background: #fff;
    -webkit-box-shadow: 0 16px 48px rgba(0, 0, 0, .2), 0 0 0 1px rgba(0, 0, 0, .05);
    box-shadow: 0 16px 48px rgba(0, 0, 0, .2), 0 0 0 1px rgba(0, 0, 0, .05);
    margin: 0 0 12px 0 !important;
    z-index: 10000000002;
}
.dp-bottom-panel .dropdown-menu.dropdown-login-box.animated {
    -webkit-animation: dpDropUp .28s cubic-bezier(.16, 1, .3, 1) both !important;
    animation: dpDropUp .28s cubic-bezier(.16, 1, .3, 1) both !important;
}
@-webkit-keyframes dpDropUp {
    from { opacity: 0; -webkit-transform: translateY(12px) scale(.95); transform: translateY(12px) scale(.95); }
    to   { opacity: 1; -webkit-transform: translateY(0) scale(1); transform: translateY(0) scale(1); }
}
@keyframes dpDropUp {
    from { opacity: 0; -webkit-transform: translateY(12px) scale(.95); transform: translateY(12px) scale(.95); }
    to   { opacity: 1; -webkit-transform: translateY(0) scale(1); transform: translateY(0) scale(1); }
}

.dp-bottom-panel .dropdown-menu .panel-heading {
    background: -o-linear-gradient(315deg, #55a8ff 0%, #4294ff 100%) !important;
    background: linear-gradient(135deg, #55a8ff 0%, #4294ff 100%) !important;
    color: #fff !important;
    padding: 16px 20px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    letter-spacing: .3px;
    text-align: center !important;
    text-shadow: 0 1px 2px rgba(0,0,0,.1);
    border: none !important;
    border-radius: 0 !important;
    margin: 0 !important;
}
.dp-bottom-panel .dropdown-menu .panel-body {
    padding: 14px;
    max-height: 65vh;
    overflow-y: auto;
    background: #fff;
    color: #333;
    -webkit-overflow-scrolling: touch;
}
.dp-bottom-panel .dropdown-menu .panel-body::-webkit-scrollbar { width: 6px; }
.dp-bottom-panel .dropdown-menu .panel-body::-webkit-scrollbar-thumb {
    background: #dde2e8;
    border-radius: 3px;
}

.dp-bottom-panel .dropdown-menu .form-group {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-wrap: wrap;
        flex-wrap: wrap;
    gap: 8px;
    margin: 0 !important;
}
.dp-bottom-panel .dropdown-menu .btn-ar {
    -webkit-box-flex: 1;
    -ms-flex: 1 1 calc(50% - 4px);
    flex: 1 1 calc(50% - 4px);
    min-width: 120px;

    display: -webkit-inline-box;
    display: -ms-inline-flexbox;
    display: inline-flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    justify-content: center;
    gap: 6px;

    min-height: 44px;
    padding: 10px 12px;

    border: none !important;
    border-radius: 12px !important;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.15;
    text-align: center;
    text-decoration: none !important;
    white-space: normal;
    word-break: break-word;

    box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    transition: transform .15s ease, box-shadow .15s ease,
                background-color .15s ease, filter .15s ease;
    -webkit-transition: -webkit-transform .15s ease, -webkit-box-shadow .15s ease,
                        background-color .15s ease, -webkit-filter .15s ease;
    cursor: pointer;
}
.dp-bottom-panel .dropdown-menu .btn-ar:hover,
.dp-bottom-panel .dropdown-menu .btn-ar:focus {
    text-decoration: none !important;
    outline: none !important;
    -webkit-transform: translateY(-1px);
        -ms-transform: translateY(-1px);
            transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
    filter: brightness(1.04);
}
.dp-bottom-panel .dropdown-menu .btn-ar:active {
    -webkit-transform: translateY(0) scale(.98);
        -ms-transform: translateY(0) scale(.98);
            transform: translateY(0) scale(.98);
    box-shadow: 0 1px 2px rgba(0, 0, 0, .08);
}

.dp-bottom-panel .dropdown-menu .btn-success {
    background: -o-linear-gradient(315deg, #55a8ff 0%, #4294ff 100%) !important;
    background: linear-gradient(135deg, #55a8ff 0%, #4294ff 100%) !important;
    color: #fff !important;
}
.dp-bottom-panel .dropdown-menu .btn-success:hover {
    box-shadow: 0 8px 22px rgba(66, 148, 255, .38);
}
.dp-bottom-panel .dropdown-menu a[href^="/worker_salary"],
.dp-bottom-panel .dropdown-menu a[href*="admin_console"] {
    background: #eaf6ff !important;
    color: #2d7fea !important;
    box-shadow: 0 1px 2px rgba(66, 148, 255, .08);
}
.dp-bottom-panel .dropdown-menu a[href^="/worker_salary"]:hover,
.dp-bottom-panel .dropdown-menu a[href*="admin_console"]:hover {
    background: #dceeff !important;
    box-shadow: 0 6px 18px rgba(66, 148, 255, .2);
}
.dp-bottom-panel .dropdown-menu .btn_profile { flex-basis: 100%; }

.dp-bottom-panel .dropdown-menu .btn-warning {
    background: #f4f7fb !important;
    color: #4294ff !important;
    border: 1px solid #e5eaf1 !important;
}
.dp-bottom-panel .dropdown-menu .btn-warning:hover {
    background: #eaf1fa !important;
    color: #2d7fea !important;
    box-shadow: 0 6px 18px rgba(66, 148, 255, .15);
}

.dp-bottom-panel .dropdown-menu .btn_orders::before      { content: "\f0f6"; font-family: FontAwesome; }
.dp-bottom-panel .dropdown-menu .btn_return::before      { content: "\f0e2"; font-family: FontAwesome; }
.dp-bottom-panel .dropdown-menu .btn_cart::before        { content: "\f07a"; font-family: FontAwesome; }
.dp-bottom-panel .dropdown-menu .btn_garazh::before      { content: "\f1b9"; font-family: FontAwesome; }
.dp-bottom-panel .dropdown-menu .btn_autoservice::before { content: "\f013"; font-family: FontAwesome; }
.dp-bottom-panel .dropdown-menu .btn_requests::before    { content: "\f075"; font-family: FontAwesome; }
.dp-bottom-panel .dropdown-menu .btn_bloknot::before     { content: "\f044"; font-family: FontAwesome; }
.dp-bottom-panel .dropdown-menu .btn_balans::before      { content: "\f155"; font-family: FontAwesome; }
.dp-bottom-panel .dropdown-menu .btn_profile::before     { content: "\f007"; font-family: FontAwesome; }
.dp-bottom-panel .dropdown-menu .btn_exit::before        { content: "\f08b"; font-family: FontAwesome; }

.dp-bottom-panel .dropdown-menu .btn-danger {
    flex-basis: 100%;
    background: #fff1f1 !important;
    color: #e53e3e !important;
    border: 1px solid #ffd9d9 !important;
}
.dp-bottom-panel .dropdown-menu .btn-danger:hover {
    background: #ffe3e3 !important;
    color: #c53030 !important;
    box-shadow: 0 6px 18px rgba(229, 62, 62, .2);
}
.dp-bottom-panel .dropdown-menu .form-group > hr,
.dp-bottom-panel .dropdown-menu hr.dotted {
    flex-basis: 100%;
    width: 100%;
    height: 0;
    margin: 8px 0 !important;
    padding: 0;
    border: 0 !important;
    border-top: 1px dashed #e5eaf1 !important;
    background: none !important;
}
.dp-bottom-panel .dropdown-menu .form-group > .clearfix { display: none !important; }

.dp-bottom-panel .dropdown-menu input[type="text"],
.dp-bottom-panel .dropdown-menu input[type="email"],
.dp-bottom-panel .dropdown-menu input[type="password"],
.dp-bottom-panel .dropdown-menu input[type="tel"] {
    width: 100%;
    padding: 10px 14px;
    margin-bottom: 8px;
    border: 1px solid #e0e5eb;
    border-radius: 10px;
    font-size: 14px;
    background: #fafbfc;
    -webkit-transition: border-color .15s, box-shadow .15s, background-color .15s;
    -o-transition: border-color .15s, box-shadow .15s, background-color .15s;
    transition: border-color .15s, box-shadow .15s, background-color .15s;
}
.dp-bottom-panel .dropdown-menu input:focus {
    outline: none;
    background: #fff;
    border-color: #4294ff;
    box-shadow: 0 0 0 3px rgba(66, 148, 255, .15);
}

@media (max-width: 768px) {
    .dp-bottom-panel .dropdown-menu.dropdown-login-box {
        position: fixed !important;
        left: 50% !important;
        right: auto !important;
        top: auto !important;
        bottom: 76px !important;
        width: calc(100vw - 20px) !important;
        max-width: 380px;
        margin: 0 !important;
        -webkit-transform: translateX(-50%);
            -ms-transform: translateX(-50%);
                transform: translateX(-50%);
    }
    .dp-bottom-panel .dropdown-menu.dropdown-login-box.animated {
        -webkit-animation: dpDropUpCenter .28s cubic-bezier(.16, 1, .3, 1) both !important;
        animation: dpDropUpCenter .28s cubic-bezier(.16, 1, .3, 1) both !important;
    }
    @-webkit-keyframes dpDropUpCenter {
        from { opacity: 0; -webkit-transform: translate(-50%, 12px) scale(.95); transform: translate(-50%, 12px) scale(.95); }
        to   { opacity: 1; -webkit-transform: translate(-50%, 0) scale(1);     transform: translate(-50%, 0) scale(1); }
    }
    @keyframes dpDropUpCenter {
        from { opacity: 0; -webkit-transform: translate(-50%, 12px) scale(.95); transform: translate(-50%, 12px) scale(.95); }
        to   { opacity: 1; -webkit-transform: translate(-50%, 0) scale(1);     transform: translate(-50%, 0) scale(1); }
    }
}

/* ============================================================
   ФОРМА ВХОДА В DROPDOWN
   ============================================================ */
.dp-bottom-panel .dropdown-menu .login_form,
.dp-bottom-panel .dropdown-menu .login_form .col-lg-12,
.dp-bottom-panel .dropdown-menu .tab-pane > .row,
.dp-bottom-panel .dropdown-menu .tab-pane .col-lg-12 {
    margin: 0 !important;
    padding: 0 !important;
}
.dp-bottom-panel .dropdown-menu .nav-tabs {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    padding: 0 !important;
    margin: 0 !important;
    background: #f4f7fb !important;
    border-bottom: 1px solid #e5eaf1 !important;
    list-style: none;
}
.dp-bottom-panel .dropdown-menu .nav-tabs::before,
.dp-bottom-panel .dropdown-menu .nav-tabs::after { display: none !important; }
.dp-bottom-panel .dropdown-menu .nav-tabs > li {
    flex: 1 1 0;
    float: none !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    border-radius: 0 !important;
}
.dp-bottom-panel .dropdown-menu .nav-tabs > li > a {
    display: block;
    padding: 12px 8px !important;
    margin: 0 !important;
    font-size: 13px !important;
    font-weight: 500;
    color: #6C7380 !important;
    background: transparent !important;
    border: none !important;
    border-bottom: 2px solid transparent !important;
    border-radius: 0 !important;
    text-align: center;
    text-decoration: none !important;
    cursor: pointer;
    transition: color .2s, background-color .2s, border-color .2s;
}
.dp-bottom-panel .dropdown-menu .nav-tabs > li > a:hover {
    color: #4294ff !important;
    background: rgba(66, 148, 255, .06) !important;
}
.dp-bottom-panel .dropdown-menu .nav-tabs > li.active > a,
.dp-bottom-panel .dropdown-menu .nav-tabs > li.active > a:hover,
.dp-bottom-panel .dropdown-menu .nav-tabs > li.active > a:focus {
    color: #4294ff !important;
    background: #fff !important;
    border-bottom-color: #4294ff !important;
    font-weight: 600;
}
.dp-bottom-panel .dropdown-menu .tab-content { padding: 0 !important; background: #fff; }
.dp-bottom-panel .dropdown-menu .tab-pane { padding: 16px !important; }

.dp-bottom-panel .dropdown-menu .form-group > br,
.dp-bottom-panel .dropdown-menu .simple-register-wrapper > br { display: none !important; }

.dp-bottom-panel .dropdown-menu .form-group > .input-group,
.dp-bottom-panel .dropdown-menu .simple-register-wrapper > .input-group,
.dp-bottom-panel .dropdown-menu .simple-register-wrapper-body > .form-control,
.dp-bottom-panel .dropdown-menu .form-group > .checkbox {
    flex-basis: 100%;
    width: 100%;
    margin: 0 0 4px 0;
}
.dp-bottom-panel .dropdown-menu .input-group {
    display: flex;
    width: 100%;
    background: #fafbfc;
    border: 1px solid #e0e5eb;
    border-radius: 10px !important;
    overflow: hidden;
    transition: border-color .15s, box-shadow .15s, background-color .15s;
    padding: 0 !important;
}
.dp-bottom-panel .dropdown-menu .input-group:focus-within {
    background: #fff;
    border-color: #4294ff;
    box-shadow: 0 0 0 3px rgba(66, 148, 255, .15);
}
.dp-bottom-panel .dropdown-menu .input-group-addon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    flex-shrink: 0;
    padding: 0 !important;
    background: #f4f7fb !important;
    border: none !important;
    border-right: 1px solid #e5eaf1 !important;
    border-radius: 0 !important;
    color: #6C7380 !important;
    font-size: 14px;
}
.dp-bottom-panel .dropdown-menu .input-group-addon i,
.dp-bottom-panel .dropdown-menu .input-group-addon small {
    color: #6C7380 !important;
    font-size: 13px;
}
.dp-bottom-panel .dropdown-menu .form-control,
.dp-bottom-panel .dropdown-menu input[type="text"],
.dp-bottom-panel .dropdown-menu input[type="email"],
.dp-bottom-panel .dropdown-menu input[type="password"],
.dp-bottom-panel .dropdown-menu input[type="tel"] {
    width: 100%;
    flex: 1 1 auto;
    height: 44px !important;
    padding: 0 14px !important;
    background: transparent !important;
    border: none !important;
    color: #333 !important;
    font-size: 14px !important;
    line-height: 44px;
    border-radius: 0 !important;
    box-shadow: none !important;
    outline: none !important;
}
.dp-bottom-panel .dropdown-menu .form-control:focus,
.dp-bottom-panel .dropdown-menu input:focus {
    border-color: transparent !important;
    box-shadow: none !important;
    outline: none !important;
}
.dp-bottom-panel .dropdown-menu input::-webkit-input-placeholder { color: #b9bcc3; }
.dp-bottom-panel .dropdown-menu input::-moz-placeholder          { color: #b9bcc3; }
.dp-bottom-panel .dropdown-menu input:-ms-input-placeholder       { color: #b9bcc3; }
.dp-bottom-panel .dropdown-menu input::placeholder                { color: #b9bcc3; }

.dp-bottom-panel .dropdown-menu .phone-simple-register-check {
    background: #fafbfc !important;
    border: 1px solid #e0e5eb !important;
    border-radius: 10px !important;
    padding: 0 14px !important;
    height: 44px !important;
    font-size: 14px !important;
    margin-bottom: 10px !important;
    transition: border-color .15s, box-shadow .15s, background-color .15s;
}
.dp-bottom-panel .dropdown-menu .phone-simple-register-check:focus {
    background: #fff !important;
    border-color: #4294ff !important;
    box-shadow: 0 0 0 3px rgba(66, 148, 255, .15) !important;
}

/* ---------- Чекбокс "Запомнить меня" ---------- */
.dp-bottom-panel .dropdown-menu .checkbox {
    position: relative;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    margin: 6px 0 14px 0 !important;
    padding: 0 !important;
    background: none !important;
    flex-basis: 100% !important;
    width: 100% !important;
    min-height: 0 !important;
}
.dp-bottom-panel .dropdown-menu .checkbox input[type="checkbox"] {
    position: static !important;
    margin: 0 !important;
    padding: 0 !important;
    left: auto !important;
    top: auto !important;
    width: 18px !important;
    height: 18px !important;
    flex-shrink: 0 !important;
    accent-color: #4294ff;
    cursor: pointer;
    display: inline-block !important;
}
.dp-bottom-panel .dropdown-menu .checkbox label {
    padding: 0 !important;
    margin: 0 !important;
    font-size: 13px !important;
    font-weight: 400 !important;
    color: #6C7380 !important;
    line-height: 18px !important;
    cursor: pointer;
    user-select: none;
}
.dp-bottom-panel .dropdown-menu .checkbox label::before,
.dp-bottom-panel .dropdown-menu .checkbox label::after {
    display: none !important;
    content: none !important;
}

/* ---------- Кнопки формы логина ---------- */
.dp-bottom-panel .dropdown-menu .login_form .btn-success,
.dp-bottom-panel .dropdown-menu .btn_reg {
    background: #eaf6ff !important;
    color: #2d7fea !important;
    box-shadow: 0 1px 2px rgba(66, 148, 255, .08);
}
.dp-bottom-panel .dropdown-menu .login_form .btn-success:hover,
.dp-bottom-panel .dropdown-menu .btn_reg:hover {
    background: #dceeff !important;
    color: #1c6dd8 !important;
    box-shadow: 0 6px 18px rgba(66, 148, 255, .22);
}
.dp-bottom-panel .dropdown-menu .btn-primary {
    background: linear-gradient(135deg, #55a8ff 0%, #4294ff 100%) !important;
    color: #fff !important;
    box-shadow: 0 4px 14px rgba(66, 148, 255, .3);
}
.dp-bottom-panel .dropdown-menu .btn-primary:hover {
    box-shadow: 0 8px 22px rgba(66, 148, 255, .42);
}
.dp-bottom-panel .dropdown-menu .btn-default {
    background: #f4f7fb !important;
    color: #6C7380 !important;
    border: 1px solid #e5eaf1 !important;
}
.dp-bottom-panel .dropdown-menu .btn-default:hover {
    background: #eaf1fa !important;
    color: #4294ff !important;
    border-color: #cfe0f3 !important;
}
.dp-bottom-panel .dropdown-menu .btn_forget {
    flex-basis: 100% !important;
    width: 100%;
    background: #f4f7fb !important;
    color: #4294ff !important;
    border: 1px solid #e5eaf1 !important;
}
.dp-bottom-panel .dropdown-menu .btn_forget:hover {
    background: #eaf1fa !important;
    color: #2d7fea !important;
    box-shadow: 0 6px 18px rgba(66, 148, 255, .15);
}
.dp-bottom-panel .dropdown-menu .btn_auth {
    flex: 2 1 calc(60% - 4px);
    margin-right: 4px !important;
}
.dp-bottom-panel .dropdown-menu .btn_reg {
    flex: 1 1 calc(40% - 4px);
}
.dp-bottom-panel .dropdown-menu .simple-register-wrapper-body > div {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin: 10px 0;
}
.dp-bottom-panel .dropdown-menu .simple-register-wrapper-body > div > .btn-ar {
    flex: 1 1 calc(50% - 4px);
    margin: 0 !important;
}
.dp-bottom-panel .dropdown-menu #wrapper_code_smtp_header_top_tab_mob_3 .btn-primary,
.dp-bottom-panel .dropdown-menu #wrapper_code_smtp_header_top_tab_mob .btn-primary {
    width: 100%;
    display: block;
}
.dp-bottom-panel .dropdown-menu hr.dotted,
.dp-bottom-panel .dropdown-menu hr.margin-10 {
    flex-basis: 100%;
    width: 100%;
    height: 0;
    margin: 10px 0 !important;
    padding: 0 !important;
    border: 0 !important;
    border-top: 1px dashed #e5eaf1 !important;
    background: none !important;
    clear: both;
}

/* ---------- Соцсети ---------- */
.dp-bottom-panel .dropdown-menu .form-group > div[style*="text-align"],
.dp-bottom-panel .dropdown-menu .login_form .form-group > div {
    flex-basis: 100%;
    width: 100%;
    margin: 6px 0 0 !important;
    text-align: center !important;
}
.dp-bottom-panel .dropdown-menu a[href*="/social/"] {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    width: 42px !important;
    height: 42px !important;
    padding: 0 !important;
    margin: 0 5px !important;
    background: #f4f7fb;
    border-radius: 12px;
    vertical-align: middle;
    text-decoration: none !important;
    transition: background-color .15s, transform .15s, box-shadow .15s;
}
.dp-bottom-panel .dropdown-menu a[href*="/social/"]:hover {
    background: #eaf6ff;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(66, 148, 255, .22);
}
.dp-bottom-panel .dropdown-menu a[href*="/social/"] img {
    width: 24px !important;
    height: 24px !important;
    display: block;
}
.dp-bottom-panel .dropdown-menu a[href*="/social/"] + a { margin-left: 4px !important; }
.dp-bottom-panel .dropdown-menu .login_form .form-group > div[style*="text-align"] > div:first-child {
    font-size: 12px;
    color: #9aa0a9;
    margin-bottom: 8px;
    text-align: center;
}

/* ---------- Тексты-описания и таймер в табе "По коду" ---------- */
.dp-bottom-panel .dropdown-menu #description-message-header_top_tab_mob_3,
.dp-bottom-panel .dropdown-menu #description-message-header_top_tab_mob {
    margin: 8px 0 10px !important;
    font-size: 13px;
    color: #6C7380;
    line-height: 1.45;
    text-align: center;
}
.dp-bottom-panel .dropdown-menu #timer-send-code-header_top_tab_mob_3,
.dp-bottom-panel .dropdown-menu #timer-send-code-header_top_tab_mob {
    margin: 10px 0 0 !important;
    font-size: 12px;
    color: #9aa0a9;
    text-align: center;
}
.dp-bottom-panel .dropdown-menu #timer-send-code-header_top_tab_mob_3 span,
.dp-bottom-panel .dropdown-menu #timer-send-code-header_top_tab_mob span {
    color: #4294ff;
    font-weight: 600;
}
.dp-bottom-panel .dropdown-menu #timer-send-code-header_top_tab_mob_3 .btn-ar,
.dp-bottom-panel .dropdown-menu #timer-send-code-header_top_tab_mob .btn-ar {
    margin: 0 auto !important;
    min-height: 36px;
    font-size: 12px !important;
    flex: 0 0 auto !important;
}

.dp-bottom-panel .dropdown-menu .clearfix { display: none !important; }
.dp-bottom-panel .dropdown-menu .hidden { display: none !important; }

@media (max-width: 360px) {
    .dp-bottom-panel .dropdown-menu .tab-pane { padding: 12px !important; }
    .dp-bottom-panel .dropdown-menu .form-control,
    .dp-bottom-panel .dropdown-menu input { font-size: 13px !important; }
    .dp-bottom-panel .dropdown-menu .btn_auth,
    .dp-bottom-panel .dropdown-menu .btn_reg {
        font-size: 12px !important;
        padding: 10px 8px !important;
    }
    .dp-bottom-panel .dropdown-menu a[href*="/social/"] {
        width: 38px !important;
        height: 38px !important;
        margin: 0 3px !important;
    }
}

/* ============================================================
   ФИКСЫ ФОРМЫ ВХОДА
   ============================================================ */
.dp-bottom-panel .dropdown-menu .login_form .tab-content .input-group,
.dp-bottom-panel .dropdown-menu .login_form .tab-pane .input-group,
.dp-bottom-panel .dropdown-menu .tab-content .input-group {
    display: flex !important;
    background: #fafbfc !important;
    border: 1px solid #e0e5eb !important;
    border-radius: 10px !important;
    padding: 0 !important;
    width: 100% !important;
    box-shadow: none !important;
}
.dp-bottom-panel .dropdown-menu .login_form .tab-content .input-group:focus-within,
.dp-bottom-panel .dropdown-menu .tab-content .input-group:focus-within {
    background: #fff !important;
    border-color: #4294ff !important;
    box-shadow: 0 0 0 3px rgba(66, 148, 255, .15) !important;
}
.dp-bottom-panel .dropdown-menu .tab-content .form-control {
    background: transparent !important;
    border: none !important;
    color: #333 !important;
    height: 44px !important;
    padding: 0 14px !important;
    box-shadow: none !important;
    outline: none !important;
}
.dp-bottom-panel .dropdown-menu .tab-content .input-group-addon {
    background: #f4f7fb !important;
    border: none !important;
    border-right: 1px solid #e5eaf1 !important;
    color: #6C7380 !important;
}

.mobile-search-div.is-sticky {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,.15);
}
</style>

<!-- ======================= РАЗМЕТКА ======================= -->
<nav class="dp-bottom-panel" role="navigation">
    <div class="dp-bottom-panel__inner">
        <div class="container">
            <div class="dp-bottom-panel__menu">

                <?php $a = dp_bottom_is_active('/zapros-prodavczu') ? ' active' : ''; ?>
                <div class="dp-bottom-panel__button<?php echo $a; ?>">
                    <a href="/zapros-prodavczu" class="dp-bottom-panel__link">
                        <div class="dp-bottom-panel__iconText">
                            <div class="dp-bottom-panel__icon"><i class="fa fa-rocket"></i></div>
                            <div class="dp-bottom-panel__text">Помощь</div>
                        </div>
                    </a>
                </div>

                <?php $a = dp_bottom_is_active('/shop/sravneniya') ? ' active' : ''; ?>
                <div class="dp-bottom-panel__button<?php echo $a; ?>">
                    <span id="mark_compare_popup_added" class="hidden">Добавлено</span>
                    <a href="/shop/sravneniya" class="dp-bottom-panel__link">
                        <div class="dp-bottom-panel__iconText">
                            <div class="dp-bottom-panel__icon">
                                <i class="fa fa-copy fa-flip-horizontal"></i>
                                <span class="badge badge-default badge-round dp-bottom-panel__badge" id="compare_count"></span>
                            </div>
                            <div class="dp-bottom-panel__text">Сравнение</div>
                        </div>
                    </a>
                </div>

                <?php $a = dp_bottom_is_active('/shop/zakladki') ? ' active' : ''; ?>
                <div class="dp-bottom-panel__button<?php echo $a; ?>">
                    <span id="mark_bookmarks_popup_added" class="hidden">Добавлено</span>
                    <a href="/shop/zakladki" class="dp-bottom-panel__link">
                        <div class="dp-bottom-panel__iconText">
                            <div class="dp-bottom-panel__icon">
                                <i class="fa fa-bookmark-o"></i>
                                <span class="badge badge-default badge-round dp-bottom-panel__badge" id="bookmarks_count"></span>
                            </div>
                            <div class="dp-bottom-panel__text">Закладки</div>
                        </div>
                    </a>
                </div>

                <?php $a = dp_bottom_is_active('/shop/cart') ? ' active' : ''; ?>
                <div class="dp-bottom-panel__button<?php echo $a; ?>">
                    <span id="mark_popup_added" class="hidden">Добавлено</span>
                    <a href="/shop/cart" class="dp-bottom-panel__link">
                        <div class="dp-bottom-panel__iconText">
                            <div class="dp-bottom-panel__icon">
                                <i class="fa fa-shopping-cart"></i>
                                <span class="badge badge-default badge-round dp-bottom-panel__badge" id="cart_items_count"></span>
                            </div>
                            <div class="dp-bottom-panel__text">Корзина</div>
                            <span id="cart_items_sum"></span>
                        </div>
                    </a>
                </div>

                <?php $a = dp_bottom_is_active('/users/profile') ? ' active' : ''; ?>
                <div class="dp-bottom-panel__button<?php echo $a; ?>">
                    <div class="new-header-user-box dropdown" id="loginDropdown">
                        <span class="dropdown-toggle dp-bottom-panel__link" data-toggle="dropdown">
                            <div class="dp-bottom-panel__iconText">
                                <div class="dp-bottom-panel__icon">
                                    <i class="fa <?php echo $user_tab_icon; ?>"></i>
                                </div>
                                <div class="dp-bottom-panel__text"><?php echo htmlspecialchars($user_tab_caption); ?></div>
                                <span id="dp_user_balance" class="dp-bottom-panel__balance"></span>
                            </div>
                        </span>
                        <div class="dropdown-menu dropdown-menu-right dropdown-login-box animated flipCenter" id="onDropDownProc">
                            <?php
                            $login_form_postfix = "header_top_tab_mob";
                            require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
                            ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</nav>

<!-- ======================= ТОСТ "ТОВАР ДОБАВЛЕН В КОРЗИНУ" ======================= -->
<div class="dp-cart-toast" id="dpCartToast" role="alert" aria-live="polite">
    <div class="dp-cart-toast__icon"></div>
    <div class="dp-cart-toast__body">
        <div class="dp-cart-toast__title">Товар добавлен в корзину</div>
        <div class="dp-cart-toast__product" id="dpToastProduct" style="display:none;">
            <div class="dp-cart-toast__name" id="dpToastName"></div>
            <div class="dp-cart-toast__meta">
                <span class="dp-cart-toast__brand" id="dpToastBrand"></span>
                <span class="dp-cart-toast__article" id="dpToastArticle"></span>
            </div>
            <div class="dp-cart-toast__delivery" id="dpToastDelivery" style="display:none;">
                <i class="fa fa-clock-o"></i>
                <span id="dpToastTime"></span>
            </div>
        </div>
        <a href="/shop/cart" class="dp-cart-toast__link">Перейти в корзину</a>
    </div>
    <button type="button" class="dp-cart-toast__close" aria-label="Закрыть" onclick="dpHideCartToast()">&times;</button>
</div>

<!-- ======================= JS ======================= -->
<script>
//--------------------------------------------
// Хранилище "последнего добавленного товара"
//--------------------------------------------
var dpLastAddedProduct = null;
var dpCartToastTimer = null;

//--------------------------------------------
// Тост корзины: позиционирование
//--------------------------------------------
function dpPositionCartToast()
{
    var toast = document.getElementById('dpCartToast');
    if (!toast) return;

    if (window.innerWidth < 992) {
        toast.style.left = '';
        toast.style.right = '';
        toast.style.top = '';
        toast.style.bottom = '';
        return;
    }

    var anchor = document.getElementById('shop_cart_notify_block');
    if (!anchor) {
        toast.style.left = 'auto';
        toast.style.right = '20px';
        toast.style.top = '80px';
        toast.style.bottom = 'auto';
        return;
    }

    var rect = anchor.getBoundingClientRect();
    toast.style.left = 'auto';
    toast.style.right = Math.max(10, window.innerWidth - rect.right) + 'px';
    toast.style.top = (rect.bottom + 12) + 'px';
    toast.style.bottom = 'auto';
}

//--------------------------------------------
// Заполнение полей тоста данными товара
//--------------------------------------------
function dpFillCartToast(data)
{
    data = data || dpLastAddedProduct || {};

    var $product = document.getElementById('dpToastProduct');
    var $name    = document.getElementById('dpToastName');
    var $brand   = document.getElementById('dpToastBrand');
    var $article = document.getElementById('dpToastArticle');
    var $deliv   = document.getElementById('dpToastDelivery');
    var $time    = document.getElementById('dpToastTime');
    if (!$product) return;

    var hasAny = data.name || data.brand || data.article
              || data.time_to_exe || data.time_to_exe_guaranteed;

    if (!hasAny) {
        $product.style.display = 'none';
        return;
    }
    $product.style.display = '';

    $name.textContent = data.name || '';
    $name.style.display = data.name ? '' : 'none';

    $brand.textContent = data.brand || '';
    $brand.style.display = data.brand ? '' : 'none';

    $article.textContent = data.article || '';
    $article.style.display = data.article ? '' : 'none';

    // Срок доставки: time_to_exe — time_to_exe_guaranteed
    var t1 = data.time_to_exe;
    var t2 = data.time_to_exe_guaranteed;
    var timeStr = '';
    if (t1 && t2) {
        timeStr = (String(t1) === String(t2)) ? String(t1) : (t1 + ' – ' + t2);
    } else if (t1) {
        timeStr = String(t1);
    } else if (t2) {
        timeStr = String(t2);
    }
    if (timeStr) timeStr = 'Срок: ' + timeStr;

    if (timeStr) {
        $time.textContent = timeStr;
        $deliv.style.display = '';
    } else {
        $deliv.style.display = 'none';
    }
}

//--------------------------------------------
// Показ/скрытие тоста
//--------------------------------------------
function dpShowCartToast(data)
{
    var toast = document.getElementById('dpCartToast');
    if (!toast) return;

    if (data && typeof data === 'object') {
        dpLastAddedProduct = data;
    }
    dpFillCartToast(dpLastAddedProduct);

    dpPositionCartToast();

    toast.classList.remove('dp-cart-toast--show');
    void toast.offsetWidth;
    toast.classList.add('dp-cart-toast--show');

    if (dpCartToastTimer) clearTimeout(dpCartToastTimer);
    dpCartToastTimer = setTimeout(function () { dpHideCartToast(); }, 5000);
}

function dpHideCartToast()
{
    var toast = document.getElementById('dpCartToast');
    if (!toast) return;
    toast.classList.remove('dp-cart-toast--show');
    if (dpCartToastTimer) {
        clearTimeout(dpCartToastTimer);
        dpCartToastTimer = null;
    }
}

// Пересчёт позиции тоста при resize/scroll, если он сейчас виден
(function () {
    function repositionIfVisible() {
        var toast = document.getElementById('dpCartToast');
        if (toast && toast.classList.contains('dp-cart-toast--show')) {
            dpPositionCartToast();
        }
    }
    window.addEventListener('resize', repositionIfVisible);
    window.addEventListener('scroll', repositionIfVisible, { passive: true });
})();

//--------------------------------------------
// Сравнение
function compareReview()
{
    var compare = getCookie('compare');
    if(compare == undefined) { compare = new Array(); }
    else { compare = JSON.parse(compare); }

    if(document.getElementById("compare_count")){
        if(compare.length == 0) {
            document.getElementById("compare_count").setAttribute("class", "badge badge-default badge-round dp-bottom-panel__badge");
        } else {
            document.getElementById("compare_count").setAttribute("class", "badge badge-primary badge-round dp-bottom-panel__badge");
        }
        document.getElementById("compare_count").innerHTML = compare.length;
    }
    if(document.getElementById("header_compare_count")){
        document.getElementById("header_compare_count").innerHTML = compare.length;
        if(compare.length == 0) {
            document.getElementById("header_compare_count").setAttribute("class", "hidden badge badge-default badge-round ");
        } else {
            document.getElementById("header_compare_count").setAttribute("class", "badge badge-primary badge-round");
        }
    }
}
function addToCompare(product_id, link)
{
    var compare = getCookie('compare');
    if(compare == undefined) { compare = new Array(); }
    else { compare = JSON.parse(compare); }

    compare.push(product_id);
    var date = new Date(new Date().getTime() + 15552000 * 1000);
    document.cookie = "compare="+JSON.stringify(compare)+"; path=/; expires=" + date.toUTCString();

    compareReview();

    link.innerHTML = "<i class=\"glyphicon glyphicon-duplicate\"></i> <span>В сравнениях</span>";
    link.setAttribute("onclick", "location = '/shop/sravneniya'; ");
    link.setAttribute("title", "На страницу сравнения товаров");
    link.blur();
}
function removeCompare(product_id, link)
{
    var compare = getCookie('compare');
    compare = JSON.parse(compare);
    for(var i=0; i < compare.length; i++) {
        if(compare[i] == product_id) { compare.splice(i,1); break; }
    }
    var date = new Date(new Date().getTime() + 15552000 * 1000);
    document.cookie = "compare="+JSON.stringify(compare)+"; path=/; expires=" + date.toUTCString();
    compareReview();
    for(var i=0; i < products_objects.length; i++) {
        if( parseInt(products_objects[i].id) == parseInt(product_id) ) {
            products_objects.splice(i,1); break;
        }
    }
    if(compare.length == 0 && document.getElementById("work_area")) {
        document.getElementById("work_area").innerHTML = "<p>Здесь Вы можете сравнивать товары по различным свойствам. Чтобы добавлять сюда товары для сравнения, нажимайте ссылку \"В сравнения\" рядом с блоками товаров в каталоге</p><p>Список товаров для сравнения пока пуст</p>";
        return;
    }
    allReview();
}
function showAdded_compare() {}
function hideAdded_compare() {}

//--------------------------------------------
// Закладки
function bookmarksReview()
{
    var bookmarks = getCookie('bookmarks');
    if(bookmarks == undefined) { bookmarks = new Array(); }
    else { bookmarks = JSON.parse(bookmarks); }

    if(document.getElementById("bookmarks_count")){
        if(bookmarks.length == 0) {
            document.getElementById("bookmarks_count").setAttribute("class", "badge badge-default badge-round dp-bottom-panel__badge");
        } else {
            document.getElementById("bookmarks_count").setAttribute("class", "badge badge-primary badge-round dp-bottom-panel__badge");
        }
        document.getElementById("bookmarks_count").innerHTML = bookmarks.length;
    }
    if(document.getElementById("header_bookmarks_count")){
        document.getElementById("header_bookmarks_count").innerHTML = bookmarks.length;
        if(bookmarks.length == 0) {
            document.getElementById("header_bookmarks_count").setAttribute("class", "hidden badge badge-default badge-round ");
        } else {
            document.getElementById("header_bookmarks_count").setAttribute("class", "badge badge-primary badge-round");
        }
    }
}
function addToBookmarks(product_id, link)
{
    var bookmarks = getCookie('bookmarks');
    if(bookmarks == undefined) { bookmarks = new Array(); }
    else { bookmarks = JSON.parse(bookmarks); }

    bookmarks.push(product_id);
    var date = new Date(new Date().getTime() + 15552000 * 1000);
    document.cookie = "bookmarks="+JSON.stringify(bookmarks)+"; path=/; expires=" + date.toUTCString();

    bookmarksReview();

    link.innerHTML = "<i class=\"fa fa-bookmark\"></i> <span>В закладках</span>";
    link.setAttribute("onclick", "location = '/shop/zakladki'; ");
    link.setAttribute("title", "Перейти в закладки");
    link.blur();
}
function removeBookmark(product_id, link)
{
    var bookmarks = getCookie('bookmarks');
    bookmarks = JSON.parse(bookmarks);
    for(var i=0; i < bookmarks.length; i++) {
        if(bookmarks[i] == product_id) { bookmarks.splice(i,1); break; }
    }
    var date = new Date(new Date().getTime() + 15552000 * 1000);
    document.cookie = "bookmarks="+JSON.stringify(bookmarks)+"; path=/; expires=" + date.toUTCString();
    bookmarksReview();

    var area = link.parentNode.parentNode.parentNode;
    area.removeChild(link.parentNode.parentNode);

    if(bookmarks.length == 0) {
        area.innerHTML = "<p>Чтобы добавлять сюда закладки, нажимайте ссылку \"В закладки\" рядом с блоками товаров в каталоге</p><p>Список Ваших закладок пока пуст</p>";
        if(document.getElementById("products_area_turning")){
            document.getElementById("products_area_turning").innerHTML = "";
        }
    }
}
function getCookie(name)
{
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}
function showAdded_bookmarks() {}
function hideAdded_bookmarks() {}

<?php if(isset($_COOKIE["session"])){ ?>
bookmarksReview();
compareReview();
<?php } ?>

//--------------------------------------------
// Корзина
function updateCartInfo()
{
    jQuery.ajax({
        type: "POST",
        async: true,
        url: "/content/shop/order_process/ajax_get_cart_info.php",
        dataType: "json",
        data: "csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
        success: function(answer)
        {
            if(document.getElementById("cart_items_sum")){
                document.getElementById("cart_items_sum").innerHTML = answer.cart_items_sum;
            }
            if(document.getElementById("cart_items_count")){
                document.getElementById("cart_items_count").innerHTML = answer.cart_items_count;
                if(answer.cart_items_count == 0) {
                    document.getElementById("cart_items_count").setAttribute("class", "badge badge-default badge-round dp-bottom-panel__badge");
                } else {
                    document.getElementById("cart_items_count").setAttribute("class", "badge badge-primary badge-round dp-bottom-panel__badge");
                }
            }
            if(document.getElementById("header_cart_items_sum")){
                document.getElementById("header_cart_items_sum").innerHTML = answer.cart_items_sum;
            }
            if(document.getElementById("header_cart_items_count")){
                document.getElementById("header_cart_items_count").innerHTML = answer.cart_items_count;
                <?php if($DP_Template->id == 63){ ?>
                if(answer.cart_items_count == 0) {
                    document.getElementById("header_cart_items_count").setAttribute("class", "hidden badge badge-default badge-round ");
                } else {
                    document.getElementById("header_cart_items_count").setAttribute("class", "badge badge-primary badge-round");
                }
                <?php } ?>
            }
            if(document.getElementById("header_cart_items_count_mobile")){
                document.getElementById("header_cart_items_count_mobile").innerHTML = answer.cart_items_count;
                if(answer.cart_items_count == 0) {
                    document.getElementById("header_cart_items_count_mobile").setAttribute("class", "hidden badge badge-default badge-round ");
                } else {
                    document.getElementById("header_cart_items_count_mobile").setAttribute("class", "badge badge-primary badge-round");
                }
            }
        }
    });
}

//--------------------------------------------
// Перенос баланса из десктопной шапки в нижнюю панель
//--------------------------------------------
function dpSyncUserBalance()
{
    var el = document.getElementById('dp_user_balance');
    if (!el) return;

    var src = document.querySelector('.user_balance');
    if (!src) { el.textContent = ''; return; }

    var text = (src.textContent || '').replace(/\s+/g, ' ').trim();
    el.textContent = text;
}

dpSyncUserBalance();
setTimeout(dpSyncUserBalance, 300);
setTimeout(dpSyncUserBalance, 1500);
window.addEventListener('resize', dpSyncUserBalance);

<?php if(isset($_COOKIE["session"])){ ?>
updateCartInfo();
<?php } ?>

//--------------------------------------------
// Хук, который вызывает внешний код при добавлении товара в корзину
//   showAdded()                 — с последними захваченными данными
//   showAdded({name, article, brand, time_to_exe, time_to_exe_guaranteed})
//--------------------------------------------
function showAdded(productData) {
    if (productData && typeof productData === 'object') {
        dpLastAddedProduct = productData;
    }
    dpShowCartToast();
}
function hideAdded() { dpHideCartToast(); }

//--------------------------------------------
// Захват данных товара в момент клика "в корзину"
// (fallback, если showAdded() вызывается без аргументов)
//--------------------------------------------
(function () {
    var CARD_SELECTORS = '.product-item, .product, .catalog-item, .tovar, .goods-item, [data-product-id]';

    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!target || !target.closest) return;

        var btn = target.closest(
            '[onclick*="addToCart"], [onclick*="add_to_cart"], [onclick*="toCart"],' +
            '.add-to-cart, .to-cart, [data-action="add-to-cart"]'
        );
        if (!btn) return;

        var card = btn.closest(CARD_SELECTORS);
        if (!card) return;

        var pick = function (selectors) {
            for (var i = 0; i < selectors.length; i++) {
                var el = card.querySelector(selectors[i]);
                if (el && el.textContent.trim()) return el.textContent.trim();
            }
            return '';
        };

        var data = {
            name:    card.getAttribute('data-name')
                  || pick(['[itemprop="name"]', '.product-name', '.tovar-name', '.name', 'h2', 'h3']),
            brand:   card.getAttribute('data-brand')
                  || card.getAttribute('data-manufacturer')
                  || pick(['[itemprop="brand"]', '.product-brand', '.manufacturer', '.brand', '.tovar-brand']),
            article: card.getAttribute('data-article')
                  || pick(['[itemprop="sku"]', '.product-article', '.article', '.sku', '.tovar-article']),
            time_to_exe:            card.getAttribute('data-time_to_exe')            || '',
            time_to_exe_guaranteed: card.getAttribute('data-time_to_exe_guaranteed') || ''
        };

        var inputMap = {
            'input[name="time_to_exe"]': 'time_to_exe',
            'input[name="time_to_exe_guaranteed"]': 'time_to_exe_guaranteed',
            'input[name="article"]': 'article',
            'input[name="manufacturer"]': 'brand'
        };
        for (var sel in inputMap) {
            if (!inputMap.hasOwnProperty(sel)) continue;
            var inp = card.querySelector(sel);
            if (inp && inp.value && !data[inputMap[sel]]) {
                data[inputMap[sel]] = inp.value;
            }
        }

        dpLastAddedProduct = data;
    }, true);
})();
</script>

<?php
if($DP_Template->id == 63){
// Панель доступна только для нового шаблона
?>
<script>
$(document).ready(function() {
    $('input[name="article"]').attr('autocomplete','off');

    $(document).mouseup(function (e)
    {
        var container = $(".search-simple-bar");
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            $('.search-simple-bar').remove();
        }
    });

    $('form').find('input').on("click input", function () {
        if($(this).attr('name') === 'article'){
            var request_object = new Object;
            request_object.value = $(this).val();

            var input = $(this);
            jQuery.ajax({
                type: "POST",
                async: true,
                url: "/content/shop/docpart/ajax_get_article_list.php",
                dataType: "json",
                data: "request_object="+encodeURIComponent(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer)
                {
                    if (!answer || typeof answer !== 'object') { $('.search-simple-bar').remove(); return; }
                    if (!answer.list || !Array.isArray(answer.list)) { $('.search-simple-bar').remove(); return; }
                    if (answer.list.length === 0) { $('.search-simple-bar').remove(); return; }

                    var html = '';
                    var currentSection = '';

                    for(var i = 0; i < answer.list.length; i++){
                        if (!answer.list[i]) continue;
                        if(answer.list[i].manufacturer == null) continue;

                        var url = '';
                        if(answer.list[i].product_id && answer.list[i].product_url) {
                            url = answer.list[i].product_url;
                        } else {
                            var manufacturer_alias = answer.list[i].manufacturer || '';
                            if(manufacturer_alias == '') {
                                manufacturer_alias = '<?php echo $DP_Config->chpu_search_config["level_2"]["mode_2"]["url"]; ?>';
                            }
                            manufacturer_alias = manufacturer_alias.split('/').join('<?php echo $DP_Config->chpu_search_config["slash_code"]; ?>');
                            url = '/<?php echo $DP_Config->chpu_search_config["level_1"]["url"]; ?>/'+manufacturer_alias+'/'+encodeURIComponent(answer.list[i].article || '');
                        }

                        var nameHtml = '';
                        if(answer.list[i].name != '' && answer.list[i].name != null){
                            nameHtml = '<div style="font-size: 12px; color: #999; word-break: break-word;">'+ answer.list[i].name +'</div>';
                        }

                        var dataSource = answer.list[i].source || 'other';
                        var section = '';
                        if(dataSource == 'catalog') { section = 'catalog'; }
                        else if(dataSource == 'catalog_no_stock') { section = 'catalog_no_stock'; }
                        else if(dataSource == 'history') { section = 'history'; }
                        else { section = 'other'; }

                        if(currentSection != section) {
                            html += '<tr class="section-divider"><td colspan="2">';
                            if(section == 'catalog') { html += '✅ Каталог (в наличии)'; }
                            else if(section == 'catalog_no_stock') { html += '📦 Каталог (под заказ)'; }
                            else if(section == 'history') { html += '🕐 История поиска'; }
                            else { html += '📌 Другие товары'; }
                            html += '</td></tr>';
                            currentSection = section;
                        }

                        var badgesHtml = '';
                        if(section == 'catalog' && answer.list[i].price) {
                            badgesHtml += ' <span class="badge badge-price">' + number_format(answer.list[i].price, 0, '.', ' ') + ' ₽</span>';
                        }

                        html += '<tr style="cursor:pointer;" data-source="' + dataSource + '" onClick="location=\''+url+'\'">';
                        html += '<td style="vertical-align: top;">';
                        html += '<div style="font-weight: 600; word-break: break-word;">' + (answer.list[i].article || '') + '</div>';
                        if(badgesHtml) { html += '<div class="badge-container">' + badgesHtml + '</div>'; }
                        html += '</td>';
                        html += '<td style="width:100%; vertical-align: top;">';
                        html += '<div style="font-weight: 500; color: #333;">' + (answer.list[i].manufacturer || '') + '</div>';
                        html += nameHtml;
                        html += '</td>';
                        html += '</tr>';
                    }

                    if (!html) { $('.search-simple-bar').remove(); return; }

                    html = '<div class="search-simple-bar-content"><div class="table-div"><table class="table">'+html+'</table></div></div>';
                    html += '<div style="border-top: 1px solid #f5f5f5;" class="hidden-md hidden-lg search-simple-bar-footer"><a style="border: 1px solid #f5f5f5; padding: 5px 15px; display: inline-block; border-radius: 6px; line-height: 20px; margin: 5px; font-size: 14px; color: #555; opacity: 1;" class="close" onClick="$(\'.search-simple-bar\').remove();">Отмена</a></div>';

                    $('.search-simple-bar').remove();
                    input.after($('<div>', {class: 'search-simple-bar'}));
                    $('.search-simple-bar').html(html);
                },
                error: function (e, ajaxOptions, thrownError){
                    console.log('Ошибка: '+ e.status +' - '+ thrownError);
                    $('.search-simple-bar').remove();
                    return;
                }
            });
        }
    });
});

function number_format(number, decimals, dec_point, thousands_sep) {
    if (number === undefined || number === null || isNaN(number)) return '0';
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}
</script>
<script>
jQuery(function($){
    $(document).on('click.bs.dropdown', function(e){ });
    $(document).on('click', '.dp-bottom-panel .dropdown-menu', function (e) {
        e.stopPropagation();
    });
    $(document).on('click', '.dp-bottom-panel .dropdown-menu .nav-tabs a', function (e) {
        e.stopPropagation();
    });
    $(document).on('click', '.dp-bottom-panel .dropdown-menu .nav-tabs a[data-toggle="tab"]', function (e) {
        e.preventDefault();
        var $this = $(this);
        var target = $this.attr('href');
        if (!target) return;
        $this.closest('.nav-tabs').find('li').removeClass('active');
        $this.closest('li').addClass('active');
        var $content = $this.closest('.login_form').find('.tab-content');
        $content.find('.tab-pane').removeClass('active');
        $content.find(target).addClass('active');
    });
});
</script>

<script>
(function () {
    var el = document.querySelector('.mobile-search-div');
    if (!el) return;

    var offsetTop = el.offsetTop;
    var placeholder = document.createElement('div');
    placeholder.style.display = 'none';
    el.parentNode.insertBefore(placeholder, el);

    function onScroll() {
        var scrolled = window.pageYOffset || document.documentElement.scrollTop;

        if (scrolled >= offsetTop) {
            if (!el.classList.contains('is-sticky')) {
                var h = el.offsetHeight;
                placeholder.style.height = h + 'px';
                placeholder.style.display = 'block';
                el.classList.add('is-sticky');
            }
        } else {
            if (el.classList.contains('is-sticky')) {
                el.classList.remove('is-sticky');
                placeholder.style.display = 'none';
            }
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', function () {
        el.classList.remove('is-sticky');
        placeholder.style.display = 'none';
        offsetTop = el.offsetTop;
        onScroll();
    });
})();
</script>
<?php
}
?> 