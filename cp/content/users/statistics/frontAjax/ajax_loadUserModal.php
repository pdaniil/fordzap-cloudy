<?php
    header('Content-Type: application/json;charset=utf-8;');
    //Конфигурация CMS
    require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
    $DP_Config = new DP_Config;

    //Подключение к БД
    try
    {
        $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    }
    catch (PDOException $e)
    {
        $answer = array();
        $answer['status'] = false;
        $answer['message'] = "No DB connect";
        exit( json_encode($answer) );
    }
    $db_link->query("SET NAMES utf8;");

    // -------------------------------------------------------------------------------
    //Защита от CSRF-атак
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
    // -------------------------------------------------------------------------------

    //Для работы с пользователями
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

    $customer_id = $_POST['customer_id'];

    $INCOME_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = ".(int)$customer_id." AND `income`=1 AND `active` = 1), 0)";
    $ISSUE_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = ".(int)$customer_id." AND `income`=0 AND `active` = 1),0)";

    $SQL = "SELECT ($INCOME_SQL-$ISSUE_SQL) AS `balance`;";

    $query = $db_link->prepare($SQL);
    $query->execute();
    $row = $query->fetch();

    $balance = $row["balance"];
    if($balance == "")
    {
        $balance = 0;
    }

    $balance = number_format($balance, 2, '.', ' ');
    $main_url = $DP_Config->domain_path.$DP_Config->backend_dir;
    $profile = DP_User::getUserProfileById($customer_id);
    $select_group = $db_link->prepare("SELECT `value` FROM `groups` WHERE `id` = ?;");
    $select_group->execute([$profile['groups'][0]]);
    $select_group_result = $select_group->fetch();
    ob_start();

?>
    <div class="customer-modal-info-block">
        <div class="customer-modal-info-block-header">
            <div>
                Карточка пользователя
            </div>
            <i class="far fa-window-close" id="close-customer-modal-info-<?=$customer_id;?>"></i>

        </div>
        <div class="customer-modal-info-block-main-content">
            <div class="customer-modal-info-block-left-side">
				<a class="btn btn-xs btn-primary" target="_blank" href="<?=$main_url;?>/users/usermanager/user?user_id=<?=$customer_id;?>"><i class="fa fa-user"></i> Профиль</a>
			</div>
            <div class="customer-modal-info-block-left-side">
                <h2 style="font-size: 16px;">Основная информация</h2>
                <div class="info-str chet">
                    <div class="info-title">ID:</div>
                    <div class="info-value"><?=$customer_id;?></div>
                </div>
                <div class="info-str">
                    <div class="info-title">Группа:</div>
                    <div class="info-value"><?=$select_group_result['value'];?></div>
                </div>
                <?php if ( isset($profile['mail_field']) && $profile['mail_field'] != '' ) : ?>
                    <div class="info-str chet">
                        <div class="info-title">Основное поле регистрации:</div>
                        <div class="info-value"><?=$profile['mail_field'];?></div>
                    </div>
                <?php endif; ?>
                <?php if ($profile['email'] != '') : ?>
                    <div class="info-str chet">
                        <div class="info-title">Email:</div>
                        <div class="info-value"><?=$profile['email'];?></div>
                    </div>
                <?php endif; ?>
                <?php if ($profile['phone'] != '') : ?>
                    <div class="info-str">
                        <div class="info-title">Телефон:</div>
                        <div class="info-value"><?=$profile['phone'];?></div>
                    </div>
                <?php endif; ?>
                <?php
                $select_user_profile = $db_link->prepare('SELECT `data_value`, (SELECT `caption` FROM `reg_fields` WHERE `users_profiles`.`data_key` = `name`) as `caption` FROM `users_profiles` WHERE `user_id` = ?;');
                $select_user_profile->execute([$customer_id]);
                $counter = 0;
                while ($select_user_profile_result = $select_user_profile->fetch())
				{
                    $counter++;
                    ?>
                    <div class="info-str <?=(($counter % 2) == 0) ? 'chet' : '';?>">
                        <div class="info-title"><?=$select_user_profile_result['caption'];?>:</div>
                        <div class="info-value"><?=$select_user_profile_result['data_value'];?></div>
                    </div>
                    <?php
                }

                $orders = DP_User::getUserOrdersById($customer_id);
                $cart = DP_User::getUserCartsById($customer_id);
                $cars = DP_User::getUserCarsById($customer_id);
                $operations = DP_User::getUserFinanceById($customer_id);
                $queries = DP_User::getUserQueriesById($customer_id);
                $returns = DP_User::getUserReturnsById($customer_id);
                ?>
            </div>
            <div class="customer-modal-info-block-right-side">
                <div class="customer-modal-info-block-left-side">
                    <h2 style="font-size: 16px;">Общая статистика</h2>
                    <div class="info-str chet">
                        <div class="info-title">Заказы:</div>
                        <div class="info-value">
                            <a href="javascript:void()" onclick="locationToOrdersByUserId('<?=$main_url;?>/shop/orders/orders', '<?=$customer_id;?>')">
                                Всего заказов</a> <?=$orders['count'];?> на сумму <?=$orders['total'];?>. Долг: <?=$orders['total_debt'];?>.
                        </div>
                    </div>
                    <div class="info-str">
                        <div class="info-title">Корзина:</div>
                        <div class="info-value"><a href="javascript:void()" onclick="locationToCartsByUserId('<?=$main_url;?>/shop/orders/carts', '<?=$customer_id;?>')">Всего позиций в корзине</a> <?=$cart['count'];?> на сумму <?=$cart['total'];?>.</div>
                    </div>

                    <div class="info-str chet">
                        <div class="info-title">Запросы:</div>
                        <div class="info-value"><a href="javascript:void()" onclick="locationToStatisticsByUserId('<?=$main_url;?>/shop/statistika', '<?=$customer_id;?>', '<?=time() - (90 * 86400);?>', '<?=time();?>')">Всего запросов</a> <?=$queries['count'];?>.</div>
                    </div>
                    <div class="info-str ">
                        <div class="info-title">Операции:</div>
                        <div class="info-value">
                            <a href="javascript:void()" onclick="locationToFinanceByUserId('<?=$main_url;?>/shop/finance/account_operations', '<?=$customer_id;?>')">Всего финансовых операций</a> <?=$operations['count_in'] + $operations['count_out'];?>: <br>
                            - приходных операций <?=$operations['count_in'];?> на сумму <?=$operations['total_in'];?>; <br>
                            - расходных операций <?=$operations['count_out'];?> на сумму <?=$operations['total_out'];?>; <br>
                            - баланс <?=$balance;?>.
                        </div>
                    </div>

                    <div class="info-str chet">
                        <div class="info-title">Гараж:</div>
                        <div class="info-value">Всего автомобилей <?=$cars['count'];?>.</div>
                    </div>

                    <div class="info-str">
                        <div class="info-title">Возвраты:</div>
                        <div class="info-value"><a href="javascript:void()" onclick="locationToReturnsByUserId('<?=$main_url;?>/shop/returns-manager', '<?=$customer_id;?>')">Всего заявок</a> <?=$returns['count'];?> на сумму <?=$returns['total'];?>.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
    $modal = ob_get_clean();
    exit(json_encode(['modal' => $modal, 'status' => true]));
?>