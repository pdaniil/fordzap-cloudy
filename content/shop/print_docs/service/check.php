<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

function orderHash($orderId, $orderTime)
{
    global $DP_Config;
    return hash('sha256', $orderId . $orderTime . $DP_Config->secret_succession);
}

function encrypt($data)
{
    global $DP_Config;
    return openssl_encrypt($data,"AES-128-ECB", $DP_Config->secret_succession);
}
function decrypt($data)
{
    global $DP_Config;
    return openssl_decrypt($data,"AES-128-ECB", $DP_Config->secret_succession);
}

function encryptItems(array $data)
{
    return encrypt(json_encode($data));
}
function decryptItems($data)
{
    return @json_decode(decrypt($data), true);
}

if(defined('_INTASK_')){
    return; // Выходим если идет подключение файла к коду сайта
}

if(!function_exists('get_date')){
    function get_date($unix_time)
    {
        $month = array("января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря");
        return date("d ", $unix_time).$month[(int)date("m", $unix_time)-1].date(" Y", $unix_time);
    }
}
if(!function_exists('print_price')){
    function print_price($price)
    {
        return number_format($price, 2, ',', ' ');
    }
}
if(!function_exists('get_user_str_by_user_profile_json_builder')){
    function get_user_str_by_user_profile_json_builder($user_id, $user_profile_json_format)
    {
        if($user_id == 0)
        {
            return "";
        }
        $user_profile = DP_User::getUserProfileById($user_id);
        if( !isset($user_profile_json_format["reg_variant_".$user_profile["reg_variant"]]) )
        {
            return "Не задан формат";
        }
        $user_profile_json_format = $user_profile_json_format["reg_variant_".$user_profile["reg_variant"]];
        $str = "";
        for( $i=0 ; $i < count($user_profile_json_format) ; $i++ )
        {
            if( $i > 0 )
            {
                $str = $str." ";
            }
            $str = $str.$user_profile[$user_profile_json_format[$i].""];
        }
        return $str;
    }
}


// ------------------------------------------------------------------------
//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
// ------------------------------------------------------------------------
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	$db_link->query("SET NAMES utf8;");
} catch (PDOException $e){
    header('Location: /');
}
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");

$enableDiscountColumn = true; // true если есть колонка discount в таблице shop_orders_items - скидка на позиции

