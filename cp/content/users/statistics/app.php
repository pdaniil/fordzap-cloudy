<?php
defined('_ASTEXE_') or die('No access');

function getBlockStyle($arr) {
    $arrSizes = [
        'style="height: 100px;"',
        'style="height: 200px;"',
        'style="height: 300px;"',
        'style="height: 600px;"',
    ];
	
	if( ! isset($arr["count"]) ){
		$arr["count"] = null;
	}
	
    $arr["count"] = is_null($arr["count"]) ? $arr['count_out'] + $arr['count_in'] : $arr['count'];

    if ($arr["count"] == 0)
        $arr["style"] = $arrSizes[0];
    else if ($arr['count'] >= 50)
        $arr['style'] = $arrSizes[3];
    else if ($arr['count'] >= 5)
        $arr['style'] = $arrSizes[2];
    else
        $arr['style'] = $arrSizes[1];

    return $arr;
}

$main_url = $DP_Config->domain_path.$DP_Config->backend_dir;

$INCOME_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = ".(int)$_GET["user_id"]." AND `income`=1 AND `active` = 1), 0)";
$ISSUE_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = ".(int)$_GET["user_id"]." AND `income`=0 AND `active` = 1),0)";

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

$orders = getBlockStyle(DP_User::getUserOrdersById($_GET['user_id']));
$cart = getBlockStyle(DP_User::getUserCartsById($_GET['user_id']));
$cars = getBlockStyle(DP_User::getUserCarsById($_GET['user_id']));
$operations = getBlockStyle(DP_User::getUserFinanceById($_GET['user_id']));
$queries = getBlockStyle(DP_User::getUserQueriesById($_GET['user_id']));
$returns = getBlockStyle(DP_User::getUserReturnsById($_GET['user_id']));
$items = getBlockStyle(DP_User::getUserOrdersItemsById($_GET['user_id']));
$messages = getBlockStyle(DP_User::getUserMessagesById($_GET['user_id']));

