<?php 

/** @var PDO $db_link */
# ОЖИДАЕМ, ЧТО $db_link УЖЕ ИНИЦИАЛИЗИРОВАН ВНЕ ЭТОГО ФАЙЛА

# --- 1) Данные: автосервисы ---
$select_car_services = $db_link->prepare("SELECT * FROM `car_services` ORDER BY `id` ASC");
$select_car_services->execute();
$car_services_arr = $select_car_services->fetchAll(PDO::FETCH_ASSOC);

# --- 2) Данные: стикеры ---
$stickers = $db_link->prepare("SELECT * FROM `car_service_stickers`;");
$stickers->execute();
$stickers_res = $stickers->fetchAll(PDO::FETCH_ASSOC);

$stickers_map = [];
foreach ($stickers_res as $sticker) {
    $stickers_map[(int)$sticker["id"]] = [
        "text"  => (string)$sticker["text"],
        "color" => (string)$sticker["bg_color"]
    ];
}

# --- 3) Дерево услуг по каждому СТО ---
$services_arr = [];

foreach ($car_services_arr as $car_service) {
    $treeId = (int)$car_service["id_tree_list_services"];

    $select_services = $db_link->prepare("SELECT * FROM `shop_tree_lists_items` WHERE `tree_list_id` = ?;");
    $select_services->execute([$treeId]);
    $services_start_arr = $select_services->fetchAll(PDO::FETCH_ASSOC);

    $services_tree_list = [];

    # Родители
    foreach ($services_start_arr as $service) {
        if ((int)$service["parent"] === 0) {
            $services_tree_list[(int)$service["id"]] = [
                "caption" => (string)$service["value"],
                "list_services" => []
            ];
        }
    }

    # Дети + их инфо (цены, стикеры и т.п.)
    foreach ($services_start_arr as $service) {
        $parentId = (int)$service["parent"];
        if ($parentId > 0 && isset($services_tree_list[$parentId])) {
            $childId = (int)$service["id"];

            $select_service_info = $db_link->prepare("
                SELECT * FROM `car_service_prices`
                WHERE `sto_id` = ? AND `id_tree_services_list` = ?
                LIMIT 1
            ");
            $select_service_info->execute([(int)$car_service["id"], $childId]);
            $info = $select_service_info->fetch(PDO::FETCH_ASSOC) ?: [
                "price" => null, "caption" => null, "stickers" => null,
                "recommendation_block" => 0, "worker_price" => 0, "active" => 0
            ];

            $price = is_null($info["price"]) ? null : (float)$info["price"];
            $stickers_ids = [];
            if (!empty($info["stickers"])) {
                $decoded = json_decode($info["stickers"], true);
                if (is_array($decoded)) $stickers_ids = array_map('intval', $decoded);
            }

            $services_tree_list[$parentId]["list_services"][$childId] = [
                "caption"             => (string)$service["value"],
                "price"               => $price,
                "description"         => (string)($info["caption"] ?? ""),
                "stickers"            => $stickers_ids, // массив ID
                "recommendation_block"=> (int)($info["recommendation_block"] ?? 0),
                "worker_price"        => (int)($info["worker_price"] ?? 0),
                "active"              => (int)($info["active"] ?? 0),
            ];
        }
    }

    $services_arr[(int)$car_service["id"]] = $services_tree_list;
}

function formatSchedule($srv) {
    // Массив с русскими сокращениями дней недели
    $daysMap = ['1'=>'Пн','2'=>'Вт','3'=>'Ср','4'=>'Чт','5'=>'Пт','6'=>'Сб','7'=>'Вс'];

    $days = $srv['operating_mode'] ?? []; // ["1","2","3","4","5"]
    if (is_string($days)) {
        // Если пришло в виде JSON или строки, попробуем декодировать
        $tmp = json_decode($days, true);
        if ($tmp !== null) $days = $tmp;
        else $days = explode(',', $days); // например "1,2,3"
    }

    // Преобразуем строки в числа и сортируем
    $days = array_map('intval', $days);
    sort($days);

    // Формируем интервалы дней
    $intervals = [];
    $start = $end = null;
    foreach ($days as $d) {
        if ($start === null) {
            $start = $end = $d;
        } elseif ($d == $end + 1) {
            $end = $d;
        } else {
            $intervals[] = ($start==$end ? $daysMap[$start] : $daysMap[$start].'-'.$daysMap[$end]);
            $start = $end = $d;
        }
    }
    if ($start !== null) {
        $intervals[] = ($start==$end ? $daysMap[$start] : $daysMap[$start].'-'.$daysMap[$end]);
    }

    $daysStr = implode(', ', $intervals);

    // Время работы
    $from = isset($srv['time_from']) ? intval($srv['time_from']) : null;
    $to = isset($srv['time_to']) ? intval($srv['time_to']) : null;
    if ($from !== null && $to !== null) {
        $timeStr = sprintf('%02d:00-%02d:00', $from, $to);
    } else {
        $timeStr = '';
    }

    return trim($daysStr . ' ' . $timeStr);
}

# Утилита: HTML для стикеров
function render_stickers_html(array $ids, array $stickers_map): string {
    if (empty($ids)) return '';
    $html = '<span class="stickers">';
    foreach ($ids as $sid) {
        if (!isset($stickers_map[$sid])) continue;
        $text = htmlspecialchars($stickers_map[$sid]["text"]);
        $color = htmlspecialchars($stickers_map[$sid]["color"] ?: '#888');
        $html .= '<span class="sticker" style="background:'.$color.';">'.$text.'</span>';
    }
    $html .= '</span>';
    return $html;
}

# JSON для JS (нужно для логики без повторного похода в DOM)
$js_services_data = [];
foreach ($services_arr as $sto_id => $parents) {
    $js_services_data[$sto_id] = [];
    foreach ($parents as $parent_id => $parent) {
        $group = [
            "id" => $parent_id,
            "caption" => $parent["caption"],
            "items" => []
        ];
        foreach ($parent["list_services"] as $service_id => $s) {
            $group["items"][] = [
                "id" => $service_id,
                "caption" => $s["caption"],
                "price" => $s["price"],
                "description" => $s["description"],
                "stickers" => $s["stickers"],
                "recommendation_block" => $s["recommendation_block"],
                "worker_price" => $s["worker_price"],
                "active" => $s["active"]
            ];
        }
        $js_services_data[$sto_id][] = $group;
    }
}

# Массив меток для карты
$map_points = [];
foreach ($car_services_arr as $s) {
    $map_points[] = [
        "id"   => (int)$s["id"],
        "name" => $s["caption"],
        "lat"  => explode(",",$s["coords"])[0],
        "lng"  => explode(",",$s["coords"])[1],
        "address" => (string)($s["address"] ?? ""),
    ];
}

$firstStoId = !empty($car_services_arr) ? (int)$car_services_arr[0]["id"] : 0;

?>