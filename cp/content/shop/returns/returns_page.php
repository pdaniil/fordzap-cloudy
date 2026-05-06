<?php
defined('_ASTEXE_') or die('No access');

function pagination($all, $lim, $prev, $curr_link, $curr_css, $link)
{

    // print_r($all);
    // echo "</br>";
    // print_r($lim);
    // echo "</br>";
    // print_r($prev);
    // echo "</br>";
    // print_r($curr_link);
    // echo "</br>";

    $html = '';
    // осуществляем проверку, чтобы выводимые первая и последняя страницы
    // не вышли за границы нумерации
    $first = $curr_link - $prev;
    if ($first < 1) $first = 0;
    $last = $curr_link + $prev;

    $count_pages = (int)($all / $lim);//Количество страниц
    if($all%$lim)//Если остались еще элементы
    {
        $count_pages++;
    }

    $count_pages = $count_pages - 1;

    if ($last > $count_pages) $last = $count_pages;

    // начало вывода нумерации
    // выводим первую страницу
    $y = 0;
    if ($first > 0) $html .= "<li class='paginate_button'><a onclick='goToPage({$y})'>0</a></li>";
    // Если текущая страница далеко от 1-й (>10), то часть предыдущих страниц
    // скрываем троеточием
    // Если текущая страница имеет номер до 10, то выводим все номера
    // перед заданным диапазоном без скрытия
    // $prev
    $y = $first - 1;
    if ($first > $prev) {
        $html .= "<li class='paginate_button'><a onclick='goToPage({$y})'>...</a></li>";
    } else {
        for($i = 2;$i < $first;$i++){
            $html .=  "<li class='paginate_button'><a onclick='goToPage({$y})'>$i</a></li>";
        }
    }
    // отображаем заданный диапазон: текущая страница +-$prev
    for($i = $first;$i < $last + 1;$i++){
        // если выводится текущая страница, то ей назначается особый стиль css
        if($i == $curr_link) {
            $html .= "<li class='paginate_button ".$curr_css."'><a>". $i ."</a></li>";
        } else {
            $alink = "<li class='paginate_button'><a onclick='goToPage(";
            if($i != 0) $alink .= "{$i}";
            $alink .= ")'>$i</a></li>";
            $html .= $alink;
        }
    }
    $y = $last + 1;
    // часть страниц скрываем троеточием
    if ($last < $count_pages && $count_pages - $last > 2) $html .=  "<li class='paginate_button'><a onclick='goToPage({$y})'>...</a></li>";
    // выводим последнюю страницу
    $e = $count_pages;
    if ($last < $count_pages) $html .=  "<li class='paginate_button'><a onclick='goToPage({$e})'>$e</a></li>";

    return $html;
}

$return_id = "";
$order_id = "";
$client_id = "";
$status_id = "";

if (isset($_GET["return_id_param"]))
    $return_id = $_GET["return_id_param"];

if (isset($_GET["order_id"]))
    $order_id = $_GET["order_id"];

if (isset($_GET["client"] ))
    $client_id = $_GET["client"];

if (isset($_GET["status_id"] ))
    $status_id  = $_GET["status_id"];