?>
    <link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/content/users/statistics/assets/main.css">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                Действия
            </div>
            <div class="panel-body">
				<?php
				print_backend_button(array('background_color'=>'#b9babb', 'fontawesome_class'=>'fas fa-chevron-left', 'caption'=>'Назад', 'url'=>$DP_Config->domain_path.$DP_Config->backend_dir.'/users/usermanager/user?user_id='.$_GET['user_id']));
				?>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                Статистика пользователя
            </div>
            <div class="panel-body">
                <div class="cards-wrapper">
                    <div class="stat-card">
                            <div class="stat-card-header">
                                Поисковые запросы
                            </div>
                            <div class="stat-card-body" <?php echo $queries['style']; ?>>
                                <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
                                    <thead>
                                    <tr>
                                        <th>Производитель</th><th>Артикул</th><th>Наименование</th><th>Дата, Время</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($queries['data'] as $query) {
                                        ?>
                                        <tr class="tr-click" onclick="window.open('<?php echo $DP_Config->domain_path; ?>parts/<?php echo ($query['manufacturer'] == '') ? 'brands' : $query['manufacturer']; ?>/<?php echo $query["article"]; ?>')">
                                            <td><?php echo $query['manufacturer']; ?></td>
                                            <td><?php echo $query['article']; ?></td>
                                            <td><?php echo $query['name']; ?></td>
                                            <td><?php echo $query['time']; ?></td>
                                        </tr>
                                        <?
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="stat-card-footer">
                                <span>Всего запросов <?php echo $queries['count']; ?>.</span>
                                <button onclick="locationToStatisticsByUserId('<?php echo $main_url; ?>/shop/statistika', '<?php echo $_GET['user_id']?>', '<?php echo time() - (90 * 86400); ?>', '<?php echo time(); ?>')">Все запросы</button>
                            </div>
                        </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            Заказы
                        </div>
                        <div class="stat-card-body" <?php echo $orders['style']; ?>>
                            <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
                                <thead>
                                    <tr>
                                        <th>ID заказа</th><th>Статус</th><th>Дата</th><th>Сумма</th><th>Долг</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        foreach ($orders['data'] as $order) {
                                            ?>
                                                <tr class="tr-click" onclick="window.open('<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order["id"]; ?>', '_blank')">
                                                    <td><?php echo $order['id']; ?></td>
                                                    <td><?php echo $order['status']; ?></td>
                                                    <td><?php echo $order['date']; ?></td>
                                                    <td><?php echo $order['sum']; ?></td>
                                                    <td><?php echo $order['debt']; ?></td>
                                                </tr>
                                            <?
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="stat-card-footer">
                            <span>
                                Всего заказов <?php echo $orders['count']; ?> на сумму <?php echo $orders['total']; ?>. <br>
                                Общая задолженность <?php echo $orders['total_debt']; ?>.
                            </span>
                            <button onclick="locationToOrdersByUserId('<?php echo $main_url; ?>/shop/orders/orders', '<?php echo $_GET['user_id']; ?>')">Все заказы</button>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            Позиции заказов
                        </div>
                        <div class="stat-card-body" <?php echo $items['style']; ?>>
                            <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th><th>Производитель</th><th>Артикул</th><th>ID Заказа</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($items['data'] as $item) {
                                    $link = $DP_Config->domain_path.$DP_Config->backend_dir . "/shop/orders/order?order_id=" . $item['order_id'];
                                    ?>
                                    <tr class="tr-click" onclick="window.open('<?php echo $link; ?>', '_blank')">
                                        <td><?php echo $item['id']; ?></td>
                                        <td><?php echo $item['manufacturer']; ?></td>
                                        <td><?php echo $item['article']; ?></td>
                                        <td><?php echo $item['order_id']; ?></td>
                                    </tr>
                                    <?
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="stat-card-footer">
                            <span>Всего позиций <?php echo $items['count']; ?>.</span>
                            <button onclick="locationToItemsByUserId('<?php echo $main_url; ?>/shop/orders/items', '<?php echo $_GET['user_id']?>')">Все позиции</button>
                        </div>
                    </div>
                    <div class="stat-card">
                            <div class="stat-card-header">
                                Финансовые операции
                            </div>
                            <div class="stat-card-body" <?php echo $operations['style']; ?>>
                                <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
                                    <thead>
                                    <tr>
                                        <th>ID операции</th><th>Дата, Время</th><th>Тип</th><th>Сумма</th><th>Заказ</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($operations['data'] as $operation) {
                                        ?>
                                        <tr>
                                            <td><?php echo $operation['id']; ?></td>
                                            <td><?php echo $operation['time']; ?></td>
                                            <td><?php echo $operation['type']; ?></td>
                                            <td><?php echo $operation['amount']; ?></td>
                                            <td><?php echo $operation['order_id']; ?></td>
                                        </tr>
                                        <?
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="stat-card-footer">
                            <span>
                                Всего финансовых операций <?php echo $operations['count_in'] + $operations['count_out']; ?>: <br>
                                - приходных операций <?php echo $operations['count_in']; ?> на сумму <?php echo $operations['total_in']; ?>; <br>
                                - расходных операций <?php echo $operations['count_out']; ?> на сумму <?php echo $operations['total_out']; ?>; <br>
                                - баланс: <?php echo  $balance; ?>.
                            </span>
                                <button onclick="locationToFinanceByUserId('<?php echo $main_url; ?>/shop/finance/account_operations', '<?php echo $_GET['user_id']; ?>')">Все операции</button>
                            </div>
                        </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            Корзина
                        </div>
                        <div class="stat-card-body" <?php echo $cart['style']; ?>>
                            <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>Наименование</th><th>Количество</th><th>Цена</th><th>Склад</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php

                                    foreach ($cart['data'] as $item) {
                                        ?>
                                        <tr>
                                            <td><?php echo $item['name']; ?></td>
                                            <td><?php echo $item['count_need']; ?></td>
                                            <td><?php echo $item['price']; ?></td>
                                            <td><?php echo $item['storage']; ?></td>
                                        </tr>
                                        <?
                                    }
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="stat-card-footer">
                            <span>Всего позиций в корзине <?php echo $cart['count']; ?> на сумму <?php echo $cart['total']; ?>.</span>
                            <button onclick="locationToCartsByUserId('<?php echo $main_url; ?>/shop/orders/carts', '<?php echo $_GET['user_id']; ?>')">Все позиции</button>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            Сообщения клиента
                        </div>
                        <div class="stat-card-body" <?php echo $messages['style']; ?>>
                            <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>Текст</th><th>ID Заказа</th><th>ID Возврата</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                    foreach ($messages['data'] as $message) {
                                        $link = '';
                                        if ($message['order_id'] > 0)
                                            $link = $DP_Config->domain_path.$DP_Config->backend_dir . "/shop/orders/order?order_id=" . $message['order_id'];
                                        else
                                            $link = $DP_Config->domain_path.$DP_Config->backend_dir . "/shop/returns-manager?return_id=" . $message['return_id'];
                                        ?>
                                        <tr class="tr-click" onclick="window.open('<?php echo $link; ?>','_blank')">
                                            <td><?php echo $message['text']; ?></td>
                                            <td>
                                                <?php if ($message['order_id'] > 0) : ?>
                                                   <?php echo $message['order_id']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($message['return_id'] > 0) : ?>
                                                    <?php echo $message['return_id']; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?
                                    }
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="stat-card-footer">
                            <span>Всего сообщений <?php echo $messages['count']; ?>.</span>
                        </div>
                    </div>
                    <div class="stat-card" style="cursor: default;">
                        <div class="stat-card-header">
                            Гараж
                        </div>
                        <div class="stat-card-body" <?php echo $cars['style']; ?>>
                            <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>VIN / FRAME</th><th>Марка</th><th>Модель</th><th>Объём двигателя (л)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                    if ($cars['count'] > 0)
                                    {
                                        foreach ($cars['data'] as $car) {
                                            ?>
                                            <tr>
                                                <td><?php echo $car['vin'].' / '.$car['frame']; ?></td>
                                                <td><?php echo $car['mark']; ?></td>
                                                <td><?php echo $car['model']; ?></td>
                                                <td><?php echo $car['engine_value']; ?></td>
                                            </tr>
                                            <?
                                        }
                                    }
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="stat-card-footer">
                            Всего автомобилей <?php echo $cars['count']; ?>.
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            Возвраты
                        </div>
                        <div class="stat-card-body" <?php echo $returns['style']; ?>>
                            <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <td>Номер заявки</td><th>Сумма</th><th>Завершён</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($returns['data'] as $return) {
                                    ?>
                                    <tr class="tr-click"  onclick="window.open('<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/returns-manager?return_id=<?php echo $return["id"]; ?>', '_blank')">
                                        <td><?php echo $return['id']; ?></td>
                                        <td><?php echo $return['return_sum']; ?></td>
                                        <td><?php echo $return['return_complete'] == '1' ? 'Да' : 'Нет'; ?></td>
                                    </tr>
                                    <?
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="stat-card-footer">
                            <span>Всего заявок <?php echo $returns['count']; ?> на сумму <?php echo $returns['total']; ?>.</span>
                            <button onclick="locationToReturnsByUserId('<?php echo $main_url; ?>/shop/returns-manager', '<?php echo $_GET['user_id']?>')">Все заявки</button>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    <script src="/<?php echo $DP_Config->backend_dir; ?>/content/users/statistics/assets/main.js"></script>
<?php
?>