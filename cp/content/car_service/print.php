<?php

function num2str($num)
{
    $nul = 'ноль';
    $ten = [
        ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
        ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
    ];
    $a20 =
        ['десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать',
            'восемнадцать', 'девятнадцать'];
    $tens = [2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'];
    $hundred = ['', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот'];
    $unit = [
        ['копейка', 'копейки', 'копеек', 1],
        ['рубль', 'рубля', 'рублей', 0],
        ['тысяча', 'тысячи', 'тысяч', 1],
        ['миллион', 'миллиона', 'миллионов', 0],
        ['миллиард', 'милиарда', 'миллиардов', 0],
    ];

    list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
    $out = [];
    if (intval($rub) > 0) {
        foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
            if (!intval($v)) continue;
            $uk = sizeof($unit) - $uk - 1; // unit key
            $gender = $unit[$uk][3];
            list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
            $out[] = $hundred[$i1]; # 1xx-9xx
            if ($i2 > 1) $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3]; # 20-99
            else $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
            if ($uk > 1) $out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
        } //foreach
    }
    else $out[] = $nul;
    $out[] = morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
    $out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
    return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
}

function morph($n, $f1, $f2, $f5)
{
    $n = abs(intval($n)) % 100;
    if ($n > 10 && $n < 20) return $f5;
    $n = $n % 10;
    if ($n > 1 && $n < 5) return $f2;
    if ($n == 1) return $f1;
    return $f5;
}



// Функция для рекурсивного обхода дерева
function fetchNode($id) {
    global $db_link;
    $query = "SELECT id, parent, value FROM shop_tree_lists_items WHERE id = :id";
    $stmt = $db_link->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getFullCarName($startId) {
    global $db_link;

    // Начальная настройка
    $fullName = "";
    $currentNode = fetchNode($startId);

    // Построение полного имени рекурсивно
    while ($currentNode) {
        $fullName = $currentNode['value'] . ' ' . $fullName;
        $currentNode = $currentNode['parent'] ? fetchNode($currentNode['parent']) : null;
    }

    // Удаление лишнего пробела в конце строки
    return trim($fullName);
}

function get_auto_full_name($car_id) {
    global $db_link;
    $carFullName = getFullCarName($car_id);

    return $carFullName;
}

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

// Подключение к БД
try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
} catch (PDOException $e) {
    exit("No DB connect");
}

header('Content-Type: text/html; charset=utf-8');

function print_price($price) {
    return number_format($price, 2, '.', ' ');
}


function selectPastMileage($car_id, $user_id, $current_record_id) {
	global $db_link;
	$select = $db_link->prepare("SELECT `mileage` FROM `car_service_records` WHERE `car_id` = ? AND `user_id` = ? AND `id` <= ? ORDER BY `id` DESC LIMIT 2;");
	$select->execute([$car_id, $user_id, $current_record_id]);
	
	$res = $select->fetchAll(PDO::FETCH_ASSOC);
	
	return $res[1]['mileage'];  
}

// Получение данных
$select_sto_data = $db_link->prepare("SELECT * FROM `car_services` WHERE `id` = (SELECT `sto_id` FROM `car_service_records` WHERE `id` = ?);");
$select_sto_data->execute([$_GET["id"]]);
$sto_data = $select_sto_data->fetch(PDO::FETCH_ASSOC);

$select_record_data = $db_link->prepare("SELECT * FROM `car_service_records` WHERE `id` = ?;");
$select_record_data->execute([$_GET['id']]);
$record_data = $select_record_data->fetch(PDO::FETCH_ASSOC);

$select_worker = $db_link->prepare("SELECT `fio` FROM `car_service_personal` WHERE `id` = ?;");
$select_worker->execute([$record_data['worker_id']]);
$worker_data = $select_worker->fetch(PDO::FETCH_ASSOC);

// Услуги
$worksArr = [];
$select_service_data = $db_link->prepare("SELECT * FROM `car_service_record_services` WHERE `record_id` = ?;");
$select_service_data->execute([$_GET["id"]]);
$services = $select_service_data->fetchAll(PDO::FETCH_ASSOC);