$checkId = str_replace("/check/", '', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
$order = false;
$orderId = 0;
$items = array();
$orderItems = array();
$checkHash = '';
$orderHash = '';
$showControl = DP_User::isAdmin() ? true : false;
$isCart = false;
$isOrderItems = false;
if(mb_strpos($checkId, '-') === false){
    if(is_numeric($checkId)){
        $orderId = (int)$checkId; // Если число, значит ID заказа передано
    }else{
        // Тут скорее всего чек корзины
        $items = decryptItems($checkId);
        if(!is_array($items)){
            header('Location: /'); // Некорректная зашифрованная строка
        }
        //Определяем это товарный чек заказа с определенными позициями или корзина
        if(isset($items['created']) && isset($items['records'])){
            $isCart = true;
        }else{
            $items = array_values($items);
            $orderTmpQuery = $db_link->prepare("SELECT order_id FROM `shop_orders_items` WHERE id = ?");
            $orderTmp = $orderTmpQuery->execute(array((int)$items[0])) ? $orderTmpQuery->fetch(PDO::FETCH_ASSOC) : false;
            if($orderTmp){
                $orderId = $orderTmp['order_id'];
                $isOrderItems = true;
            }
        }
    }
}else{
    $orderInfo = explode("-", $checkId);
    if(count($orderInfo) > 1){
        $orderId = (int) array_shift($orderInfo); // ID заказа
        $checkHash = implode("-", $orderInfo); // Остальная часть в хеш, если есть после хеша еще что-то, то ссылка проверку не пройдет
    }
}

if($orderId){
    //Подстрока с условиями фильтрования статусов позиций, которые не участвуют в ценовых расчетах
    $WHERE_statuses_not_count = "";
    for($i=0; $i<count($orders_items_statuses_not_count); $i++)
    {
        $WHERE_statuses_not_count .= " AND `status` != ".(int)$orders_items_statuses_not_count[$i];
    }
    $INCOME_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `order_id` = ?), 0)";
    $ISSUE_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `order_id` = ?),0)";
    //Баланс клиента
    $office_SQL = "";
    $office_SQL_values = array();
    if( isset( $DP_Config->wholesaler ) )
    {
        $office_SQL = " AND `office_id` = (SELECT `office_id` FROM `shop_orders` WHERE `id` = ?) ";
        $office_SQL_values = array($orderId);
    }
    $INCOME_USER_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `user_id` = `shop_orders`.`user_id` ".$office_SQL." ), 0)";
    $ISSUE_USER_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `user_id` = `shop_orders`.`user_id` ".$office_SQL." ),0)";
    $orderQuery = $db_link->prepare("SELECT *, CAST( ($ISSUE_SQL - $INCOME_SQL) AS DECIMAL(8,2) ) AS `paid_sum`, CAST( ($INCOME_USER_SQL - $ISSUE_USER_SQL) AS DECIMAL(8,2) ) AS `customer_balance`, CAST( ( (SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) - ($ISSUE_SQL - $INCOME_SQL) ) AS DECIMAL(8,2) )  AS `paid_left` FROM `shop_orders` WHERE `id` = ?;");
    if($enableDiscountColumn){
        $orderQuery = $db_link->prepare("SELECT *, CAST( ($ISSUE_SQL - $INCOME_SQL) AS DECIMAL(8,2) ) AS `paid_sum`, CAST( ($INCOME_USER_SQL - $ISSUE_USER_SQL) AS DECIMAL(8,2) ) AS `customer_balance`, CAST( ( (SELECT SUM(`price`*`count_need`-`price`*`count_need`*`discount`/100) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) - ($ISSUE_SQL - $INCOME_SQL) ) AS DECIMAL(8,2) )  AS `paid_left` FROM `shop_orders` WHERE `id` = ?;");
    }
    $order = $orderQuery->execute( array_merge( array($orderId, $orderId, $orderId, $orderId, $orderId), $office_SQL_values)) ? $orderQuery->fetch(PDO::FETCH_ASSOC) : false;
    if($order){
        $orderId = $order['id'];
        $orderHash = orderHash($order['id'], $order['time']);
        if($orderHash !== $checkHash){
            if(!DP_User::isAdmin()){
                $orderHash = '';
            }
        }
    }
}

$checkUrl = $DP_Config->domain_path . 'check/' . ($order ? $orderId . '-' . $orderHash : encryptItems($items));

