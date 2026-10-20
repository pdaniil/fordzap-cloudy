<?php
/**
 * search_ajax.php – обработчик для поиска по наличию (прямой вызов)
 * 
 * Принимает POST-параметры:
 *   - article (обязательный)
 *   - manufacturer (опционально, если не указан – вернёт список производителей)
 *   - search_name (опционально, фильтр по наименованию)
 *   - storages (массив ID складов, по которым фильтровать)
 *   - geo_id (опционально)
 *   - user_id (опционально, если не передан – из сессии)
 * 
 * Возвращает JSON:
 *   - status: 'success' или 'error'
 *   - data: {
 *       manufacturers: [...],   // если производитель не указан
 *       selected_manufacturer: string,
 *       products: {             // сгруппированные товары (required, analogs, ...)
 *           required: [...],
 *           analogs: [...],
 *           ...
 *       },
 *       cards: { ... }          // минимальные цены и сроки
 *   }
 */

// Подключаем глобальный конфиг и БД (они уже должны быть загружены в контексте сайта)
// Но поскольку этот файл вызывается напрямую, нужно подключить их вручную
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
$DP_Config = new DP_Config();

// Подключение к БД
try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
} catch (PDOException $e) {
    sendJsonResponse(['status' => 'error', 'message' => 'Ошибка подключения к БД'], 500);
}

// Подключаем пользовательские классы (для групп и т.п.)
require_once $_SERVER['DOCUMENT_ROOT'] . '/content/users/dp_user.php';

// Включаем отображение ошибок для отладки (потом можно убрать)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ------------------------------------------------------------
// 1. Получение и валидация входных данных
// ------------------------------------------------------------
/*$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(['status' => 'error', 'message' => 'Неверный JSON'], 400);
}

$article = trim($input['article'] ?? '');
$manufacturer = trim($input['manufacturer'] ?? '');
$searchName = trim($input['search_name'] ?? '');
$storagesFilter = $input['storages'] ?? []; // массив ID складов для фильтрации
$geo_id = (int)($input['geo_id'] ?? $_COOKIE['my_city'] ?? 1);
$user_id = (int)($input['user_id'] ?? $_SESSION['user_id'] ?? 0);

if (empty($article)) {
    sendJsonResponse(['status' => 'error', 'message' => 'Артикул обязателен'], 400);
}
*/

$article = 'C110';
$manufacturer = 'DOLZ';
$searchName = '';
$storagesFilter = []; // массив ID складов для фильтрации
$geo_id = $_COOKIE['my_city'] ?? 1;
$user_id = $_SESSION['user_id'] ?? 0;


// Если пользователь авторизован – получаем его группу
$group_id = 1;
if ($user_id > 0) {
    $userProfile = DP_User::getUserProfile($user_id);
    if (isset($userProfile['groups'][0])) {
        $group_id = (int)$userProfile['groups'][0];
    }
}

// ------------------------------------------------------------
// 2. Вспомогательные функции (копируем из SearchHandler)
// ------------------------------------------------------------
function httpPost($url, $data, $timeout = 30) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    return null;
}

