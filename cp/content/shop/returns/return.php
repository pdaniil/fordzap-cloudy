<?php
defined('_ASTEXE_') or die('No access');
    //Страница обработки одного возврата
    $select_return = $db_link->prepare("SELECT * FROM `shop_orders_returns` WHERE `id` = ?;");
    $select_return->execute([$_GET["return_id"]]);
    $return_result = $select_return->fetch();
    $return_select = $db_link->prepare("SELECT *, (SELECT `caption` FROM `shop_orders_returns_reasons` WHERE `shop_orders_returns_items`.`reason_id` = `id`) as `reason` FROM `shop_orders_returns_items` WHERE `return_id` = ?;");
    $return_select->execute([$_GET["return_id"]]);
    $returns_items_arr = [];
    $items_id_arr = [];
    while ($result = $return_select->fetch())
    {
        $returns_items_arr[$result["item_id"]] = $result;
        $items_id_arr[] = $result["item_id"];
    }
    $items_str = implode(",", $items_id_arr);
	// Делаем все сообщения по заявке на возврат прочитанными
	if((int)$_GET["return_id"] > 0){
		$db_link->prepare("UPDATE `shop_orders_messages` SET `read` = 1 WHERE `return_id` = ? AND `is_customer` = 1;")->execute( array($_GET["return_id"]) );
	}
