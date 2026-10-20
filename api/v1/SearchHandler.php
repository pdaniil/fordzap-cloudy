<?php
/**
 * SearchHandler.php – обработчик API-запроса /api/v1/search
 * 
 * Реализует полную логику проценки: получение производителей, кроссов,
 * параллельный опрос складов, фильтрация, формирование карточек.
 */

// Подключаем глобальные файлы (уже подключены в index.php)
global $db_link, $DP_Config;

// ------------------------------------------------------------
// 1. Получение и валидация входных данных
// ------------------------------------------------------------
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(errorResponse('Неверный JSON', 400), 400);
}

$validation = validateSearchRequest($input);
if (!$validation['valid']) {
    sendJsonResponse(errorResponse('Ошибка валидации', 400, $validation['errors']), 400);
}
$data = $validation['data'];

$article = $data['article'];
$manufacturer = $data['manufacturer'] ?? null;
$geo_id = getGeoId($data);
$user_id = $data['user_id'] ?? 0;         // если не передан, будет 0 (гость)
$group_id = $data['group_id'] ?? 1;       // группа по умолчанию

// Если пользователь авторизован – получим его группу из сессии
if ($user_id == 0 && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    // Загружаем профиль пользователя, чтобы получить группу
    $userProfile = DP_User::getUserProfile($user_id);
    if (isset($userProfile['groups'][0])) {
        $group_id = (int)$userProfile['groups'][0];
    }
}

$filters = $data['filters'] ?? [];

// ------------------------------------------------------------
// 2. Получение списка складов (связок офис-склад)
// ------------------------------------------------------------
function getOfficeStorageBunches() {
    global $db_link, $DP_Config;

    $customer_offices = [];
    // Получаем офисы из конфига или из БД (аналогично part_search_page_1.php)
    // Здесь используем существующий код из part_search_page_1.php (скопирован)
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
    // $customer_offices уже определён

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
            if (!isset($storage["storage_id"])) $storage["storage_id"] = null;

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

    // Для асинхронного режима добавляем сервер кроссов (опционально)
    if ((int)$DP_Config->is_async_search == 1) {
        $office_storage_bunches[] = ['protocol_version' => 'server'];
    }

    return $office_storage_bunches;
}

// ------------------------------------------------------------
// 3. Вспомогательные функции для HTTP-запросов
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