?>
<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/content/users/statistics/assets/modal.css"><!--//Статистика-->
<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Фильтры
        </div>
        <div class="panel-body filter_panel">
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="" class="col-lg-6 control-label">
                        Номер заявки
                    </label>
                    <div class="col-lg-6">
                        <input <?=(!empty($return_id))?'style="background:#b9fcab;"':'';?> type="text" id="return_id" value="<?=$return_id;?>" class="form-control" />
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="form-group">
                    <label for="" class="col-lg-6 control-label">
                        Номер заказа
                    </label>
                    <div class="col-lg-6">
                        <input <?=(!empty($order_id))?'style="background:#b9fcab;"':'';?> type="text" id="order_id" value="<?=$order_id;?>" class="form-control" />
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="" class="col-lg-6 control-label">
                        Статус
                    </label>
                    <div class="col-lg-6">
                        <select <?=(!empty($status_id))?'style="background:#b9fcab;"':'';?> name="" id="status_id" class="form-control">
                            <option value="0">Не выбрано</option>
                        <?php

                            $query = $db_link->prepare("SELECT * FROM `shop_orders_returns_statuses`;");
                            $query->execute();
                            while($result_st = $query->fetch())
                            {
                                ?>
                                <option <?php if ($status_id == $result_st["id"]) echo "selected"; ?> value="<?=$result_st["id"];?>"><?=$result_st["caption"];?></option>
                                <?
                            }

                        ?>
                        </select>
						<script>
							document.getElementById("status_id").value = <?php echo $status_id; ?>;
						</script>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="" class="col-lg-6 control-label">
                        ID клиента
                    </label>
                    <div class="col-lg-6">
                        <input <?=(!empty($client_id))?'style="background:#b9fcab;"':'';?> type="text" id="client" class="form-control" value="<?=$client_id;?>">
                    </div>
                </div>
            </div>
        </div>
		<script>
		$(".filter_panel .form-control").keyup(function(event){
			if(event.keyCode == 13){
				filterReturns();
			}
		});
		</script>
        <div class="panel-footer">
            <button class="btn btn-success" type="button" onclick="filterReturns();"><i class="fa fa-filter"></i> Отфильтровать</button>
            <button class="btn btn-primary" type="button" onclick="unsetFilterReturns();"><i class="fa fa-square"></i> Снять фильтры</button>
        </div>
    </div>
