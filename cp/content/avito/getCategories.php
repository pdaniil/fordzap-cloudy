<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");

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

try {

    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db_link->query("
        SELECT 
            c.id,
            IFNULL(CONCAT(p.name, ' - ', c.name), c.name) AS name 
        FROM avito_categories c
        LEFT JOIN avito_categories p ON c.parent_id = p.id
        WHERE c.id NOT IN (SELECT DISTINCT parent_id FROM avito_categories WHERE parent_id IS NOT NULL)
        ORDER BY name
    ");
    $avito_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //$stmt = $db_link->query("SELECT id, value as name FROM shop_catalogue_categories");
    $stmt = $db_link->query("SELECT * FROM shop_catalogue_categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories_by_id = [];
    foreach ($categories as $category) {
        $categories_by_id[$category['id']] = $category;
    }

    function getCategoryChain($category, $categories_by_id) {
        $chain = [$category['value']];
        while ($category['parent'] != NULL) {
            $category = $categories_by_id[$category['parent']];
            array_unshift($chain, $category['value']);
        }
        return implode(' - ', $chain);
    }

    $shop_categories = [];
    foreach ($categories as $category) {
        $shop_categories[] = [
            'id' => $category['id'],
            'name' => getCategoryChain($category, $categories_by_id)
        ];
    }

    echo json_encode(['avito' => $avito_categories, 'shop' => $shop_categories]);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
