<?php 
    ini_set("display_errors", 1);
    
    require_once($_SERVER["DOCUMENT_ROOT"] . "/" . $DP_Config->backend_dir . "/content/car_service/modules/workers_salary/WorkersSalary.php");
    $service_personal_list_arr = [];
    $service_personal_list_select = $db_link->prepare("SELECT * FROM `car_service_personal`;");
    $service_personal_list_select->execute();
    
    $service_personal_list_arr = $service_personal_list_select->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($service_personal_list_arr as $key => $worker) {
        $service_personal_list_arr[$key]["salary"] = WorkersSalary::calculate_worker_salary($worker["user_id"]);
        $service_personal_list_arr[$key]["records_info"] = WorkersSalary::get_detal_info($worker["id"]);
        $service_personal_list_arr[$key]["salary_info"] = WorkersSalary::get_salary_info($worker["id"]);
        $service_personal_list_arr[$key]["bill_info"] = WorkersSalary::get_bill_info($worker["id"]);
    }
    
?>