</div>
<!-- Страница всех возвратов -->
<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Таблица возвратов
        </div>
        <div class="panel-body">
        <?php

            $WHERE_condition = "";
            $bind_param = [];
            if (isset($_GET["return_id_param"]))
            {
                $WHERE_condition .= " AND `shop_orders_returns`.`id` = ? ";
                array_push($bind_param, $_GET["return_id_param"]);
            }



            if (isset($_GET["status_id"]))
            {
                $WHERE_condition .= " AND `status_id` = ? ";
                array_push($bind_param, $_GET["status_id"]);

            }

            $sql_select_name = "(SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'surname' AND `shop_orders_returns`.`user_id` = `user_id`)";
            $sql_select_surname = "(SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'name' AND `shop_orders_returns`.`user_id` = `user_id`)";
            $sql_client = "CONCAT('ID ',`shop_orders_returns`.`user_id`, ', ', $sql_select_name, ' ', $sql_select_surname)";

            if (isset($_GET["client"]))
            {
                $WHERE_condition .= " AND `shop_orders_returns`.`user_id` = ? ";
                array_push($bind_param, $_GET["client"]);
            }

            $sql_select_order_id = "(SELECT `id` FROM `shop_orders` WHERE `id` = (SELECT `order_id` FROM `shop_orders_items` WHERE `id` = (SELECT `item_id` FROM `shop_orders_returns_items` WHERE `shop_orders_returns`.`id` = `return_id` LIMIT 1) LIMIT 1))";


            if (isset($_GET["order_id"]))
            {
                $WHERE_condition .= " AND ".$sql_select_order_id." = ? ";
                array_push($bind_param, $_GET["order_id"]);
            }
			
			if(isset($_GET["read"]) && (int)$_GET["read"] === 0)
			{
				$WHERE_condition .= " AND `shop_orders_returns`.`id` IN(SELECT DISTINCT `return_id` FROM `shop_orders_messages` WHERE `read` = 0 AND `is_customer` = 1 AND `return_id` > 0) ";
			}

            $select = "SELECT *, `shop_orders_returns`.`id` as `main_return_id`,$sql_select_order_id as `order_id`, $sql_client as `client`, ";
            $select .= "(SELECT COUNT(*) FROM `shop_orders_messages` WHERE `return_id` = `shop_orders_returns`.`id` AND `read` = 0 AND `is_customer` = 1) AS `count_not_viewed_msg` ";



            //Определяем, с какой страницы начать вывод:
            $s_page = 0;
            if( isset($_COOKIE['returns_need_page']) )
            {
                $s_page = (int) $_COOKIE['returns_need_page'];
            }


            //Определяем сколько пропустить записей для выборки
            $p = $DP_Config->list_page_limit;//Штук на страницу
            $start_elements_of_page = abs($s_page * $p);

            // print_r($SQL_SELECT_ITEMS_LIMIT);

            $elements_counter = 0;

            $select .= "FROM `shop_orders_returns` INNER JOIN `shop_orders_returns_statuses` ON `shop_orders_returns`.`status_id` = `shop_orders_returns_statuses`.`id` ".$WHERE_condition."  ORDER BY `shop_orders_returns`.`id` DESC";


            $returns_query = $db_link->prepare( $select );
            $returns_query->execute($bind_param);

            $elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
            $elements_count_rows_query->execute();
            $elements_count_rows = $elements_count_rows_query->fetchColumn();

            $count_pages = (int) ($elements_count_rows / $DP_Config->list_page_limit);


            $select .= " LIMIT $start_elements_of_page,$p";

            $returns_query = $db_link->prepare( $select );
            $returns_query->execute($bind_param);

        ?>
                <input class="form-control" type="text" placeholder="Поиск заявки - начните вводить значение из таблицы или любое поле товара" id="search-text" onkeyup="tableSearch()">
                <div class="hr-line-dashed col-lg-12"></div>
                <table cellpadding="1" cellspacing="1" id="orders_returns_table" class="table table-condensed table-striped" style="border-collapse: separate; border-spacing: 0 5px;">
                    <tr>
                        <th onclick="tableSort(1)" style="cursor: pointer;">ID</th>
                        <th onclick="tableSort(2)" style="cursor: pointer;">Статус</th>
                        <th onclick="tableSort(3)" style="cursor: pointer;">Клиент</th>
                        <th onclick="tableSort(4)" style="cursor: pointer;">ID заказа</th>
                    </tr>
                    <tbody>
                    <?php
                        $order_id = "";
                        while ($return = $returns_query->fetch())
                        {
                            $customer_id = $return['user_id'];//Статистика
                        ?>
                            <tr style="background: <?=$return["color"];?>;">
                                <td><a href="<?=$DP_Config->domain_path . $DP_Config->backend_dir . "/"?>shop/returns-manager?return_id=<?=$return["main_return_id"];?>"> <?=(int)$return["main_return_id"];?></a>
								<?php
								// Индикатор непрочитанных сообщений в заказах
								if($return["count_not_viewed_msg"] > 0){
								?>
								<a style="white-space: nowrap;" class="dropdown-toggle label-menu-corner" href="<?=$DP_Config->domain_path.$DP_Config->backend_dir;?>/shop/returns-manager?return_id=<?=$return["main_return_id"];?>">
									<i style="font-size: 25px;" class="pe-7s-mail"></i>
									<span style="font-size: 9px;"><?=$return["count_not_viewed_msg"];?></span>
								</a>
								<?php
								}
								?>
								</td>
                                <td><?=$return["caption"];?></td>
                                <td><?php include $_SERVER['DOCUMENT_ROOT'].'/'.$DP_Config->backend_dir.'/content/users/statistics/modal.php';//Статистика?><?=$return["client"];?></td></a>
                                <td><a href="<?=$DP_Config->domain_path . $DP_Config->backend_dir . "/"?>shop/orders/order?order_id=<?=$return["order_id"];?>" target="_blank"> <?=(int)$return["order_id"];?></a></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>

                <?php
                //START ВЫВОД ПЕРЕКЛЮЧАТЕЛЕЙ СТРАНИЦ ТАБЛИЦЫ
                if( $count_pages > 1 )
                {
                    // формируем пагинацию
                    $pagination = pagination($elements_count_rows, $p, 3, $s_page, 'paginate_button active', '');
                    if($pagination != '<a class="paginate_button active">1</a>'){
                        $pagination = '<div class="pagination">'.$pagination.'</div>';
                    }else{
                        $pagination = '';
                    }
                    ?>
                    <div class="row">
                        <div class="col-lg-12 text-center">
                            <div class="dataTables_paginate paging_simple_numbers">
                                <?php echo $pagination; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                //END ВЫВОД ПЕРЕКЛЮЧАТЕЛЕЙ СТРАНИЦ ТАБЛИЦЫ
                ?>
        </div>
    </div>
</div>

<!--//Статистика-->
<script src="/<?php echo $DP_Config->backend_dir; ?>/content/users/statistics/assets/main.js"></script>
<script>
    function closeCustomerModalInfo(user_id) {
        if (window.event.srcElement.id == 'customer-modal-info-' + user_id || window.event.srcElement.id == 'close-customer-modal-info-' + user_id)
            document.querySelector('#customer-modal-info-' + user_id).classList.remove('customer-modal-info-wrapper-show');
    }

    function showCustomerModalInfo(customer_id)
    {
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/users/statistics/frontAjax/ajax_loadUserModal.php",
            dataType: "json",//Тип возвращаемого значения
            data: "customer_id="+customer_id+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function(answer){
                if(answer.status == true)
                {
                    document.querySelector('#customer-modal-info-' + customer_id).classList.add('customer-modal-info-wrapper-show');
                    document.querySelector('#customer-modal-info-' + customer_id).innerHTML = answer.modal;
                }
                else
                {
                    alert("Ошибка авторизации");
                }
            }
        });
    }