function getOfficeStorageBunches() {
    global $db_link, $DP_Config;

    // Этот код взят из part_search_page_1.php
    $customer_offices = [];
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
    // $customer_offices определён

    $office_storage_bunches = [];
    $office_storage_bunches_prices = [];

    for ($i=0; $i < count($customer_offices); $i++) {
        $storages_query = $db_link->prepare('SELECT DISTINCT(storage_id) AS storage_id, (SELECT `handler_folder` FROM `shop_storages_interfaces_types` WHERE `id` = (SELECT `interface_type` FROM `shop_storages` WHERE `id` = `shop_offices_storages_map`.`storage_id`) ) AS `handler_folder` FROM shop_offices_storages_map WHERE office_id = ? AND storage_id IN (SELECT id FROM shop_storages WHERE interface_type > 1 AND `hidden` = 0);');
        $storages_query->execute([$customer_offices[$i]]);
        while ($storage = $storages_query->fetch()) {
            $protocol_version = 1;
            if (file_exists($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/".$storage["handler_folder"]."/get_manufacturers.php")) {
                $protocol_version = 2;
            }
            $treelax_catalogue = ($storage["handler_folder"] === 'treelax_catalogue');

            if ($storage["handler_folder"] != "prices") {
                $office_storage_bunches[] = [
                    "office_id" => (int)$customer_offices[$i],
                    "storage_id" => (int)$storage["storage_id"],
                    "sent" => 0,
                    "protocol_version" => $protocol_version,
                    "manufacturers_sent" => 0,
                    "treelax_catalogue" => $treelax_catalogue
                ];
            } else {
                $office_storage_bunches_prices[] = [
                    "office_id" => (int)$customer_offices[$i],
                    "storage_id" => (int)$storage["storage_id"],
                    "sent" => 0,
                    "protocol_version" => $protocol_version,
                    "manufacturers_sent" => 0
                ];
            }
        }
    }

    if (count($office_storage_bunches_prices) > 0) {
        array_unshift($office_storage_bunches, [
            "office_id" => 0,
            "storage_id" => 0,
            "sent" => 0,
            "protocol_version" => 3,
            "manufacturers_sent" => 0,
            "office_storage_bunches" => $office_storage_bunches_prices
        ]);
    }

    // Добавляем сервер кроссов (если включено)
    if ((int)$DP_Config->is_async_search == 1) {
        $office_storage_bunches[] = ['protocol_version' => 'server'];
    }
	print_r(json_encode($office_storage_bunches));
    return $office_storage_bunches;
}

function getManufacturersList($article, $geo_id) {
    global $DP_Config;

    $bunches = getOfficeStorageBunches();
    $manufacturers = [];

    foreach ($bunches as $bunch) {
        if (isset($bunch['protocol_version'])) {
            if ($bunch['protocol_version'] == 2) {
                $url = $DP_Config->domain_path . 'content/shop/docpart/ajax_getManufacturersList.php';
                $postData = [
                    'is_async_search' => 1,
                    'geo_id' => $geo_id,
                    'office_id' => $bunch['office_id'],
                    'storage_id' => $bunch['storage_id'],
                    'query' => json_encode(['article' => $article])
                ];
                $result = httpPost($url, $postData);
                if ($result && isset($result['ProductsManufacturers'])) {
                    foreach ($result['ProductsManufacturers'] as $m) {
                        $manufacturers[] = $m;
                    }
                }
            } elseif ($bunch['protocol_version'] == 3) {
                $url = $DP_Config->domain_path . 'content/shop/docpart/ajax_getManufacturersListFromPrices.php';
                $postData = [
                    'is_async_search' => 1,
                    'office_storage_bunches' => json_encode($bunch['office_storage_bunches']),
                    'query' => json_encode(['article' => $article])
                ];
                $result = httpPost($url, $postData);
                if ($result && isset($result['ProductsManufacturers'])) {
                    foreach ($result['ProductsManufacturers'] as $m) {
                        $manufacturers[] = $m;
                    }
                }
            } elseif ($bunch['protocol_version'] == 'server') {
                $url = $DP_Config->domain_path . 'content/shop/docpart/ajax_getManufacturersListFromCrossServer.php';
                $postData = [
                    'is_async_search' => 1,
                    'query' => json_encode(['article' => $article])
                ];
                $result = httpPost($url, $postData);
                if ($result && isset($result['ProductsManufacturers'])) {
                    foreach ($result['ProductsManufacturers'] as $m) {
                        $manufacturers[] = $m;
                    }
                }
            }
        }
    }

    // Дедупликация
    $unique = [];
    foreach ($manufacturers as $m) {
        $key = $m['manufacturer_show'] ?? $m['manufacturer'];
        if (!isset($unique[$key])) {
            $unique[$key] = $m;
        }
    }

    return array_values($unique);
}

function getCrosses($article, $manufacturer) {
    global $DP_Config;

    $searchObject = [
        'article' => $article,
        'manufacturers' => [
            [
                'manufacturer' => $manufacturer,
                'manufacturer_show' => $manufacturer,
            ]
        ],
        'analogs' => []
    ];

    $url = $DP_Config->domain_path . 'content/shop/docpart/ajax_getAnalogsList.php';
    $postData = [
        'search_object' => json_encode($searchObject)
    ];
    $result = httpPost($url, $postData);
    if ($result && isset($result['analogs'])) {
        return $result['analogs'];
    }
    return [];
}

function queryAllStorages($article, $manufacturer, $crosses, $geo_id, $user_id, $group_id) {
    global $DP_Config;

    $bunches = getOfficeStorageBunches();
    $multiHandle = curl_multi_init();
    $curlHandles = [];
    $allProducts = [];

    foreach ($bunches as $index => $bunch) {
        if (isset($bunch['protocol_version']) && $bunch['protocol_version'] === 'server') {
            continue;
        }

        $searchObject = [
            'article' => $article,
            'manufacturers' => [],
            'analogs' => $crosses,
            'office_storage_bunches' => []
        ];

        if (isset($bunch['protocol_version']) && $bunch['protocol_version'] == 2) {
            $searchObject['manufacturers'][] = [
                'manufacturer' => $manufacturer,
                'manufacturer_show' => $manufacturer,
                'storage_id' => $bunch['storage_id'],
                'office_id' => $bunch['office_id']
            ];
        } elseif (isset($bunch['protocol_version']) && $bunch['protocol_version'] == 3) {
            $searchObject['office_storage_bunches'] = $bunch['office_storage_bunches'];
            $searchObject['manufacturers'][] = [
                'manufacturer' => $manufacturer,
                'manufacturer_show' => $manufacturer,
                'storage_id' => 0,
                'office_id' => 0,
                'params' => ['type' => 'prices']
            ];
        } else {
            $searchObject['manufacturers'][] = [
                'manufacturer' => $manufacturer,
                'manufacturer_show' => $manufacturer,
                'storage_id' => $bunch['storage_id'] ?? 0,
                'office_id' => $bunch['office_id'] ?? 0
            ];
        }

        $postData = [
            'geo_id' => $geo_id,
            'async' => 1,
            'tech_key' => $DP_Config->tech_key,
            'user_id' => $user_id,
            'group_id' => $group_id,
            'office_id' => $bunch['office_id'] ?? 0,
            'storage_id' => $bunch['storage_id'] ?? 0,
            'query' => json_encode($searchObject)
        ];

        $url = $DP_Config->domain_path . 'content/shop/docpart/ajax_getProductsOfBunch.php';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[$index] = ['ch' => $ch, 'bunch' => $bunch];
    }

    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
    } while ($running > 0);

    foreach ($curlHandles as $index => $item) {
        $response = curl_multi_getcontent($item['ch']);
        $httpCode = curl_getinfo($item['ch'], CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($multiHandle, $item['ch']);
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['Products']) && is_array($data['Products'])) {
                foreach ($data['Products'] as &$product) {
                    if (!isset($product['storage_id'])) {
                        $product['storage_id'] = $item['bunch']['storage_id'] ?? 0;
                    }
                    if (!isset($product['office_id'])) {
                        $product['office_id'] = $item['bunch']['office_id'] ?? 0;
                    }
                    // Добавляем поле search_name, если есть (для фильтрации)
                    if (!isset($product['search_name']) && isset($product['name'])) {
                        $product['search_name'] = $product['name'];
                    }
                }
                $allProducts = array_merge($allProducts, $data['Products']);
            }
        }
    }
    curl_multi_close($multiHandle);

    return $allProducts;
}

