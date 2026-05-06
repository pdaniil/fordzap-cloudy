<?php
/**
 * This is a simple php example code of using Technomir Rest Api.
 */

/** @var string $apiToken generated in your personal account */
$apiToken = 'YOUR API TOKEN';

/**
 * Function send a request to the API server, calls the specified method and returns the request result.
 * @param array $method called method name
 * @param array $data   called method arguments
 * @return array query result like ['success' => true, data => []]
 */
function query($method, $data)
{
    global $apiToken;

    $data = array_merge($data, ['apiToken' => $apiToken]);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://api.tehnomir.com.ua/' . $method);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'accept: application/json']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($curl);
    curl_close($curl);

    return json_decode($result, true);
}

// 1. TEST CONNECT TO API SERVER
$connectRequest = query('test/connect', ['string' => 'Test connection']);
if ($connectRequest['success'] === false) {
    var_dump($connectRequest['data']);
    die('Bad test connection request.');
}

// 2. PRICING DETAIL WITHOUT BRAND
$pricingRequest = query('price/search', [
    'code'          => 'MN100250',
    'isShowAnalogs' => 0,
    'currency'      => 'USD'
]);
if ($pricingRequest['success'] === false) {
    var_dump($pricingRequest['data']);
    die('Bad pricing request.');
}

$details = [];
foreach ($pricingRequest['data'] as $pricingRequestItem) {
    foreach ($pricingRequestItem['rests'] as $pricingRequestItemRest) {
        $details[$pricingRequestItem['productId']][] = $pricingRequestItemRest;
    }
}
if (!count($details)) {
    die('Not found any available positions.');
}


// 3. PRICING DETAIL WIT BRAND
$pricingRequest = query('price/search', [
    'brandId'       => 555,
    'code'          => 'MN100250',
    'isShowAnalogs' => 1,
    'currency'      => 'USD'
]);
if ($pricingRequest['success'] === false) {
    var_dump($pricingRequest['data']);
    die('Bad pricing request.');
}

$details = [];
foreach ($pricingRequest['data'] as $pricingRequestItem) {
    foreach ($pricingRequestItem['rests'] as $pricingRequestItemRest) {
        $details[$pricingRequestItem['productId']][] = $pricingRequestItemRest;
    }
}
if (!count($details)) {
    die('Not found any available positions.');
}

// 3. ADD TO BASKET ALL OFFERED DETAILS
foreach ($details as $productId => $rests) {
    foreach ($rests as $rest) {
        $addToBasketRequest = query('basket/add', [
            'productId' => $productId,
            'priceLogo' => $rest['priceLogo'],
            'quantity'  => 1,
            'reference' => 'YOUR UNIQUE REFERENCE TO DETAIL',
            'comment'   => 'YOUR COMMENT TO DETAIL'
        ]);
        if ($addToBasketRequest['success'] === false) {
            var_dump($addToBasketRequest['data']);
            die('Bad add to basket request.');
        }
    }
}

// 4. CREATE ORDER
$createOrderRequest = query('order/create', ['orderNumber' => 'YOUR ORDER NUMBER']);
if ($createOrderRequest['success'] === false) {
    var_dump($createOrderRequest['data']);
    die('Bad create order request.');
}

$orderId = $createOrderRequest['data']['orderId'];

// 5. GET POSITION LIST IN CREATED ORDER
$orderPositionsRequest = query('order/getOrderPositions', ['orderId' => $orderId]);
if ($orderPositionsRequest['success'] === false) {
    var_dump($orderPositionsRequest['data']);
    die('Bad search order positions request.');
}

var_dump($orderPositionsRequest);

// 6. GET UNLOAD LIST FROM BEGIN OF CURRENT YEAR
$unloadListRequest = query('unload/search', [
    'fromDate' => date('Y-m-d', strtotime('first day of january this year')),
    'toDate'   => date('Y-m-d', time())
]);
if ($unloadListRequest['success'] === false) {
    var_dump($unloadListRequest['data']);
    die('Bad search unload list request.');
}

var_dump($unloadListRequest);
?>