?>
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                Заявка №<?=$_GET["return_id"];?>
            </div>
            <div class="panel-body">
                <div class="col-lg-4">
                    <label for="">Статус заявки</label>
                    <select name="reason_id" id="reason_id" class="form-control" onchange="change_status_return();">
                        <?php
                        $query_reason = $db_link->prepare("SELECT * FROM `shop_orders_returns_statuses`;");
                        $query_reason->execute();
                        while ($result_reason = $query_reason->fetch())
                            if ($return_result["status_id"] == $result_reason["id"])
                                echo '<option selected id="status_'.$result_reason["id"].'" value="'.$result_reason["id"].'">'.$result_reason["caption"].'</option>';
                            else
                                echo '<option id="status_'.$result_reason["id"].'"  value="'.$result_reason["id"].'">'.$result_reason["caption"].'</option>';
                        ?>
                    </select>
                </div>
                <div class="col-lg-12 col-sm-12 col-md-12" style="margin-top: 10px;">
                    <p class="lead" style="border-bottom: 3px solid; padding-bottom: 10px;"><strong>Товарные позиции</strong></p>
                    <div style="overflow: hidden; overflow-x: auto;">
                        <table class="table">
                            <tr>
                                <th style="vertical-align: middle; white-space: nowrap;">Производитель</th>
                                <th style="vertical-align: middle; white-space: nowrap;">Артикул</th>
                                <th style="vertical-align: middle;">Наименование</th>
                                <th style="vertical-align: middle; white-space: nowrap;">Цена</th>
                                <th style="vertical-align: middle; white-space: nowrap; text-align:center;">Кол-во</th>
                                <th style="vertical-align: middle; white-space: nowrap;">Сумма</th>
                            </tr>
                            <?php
                            //Флаг - по всем ли позициям принято решение
                            $confirm = true;
														$price_sum_total = 0;
                            //ПОЛЯ ИТОГО ПО ЗАКАЗУ
                            $count_need_total = 0;//Итого количество
                            //ПОЛУЧАЕМ ВСЕ ПОЗИЦИИ ЗАКАЗА
                            //Запрос наименований
                            $SELECT_product_name = "`t2_name`";
                            //Запрос артикула
                            $SELECT_product_article = "`t2_article`";
                            //Запрос производителя
                            $SELECT_product_manufacturer = "`t2_manufacturer`";
                            //Сумма позиции
                            $SELECT_item_price_sum = "`price`*`count_need`-`price`*`count_need`*`discount`/100";
                            //СЛОЖНЫЙ ВЛОЖЕННЫЙ ЗАПРОС
                            $SELECT_ORDER_ITEMS = "SELECT *, 
                            $SELECT_product_name AS `product_name`, 
                            $SELECT_item_price_sum AS `price_sum`, 
                            $SELECT_product_article AS `article`, 
                            $SELECT_product_manufacturer AS `manufacturer` 
                            FROM `shop_orders_items` WHERE `id` IN (".$items_str.");";
                            $order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
                            $order_items_query->execute( );
                            $order_id = '';
                            while( $order_item = $order_items_query->fetch() )
                            {
                                $order_id = $order_item["order_id"];
                                $item_id            = $order_item["id"];
                                $item_count_need    = $order_item["count_need"];
                                $item_price         = $order_item["price"];
                                $item_price_sum     = $order_item["price_sum"];
                                $item_product_name  = $order_item["product_name"];
                                $item_product_manufacturer  = $order_item["manufacturer"];
                                $item_product_article  = $order_item["article"];
                                //Считаем поля ИТОГО ПО ЗАКАЗУ (если статус позиции позволяет)
                                $count_need_total += $item_count_need;
                                $disabled_success = "";
                                $disabled_reject = "";
                                if ($returns_items_arr[$item_id]['return_success'] == '1')
                                {
																		$price_sum_total += $order_item["price"] * $order_item["count_need"];
                                    $return_item_status  = "Одобрено";
                                    $disabled_success = "disabled";
                                }
                                else if ($returns_items_arr[$item_id]['return_success'] == '0')
                                {
                                    $return_item_status  = "Отказано";
                                    $disabled_reject = "disabled";
                                }
                                else
                                {
                                    $return_item_status  = "На рассмотрении";
                                    $confirm = false;
                                }
                            ?>
                                <tr style="border-top: 1px solid black; ">
                                    <td style="vertical-align: middle; white-space: nowrap;"><?php echo $item_product_manufacturer; ?></td>
                                    <td style="vertical-align: middle; white-space: nowrap;"><?php echo $item_product_article; ?></td>
                                    <td style="vertical-align: middle; width: 100%; min-width: 200px; max-width: 800px; word-wrap: break-word;"><?php echo $item_product_name; ?></td>
                                    <td style="vertical-align: middle; white-space: nowrap;">
                                        <input type="hidden" id="item_price_<?=$item_id;?>" value="<?=$item_price;?>">
                                        <?php echo number_format($item_price, 2, '.', ' '); ?>
                                    </td>
                                    <td style="vertical-align: middle; white-space: nowrap; text-align:center;">
<!--Для доработок<input type="number" style="width: 70px;" onblur="check_val();" data-count="<?=$item_count_need;?>" id="item_count_<?=$item_id;?>" value="<?=$item_count_need;?>">-->
                                        <?=$item_count_need;?>
                                    </td>
																		<td style="vertical-align: middle; white-space: nowrap;"><?php echo number_format($item_price_sum, 2, '.', ' '); ?></td>
                                </tr>
                                <tr style="border-bottom: 1px solid black;">
                                    <td colspan="6">
                                        <div class="return_options">
                                            <div class="col-lg-12 col-sm-12 col-md-12" style="margin-top: 10px;">
                                                <label for="">Фото товара: </label>
                                                <div style="display: flex;">
                                                    <?php
                                                    $query = $db_link->prepare("SELECT * FROM `shop_orders_returns_items_images` WHERE `return_item_id` = ?;");
                                                    $query->execute([$returns_items_arr[$item_id]["id"]]);
                                                    while ($result_img_blob = $query->fetch()) {
																											echo '<div class="place_return_item_img"><img onclick="openImage(this)" class="return_item_img" src = "data:image/png;base64,' . base64_encode(file_get_contents($result_img_blob['image'])) . '"></div>';                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-sm-12 col-md-12" style="margin-top: 10px;">
                                                <label for="">Причина возврата: </label><?=" ".$returns_items_arr[$item_id]["reason"];?>
                                            </div>
                                            <div class="col-lg-12 col-sm-12 col-md-12" style="margin-top: 10px;">
                                                <label for="">Коммертарий покупателя: </label> <br>
                                                <div>
                                                    <?=$returns_items_arr[$item_id]["comment"];?>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-sm-12 col-md-12" style="margin-top: 10px;">
                                                <label for="">Статус: </label> <br>
                                                <div>
                                                    <?=$return_item_status;?>
                                                </div>
                                            </div>
                                            <?php
                                            if ($return_result["return_complete"] == '0')
                                            {
                                            ?>
                                            <div class="col-lg-12 col-sm-12 col-md-12" style="margin-top: 10px;">
                                                <button class="btn btn-success" <?=$disabled_success;?> onclick="resolveReturn(<?=$returns_items_arr[$item_id]["id"];?>)">Одобрить возврат</button>
                                                <button class="btn btn-danger" <?=$disabled_reject;?> onclick="rejectReturn(<?=$returns_items_arr[$item_id]["id"];?>)">Отклонить возврат</button>
                                            </div>
                                            <?
                                                }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" style="border-top: 3px solid; padding-top: 10px;"></td>
                                </tr>
                                <?php
                            }//while - по позициям заказа
                            ?>
                        </table>
                    </div>
                    <?php
                        if ($return_result["return_complete"] == '0')
                        {
													if ($DP_Config->retention_percentage > 0) {
														$price_sum_total = $price_sum_total - ($price_sum_total / 100) * $DP_Config->retention_percentage;
													}
                            ?>
                                <p class="lead">Сумма возврата:  <input onkeyup="price_sum_total = this.value; console.log(price_sum_total);" style="max-width: 200px;display: inline-block;" class="form-control" id="total_price_span" value="<?=$price_sum_total;?>" /> руб.</p>
                                <div class="complete_return_btn_wrapper">
                                	<?php
																	 if ($confirm > 0 && $price_sum_total > 0)
																	 {
																			 ?>
																			 <button onclick="refund(0);" class="btn btn-primary " type="button"><i class="fas fa-money-check-alt"></i> <span class="bold">Возрат на баланс клиента</span></button>
																			 <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Возврат оплаты на баланс клиента означает, что клиент сможет затем использовать денежные средства с баласа для оплаты других заказов.');"><i class="fa fa-info"></i></button>
																			 <button onclick="refund(1);" class="btn btn-primary " type="button"><i class="fas fa-hand-holding-usd"></i> <span class="bold">Прямой возврат клиенту</span></button>
																			 <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Прямой возврат клиенту означает, что вы вернули клиенту деньги наличными или на карту. Таким образом, после фактического возврата денег, нажмите эту кнопку, чтобы отразить возврат в системе учета платежей по заказам.');"><i class="fa fa-info"></i></button>
																			 
																			  <div class="cashless_payment">
																					<input type="checkbox" id="cashless_payment" name="cashless_payment" /> <div>Безналичный расчёт</div>
																				</div>

																				<style>
																					.cashless_payment {
																						display: flex;
																						grid-gap: 8px;
																						align-items: flex-start;
																						margin-top: 5px;
																						margin-bottom: 15px;
																					}
																				</style>
																			 <?
																	 }
																	 ?>
                                    <button class="btn btn-danger" onclick="closeReturn()">Отказать в возврате</button>
																</div>
														<?
                        }
                        else
                            echo "<h3>Заявка закрыта.</h3>";
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script>
		<?php 
			$selectUserId = $db_link->prepare("SELECT * FROM `shop_orders` WHERE `id` = ?;");
			$selectUserId->execute([$order_id]);
			$selectUserIdResult = $selectUserId->fetch();
			$user_id = $selectUserIdResult["user_id"];
		?>
		let return_id = <?=$_GET["return_id"];?>;
        let main_url = "<?php echo $DP_Config->domain_path;?><?=$DP_Config->backend_dir;?>";
        let user_id = <?=$user_id;?>;
        let price_sum_total = <?=$price_sum_total;?>;
        let order_id = <?=$order_id;?>;
        let tech_key = "<?=$DP_Config->tech_key;?>";
        let csrf_guard_key = '<?php echo $user_session["csrf_guard_key"]; ?>';
    </script>
    <script src="<?php echo $DP_Config->domain_path . $DP_Config->backend_dir;?>/content/shop/returns/assets/return.js.php"></script>
    <link rel="stylesheet" href="<?=$DP_Config->domain_path . $DP_Config->backend_dir;?>/content/shop/returns/assets/return.css">
<?php
    //Чат заявки
    require_once  $_SERVER["DOCUMENT_ROOT"] . "/". $DP_Config->backend_dir . "/content/shop/returns/return_chat.php";
?>