function groupProducts($products, $article, $manufacturer) {
    $groups = [
        'required' => [],
        'search_name' => [],
        'quick_analogs' => [],
        'analogs' => [],
        'possible_replacement' => [],
        'spare_box' => []
    ];

    foreach ($products as $p) {
        $is_required = ($p['article'] == $article && $p['manufacturer'] == $manufacturer);
        $is_search_name = isset($p['search_name']) && $p['search_name'] == 1;

        if ($is_required) {
            $groups['required'][] = $p;
        } elseif ($is_search_name) {
            $groups['search_name'][] = $p;
        } elseif (isset($p['article']) && $p['article'] == $article) {
            $groups['possible_replacement'][] = $p;
        } else {
            if ($p['time_to_exe'] <= 2) {
                $groups['quick_analogs'][] = $p;
            } else {
                $groups['analogs'][] = $p;
            }
        }
    }
    return $groups;
}

function buildCards($groups) {
    $cards = [
        'min_price_original' => null,
        'min_price_analog' => null,
        'min_delivery' => null
    ];

    if (!empty($groups['required'])) {
        $min = null;
        foreach ($groups['required'] as $p) {
            if ($min === null || $p['price'] < $min['price']) {
                $min = $p;
            } elseif ($p['price'] == $min['price'] && $p['time_to_exe'] < $min['time_to_exe']) {
                $min = $p;
            }
        }
        $cards['min_price_original'] = $min;
    }

    $analogs = array_merge($groups['quick_analogs'], $groups['analogs']);
    if (!empty($analogs)) {
        $min = null;
        foreach ($analogs as $p) {
            if ($min === null || $p['price'] < $min['price']) {
                $min = $p;
            } elseif ($p['price'] == $min['price'] && $p['time_to_exe'] < $min['time_to_exe']) {
                $min = $p;
            }
        }
        $cards['min_price_analog'] = $min;
    }

    $all = array_merge($groups['required'], $groups['quick_analogs'], $groups['analogs'], $groups['possible_replacement'], $groups['search_name']);
    if (!empty($all)) {
        $min = null;
        foreach ($all as $p) {
            if ($min === null || $p['time_to_exe'] < $min['time_to_exe']) {
                $min = $p;
            } elseif ($p['time_to_exe'] == $min['time_to_exe'] && $p['price'] < $min['price']) {
                $min = $p;
            }
        }
        $cards['min_delivery'] = $min;
    }

    return $cards;
}

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------
// 3. Основная логика
// ------------------------------------------------------------

