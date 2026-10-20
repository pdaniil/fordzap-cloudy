<div class="car_service_personal_salary_container">
    <div class="car_service_personal_salary_row">
        <div class="car_service_personal_salary_col">
            <div class="car_service_personal_salary_panel">
                <div class="car_service_personal_salary_panel_heading">
                    Таблица сотрудников
                </div>
                <div class="car_service_personal_salary_panel_body" id="workersList">
                    <!-- Данные из PHP -->
                    <?php foreach ($service_personal_list_arr as $worker) : ?>
                        <?php 
                        // Рассчитываем общую сумму для сотрудника
                        $totalAccrued = 0;
                        $totalPaid = 0;
                        
                        if(!empty($worker["bill_info"])) {
                            foreach($worker["bill_info"] as $item) {
                                if($item["type"] == "record") {
                                    $workerAmount = $item["percent_salary"] ?? 0;
                                    $totalAccrued += $workerAmount;
                                } elseif($item["type"] == "payment") {
                                    $totalPaid += $item["salary"];
                                }
                            }
                        }
                        
                        $balance = $totalAccrued - $totalPaid;
                        ?>
                        <div class="car_service_personal_salary_worker_row" data-worker-id="<?=$worker["id"] ?? '';?>" data-total-amount="<?=$balance;?>">
                            <div style="display: flex; flex-direction: column; justify-content: space-between; flex-wrap: wrap;">
							
								<div><?=$worker["fio"];?></div>
								<div>
								<a style="display: inline-block; width: 22px; height: 22px; background: url(/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/store.png) 0 0 no-repeat; background-size: cover;" href="javascript:void(0);" onclick="locationOrders(<?php echo $worker["user_id"]; ?>);"></a>
									<a style="display: inline-block; width: 22px; height: 22px; background: url(/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/credit_card.png) 0 0 no-repeat; background-size: cover;" href="javascript:void(0);" onclick="locationBalance(<?php echo $worker["user_id"]; ?>);"></a>
									<a style="display: inline-block; width: 22px; height: 22px; background: url(/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/key.png) 0 0 no-repeat; background-size: cover;" href="javascript:void(0);" onclick="auth_with_user(<?php echo $worker["user_id"]; ?>);"></a>
									<a style="display: inline-block; width: 22px; height: 22px; background: url(/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/statistics.png) 0 0 no-repeat; background-size: cover;" href="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir."/users/usermanager/user?user_id=".$worker["user_id"]."&type=statistics"; ?>" target="_blank"></a>
								</div>	
							</div>
                            <div class="car_service_personal_salary_worker_balance <?=($balance > 0) ? 'positive' : (($balance < 0) ? 'negative' : 'zero');?>">
                                <?=number_format($balance, 0, '', ' ');?> руб.
                            </div>
							<div>
							<span>
							<?php
								$stmt = $db_link->prepare('SELECT *,( IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=1 AND `active` = 1), 0) - IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=0 AND `active` = 1),0) ) AS `balance` FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `active` = 1;');
								$stmt->bindValue(':user_id', $worker["user_id"]);
								$stmt->execute();
								$balance_record = $stmt->fetch(PDO::FETCH_ASSOC);
								if($balance_record !== false){
									$balance_2 = (float) $balance_record["balance"];
								}else{
									$balance_2 = 0;
								}
								$balance_2 = number_format($balance_2, 2, '.', ' ');
								echo $balance_2 . ' руб.';
							?>
							</span>
							</div>
                            <div class="car_service_personal_salary_worker_percent"><?=($worker["service_percent"] ?? 0);?>%</div>
                            <div class="actions_wrapper">
                                <?php if($balance > 0): ?>
                                    <button class="car_service_personal_salary_pay_btn action_node" onclick="carServicePersonalSalaryOpenModal(<?=$worker["id"] ?? '0';?>, '<?=$worker["fio"];?>', <?=$balance;?>)">
                                        <i class="fas fa-money-bill-wave" style="margin-right: 5px;"></i>Выплатить
                                    </button>
                                <?php elseif($balance < 0): ?>
                                    <span class="car_service_personal_salary_overpaid">Переплата</span>
                                <?php else: ?>
                                    <span class="car_service_personal_salary_paid">Оплачено</span>
                                <?php endif; ?>
                                <!-- Кнопка Печать -->
                                <button class="car_service_personal_salary_print_btn action_node" onclick="openPrintModal(<?=$worker["id"] ?? '0';?>)">
                                    <i class="fas fa-print"></i> Печать
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="car_service_personal_salary_col">
            <div class="car_service_personal_salary_panel">
                <div class="car_service_personal_salary_panel_heading">
                    Детализация
                </div>
                <div class="car_service_personal_salary_panel_body" id="detailsPanel">
                    <!-- Детализация из PHP -->
                    <?php foreach ($service_personal_list_arr as $worker) : ?>
                        <div class="car_service_personal_salary_service_wrapper" id="car_service_personal_salary_details_<?=$worker["id"] ?? '';?>">
                            <div class="car_service_personal_salary_details_title">
                                Детализация: <?=$worker["fio"];?>
                            </div>
                            
                            <?php if(!empty($worker["bill_info"])): ?>
                                <div class="car_service_personal_salary_bill_header">
                                    <div class="car_service_personal_salary_bill_header_cell">Дата</div>
                                    <div class="car_service_personal_salary_bill_header_cell">Тип</div>
                                    <div class="car_service_personal_salary_bill_header_cell">Сумма услуг</div>
                                    <div class="car_service_personal_salary_bill_header_cell">Начислено</div>
                                    <div class="car_service_personal_salary_bill_header_cell">Действия</div>
                                </div>
                                
                                <?php 
                                $totalAccrued = 0;
                                $totalPaid = 0;
                                foreach($worker["bill_info"] as $item): 
                                    if($item["type"] == "record"):
                                        $workerAmount = $item["percent_salary"] ?? 0;
                                        $totalAccrued += $workerAmount;
										
										// --- ПОЛУЧАЕМ АВТОМОБИЛЬ ИЗ УСЛУГ ---
										$car_display = '';
										if (!empty($item["services"])) {
											// Собираем уникальные значения car_info
											$cars = array_unique(array_column($item["services"], 'car_info'));
											$cars = array_filter($cars); // убираем пустые, null, false
											if (!empty($cars)) {
												$car_display = implode(', ', $cars);
											}
										}
										// --- КОНЕЦ ДОБАВЛЕНИЯ ---  
										
                                    ?> 
                                        <?php
											// Определение класса для подсветки записи
											$highlight_class = '';
											if (!empty($item["order_id"]) && isset($item["order_status"])) {
												$current_time = new DateTime();
												$record_time = new DateTime($item["date"]);
												
												if ($current_time > $record_time) {
													// Статусы 3, 6, 9 считаются выполненными
													$completed_statuses = [3, 6, 9];
													if (in_array($item["order_status"], $completed_statuses)) {
														$highlight_class = 'completed';
													} else {
														$highlight_class = 'overdue';
													}
												}
											}
											?>

											<div class="car_service_personal_salary_service_row record <?= $highlight_class; ?>">
												
												
												<div><?=date('d.m.Y H:i', strtotime($item["date"]));?></div>
												<div>
													<span class="car_service_personal_salary_type_badge type-record">Работа</span>
												</div>
												<div><?=number_format($item["price"], 0, '', ' ');?> руб.</div>
												<div class="car_service_personal_salary_worker_amount positive">
													+<?=number_format($item["percent_salary"], 0, '', ' ');?> руб.
													<?php if(isset($item["services"]) && !empty($item["services"])): ?>
														<span class="car_service_personal_salary_services_toggle" onclick="toggleServices(<?=$item['order_id']?>)">
															<i class="fas fa-chevron-down"></i>
														</span>
													<?php endif; ?>
												</div>
												<div>
													<?php if(isset($item["order_id"]) && $item["order_id"]): ?>
														<a class="car_service_personal_salary_order_btn" href="https://fordzap.com/cp/shop/orders/order?order_id=<?=$item["order_id"]?>" target="_blank">
															<i class="fas fa-external-link-alt" style="margin-right: 5px;"></i>Заказ
														</a>
													<?php else: ?>
														<span class="car_service_personal_salary_no_order">Нет заказа</span>
													<?php endif; ?>
												</div>
												<?php if (!empty($car_display)): ?>
													<div class="record_salary_header" style="flex: 0 0 100%;">
														<i class="fas fa-car" style="margin-right: 3px; color: #3498db;"></i>
														<?=htmlspecialchars($car_display);?>
													</div>
												<?php endif; ?>
											</div>
                                        
										<!-- Детализация по услугам -->
										<?php if(isset($item["services"]) && !empty($item["services"])): ?>
											<div class="car_service_personal_salary_services_detail" id="services_<?=$item['order_id']?>" style="display: none;">
												<div class="car_service_personal_salary_services_header">
													<div class="car_service_personal_salary_service_cell">Услуга</div>
													<div class="car_service_personal_salary_service_cell">Авто</div>
													<div class="car_service_personal_salary_service_cell">Тип начисления</div>
													<div class="car_service_personal_salary_service_cell">Статус</div>
													<div class="car_service_personal_salary_service_cell">Сумма</div>
													<div class="car_service_personal_salary_service_cell">Начислено сотруднику</div>
												</div>
												<?php foreach($item["services"] as $service): ?>
													<div class="car_service_personal_salary_service_item <?=!$service['is_ready'] ? 'not-ready' : '';?>">
														<div class="car_service_personal_salary_service_cell">
															<?=htmlspecialchars($service["service_name"]);?>
														</div>
														<div class="car_service_personal_salary_service_cell">
															<?=htmlspecialchars($service["car_info"] ?? 'Не указано');?>
														</div>
														<div class="car_service_personal_salary_service_cell">
															<?php if($service["payment_type"] == "fixed"): ?>
																<span class="car_service_personal_salary_fixed_badge" title="Фиксированная оплата">
																	<i class="fas fa-lock"></i> Фикс
																</span>
															<?php else: ?>
																<span class="car_service_personal_salary_percent_badge" title="Процент от услуги">
																	<i class="fas fa-percent"></i> Процент
																</span>
															<?php endif; ?>
														</div>
														<div class="car_service_personal_salary_service_cell">
															<?php if(!$service['is_ready']): ?>
																<span class="car_service_personal_salary_status_badge status-<?=$service['status_id'];?>" 
																	  title="Статус: <?=htmlspecialchars($service['status_name'] ?? 'Неизвестный');?>">
																	<i class="fas fa-hourglass-half"></i> 
																	<?=htmlspecialchars($service['status_name'] ?? 'В работе');?>
																</span>
															<?php else: ?>
																<span class="car_service_personal_salary_status_badge status-ready">
																	<i class="fas fa-check-circle"></i> Готово
																</span>
															<?php endif; ?>
														</div>
														<div class="car_service_personal_salary_service_cell">
															<?=number_format($service["price"], 0, '', ' ');?> руб.
															<?php if($service["fix_price"] > 0): ?>
																<span class="car_service_personal_salary_fix_price_hint">
																	(фикс: <?=number_format($service["fix_price"], 0, '', ' ');?> руб.)
																</span>
															<?php endif; ?>
														</div>
														<div class="car_service_personal_salary_service_cell <?=($service["worker_payment"] > 0) ? 'positive' : 'not-ready-text';?>">
															<?php if($service["worker_payment"] > 0): ?>
																+<?=number_format($service["worker_payment"], 0, '', ' ');?> руб.
															<?php else: ?>
																<span class="not-ready-text">—</span>
															<?php endif; ?>
														</div>
													</div>
												<?php endforeach; ?>
												
												<?php if($item["has_unpaid_services"]): ?>
													<div class="car_service_personal_salary_unpaid_notice">
														<i class="fas fa-info-circle"></i> 
														Некоторые услуги еще не готовы к оплате (Работа не выполнена)
													</div>
												<?php endif; ?>
											</div>
										<?php endif; ?>
                                        
                                    <?php elseif($item["type"] == "payment"): 
                                        $totalPaid += $item["salary"];
                                    ?>
                                        <div class="car_service_personal_salary_service_row payment">
                                            <div><?=date('d.m.Y H:i', strtotime($item["date"]));?></div>
                                            <div><span class="car_service_personal_salary_type_badge type-payment">Выплата</span></div>
                                            <div></div>
                                            <div class="car_service_personal_salary_payment_amount negative">-<?=number_format($item["salary"], 0, '', ' ');?> руб.</div>
                                            <div>
                                                <span class="car_service_personal_salary_payment_info">
                                                    <i class="fas fa-check-circle" style="color: #27ae60; margin-right: 5px;"></i>Выплачено
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <div class="car_service_personal_salary_summary">
                                    <div class="car_service_personal_salary_summary_row accrual">
                                        <span>Всего начислено:</span>
                                        <span class="positive">+<?=number_format($totalAccrued, 0, '', ' ');?> руб.</span>
                                    </div>
                                    <div class="car_service_personal_salary_summary_row payment">
                                        <span>Всего выплачено:</span>
                                        <span class="negative">-<?=number_format($totalPaid, 0, '', ' ');?> руб.</span>
                                    </div>
                                    <div class="car_service_personal_salary_summary_row total" style="flex-direction: column;">
										<div style="display: flex; justify-content: space-between;">
                                        <span>Остаток для оплаты:</span>
                                        <span class="<?=($totalAccrued - $totalPaid > 0) ? 'positive' : (($totalAccrued - $totalPaid < 0) ? 'negative' : 'zero');?>">
                                            <?=number_format($totalAccrued - $totalPaid, 0, '', ' ');?> руб.
                                        </span>
										</div>
										<div style="display: flex; justify-content: space-between;">
										<span>Баланс на сайте: </span>
										<span><?php
											$stmt = $db_link->prepare('SELECT *,( IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=1 AND `active` = 1), 0) - IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=0 AND `active` = 1),0) ) AS `balance` FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `active` = 1;');
											$stmt->bindValue(':user_id', $worker["user_id"]);
											$stmt->execute();
											$balance_record = $stmt->fetch(PDO::FETCH_ASSOC);
											if($balance_record !== false){
												$balance = (float) $balance_record["balance"];
											}else{
												$balance = 0;
											}
											$balance = number_format($balance, 2, '.', ' ');
											echo $balance . ' руб.';
										?>
										</span>
										</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="car_service_personal_salary_no_data">
                                    Нет данных об операциях за выбранный период
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для выплаты -->
<div class="car_service_personal_salary_modal" id="carServicePersonalSalaryModal">
    <div class="car_service_personal_salary_modal_content">
        <div class="car_service_personal_salary_modal_header">
            <h3>Выплата зарплаты</h3>
            <span class="car_service_personal_salary_modal_close" onclick="carServicePersonalSalaryCloseModal()">&times;</span>
        </div>
        <div class="car_service_personal_salary_modal_body">
            <div class="car_service_personal_salary_modal_worker_info">
                Сотрудник: <span id="car_service_personal_salary_modal_worker_name"></span>
                <br>
                Баланс: <span id="car_service_personal_salary_modal_worker_balance" class="positive">0 руб.</span>
            </div>
            
            <div class="car_service_personal_salary_form_group">
                <label for="car_service_personal_salary_amount_input">Сумма к выплате (руб.)</label>
                <div class="car_service_personal_salary_input_wrapper">
                    <input type="number" 
                           id="car_service_personal_salary_amount_input" 
                           min="0" 
                           step="100"
                           class="car_service_personal_salary_form_input">
                    <div class="car_service_personal_salary_input_hint">
                        Максимум: <span id="car_service_personal_salary_max_amount" class="positive">0</span> руб.
                    </div>
                </div>
            </div>
            
			<?php 
			
				$select_offices_salary = $db_link->prepare("SELECT `caption`, `id` FROM `shop_offices`;");
				$select_offices_salary->execute();
				$offices_sal = $select_offices_salary->fetchAll(PDO::FETCH_ASSOC);
			?>
			
			<div class="car_service_personal_salary_form_group">
                <label for="car_service_personal_salary_amount_input">Офис</label>
                <div class="car_service_personal_salary_input_wrapper">
                    <select class="form-control" id="office_id_salary">
						<?php foreach ($offices_sal as $office) : ?>
							<option value="<?=$office["id"]?>"><?=$office["caption"]?></option>
						<?php endforeach; ?>
					</select>
                </div>
            </div>
			
            <div class="car_service_personal_salary_form_group">
                <label for="car_service_personal_salary_percent_input">Процент от суммы (%)</label>
                <div class="car_service_personal_salary_percent_controls">
                    <input type="range" 
                           id="car_service_personal_salary_percent_slider" 
                           min="0" 
                           max="100" 
                           value="100"
                           class="car_service_personal_salary_percent_slider">
                    <div class="car_service_personal_salary_percent_input_wrapper">
                        <input type="number" 
                               id="car_service_personal_salary_percent_input" 
                               min="0" 
                               max="100" 
                               value="100"
                               class="car_service_personal_salary_form_input car_service_personal_salary_percent_input">
                        <span class="car_service_personal_salary_percent_symbol">%</span>
                    </div>
                </div>
                <div class="car_service_personal_salary_percent_buttons">
                    <button type="button" class="car_service_personal_salary_percent_btn" data-percent="25">25%</button>
                    <button type="button" class="car_service_personal_salary_percent_btn" data-percent="50">50%</button>
                    <button type="button" class="car_service_personal_salary_percent_btn" data-percent="75">75%</button>
                    <button type="button" class="car_service_personal_salary_percent_btn" data-percent="100">100%</button>
                </div>
            </div>
            
            <div class="car_service_personal_salary_modal_summary">
                <div class="car_service_personal_salary_summary_item">
                    <span>Исходная сумма:</span>
                    <span id="car_service_personal_salary_original_amount" class="positive">0 руб.</span>
                </div>
                <div class="car_service_personal_salary_summary_item">
                    <span>Выплачиваемая сумма:</span>
                    <span id="car_service_personal_salary_payable_amount" class="negative">0 руб.</span>
                </div>
                <div class="car_service_personal_salary_summary_item">
                    <span>Процент выплаты:</span>
                    <span id="car_service_personal_salary_current_percent">100%</span>
                </div>
                <div class="car_service_personal_salary_summary_item">
                    <span>Остаток после выплаты:</span>
                    <span id="car_service_personal_salary_remaining_balance" class="positive">0 руб.</span>
                </div>
            </div>
        </div>
        <div class="car_service_personal_salary_modal_footer">
            <button type="button" class="car_service_personal_salary_modal_btn car_service_personal_salary_modal_btn_cancel" onclick="carServicePersonalSalaryCloseModal()">
                Отмена
            </button>
            <button type="button" class="car_service_personal_salary_modal_btn car_service_personal_salary_modal_btn_confirm" onclick="carServicePersonalSalaryConfirmPayment()">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i>Подтвердить выплату
            </button>
        </div>
    </div>
