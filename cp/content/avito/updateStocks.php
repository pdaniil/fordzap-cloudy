<?php
header('Content-Type: application/json; charset=utf-8');

require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php");

use GuzzleHttp\Client;

$DP_Config = new DP_Config;



// Подключение к БД
try {
    $db_link = new PDO(
        'mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db,
        $DP_Config->user,
        $DP_Config->password,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    $db_link->query("SET NAMES utf8");
} catch (Exception $e) {
    echo json_encode(array('error' => true, 'message' => 'Ошибка подключения к БД: ' . $e->getMessage()));
    exit;
}
// CSRF-проверка
require_once($_SERVER["DOCUMENT_ROOT"] . "/content/users/stop_csrf.php");

$http = new Client(array('timeout' => 60));

// 1) OAuth client_credentials
try {
    $resp = $http->post('https://api.avito.ru/token/', array(
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        ),
        'form_params' => array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $DP_Config->avito_client_id,
            'client_secret' => $DP_Config->avito_client_secret
        )
    ));
    $data = json_decode($resp->getBody(), true);
    if (empty($data['access_token'])) {
        echo json_encode(array('error' => true, 'message' => 'Не удалось получить access_token'));
        exit;
    }
    $accessToken = $data['access_token'];
} catch (Exception $e) {
    echo json_encode(array('error' => true, 'message' => 'Ошибка при получении токена: ' . $e->getMessage()));
    exit;
}

// 2) Берем товары со связкой категорий Авито. Нужны product_id и суммарное количество.
try {
    $sql = "
        SELECT
            ssd.product_id           AS product_id,
            SUM(ssd.exist)           AS quantity,
            ac.name                  AS avito_category_name,
            ac.parent_id             AS avito_category_parent
        FROM shop_storages_data ssd
        INNER JOIN shop_catalogue_products scp
            ON scp.id = ssd.product_id
        INNER JOIN shop_catalogue_categories scc
            ON scc.id = scp.category_id
        INNER JOIN avito_categories_mapping acm
            ON acm.shop_catalogue_categorie_id = scc.id
        INNER JOIN avito_categories ac
            ON ac.id = acm.avito_category_id
        GROUP BY ssd.product_id, ac.name, ac.parent_id
        HAVING quantity >= 0
    ";
    $stmt = $db_link->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo json_encode(array('error' => true, 'message' => 'Нет товаров со связкой категорий для обновления остатков'));
        exit;
    }
} catch (Exception $e) {
    echo json_encode(array('error' => true, 'message' => 'Ошибка выборки товаров: ' . $e->getMessage()));
    exit;
}

// 3) Список product_id из выборки
$productIds = array();
foreach ($rows as $r) {
    $pid = (int)$r['product_id'];
    if ($pid > 0) $productIds[$pid] = true;
}
$productIds = array_keys($productIds);

// 4) Узнаем, какие product_id уже замаплены в avito_items_map
try {
    $already = array();
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $db_link->prepare("SELECT product_id FROM avito_items_map WHERE product_id IN ($placeholders)");
        $stmt->execute($productIds);
        $already = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $already = $already ? array_map('intval', $already) : array();
    }
} catch (Exception $e) {
    echo json_encode(array('error' => true, 'message' => 'Ошибка проверки маппинга: ' . $e->getMessage()));
    exit;
}

// 5) Кому не хватает avito_id
$needMap = array_values(array_diff($productIds, $already));

