<?php 
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $result["status"] = false;
	$result["message"] = "DB connect error";
	$result["code"] = 502;
	exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id']) && isset($data['accounting_code'])) {
    $id = $data['id'];
    $accounting_code = $data['accounting_code'];	

	$sql = 'UPDATE `shop_offices_cash_codes` SET `accounting_code` = :accounting_code WHERE `id` = :id';
	$stmt = $db_link->prepare($sql);
	$stmt->bindParam(':accounting_code', $accounting_code, PDO::PARAM_STR);
	$stmt->bindParam(':id', $id, PDO::PARAM_INT);

	if ($stmt->execute()) {
		echo json_encode(['success' => true]);
	} else {
		echo json_encode(['success' => false, 'error' => 'Ошибка при выполнении запроса']);
	}
}
else {
    echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
}
?>