</div>

<!-- Модальное окно для печати (обновлённое) -->
<div class="car_service_personal_salary_modal" id="carServicePersonalPrintModal" style="display: none;">
    <div class="car_service_personal_salary_modal_content">
        <div class="car_service_personal_salary_modal_header">
            <h3>Печать ведомости</h3>
            <span class="car_service_personal_salary_modal_close" onclick="closePrintModal()">&times;</span>
        </div>
        <div class="car_service_personal_salary_modal_body">
            <p>Выберите период для формирования ведомости:</p>
            
            <!-- Быстрый выбор периода -->
            <div class="car_service_personal_salary_quick_periods" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px;">
                <button type="button" class="car_service_personal_salary_quick_period_btn" data-period="day" style="padding: 6px 14px; background: #e0e7ff; border: 1px solid #3b82f6; border-radius: 6px; cursor: pointer; font-size: 13px;">День</button>
                <button type="button" class="car_service_personal_salary_quick_period_btn" data-period="week" style="padding: 6px 14px; background: #e0e7ff; border: 1px solid #3b82f6; border-radius: 6px; cursor: pointer; font-size: 13px;">Неделя</button>
                <button type="button" class="car_service_personal_salary_quick_period_btn" data-period="month" style="padding: 6px 14px; background: #e0e7ff; border: 1px solid #3b82f6; border-radius: 6px; cursor: pointer; font-size: 13px;">Месяц</button>
                <button type="button" class="car_service_personal_salary_quick_period_btn" data-period="3months" style="padding: 6px 14px; background: #e0e7ff; border: 1px solid #3b82f6; border-radius: 6px; cursor: pointer; font-size: 13px;">3 месяца</button>
                <button type="button" class="car_service_personal_salary_quick_period_btn" data-period="alltime" style="padding: 6px 14px; background: #e0e7ff; border: 1px solid #3b82f6; border-radius: 6px; cursor: pointer; font-size: 13px;">Всё время</button>
            </div>
            
            <div class="car_service_personal_salary_form_group">
                <label for="print_date_from">Дата начала</label>
                <input type="date" id="print_date_from" class="car_service_personal_salary_form_input" value="<?=date('Y-m-01')?>">
            </div>
            <div class="car_service_personal_salary_form_group">
                <label for="print_date_to">Дата окончания</label>
                <input type="date" id="print_date_to" class="car_service_personal_salary_form_input" value="<?=date('Y-m-d')?>">
            </div>
            
            <!-- Чекбокс скрытия деталей -->
            <div class="car_service_personal_salary_form_group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                <input type="checkbox" id="hide_details_checkbox" style="width: 18px; height: 18px;">
                <label for="hide_details_checkbox" style="font-weight: normal; cursor: pointer;">Не показывать исходную сумму (только итоговый заработок)</label>
            </div>
        </div>
        <div class="car_service_personal_salary_modal_footer">
            <button type="button" class="car_service_personal_salary_modal_btn car_service_personal_salary_modal_btn_cancel" onclick="closePrintModal()">
                Отмена
            </button>
            <button type="button" class="car_service_personal_salary_modal_btn car_service_personal_salary_modal_btn_confirm" onclick="generatePrint()">
                <i class="fas fa-print" style="margin-right: 8px;"></i>Сформировать
            </button>
        </div>
    </div>
