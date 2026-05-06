<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");


$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

try {
    // Получение данных из запроса
    $barcode = $_GET['barcode'];

    // Поиск данных по штрих-коду
    $query = "SELECT * FROM barcode WHERE barcode = :barcode";
    $stmt = $db_link->prepare($query);
    $stmt->bindParam(':barcode', $barcode, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
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

        $client = new \GuzzleHttp\Client();

        $url = "http://{$subdomain}.public.api.abcp.ru/search/articles";

        try {
            $response = $client->request('GET', $url, [
                'query' => [
                    'userlogin' => $login,
                    'userpsw' => $password,
                    'number' => $result['article'],
                    'brand' => $result['brand'],
                    'useOnlineStocks' => true
                ]
            ]);

            $body = $response->getBody();
            $data = json_decode($body, true);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $data = null;
        }

        echo json_encode(['success' => true, 'data' => $result, 'abcp' => $data]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Штрих-код не найден!']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
