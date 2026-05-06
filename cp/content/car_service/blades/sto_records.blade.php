<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Записи для <?=$sto_data_res['caption'];?>
		</div>
		<div class="panel-body scrollable">
			<div class="docpart_calendar" id="docpart_calendar">
				<div class="docpart_calendar__toolbar_header">
					<div class="dates">
						<div class="switcher_date_wrapper">
							<div class="switch_prev_date"><i class="fas fa-chevron-left"></i></div>
							<div class="curr_date" id="curr_date"></div>
							<div class="switch_next_date"><i class="fas fa-chevron-right"></i></div>
						</div>
					</div>
					<div class="switch_mode">
						<div class="switch_to_day" onclick="DocpartCalendar.changeMode('day')">День</div>
						<div class="switch_to_week" onclick="DocpartCalendar.changeMode('week')">Неделя</div>
					</div>
				</div>
				<div class="docpart_calendar__main_content">
					<div class="calendar_body">
						<div class="header_content">
							<div class="week_mode" id="header_week_mode">
								<div class="post_types_switcher"></div>
								<div class="week_days_wrapper">
									<div class="empty_day"></div>
									<div class="week_days"></div>
								</div>
							</div>
							<div class="day_mode">
								<div id="header_day_mode">
									<div class="empty_day"></div>
									<div id="post_types"></div>
								</div>
							</div>
						</div>
						<div class="calendar_grid">
							<div class="week_mode">
								<div class="days_greed" id="days_greed_week_mode">
								</div>
							</div>
							<div class="day_mode">
								<div class="days_greed" id="days_greed_day_mode">
									<div class="docpart_col" id="">
										<div class="docpart_cell" id="day_cell_id"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="docpart_calendar__toolbar_footer">

				</div>
			</div>
		</div>
	</div>
</div>
<!--
Если вдруг понадобится модалка
<div class="modal_record_info hidden">
	<div class="modal_record_content">
		<div class="modal_record_title" id="time_value">

		</div>
		<div class="table-responsive">
			<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
				<tr>
					<th>Автомобиль</th>
					<td id="car_value"></td>
				</tr>
				<tr>
					<th>Услуги</th>
					<td id="services_value"></td>
				</tr>
				<tr>
					<th>Пост</th>
					<td id="post_value"></td>
				</tr>
				<tr>
					<th>Сотрудник</th>
					<td id="worker_fio"></td>
				</tr>
				<tr>
					<th>Детали</th>
					<td><a id="link_order" href=""></a></td>
				</tr>
			</table>
		</div>
		<div class="modal_record_footer">
			<button class="btn btn-danger" onclick="CalendarHtmlHandler.getEl('.modal_record_info').classList.add('hidden');" id="close_modal_btn">Закрыть</button>
			<button class="btn btn-success" id="update_record">Редактировать</button>
		</div>
	</div>
</div>
-->
<script>
</script>
<script src="./../../content/car_service/assets/DocpartCalendar/CalendarCell.js"></script>
<script src="./../../content/car_service/assets/DocpartCalendar/Calendar.js"></script>
<script src="./../../content/car_service/assets/DocpartCalendar/CalendarHtmlHandler.js"></script>
<script src="./../../content/car_service/assets/DocpartCalendar/main.js"></script>