// ------------------------------------------------------------
// 4. Получение списка производителей
// ------------------------------------------------------------
function getManufacturersList($article, $geo_id) {
    global $DP_Config;

    $bunches = getOfficeStorageBunches();
    $manufacturers = [];

    foreach ($bunches as $bunch) {
        if (isset($bunch['protocol_version'])) {
            if ($bunch['protocol_version'] == 2) {
                // API-поставщик
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
                // Прайс-листы
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
                // Сервер кроссов
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

    // Дедупликация по manufacturer_show
    $unique = [];
    foreach ($manufacturers as $m) {
        $key = $m['manufacturer_show'] ?? $m['manufacturer'];
        if (!isset($unique[$key])) {
            $unique[$key] = $m;
        }
    }

    return array_values($unique);
}

// ------------------------------------------------------------
// 5. Получение аналогов (кроссов)
// ------------------------------------------------------------
function getCrosses($article, $manufacturer) {
    global $DP_Config;

    // Формируем search_object как в JavaScript
    $searchObject = [
        'article' => $article,
        'manufacturers' => [
            [
                'manufacturer' => $manufacturer,
                'manufacturer_show' => $manufacturer,
                // другие поля не обязательны для кроссов, но можно добавить
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

// ------------------------------------------------------------
// 6. Опрос складов (параллельный)
// ------------------------------------------------------------
function queryAllStorages($article, $manufacturer, $crosses, $geo_id, $user_id, $group_id) {
    global $DP_Config;

    $bunches = getOfficeStorageBunches();
    // Подготовим запросы для каждого склада
    $multiHandle = curl_multi_init();
    $curlHandles = [];
    $allProducts = [];

    // Для каждого склада формируем search_object
    foreach ($bunches as $index => $bunch) {
        if (isset($bunch['protocol_version']) && $bunch['protocol_version'] === 'server') {
            continue; // сервер кроссов не опрашиваем для товаров
        }

        // Формируем search_object аналогично JavaScript
        $searchObject = [
            'article' => $article,
            'manufacturers' => [],
            'analogs' => $crosses,
            'office_storage_bunches' => []
        ];

        // Добавляем производителя для данного склада (если есть)
        if (isset($bunch['protocol_version']) && $bunch['protocol_version'] == 2) {
            // Для API-поставщиков добавляем только тех производителей, которые есть в этом складе
            // В упрощённом варианте добавляем одного производителя (выбранного)
            $searchObject['manufacturers'][] = [
                'manufacturer' => $manufacturer,
                'manufacturer_show' => $manufacturer,
                'storage_id' => $bunch['storage_id'],
                'office_id' => $bunch['office_id']
            ];
        } elseif (isset($bunch['protocol_version']) && $bunch['protocol_version'] == 3) {
            // Для прайсов добавляем связки
            $searchObject['office_storage_bunches'] = $bunch['office_storage_bunches'];
            // Также добавляем производителя (для фильтрации)
            $searchObject['manufacturers'][] = [
                'manufacturer' => $manufacturer,
                'manufacturer_show' => $manufacturer,
                'storage_id' => 0,
                'office_id' => 0,
                'params' => ['type' => 'prices']
            ];
        } else {
            // Для остальных (protocol_version 1 или treelax_catalogue) – добавляем производителя
            $searchObject['manufacturers'][] = [
                'manufacturer' => $manufacturer,
                'manufacturer_show' => $manufacturer,
                'storage_id' => $bunch['storage_id'] ?? 0,
                'office_id' => $bunch['office_id'] ?? 0
            ];
        }

        // Формируем POST-данные
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

    // Выполняем все запросы параллельно
    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
    } while ($running > 0);

    // Собираем ответы
    foreach ($curlHandles as $index => $item) {
        $response = curl_multi_getcontent($item['ch']);
        $httpCode = curl_getinfo($item['ch'], CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($multiHandle, $item['ch']);
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['Products']) && is_array($data['Products'])) {
                // Добавляем каждому продукту идентификатор склада (если нет)
                foreach ($data['Products'] as &$product) {
                    if (!isset($product['storage_id'])) {
                        $product['storage_id'] = $item['bunch']['storage_id'] ?? 0;
                    }
                    if (!isset($product['office_id'])) {
                        $product['office_id'] = $item['bunch']['office_id'] ?? 0;
                    }
                }
                $allProducts = array_merge($allProducts, $data['Products']);
            }
        }
    }
    curl_multi_close($multiHandle);

    return $allProducts;
}

// ------------------------------------------------------------
// 7. Группировка продуктов по типам
// ------------------------------------------------------------
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
        // Преобразуем типы
        $p['exist'] = (int)$p['exist'];
        $p['time_to_exe'] = (int)$p['time_to_exe'];
        $p['price'] = (float)$p['price'];
        $p['storage_id'] = (int)$p['storage_id'];

        $is_required = ($p['article'] == $article && $p['manufacturer'] == $manufacturer);
        $is_search_name = isset($p['search_name']) && $p['search_name'] == 1;

        // Определяем группу
        if ($is_required) {
            $groups['required'][] = $p;
        } elseif ($is_search_name) {
            $groups['search_name'][] = $p;
        } elseif (isset($p['article']) && $p['article'] == $article) {
            // Возможные замены (тот же артикул, другой производитель)
            $groups['possible_replacement'][] = $p;
        } else {
            // Аналоги: быстрые (0-2 дня) и остальные
            if ($p['time_to_exe'] <= 2) {
                $groups['quick_analogs'][] = $p;
            } else {
                $groups['analogs'][] = $p;
            }
        }
    }

    // Можно также добавить логику для spare_box (например, склады с ID 1,23,28)
    // Но для простоты оставим как есть.

    return $groups;
}

// ------------------------------------------------------------
// 8. Применение фильтров
// ------------------------------------------------------------
function applyFilters($groups, $filters) {
    // Реализуем простую фильтрацию по диапазонам и спискам
    // Возвращаем отфильтрованные группы
    $filtered = [];
    foreach ($groups as $groupName => $items) {
        $filtered[$groupName] = [];
        foreach ($items as $product) {
            $pass = true;
            // Цена
            if (isset($filters['price_min']) && $product['price'] < $filters['price_min']) $pass = false;
            if (isset($filters['price_max']) && $product['price'] > $filters['price_max']) $pass = false;
            // Срок
            if (isset($filters['time_to_exe_max']) && $product['time_to_exe'] > $filters['time_to_exe_max']) $pass = false;
            // Наличие
            if (isset($filters['exist_min']) && $product['exist'] < $filters['exist_min']) $pass = false;
            // Производитель
            if (isset($filters['manufacturers']) && is_array($filters['manufacturers'])) {
                if (!in_array($product['manufacturer'], $filters['manufacturers'])) $pass = false;
            }
            // Склад
            if (isset($filters['storages']) && is_array($filters['storages'])) {
                if (!in_array($product['storage_id'], $filters['storages'])) $pass = false;
            }
            if ($pass) {
                $filtered[$groupName][] = $product;
            }
        }
    }
    return $filtered;
}

// ------------------------------------------------------------
// 9. Формирование карточек
// ------------------------------------------------------------
function buildCards($groups) {
    $cards = [
        'min_price_original' => null,
        'min_price_analog' => null,
        'min_delivery' => null
    ];

    // Самая низкая цена среди оригиналов (required)
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

    // Самая низкая цена среди аналогов (quick_analogs + analogs)
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

    // Наименьший срок доставки среди всех товаров
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

// ------------------------------------------------------------
// 10. Основная логика обработки запроса
// ------------------------------------------------------------
function handleSearchRequest() {
    global $input, $article, $manufacturer, $geo_id, $user_id, $group_id, $filters;

    // Получаем список производителей, если не указан
    if (empty($manufacturer)) {
        $manufacturers = getManufacturersList($article, $geo_id);
        if (empty($manufacturers)) {
            sendJsonResponse(successResponse([
                'manufacturers' => [],
                'products' => [],
                'cards' => []
            ], ['Товар не найден']), 200);
        }
        if (count($manufacturers) == 1) {
            $manufacturer = $manufacturers[0]['manufacturer_show'];
        } else {
            // Возвращаем список производителей
            sendJsonResponse(successResponse([
                'manufacturers' => $manufacturers,
                'products' => [],
                'cards' => []
            ]), 200);
        }
    }

    // Получаем кроссы
    $crosses = getCrosses($article, $manufacturer);

    // Опрос складов
    $products = queryAllStorages($article, $manufacturer, $crosses, $geo_id, $user_id, $group_id);

    // Группировка
    $grouped = groupProducts($products, $article, $manufacturer);

    // Фильтрация
    if (!empty($filters)) {
        $grouped = applyFilters($grouped, $filters);
    }

    // Карточки
    $cards = buildCards($grouped);

    // Ответ
    $responseData = [
        'manufacturers' => [$manufacturer], // или можно вернуть весь список
        'selected_manufacturer' => $manufacturer,
        'products' => $grouped,
        'cards' => $cards
    ];

    sendJsonResponse(successResponse($responseData), 200);
}

// Запускаем обработку
handleSearchRequest();