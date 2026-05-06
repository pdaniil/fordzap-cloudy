<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Соединение с основной БД
$DP_Config = new DP_Config;//Конфигурация CMS

//Подключение к БД
try
{
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e)
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

require_once($_SERVER["DOCUMENT_ROOT"] . "/" . $DP_Config->backend_dir . "/content/car_service/modules/workers_salary/WorkersSalary.php");

function exitFunction($result) {
    exit(json_encode($result));
}


function pay_salary() {
	$worker_id = $_POST["worker_id"];
	$salary = $_POST["salary"];
	$office_id = $_POST["office_id"];
	
	WorkersSalary::paySalary($worker_id, $salary);
	
	if (!WorkersSalary::sendToBalance($worker_id, $salary, $office_id)) {
		exitFunction(["status" => 501, "message" => "Не удалось добавить средства на баланс."]);
	}
	
	exitFunction(["status" => 200]);
}
  

//$_POST = json_decode(file_get_contents("php://input"), true);

if (isset($_POST["action"]) && function_exists($_POST["action"]))
    $_POST["action"]();
else
    exitFunction(["status" => 404, "message" => "Метод не определён"]);
?>