// 6) Подтягиваем avito_id для недостающих product_id через GET /autoload/v2/items/avito_ids
// Документация: Response { "items": [ { "ad_id": "string", "avito_id": 0 } ] }
// Параметр query: строка c ID, разделенных запятой или вертикальной чертой.
if (!empty($needMap)) {
    $mapEndpoint = 'https://api.avito.ru/autoload/v2/items/avito_ids';
    $headersMap = array(
        'Authorization' => 'Bearer ' . $accessToken,
        'Accept'        => 'application/json'
    );

    // Разбиваем на порции по 100 id, чтобы не раздуть query
    $chunks = array_chunk($needMap, 100);
    $ins = $db_link->prepare("INSERT IGNORE INTO avito_items_map (product_id, avito_item_id) VALUES (?, ?)");

    foreach ($chunks as $chunk) {
        $queryParam = implode(',', $chunk);
        try {
            $resp = $http->request('GET', $mapEndpoint, array(
                'headers' => $headersMap,
                'query'   => array('query' => $queryParam)
            ));
            $payload = json_decode((string)$resp->getBody(), true);

            if (isset($payload['items']) && is_array($payload['items'])) {
                foreach ($payload['items'] as $item) {
                    // ad_id приходит строкой, приводим к int
                    $adId = isset($item['ad_id']) ? (int)$item['ad_id'] : 0;
                    $avitoId = isset($item['avito_id']) ? (int)$item['avito_id'] : 0;

                    if ($adId > 0 && $avitoId > 0) {
                        $ins->execute(array($adId, $avitoId));
                    }
                }
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            echo json_encode(array(
                'error'   => true,
                'stage'   => 'map_request',
                'message' => $e->getMessage()
            ));
        }

        // легкая пауза
        usleep(200000);
    }
}

// 7) Формируем массив stocks по avito_item_id
try {
    $sql = "
        SELECT
            aim.avito_item_id AS item_id,
            SUM(ssd.exist)    AS quantity
        FROM shop_storages_data ssd
        INNER JOIN shop_catalogue_products scp
            ON scp.id = ssd.product_id
        INNER JOIN shop_catalogue_categories scc
            ON scc.id = scp.category_id
        INNER JOIN avito_categories_mapping acm
            ON acm.shop_catalogue_categorie_id = scc.id
        INNER JOIN avito_categories ac
            ON ac.id = acm.avito_category_id
        INNER JOIN avito_items_map aim
            ON aim.product_id = ssd.product_id
        GROUP BY aim.avito_item_id
        HAVING quantity >= 0
    ";
    $stmt = $db_link->prepare($sql);
    $stmt->execute();
    $rowsStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo json_encode(array('error' => true, 'message' => 'Ошибка выборки stocks: ' . $e->getMessage()));
    exit;
}

$stocks = array();
if ($rowsStocks) {
    foreach ($rowsStocks as $r) {
        $itemId = (int)$r['item_id'];
        $qty    = (int)$r['quantity'];

        if ($itemId <= 0) continue;
        if ($qty < 0) $qty = 0;
        elseif ($qty > 999999) $qty = 999999;

        $stocks[] = array(
            'item_id'  => $itemId,
            'quantity' => $qty
        );
    }
}

if (empty($stocks)) {
    echo json_encode(array('error' => true, 'message' => 'Нет валидных остатков. Вероятно нет сопоставленных Avito ID'));
    exit;
}

// 8) Отправка остатков чанками по 200 через PUT /stock-management/1/stocks
$headersStocks = array(
    'Authorization' => 'Bearer ' . $accessToken,
    'Content-Type'  => 'application/json',
    'Accept'        => 'application/json'
);

// Возможные эндпойнты. Сначала без /core, потом с /core.
$stockEndpoints = array(
    'https://api.avito.ru/stock-management/1/stocks',
    'https://api.avito.ru/stock-management/1/stocks/',
);

function putStocksWithFallbackAndRetry($http, $headers, $batch, $urls) {
    $payload = array('stocks' => $batch);
    $lastException = null;

    foreach ($urls as $url) {
        $attempts = 0;
        while ($attempts < 3) {
            $attempts++;
            try {
                return $http->request('PUT', $url, array(
                    'headers' => $headers,
                    'json'    => $payload
                ));
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $code = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;

                // 404 — пробуем следующий URL
                if ($code === 404) {
                    $lastException = $e;
                    break;
                }

                // 429 или 5xx — повторим попытку до 3 раз
                if ($code === 429 || ($code >= 500 && $code <= 599)) {
                    usleep(500000);
                    $lastException = $e;
                    continue;
                }

                // остальные ошибки — пробрасываем
                throw $e;
            }
        }
    }

    if ($lastException) {
        throw $lastException;
    }

    return null;
}

$totalSent    = 0;
$totalSuccess = 0;
$totalFailed  = 0;
$details      = array();

$batches = array_chunk($stocks, 200);

foreach ($batches as $batch) {
    $totalSent += count($batch);

    try {
        $resp = putStocksWithFallbackAndRetry($http, $headersStocks, $batch, $stockEndpoints);
        $body = $resp ? (string)$resp->getBody() : '';
        $decoded = $body !== '' ? json_decode($body, true) : null;

        // В ответе обычно массив результатов. Перестрахуемся по ключам.
        if (isset($decoded['result']) && is_array($decoded['result'])) {
            $items = $decoded['result'];
        } elseif (isset($decoded['results']) && is_array($decoded['results'])) {
            $items = $decoded['results'];
        } elseif (isset($decoded['stocks']) && is_array($decoded['stocks'])) {
            $items = $decoded['stocks'];
        } elseif (is_array($decoded)) {
            $items = $decoded;
        } else {
            $items = array();
        }

        foreach ($items as $it) {
            $succ = !empty($it['success']);
            if ($succ) $totalSuccess++; else $totalFailed++;
            $details[] = array(
                'item_id'     => isset($it['item_id']) ? $it['item_id'] : null,
                'external_id' => isset($it['external_id']) ? $it['external_id'] : null,
                'success'     => $succ,
                'errors'      => isset($it['errors']) ? $it['errors'] : null
            );
        }

    } catch (Exception $e) {
        $totalFailed += count($batch);
        $details[] = array(
            'batch_error' => true,
            'message'     => $e->getMessage()
        );
    }

    // лимит 100 запросов в минуту
    sleep(1);
}

echo json_encode(array(
    'success'       => true,
    'total_sent'    => $totalSent,
    'total_success' => $totalSuccess,
    'total_failed'  => $totalFailed,
    'details'       => $details
));