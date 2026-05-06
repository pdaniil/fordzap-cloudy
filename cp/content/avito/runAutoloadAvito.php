<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php");

use GuzzleHttp\Client;

$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

$client = new Client();

try {
    $response = $client->post('https://api.avito.ru/token/', [
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ],
        'form_params' => [
            'grant_type' => 'client_credentials',
            'client_id' => $DP_Config->avito_client_id,
            'client_secret' => $DP_Config->avito_client_secret
        ]
    ]);

    $data = json_decode($response->getBody(), true);

    if (isset($data['access_token'])) {
        $accessToken = $data['access_token'];
    } else {
        throw new Exception('Токен не был получен');
    }
} catch (\GuzzleHttp\Exception\RequestException $e) {
    echo json_encode(["error" => "Не удалось получить токен доступа.", "message" => $e->getMessage()]);
    return;
} catch (Exception $e) {
    echo json_encode(["error" => "Произошла ошибка.", "message" => $e->getMessage()]);
    return;
}

try {
    $response = $client->post('https://api.avito.ru/autoload/v1/upload', [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken
        ],
    ]);
    $result = json_decode($response->getBody(), true);
    echo json_encode(["success" => true, "data" => $result]);
} catch (\GuzzleHttp\Exception\RequestException $e) {
    echo json_encode(["error" => "Не удалось выполнить загрузку.", "message" => $e->getMessage()]);
}


