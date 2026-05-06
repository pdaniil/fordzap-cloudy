<?php
header('Content-Type: text/html; charset=utf-8');

include($_SERVER["DOCUMENT_ROOT"] . "/content/carservice/prepare_info.php");
include($_SERVER["DOCUMENT_ROOT"] . "/content/carservice/prepare_user_info.php");
include($_SERVER["DOCUMENT_ROOT"] . "/content/carservice/css.php");

?>
<div class="container">
  
    <!-- ===================== ВЫБОР АВТОСЕРВИСА + КАРТА ===================== -->
    <div class="card section">
        <div class="grid">
            <div>
                <h3 style="margin-bottom:12px">Выберите автосервис</h3>
                <div id="service-radios">
                    <?php foreach ($car_services_arr as $idx => $srv): ?>
                        <?php
                        $id   = (int)$srv["id"];
                        $name = $srv["caption"];
                        $sched= formatSchedule($srv);
                        $addr = htmlspecialchars($srv["address"] ?? "");
                        $lat  = explode(",",$srv["coords"])[0];
                        $lng  = explode(",",$srv["coords"])[1];
                        ?>
                        <label class="service-radio">
                            <input type="radio" name="car_service"
                                   value="<?= $id ?>"
                                   data-id="<?= $id ?>"
                                   data-lat="<?= $lat ?>"
                                   data-lng="<?= $lng ?>"
                                   <?= $idx===0 ? 'checked' : '' ?>>
                            <div class="service-meta">
                                <div class="name"><?= $name ?></div>
                                <?php if ($sched): ?><div class="schedule"><?= $sched ?></div><?php endif; ?>
                                <?php if ($addr): ?><div class="address"><?= $addr ?></div><?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <div class="map-wrap"><div id="yandex-map"></div></div>
            </div>
        </div>
    </div>
	<div class="sep_wrappers">
		<div class="card section left">
			<div class="block-title">
				<h3>Выберите время</h3>
			</div>
			<div class="time-box">					
				<div class="time-record-container">
				  <div id="time-record-display" class="time-record-display">
					<?= !empty($sto_record_data["time"]) ? $sto_record_data["time_formatted"] : "Выберите дату и время";?>
				  </div>
				</div>
			</div>
			 <div class="confirm-button-wrapper">
				<button class="btn-confirm" onclick="save_record()">Подтвердить запись</button>
			</div>
		</div>
		
		<div class="card section right">
			<div class="block-title">
				<h3>Заказчик и авто</h3>
			</div>
			<div class="user_wrapper">
				<div class="user_info">
					<input type="hidden" id="user_id" value="<?=empty($user_info_arr["user_id"]) ? "" : $user_info_arr["user_id"]?>">
					<input type="text" placeholder="Email" class="form-control" id="email" value="<?=empty($user_info_arr["email"]) ? "" : $user_info_arr["email"]?>">
					<input type="text" placeholder="Телефон" class="form-control" id="phone" value="<?=empty($user_info_arr["phone"]) ? "" : $user_info_arr["phone"]?>">
					<input type="text" placeholder="ФИО" class="form-control" id="fio" value="<?=empty($user_info_arr["fio"]) ? "" : $user_info_arr["fio"]?>">
				</div> 
				<div class="car_info">
					<?php if (!empty($user_info_arr["cars"])) : ?>
						<select class="form-control" id="car_id" onchange="set_selected_data_cars();">
							<?php foreach ($user_info_arr["cars"] as $car) : ?>
								<option
								data-id="<?=$car["id"]?>"
								data-caption="<?=$car["caption"]?>"
								data-model="<?=$car["model"]?>"
								data-year="<?=$car["year"]?>"
								data-vin="<?=empty($car["vin"]) ? $car["frame"] : $car["vin"];?>"
								value="<?=$car["id"]?>"><?=$car["caption"]?></option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>  
					
					<input type="text" placeholder="Модель" class="form-control" id="car_model" value="">
					<input type="text" placeholder="Год" class="form-control" id="car_year" value="">
					<input type="text" placeholder="VIN/FRAME" class="form-control" id="car_vin" value="">
					<input type="text" placeholder="Гос номер" class="form-control" id="car_gos_num" value="">
					<input type="text" placeholder="Пробег" class="form-control" id="car_mileage" value="">
					
				</div>
			</div>
		</div>
	</div>
	
	
 

    <!-- ===================== БЛОКИ ПО КАЖДОМУ СТО ===================== -->
    <?php foreach ($services_arr as $sto_id => $parents): ?>
        <div class="card section sto-block <?= $sto_id===$firstStoId ? 'active' : '' ?>" id="sto_<?= (int)$sto_id ?>">
            <!-- Время -->

            <hr class="sep">

            <!-- Выбранные услуги -->
            <div class="block-title">
                <h3>Выбранные услуги</h3>
            </div>
            <div class="selected-table" id="selected_wrap_<?= (int)$sto_id ?>">
                <table>
                    <thead>
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Услуга</th>
                            <th>Описание</th>
                            <th style="width:160px;">Цена</th>
                        </tr>
                    </thead>
                    <tbody id="selected_tbody_<?= (int)$sto_id ?>">
                        <!-- динамически -->
                    </tbody>
                </table>
                <div class="total">
                    <span class="muted">Итого от:</span>
                    <span class="sum" id="total_sum_<?= (int)$sto_id ?>">0 ₽</span>
                </div>
            </div>

            <hr class="sep">

            <!-- Рекомендуем добавить -->
            <div class="block-title">
                <h3>Рекомендуем добавить</h3>
                <div class="hint"></div>
            </div>
            <div class="reco-list" id="reco_list_<?= (int)$sto_id ?>">
                <?php foreach ($parents as $parent_id => $parent): ?>
                    <?php foreach ($parent["list_services"] as $sid => $svc):
                        if ((int)$svc["recommendation_block"] !== 1) continue;
                        $caption = htmlspecialchars($svc["caption"]);
                        $desc    = htmlspecialchars($svc["description"]);
                        $price   = $svc["price"];
                        $wp      = (int)$svc["worker_price"] === 1;
                        $stickers_html = render_stickers_html($svc["stickers"], $stickers_map);
                        $price_text = $wp || !($price > 0) ? 'Цену рассчитает мастер' : (number_format($price, 0, ',', ' ').' ₽');
                    ?>
                        <label class="reco-item">
                            <input type="checkbox"
                                   class="reco-check"
                                   data-sto="<?= (int)$sto_id ?>"
                                   data-id="<?= (int)$sid ?>"
                                   data-caption="<?= htmlspecialchars($svc["caption"], ENT_QUOTES) ?>"
                                   data-desc="<?= htmlspecialchars($svc["description"], ENT_QUOTES) ?>"
                                   data-price="<?= htmlspecialchars((string)$svc["price"], ENT_QUOTES) ?>"
                                   data-wp="<?= (int)$svc["worker_price"] ?>">
                            <div style="flex:1">
                                <div style="display:flex; gap:8px; align-items:center">
                                    <strong><?= $caption ?></strong>
                                    <?= $stickers_html ?>
                                </div>
                                <?php if ($desc): ?>
                                    <div class="muted" style="margin-top:4px"><?= $desc ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="price-badge"><?= $price_text ?></div>
                        </label>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <div class="actions" style="margin-top:12px">
                <button class="btn primary add-service-btn" data-sto="<?= (int)$sto_id ?>">Добавить услугу</button>
            </div>

            <!-- Таблица услуг с поиском -->
            <div id="services_table_wrap_<?= (int)$sto_id ?>" class="services-table" style="margin-top:12px; display:none">
                <div class="search">
                    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zM9.5 14C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input type="text" class="service-search" data-sto="<?= (int)$sto_id ?>" placeholder="Поиск услуг...">
                </div>

                <?php foreach ($parents as $parent_id => $parent): ?>
                    <div class="group" data-group="<?= (int)$parent_id ?>">
                        <div class="group-title"><?= htmlspecialchars($parent["caption"]) ?></div>
                        <div class="group-grid">
                            <?php foreach ($parent["list_services"] as $sid => $svc): ?>
                                <?php
                                $caption = htmlspecialchars($svc["caption"]);
                                $desc    = htmlspecialchars($svc["description"]);
                                $price   = $svc["price"];
                                $wp      = (int)$svc["worker_price"] === 1;
                                $active  = (int)$svc["active"] === 1;
                                $stickers_html = render_stickers_html($svc["stickers"], $stickers_map);
                                $price_text = $wp || !($price > 0) ? 'Цену рассчитает мастер' : (number_format($price, 0, ',', ' ').' ₽');
                                ?>
                                <?php if ($active): ?>
                                    <label class="service-item" data-sto="<?= (int)$sto_id ?>" data-name="<?= mb_strtolower($caption) ?>">
                                        <input type="checkbox"
                                               class="svc-check"
                                               data-sto="<?= (int)$sto_id ?>"
                                               data-id="<?= (int)$sid ?>"
                                               data-caption="<?= htmlspecialchars($svc["caption"], ENT_QUOTES) ?>"
                                               data-desc="<?= htmlspecialchars($svc["description"], ENT_QUOTES) ?>"
                                               data-price="<?= htmlspecialchars((string)$svc["price"], ENT_QUOTES) ?>"
                                               data-wp="<?= (int)$svc["worker_price"] ?>">
                                        <div style="flex:1">
                                            <div style="display:flex; gap:8px; align-items:center">
                                                <span class="service-name"><?= $caption ?></span>
                                                <?= $stickers_html ?>
                                            </div>
                                            <?php if ($desc): ?>
                                                <div class="muted" style="margin-top:4px"><?= $desc ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="price-badge"><?= $price_text ?></div>
                                    </label>
                                <?php else: ?>
                                    <div class="service-item inactive" data-name="<?= mb_strtolower($caption) ?>">
                                        <div style="flex:1">
                                            <div style="display:flex; gap:8px; align-items:center">
                                                <span class="service-name"><?= $caption ?></span>
                                            </div>
                                            <?php if ($desc): ?>
                                                <div class="muted" style="margin-top:4px"><?= $desc ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="price-badge"><span class="badge-na">Недоступно</span></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    <?php endforeach; ?>

</div>


<?php 

include($_SERVER["DOCUMENT_ROOT"] . "/content/carservice/script_from_front.php");
include($_SERVER["DOCUMENT_ROOT"] . "/content/carservice/script_from_back.php");
include($_SERVER["DOCUMENT_ROOT"] . "/content/carservice/script_select_car.php");

?>

<script>
window.onload = function () {
	display = document.getElementById('time-record-display');
	overlay = document.getElementById('calendar-overlay');
	slotsHeader = document.getElementById('slots-header');
	slotsList = document.getElementById('slots-list');
	
	document.getElementById('prev-month').addEventListener('click', () => {
	  currentDate.setMonth(currentDate.getMonth() - 1);
	  renderCalendar();
	});
	document.getElementById('next-month').addEventListener('click', () => {
	  currentDate.setMonth(currentDate.getMonth() + 1);
	  renderCalendar();
	});
	
	display.addEventListener('click', openCalendar);
	overlay.addEventListener('click', e => {
	  if (e.target === overlay) closeCalendar();
	});

	if (selectedDateTime) {
	  display.textContent = selectedDateTime;
	}
	renderCalendar();
	
	get_data_by_sto_id();
	set_selected_data_cars();
}
</script>