if($order){
    $orderItemsQuery = $db_link->prepare("SELECT *, (CONCAT(`t2_manufacturer`, ' ', `t2_article`, ' ', `t2_name`)) AS `product_name`, `price`*`count_need` as `price_sum` FROM `shop_orders_items` WHERE `order_id` = ? AND `status` IN (SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `count_flag` = 1);");
    if($enableDiscountColumn){
        $orderItemsQuery = $db_link->prepare("SELECT *, (CONCAT(`t2_manufacturer`, ' ', `t2_article`, ' ', `t2_name`)) AS `product_name`, `price`*`count_need` as `price_sum`, CAST(`price`*`count_need`*`discount`/100 as DECIMAL(10,2)) as `price_discount`, CAST(`price`*`count_need`-`price`*`count_need`*`discount`/100 as DECIMAL(10,2)) as `price_sum_discount` FROM `shop_orders_items` WHERE `order_id` = ? AND `status` IN (SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `count_flag` = 1);");
    }
    $orderItems = $orderItemsQuery->execute(array($order['id'])) ? $orderItemsQuery->fetchAll(PDO::FETCH_ASSOC) : array();
    if($isOrderItems){
        $itemsList = [];
        foreach($orderItems as $orderItem){
            if(in_array($orderItem['id'], $items)){
                $itemsList[] = $orderItem;
            }
        }
        $orderItems = $itemsList;
    }
    $items = $orderItems;
}elseif(isset($items['created']) && isset($items['records'])){    
    $cartRecords = array();
    $cartRecordsIds = array();
    foreach($items['records'] as $cartRecordId){
        $cartRecordsIds[] = (int)$cartRecordId;
    }
    //Получаем содержимое корзины из базы данных
    $cartRecordQuery = $db_link->prepare('SELECT *, (`price`*`count_need`) as `price_sum` FROM `shop_carts` WHERE id IN ('.implode(",", $cartRecordsIds).')');
    if($cartRecordQuery->execute()){
        while($cartRecord = $cartRecordQuery->fetch(PDO::FETCH_ASSOC) ){
            $cartRecords[] = $cartRecord;
        }
    }
    if(count($cartRecords) !== count($items['records'])){
        header('Location: /'); // Если позиций нет в корзине
    }
    $items['records'] = $cartRecords;
}

if(!$order){
    if($isCart && isset($items['office_id'])){
        $officeQuery = $db_link->prepare("SELECT * FROM shop_offices WHERE id=?");
        $office = $officeQuery->execute(array($items['office_id'])) ? $officeQuery->fetch(PDO::FETCH_ASSOC) : false;
    }else{
        $officeQuery = $db_link->prepare("SELECT * FROM shop_offices ORDER BY id ASC LIMIT 1");
        $office = $officeQuery->execute() ? $officeQuery->fetch(PDO::FETCH_ASSOC) : false;
    }
}else{
    $officeQuery = $db_link->prepare("SELECT * FROM shop_offices WHERE id=?");
    $office = $order && $officeQuery->execute(array($order['office_id'])) ? $officeQuery->fetch(PDO::FETCH_ASSOC) : false;
}

$officeAddress = array();
if($office){
    if($office['city']){
        $officeAddress[] = $office['city'];
    }
    if($office['address']){
        $officeAddress[] = $office['address'];
    }
}

$officeAddress = implode(", ", $officeAddress);

$officePhone = str_replace("<br>", ", ", $office['phone']);

$docQuery = $db_link->prepare("SELECT * FROM `shop_print_docs` WHERE `name` = ?;");
$doc = $docQuery->execute(array($order ? ($isOrderItems ? 'sales_items' : 'sales_receipt') : 'user_cart')) ? $docQuery->fetch(PDO::FETCH_ASSOC) : false;

if(($order && (!$orderHash || !$office)) || !$doc){
    //header('Location: /');
}

