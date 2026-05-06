<?php
/**
 * Страница одного заказа
 */
defined('_ASTEXE_') or die('No access');

//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");

//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$manager_id = DP_User::getAdminId();//ID менежера, который отображает эту страницу
?>

<?php
if(!empty($_POST["action"]))
{

    // -------------------------------------------------------------------------------
    //Защита от CSRF-атак
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
    // -------------------------------------------------------------------------------

    //Индикатор непрочитанных сообщений в заказах
    if($_POST["action"] == 'update_msg'){
        $flag = (int) $_POST["flag"];
        $order_id = (int) $_POST["order_id"];
        if($db_link->prepare("UPDATE `shop_orders_messages` SET `read` = ? WHERE `order_id` = ? AND `is_customer` = 1 ORDER BY `id` DESC LIMIT 1;")->execute( array($flag, $order_id) ) === true){
            ?>
            <script>
                location = "/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders";
            </script>
            <?php
            exit;
        }else{
            ?>
            <script>
                location = "/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?=$order_id;?>&error_message=Неудалось совершить операцию";
            </script>
            <?php
            exit;
        }
    }

}
else if(!empty($_POST["save_action"]))
{
    if($_POST["save_action"] != 'insert')
    {
        $error_message = 'Неизвестная операция';
    }
    else
    {
        //Технические данные для работы с заказами
        require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");


        //Подстрока для умножение цены на курс валюты склада
        $SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";

        $AdminId = (int) DP_User::getAdminId();

        $order_id = (int)$_POST['order_id'];
        $product_type = (int)$_POST['item_product_type'];


        if($product_type === 1){

            $id_storages_data = $_POST['id_storages_data'];
            $count_need = (int)$_POST['count_need'];

            $user_id = (int)$_POST['user_id'];
            $group_id = (int)$_POST['group_id'];
            $office_id = (int)$_POST['office_id'];

            $sql = "
			SELECT *,
			`price` * $SQL_currency_rate + `price` * $SQL_currency_rate * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = $office_id AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = $group_id AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price`, 
			(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`) AS 'caption', 
			
			(SELECT `value` FROM `shop_properties_values_text` WHERE `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_storages_data`.`category_id` AND `value` = 'Артикул' AND `property_type_id` = 3) AND `product_id` = `shop_storages_data`.`product_id`) AS article, 
			(SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `shop_storages_data`.`product_id` AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_storages_data`.`category_id` AND `value` = 'Производитель' AND `property_type_id` = 5))) AS manufacturer,
			
			(SELECT `value` FROM `shop_catalogue_categories` WHERE `id` = (SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`)) AS 'category', 
			(SELECT `name` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) AS 'storage_name' 
			FROM `shop_storages_data` WHERE `id` = $id_storages_data
			";

            $query = $db_link->prepare($sql);
            $query->execute();
            $product = $query->fetch();

            if(!empty($product)){

                /////////////////////////

                $work_price = $product['customer_price'];

                //Округление цены
                if($DP_Config->price_rounding == '1')//Без копеечной части
                {
                    if($work_price > (int)$work_price)
                    {
                        $work_price = (int)$work_price+1;
                    }
                    else
                    {
                        $work_price = (int)$work_price;
                    }
                }
                else if($DP_Config->price_rounding == '2')//До 5 руб
                {
                    $work_price = (integer)$work_price;
                    $price_str = (string)$work_price;
                    $price_str_last_char = (integer)$price_str[strlen($price_str)-1];
                    if($price_str_last_char > 0 && $price_str_last_char < 5)
                    {
                        $work_price = $work_price + (5 - $price_str_last_char);
                    }
                    else if($price_str_last_char > 5 && $price_str_last_char <= 9)
                    {
                        $work_price = $work_price + (10 - $price_str_last_char);
                    }
                }
                else if($DP_Config->price_rounding == '3')//До 10 руб
                {
                    $work_price = (integer)$work_price;
                    $price_str = (string)$work_price;
                    $price_str_last_char = (integer)$price_str[strlen($price_str)-1];
                    if($price_str_last_char != 0)
                    {
                        $work_price = $work_price + (10 - $price_str_last_char);
                    }
                }

                $product['customer_price'] = $work_price;

                /////////////////////////

                $name = $product['caption'];

                $product_id = $product["product_id"];
                $price = $product["customer_price"];
                if($count_need > 0 && $count_need <= $product["exist"]){

                    $article = $product['article'];
                    $manufacturer = $product['manufacturer'];
                    $name = $product['caption'];

                    $storage_id = (int)$product['storage_id'];
                    $price_zakup = (float)$product['price_purchase'];

                    $time_to_exe = 0;
                    $time_to_exe_guaranteed = 0;
                    $t2_exist = 0;

                }
            }

        }else{

            $article = $_POST['art'];
            $manufacturer = $_POST['man'];
            $name = $_POST['name'];

            $storage_id = (int)$_POST['storage_id'];
            $user_id = (int)$_POST['user_id'];

            $count_need = (int)$_POST['count_need'];
            $price = (float)$_POST['price'];//$t2_price_purchase + (($t2_price_purchase / 100) * ((float)$_POST['markup']));
            $price_zakup = (float)$_POST['price_zakup'];

            $time_to_exe = (int)$_POST['time_to_exe'];
            $time_to_exe_guaranteed = (int)$_POST['time_to_exe_guaranteed'];

            $office_id = (int)$_POST['office_id'];

            /******************/

            $product_id = 0;
            $t2_exist = 100;

            /******************/

        }

        //Получаем статус позиции заказа, который присваивается для вновь-созданной позиции
        $for_created_status_query = $db_link->prepare("SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `for_created`=1;");
        $for_created_status_query->execute();
        $for_created_status_record = $for_created_status_query->fetch();
        $for_created_status = $for_created_status_record["id"];


        $SQL_INSERT_ORDER_ITEM = "INSERT INTO `shop_orders_items` 
			(`order_id`, 
			`product_type`, 
			`price`, 
			`count_need`, 
			`product_id`, 
			`status`,
			`t2_manufacturer`,
			`t2_article`,
			`t2_article_show`,
			`t2_name`,
			`t2_exist`,
			`t2_time_to_exe`,
			`t2_time_to_exe_guaranteed`,
			`t2_storage`,
			`t2_min_order`,
			`t2_probability`,
			`t2_markup`,
			`t2_price_purchase`,
			`t2_office_id`,
			`t2_storage_id`,
			`t2_product_json`,
			`sao_state`,
			`sao_robot`,
			`t2_json_params`
			) 
			VALUES 
			(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";

        if($product_type == 1)
        {
            $binding_values = array($order_id, $product_type, $price, $count_need, $product_id, $for_created_status, $manufacturer, $article, $article, $name, $t2_exist, $time_to_exe, $time_to_exe_guaranteed, '', 1, 100, 0, 0, $office_id, $storage_id, '', 0, 0, '');
        }
        else
        {
            $binding_values = array($order_id, $product_type, $price, $count_need, $product_id, $for_created_status, $manufacturer, $article, $article, $name, $t2_exist, $time_to_exe, $time_to_exe_guaranteed, '', 1, 100, 0, $price_zakup, $office_id, $storage_id, '', 0, 0, '');
        }


        ////////////
        /*
        //Боевой SQL-запрос присваем в $SQL_bebug, чтобы боевой остался без изменений, т.к. он будет далее использоваться в скрипте
        $SQL_bebug = $SQL_INSERT_ORDER_ITEM;
        //Цикл по массиву значений, которые нужно биндить
        for( $i=0 ; $i < count($binding_values) ; $i++ )
        {
            $SQL_bebug = str_replace_once('?', $binding_values[$i], $SQL_bebug);
        }
        */
        //$f = fopen('lllll.txt', 'w');
        //fwrite($f, $SQL_bebug);

        ////////////


        if( $db_link->prepare($SQL_INSERT_ORDER_ITEM)->execute($binding_values) != true)
        {
            $error_message = "Ошибка: <br/> Не удалось добавить данные.";
        }
        else
        {
            $success_message = 'Успешно';

            if($product_type == 1)
            {
                $order_item_id =$db_link->lastInsertId();//ID добавленной записи (позиция заказа)

                $storage_record_id = $id_storages_data;
                $count_reserved = $count_need;
                $price_purchase = $price_zakup;

                //3.2 Создаем детальную запись позиции заказа
                if( $db_link->prepare('INSERT INTO `shop_orders_items_details` (`order_id`, `order_item_id`, `office_id`, `storage_id`, `storage_record_id`, `count_reserved`, `price_purchase`) VALUES (?,?,?,?,?,?,?);')->execute( array($order_id, $order_item_id, $office_id, $storage_id, $storage_record_id, $count_reserved, $price_purchase) ) == true)
                {
                    //3.1.Резервируем товар на складе
                    $db_link->prepare('UPDATE `shop_storages_data` SET `exist` = (`exist`-'.$count_need.'), `reserved` = (`reserved`+'.$count_need.') WHERE `id`= ?;')->execute( array($storage_record_id) );
                }
            }

        }
    }

    $location_url = '/'.$DP_Config->backend_dir.'/shop/orders/order?order_id='.$order_id;
    ?>
    <script>
        location="<?=$location_url?>&error_message=<?=$error_message;?>&success_message=<?=$success_message;?>";
    </script>
    <?php
    exit;
}
else//Действий нет - выводим страницу
{

    //Для работы с пользователем
    require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
    $user_session = DP_User::getAdminSession();

    ?>
    <?php
    require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>

    <?php
    $order_id = $_GET["order_id"];

    //Ставим флаг "Просмотрен"
    $db_link->prepare("UPDATE `shop_orders_viewed` SET `viewed_flag` = 1 WHERE `order_id` = ?;")->execute( array($order_id) );

    //Ставим флаг отсутствия отмененных позиций
    $db_link->prepare("UPDATE `shop_orders` SET `flag_canceled_items` = 0 WHERE `id` = ?;")->execute( array($order_id) );

    //Делаем все сообщения заказа прочитанными
    $db_link->prepare("UPDATE `shop_orders_messages` SET `read` = 1 WHERE `order_id` = ? AND `is_customer` = 1;")->execute( array($order_id) );




    //Подстрока с условиями фильтрования статусов позиций, которые не участвуют в ценовых расчетах
    $WHERE_statuses_not_count = "";
    for($i=0; $i<count($orders_items_statuses_not_count); $i++)
    {
        $WHERE_statuses_not_count .= " AND `status` != ".(int)$orders_items_statuses_not_count[$i];
    }

    //Для подсчета суммы оплаты по заказу
    $INCOME_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `order_id` = ?), 0)";
    $ISSUE_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `order_id` = ?),0)";


    //Для определения текущего баланса клиента
    $sub_balance_SQL = "";
    if( isset( $DP_Config->wholesaler ) )
    {
        $sub_balance_SQL = " AND `office_id` = `shop_orders`.`office_id` ";
    }
    $INCOME_USER_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `user_id` = `shop_orders`.`user_id` ".$sub_balance_SQL." ), 0)";
    $ISSUE_USER_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `user_id` = `shop_orders`.`user_id` ".$sub_balance_SQL." ),0)";


    //Получаем данные заказа
    $order_query = $db_link->prepare("SELECT *, (SELECT `caption` FROM `shop_obtaining_modes` WHERE `id` = `shop_orders`.`how_get`) AS `obtain_caption`, (SELECT `handler` FROM `shop_obtaining_modes` WHERE `id` = `shop_orders`.`how_get`) AS `obtain_handler`, CAST( (SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) AS DECIMAL(20,2)) AS `price_sum`, CAST( ($ISSUE_SQL - $INCOME_SQL) AS DECIMAL(20,2) ) AS `paid_sum`, CAST( ($INCOME_USER_SQL - $ISSUE_USER_SQL) AS DECIMAL(20,2) ) AS `customer_balance`, CAST( ( (SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) - ($ISSUE_SQL - $INCOME_SQL) ) AS DECIMAL(20,2) )  AS `paid_left` FROM `shop_orders` WHERE `id` = ?;");
    $order_query->execute( array($order_id, $order_id, $order_id, $order_id, $order_id) );
    $order = $order_query->fetch();
    if( $offices_list[$order["office_id"]] == NULL )
    {
        exit("Заказ не найден или у Вас нет прав для работы с этим заказом");
    }

    // Получаем список способов оплаты:
    $shop_orders_paid_type = array();
    $query = $db_link->prepare('SELECT * FROM `shop_orders_paid_type` WHERE `active` = 1 ORDER BY `order`;');
    $query->execute();
    while($rov = $query->fetch()){
        $shop_orders_paid_type[$rov['id']] = $rov['name'];
    }

    $time = $order["time"];
    $office_id = $order["office_id"];
    $status = $order["status"];
    $paid = $order["paid"];
    $paid_type = $order["paid_type"];

    $customer_id = $order["user_id"];
    $userProfile = DP_User::getUserProfileById($customer_id);
    $group_id = $userProfile["groups"][0];

    $how_get = $order["how_get"];
    $obtain_caption = $order["obtain_caption"];
    $obtain_handler = $order["obtain_handler"];
    $price_sum = $order["price_sum"];
    $paid_sum = $order["paid_sum"];
    $paid_left = $order["paid_left"];
    $for_warehouse = $order["for_warehouse"];
    $customer_balance = $order["customer_balance"];
    $how_get_json = json_decode($order["how_get_json"], true);
    ?>


    <div class="col-lg-6">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
                Данные заказа
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label for="" class="col-lg-3 control-label">
                        ID заказа
                    </label>
                    <div class="col-lg-9">
                        <?php echo $order_id; ?>
                    </div>
                </div>

                <div class="hr-line-dashed col-lg-12"></div>

                <div class="form-group">
                    <label for="" class="col-lg-3 control-label">
                        Создан
                    </label>
                    <div class="col-lg-9">
                        <?php echo date("d.m.Y", $time)." ".date("G:i", $time); ?>
                    </div>
                </div>

                <div class="hr-line-dashed col-lg-12"></div>

                <div class="form-group">
                    <label for="" class="col-lg-3 control-label">
                        Офис обслуживания
                    </label>
                    <div class="col-lg-9">
                        <?php echo $offices_list[$office_id]; ?>
                    </div>
                </div>

                <div class="hr-line-dashed col-lg-12"></div>

                <div class="form-group">
                    <label for="" class="col-lg-3 control-label">
                        Способ получения
                    </label>
                    <div class="col-lg-9">
                        <?php echo $obtain_caption; ?>
                    </div>
                </div>

                <div class="hr-line-dashed col-lg-12"></div>

                <div class="form-group">
                    <label for="" class="col-lg-3 control-label">
                        Способ оплаты
                    </label>
                    <div class="col-lg-9">
                        <?=(!empty($shop_orders_paid_type[$paid_type]))?$shop_orders_paid_type[$paid_type]:'';?>
                    </div>
                </div>

                <div class="hr-line-dashed col-lg-12"></div>

                <div class="form-group">
                    <label for="" class="col-lg-3 control-label">
                        Пополнение наличия
                    </label>
                    <div class="col-lg-9">
                        <?php echo $for_warehouse == '1' ? '<i class="fa fa-check" style="color: green"></i>' : ""; ?>
                    </div>
                </div>

                <div class="hr-line-dashed col-lg-12"></div>

                <div class="form-group">
                    <label for="" class="col-lg-3 control-label">
                        Статус всего заказа
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <select id="status_selector" onchange="document.getElementById('apply_order_status_button').disabled = 0;" class="form-control">
                                <?php
                                foreach($orders_statuses as $key => $value)
                                {
                                    $selected = "";
                                    if($status == $key)$selected = "selected=\"selected\"";
                                    ?>
                                    <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $orders_statuses[$key]["name"]; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <span class="input-group-btn">
								<button onclick="setOrderStatus();" disabled="disabled" id="apply_order_status_button" type="button" class="btn btn-success">Применить</button>
							</span>
                        </div>
                        <?php
                        //Статусы заказов:

                        //Получаем список статусов заказа с флагом "for_finish" - заказ выполнен
                        $orders_statuses_for_finish = array();
                        $query = $db_link->prepare("SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_finish` = 1;");
                        $query->execute();
                        while($status_row = $query->fetch() )
                        {
                            $orders_statuses_for_finish[] = $status_row["id"];
                        }

                        //Получаем список статусов заказа с флагом "for_inverse" - заказ отменен
                        $orders_statuses_for_inverse = array();
                        $query = $db_link->prepare("SELECT `id` FROM `shop_orders_statuses_ref` WHERE `for_inverse` = 1;");
                        $query->execute();
                        while($status_row = $query->fetch() )
                        {
                            $orders_statuses_for_inverse[] = $status_row["id"];
                        }

                        ?>
                        <script>
                            var orders_statuses_for_finish = JSON.parse('<?php echo json_encode($orders_statuses_for_finish); ?>');
                            var orders_statuses_for_inverse = JSON.parse('<?php echo json_encode($orders_statuses_for_inverse); ?>');

                            //Функция - изменить статус заказа
                            function setOrderStatus()
                            {
                                var needStatus = document.getElementById("status_selector").value;

                                if(orders_statuses_for_finish.indexOf(needStatus) !== -1){
                                    <?php
                                    // Если заказ не оплачен - тогда нельзя установить статус заказа "Выполнен"
                                    if($paid != 1){
                                    ?>

                                    alert('Ошибка. Данный статус нельзя присвоить до полной оплаты заказа');
                                    return;

                                    <?php
                                    }else{
                                    ?>

                                    if( !confirm("Помимо смены статуса заказа, всем его не отмененным позициям так же будет изменен статус автоматически. Продолжить?") )
                                    {
                                        return;
                                    }

                                    <?php
                                    }
                                    ?>
                                }

                                if(orders_statuses_for_inverse.indexOf(needStatus) !== -1){
                                    <?php
                                    // Если заказ не оплачен
                                    if($paid == 0){
                                    ?>

                                    if( !confirm("Помимо смены статуса заказа, всем его позициям так же будет изменен статус автоматически. Продолжить?") )
                                    {
                                        return;
                                    }

                                    <?php
                                    }else{
                                    ?>

                                    if( !confirm("Помимо смены статуса заказа, всем его позициям так же будет изменен статус автоматически. Все платежи по заказу будут возвращены. Продолжить?") )
                                    {
                                        return;
                                    }

                                    <?php
                                    }
                                    ?>
                                }

                                jQuery.ajax({
                                    type: "GET",
                                    async: false, //Запрос синхронный
                                    url: "/content/shop/protocol/set_order_status.php",
                                    dataType: "json",//Тип возвращаемого значения
                                    data: "initiator=1&orders=[<?php echo $order_id; ?>]&status="+needStatus+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                                    success: function(answer)
                                    {
                                        console.log(answer);
                                        if(answer.status == true)
                                        {
                                            //Обновляем страницу
                                            location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>&success_message='+encodeURI('Выполнено упешно');
                                        }
                                        else
                                        {
                                            if(answer.message){
                                                alert("Ошибка изменения статуса. "+answer.message);
                                            }else{
                                                alert("Ошибка изменения статуса");
                                            }
                                        }
                                    }
                                });
                            }
                        </script>
                    </div>
                </div>

                <div class="hr-line-dashed col-lg-12"></div>



                <div class="form-group">
                    <label for="" class="col-lg-3 control-label">
                        Действия
                    </label>
                    <div class="col-lg-9">
                        <button class="btn btn-danger" type="button" onclick="deleteOrder();"><i class="fas fa-trash"></i> <span class="bold">Удалить заказ</span></button>
                        <button class="btn btn-default" type="button" onclick="setOrderNoViewed();"><i class="fas fa-eye-slash"></i> <span class="bold">Сделать не просмотренным</span></button>
                    </div>
                </div>
                <script>
                    // --------------------------------------------------------------------------------------------
                    //Функция удаления заказа
                    function deleteOrder()
                    {
                        if( !confirm("Заказ будет безвозвратно удален. Продолжить?") )
                        {
                            return;
                        }


                        var orders_list = new Array();
                        orders_list.push(<?php echo $order_id; ?>);

                        jQuery.ajax({
                            type: "POST",
                            async: true, //Запрос асинхронный
                            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_delete_orders.php",
                            dataType: "json",//Тип возвращаемого значения
                            data: "orders_list="+JSON.stringify(orders_list)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                            success: function(answer)
                            {
                                if(answer.status == true)
                                {
                                    //Обновляем страницу
                                    location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
                                }
                                else
                                {
                                    alert(answer.message);
                                }
                            }
                        });
                    }
                    // --------------------------------------------------------------------------------------------
                    //Функция изменения статуса просмотра заказа
                    function setOrderNoViewed()
                    {
                        let orders = new Array();
                        orders.push(<?php echo $order_id; ?>);

                        //Объект запроса
                        var request_object = new Object;
                        request_object.orders = orders;
                        request_object.viewed_flag = 0;
                        request_object.user_id = <?php echo $manager_id; ?>;

                        jQuery.ajax({
                            type: "POST",
                            async: true, //Запрос асинхронный
                            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_set_orders_viewed.php",
                            dataType: "json",//Тип возвращаемого значения
                            data: "request_object="+JSON.stringify(request_object)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                            success: function(answer)
                            {
                                if(answer.status == true)
                                {
                                    //Обновляем страницу
                                    location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
                                }
                                else
                                {
                                    alert("Ошибка сервера");
                                }
                            }
                        });
                    }
                    // --------------------------------------------------------------------------------------------
                </script>

            </div>
        </div>

        <style>
            .cashless_payment {
                display: flex;
                grid-gap: 8px;
                align-items: flex-start;
                margin-top: 5px;
            }
        </style>


        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
                Покупатель
            </div>
            <div class="panel-body">
                <?php
                $customer_profile = DP_User::getUserProfileById($customer_id);//Получаем данные покупателя

                if($customer_id > 0)
                {
                    ?>
                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">ID клиента</label>
                        <div class="col-lg-9">
                            <?php
                            echo $customer_id;
                            ?>


                            <?php
                            //Профиль пользователя
                            if( $customer_id > 0 )
                            {
                                ?>
                                <a style="float: right; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; margin-right: 0px;" class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/users/usermanager/user?user_id=<?php echo $customer_id; ?>" target="_blank" title="Профиль пользователя">
                                    <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/user.png') 0 0 no-repeat; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; background-size: contain; margin-right: 0px;"></div>
                                </a>
                                <?php
                            }
                            ?>


                            <?php
                            //Статистика пользователя
                            if( $customer_id > 0 )
                            {
                                ?>
                                <a style="float: right; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; margin-right: 5px;" class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/users/usermanager/user?user_id=<?php echo $customer_id; ?>&type=statistics" target="_blank" title="Статистика пользователя">
                                    <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/statistics.png') 0 0 no-repeat; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; background-size: contain; margin-right: 0px;"></div>
                                </a>
                                <?php
                            }
                            ?>


                            <?php
                            //Авторизация от имени пользователя
                            if( $customer_id > 0 )
                            {
                                ?>
                                <a style="float: right; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; margin-right: 5px;" class="panel_a" href="javascript:void(0);" onclick="auth_with_user();" title="Авторизация от пользователя">
                                    <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/key.png') 0 0 no-repeat; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; background-size: contain; margin-right: 0px;"></div>
                                </a>
                                <script>
                                    //Функция авторизации от имени пользователя
                                    function auth_with_user()
                                    {
                                        jQuery.ajax({
                                            type: "POST",
                                            async: false, //Запрос синхронный
                                            url: "/<?php echo $DP_Config->backend_dir; ?>/content/users/auth_with_user.php",
                                            dataType: "json",//Тип возвращаемого значения
                                            data: "user_id=<?php echo $customer_id; ?>"+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                                            success: function(answer){
                                                if(answer.status == true)
                                                {
                                                    window.open(
                                                        '<?php echo $DP_Config->domain_path; ?>',
                                                        '_blank'
                                                    );
                                                }
                                                else
                                                {
                                                    alert("Ошибка авторизации");
                                                }
                                            }
                                        });
                                    }
                                </script>
                                <?php
                            }
                            ?>


                            <?php
                            //Заказы пользователя
                            if( $customer_id > 0 )
                            {
                                ?>
                                <a style="float: right; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; margin-right: 5px;" class="panel_a" href="javascript:void(0);" onclick="locationOrders();" title="Заказы пользователя">
                                    <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/store.png') 0 0 no-repeat; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; background-size: contain; margin-right: 0px;"></div>
                                </a>
                                <script>
                                    function locationOrders()
                                    {
                                        var orders_filter = new Object;
                                        //1. Время с
                                        orders_filter.time_from = "";
                                        //2. Время по
                                        orders_filter.time_to = "";
                                        //3. Номер заказа
                                        orders_filter.order_id = "";
                                        //4. Статус заказа
                                        orders_filter.status = 0;
                                        //5. Товар
                                        orders_filter.paid = -1;
                                        //6. Просмотрен
                                        orders_filter.viewed = -1;
                                        //7. Покупатель
                                        orders_filter.customer = "";
                                        orders_filter.customer_id = <?php echo $customer_id; ?>;
                                        orders_filter.paid_type = -1;
                                        //Устанавливаем cookie (на полгода)
                                        var date = new Date(new Date().getTime() + 15552000 * 1000);
                                        document.cookie = "orders_filter="+JSON.stringify(orders_filter)+"; path=/; expires=" + date.toUTCString();

                                        //Обновляем страницу
                                        window.open(
                                            '/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders',
                                            '_blank'
                                        );
                                    }
                                </script>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="hr-line-dashed col-lg-12"></div>
                    <?php

                    //Регистрационный вариант
                    $all_reg_variants_query = $db_link->prepare("SELECT COUNT(*) FROM `reg_variants`");//Для получения количества всех вариантов
                    $all_reg_variants_query->execute();
                    if($all_reg_variants_query->fetchColumn() > 1)
                    {
                        //Теперь запрос своего варианта
                        $user_reg_variant_query = $db_link->prepare("SELECT * FROM `reg_variants` WHERE `id` = ?;");
                        $user_reg_variant_query->execute( array($customer_profile["reg_variant"]) );
                        $user_reg_variant_record = $user_reg_variant_query->fetch();

                        echo "<div class=\"form-group\"><label for=\"\" class=\"col-lg-3 control-label\">Вариант регистрации</label><div class=\"col-lg-9\">".$user_reg_variant_record["caption"]."</div></div> <div class=\"hr-line-dashed col-lg-12\"></div>";
                    }//в противном случае не выводим регистрационный вариант


                    //Баланс клиента
                    ?>

                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">Баланс клиента</label>
                        <div class="col-lg-9">
                            <?php
                            if($customer_balance > 0){
                                echo number_format($customer_balance,2,'.',' ');
                            }else{
                                echo $customer_balance;
                            }
                            ?>


                            <?php
                            //Авторизация от имени пользователя
                            if( $customer_id > 0 )
                            {
                                ?>
                                <a style="float: right; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; margin-right: 0px;" class="panel_a" href="javascript:void(0);" onclick="locationBalance();" title="Операции с балансом">
                                    <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/credit_card.png') 0 0 no-repeat; width: 30px; height: 30px; max-width: 30px; max-height: 30px; min-width: 30px; min-height: 30px; display: inline-block; background-size: contain; margin-right: 0px;"></div>
                                </a>
                                <script>
                                    function locationBalance()
                                    {
                                        var account_operations_filter = new Object;

                                        account_operations_filter.time_from = "";
                                        account_operations_filter.time_to = "";
                                        account_operations_filter.income = -1;
                                        account_operations_filter.operation_code = -1;
                                        account_operations_filter.user_id = <?php echo $customer_id; ?>;

                                        //Устанавливаем cookie (на полгода)
                                        var date = new Date(new Date().getTime() + 15552000 * 1000);
                                        document.cookie = "account_operations_filter="+JSON.stringify(account_operations_filter)+"; path=/; expires=" + date.toUTCString();

                                        //Обновляем страницу
                                        window.open(
                                            '/<?php echo $DP_Config->backend_dir; ?>/shop/finance/account_operations',
                                            '_blank'
                                        );
                                    }
                                </script>
                                <?php
                            }
                            ?>

                        </div>
                    </div>
                    <div class="hr-line-dashed col-lg-12"></div>
                    <?php



                    if( $customer_id > 0 )
                    {
                        $orders = DP_User::getUserOrdersById($customer_id);// Суммарная информация по заказам клиента
                        ?>
                        <div class="form-group">
                            <label for="" class="col-lg-3 control-label">Заказы</label>
                            <div class="col-lg-9">
                                Всего: <?php echo number_format($orders['count'],0,'.',' '); ?> На сумму: <?php echo number_format($orders['total'],2,'.',' '); ?> Долг: <?php echo number_format($orders['total_debt'],2,'.',' '); ?>
                            </div>
                        </div>

                        <div class="hr-line-dashed col-lg-12"></div>
                        <?php
                    }



                    //Основные контакты
                    ?>
                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">Телефон</label>
                        <div class="col-lg-9">
                            <?php
                            if( !empty( $customer_profile['phone'] ) ) {
                                // Преобразуем номер телефона в международный формат
                                $phone = preg_replace('/[^0-9]/', '', $customer_profile['phone']); // Удаление всех символов, кроме цифр
                                $phone = '+'.$phone; // Добавление плюса для международного формата

                                // Создаем ссылку на WhatsApp
                                $whatsapp_link = 'https://wa.me/' . $phone;

                                echo '<a href="' . $whatsapp_link . '" target="_blank">';
                                echo $customer_profile['phone'];

                                if( $customer_profile['phone_confirmed'] == 1 ) {
                                    echo '<i class="fa fa-check-circle" style="color:#0A0;cursor:pointer;" title="Подтвержден"></i>';
                                } else {
                                    echo '<i class="fa fa-exclamation-triangle" style="color:#F00;cursor:pointer;" title="Не подтвержден"></i>';
                                }

                                echo '</a>';
                                echo '<a href="' . $whatsapp_link . '" target="_blank" title="Написать в WhatsApp">
                                    <i class="fab fa-whatsapp-square" style="color:#25D366; cursor:pointer; font-size: 20px; margin-left: 10px"></i>
                                </a>';
                            } else {
                                echo 'Не указан';
                            }
                            ?>

                        </div>
                    </div>

                    <div class="hr-line-dashed col-lg-12"></div>
                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">АВТО клиента</label>
                        <div class="col-lg-9">
                            <?
                            // Подготовим SQL-запрос для выборки данных из таблицы `shop_docpart_garage`
                            try {
                                // Подготовка запроса
                                $garage_query = $db_link->prepare("
                                SELECT 
                                    COALESCE(marka, 'не заполнено') AS marka,
                                    COALESCE(model, 'не заполнено') AS model,
                                    COALESCE(vin, 'не заполнено') AS vin
                                FROM shop_docpart_garage
                                WHERE user_id = :customer_id
                            ");

                                $garage_query->bindParam(':customer_id', $customer_id, PDO::PARAM_INT); // PDO::PARAM_STR если строка

                                // Выполнение запроса
                                $garage_query->execute();

                                // Получение и вывод данных
                                $garage_records = $garage_query->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($garage_records as $record) {
                                    echo $record['marka'] . " " . $record['model'] . " ";
                                    echo "<a href='javascript:void(0);' onclick='copyToClipboard(\"" . htmlspecialchars($record['vin']) . "\")'>";
                                    echo $record['vin'];
                                    echo "</a><br>";
                                }

                            } catch (PDOException $e) {
                                // Обработка и вывод ошибки
                                echo "Ошибка выполнения запроса: " . $e->getMessage();
                            }
                            ?>
                            <div id="copy-alert" class="alert alert-success alert-dismissible" style="display:none; position: fixed; top: 20px; right: 20px; z-index: 9999;">
                                <button type="button" class="close" onclick="closeAlert()">&times;</button>
                                VIN скопирован в буфер обмена!
                            </div>
                        </div>
                    </div>
                    <div class="hr-line-dashed col-lg-12"></div>


                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">E-mail</label>
                        <div class="col-lg-9">
                            <?php
                            if( !empty( $customer_profile['email'] ) )
                            {
                                echo $customer_profile['email'];

                                if( $customer_profile['email_confirmed'] == 1 )
                                {
                                    ?>
                                    <i class="fa fa-check-circle" style="color:#0A0;cursor:pointer;" title="Подтвержден"></i>
                                    <?php
                                }
                                else
                                {
                                    ?>
                                    <i class="fa fa-exclamation-triangle" style="color:#F00;cursor:pointer;" title="Не подтвержден"></i>
                                    <?php
                                }
                            }
                            else
                            {
                                ?>
                                Не указан
                                <?php
                            }
                            ?>
                        </div>
                    </div>

                    <div class="hr-line-dashed col-lg-12"></div>
                    <?php


                }

                $need_hr = false;


                //Перед выводом профиля получаем имена колонок таблицы users, чтобы отфильтровать их при выводе профиля
                $users_table_columns_query = $db_link->prepare("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE TABLE_NAME = 'users' AND `TABLE_SCHEMA` = '".$DP_Config->db."';");
                $users_table_columns_query->execute();
                $users_table_columns = array();
                while( $col_record =  $users_table_columns_query->fetch() )
                {
                    $users_table_columns[] = $col_record['COLUMN_NAME'];
                }


                foreach($customer_profile as $key => $value)
                {
                    //Фильтруем все, что не относится к users_profiles и что не нужно показывать пользователю
                    if( array_search($key, $users_table_columns ) !== false )
                    {
                        continue;
                    }

                    //Получаем название поля
                    $parameter = "";
                    if($key == "user_id")
                    {
                        $parameter = "ID пользователя";
                    }
                    else if($key == "groups")
                    {
                        $parameter = "Группы пользователя";
                        $groups_names = "";
                        //Получаем названия групп
                        for($i=0; $i < count($value); $i++)
                        {
                            $group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `id` = ?;');
                            $group_query->execute( array($value[$i]) );
                            $group_record = $group_query->fetch();
                            if($groups_names != "")
                            {
                                $groups_names .= ";<br>";
                            }
                            $groups_names .= $group_record["value"];
                        }
                        $value = $groups_names;//Для вывода
                    }
                    else
                    {
                        //Название из таблицы регистрационны полей
                        $field_caption_query = $db_link->prepare('SELECT * FROM `reg_fields` WHERE `name`=?;');
                        $field_caption_query->execute( array($key) );
                        $field_caption_record = $field_caption_query->fetch();
                        $parameter = $field_caption_record["caption"];
                    }

                    if($need_hr)
                    {
                        echo "<div class=\"hr-line-dashed col-lg-12\"></div>";
                    }
                    else
                    {
                        $need_hr = true;
                    }
                    ?>

                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">
                            <?php echo $parameter; ?>
                        </label>
                        <div class="col-lg-9">
                            <?php echo $value; ?>
                        </div>
                    </div>
                    <?php
                }//foreach($customer_profile AS $key => $value)
                ?>

                <?php
                //Если покупатель не авторизован - показываем его контакты
                if( $customer_id == 0)
                {
                    ?>
                    <div class="hr-line-dashed col-lg-12"></div>

                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">
                            Статус покупателя
                        </label>
                        <div class="col-lg-9">
                            Не зарегистрирован (ID 0)
                        </div>
                    </div>

                    <div class="hr-line-dashed col-lg-12"></div>

                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">
                            Телефон
                        </label>
                        <div class="col-lg-9">
                            <?php echo $order["phone_not_auth"]; ?>
                        </div>
                    </div>

                    <div class="hr-line-dashed col-lg-12"></div>

                    <div class="form-group">
                        <label for="" class="col-lg-3 control-label">
                            E-mail
                        </label>
                        <div class="col-lg-9">
                            <?php echo $order["email_not_auth"]; ?>
                        </div>
                    </div>
                    <?php
                }

                ?>
            </div>
        </div>
    </div>








    <?php
    /*
    Выводим блок оплаты, если заказ "Не оплачен" или "Частично оплачен"
    Выводим блок возврата, если заказ "Оплачен" или "Частично оплачен"
    */
    ?>
    <div class="col-lg-6">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                Учет платежей по заказу
            </div>
            <div class="panel-body">


                <div class="panel-footer contact-footer">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="contact-stat">
                                <span>Состояние: </span>
                                <strong>
                                    <?php
                                    switch( $paid )
                                    {
                                        case 0:
                                            echo '<div style="color:#FFF;background-color:#e74c3c;border-radius:3px;padding:6px 12px;font-weight:normal;">Не оплачен</div>';
                                            break;
                                        case 1:
                                            echo '<div style="color:#FFF;background-color:#62cb31;border-radius:3px;padding:6px 12px;font-weight:normal;">Оплачен полностью</div>';
                                            break;
                                        case 2:
                                            echo '<div style="color:#FFF;background-color:#3498db;border-radius:3px;padding:6px 12px;font-weight:normal;">Оплачен частично</div>';
                                            break;
                                    }
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="contact-stat">
                                <span>Сумма заказа: </span>
                                <strong><?php echo $price_sum; ?></strong>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="contact-stat">
                                <span>Оплачено клиентом: </span>
                                <strong><?php echo $paid_sum; ?></strong>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="contact-stat">
                                <span>Долг клиента: </span>
                                <strong><?php echo $paid_left; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>



                <?php
                //Блок добавления оплаты - выводим, если заказа еще оплачен не полностью
                if( $paid != 1 )
                {
                    ?>
                    <div class="hr-line-dashed col-lg-12"></div>
                    <div class="form-horizontal">
                        <div class="form-group col-md-12 text-center">
                            <label>Добавление оплаты в заказ:</label>
                        </div>
                        <?php
                        //Варианты - только для зарегистрированного покупателя
                        if( $customer_id > 0 )
                        {
                            ?>
                            <div class="form-group col-md-6">
                                <div class="col-sm-12">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" checked="" value="1" id="optionsRadios1" name="pay_source" /> Прямой платеж от клиента
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" value="0" id="optionsRadios2" name="pay_source" /> Платеж с текущего баланса клиента
                                        </label>
                                    </div>
                                    <div class="cashless_payment">
                                        <input type="checkbox" id="cashless_payment" name="cashless_payment" /> <div>Безналичный расчёт</div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        else
                        {
                            ?>
                            <div class="form-group col-md-6">
                                <div class="col-sm-12">
                                    Клиент не зарегистрирован, поэтому, оплата с баланса не возможна. Доступен только прямой платеж от клиента.
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="form-group col-md-6">
                            <label>К заказу будет добавлен платеж на сумму:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" placeholder="Введите сумму платежа" value="<?php echo $price_sum - $paid_sum; ?>" id="pay_value" />
                                <span class="input-group-btn">
									<button onclick="add_payment_to_order();" type="button" class="btn btn-success">Добавить платеж</button>
								</span>
                            </div>
                        </div>
                    </div>
                    <script>
                        //Обработка кнопки "Добавить платеж"
                        function add_payment_to_order()
                        {
                            //Сумма из поля ввода
                            var pay_value = document.getElementById('pay_value').value;
                            pay_value = parseFloat(pay_value).toFixed(2);

                            //Локальные проверки:

                            //1. Должна быть указана сумма
                            if( pay_value == '' || pay_value == 'NaN' )
                            {
                                alert('Укажите сумму платежа');
                                return;
                            }
                            //2. Сумма не должна превышать остаток долга клиента по заказу, не должна быть отрицательной, не должна быть равна 0
                            if( pay_value > <?php echo $price_sum - $paid_sum; ?> || pay_value <= 0 )
                            {
                                alert('Сумма не должна превышать остаток долга клиента по заказу, не должна быть отрицательной, не должна быть равна 0');
                                return;
                            }


                            var cashless_payment = document.getElementById('cashless_payment').checked ? '2' : '1';

                            //Источник оплаты
                            var direct_pay = 1;//Прямая оплата заказа от клиента (т.е. добавляется приход и той же расход)
                            <?php
                            //Если клиент зарегистрирован, то, доступна возможность списания денег с баланса клиента
                            if( $customer_id > 0 )
                            {
                            ?>
                            //Берем значение из радио-кнопок
                            direct_pay = $('input[name="pay_source"]:checked').val();

                            //Если оплата с баланса клиента, нужно проверить баланс. Если на балансе недостаточно средств, нужно предупредить продавца об этом и далее уже - на его усмотрение.
                            if( direct_pay == 0 )
                            {
                                if( pay_value > <?php echo $customer_balance; ?> )
                                {
                                    if( !confirm("На балансе клиента недостаточно средств. Если продожить, то, на балансе клиента возникнет перерасход. Продолжить?") )
                                    {
                                        return;
                                    }
                                }
                            }
                            <?php
                            }
                            ?>
                            //Если прямая оплата от клиента, то, другие проверки не делаем.

                            jQuery.ajax({
                                type: "GET",
                                async: false, //Запрос синхронный
                                url: "/content/shop/protocol/pay_for_order.php",
                                dataType: "text",//Тип возвращаемого значения
                                data: "cashless_payment="+cashless_payment+"&initiator=1&order_id=<?php echo $order_id; ?>&direct_pay="+direct_pay+"&pay_sum="+pay_value+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                                success: function(answer)
                                {
                                    console.log(answer);
                                    var answer_ob = JSON.parse(answer);

                                    //Если некорректный парсинг ответа
                                    if( typeof answer_ob.status === "undefined" )
                                    {
                                        alert("Неизвестная ошибка");
                                    }
                                    else
                                    {
                                        //Корректный парсинг ответа
                                        if(answer_ob.status == true)
                                        {
                                            //Обновляем страницу
                                            location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>&success_message='+encodeURI('Платеж успешно добавлен в заказ');
                                        }
                                        else
                                        {
                                            alert(answer_ob.message);
                                        }
                                    }
                                }
                            });

                        }
                    </script>
                    <?php
                }
                ?>


                <?php
                //Блок возврата оплаты выводим, только если по заказу есть оплата или предоплата
                if( $paid != 0 )
                {
                    ?>
                    <div class="hr-line-dashed col-lg-12"></div>
                    <div class="form-horizontal">
                        <div class="form-group col-md-12 text-center">
                            <label><button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Данный блок предназначен для отражения в системе учета платежей возврата оплаты по заказу. Возврат по заказу осуществляется, в случае, если ранее был отражен платеж по заказу и при этом заказ по каким-либо причинам не может быть выполнен, либо, если клиент вернул товар в магазин.<br><br>Для возврата:<br>1. Примите товар от клиента<br>2. Верните клиенту деньги (если клиент не хочет оставить их у себя на балансе)<br>3. При необходимости сформируйте фискальный чек<br>4. Затем, отразите возврат, нажав соответствующую кнопку ниже');"><i class="fa fa-info"></i></button> Возврат оплаты по заказу:</label>
                        </div>
                        <?php
                        if( $customer_id > 0 )
                        {
                            ?>
                            <button onclick="refund(0);" class="btn btn-primary " type="button"><i class="fas fa-money-check-alt"></i> <span class="bold">Возрат на баланс клиента</span></button>

                            <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Возврат оплаты на баланс клиента означает, что клиент сможет затем использовать денежные средства с баласа для оплаты других заказов.');"><i class="fa fa-info"></i></button>
                            <?php
                        }
                        else
                        {
                            ?>
                            Покупатель не зарегистрирован, поэтому, возврат оплаты на баланс не возможен. Можно сделать только прямой возврат оплаты покупателю.<br>
                            <?php
                        }
                        ?>
                        <button onclick="refund(1);" class="btn btn-primary " type="button"><i class="fas fa-hand-holding-usd"></i> <span class="bold">Прямой возврат клиенту</span></button> <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Прямой возврат клиенту означает, что вы вернули клиенту деньги наличными или на карту. Таким образом, после фактического возврата денег, нажмите эту кнопку, чтобы отразить возврат в системе учета платежей по заказам.');"><i class="fa fa-info"></i></button>
                        <div class="cashless_payment">
                            <input type="checkbox" id="cashless_payment" name="cashless_payment" /> <div>Безналичный расчёт</div>
                        </div>
                    </div>
                    <script>
                        //Обработка кнопок возврата
                        function refund(direct_refund)
                        {
                            if( !confirm('Отразить возврат в системе учета платежей по заказу?') )
                            {
                                return;
                            }

                            var cashless_payment = document.getElementById('cashless_payment').checked ? '2' : '1';

                            jQuery.ajax({
                                type: "POST",
                                async: false, //Запрос синхронный
                                url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_order_pay_refund.php",
                                dataType: "text",//Тип возвращаемого значения
                                data: "cashless_payment="+cashless_payment+"&direct_refund="+direct_refund+"&order_id=<?php echo $order_id; ?>"+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                                success: function(answer)
                                {
                                    console.log(answer);
                                    var answer_ob = JSON.parse(answer);

                                    //Если некорректный парсинг ответа
                                    if( typeof answer_ob.status === "undefined" )
                                    {
                                        alert("Неизвестная ошибка");
                                    }
                                    else
                                    {
                                        //Корректный парсинг ответа
                                        if(answer_ob.status == true)
                                        {
                                            //Обновляем страницу
                                            location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>&success_message='+encodeURI('Возврат успешно отражен в системе учета платежей');
                                        }
                                        else
                                        {
                                            alert(answer_ob.message);
                                        }
                                    }
                                }
                            });

                        }
                    </script>
                    <?php
                }
                ?>



            </div>
        </div>



        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
                Управление получением товара
            </div>
            <div class="panel-body">
                <?php
                require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/obtaining_modes/$obtain_handler/manager_interface.php");
                ?>
            </div>
        </div>


        <?php
        if($customer_id > 0){
            $user_query = $db_link->prepare('SELECT `comment` FROM `users` WHERE `user_id` = ?');
            $user_query->execute( array($customer_id) );
            $comment_row = $user_query->fetch();
            $comment = $comment_row['comment'];
            ?>
            <div class="hpanel">
                <div class="panel-heading hbuilt">
                    <div class="panel-tools">
                        <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                    </div>
                    Комментарий
                    <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Этот блок принадлежит клиенту в целом, а не конкретно этому заказу, он отображается в профиле пользователя и на странице заказа. Виден только в панели управления, клиент данный блок не видит.');"><i class="fa fa-info"></i></button>
                    <span id="user_comment_loader"></span>
                </div>
                <div class="panel-body">
                    <textarea onchange="setComment();" id="user_comment" style="width:100%; font-size: 14px; font-weight: bold;" rows="6"><?=$comment;?></textarea>
                    <script>
                        function setComment()
                        {
                            var comment = document.getElementById('user_comment').value;
                            document.getElementById('user_comment_loader').innerHTML = '<img style="height: 18px; margin: 0px 10px; position: absolute;" src="/content/files/images/ajax-loader-transparent.gif" />';

                            jQuery.ajax({
                                type: "POST",
                                async: true, //Запрос синхронный
                                url: "/<?php echo $DP_Config->backend_dir; ?>/content/users/ajax_set_user_comment.php",
                                dataType: "json",//Тип возвращаемого значения
                                data: "user_id=<?=$customer_id;?>&comment="+encodeURIComponent(comment)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                                success: function(answer)
                                {
                                    //console.log(answer);
                                    if(answer.status == true)
                                    {
                                        //alert('OK');
                                    }
                                    else
                                    {
                                        alert("Ошибка сохранения");
                                    }

                                    document.getElementById('user_comment_loader').innerHTML = '';
                                }
                            });
                        }
                    </script>
                </div>
            </div>
            <?php
        }
        ?>
    </div>


    <div class="">
        <div class="">
            <!-- Nav tabs -->
            <div class="col-md-12">
                <ul class="nav nav-tabs">
                    <li class="active" style="margin-bottom: -2px;"><a href="#tab_1" data-toggle="tab" style="z-index: 1;">Из каталога</a></li>
                    <li style="margin-bottom: -2px;"><a href="#tab_2" data-toggle="tab" style="z-index: 1;">Произвольно</a></li>
                </ul>
            </div>
            <!-- Tab panes -->
            <div class="tab-content">
                <div class="tab-pane active" id="tab_1">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <div class="hpanel">
                                <div class="panel-body">

                                    <div class="col-xs-12 col-md-8 col-lg-6">
                                        <h5>Поиск товара:</h5>
                                        <table style="width:100%;">
                                            <tr>
                                                <!-- Поле ввода для штрих-кода или артикула -->
                                                <td>
                                                    <input id="search" type="text" class="form-control" onInput="cat_search();" autocomplete="off" placeholder="Штрих код или артикул" />
                                                </td>

                                                <!-- Кнопка поиска -->
                                                <td style="display: inline-flex; gap: 5px; align-items: center;">
                                                    <!-- Кнопка поиска -->
                                                    <a href="javascript:void(0);" onClick="cat_search();"
                                                       style="border-radius: 0 3px 3px 0; text-align: center;"
                                                       class="btn btn-as btn-primary">
                                                        Поиск
                                                    </a>
                                                    <!-- Кнопка для открытия сканера -->
                                                    <a href="javascript:void(0);" onClick="openScanner();"
                                                       style="border-radius: 0 3px 3px 0; text-align: center;"
                                                       class="btn btn-as btn-secondary" title="Сканировать">
                                                        <i class="fas fa-camera"></i> <!-- Иконка камеры -->
                                                    </a>
                                                </td>

                                            </tr>
                                        </table>

                                        <!-- Overlay для затемнения фона -->
                                        <div id="overlay" onClick="closeScanner();" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999;"></div>

                                        <!-- Контейнер для сканера -->
                                        <div id="scanner-container" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; border-radius: 8px; padding: 20px; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                            <div id="reader" style="width: 300px; height: 300px;"></div> <!-- Контейнер для HTML5-QRCode -->
                                            <button onClick="closeScanner();" class="btn btn-danger" style="margin-top: 10px;">Закрыть</button>
                                        </div>

                                    </div>

                                    <div class="col-xs-12">
                                        <div id="product_div"></div>
                                    </div>

                                    <div class="col-xs-12">
                                        <div id="products_div"></div>
                                    </div>



                                    <style>
                                        .tab-content h5{
                                            margin-top:20px;
                                        }
                                        #products_div > div{
                                            max-height: 500px;
                                            overflow-y: auto;
                                        }
                                        #products_div .table{

                                        }
                                        #products_div .table tr:hover{
                                            background:#f3e7e7;
                                        }
                                        #products_div .table tr{
                                            color:#000;
                                        }
                                    </style>

                                    <script>
                                        var id_storages_data = 0;
                                        var exist_storages_data = 0;
                                        function add_product(id){

                                            id_storages_data = 0;
                                            exist_storages_data = 0;

                                            //Объект для запроса
                                            var request_object = new Object;
                                            request_object.action = 'get_product';
                                            request_object.id = encodeURIComponent(id);
                                            request_object.office_id = <?=(int)$office_id;?>;
                                            request_object.group_id = <?=(int)$group_id;?>;

                                            jQuery.ajax({
                                                type: "POST",
                                                async: false, //Запрос синхронный
                                                url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/orders_items_add.php",
                                                dataType: "json",//Тип возвращаемого значения
                                                data: "request_object="+encodeURI(JSON.stringify(request_object)),
                                                success: function(answer)
                                                {
                                                    //console.log(answer);
                                                    var html = '';

                                                    if(answer.status == true)
                                                    {
                                                        var product = answer.product;
                                                        if(product){
                                                            html += '<tr>';
                                                            html += '</td>';
                                                            html += '<td>'+product['manufacturer']+' - '+product['article']+' - '+product['caption']+'</td>';
                                                            html += '<td>'+product['category']+'</td>';
                                                            html += '<td>'+product['storage_id']+' - '+product['storage_name']+'</td>';
                                                            html += '<td>'+product['exist']+'</td>';
                                                            html += '<td>'+product['price']+'</td>';
                                                            html += '<td>'+product['customer_price']+'</td>';
                                                            html += '</tr>';

                                                            id_storages_data = product['id'];
                                                            exist_storages_data = product['exist'];
                                                        }
                                                    }

                                                    if(html != ''){
                                                        html = '<h5>Выбранный товар:</h5><table class="table"><tr><th>Наименование</th><th>Категория</th><th>Склад</th><th>Наличие</th><th>Закуп</th><th>Цена</th></tr>'+html+'</table>';
                                                        html += '<h5>Требуемое количество:</h5><div><input id="exist_inp" class="form-control" style="max-width:200px;" /><a href="javascript:void(0);" onClick="save_action();"  style="border-radius: 0 3px 3px 0; margin-top: 5px;" class="btn btn-as btn-primary">Добавить</a></div>';
                                                    }else{
                                                        html = 'Товар не найден в базе';
                                                    }

                                                    document.getElementById('product_div').innerHTML = html;
                                                },
                                                error: function(answer)
                                                {
                                                    var html = 'Произошла ошибка';
                                                    document.getElementById('product_div').innerHTML = html;
                                                }
                                            });
                                        }

                                        function cat_search(){
                                            document.getElementById("products_div").innerHTML = '';
                                            var value = document.getElementById("search").value;
                                            if(value != ''){
                                                //Объект для запроса
                                                var request_object = new Object;
                                                request_object.action = 'search';
                                                request_object.value = encodeURIComponent(value);
                                                request_object.office_id = <?=(int)$office_id;?>;
                                                request_object.group_id = <?=(int)$group_id;?>;

                                                document.getElementById("products_div").innerHTML = '<img id="img_crosses_add" style="height: 31px; margin-right: 5px; margin-top: 15px;" src="/content/files/images/ajax-loader-transparent.gif"/>';

                                                jQuery.ajax({
                                                    type: "POST",
                                                    async: false, //Запрос синхронный
                                                    url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/orders_items_add.php",
                                                    dataType: "json",//Тип возвращаемого значения
                                                    data: "request_object="+encodeURI(JSON.stringify(request_object)),
                                                    success: function(answer)
                                                    {
                                                        //console.log(answer);
                                                        var html = '';
                                                        var color_bg = '';
                                                        if(answer.status == true)
                                                        {
                                                            var products_list = answer.products_list;
                                                            if(products_list){
                                                                for(var i = 0; products_list.length > i; i++){
                                                                    color_bg = '';
                                                                    if(products_list[i]['exist'] <= 0 || products_list[i]['price'] <= 0){
                                                                        color_bg = ' style="color: #ccc;" ';
                                                                    }
                                                                    html += '<tr '+color_bg+' >';
																	html += '<td>';
																	
																	html += '<a href="' + products_list[i]['image'] + '" data-fancybox="gallery' + products_list[i]['product_id'] + '"><img class="product_image_stock" src="' + products_list[i]['image'] + '" /></a>';
																	html += '</td>';
																	html += '<td>';
                                                                    if(color_bg == ''){
                                                                        html += '<a class="btn btn-sm btn-primary" onclick="add_product('+products_list[i]['id']+');">Выбрать</a>';
                                                                    }
                                                                    html += '</td>';
                                                                    html += '<td>'+products_list[i]['manufacturer']+' - '+products_list[i]['article']+' - '+products_list[i]['caption']+'</td>';
                                                                    html += '<td>'+products_list[i]['category']+'</td>';
                                                                    html += '<td>'+products_list[i]['storage_id']+' - '+products_list[i]['storage_name']+'</td>';
                                                                    html += '<td>'+products_list[i]['exist']+'</td>';
                                                                    html += '<td>'+products_list[i]['price']+'</td>';
                                                                    html += '<td>'+products_list[i]['customer_price']+'</td>';
                                                                    html += '<td style="white-space: nowrap;"><a title="Редактор товара" target="_blank" href="/<?php echo $DP_Config->backend_dir; ?>/shop/catalogue/products/product?category_id='+products_list[i]['category_id']+'&product_id='+products_list[i]['product_id']+'"><img src="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/cargo.png" style="width:20px;"></a><a style="margin-left:5px;" title="Управление наличием" target="_blank" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/stock/product?product_id='+products_list[i]['product_id']+'"><img src="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/cargo_control.png" style="width:20px;"></a></td>';
                                                                    html += '</tr>';
                                                                }
                                                            }
                                                        }

                                                        if(html != ''){
                                                            html = '<h5>Найденные товары:</h5><div><table class="table"><tr><th></th><th></th><th>Наименование</th><th>Категория</th><th>Склад</th><th>Наличие</th><th>Закуп</th><th>Цена</th><th></th></tr>'+html+'</table></div>';
                                                        }else{
                                                            html = 'Товары не найдены';
                                                        }

                                                        document.getElementById('products_div').innerHTML = html;
                                                    },
                                                    error: function(answer)
                                                    {
                                                        var html = 'Произошла ошибка, попробуйте запросить другое наименование';
                                                        document.getElementById('products_div').innerHTML = html;
                                                    }
                                                });
                                            }
                                        }
                                    </script>



<style>
.product_image_stock {
    width: 50px; /* Устанавливает ширину изображения */
    border: 2px solid #ccc; /* Изменена рамка на серую и толщину 2px */
    border-radius: 5px; /* Добавляет скругление углов */
    cursor: pointer; /* Курсор при наведении становится указателем */
    transition: all 0.3s ease; /* Плавные переходы для эффектов */
}

.product_image_stock:hover {
    border-color: #007bff; /* При наведении цвет рамки меняется на синий */
    transform: scale(1.1); /* Увеличение изображения при наведении */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Тень вокруг изображения */
}
</style>
<!-- Подключение стилей Fancybox -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css">

<!-- Подключение скриптов Fancybox -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane" id="tab_2">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <div class="hpanel">
                                <div class="panel-body">

                                    <div>
                                        <h5>Данные позиции:</h5>
                                        <label>Артикул:</label><br/>
                                        <input style="width:500px;" id="art_item_inp" type="text" value="" class="form-control" />
                                        <br/><label>Производитель:</label><br/>
                                        <input style="width:500px;" id="man_item_inp" type="text" value="" class="form-control" /><br/>
                                        <label>Наименование:</label><br/>
                                        <input style="width:500px;" id="name_item_inp" type="text" value="" class="form-control" />
                                    </div>


                                    <br>


                                    <table>
                                        <tr>
                                            <td>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped table-bordered">
                                                            <thead>
                                                            <th>Склад</th>
                                                            <th>Цена</th>
                                                            <th>Закупочная Цена</th>
                                                            <th>Количество</th>
                                                            <th>Ожидаемый срок в днях</th>
                                                            <th>Гарантированный срок в днях</th>
                                                            </thead>
                                                            <tbody>

                                                            <tr>
                                                                <td>
                                                                    <select id="inp_storage_id" class="form-control">
                                                                        <?php
                                                                        foreach($storages_list as $k => $v){
                                                                            echo '<option value="'.$k.'">'.$v.' ('.$k.')</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                <td><input class="form-control" type="text" id="inp_price" value="<?php echo number_format(0, 2, '.', ''); ?>" /> </td>
                                                                <td><input class="form-control" type="text" id="inp_price_zakup" value="<?php echo number_format(0, 2, '.', ''); ?>" /> </td>
                                                                <td><input class="form-control" type="text" id="inp_count_need" value="1" /></td>


                                                                <td><input class="form-control" id="time_to_exe_item_inp" type="text" value="1"/></td>
                                                                <td><input class="form-control" id="time_to_exe_guaranteed_item_inp" type="text" value="5"/></td>

                                                            </tr>


                                                            </tbody>
                                                        </table>

                                                        <a href="javascript:void(0);" onClick="save_action();"  style="border-radius: 0 3px 3px 0; margin-top: 5px;" class="btn btn-as btn-primary">Добавить</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>



                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="save_form" name="save_form" method="POST" style="display:none;">
        <input type="hidden" name="save_action" id="save_action" value="insert" />
        <input type="hidden" id="order_id" name="order_id" value="<?=$order_id;?>" />
        <input type="hidden" id="price" name="price" value="" />
        <input type="hidden" id="price_zakup" name="price_zakup" value="" />
        <input type="hidden" id="count_need" name="count_need" value="" />
        <input type="hidden" id="user_id" name="user_id" value="<?=$customer_id;?>" />
        <input type="hidden" id="group_id" name="group_id" value="<?=$group_id;?>" />
        <input type="hidden" id="storage_id" name="storage_id" value="" />
        <input type="hidden" id="art" name="art" value="" />
        <input type="hidden" id="man" name="man" value="" />
        <input type="hidden" id="name" name="name" value="" />
        <input type="hidden" id="item_product_type" name="item_product_type" value="2" />

        <input type="hidden" id="time_to_exe" name="time_to_exe" value="" />
        <input type="hidden" id="time_to_exe_guaranteed" name="time_to_exe_guaranteed" value="" />

        <input type="hidden" id="id_storages_data" name="id_storages_data" value="" />

        <input type="hidden" id="office_id" name="office_id" value="<?=$office_id;?>" />
    </form>

    <script>
        //Функция сохранения (отправка формы)
        function save_action(){
            debugger

            // Определяем какая вкладка активна
            var access = 0;
            var tab = 0;

            if ( $('#tab_1').hasClass('active')) {
                tab = 1;
            }
            if ( $('#tab_2').hasClass('active')) {
                tab = 2;
            }



            if(tab == 1){

                if(id_storages_data == 0){
                    alert('Не выбран товар');
                    return false;
                }

                var exist = document.getElementById('exist_inp').value;
                
                if(exist != "")
                {
                    if(parseInt(exist_storages_data) < parseInt(exist) || parseInt(exist) <= 0){
                        alert('Не верно указано количество товара');
                        return false;
                    }
                }
                else
                {
                    alert('Не указано количество товара');
                    return false;
                }

                document.getElementById('id_storages_data').value = id_storages_data;
                document.getElementById('count_need').value = exist;
                document.getElementById('item_product_type').value = 1;

                access = 1;
            }

            if(tab == 2){
                document.getElementById('price').value = document.getElementById('inp_price').value;
                document.getElementById('price_zakup').value = document.getElementById('inp_price_zakup').value;
                document.getElementById('count_need').value = document.getElementById('inp_count_need').value;

                document.getElementById('storage_id').value = document.getElementById('inp_storage_id').value;

                document.getElementById('art').value = document.getElementById('art_item_inp').value;
                document.getElementById('man').value = document.getElementById('man_item_inp').value;
                document.getElementById('name').value = document.getElementById('name_item_inp').value;

                document.getElementById('time_to_exe').value = document.getElementById('time_to_exe_item_inp').value;
                document.getElementById('time_to_exe_guaranteed').value = document.getElementById('time_to_exe_guaranteed_item_inp').value;

                access = 1;
            }

            if(access == 1){
                document.forms["save_form"].submit();
            }
        }

    </script>

		<?php if($for_warehouse == '1') : ?>
			<div id="modal-addCatalog" style="display:none;">
        <div class="modal-addCatalog__header">
           <div class="modal-addCatalog__title">Оприходование позиции <span class="modal-addCatalog__itemId" data-item-id=""></span> на склад</div>
					 <div class="modal-addCatalog__subtitle">Количество: <span class="modal-addCatalog__itemCount" data-count-need="" ></span></div>
        </div>
        <div class="modal-addCatalog__body">

					<div class="form-search">
						<input id="modal-addCatalog__search" type="text" class="form-control" onInput="addCatalog_search();" autocomplete="off" placeholder="Штрих код или артикул" />
					</div>

					<div class="form-products-list">
					</div>
        </div>
			</div>

			<script>
				jQuery(document).ready(function(){
					if(Fancybox) {
						Fancybox.bind('[data-modal]', {hideScrollbar: false});
					}
				});

				function showAddCatalogModal(el) {

					const modalElement = document.getElementById('modal-addCatalog');

					if(modalElement) {

						let itemId = el.dataset.itemId || null;
						let itemCount = el.dataset.itemCountNeed || null;
						let itemArticle = el.dataset.itemArticle || null;

						Fancybox.show(
							[{
						   src: "#modal-addCatalog",
						   type: "inline"
							}],
							{
								hideScrollbar: false,
								on: {
								  "ready": (fancybox) => {
								     
										const modal_itemId_el = modalElement.querySelector('.modal-addCatalog__itemId');
										const modal_itemCount_el = modalElement.querySelector('.modal-addCatalog__itemCount');
										const modal_search = document.getElementById('modal-addCatalog__search');
  
									  if (modal_itemId_el) {
									    modal_itemId_el.textContent = 'ID ' + itemId;
											modal_itemId_el.dataset.itemId = itemId;
									  }
										if (modal_itemCount_el) {
									    modal_itemCount_el.textContent = itemCount + ' шт.';
											modal_itemCount_el.dataset.countNeed = itemCount;
									  }
										if (modal_search) {
									    modal_search.value = itemArticle;

											addCatalog_search();
									  }

								  },
									"close": (fancybox) => {
										clearAddCatalogModal();
									}
								},
								
							}
						);
					}
					else
					{
						alert('HTML для модального окна не найден.');
					}
				}

				function clearAddCatalogModal() {

					const modalElement = document.getElementById('modal-addCatalog');
					
					if(modalElement) {

						const modal_itemId_el = modalElement.querySelector('.modal-addCatalog__itemId');
						const modal_itemCount_el = modalElement.querySelector('.modal-addCatalog__itemCount');
						const modal_productList_el = modalElement.querySelector('.form-products-list');
						const modal_search = document.getElementById('modal-addCatalog__search');
						
  
					  if (modal_itemId_el) {
					    modal_itemId_el.textContent = '';
					    modal_itemId_el.dataset.itemId = '';
					  }
						if (modal_itemCount_el) {
					    modal_itemCount_el.textContent = '';
							modal_itemCount_el.dataset.countNedd = '';
					  }
						if (modal_productList_el) {
					    modal_productList_el.innerHTML = '';
					  }
						if (modal_search) {
					    modal_search.value = '';
					  }
					}
					else
					{
						alert('HTML для модального окна не найден.');
					}
				}

				function addCatalog_search(){
					const modalElement = document.getElementById('modal-addCatalog');

					if(modalElement) {

						const productsList = modalElement.querySelector('.form-products-list');
						let searchTxt = document.getElementById("modal-addCatalog__search").value;

						let countNeed_el = modalElement.querySelector('.modal-addCatalog__itemCount');
						let countNeed = countNeed_el.dataset.countNeed;

						const itemId_el = modalElement.querySelector('.modal-addCatalog__itemId');
						let itemId = itemId_el.dataset.itemId;

						productsList.innerHTML = '';

						if(searchTxt != ''){

              //Объект для запроса
              var request_object = new Object;
              request_object.action = 'search';
              request_object.value = encodeURIComponent(searchTxt);
              request_object.office_id = <?=(int)$office_id;?>;
              request_object.group_id = <?=(int)$group_id;?>;

              productsList.innerHTML = '<img id="img_crosses_add" style="height: 31px; margin-right: 5px; margin-top: 15px;" src="/content/files/images/ajax-loader-transparent.gif"/>';

              jQuery.ajax({
                  type: "POST",
                  async: false, //Запрос синхронный
                  url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/orders_items_add.php",
                  dataType: "json",//Тип возвращаемого значения
                  data: "request_object="+encodeURI(JSON.stringify(request_object)),
                  success: function(answer)
                  {
                      //console.log(answer);
                      var html = '';
                      var color_bg = '';
                      if(answer.status == true)
                      {
                          var products_list = answer.products_list;
                          if(products_list){
                              for(var i = 0; products_list.length > i; i++){
                                  color_bg = '';
                                  html += '<tr '+color_bg+' >';
                                  html += '<td>';
                                  if(color_bg == ''){
                                      html += '<a class="btn btn-sm btn-primary" onclick="add_count_to_product('+products_list[i]['id']+', '+countNeed+', '+itemId+');">Добавить сюда</a>';
                                  }
                                  html += '</td>';
                                  html += '<td>'+products_list[i]['manufacturer']+' - '+products_list[i]['article']+' - '+products_list[i]['caption']+'</td>';
                                  html += '<td>'+products_list[i]['category']+'</td>';
                                  html += '<td>'+products_list[i]['storage_id']+' - '+products_list[i]['storage_name']+'</td>';
                                  html += '<td>'+products_list[i]['exist']+'</td>';
                                  html += '<td>'+products_list[i]['price']+'</td>';
                                  html += '<td>'+products_list[i]['customer_price']+'</td>';
                                  html += '<td style="white-space: nowrap;"><a title="Редактор товара" target="_blank" href="/<?php echo $DP_Config->backend_dir; ?>/shop/catalogue/products/product?category_id='+products_list[i]['category_id']+'&product_id='+products_list[i]['product_id']+'"><img src="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/cargo.png" style="width:20px;"></a><a style="margin-left:5px;" title="Управление наличием" target="_blank" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/stock/product?product_id='+products_list[i]['product_id']+'"><img src="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/cargo_control.png" style="width:20px;"></a></td>';
                                  html += '</tr>';
                              }
                          }
                      }

                      if(html != ''){
                          html = '<h5>Найденные товары:</h5><div><table class="table"><tr><th></th><th>Наименование</th><th>Категория</th><th>Склад</th><th>Наличие</th><th>Закуп</th><th>Цена</th><th></th></tr>'+html+'</table></div>';
                      }else{
                          html = 'Товары не найдены';
                      }

                      productsList.innerHTML = html;
                  },
                  error: function(answer)
                  {
                      var html = 'Произошла ошибка, попробуйте запросить другое наименование';
                      productsList.innerHTML = html;
                  }
              });
            }
						
					}
					else
					{
					alert('HTML для модального окна не найден.');
					}
        }

				function add_count_to_product(id, count_need, itemId){

					//Объект для запроса
					var request_object = new Object;
					request_object.action = 'add_count_to_product';
					request_object.id = encodeURIComponent(id);
					request_object.count_need = encodeURIComponent(count_need);
					request_object.office_id = <?=(int)$office_id;?>;
					request_object.group_id = <?=(int)$group_id;?>;

					jQuery.ajax({
							type: "POST",
							async: false, //Запрос синхронный
							url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/orders_items_add.php",
							dataType: "json",//Тип возвращаемого значения
							data: "request_object="+encodeURI(JSON.stringify(request_object)),
							success: function(answer)
							{
									if(answer.status == true)
									{
										alert('Наличие товара успешно увеличено.');

										addExistDone(itemId);

										clearAddCatalogModal();
									}
									else
									{
										alert('Произошла ошибка увеличения количества товара на складе.');
									}
							},
							error: function(answer)
							{
								alert('Произошла ошибка увеличения количества товара на складе.');
							}
					});
				}

				function addExistDone(itemId) {

					//Объект для запроса
					var request_object = new Object;
					request_object.action = 'change_item_status';
					request_object.item_id = encodeURIComponent(itemId);

					jQuery.ajax({
							type: "POST",
							async: false, //Запрос синхронный
							url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/orders_items_add.php",
							dataType: "json",//Тип возвращаемого значения
							data: "request_object="+encodeURI(JSON.stringify(request_object)),
							success: function(answer)
							{
									if(answer.status == true)
									{
										location.reload();
									}
									else
									{
										alert('Произошла ошибка изменения статуса оприходования у позиции товара.');
									}
							},
							error: function(answer)
							{
								alert('Произошла ошибка изменения статуса оприходования у позиции товара.');
							}
					});
				}

			</script>

			<style>
				.btn-add-catalog {
					padding: 6px;
			    font-size: 12px;
			    line-height: 1;
			    background-color: #91dfff;
					opacity: 1;
					transition: background 0.1s ease-in-out;
					outline: none
				}
				.btn-add-catalog.done {
					background-color: #c3ff91; 
				}
				.btn-add-catalog:hover, .btn-add-catalog:focus {
					background-color: #81cdec;
					outline: none;
					box-shadow: none;
				}
				.btn-add-catalog:active {
					background-color: #70b5d0;
					outline: none;
					box-shadow: none;
				}
				.modal-addCatalog__header {
					
				}
				.modal-addCatalog__title {
					font-size: 18px;
			    font-weight: 600;
			    margin-bottom: 10px;
				}
				.modal-addCatalog__subtitle {
					font-size: 18px;
			    font-weight: 600;
			    margin-bottom: 20px;
				}
				.modal-addCatalog__body {
					
				}
				.form-products-list {
					padding: 20px;
				}
			</style>
		<?php endif; ?>


    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                Товарные позиции
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <?php
                    // ---------- Start SAO ----------
                    //Предварительно получаем список возможных SAO-действий:
                    $sao_actions = array();

                    $sao_actions_query = $db_link->prepare("SELECT * FROM `shop_sao_actions`");
                    $sao_actions_query->execute();
                    while( $sao_action = $sao_actions_query->fetch() )
                    {
                        $sao_actions[$sao_action["id"]] = array();
                        $sao_actions[$sao_action["id"]]["name"] = $sao_action["name"];
                        $sao_actions[$sao_action["id"]]["script"] = $sao_action["script"];
                        $sao_actions[$sao_action["id"]]["fontawesome"] = $sao_action["fontawesome"];
                        $sao_actions[$sao_action["id"]]["btn_class"] = $sao_action["btn_class"];
                    }

                    //Подключаем протокол выполнения действий
                    $sao_propocol_mode = 1;//Режим работы протокола - страница заказа
                    require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/sao/actions_exec_propocol.php");
                    // ---------- End SAO ----------

                    $items_counter = 0;//Счетчик позиций

                    //ПОЛЯ ИТОГО ПО ЗАКАЗУ
                    $count_need_total = 0;//Итого количество
                    $price_sum_total = 0;//Итого сумма
                    $price_purchase_sum_total = 0;//Итого закуп
                    $profit_total = 0;//Итого маржа

                    //ПОЛУЧАЕМ ВСЕ ПОЗИЦИИ ЗАКАЗА

                    //Запрос закупа
                    $SELECT_price_purchase_sum = "IFNULL((SELECT SUM(`price_purchase`*(`count_reserved`+`count_issued`+`count_canceled`)) FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id`), CAST(`t2_price_purchase`*`count_need` AS DECIMAL(20,2)))";

                    //Сумма позиции
                    $SELECT_item_price_sum = "CAST(`price`*`count_need` AS DECIMAL(20,2))";

                    //Маржа позиции
                    $SELECT_item_profit = "CAST(`price`*`count_need` - $SELECT_price_purchase_sum AS DECIMAL(20,2))";


                    //SAO
                    $SELECT_item_sao_state = "IFNULL( (SELECT `name` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
                    $SELECT_item_sao_color_background = "IFNULL( (SELECT `color_background` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
                    $SELECT_item_sao_color_text = "IFNULL( (SELECT `color_text` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
                    //Получаем через запятую возможные дейстия для SAO для данного состояния и данного поставщика
                    $SELECT_item_sao_actions = " IFNULL(( SELECT GROUP_CONCAT(`id` SEPARATOR ',') FROM `shop_sao_actions` WHERE id IN (SELECT `action_id` FROM `shop_sao_states_types_actions_link` WHERE `state_type_id` = (SELECT `id` FROM `shop_sao_states_types_link` WHERE `state_id` = `shop_orders_items`.`sao_state` AND `interface_type_id` =  (SELECT `interface_type` FROM `shop_storages` WHERE `id` = `shop_orders_items`.`t2_storage_id` ) )) ), '')";



                    //СЛОЖНЫЙ ВЛОЖЕННЫЙ ЗАПРОС
                    $SELECT_ORDER_ITEMS = "SELECT SQL_CALC_FOUND_ROWS *, $SELECT_price_purchase_sum AS `price_purchase_sum`, $SELECT_item_price_sum AS `price_sum`, $SELECT_item_profit AS `profit`, $SELECT_item_sao_state AS `sao_state_name`, $SELECT_item_sao_color_background AS `sao_state_color_background`, $SELECT_item_sao_color_text AS `sao_state_color_text`, $SELECT_item_sao_actions AS `sao_actions`, (SELECT COUNT(`id`) FROM `shop_kkt_checks_products_to_orders_items_map` WHERE `order_item_id` = `shop_orders_items`.`id`) AS `checks_count` FROM `shop_orders_items` WHERE `order_id` = ? ";

                    /*$sql_log_file = fopen("sql_log_file.txt", "w");
                    fwrite($sql_log_file, $SELECT_ORDER_ITEMS);
                    fclose($sql_log_file);*/


                    $order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
                    $order_items_query->execute( array($order_id) );


                    $elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
                    $elements_count_rows_query->execute();
                    $elements_count_rows = $elements_count_rows_query->fetchColumn();


                    //Массивы для JS с id элементов и с чекбоксами элементов
                    $for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
                    $for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов

                    $for_js = $for_js."var orders_items_ids_to_orders_items_objects = new Array();";//Для связи ID позиций заказов с их объектами (требуется для пробивки чеков)

                    ?>
                    <table id="order_items_table" class="footable table table-hover toggle-arrow " data-sort="false" data-page-size="<?php echo $elements_count_rows; ?>">
                        <thead>
                        <th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>
                        <th data-toggle="true"></th>
												<th></th>
                        <th>ID</th>
                        <th>Производитель</th>
                        <th>Артикул</th>
                        <th>Наименование</th>
                        <th data-hide="phone">Цена</th>
                        <th data-hide="phone">Кол-во</th>
                        <th data-hide="phone">Сумма</th>
                        <th data-hide="phone,tablet">Закуп</th>
                        <th data-hide="phone,tablet">Маржа</th>
                        <th data-hide="phone,tablet">Статус</th>
                        <th data-hide="phone,tablet">Срок</th>
                        <th data-hide="phone,tablet">Чеки</th>
                        <th data-hide="phone,tablet,default">Данные склада</th>
                        <th></th>
                        </thead>
                        <tbody>
                        <?php
                        while( $order_item = $order_items_query->fetch() )
                        {
                            //Для Javascript
                            $for_js = $for_js."elements_array[elements_array.length] = \"checked_".$order_item["id"]."\";\n";//Добавляем элемент для JS
                            $for_js = $for_js."elements_id_array[elements_id_array.length] = ".$order_item["id"].";\n";//Добавляем элемент для JS


                            $for_js = $for_js."orders_items_ids_to_orders_items_objects[".$order_item["id"]."] = {\"product_name\":\"".$order_item["t2_manufacturer"].' '.$order_item["t2_article"].' '.$order_item["t2_name"]."\",\"name\":\"".$order_item["t2_name"]."\",\"article\":\"".$order_item["t2_article"]."\",\"manufacturer\":\"".$order_item["t2_manufacturer"]."\",\"price\":".$order_item["price"].",\"count_need\":".$order_item["count_need"]."};\n";//Добавляем элемент для JS


                            $item_id            = $order_item["id"];
                            $item_status        = $order_item["status"];
                            $item_count_need    = $order_item["count_need"];
                            $item_price         = $order_item["price"];
                            $item_price_sum     = $order_item["price_sum"];
                            $item_product_type  = $order_item["product_type"];
                            $item_product_id    = $order_item["product_id"];
                            $item_price_purchase_sum = $order_item["price_purchase_sum"];
                            $item_product_name  = $order_item["t2_name"];
                            $item_article 		= $order_item["t2_article"];
                            $item_manufacturer 	= $order_item["t2_manufacturer"];
                            $item_profit        = $order_item["profit"];
														$item_in_storage_data  = $order_item["in_storage_data"];

                            //SAO
                            $item_sao_state_name = $order_item["sao_state_name"];
                            $item_sao_state = $order_item["sao_state"];
                            $item_sao_state_color_background = $order_item["sao_state_color_background"];
                            $item_sao_state_color_text = $order_item["sao_state_color_text"];
                            $item_sao_actions = $order_item["sao_actions"];
                            $item_sao_message = $order_item["sao_message"];

                            $item_t2_time_to_exe = $order_item["t2_time_to_exe"];
                            $item_t2_time_to_exe_guaranteed = $order_item["t2_time_to_exe_guaranteed"];

                            //Срок доставки для продуктов типа 2
                            if($item_t2_time_to_exe < $item_t2_time_to_exe_guaranteed)
                            {
                                $item_t2_time_to_exe = $item_t2_time_to_exe." - ".$item_t2_time_to_exe_guaranteed;
                            }
                            $item_t2_time_to_exe = $item_t2_time_to_exe." дн.";

                            //Считаем поля ИТОГО ПО ЗАКАЗУ (если статус позиции позволяет)
                            if( array_search($item_status, $orders_items_statuses_not_count) === false)
                            {
                                $count_need_total += $item_count_need;
                                $price_sum_total += $item_price_sum;
                                $price_purchase_sum_total += $item_price_purchase_sum;
                                $profit_total += $item_profit;
                            }


                            //Чеки
                            $item_checks_count = $order_item["checks_count"];
                            if( $item_checks_count == 0 )
                            {
                                $item_checks_count = "Нет";
                            }
                            else
                            {
                                $item_checks_count = "<span onclick=\"show_order_item_checks(".$item_id.");\">".$item_checks_count." <i class=\"fas fa-search\"></i></span>";
                            }
                            ?>

                            <tr style="background-color:<?php echo $orders_items_statuses[$item_status]["color"]; ?>" id="order_item_record_<?php echo $item_id; ?>">

                                <td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $order_item["id"];?>');" id="checked_<?php echo $order_item["id"];?>" name="checked_<?php echo $order_item["id"];?>"/></td>
                                <td></td>
																<td>
																	<?php if($for_warehouse == '1') : ?>
																		
																		<?php

																			$title_in_storage_data = 'Оприходовать';
																			$class_in_storage_data = '';
																			$fa_in_storage_data = 'plus';

																			if($item_in_storage_data == '1') {

																				$title_in_storage_data = 'Оприходовано';
																				$class_in_storage_data = 'done';
																				$fa_in_storage_data = 'check';

																			}

																		?>

																		<a 
																			title="<?= $title_in_storage_data ;?>"
																			href="javascript:void();"
																			role="button"
																			class="btn btn-add-catalog <?= $class_in_storage_data ;?>"
																			data-item-id="<?php echo $item_id; ?>"
																			data-item-manufacturer="<?php echo $item_manufacturer; ?>"
																			data-item-article="<?php echo $item_article; ?>"
																			data-item-product-name="<?php echo $item_product_name; ?>"
																			data-item-count-need="<?php echo $item_count_need; ?>"
																			data-item-price="<?php echo $item_price; ?>"
																			onclick="showAddCatalogModal(this);"
																		>
																			<i class="fa fa-<?= $fa_in_storage_data ;?>"></i>
																		</a>
																		
																	<?php endif; ?>
																</td>
                                <td><?php echo $item_id; ?></td>
                                <td><?php echo $item_manufacturer; ?></td>
                                <td><?php echo $item_article; ?></td>
                                <td id="order_item_name_<?php echo $item_id; ?>"><?php echo $item_product_name; ?></td>
                                <td><?php echo number_format($item_price, 2, '.', ''); ?></td>
                                <td><?php echo $item_count_need; ?></td>
                                <td><?php echo number_format($item_price_sum, 2, '.', ''); ?></td>
                                <td><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></td>
                                <td><?php echo number_format($item_profit, 2, '.', ''); ?></td>
                                <td id="order_item_status_<?php echo $item_id; ?>"><?php echo $orders_items_statuses[$item_status]["name"]; ?></td>
                                <td><?php echo $item_t2_time_to_exe; ?></td>
                                <td><?php echo $item_checks_count; ?></td>
                                <td>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped table-bordered">
                                                <thead>
                                                <tr>
                                                    <th rowspan="2" style="vertical-align:middle;">Склад</th>
                                                    <th rowspan="2" style="vertical-align:middle;">ID поставки</th>
                                                    <th rowspan="2" style="vertical-align:middle;">Цена закупа</th>
                                                    <th rowspan="2" style="vertical-align:middle;">Количество</th>
                                                    <th rowspan="2" style="vertical-align:middle;">Сумма закупа</th>
                                                    <th colspan="3" style="text-align:center;">
                                                        SAO
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th style="text-align:center;">Состояние</th>
                                                    <th style="text-align:center;">Инфо</th>
                                                    <th style="text-align:center;">Действия</th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                <?php
                                                //Выводим данные по поставкам. Логика зависит от типа продукта
                                                if($item_product_type == 1)
                                                {
                                                    $details_query = $db_link->prepare("SELECT *, (`count_reserved`+`count_issued`+`count_canceled`)*`price_purchase` AS `price_purchase_sum`, `count_reserved`+`count_issued`+`count_canceled` AS `count_reserved_issued` FROM `shop_orders_items_details` WHERE `order_item_id` = ?;");
                                                    $details_query->execute( array($item_id) );
                                                    while( $detail = $details_query->fetch() )
                                                    {
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $storages_list[$detail["storage_id"]]; ?></td>
                                                            <td><?php echo $detail["storage_record_id"]; ?></td>
                                                            <td><?php echo number_format($detail["price_purchase"], 2, '.', ''); ?></td>
                                                            <td><?php echo $detail["count_reserved_issued"]; ?></td>
                                                            <td><?php echo number_format($detail["price_purchase_sum"], 2, '.', ''); ?></td>
                                                            <td colspan="3">В каталоге SAO не поддерживается</td>
                                                        </tr>
                                                        <?php
                                                    }
                                                }
                                                else if($item_product_type == 2)
                                                {
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $storages_list[$order_item["t2_storage_id"]]; ?></td>
                                                        <td><?php echo "-"; ?></td>
                                                        <td><?php echo number_format($order_item["t2_price_purchase"], 2, '.', ''); ?></td>
                                                        <td><?php echo $order_item["count_need"]; ?></td>
                                                        <td><?php echo number_format($order_item["t2_price_purchase"]*$order_item["count_need"], 2, '.', ''); ?></td>
                                                        <?php
                                                        if( $item_sao_state > 0 )
                                                        {
                                                            ?>
                                                            <td style="background-color:<?php echo $item_sao_state_color_background; ?>; color:<?php echo $item_sao_state_color_text; ?>;vertical-align:middle;">
                                                                <?php echo $item_sao_state_name; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                if($item_sao_message != "")
                                                                {
                                                                    echo $item_sao_message;
                                                                }
                                                                else
                                                                {
                                                                    echo "-";
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                if($item_sao_actions != "")
                                                                {
                                                                    $item_sao_actions = explode(",", $item_sao_actions);
                                                                    for($ac=0; $ac < count($item_sao_actions); $ac++)
                                                                    {
                                                                        ?>
                                                                        <button onclick="exec_action(<?php echo $item_id; ?>, <?php echo $item_sao_actions[$ac]; ?>);" class="btn <?php echo $sao_actions[$item_sao_actions[$ac]]["btn_class"]; ?> " type="button"><i class="fa <?php echo $sao_actions[$item_sao_actions[$ac]]["fontawesome"]; ?>"></i> <span class="bold"><?php echo $sao_actions[$item_sao_actions[$ac]]["name"]; ?></span></button>
                                                                        <?php
                                                                    }
                                                                }
                                                                else
                                                                {
                                                                    ?>
                                                                    Доступных действий нет
                                                                    <?php
                                                                }
                                                                ?>
                                                            </td>
                                                            <?php
                                                        }
                                                        else
                                                        {
                                                            ?>
                                                            <td colspan="3">Поставщик не поддерживает SAO</td>
                                                            <?php
                                                        }
                                                        ?>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                                </tbody>
                                                <tfoot>
                                                <tr>
                                                    <td colspan="2"></td>
                                                    <td><strong>Итого</strong></td>
                                                    <td><strong><?php echo $item_count_need; ?></strong></td>
                                                    <td><strong><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></strong></td>
                                                    <td colspan="3"></td>
                                                </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <a class="btn btn-sm btn-info" onClick="btn_modal_clicked(<?php echo $order_item["id"];?>, <?php echo number_format($paid_sum, 2, '.', ''); ?>, <?php echo number_format($item_price, 2, '.', ''); ?>, <?php echo $item_count_need; ?>, <?php echo $item_status; ?>)"><i class="fa fa-cut"></i></a>

                                    <a class="btn btn-sm btn-info" <?=((int)$paid !== 0)?'onClick="alert(\'Полностью или частично оплаченный заказ редактировать нельзя. Сначала верните сумму на баланс клиента.\')"':'href="/'.$DP_Config->backend_dir.'/shop/orders/items/edit?id='.$item_id.'"';?> ><i class="fas fa-pencil-alt"></i></a>
                                </td>
                            </tr>
                            <?php
                            $items_counter++;
                        }//while - по позициям заказа
                        ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>Итого</strong></td>
                            <td><strong><?php echo $count_need_total; ?></strong></td>
                            <td><strong><?php echo $price_sum_total; ?></strong></td>
                            <td><strong><?php echo $price_purchase_sum_total; ?></strong></td>
                            <td><strong><?php echo $profit_total; ?></strong></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>


            </div>
            <div class="panel-footer">
                <div class="row float-e-margins">
                    <div class="col-lg-8">
                        <button class="btn btn-info " type="button" onclick="create_check_for_orders_items();"><i class="fas fa-receipt"></i> Оформить кассовый чек</button>

                        <a class="btn btn-success " type="button" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items/add?id=<?php echo $order_id; ?>"><i class="fa fa-plus"></i> <span class="bold">Добавить позицию</span></a>
                        <form action="/../../../<?= $DP_Config->backend_dir ?>/content/label/print_labels_order.php" method="post" target="_blank" id="print_labels_form">
                            <input type="hidden" name="items_data" id="items_data_input">
                            <button type="submit" class="btn btn-warning"><i class="fa fa-print"></i> Напечатать этикетки</button>
                        </form>
                        <br/>

                        <a class="btn btn-success" onClick="doc_print('sales_receipt');"><i class="fa fa-print"></i> <span class="bold">Товарный чек</span></a>
                        <a class="btn btn-success" onClick="doc_print('invoice_for_payment');"><i class="fa fa-print"></i> <span class="bold">Счет на оплату</span></a>
                        <a class="btn btn-success" onClick="doc_print('torg_12');"><i class="fa fa-print"></i> <span class="bold">Накладная ТОРГ-12</span></a>
                        <a class="btn btn-success" onClick="doc_print('upd');"><i class="fa fa-print"></i> <span class="bold">УПД</span></a>
                        <a class="btn btn-success" onClick="doc_print('upd_2021');"><i class="fa fa-print"></i> <span class="bold">УПД (c 01.07.2021)</span></a>

                    </div>
                    <script>
                        function doc_print(doc_name){
                            let order_items = getCheckedElements();//Список отмеченных позиций
                            window.open('/content/shop/print_docs/service/print.php?doc_name='+doc_name+'&order_id=<?php echo $order_id; ?>&order_items='+JSON.stringify(order_items)+'&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>', '_blank');
                        }
                    </script>



                    <div class="col-lg-4">
                        <div><label>Статус для выбранных позиций:</label></div>
                        <div class="input-group">
                            <select id="setOrderItemsStatusSelect" class="form-control">
                                <?php
                                foreach($orders_items_statuses as $status_id=>$status_data)
                                {
                                    ?>
                                    <option value="<?php echo $status_id; ?>"><?php echo $status_data["name"]; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <span class="input-group-btn">
								<button onclick="setOrderItemsStatus();" class="btn btn-success " type="button"><i class="fa fa-check"></i> <span class="bold">Присвоить статус</span></button>
							</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>
        jQuery( window ).load(function() {
            $('#order_items_table').footable();
        });
    </script>

    <script>
        // Добавляем событие на выбор всех позиций
        document.getElementById("check_uncheck_all").addEventListener("change", function () {
            const isChecked = this.checked;
            document.querySelectorAll("input[type=checkbox][name^='checked_']").forEach((checkbox) => {
                checkbox.checked = isChecked; // Отмечаем/снимаем все чекбоксы
            });
        });

        // Добавляем событие на отправку формы
        document.getElementById("print_labels_form").addEventListener("submit", function (event) {
            event.preventDefault(); // Останавливаем стандартную отправку формы
            let selectedItems = [];

            // Собираем данные всех отмеченных чекбоксов товаров
            document.querySelectorAll("input[type=checkbox][name^='checked_']:checked").forEach((checkbox) => {
                const row = checkbox.closest("tr");
                const itemData = {
                    id: row.querySelector('td:nth-child(4)').innerText.trim(),
                    manufacturer: row.querySelector('td:nth-child(5)').innerText.trim(),
                    article: row.querySelector('td:nth-child(6)').innerText.trim(),
                    name: row.querySelector('td:nth-child(7)').innerText.trim(),
                    price: row.querySelector('td:nth-child(8)').innerText.trim(),
                    count: parseInt(row.querySelector('td:nth-child(9)').innerText.trim()),
                    order_id: "<?php echo $order_id; ?>", // ID заказа
                    office_id: "<?php echo $office_id; ?>" // ID магазина
                };
                // Добавляем товар столько раз, сколько его количество
                for (let i = 0; i < itemData.count; i++) {
                    selectedItems.push(itemData);
                }
            });

            if (selectedItems.length === 0) {
                alert("Выберите хотя бы один товар для печати.");
                return;
            }

            // Добавляем данные в скрытое поле
            document.getElementById("items_data_input").value = JSON.stringify(selectedItems);

            // Отправляем форму, открывая новую вкладку
            this.submit();
        });
    </script>
    <script>
        //Выставить статус для позиций заказа
        function setOrderItemsStatus()
        {
            var orders_items = getCheckedElements();//Список отмеченных заказов
            if(orders_items.length == 0)
            {
                alert("Выберите товарные позиции из списка");
                return;
            }

            var needStatus = document.getElementById("setOrderItemsStatusSelect").value;

            jQuery.ajax({
                type: "GET",
                async: false, //Запрос синхронный
                url: "/content/shop/protocol/set_order_item_status.php",
                dataType: "json",//Тип возвращаемого значения
                data: "initiator=1&orders_items="+JSON.stringify(orders_items)+"&status="+needStatus+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer)
                {
                    console.log(answer);
                    if(answer.status == true)
                    {
                        //Обновляем страницу
                        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>&success_message='+encodeURI('Выполнено');
                    }
                    else
                    {
                        if( typeof answer.message != undefined )
                        {
                            alert(answer.message);
                        }
                        else
                        {
                            alert("Ошибка изменения статуса позиций");
                        }
                    }
                }
            });
        }
    </script>



    <script>
        // ------------------------------------------------------------------------------------------------------
        //Скрыть / Открыть  информацию по поставкам
        function show_hide_storage_info(item_id)
        {
            var block = document.getElementById("storage_info_"+item_id);
            if(block == undefined)
            {
                return;
            }

            var a = document.getElementById("storage_info_button_"+item_id);
            var state = block.getAttribute("state");
            if(state == "hidden")
            {
                block.setAttribute("state", "shown");
                $("#storage_info_"+item_id).show("fast");
                a.innerHTML = "Скрыть данные склада";
            }
            else
            {
                block.setAttribute("state", "hidden");
                $("#storage_info_"+item_id).hide(150);
                a.innerHTML = "Открыть данные склада";
            }
        }
        // ------------------------------------------------------------------------------------------------------
    </script>








    <!-- Переписка с покупателем -->
    <div class="col-lg-6">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
                Переписка с покупателем
            </div>
            <div class="panel-body">
                <div class="chat_block" id="chat_block">
                </div>
            </div>
            <div class="panel-footer">
                <div class="row">
                    <div class="col-lg-12">
                        <table style="width:100%;">
                            <tr>
                                <td>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="update_msg"/>
                                        <input type="hidden" name="flag" value="0"/>
                                        <input type="hidden" name="order_id" value="<?=(int)$order_id;?>"/>
                                        <input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>"/>
                                        <button class="form-control" type="submit" title="Сделать последнее сообщение непрочитанным"><i class="fa fa-envelope" aria-hidden="true"></i></button>
                                    </form>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" id="new_message_area" class="form-control" />
                                        <span class="input-group-btn">
											<button onclick="sendMessage();" class="btn btn-success " type="button"><i class="fa fa-pencil"></i> <span class="bold">Оставить сообщение</span></button>
										</span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // --------------------------------------------------------------------------
        //Получить сообщения по заказу
        function getOrderMessages()
        {
            jQuery.ajax({
                type: "GET",
                async: true,
                url: "/content/shop/messager/ajax_get_order_messages.php",
                dataType: "json",//Тип возвращаемого значения
                data: "manager=1&order_id=<?php echo $order_id; ?>"+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer)
                {
                    var html = "";
                    for(var i=0; i < answer.length; i++)
                    {
                        var class_str = "bubble";
                        var sender = "Покупатель";
                        if(answer[i].is_customer == false)
                        {
                            class_str += "2";
                            sender = "Продавец";
                        }
                        html += "<div class=\""+class_str+"\">"+sender+" "+answer[i].time+"<br>"+answer[i].text+"</div>";
                    }
                    if(html == "") html = "<div align=\"center\">Сообщений в данном заказе нет</div>";
                    document.getElementById("chat_block").innerHTML = html;

                    document.getElementById("chat_block").scrollTop = document.getElementById("chat_block").scrollHeight;
                }
            });
        }
        // --------------------------------------------------------------------------
        //Отправить сообщение
        function sendMessage()
        {
            var text = document.getElementById("new_message_area").value;
            if(text == "")
            {
                alert("Поле для сообщение пустое");
                return;
            }

            jQuery.ajax({
                type: "GET",
                async: true,
                url: "/content/shop/messager/ajax_send_message.php",
                dataType: "json",//Тип возвращаемого значения
                data: "manager=1&order_id=<?php echo $order_id; ?>&text="+encodeURIComponent(text)+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer)
                {
                    if(answer == true)
                    {
                        document.getElementById("new_message_area").value = "";
                        getOrderMessages();
                    }
                    else
                    {
                        alert("Ошибка отправки сообщения");
                    }
                }
            });
        }
        // --------------------------------------------------------------------------
        getOrderMessages();//Запрашиваем переписку по заказу

        setInterval(function(){
            getOrderMessages();
        }, 300000);
    </script>






    <div class="col-lg-6">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
                История заказа
            </div>
            <div class="panel-body">
                <div id="order_log" style="height:150px;border:1px solid #EEE;border-radius:7px;padding:7px;overflow-y:scroll;">

                    <?php
                    $log_records = "";
                    $log_query = $db_link->prepare("SELECT * FROM `shop_orders_logs` WHERE `order_id` = ? ORDER BY `id`;");
                    $log_query->execute( array($order_id) );
                    while($log = $log_query->fetch() )
                    {
                        //Имя инициатора действия:
                        if($log["is_robot"])
                        {
                            $initiator_name = "Робот";
                        }
                        else if($log["is_manager"])
                        {
                            $initiator_profile = DP_User::getUserProfileById($log["user_id"]);
                            $initiator_name = "Менеджер ".$initiator_profile["surname"];
                        }
                        else
                        {
                            if($log["user_id"] == 0)
                            {
                                $initiator_name = "Покупатель (не авторизован)";
                            }
                            else
                            {
                                $initiator_profile = DP_User::getUserProfileById($log["user_id"]);
                                $initiator_name = "Покупатель ".$initiator_profile["surname"];
                            }
                        }
                        $log_records .= date("Y-m-d H:i:s", $log["time"])." [$initiator_name] ".$log["text"]."<br>";
                    }
                    echo $log_records;
                    ?>
                </div>
            </div>
            <div class="panel-footer">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="input-group">
                            <input id="new_message_area_log" class="form-control" />
                            <span class="input-group-btn">
								<button onclick="addCommentToLog();" type="button" class="btn btn-success">
									<i class="fa fa-pencil"></i>
									<span class="bold">Комментарий</span>
								</button>
							</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById("order_log").scrollTop = document.getElementById("order_log").scrollHeight;
        // -----------------------------------------------------------------------
        //Добавление комментария в лог
        function addCommentToLog()
        {
            var text = document.getElementById("new_message_area_log").value;
            if(text == "")
            {
                alert("Поле пустое");
                return;
            }

            jQuery.ajax({
                type: "GET",
                async: true,
                url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_add_comment_to_log.php",
                dataType: "json",//Тип возвращаемого значения
                data: "order_id=<?php echo $order_id; ?>&text="+text+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                success: function(answer)
                {
                    if(answer.status == true)
                    {
                        location = "/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>";
                    }
                    else
                    {
                        alert("Ошибка добавление коментария");
                        console.log(answer);
                    }
                }
            });
        }
        // -----------------------------------------------------------------------
    </script>






    <script>
        // ----------------------------------------------------------------------------------------
        <?php
        echo $for_js;//Выводим массив с чекбоксами для элементов
        ?>
        //Обработка переключения Выделить все/Снять все
        function on_check_uncheck_all()
        {
            var state = document.getElementById("check_uncheck_all").checked;

            for(var i=0; i<elements_array.length;i++)
            {
                document.getElementById(elements_array[i]).checked = state;
            }
        }//~function on_check_uncheck_all()
        // ----------------------------------------------------------------------------------------
        //Обработка переключения одного чекбокса
        function on_one_check_changed(id)
        {
            //Если хотя бы один чекбокс снят - снимаем общий чекбокс
            for(var i=0; i<elements_array.length;i++)
            {
                if(document.getElementById(elements_array[i]).checked == false)
                {
                    document.getElementById("check_uncheck_all").checked = false;
                    break;
                }
            }
        }//~function on_one_check_changed(id)
        // ----------------------------------------------------------------------------------------
        //Получение массива id отмеченых элементов
        function getCheckedElements()
        {
            var checked_ids = new Array();
            //По массиву чекбоксов
            for(var i=0; i<elements_array.length;i++)
            {
                if(document.getElementById(elements_array[i]).checked == true)
                {
                    checked_ids.push(elements_id_array[i]);
                }
            }

            return checked_ids;
        }
        // ----------------------------------------------------------------------------------------

        // Функция копирования в буфер обмена
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Показать алерт об успешном копировании
                showCopyAlert();
            }).catch(function(error) {
                console.error("Не удалось скопировать текст: ", error);
            });
        }

        // Функция для показа Bootstrap алерта
        function showCopyAlert() {
            var alertBox = document.getElementById('copy-alert');
            alertBox.style.display = 'block';

            // Скрытие алерта автоматически через 3 секунды
            setTimeout(function() {
                alertBox.style.display = 'none';
            }, 3000);
        }

        // Функция для закрытия алерта вручную
        function closeAlert() {
            document.getElementById('copy-alert').style.display = 'none';
        }
    </script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
        function openScanner() {
            // Показываем модальное окно
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('scanner-container').style.display = 'block';

            // Инициализация html5-qrcode
            html5QrCode = new Html5Qrcode("reader");

            // Опции
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                videoConstraints: {
                    width: 300,
                    height: 300,
                    facingMode: "environment",
                    focusMode: "continuous"
                },
            };

            // Запуск сканера
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    console.log(`Сканированный текст: ${decodedText}`);
                    document.getElementById('search').value = decodedText;
                    closeScanner();
                },
                (errorMessage) => {
                    console.warn(`Ошибка сканирования: ${errorMessage}`);
                }
            ).catch((err) => {
                console.error(`Ошибка запуска камеры: ${err}`);
            });
        }

        function closeScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    document.getElementById('overlay').style.display = 'none';
                    document.getElementById('scanner-container').style.display = 'none';
                }).catch((err) => {
                    console.error(`Ошибка остановки камеры: ${err}`);
                });
            } else {
                document.getElementById('overlay').style.display = 'none';
                document.getElementById('scanner-container').style.display = 'none';
            }
        }


    </script>

    <?php
    //Загружаем модальное окно разбиения позиции
    require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_items_reload_modal.php");
}
?>