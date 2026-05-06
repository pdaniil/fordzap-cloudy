<?php
//defined('_ASTEXE_') or die('No access');
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");

$DP_Config = new DP_Config;
try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

try {
    $id = $_POST['id'];
    $avito_category_id = $_POST['avito_category_id'];
    $shop_catalogue_categorie_id = $_POST['shop_catalogue_categorie_id'];

    if ($id) {
        // Обновление записи по ID
        $sql = "UPDATE avito_categories_mapping SET avito_category_id = :avito_category_id, shop_catalogue_categorie_id = :shop_catalogue_categorie_id WHERE id = :id";
        $stmt = $db_link->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':avito_category_id', $avito_category_id, PDO::PARAM_INT);
        $stmt->bindParam(':shop_catalogue_categorie_id', $shop_catalogue_categorie_id, PDO::PARAM_INT);
    } else {
        // Проверка на существование записи с данным shop_catalogue_categorie_id
        $check_sql = "SELECT id FROM avito_categories_mapping WHERE shop_catalogue_categorie_id = :shop_catalogue_categorie_id";
        $check_stmt = $db_link->prepare($check_sql);
        $check_stmt->bindParam(':shop_catalogue_categorie_id', $shop_catalogue_categorie_id, PDO::PARAM_INT);
        $check_stmt->execute();
        $existing_id = $check_stmt->fetchColumn();

        if ($existing_id) {
            // Запись существует, обновление по найденному ID
            $sql = "UPDATE avito_categories_mapping SET avito_category_id = :avito_category_id WHERE shop_catalogue_categorie_id = :shop_catalogue_categorie_id";
            $stmt = $db_link->prepare($sql);
            $stmt->bindParam(':avito_category_id', $avito_category_id, PDO::PARAM_INT);
            $stmt->bindParam(':shop_catalogue_categorie_id', $shop_catalogue_categorie_id, PDO::PARAM_INT);
        } else {
            // Запись не существует, вставка новой записи
            $sql = "INSERT INTO avito_categories_mapping (avito_category_id, shop_catalogue_categorie_id) VALUES (:avito_category_id, :shop_catalogue_categorie_id)";
            $stmt = $db_link->prepare($sql);
            $stmt->bindParam(':avito_category_id', $avito_category_id, PDO::PARAM_INT);
            $stmt->bindParam(':shop_catalogue_categorie_id', $shop_catalogue_categorie_id, PDO::PARAM_INT);
        }
    }

    $stmt->execute();

    if ($stmt->rowCount()) {
        echo json_encode(["status" => true, "message" => "Запись успешно сохранена!"]);
    } else {
        echo json_encode(["status" => false, "message" => "Error saving record"]);
    }
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
