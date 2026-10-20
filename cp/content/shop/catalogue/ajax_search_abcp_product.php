<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");

$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
    exit;
}

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

try {
    // Получение данных из AJAX-запроса
    $article = isset($_POST['article']) ? $_POST['article'] : null;
    $brand = isset($_POST['manufacturer']) ? $_POST['manufacturer'] : null;

    if (!$article || !$brand) {
        echo json_encode(['success' => false, 'message' => 'Missing article or manufacturer data.']);
        exit;
    }

    // Подготовка запроса к базе данных
    $queryAutorus = "SELECT connection_options FROM shop_storages WHERE interface_type = :id";
    $stmt1 = $db_link->prepare($queryAutorus);
    $id = 3;
    $stmt1->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt1->execute();
    $dataAutorus = $stmt1->fetch(PDO::FETCH_ASSOC);
    $dataAutorus = json_decode($dataAutorus['connection_options'], true);

    $login = $dataAutorus['login'];
    $password = md5($dataAutorus['password']);
    $subdomain = $dataAutorus['subdomain'];

    // Запрос к внешнему API
    $client = new \GuzzleHttp\Client();

    $url = "https://{$subdomain}.public.api.abcp.ru/search/articles";

    try {
        $response = $client->request('GET', $url, [
            'query' => [
                'userlogin' => $login,
                'userpsw' => $password,
                'number' => $article,
                'brand' => $brand,
                'useOnlineStocks' => true
            ]
        ]);

        $body = $response->getBody();
        $data = json_decode($body, true);


        if (isset($data) && is_array($data)) {
            foreach ($data as $item) {
                // Используем stripos для поиска подстроки без учета регистра
                if (stripos($item['brand'], $brand) !== false) {
                    $found_item = $item;
                    break; // Прерываем цикл, так как нашли нужный элемент
                }
            }
        }
        if ($found_item) {
            $description = $found_item['description']; // Получаем описание или имя
        }

        // Подготовка данных для ответа
        $result = [
            'brand' => $brand,
            'article' => $article,
            'name' => isset($description) ? $description : '',
        ];

        echo json_encode(['success' => true, 'data' => $result, 'abcp' => $data]);

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        echo json_encode(['success' => false, 'message' => 'API request error: ' . $e->getMessage()]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