</div>

<style>
	/* Подсветка записей по статусу заказа и времени */
	.car_service_personal_salary_service_row.record.completed {
		border: 2px solid #27ae60;
		background: rgba(39, 174, 96, 0.05);
		box-shadow: 0 0 0 1px #27ae60;
	}

	.car_service_personal_salary_service_row.record.overdue {
		border: 2px solid #e74c3c;
		background: rgba(231, 76, 60, 0.05);
		box-shadow: 0 0 0 1px #e74c3c;
	}
    .car_service_personal_salary_container {
        margin: 0 auto;
        padding: 20px;
    }
    
    .car_service_personal_salary_row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .car_service_personal_salary_col {
        flex: 0 0 100%;
        max-width: 100%;
        padding: 0 10px;
        margin-bottom: 20px;
    }
    
    @media (min-width: 992px) {
        .car_service_personal_salary_col {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
    
    .car_service_personal_salary_panel {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        height: 100%;
        border: 1px solid #eaeaea;
    }
    
    .car_service_personal_salary_panel_heading {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 18px 20px;
        font-size: 18px;
        font-weight: 600;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .car_service_personal_salary_panel_body {
        padding: 20px;
        max-height: 600px;
        overflow-y: auto;
    }
    
    .car_service_personal_salary_worker_row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding: 15px;
        margin-bottom: 12px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #3498db;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .car_service_personal_salary_worker_row:hover {
        background: #edf7ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.15);
    }
    
    .car_service_personal_salary_worker_row.car_service_personal_salary_active {
        background: #e3f2fd;
        border-left-color: #2ecc71;
        box-shadow: 0 4px 12px rgba(46, 204, 113, 0.2);
    }
    
    .car_service_personal_salary_worker_row > div {
        flex: 1;
        padding: 0 10px;
    }
    
    .car_service_personal_salary_worker_row > div:nth-child(1) {
        flex: 0 0 35%;
        max-width: 35%;
        font-weight: 500;
        color: #2c3e50;
    }
    
    .car_service_personal_salary_worker_row > div:nth-child(2),
    .car_service_personal_salary_worker_row > div:nth-child(3) {
        flex: 0 0 15%;
        max-width: 15%;
        color: #555;
    }
    
    .car_service_personal_salary_worker_row > div:nth-child(4) {
        max-width: 25%;
        text-align: right;
    }
    
    /* Цвета для баланса */
    .positive {
        color: #27ae60 !important;
        font-weight: 600;
    }
    
    .negative {
        color: #e74c3c !important;
        font-weight: 600;
    }
    
    .zero {
        color: #7f8c8d !important;
        font-weight: 600;
    }
    
    .car_service_personal_salary_worker_balance.positive {
        color: #27ae60;
    }
    
    .car_service_personal_salary_worker_balance.negative {
        color: #e74c3c;
    }
    
    .car_service_personal_salary_worker_balance.zero {
        color: #7f8c8d;
    }
    
    .car_service_personal_salary_worker_percent {
        font-weight: 600;
        color: #3498db;
    }
    .actions_wrapper {
		display: flex;
		grid-gap: 10px;
		flex-direction: column;
	}
	.action_node {
		display: flex;
		max-height: 35px;
		max-width: 135px;
		justify-content: center;
		grid-gap: 5px;
		align-items: center;
	}
    .car_service_personal_salary_pay_btn {
        background: linear-gradient(to right, #27ae60, #219653);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    .car_service_personal_salary_pay_btn:hover {
        background: linear-gradient(to right, #219653, #1e8449);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(39, 174, 96, 0.3);
    }
    
    .car_service_personal_salary_overpaid {
        color: #e74c3c;
        font-weight: 500;
        font-size: 14px;
        padding: 6px 12px;
        background: rgba(231, 76, 60, 0.1);
        border-radius: 4px;
        border: 1px solid rgba(231, 76, 60, 0.2);
    }
    
    .car_service_personal_salary_paid {
        color: #7f8c8d;
        font-weight: 500;
        font-size: 14px;
        padding: 6px 12px;
        background: rgba(127, 140, 141, 0.1);
        border-radius: 4px;
        border: 1px solid rgba(127, 140, 141, 0.2);
    }
    
    .car_service_personal_salary_service_wrapper {
        display: none;
    }
    
    .car_service_personal_salary_service_wrapper.car_service_personal_salary_active {
        display: block;
        animation: car_service_personal_salary_fadeIn 0.5s ease;
    }
    
    /* Заголовок таблицы биллинга */
    .car_service_personal_salary_bill_header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding: 12px 14px;
        margin-bottom: 10px;
        background: #2c3e50;
        color: white;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
    }
    
    .car_service_personal_salary_bill_header_cell {
        flex: 1;
        padding: 0 10px;
    }
    
    .car_service_personal_salary_bill_header_cell:nth-child(1) {
        flex: 0 0 15%;
        max-width: 15%;
    }
    
    .car_service_personal_salary_bill_header_cell:nth-child(2) {
        flex: 0 0 12%;
        max-width: 12%;
    }
    
    .car_service_personal_salary_bill_header_cell:nth-child(3) {
        flex: 0 0 15%;
        max-width: 15%;
    }
    
    .car_service_personal_salary_bill_header_cell:nth-child(4) {
        flex: 0 0 20%;
        max-width: 20%;
    }
    
    .car_service_personal_salary_bill_header_cell:nth-child(5) {
        flex: 0 0 18%;
        max-width: 18%;
    }
    
    /* Строки биллинга */
    .car_service_personal_salary_service_row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding: 14px;
        margin-bottom: 10px;
        border-radius: 8px;
        border: 1px solid #eaeaea;
    }
    
    .car_service_personal_salary_service_row.record {
        background: #f8f9fa;
    }
    
    .car_service_personal_salary_service_row.payment {
        background: #fff5f5;
        border-left: 4px solid #e74c3c;
    }
    
    .car_service_personal_salary_service_row:nth-child(even).record {
        background: #f1f8ff;
    }
    
    .car_service_personal_salary_service_row:nth-child(even).payment {
        background: #ffeaea;
    }
    
    .car_service_personal_salary_service_row > div {
        flex: 1;
        padding: 0 10px;
    }
    
    .car_service_personal_salary_service_row > div:nth-child(1) {
        flex: 0 0 15%;
        max-width: 15%;
        color: #2c3e50;
        font-weight: 500;
        font-size: 14px;
    }
    
    .car_service_personal_salary_service_row > div:nth-child(2) {
        flex: 0 0 12%;
        max-width: 12%;
    }
    
    .car_service_personal_salary_service_row > div:nth-child(3) {
        flex: 0 0 15%;
        max-width: 15%;
        color: #555;
        font-weight: 500;
    }
    
    .car_service_personal_salary_service_row > div:nth-child(4) {
        flex: 0 0 20%;
        max-width: 20%;
        font-weight: 600;
    }
    
    .car_service_personal_salary_service_row > div:nth-child(5) {
        flex: 0 0 18%;
        max-width: 18%;
        text-align: right;
    }
    
    /* Бейджи типов */
    .car_service_personal_salary_type_badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .car_service_personal_salary_type_badge.type-record {
        background: rgba(39, 174, 96, 0.1);
        color: #27ae60;
        border: 1px solid #27ae60;
    }
    
    .car_service_personal_salary_type_badge.type-payment {
        background: rgba(231, 76, 60, 0.1);
        color: #e74c3c;
        border: 1px solid #e74c3c;
    }
    
    /* Бейджи для типов начисления */
    .car_service_personal_salary_fixed_badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        background: rgba(52, 152, 219, 0.1);
        color: #3498db;
        border: 1px solid #3498db;
        font-size: 11px;
        font-weight: 600;
    }
    
    .car_service_personal_salary_percent_badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        background: rgba(155, 89, 182, 0.1);
        color: #9b59b6;
        border: 1px solid #9b59b6;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Детализация услуг */
    .car_service_personal_salary_services_detail {
        padding: 15px;
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #eaeaea;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .car_service_personal_salary_services_header {
        display: flex;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 13px;
    }
    
    .car_service_personal_salary_service_item {
        display: flex;
        padding: 10px;
        border-bottom: 1px solid #eaeaea;
        font-size: 13px;
    }
    
    .car_service_personal_salary_service_item:last-child {
        border-bottom: none;
    }
    
    .car_service_personal_salary_service_cell {
        flex: 1;
        padding: 0 10px;
    }
    
   	.car_service_personal_salary_service_cell:nth-child(1) {
		flex: 0 0 25%;
		max-width: 25%;
	}
	.car_service_personal_salary_service_cell:nth-child(2) {
		flex: 0 0 15%;
		max-width: 15%;
	}
	.car_service_personal_salary_service_cell:nth-child(3) {
		flex: 0 0 12%;
		max-width: 12%;
	}
	.car_service_personal_salary_service_cell:nth-child(4) {
		flex: 0 0 18%;
		max-width: 18%;
	}
	.car_service_personal_salary_service_cell:nth-child(5) {
		flex: 0 0 15%;
		max-width: 15%;
	}
	.car_service_personal_salary_service_cell:nth-child(6) {
		flex: 0 0 15%;
		max-width: 15%;
	}
    
    .car_service_personal_salary_fix_price_hint {
        font-size: 11px;
        color: #7f8c8d;
        margin-left: 5px;
    }
    
    .car_service_personal_salary_services_toggle {
        display: inline-block;
        margin-left: 10px;
        padding: 3px 8px;
        background: #eaeaea;
        border-radius: 4px;
        cursor: pointer;
        color: #555;
        font-size: 12px;
    }
    
    .car_service_personal_salary_services_toggle:hover {
        background: #d0d0d0;
    }
    
    /* Суммы */
    .car_service_personal_salary_worker_amount.positive {
        color: #27ae60 !important;
    }
    
    .car_service_personal_salary_payment_amount.negative {
        color: #e74c3c !important;
    }
    
    .car_service_personal_salary_payment_info {
        color: #27ae60;
        font-weight: 500;
        font-size: 14px;
    }
    
    .car_service_personal_salary_order_btn {
        background: linear-gradient(to right, #3498db, #2980b9);
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 13px;
        text-decoration: none;
        display: inline-block;
    }
    
    .car_service_personal_salary_order_btn:hover {
        background: linear-gradient(to right, #2980b9, #2573a7);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
    }
    
    .car_service_personal_salary_no_order {
        color: #95a5a6;
        font-style: italic;
        font-size: 13px;
    }
    
    .car_service_personal_salary_details_title {
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
    }
    
    /* Сводка */
    .car_service_personal_salary_summary {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        border: 1px solid #eaeaea;
    }
    
    .car_service_personal_salary_summary_row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 15px;
    }
    
    .car_service_personal_salary_summary_row:last-child {
        margin-bottom: 0;
    }
    
    .car_service_personal_salary_summary_row.accrual {
        color: #2c3e50;
    }
    
    .car_service_personal_salary_summary_row.payment {
        color: #2c3e50;
    }
    
    .car_service_personal_salary_summary_row.total {
        font-weight: 600;
        font-size: 16px;
        padding-top: 10px;
        border-top: 1px solid #eaeaea;
        margin-top: 10px;
    }
    
    .car_service_personal_salary_balance {
        font-weight: 700 !important;
    }
    
    .car_service_personal_salary_no_data {
        text-align: center;
        padding: 40px 20px;
        color: #7f8c8d;
        font-style: italic;
    }
    
    /* Стили для модального окна */
    .car_service_personal_salary_modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        animation: car_service_personal_salary_modal_fadeIn 0.3s ease;
    }
    
    .car_service_personal_salary_modal_content {
        background-color: white;
        margin: 5% auto;
        width: 90%;
        max-width: 500px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }
    
    .car_service_personal_salary_modal_header {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .car_service_personal_salary_modal_header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
    }
    
    .car_service_personal_salary_modal_close {
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s;
    }
    
    .car_service_personal_salary_modal_close:hover {
        color: #ffdddd;
    }
    
    .car_service_personal_salary_modal_body {
        padding: 25px;
    }
    
    .car_service_personal_salary_modal_worker_info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 16px;
        border-left: 4px solid #3498db;
        line-height: 1.6;
    }
    
    .car_service_personal_salary_modal_worker_info span {
        font-weight: 600;
    }
    
    .car_service_personal_salary_form_group {
        margin-bottom: 25px;
    }
    
    .car_service_personal_salary_form_group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #2c3e50;
    }
    
    .car_service_personal_salary_form_input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;
    }
    
    .car_service_personal_salary_form_input:focus {
        border-color: #3498db;
        outline: none;
    }
    
    .car_service_personal_salary_input_wrapper {
        position: relative;
    }
    
    .car_service_personal_salary_input_hint {
        margin-top: 5px;
        font-size: 14px;
        color: #7f8c8d;
    }
    
    .car_service_personal_salary_percent_controls {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .car_service_personal_salary_percent_slider {
        flex: 1;
        height: 6px;
        border-radius: 3px;
        background: #e0e0e0;
        outline: none;
        -webkit-appearance: none;
    }
    
    .car_service_personal_salary_percent_slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #3498db;
        cursor: pointer;
    }
    
    .car_service_personal_salary_percent_input_wrapper {
        position: relative;
        width: 100px;
    }
    
    .car_service_personal_salary_percent_input {
        padding-right: 35px;
        text-align: right;
    }
    
    .car_service_personal_salary_percent_symbol {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #7f8c8d;
        font-weight: 500;
    }
    
    .car_service_personal_salary_percent_buttons {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    
    .car_service_personal_salary_percent_btn {
        flex: 1;
        padding: 8px 12px;
        background: #f1f8ff;
        border: 2px solid #3498db;
        border-radius: 6px;
        color: #3498db;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .car_service_personal_salary_percent_btn:hover {
        background: #3498db;
        color: white;
    }
    
    .car_service_personal_salary_modal_summary {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-top: 25px;
    }
    
    .car_service_personal_salary_summary_item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 15px;
    }
    
    .car_service_personal_salary_summary_item:last-child {
        margin-bottom: 0;
    }
    
    .car_service_personal_salary_highlight {
        font-weight: 600;
        font-size: 18px;
    }
    
    .car_service_personal_salary_modal_footer {
        padding: 20px;
        background: #f8f9fa;
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        border-top: 1px solid #eaeaea;
    }
    
    .car_service_personal_salary_modal_btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.3s;
        font-size: 16px;
    }
    
    .car_service_personal_salary_modal_btn_cancel {
        background: #e0e0e0;
        color: #555;
    }
    
    .car_service_personal_salary_modal_btn_cancel:hover {
        background: #d0d0d0;
    }
    
    .car_service_personal_salary_modal_btn_confirm {
        background: linear-gradient(to right, #e74c3c, #c0392b);
        color: white;
    }
    
    .car_service_personal_salary_modal_btn_confirm:hover {
        background: linear-gradient(to right, #c0392b, #a93226);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
    }
    
    /* Кнопка печати */
    .car_service_personal_salary_print_btn {
        background: linear-gradient(to right, #3498db, #2980b9);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    .car_service_personal_salary_print_btn:hover {
        background: linear-gradient(to right, #2980b9, #2573a7);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
    }
    
    @keyframes car_service_personal_salary_fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes car_service_personal_salary_modal_fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @media (max-width: 768px) {
        .car_service_personal_salary_worker_row,
        .car_service_personal_salary_service_row {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .car_service_personal_salary_worker_row > div,
        .car_service_personal_salary_service_row > div {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 8px;
        }
        
        .car_service_personal_salary_worker_row > div:nth-child(4),
        .car_service_personal_salary_service_row > div:nth-child(5) {
            text-align: left;
            margin-top: 5px;
        }
        
        .car_service_personal_salary_bill_header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .car_service_personal_salary_bill_header_cell {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 5px;
        }
        
        .car_service_personal_salary_panel_body {
            padding: 15px;
            max-height: 500px;
        }
        
        .car_service_personal_salary_modal_content {
            width: 95%;
            margin: 10% auto;
        }
        
        .car_service_personal_salary_percent_controls {
            flex-direction: column;
            align-items: stretch;
        }
        
        .car_service_personal_salary_percent_input_wrapper {
            width: 100%;
        }
        
        .car_service_personal_salary_modal_footer {
            flex-direction: column;
        }
        
        .car_service_personal_salary_modal_btn {
            width: 100%;
        }
    }
	
	
	/* Стили для статусов */
.car_service_personal_salary_service_item.not-ready {
    background: rgba(255, 243, 205, 0.3);
}

.car_service_personal_salary_status_badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.car_service_personal_salary_status_badge.status-ready {
    background: rgba(39, 174, 96, 0.1);
    color: #27ae60;
    border: 1px solid #27ae60;
}

.car_service_personal_salary_status_badge.status-21 {
    background: rgba(39, 174, 96, 0.1);
    color: #27ae60;
    border: 1px solid #27ae60;
}

.car_service_personal_salary_status_badge.status-20 {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border: 1px solid #3498db;
}

.car_service_personal_salary_status_badge.status-19 {
    background: rgba(241, 196, 15, 0.1);
    color: #f1c40f;
    border: 1px solid #f1c40f;
}

.car_service_personal_salary_status_badge[class*="status-"]:not(.status-21):not(.status-20):not(.status-19) {
    background: rgba(127, 140, 141, 0.1);
    color: #7f8c8d;
    border: 1px solid #7f8c8d;
}

.not-ready-text {
    color: #95a5a6;
    font-style: italic;
}

.car_service_personal_salary_unpaid_notice {
    margin-top: 10px;
    padding: 8px 12px;
    background: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 4px;
    color: #856404;
    font-size: 12px;
}

.car_service_personal_salary_unpaid_notice i {
    margin-right: 5px;
}
	
</style>

<script>

	function locationOrders(user_id)
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
		orders_filter.customer_id = user_id;
		orders_filter.paid_type = -1;
		//Устанавливаем cookie (на полгода)
		var date = new Date(new Date().getTime() + 15552000 * 1000);
		document.cookie = "orders_filter="+JSON.stringify(orders_filter)+"; path=/; expires=" + date.toUTCString();

		//Обновляем страницу
		//location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
		window.open('/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders', "_blank");
	}
	
	function locationBalance(user_id)
	{
		var account_operations_filter = new Object;

		account_operations_filter.time_from = "";
		account_operations_filter.time_to = "";
		account_operations_filter.income = -1;
		account_operations_filter.operation_code = -1;
		account_operations_filter.user_id = user_id;

		//Устанавливаем cookie (на полгода)
		var date = new Date(new Date().getTime() + 15552000 * 1000);
		document.cookie = "account_operations_filter="+JSON.stringify(account_operations_filter)+"; path=/; expires=" + date.toUTCString();

		//Обновляем страницу
		window.open('/<?php echo $DP_Config->backend_dir; ?>/shop/finance/account_operations', "_blank");
	}
	
	function auth_with_user(user_id)
	{
		jQuery.ajax({
			type: "POST",
			async: false, //Запрос синхронный
			url: "/<?php echo $DP_Config->backend_dir; ?>/content/users/auth_with_user.php",
			dataType: "json",//Тип возвращаемого значения
			data: "user_id="+user_id+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
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

	
    // Текущий выбранный сотрудник
    let carServicePersonalSalarySelectedWorkerId = null;
    let carServicePersonalSalaryCurrentWorkerId = null;
    let carServicePersonalSalaryMaxAmount = 0;
    let carServicePersonalSalaryOriginalAmount = 0;

    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        // Находим первого сотрудника из списка
        const firstWorkerRow = document.querySelector('.car_service_personal_salary_worker_row');
        if (firstWorkerRow) {
            carServicePersonalSalarySelectedWorkerId = firstWorkerRow.dataset.workerId;
            carServicePersonalSalarySelectWorker(carServicePersonalSalarySelectedWorkerId);
        }
        
        // Добавляем обработчики кликов на строки сотрудников
        document.querySelectorAll('.car_service_personal_salary_worker_row').forEach(row => {
            row.addEventListener('click', function(e) {
                // Если клик был по кнопке "Выплатить" или "Печать", не обрабатываем выбор сотрудника
                if (e.target.closest('.car_service_personal_salary_pay_btn') || e.target.closest('.car_service_personal_salary_print_btn')) {
                    return;
                }
                
                const workerId = this.dataset.workerId;
                carServicePersonalSalarySelectWorker(workerId);
            });
        });
        
        // Инициализация обработчиков для модального окна
        carServicePersonalSalaryInitModalHandlers();
        // Инициализация обработчиков для печатной модалки
        carServicePersonalSalaryInitPrintModalHandlers();
    });

    // Функция выбора сотрудника
    function carServicePersonalSalarySelectWorker(workerId) {
        carServicePersonalSalarySelectedWorkerId = workerId;
        
        // Обновляем список сотрудников - выделяем активного
        document.querySelectorAll('.car_service_personal_salary_worker_row').forEach(row => {
            if (row.dataset.workerId === workerId) {
                row.classList.add('car_service_personal_salary_active');
            } else {
                row.classList.remove('car_service_personal_salary_active');
            }
        });
        
        // Обновляем детализацию - показываем только выбранного сотрудника
        document.querySelectorAll('.car_service_personal_salary_service_wrapper').forEach(wrapper => {
            if (wrapper.id === `car_service_personal_salary_details_${workerId}`) {
                wrapper.classList.add('car_service_personal_salary_active');
            } else {
                wrapper.classList.remove('car_service_personal_salary_active');
            }
        });
    }

    // Функция открытия модального окна
    function carServicePersonalSalaryOpenModal(workerId, workerName, balance) {
        //event.stopPropagation();
        
        carServicePersonalSalaryCurrentWorkerId = workerId;
        carServicePersonalSalaryMaxAmount = balance;
        carServicePersonalSalaryOriginalAmount = balance;
        
        // Заполняем данные в модальном окне
        document.getElementById('car_service_personal_salary_modal_worker_name').textContent = workerName;
        document.getElementById('car_service_personal_salary_modal_worker_balance').textContent = 
            carServicePersonalSalaryFormatNumber(balance) + ' руб.';
        document.getElementById('car_service_personal_salary_max_amount').textContent = 
            carServicePersonalSalaryFormatNumber(balance);
        document.getElementById('car_service_personal_salary_original_amount').textContent = 
            carServicePersonalSalaryFormatNumber(balance) + ' руб.';
        
        // Обновляем цвет баланса
        const balanceElement = document.getElementById('car_service_personal_salary_modal_worker_balance');
        balanceElement.className = balance > 0 ? 'positive' : (balance < 0 ? 'negative' : 'zero');
        
        // Устанавливаем значения по умолчанию
        const amountInput = document.getElementById('car_service_personal_salary_amount_input');
        amountInput.value = balance > 0 ? balance : 0;
        amountInput.max = balance > 0 ? balance : 0;
        
        const percentInput = document.getElementById('car_service_personal_salary_percent_input');
        const percentSlider = document.getElementById('car_service_personal_salary_percent_slider');
        percentInput.value = 100;
        percentSlider.value = 100;
        
        // Обновляем сводку
        carServicePersonalSalaryUpdateSummary();
        
        // Показываем модальное окно
        document.getElementById('carServicePersonalSalaryModal').style.display = 'block';
    }

    // Функция закрытия модального окна
    function carServicePersonalSalaryCloseModal() {
        document.getElementById('carServicePersonalSalaryModal').style.display = 'none';
        carServicePersonalSalaryCurrentWorkerId = null;
    }

    // Инициализация обработчиков для модального окна
    function carServicePersonalSalaryInitModalHandlers() {
        const amountInput = document.getElementById('car_service_personal_salary_amount_input');
        const percentInput = document.getElementById('car_service_personal_salary_percent_input');
        const percentSlider = document.getElementById('car_service_personal_salary_percent_slider');
        
        // Обработчик изменения суммы вручную
        amountInput.addEventListener('input', function() {
            let value = parseFloat(this.value) || 0;
            
            // Ограничиваем максимальное значение
            if (value > carServicePersonalSalaryMaxAmount) {
                value = carServicePersonalSalaryMaxAmount;
                this.value = value;
            }
            
            // Если сумма отрицательная, устанавливаем 0
            if (value < 0) {
                value = 0;
                this.value = 0;
            }
            
            // Пересчитываем процент
            if (carServicePersonalSalaryMaxAmount > 0) {
                const newPercent = Math.round((value / carServicePersonalSalaryMaxAmount) * 100);
                percentInput.value = newPercent;
                percentSlider.value = newPercent;
            }
            
            carServicePersonalSalaryUpdateSummary();
        });
        
        // Обработчик изменения процента через поле ввода
        percentInput.addEventListener('input', function() {
            let percent = parseInt(this.value) || 0;
            
            // Ограничиваем от 0 до 100
            if (percent < 0) percent = 0;
            if (percent > 100) percent = 100;
            
            this.value = percent;
            percentSlider.value = percent;
            
            // Пересчитываем сумму
            const newAmount = carServicePersonalSalaryMaxAmount * (percent / 100);
            amountInput.value = Math.round(newAmount);
            
            carServicePersonalSalaryUpdateSummary();
        });
        
        // Обработчик изменения процента через слайдер
        percentSlider.addEventListener('input', function() {
            const percent = parseInt(this.value);
            percentInput.value = percent;
            
            // Пересчитываем сумму
            const newAmount = carServicePersonalSalaryMaxAmount * (percent / 100);
            amountInput.value = Math.round(newAmount);
            
            carServicePersonalSalaryUpdateSummary();
        });
        
        // Обработчики для кнопок быстрого выбора процента
        document.querySelectorAll('.car_service_personal_salary_percent_btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const percent = parseInt(this.dataset.percent);
                percentInput.value = percent;
                percentSlider.value = percent;
                
                // Пересчитываем сумму
                const newAmount = carServicePersonalSalaryMaxAmount * (percent / 100);
                amountInput.value = Math.round(newAmount);
                
                carServicePersonalSalaryUpdateSummary();
            });
        });
        
        // Закрытие модального окна при клике вне его
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('carServicePersonalSalaryModal');
            if (event.target === modal) {
                carServicePersonalSalaryCloseModal();
            }
        });
    }

    // Обновление сводки в модальном окне
    function carServicePersonalSalaryUpdateSummary() {
        const amountInput = document.getElementById('car_service_personal_salary_amount_input');
        const percentInput = document.getElementById('car_service_personal_salary_percent_input');
        
        const payableAmount = parseFloat(amountInput.value) || 0;
        const currentPercent = parseInt(percentInput.value) || 0;
        const remainingBalance = carServicePersonalSalaryMaxAmount - payableAmount;
        
        document.getElementById('car_service_personal_salary_payable_amount').textContent = 
            carServicePersonalSalaryFormatNumber(payableAmount) + ' руб.';
        document.getElementById('car_service_personal_salary_current_percent').textContent = 
            currentPercent + '%';
        document.getElementById('car_service_personal_salary_remaining_balance').textContent = 
            carServicePersonalSalaryFormatNumber(remainingBalance) + ' руб.';
        
        // Обновляем цвет остатка
        const remainingElement = document.getElementById('car_service_personal_salary_remaining_balance');
        remainingElement.className = remainingBalance > 0 ? 'positive' : (remainingBalance < 0 ? 'negative' : 'zero');
    }

    // Функция подтверждения выплаты
    function carServicePersonalSalaryConfirmPayment() {
        const amountInput = document.getElementById('car_service_personal_salary_amount_input');
        const office_id = document.getElementById('office_id_salary').value;
        const workerName = document.getElementById('car_service_personal_salary_modal_worker_name').textContent;
        
        const payableAmount = parseFloat(amountInput.value) || 0;
        
        if (payableAmount <= 0) {
            alert('Укажите сумму для выплаты');
            return;
        }
        
        if (payableAmount > carServicePersonalSalaryMaxAmount) {
            alert('Сумма выплаты не может превышать баланс сотрудника');
            return;
        }
        
        if (confirm(`Выплатить ${carServicePersonalSalaryFormatNumber(payableAmount)} руб. сотруднику ${workerName}?`)) {
            postAjax({
                action: 'pay_salary', 
                worker_id: carServicePersonalSalaryCurrentWorkerId, 
                salary: payableAmount,
				office_id : office_id
            }, false, 'reload_page');
        }
    }
    
    // Функция перезагрузки страницы
    function reload_page() {
        location.reload();
    }
    
    // Функция показа/скрытия детализации услуг
    function toggleServices(orderId) {
        const servicesBlock = document.getElementById('services_' + orderId);
        if (servicesBlock) {
            if (servicesBlock.style.display === 'none' || servicesBlock.style.display === '') {
                servicesBlock.style.display = 'block';
            } else {
                servicesBlock.style.display = 'none';
            }
        }
    }
    
    // Вспомогательная функция для форматирования чисел
    function carServicePersonalSalaryFormatNumber(number) {
        return new Intl.NumberFormat('ru-RU').format(Math.round(number));
    }

    // ---------- Функции для печати (обновлённые) ----------
    let printWorkerId = null;

    function openPrintModal(workerId) {
        printWorkerId = workerId;
        // Устанавливаем даты по умолчанию: начало текущего месяца и сегодня
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        document.getElementById('print_date_from').value = firstDay.toISOString().split('T')[0];
        document.getElementById('print_date_to').value = now.toISOString().split('T')[0];
        // Сбрасываем чекбокс
        document.getElementById('hide_details_checkbox').checked = false;
        document.getElementById('carServicePersonalPrintModal').style.display = 'block';
    }

    function closePrintModal() {
        document.getElementById('carServicePersonalPrintModal').style.display = 'none';
        printWorkerId = null;
    }

    // Инициализация обработчиков для печатной модалки
    function carServicePersonalSalaryInitPrintModalHandlers() {
        // Кнопки быстрого выбора периода
        document.querySelectorAll('.car_service_personal_salary_quick_period_btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const period = this.dataset.period;
                const now = new Date();
                let from = new Date();
                let to = new Date(now);
                switch(period) {
                    case 'day':
                        from = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                        break;
                    case 'week':
                        from = new Date(now);
                        from.setDate(now.getDate() - 7);
                        break;
                    case 'month':
                        from = new Date(now.getFullYear(), now.getMonth(), 1);
                        break;
                    case '3months':
                        from = new Date(now);
                        from.setMonth(now.getMonth() - 3);
                        break;
                    case 'alltime':
                        from = new Date(2000, 0, 1); // 1 января 2000
                        break;
                    default: return;
                }
                document.getElementById('print_date_from').value = from.toISOString().split('T')[0];
                document.getElementById('print_date_to').value = to.toISOString().split('T')[0];
            });
        });

        // Закрытие модалки при клике вне
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('carServicePersonalPrintModal');
            if (event.target === modal) {
                closePrintModal();
            }
        });
    }

    function generatePrint() {
        if (!printWorkerId) {
            alert('Ошибка: сотрудник не выбран');
            return;
        }
        const dateFrom = document.getElementById('print_date_from').value;
        const dateTo = document.getElementById('print_date_to').value;
        if (!dateFrom || !dateTo) {
            alert('Выберите диапазон дат');
            return;
        }
        if (dateFrom > dateTo) {
            alert('Дата начала не может быть позже даты окончания');
            return;
        }
        const hideDetails = document.getElementById('hide_details_checkbox').checked ? '1' : '0';
        // Открываем страницу печати в новом окне с параметром hide_details
        const url = `/content/shop/print_docs/print_salary.php?worker_id=${printWorkerId}&from=${dateFrom}&to=${dateTo}&hide_details=${hideDetails}`;
        window.open(url, '_blank');
        closePrintModal();
    }
</script>

<!-- Подключаем Font Awesome для иконок -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">  