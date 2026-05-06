<?php 
	defined('_ASTEXE_') or die('No access');
	
	define("APP_PATH", $_SERVER["DOCUMENT_ROOT"] . '/' . $DP_Config->backend_dir . '/content/car_service/');
	define("URL", explode('?', $_SERVER['REQUEST_URI'])[0]);
	define("REDIRECT_URL", $DP_Config->domain_path . $DP_Config->backend_dir . '/car_service/');
	define("URL_PATH", explode('/', URL)[count(explode('/', URL)) - 1]);

    function get_price($record_id, $without_items = null) {
        global $db_link;

        //Получаем ставку нормо-часа
        $select_hourly_rate = $db_link->prepare("SELECT `id`,`hourly_rate` FROM `car_services` WHERE `id` = (SELECT `sto_id` FROM `car_service_records` WHERE `id` = ?)");
        $select_hourly_rate->execute([$record_id]);

        $hourly_rate = $select_hourly_rate->fetch(PDO::FETCH_ASSOC);

        $sto_id = $hourly_rate['id'];
        $hourly_rate = $hourly_rate['hourly_rate'];



        //Получаем авто и массив услуг
        $select_auto_services = $db_link->prepare("SELECT `services_arr`, `car_id` FROM `car_service_records` WHERE `id` = ?;");
        $select_auto_services->execute([$record_id]);

        $res_auto_serv = $select_auto_services->fetch(PDO::FETCH_ASSOC);

        $services_arr = json_decode($res_auto_serv['services_arr'], true);
        $car_id = $res_auto_serv['car_id'];

        //Получаем сумму позиций связанного заказа
        $select_sum_orders_items = $db_link->prepare("SELECT `order_id`, SUM(`price` * `count_need`) as `sum_price` FROM `shop_orders_items` WHERE `order_id` = (SELECT `order_id` FROM `car_service_records` WHERE `id` = ?) GROUP BY `order_id`;");
        $select_sum_orders_items->execute([$record_id]);

        $res_price = $select_sum_orders_items->fetch(PDO::FETCH_ASSOC);
        $orders_items_price = $res_price['sum_price'] > 0 ?   $res_price['sum_price'] : 0;


        $price = 0;
        //По всем услугам для уточнения цены
        if (is_array($services_arr))
            foreach ($services_arr as $service) {
                $select_fact = $db_link->prepare("SELECT `hourly_rate_count`, `difficulty_factor` FROM `car_service_prices` WHERE `sto_id` = ? AND `id_tree_cars_list` = ? AND `id_tree_services_list` = ?;");

                $select_fact->execute([$sto_id, $car_id, $service]);
                $res_price = $select_fact->fetch(PDO::FETCH_ASSOC);
                $sub_price = $hourly_rate * $res_price['hourly_rate_count'] + (($hourly_rate * $res_price['hourly_rate_count']) * $res_price['difficulty_factor'] / 100);

                $price += $sub_price;
            }

        if (is_null($without_items))
            $price += $orders_items_price;

        return $price > 0 ? $price : "";
    }

?>