</script>
<!--END //Статистика-->

<script>
    function tableSearch() {
        var phrase = document.getElementById('search-text');
        var table = document.getElementById('orders_returns_table');
        var regPhrase = new RegExp(phrase.value, 'i');
        var flag = false;
        for (var i = 1; i < table.rows.length; i++) {
            flag = false;
            for (var j = table.rows[i].cells.length - 1; j >= 0; j--) {
                flag = regPhrase.test(table.rows[i].cells[j].innerHTML);
                if (flag) break;
            }
            if (flag) {
                table.rows[i].style.display = "";
            } else {
                table.rows[i].style.display = "none";
            }

        }
    }

    let sorted_table = document.getElementById('orders_returns_table');;
    let sorted_way = [true, true, true, true];

    function tableSort(field)
    {
        if (sorted_way[field -1])
        {

            let sortedRows = Array.from(sorted_table.rows)
                .slice(1)
                .sort(function (rowA, rowB) {
                    if (field == 1 || field == 4)
                        return parseInt(rowA.cells[field - 1].textContent) > parseInt(rowB.cells[field - 1].textContent) ? 1 : -1
                    else
                        return rowA.cells[field - 1].textContent > rowB.cells[field - 1].textContent ? 1 : -1
                });

            sorted_table.tBodies[0].append(...sortedRows);
        }
        else
        {
            let sortedRows = Array.from(sorted_table.rows)
                .slice(1)
                .sort(function (rowA, rowB) {
                    if (field == 1 || field == 4)
                        return parseInt(rowA.cells[field - 1].textContent) < parseInt(rowB.cells[field - 1].textContent) ? 1 : -1
                    else
                        return rowA.cells[field - 1].textContent < rowB.cells[field - 1].textContent ? 1 : -1
                });

            sorted_table.tBodies[0].append(...sortedRows);
        }
        sorted_way[field -1] = !sorted_way[field -1];
    }

    function filterReturns()
    {
        let order_id = document.querySelector('#order_id').value;
        let return_id = document.querySelector('#return_id').value;
        let client = document.querySelector('#client').value;
        let status_id = document.querySelector('#status_id').value;


        let url = new URL(location.origin + location.pathname);

        if (order_id != '')
            url.searchParams.append('order_id', order_id);
        if (return_id != '')
           url.searchParams.append('return_id_param', return_id);
        if (client != '')
            url.searchParams.append('client', client);
        if (status_id != 0)
           url.searchParams.append('status_id', status_id);

        location = url.href;
    }

    function unsetFilterReturns()
    {
        location = location.origin + location.pathname;
    }
    function goToPage(need_page)
    {
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "returns_need_page="+need_page+"; path=/; expires=" + date.toUTCString();

        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/returns';
    }

</script>