// Если производитель не указан – получаем список и возвращаем его
if (empty($manufacturer)) {
    $manufacturers = getManufacturersList($article, $geo_id);
    if (empty($manufacturers)) {
        sendJsonResponse([
            'status' => 'success',
            'data' => [
                'manufacturers' => [],
                'products' => [],
                'cards' => []
            ],
            'warnings' => ['Товар не найден']
        ]);
    }
    if (count($manufacturers) == 1) {
        $manufacturer = $manufacturers[0]['manufacturer_show'];
    } else {
        sendJsonResponse([
            'status' => 'success',
            'data' => [
                'manufacturers' => $manufacturers,
                'products' => [],
                'cards' => []
            ]
        ]);
    }
}

// Получаем кроссы
$crosses = getCrosses($article, $manufacturer);

// Опрос складов
$products = queryAllStorages($article, $manufacturer, $crosses, $geo_id, $user_id, $group_id);
exit();
// Группировка
$grouped = groupProducts($products, $article, $manufacturer);

// Применяем фильтр по складам (если передан)
if (!empty($storagesFilter) && is_array($storagesFilter)) {
    foreach ($grouped as $groupName => &$items) {
        $items = array_filter($items, function($p) use ($storagesFilter) {
            return in_array($p['storage_id'], $storagesFilter);
        });
        $items = array_values($items); // переиндексация
    }
    unset($items);
}

// Фильтр по наименованию (если передан)
if (!empty($searchName)) {
    $searchNameLower = mb_strtolower($searchName);
    foreach ($grouped as $groupName => &$items) {
        $items = array_filter($items, function($p) use ($searchNameLower) {
            $name = isset($p['search_name']) ? $p['search_name'] : ($p['name'] ?? '');
            return mb_strpos(mb_strtolower($name), $searchNameLower) !== false;
        });
        $items = array_values($items);
    }
    unset($items);
}

// Карточки
$cards = buildCards($grouped);

// Ответ
sendJsonResponse([
    'status' => 'success',
    'data' => [
        'selected_manufacturer' => $manufacturer,
        'products' => $grouped,
        'cards' => $cards
    ]
]);