foreach ($services as $service) {
	$price = $service['price'] * $service['count'];
	$price = $price - ($price * ($service['discount'] / 100));
	
    $worksArr[] = [
        'nameWork' => $service["caption"],
        'exist' => $service["count"],
        'price' => $service["price"],
        'discount' => $service["discount"],
        'sum' => $price
    ];
}


$orders_items_statuses_not_count = array();
$orders_items_statuses_query = $db_link->prepare('SELECT * FROM `shop_orders_items_statuses_ref` ORDER BY `order` ASC;');
$orders_items_statuses_query->execute();
while($status = $orders_items_statuses_query->fetch() )
{   
    //2.1
    if($status["count_flag"] == 0)
    {
        array_push($orders_items_statuses_not_count, $status["id"]);
    }
}

// Детали
$itemsArr = [];
$recomendation = '';
if (!empty($record_data['orders_ids'])) {
    $orders_ids = explode(",", $record_data['orders_ids']);
    foreach ($orders_ids as $order_id) {
		
		$WHERE_statuses_not_count = "";
		
		for($i=0; $i<count($orders_items_statuses_not_count); $i++)
		{
			$WHERE_statuses_not_count .= " AND `status` != ".(int)$orders_items_statuses_not_count[$i];
		}
		
        $SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
        $SELECT_type2_name = "CONCAT(`t2_manufacturer`, ' ', `t2_article`, '. ', `t2_name`)";
        $SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";
        
        $select_items = $db_link->prepare("SELECT *, $SELECT_product_name AS `caption` FROM `shop_orders_items` WHERE `order_id` = ?  " . $WHERE_statuses_not_count . ";");
        $select_items->execute([$order_id]);
        $items = $select_items->fetchAll(2);
        
        foreach ($items as $item) {
			$price = $item['price'] * $item['count_need'];
			$price = $price - ($price * ($item['discount'] / 100));
            $itemsArr[] = [
                'nameWork' => $item['caption'],
                'exist' => $item['count_need'],
                'price' => $item['price'],
                'discount' => $item['discount'],
                'sum' => $price
            ];
        }
		
		$select_rec = $db_link->prepare("SELECT `recomendation` FROM `shop_orders` WHERE `id` = ?;");
		$select_rec->execute([$order_id]);
		
		$select_rec_res = $select_rec->fetch(PDO::FETCH_ASSOC);
		
		$recomendation = $select_rec_res['recomendation'];
    }
}

// Данные пользователя и автомобиля
$user_id = $record_data["user_id"];

//Получаем данные пользователя
$user_data = [];

$selest_users = $db_link->prepare("SELECT *, (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'name' AND `user_id` = `users`.`user_id`) as `name`, (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'surname' AND `user_id` = `users`.`user_id`) as `surname`, (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'patronymic' AND `user_id` = `users`.`user_id`) as `patronymic` FROM `users` WHERE `user_id` = ?;");
$selest_users->execute([$user_id]);
$user_data = $selest_users->fetch(PDO::FETCH_ASSOC);

if ($user_data["phone"] != '') {
	$user_data["phone"] = ", " . $user_data["phone"];
}

$user_str = $user_data["surname"] . " " . $user_data["name"] . " " . $user_data["patronymic"] . $user_data["phone"];



//Получаем данные авто
$car_id = $record_data["car_id"];
$car_data = [];
if ($car_id > 0) {
	$select_car_data = $db_link->prepare("SELECT * FROM `shop_docpart_garage` WHERE `id` = ?;");
	$select_car_data->execute([$car_id]);
	$select_car_res = $select_car_data->fetch(PDO::FETCH_ASSOC);
	$car_data = $select_car_res;
}

$past_mileage = selectPastMileage($car_id, $user_id, $record_data["id"]);



$img = 'https://fordzap.com/content/files/%D0%A1%D0%BD%D0%B8%D0%BC%D0%BE%D0%BA%20%D1%8D%D0%BA%D1%80%D0%B0%D0%BD%D0%B0%202024-02-01%20223453.png?v=123';

// Инициализация переменных для итогов
$total_sum_services = 0;
$totalItemsSum = 0;
$totalExistWorks = 0;
$totalExistItems = 0;