$parametersDescription = json_decode($doc["parameters_description"], true);
$parametersValues = json_decode($doc["parameters_values"], true);
$officeLogo = 'https://fordzap.com/content/files/%D0%A1%D0%BD%D0%B8%D0%BC%D0%BE%D0%BA%20%D1%8D%D0%BA%D1%80%D0%B0%D0%BD%D0%B0%202024-02-01%20223453.png?v=123';
$clientName = $order ? get_user_str_by_user_profile_json_builder($order['user_id'], $parametersValues['customer']) : '';
if($isOrderItems){
    $paidSum = 0;
    $paidLeft = 0;
}else{
    $paidSum = $order ? $order['paid_sum'] : 0;
    $paidLeft = $order ? $order['paid_left'] : 0;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title><?=($isCart ? 'Корзина' : 'Чек')?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <?if($showControl):?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/6.3.7/tippy.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/6.3.7/tippy-bundle.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <?endif;?>
    <style>
        @import url(https://fonts.googleapis.com/css?family=PT+Sans:400,700&subset=latin,latin-ext,cyrillic);
        @import url(https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap);
        table{
            margin-bottom: 0px!important;
            border: none;
            border-color: #dee2e6;
        }
        html, body{
            font-family: 'Inter', 'PT Sans', sans-serif;
            background-color: #f0f7f9;
            color: rgba(0, 0, 0, 0.87);
            font-size: 14px;
        }
        #check-header-content{
            background: white;
        }
        #chc-list{
            display: flex;
        }
        #chc-list .left, #chc-list .right{
            padding: 20px 0px;
        }
        #chc-list .left{
            flex: 0 0 40%;
            width: 40%;
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            padding-right: 30px;;
        }
        #chc-list .left, #chc-list .right{
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        #chc-list .right{
            flex: 0 0 60%;
            width: 60%;
            padding-left: 30px;
        }
        .check-content-wrapper{
            -webkit-box-shadow: 4px 4px 44px 8px rgba(34, 60, 80, 0.2);
            -moz-box-shadow: 4px 4px 44px 8px rgba(34, 60, 80, 0.2);
            box-shadow: 4px 4px 44px 8px rgba(34, 60, 80, 0.2);
        }
        .check-content{
            background: white;
            border: 1px solid #D3D3D3!important;
        }
        .shop-info{
            background: #f6f8f9;
            padding: 24px 32px;
            display: flex;
            position: relative;
        }
        .shop-name-content{
            flex-direction: column;
            gap: 5px;
            margin-right: calc(108px + 50px);
        }
        .shop-name{
            font-weight: 700;
            font-size: 16px;
        }
        .shop-address, .shop-phone{
            color: rgba(0, 0, 0, 0.56);
            font-size: 14px;
        }
        .shop-logo{
            position: absolute;
            right: 50px;
            border: 4px solid #f6f8f9;
            width: 108px;
            height: 108px;
            border-radius: 56px;
            background: white;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        @media screen (max-width: 767px){
            .shop-logo{
                right: 15px;
            }
            .shop-name-content{
                margin-right: calc(108px + 15px);
            }
        }
        .shop-logo img{
            width: 100%;
        }
        .check-header{
            font-size: 22px;
            line-height: 120%;
            padding: 30px 30px 10px 30px;
            text-align: center;
        }
        .check-client{
            font-size: 16px;
            padding: 0px 30px;
        }
        .check-table{
            padding: 15px 30px 15px 30px;
        }
        .background-white{
            background: white;
        }
        .check-table table td, .check-table table th{
            padding-left: 0px;
            padding-right: 0px;
        }
        .check-table tfoot td{
            padding: 2px 0px;
            border-bottom: none;
            font-weight: bold;
        }
        .check-table table th:last-child, .check-table table td:last-child{
            text-align: right;
        }
        .check-table table tr:last-child td{
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        .check-signature{
            padding: 30px;
        }
        .check-info, .check-sub-info{
            padding: 0px 30px;
            text-align: center;
            font-size: 14px;
            padding-top: 15px;
            background: #f6f8f9;
        }
        .check-info{
            padding: 15px 30px 30px 30px;
        }
        .check-signature{
            display: flex;
            justify-content: space-between;
        }
        .check-signature div{
            width: 200px;
            border-top: 1px solid black;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?if($showControl):?>
    <div id="check-header-content" class="container-fluid no-print">
        <div class="container">
            <div class="row">
                <div id="chc-list" class="col-12 col-lg-8 offset-lg-2">
                    <div class="left">
                        <div>
                            <?=($isCart ? 'Корзина готова': 'Чек готов')?>
                            <br>
                            Давайте покажем клиенту
                        </div>
                        <div class="d-flex flex-row gap-2">
                            <button id="btn-print" class="btn btn-secondary">
                                <i class="fas fa-print"></i> Печать
                            </button>
                        </div>
                    </div>
                    <div class="right">
                        <div class="fw-normal">
                            Или можно отправить клиенту
                            <br>
                            ссылку на страницу <?=($isCart ? 'корзины': 'чека')?>
                        </div>
                        <div class="d-flex flex-row gap-2 justify-content-between">
                            <button id="btn-whatsapp" class="btn btn-secondary text-nowrap">
                                <i class="fab fa-whatsapp"></i> В WhatsApp
                            </button>
                            <button id="btn-copy" class="btn btn-secondary text-nowrap">
                                <i class="fas fa-print"></i> Копировать ссылку
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?endif;?>
    <div class="container-fluid">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-8 offset-lg-2 my-4">
                    <div class="check-content-wrapper">
                        <div class="check-content">
                            <div class="shop-info">
                                <div class="shop-name-content">
                                    <div class="shop-name">Магазин<?=($office['caption'] ? ' ' . $office['caption']: '')?></div>
                                    <div class="shop-address"><?=$officeAddress?></div>
                                    <div class="shop-phone"><?=$officePhone?></div>
                                </div>
                                <div class="shop-logo">
                                    <img src="<?=$officeLogo?>">
                                </div>
                            </div>
                            <div class="check-header background-white">
                                <?=(!$isCart ? 'Товарный чек №' . $order['id'] : 'Корзина ') . ' от ' . get_date(!$isCart ? $order['time'] : $items['created']) . ' г.'?>
                            </div>
                            <div class="check-client background-white">
                                <?=$clientName ? 'Покупатель: ' . $clientName : ''?>
                            </div>
                            <div class="check-table background-white">
                                <table class="table" border="0" cellspacing="1" cellpadding="1">
                                    <thead>
                                        <tr>
                                            <th>Наименование</th>
                                            <th>Кол-во</th>
                                            <th>Цена</th>
                                            <th>Стоимость</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sumDiscount = 0;
                                        $sum = 0;
                                        ?>
                                        <?foreach($isCart ? $items['records'] : $items as $item):?>
                                        <?php
                                            if($isCart){
                                                $sum += isset($item['price_sum']) ? $item['price_sum'] : 0;
                                            }else{
                                                if($enableDiscountColumn){
                                                    $sumDiscount += isset($item['price_discount']) ? $item['price_discount'] : 0;
                                                    $sum += isset($item['price_sum_discount']) ? $item['price_sum_discount'] : 0;
                                                }else{
                                                    $sum += isset($item['price_sum']) ? $item['price_sum'] : 0;
                                                }
                                            }
                                            $discount = isset($item['discount']) && $item['discount'] > 0 ? $item['discount'] : 0;
                                        ?>
                                        <tr>
                                            <td><?=($isCart ? $item['t2_manufacturer'] . ' ' . $item['t2_name'] : $item['product_name'])?></td>
                                            <td width="100" class="text-nowrap"><?=$item['count_need']?> шт.</td>
                                            <td width="100" class="text-nowrap"><?=print_price($item['price'])?> ₽<?=($discount > 0 ? '<br>' . '-' . $discount . '%' : '')?></td>
                                            <td width="120" class="text-nowrap"><?=print_price($item['price_sum'])?> ₽<?=($discount > 0 ? '<br>' . print_price($item['price_sum_discount']) . ' ₽' : '')?></td>
                                        </tr>
                                        <?endforeach;?>
                                    </tbody>
                                    <tfoot>
                                        <?if($sumDiscount > 0):?>
                                        <tr>
                                            <td>Скидка:</td>
                                            <td></td>
                                            <td></td>
                                            <td><?=print_price($sumDiscount)?> ₽</td>
                                        </tr>
                                        <?endif;?>
                                        <?php
                                        $sumNDS = $sum / (1 + (int)$parametersValues["nds"] / 100);
                                        ?>
                                        <tr>
                                            <td>Сумма с НДС:</td>
                                            <td></td>
                                            <td></td>
                                            <td><?=print_price($sumNDS)?> ₽</td>
                                        </tr>
                                        <tr>
                                            <td>Сумма без НДС:</td>
                                            <td></td>
                                            <td></td>
                                            <td><?=print_price($sum)?> ₽</td>
                                        </tr>
                                        <?if($paidSum > 0 && $paidLeft > 0):?>
                                        <tr>
                                            <td>Предоплата</td>
                                            <td></td>
                                            <td></td>
                                            <td><?=print_price($paidSum)?> ₽</td>
                                        </tr>
                                        <?endif;?>
                                        <tr>
                                            <td>Итого:</td>
                                            <td></td>
                                            <td></td>
                                            <td><?=print_price($sumNDS - $paidSum)?> ₽</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?if(DP_User::isAdmin() && !$isCart):?>
							<?php if ($order["recomendation"] != '' && !is_null($order["recomendation"])) : ?>
							<div class="check-sub-info background-white" style="padding-bottom: 18px;">
								<b>Рекомендации</b> <br>
                                <?=$order["recomendation"];?>
                            </div>
							<?php endif; ?>
                            <div class="check-signature background-white">
                                <div>Подпись продавца</div>
                                <div>Подпись клиента</div>
                            </div>
                            <div class="check-sub-info background-white">
                                Указанные товары получены покупателем. Претензии по внешнему виду товаров и их количеству отсутствуют.	
                            </div>
                            <?endif;?>
                            <?if(!$isCart):?>
                            <div class="check-info background-white">В случае ВОЗВРАТА приобретённого товара, затраты клиента, связанные с его установкой и транспортировкой, использованию расходных материалов и другие расходы компания НЕ ВОЗМЕЩАЕТ.</div>
                            <?endif;?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?if($showControl):?>
    <div id="qrcode-whatsapp" class="d-none"></div>
    <div id="qrcode" class="d-none"></div>
    <style>
        .tippy-content{
            background: white;
            padding: 30px;
            border: 1px solid black;
            border-radius: 5px;
        }
        @media print{
            @page{
                size: A4 portrait;
                margin: 0;
                padding: 0;
            }
            body{
                -webkit-print-color-adjust:exact !important;
                print-color-adjust:exact !important;
                margin: 0;
                background: white;
            }
            .container-fluid{
                padding: 0px;
                margin: 0px;
            }
            .col-12{
                width: 100%!important;
            }
            .container{
                width: 100%;
                max-width: 100%;
                margin: 0;
            }
            .no-print{
                display: none;
            }
            .check-content-wrapper{
                -webkit-box-shadow: none;
                -moz-box-shadow: none;
                box-shadow: none;
            }
            .check-content{
                background: #D3D3D3;
                padding: 1px;
            }
            table{
                border: none!important;
                border-collapse: collapse!important;
            }
            td, th{
                border-color: #dee2e6!important;
            }
        }
    </style>
    <script>
        let link = '<?=$checkUrl?>';
        let whatsAppLink = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(link);
        let qrcodeWhatsapp = new QRCode(document.getElementById("qrcode-whatsapp"), {
            text: whatsAppLink,
            width: 128,
            height: 128,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
        let qrcode = new QRCode(document.getElementById("qrcode"), {
            text: link,
            width: 128,
            height: 128,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
        tippy('#btn-whatsapp', {
            allowHTML: true,
            interactive: true,
            onTrigger: function(self){
                self.setContent('<img src="'+$("#qrcode-whatsapp img").attr('src')+'">');
            }
        });
        tippy('#btn-copy', {
            allowHTML: true,
            interactive: true,
            onTrigger: function(self){
                self.setContent('<img src="'+$("#qrcode img").attr('src')+'">');
            }
        });
        $("#btn-whatsapp").click(function(){
            window.open(whatsAppLink, '_blank');
        });
        $("#btn-copy").click(function(){
            if(navigator.clipboard){
                navigator.clipboard.writeText(link).then(function(){
                    alert('Адрес скопирован');
                });
            }else{
                alert('Функция копирования не найдена');
            }
        });
        $("#btn-print").click(function(){
            window.print();
        });
    </script>
    <?endif;?>
</body>
</html>