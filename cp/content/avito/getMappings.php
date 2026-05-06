<?php
//defined('_ASTEXE_') or die('No access');
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");

$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e){
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

try {
    $stmt = $db_link->query("SELECT m.id, m.avito_category_id, a.name as avito_category_name, m.shop_catalogue_categorie_id, s.value as shop_catalogue_category_name 
                             FROM avito_categories_mapping m
                             JOIN avito_categories a ON m.avito_category_id = a.id
                             JOIN shop_catalogue_categories s ON m.shop_catalogue_categorie_id = s.id");
    $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($mappings);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