// Подсчет сумм
foreach ($worksArr as $row) {
    $total_sum_services += $row['sum'] ?? 0;
    $totalExistWorks += $row['exist'] ?? 0;
}

foreach ($itemsArr as $row) {
    $totalItemsSum += $row['sum'] ?? 0;
    $totalExistItems += $row['exist'] ?? 0;
}

$totalSumBoth = ($total_sum_services + $totalItemsSum) - ($record_data["prepayment"] ?? 0);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Заказ-наряд</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        @import url(https://fonts.googleapis.com/css?family=PT+Sans:400,700&subset=latin,latin-ext,cyrillic);
        @import url(https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap);
        
        html, body {
            font-family: 'Inter', 'PT Sans', sans-serif;
            background-color: #f0f7f9;
            color: rgba(0, 0, 0, 0.87);
            font-size: 14px;
        }
        
        .check-content-wrapper {
            -webkit-box-shadow: 4px 4px 44px 8px rgba(34, 60, 80, 0.2);
            -moz-box-shadow: 4px 4px 44px 8px rgba(34, 60, 80, 0.2);
            box-shadow: 4px 4px 44px 8px rgba(34, 60, 80, 0.2);
        }
        
        .check-content {
            background: white;
            border: 1px solid #D3D3D3!important;
        }
        
        .shop-info {
            background: #f6f8f9;
            padding: 24px 32px;
            display: flex;
            position: relative;
        }
        
        .shop-name-content {
            flex-direction: column;
            gap: 5px;
            margin-right: calc(108px + 50px);
        }
        
        .shop-name {
            font-weight: 700;
            font-size: 16px;
        }
        
        .shop-address, .shop-phone {
            color: rgba(0, 0, 0, 0.56);
            font-size: 14px;
        }
        
        .shop-logo {
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
        
        .shop-logo img {
            width: 100%;
        }
        
        .check-header {
            font-size: 22px;
            line-height: 120%;
            padding: 30px 30px 10px 30px;
            text-align: center;
        }
        
        .check-client {
            font-size: 16px;
            padding: 0px 30px;
        }
        
        .check-table {
            padding: 15px 30px 15px 30px;
        }
        
        .check-table table {
            margin-bottom: 0px!important;
            border: none;
            border-color: #dee2e6;
        }
        
        .check-table table td, .check-table table th {
            padding-left: 0px;
            padding-right: 0px;
        }
        
        .check-table tfoot td {
            padding: 2px 0px;
            border-bottom: none;
            font-weight: bold;
        }
        
        .check-table table th:last-child, .check-table table td:last-child {
            text-align: right;
        }
        
        .check-table table tr:last-child td {
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        
        .check-signature {
            padding: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        .check-signature div {
            width: 200px;
            border-top: 1px solid black;
            font-size: 14px;
        }
        
        .check-info, .check-sub-info {
            padding: 0px 30px;
            text-align: center;
            font-size: 14px;
            padding-top: 15px;
            background: #f6f8f9;
        }
        
        .check-info {
            padding: 15px 30px 30px 30px;
        }
        
        /* Специфичные для заказ-наряда */
        .order-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            padding: 20px 30px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .order-car-info {
            border-left: 1px solid rgba(0, 0, 0, 0.08);
            padding-left: 20px;
            font-size: 14px;
        }
        
        .order-total {
            background: #f6f8f9;
            padding: 15px 30px;
            font-weight: 600;
        }
        
        .order-legal-text {
            padding: 20px 30px;
            font-size: 14px;
            line-height: 1.4;
        }
        
        @media screen and (max-width: 767px) {
            .shop-logo {
                right: 15px;
            }
            .shop-name-content {
                margin-right: calc(108px + 15px);
            }
            .order-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .order-car-info {
                border-left: none;
                border-top: 1px solid rgba(0, 0, 0, 0.08);
                padding-left: 0;
                padding-top: 15px;
            }
        }
        
        @media print {
			@page {
				size: A4 portrait;
				margin: 0;
				padding: 0;
			}
			body {
				-webkit-print-color-adjust: exact !important;
				print-color-adjust: exact !important;
				margin: 0;
				background: white !important;
			}
			.container-fluid {
				padding: 0px;
				margin: 0px;
			}
			.col-12 {
				width: 100%!important;
			}
			.container {
				width: 100%;
				max-width: 100%;
				margin: 0;
			}
			.no-print {
				display: none !important;
			}
			.check-content-wrapper {
				-webkit-box-shadow: none !important;
				-moz-box-shadow: none !important;
				box-shadow: none !important;
			}
			.check-content {
				background: white !important; /* ИЗМЕНИЛ С СЕРОГО НА БЕЛЫЙ */
				border: 1px solid #D3D3D3 !important;
				padding: 0 !important;
			}
			table {
				border: none!important;
				border-collapse: collapse!important;
			}
			td, th {
				border-color: #dee2e6!important;
			}
			.shop-info {
				background: #f6f8f9 !important;
			}
			.order-total {
				background: #f6f8f9 !important;
			}
			.check-info, .check-sub-info {
				background: #f6f8f9 !important;
			}
			
			/* Добавляем отступы для печати */
			.check-header {
				padding: 30px 30px 10px 30px !important;
			}
			.check-table {
				padding: 15px 30px 15px 30px !important;
			}
			.check-signature {
				padding: 30px !important;
			}
		}
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print();return false;" class="btn btn-secondary">
            <i class="fas fa-print"></i> Печать
        </button>
    </div>

    <div class="container-fluid">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-8 offset-lg-2 my-4">
                    <div class="check-content-wrapper">
                        <div class="check-content">
                            
                            <!-- Шапка с информацией об организации -->
                            <div class="shop-info">
                                <div class="shop-name-content">
                                    <div class="shop-name">
                                        <?= htmlspecialchars($sto_data["organization"] ?? ''); ?>
                                    </div>
                                    <div class="shop-address">
                                        ИНН: <?= htmlspecialchars($sto_data["inn"] ?? ''); ?>
                                    </div>
                                    <div class="shop-phone">
                                        Телефон: <?= htmlspecialchars($sto_data["phone"] ?? ''); ?>
                                    </div>
									<div class="shop-phone">
                                        Адрес: <?= htmlspecialchars($sto_data["address"] ?? ''); ?>
                                    </div>
                                </div>
                                <?php if (!empty($img)): ?>
                                <div class="shop-logo">
                                    <img src="<?= htmlspecialchars($img); ?>" alt="Логотип">
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Заголовок -->
                            <div class="check-header">
                                Заказ-наряд № <?= htmlspecialchars($_GET["id"] ?? ''); ?> от <?= date('d.m.Y', time()); ?>
                            </div>

                            <!-- Информация о клиенте и автомобиле -->
                            <div class="order-grid">
                                <div style="display: flex; flex-direction: column; justify-content: space-between;">
                                    <div class="check-client">
                                        <strong>Заказчик:</strong> <?= htmlspecialchars($user_str); ?>
                                    </div>
                                    <div class="shop-phone">
                                        <strong>Мастер:</strong> <?= htmlspecialchars($worker_data['fio'] ?? ''); ?>
                                    </div>
                                </div>
                                <div class="order-car-info">
                                    <strong>Транспортное средство:</strong><br>
									Марка: <strong><?= htmlspecialchars($car_data["marka"] ?? ''); ?></strong><br>
                                    Модель: <strong><?= htmlspecialchars($car_data["model"] ?? ''); ?></strong><br>
                                    Гос. номер: <?= htmlspecialchars($car_data['license_plate'] ?? ''); ?><br>
                                    VIN: <?= htmlspecialchars($car_data['vin'] ?? ''); ?><br>
                                    Пробег: <?= htmlspecialchars($record_data['mileage'] ?? ''); ?> км<br>
									<?php if ($past_mileage > 0) : ?>
									Предыдущий пробег: <?= htmlspecialchars($past_mileage ?? ''); ?> км<br>
									Разница: <?= $record_data['mileage'] - $past_mileage ?> км<br>
									<?php endif; ?>
                                    Год выпуска: <?= ($car_data['year'] ?? 0) > 0 ? htmlspecialchars($car_data['year']) : 'не указан'; ?>
                                </div>
                            </div>

                            <!-- Таблица работ -->
                            <?php if (count($worksArr) > 0): ?>
                            <div class="check-table">
                                <strong>Работы</strong>
                                <table class="table" border="0" cellspacing="1" cellpadding="1">
                                    <thead>
                                        <tr>
                                            <th>Наименование</th>
                                            <th width="100" class="text-nowrap">Кол-во</th>
                                            <th width="100" class="text-nowrap">Цена</th>
                                            <th width="100" class="text-nowrap">Скидка</th>
                                            <th width="120" class="text-nowrap">Стоимость со скидкой</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($worksArr as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nameWork'] ?? ''); ?></td>
                                            <td class="text-nowrap"><?= htmlspecialchars($row['exist'] ?? 0); ?> шт.</td>
                                            <td class="text-nowrap"><?= print_price($row['price'] ?? 0); ?></td>
                                            <td class="text-nowrap"><?= $row['discount']; ?></td>
                                            <td class="text-nowrap"><?= print_price($row['sum'] ?? 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td><strong>ИТОГО РАБОТЫ:</strong></td>
                                            <td class="text-nowrap"><strong><?= $totalExistWorks; ?> шт.</strong></td>
											<td></td>
											<td></td>
                                            <td class="text-nowrap"><strong><?= print_price($total_sum_services); ?></strong></td> 
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- Таблица деталей -->
                            <?php if (count($itemsArr) > 0): ?>
                            <div class="check-table">
                                <strong>Детали</strong>
                                <table class="table" border="0" cellspacing="1" cellpadding="1">
                                    <thead>
                                        <tr>
                                            <th>Наименование</th>
                                            <th width="100" class="text-nowrap">Кол-во</th>
                                            <th width="100" class="text-nowrap">Цена</th>
                                            <th width="100" class="text-nowrap">Скидка</th>
                                            <th width="120" class="text-nowrap">Стоимость со скидкой</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($itemsArr as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nameWork'] ?? ''); ?></td>
                                            <td class="text-nowrap"><?= htmlspecialchars($row['exist'] ?? 0); ?> шт.</td>
                                            <td class="text-nowrap"><?= print_price($row['price'] ?? 0); ?></td>
                                            <td class="text-nowrap"><?= print_price($row['discount'] ?? 0); ?></td>
                                            <td class="text-nowrap"><?= print_price($row['sum'] ?? 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td><strong>ИТОГО ДЕТАЛИ:</strong></td>
                                            <td class="text-nowrap"><strong><?= $totalExistItems; ?> шт.</strong></td>
                                            <td></td>
                                            <td></td>
                                            <td class="text-nowrap"><strong><?= print_price($totalItemsSum); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- Итоговая сумма -->
                            <div class="order-total">
                                <table class="table">
                                    <tr>
                                        <td style="text-align: right; font-weight: bold; border: none;">ВСЕГО по акту:</td>
                                        <td style="text-align: right; font-weight: bold; border: none; width: 150px;">
                                            <?= print_price($totalSumBoth); ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Текстовая часть -->
                            <div class="order-legal-text">
                                <div><strong>Всего на сумму:</strong> <?= num2str($totalSumBoth); ?></div>
                                <?php if (!empty($record_data['prepayment'])): ?>
                                <div style="margin-top: 10px;">
                                    <strong>Предоплата:</strong> <?= print_price($record_data['prepayment']); ?>, <?= num2str($record_data['prepayment']); ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Юридический текст -->
                            <div class="check-sub-info" style="padding-bottom: 18px;">
                                Работы выполнены полностью и в срок, детали/товары поставлены.<br>
                                Заказчик претензий к объему, качеству и срокам оказания работ не имеет.<br>
                                Настоящий акт составлен в двух экземплярах и служит, в соответствии с условиями договора,<br>
                                основанием для проведения расчетов Заказчика с Исполнителем за оказанные услуги.
                            </div>
							
							<?php if ($recomendation != '') : ?>
							<div class="check-sub-info" style="margin-top: 10px; padding-bottom: 18px;">
								<b>Рекомендации</b> <br>
                                <?=$recomendation;?>
                            </div>
							<?php endif; ?>
							
                            <!-- Подписи -->
                            <div class="check-signature">
                                <div>Подпись клиента</div>
                                <div>Подпись исполнителя</div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js"></script>
</body>
</html>