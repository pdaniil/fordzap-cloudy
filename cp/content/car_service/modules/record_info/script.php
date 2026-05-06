<script>
	
	var url_request = '<?=$DP_Config->domain_path . $DP_Config->backend_dir;?>/content/car_service/ajax/ajax_sto_record_functions.php';
	var record_id = '<?=$record_sto_id > 0 ? $record_sto_id : "";?>';
	
	function postAjax(body_obj, show_message, callback_function = null) {
		let obj = new URLSearchParams(body_obj);
		obj = obj.toString();
		console.log(callback_function);
		jQuery.ajax({
				type: "POST",
				async: true, //Запрос асинх
				url: url_request,
				dataType: "json",//Тип возвращаемого значения
				data: obj,
				success: function(answer)
				{
					if (answer.status != 200)
					{
						console.log(answer.message);
						if (callback_function != null)
							window[callback_function](answer);
					}
					else 
					{
						if (callback_function != null)
							window[callback_function](answer);
						
						if (show_message)
							alert(answer.message);
					}
				}
			}); 
	}
	
	function getElement(selector) {
		return document.querySelector(selector);
	}
	
	function get_cars_info_by_user_id() { 
		//document.getElementById('user_car_mark_model').value = '';
		//document.getElementById('user_car_vin').value =  '';
		
		let user_id = '<?=$customer_id;?>';
		console.log(user_id); 
		postAjax({action: 'get_cars_info_by_user_id', user_id : user_id}, false, 'render_data_by_user_id');
	}
	
	
	function get_html_car_option(id, caption) {
		const option = document.createElement('option');
		option.value = id;
		option.textContent = caption;
		
		return option; 
	}
	
	var is_programm_select_change = false;
	
	function render_data_by_user_id(answer) {
		const cars_arr = answer.data.cars_info;
		const select = document.getElementById('user_car');
		
		let options_arr = [];
		
		if (cars_arr.length < 1) {
			options_arr.push(get_html_car_option(-1, 'Добавьте авто'));
		}else {
			options_arr.push(get_html_car_option(-1, 'Выберите авто'));
			
			cars_arr.forEach(el => {
				options_arr.push(get_html_car_option(el.id, el.caption));
			});
		}
		
		is_programm_select_change = true;
		
		select.innerHTML = '';
		options_arr.forEach(el => {
			select.append(el);
		});
		
		set_selected_car();
	}
	
	function set_selected_car(id_needed = 0) {
		if (id_needed > 0) {
			select.value = id_needed;
		}
		else {
			
			const car_id = '<?=$car_id?>';		
			if (car_id > 0) {
				const select = document.getElementById('user_car');
				select.value = car_id;
			}
		}
		
		is_programm_select_change = false;
		
		render_car_inputs();
	}
	
	function render_car_inputs() {
		const select = document.getElementById('user_car');
		const selected_car_id = select.value;
		
		postAjax({action: 'get_car_data_by_id', car_id : selected_car_id}, false, 'set_car_info_inputs');
	}
	
	function set_car_info_inputs(answer) {
		if (answer.data.id > 0) {
			document.querySelector('#user_car_model').value = answer.data.marka + ' ' + answer.data.model;
			document.querySelector('#user_car_vin').value = answer.data.vin || answer.data.frame;
		}
		else {
			document.querySelector('#user_car_model').value = '';
			document.querySelector('#user_car_vin').value = '';
		}
		
		if (!is_programm_select_change) {
			save_record();  
		}		
	}
	
    function save_record() {
		
		/*if (document.querySelector('#record_id').value < 1) {
			if (!confirm('Будет создана новая запись. Продолжить?')) {
				return;
			}
		}*/
		
		const sto_id = $('#sto_id').val();
		const post_id = $('#posts_select').val();
		const worker_id = $('#workers_select').val();
		const percent = $('#workers_select option:selected').data('percent');
		const time = document.querySelector('#time-record-display').textContent;
		const time_from = getElement('#time_from').value;
		const time_to = getElement('#time_to').value;
        const car_id = $('#user_car').val();
		const user_id = "<?=$customer_id;?>";
		const mileage = document.querySelector('#mileage_record').value;
        
		const prepayment = document.querySelector('#prepayment').value;
		
		const services_arr = getTableData();
		
        const orders_ids = "<?=$_GET["order_id"];?>";//document.querySelector('#orders_ids').value;

        let obj = {
            tables : {
				car_service_records : {
						id : document.querySelector('#record_id').value,
						sto_id : sto_id,
						worker_id : worker_id,
						post_id : post_id,
						time : time.trim(),
						time_from : time_from,
						time_to : time_to,
						user_id : user_id,
						car_id : car_id,
						prepayment : prepayment,
						orders_ids : orders_ids,
						mileage : mileage,
						worker_percent : percent
				},
				car_service_record_services : services_arr
			}
        };
		
        postAjax({action : 'save_record', json : JSON.stringify(obj)}, false, 'go_to_record_page');	
    }
	
	function go_to_record_page(answer) {
		if (answer.status != 200) {
			console.log(answer.message);
			return false;
		}
		else {
			document.querySelector('#record_id').value = answer.data.id;
		}
	}
	
	function getTableData() {
		let data = [];

		$('#table_services_wrapper table tbody tr').each(function () {
			let caption = $(this).find('td:nth-child(2)').text().trim();
			let price = parseFloat($(this).find('input.price').val()) || 0;
			let fix_price = parseFloat($(this).find('input.fix_price').val()) || 0;
			let hourly_rate = parseFloat($(this).find('input.hourly_rate').val()) || 0;
			let count = parseInt($(this).find('input.quantity').val()) || 0;
			let discount = parseInt($(this).find('input.discount').val()) || 0;
			let status_id = $(this).find('select.status').val() || 0;
			data.push({
				caption: caption,
				price: price,
				fix_price: fix_price,
				hourly_rate: hourly_rate,
				count: count,
				discount : discount,
				status_id : status_id
			});
		});

		return data;
	}
	
    function deleteRecordById(record_id) {
		if (canAddServices) {
			postAjax({action: 'delete_record', id : record_id}, false, 'go_to_list_page');
		}
		else {
			alert('Нельзя редактировать оплаченный заказ');
		}
    }
	
	function set_unview(record_id) {
		postAjax({action: 'set_unview', id : record_id}, false, 'go_to_list_page');
	}
	
	function go_to_list_page() {
		location = "<?=$DP_Config->domain_path . $DP_Config->backend_dir;?>/car_service/sto_list_records";
	}
	
	function get_data_by_sto_id() {
		const sto_id = $('#sto_id').val();
		const order_id = '<?=$_GET["order_id"];?>';
		postAjax({action: 'get_data_by_sto_id', id : sto_id, order_id : order_id}, false, 'render_data_by_sto_id');  
	}
	
	
	function render_data_by_sto_id(answer) {
		
		const tree_lists_services_arr = answer.data.tree_lists_services_arr;
		console.log(tree_lists_services_arr);
		let $services = $('#services_select');
		$services.empty();
		$services.append(new Option("Добавьте в таблицу", "-1", false, false));
		tree_lists_services_arr.forEach(item => {
			const option = new Option(item.value, item.id, false, false);
			
			// Добавляем data-атрибут с ценой
			// Если fix_price > 0, используем значение fix_price, иначе 0
			const priceValue = parseFloat(item.fix_price) > 0 ? item.fix_price : 0;
			option.setAttribute('data-price', priceValue);
			
			$services.append(option);
		});
		//$services.trigger('change');
		document.querySelector('#hourly_rate_price').value = answer.data.hourly_rate;
		document.querySelector('#record_id').value = answer.data.record_id;
		
		//updateTimeRange('time_from', answer.data.time_from + ":00", answer.data.time_to + ":00");
		//updateTimeRange('time_to', answer.data.time_from + ":00", answer.data.time_to + ":00");
	}
	  
	function generate_table_by_services_select() {
		
		if (!canAddServices) {
			$('#services_select').val('-1');
			alert('Нельзя редактировать оплаченный заказ.');
			return false;
		}
		
		let id = $('#services_select').val();
		let sto_id = $('#sto_id').val();
		
		if (id === "-1") return; // не добавляем placeholder
		
		postAjax({ action: 'generate_table_by_services_select', service_id: id, sto_id: sto_id }, false, 'render_data_services');
	}

	function render_data_services(answer) { 
		if (answer.status === 200) {
			$('#table_services_wrapper table tbody').append(answer.data.html);

			// Сброс select после добавления
			$('#services_select').val('-1').trigger('change');
			// Обновляем итоговую строку
			updateTotals();
		}
	}
	
	
	function calcTotalSumRowDiscount(row) {
		console.log(row);
		
		const hourly_rate = parseFloat(row.querySelector('.hourly_rate').value) || 0;
		const quantity = parseInt(row.querySelector('.quantity').value) || 0;
		const hourly_rate_price = parseFloat(document.querySelector("#hourly_rate_price").value) || 0;
		
		const price = hourly_rate * hourly_rate_price;
		row.querySelector('.price').value = price.toFixed(2);
		
		const discount = parseFloat(row.querySelector('.discount').value) || 0;
		
		let total_price = price * quantity;
		let total_price_discount = total_price - (total_price * (discount / 100));
		
		row.querySelector('.total').value = total_price_discount.toFixed(2);
	}

	function procDiscountServices() {
		const discount_value = document.querySelector('#positions-discount').value;
		
		document.querySelectorAll('.service_one_check:checked').forEach(el => {
			const row = el.closest('tr');
			const discountInput = row.querySelector('.discount');
			discountInput.value = discount_value;
			
			calcTotalSumRowDiscount(row);
		});
		updateTotals();
		save_record();
	}

	function handleInputChange(event) {
		// Если canAddServices === false, отменяем изменения
		if (typeof canAddServices !== 'undefined' && canAddServices === false && event.target.type !== 'checkbox') {
			// Восстанавливаем предыдущее значение
			if (event.target.type === 'checkbox') {
				event.target.checked = event.target.getAttribute('data-prev-checked') === 'true' || false;
			} else {
				event.target.value = event.target.getAttribute('data-prev-value') || event.target.defaultValue;
			}
			
			// Показываем алерт только если еще не показывали
			if (!isAlertShown) {
				alert('Нельзя редактировать оплаченный заказ');
				isAlertShown = true;
				
				// Сбрасываем флаг через короткое время
				setTimeout(() => {
					isAlertShown = false;
				}, 100);
			}
			
			event.preventDefault();
			event.stopPropagation();
			return false;
		}
	}


	// Функция для сохранения текущего значения перед изменением
	function saveCurrentValue(event) {
		if (event.target.type === 'checkbox') {
			event.target.setAttribute('data-prev-checked', event.target.checked);
		} else {
			event.target.setAttribute('data-prev-value', event.target.value);
		}
	}


	function addEventListenersToElements(elements) {
		elements.forEach(element => {
			// Сохраняем текущее значение перед изменением
			element.addEventListener('focus', saveCurrentValue);
			element.addEventListener('mousedown', saveCurrentValue);
			
			// Для чекбоксов используем только change событие
			if (element.type === 'checkbox') {
				element.addEventListener('change', handleInputChange);
			} else {
				// Для input используем и change и input
				element.addEventListener('change', handleInputChange);
				element.addEventListener('input', handleInputChange);
			}
			
			// Инициализируем сохраненные значения
			if (element.type === 'checkbox') {
				element.setAttribute('data-prev-checked', element.checked);
			} else {
				element.setAttribute('data-prev-value', element.value);
			}
		});
	}
	
	// Удаление строки
	$(document).on('click', '.btn-remove-row', function () {
		if (canAddServices) {
			$(this).closest('tr').remove();
			updateTotals();
		}
		else {
			alert('Нельзя редактировать оплаченный заказ');
		}
	});

	// Пересчёт "Итого" для строки при изменении цены или количества
	$(document).on('change', '.hourly_rate,.quantity,.status', function () {  
		const row = $(this).closest('tr');
		const hourly_rate = parseFloat(row.find('.hourly_rate').val()) || 0;
		const quantity = parseInt(row.find('.quantity').val()) || 0;
		const hourly_rate_price = document.querySelector("#hourly_rate_price").value;
		row.find('.price').val((hourly_rate * hourly_rate_price).toFixed(2));
		const price = (hourly_rate * hourly_rate_price).toFixed(2);
		
		const discount = parseInt(row.find('.discount').val()) || 0;
		
		let total_price = price * quantity;
		let total_price_dicsount = total_price - (total_price * (discount / 100));
		
		row.find('.total').val((total_price_dicsount).toFixed(2));
		updateTotals();
	});

	// Обновление общей суммы и количества
	function updateTotals() {
		let totalQuantity = 0;
		let totalSum = 0;
		let totalRate = 0;
		
		$('#table_services_wrapper table tbody tr').each(function () {
			const qty = parseInt($(this).find('.quantity').val()) || 0;
			const rate = parseFloat($(this).find('.hourly_rate').val()) || 0;
			const sum = parseFloat($(this).find('.total').val()) || 0;
			totalQuantity += qty;
			totalRate += rate;
			totalSum += sum;
		});
		
		add_sum_to_pay_value(totalSum);
		
		$('#total_quantity').text(totalQuantity);
		$('#total_hourly_rate').text(totalRate.toFixed(2));
		$('#total_sum').text(totalSum.toFixed(2));
		if (!calculateTimeTo()) {
			//Автосохранение
			save_record();
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	 
	let currentDate = new Date();
	let selectedDateTime = '<?= isset($selectedDateTime) ? $selectedDateTime : '' ?>';
	let selectedDay = null; // глобально
	var display = document.getElementById('time-record-display');
	var overlay = document.getElementById('calendar-overlay');
	var slotsHeader = document.getElementById('slots-header');
	var slotsList = document.getElementById('slots-list');

	function formatDateLocal(d) {
	  const y = d.getFullYear();
	  const m = String(d.getMonth() + 1).padStart(2, '0');
	  const day = String(d.getDate()).padStart(2, '0');
	  return `${y}-${m}-${day}`;
	}

	function getCalendarStart(year, month) {
	  const first = new Date(year, month, 1);
	  const dow = (first.getDay() + 6) % 7; // Понедельник = 0
	  const start = new Date(first);
	  start.setDate(first.getDate() - dow);
	  start.setHours(0,0,0,0);
	  return start;
	}
	
	function setServicesComplete() {
		document.querySelectorAll('select[name="service_status[]"]').forEach(select => {
			select.value = '21';
		});
	}
	
	function setTimeToByOrderStatusChange() {
		// Получаем текущее время
		var now = new Date();
		var nowTimestamp = Math.floor(now.getTime() / 1000);
		
		// Получаем значение из time_from
		var timeFromInput = document.getElementById("time_from");
		var timeFromValue = parseInt(timeFromInput.value) || 0;
		
		// Определяем начальное значение: максимальное из текущего времени и time_from + 30 минут
		var minTimeFromValue = timeFromValue + (30 * 60); // Добавляем 30 минут в секундах
		var finalTimestamp = Math.max(nowTimestamp, minTimeFromValue);
		
		// Округляем до ближайших больших 30 минут (вверх)
		var dateObj = new Date(finalTimestamp * 1000);
		var minutes = dateObj.getMinutes();
		
		// Округление минут вверх до ближайших 30
		var roundedMinutes;
		if (minutes % 30 === 0) {
			// Если минуты уже кратны 30, оставляем как есть
			roundedMinutes = minutes;
		} else {
			// Иначе округляем вверх до следующего кратного 30
			roundedMinutes = Math.ceil(minutes / 30) * 30;
		}
		
		// Обрабатываем случай, когда получилось 60 минут
		if (roundedMinutes === 60) {
			dateObj.setHours(dateObj.getHours() + 1);
			dateObj.setMinutes(0);
		} else {
			dateObj.setMinutes(roundedMinutes);
		}
		
		// Обнуляем секунды и миллисекунды
		dateObj.setSeconds(0);
		dateObj.setMilliseconds(0);
		
		// Получаем timestamp после округления
		finalTimestamp = Math.floor(dateObj.getTime() / 1000);
		
		// Дополнительная проверка: убеждаемся, что время не равно time_from
		// и как минимум на 30 минут больше
		if (finalTimestamp <= timeFromValue) {
			// Если после округления стало меньше или равно time_from,
			// добавляем 30 минут к time_from
			finalTimestamp = timeFromValue + (30 * 60);
			dateObj = new Date(finalTimestamp * 1000);
			
			// Еще раз округляем до 30 минут
			var newMinutes = dateObj.getMinutes();
			if (newMinutes % 30 !== 0) {
				var newRoundedMinutes = Math.ceil(newMinutes / 30) * 30;
				if (newRoundedMinutes === 60) {
					dateObj.setHours(dateObj.getHours() + 1);
					dateObj.setMinutes(0);
				} else {
					dateObj.setMinutes(newRoundedMinutes);
				}
				dateObj.setSeconds(0);
				dateObj.setMilliseconds(0);
				finalTimestamp = Math.floor(dateObj.getTime() / 1000);
			}
		}
		
		// Устанавливаем значение в скрытое поле
		document.getElementById("time_to").value = finalTimestamp;
		
		// Форматируем дату для отображения
		var day = dateObj.getDate();
		var month = dateObj.getMonth() + 1;
		var year = dateObj.getFullYear();
		var hours = dateObj.getHours();
		var minutes = dateObj.getMinutes();
		
		// Собираем строку в формате DD.MM.YYYY HH:MM
		var time_string = day + "." + 
						  month + "." + 
						  year + " " + 
						  hours + ":" + 
						  (minutes < 10 ? "0" : "") + minutes;
		
		// Устанавливаем значение в поле отображения
		document.getElementById("time_to_show").value = time_string;
		
		// Если используется datetimepicker, обновляем его значение
		if (typeof jQuery !== 'undefined' && jQuery.fn.datetimepicker) {
			var $timeToPicker = jQuery("#time_to");
			if ($timeToPicker.data('xdsoft_datetimepicker')) {
				$timeToPicker.datetimepicker({ value: dateObj });
			}
		}
		
		console.log("time_to установлено на:", time_string, "(timestamp:", finalTimestamp + ")");
		console.log("time_from:", new Date(timeFromValue * 1000).toLocaleString(), "(timestamp:", timeFromValue + ")");
		console.log("Разница:", (finalTimestamp - timeFromValue) / 60, "минут");
		
		save_record();
	}
	
	function isWorkingDay(dow, sto_id) {
		// Проверяем, есть ли данные о рабочих днях для этой СТО
		let workingDays = [];
		
		// operating_mode[sto_id] - это уже массив рабочих дней, например [1,2,3,4,5]
		if (operating_mode && operating_mode[sto_id] && Array.isArray(operating_mode[sto_id])) {
			workingDays = operating_mode[sto_id];
		}
		
		// Если для этой СТО нет данных о рабочих днях, используем стандартную неделю (1-5 рабочие)
		if (workingDays.length === 0) {
			//return dow >= 1 && dow <= 5; // Понедельник(1) - Пятница(5)
		}
		
		// Проверяем, есть ли текущий день недели в массиве рабочих дней
		// dow у Date.getDay(): 0=воскресенье, 1=понедельник, ..., 6=суббота
		// В ваших данных: 1=понедельник, ..., 7=воскресенье
		let dayInDataFormat;
		if (dow === 0) {
			dayInDataFormat = 7; // Воскресенье
		} else {
			dayInDataFormat = dow; // Понедельник-Суббота
		}
		
		return workingDays.includes(dayInDataFormat.toString());
	}
	
	function renderCalendar() {
	  
	  
	  const monthName = currentDate.toLocaleString('ru-RU', { month: 'long', year: 'numeric' });
	  document.getElementById('calendar-month').textContent = monthName;

	  const grid = document.querySelector('.calendar-grid');
	  grid.innerHTML = '';

	  const year = currentDate.getFullYear();
	  const month = currentDate.getMonth();

	  const start = getCalendarStart(year, month);
	  const today = new Date(); today.setHours(0,0,0,0);
	  const sto_id = document.querySelector('#sto_id').value;
	  
	  for (let i = 0; i < 42; i++) {
		const cellDate = new Date(start);
		cellDate.setDate(start.getDate() + i);

		const div = document.createElement('div');
		div.textContent = cellDate.getDate();

		if (cellDate.getMonth() !== month) {
		  div.classList.add('disabled');
		} else {
		  const dow = cellDate.getDay();
		  let sto_id = document.querySelector('#sto_id').value;
		  const isWeekend = (dow === 0 || dow === 6); // вот это не правильно
		  const isToday = cellDate.getTime() === today.getTime();
		  const isFuture = cellDate.getTime() > today.getTime();
		  
		  // Подсветка сегодняшней даты
		  if (isToday) {
			div.classList.add('today');
		  }

		  // Подсветка выбранной даты
		  if (selectedDay && cellDate.getTime() === selectedDay.getTime()) {
			div.classList.add('selected');
		  }

		  // Доступные для выбора даты: сегодня ИЛИ будущие даты, не выходные
		  if ((isToday || isFuture) && isWorkingDay(dow, sto_id)) {
			div.classList.add('available');
			const dayCopy = new Date(cellDate);
			dayCopy4 = dayCopy;
			div.addEventListener('click', () => selectDate(dayCopy));
		  } else {
			div.classList.add('disabled');
		  }
		}
		grid.appendChild(div);
	  }
	}  
	
	var dayCopy4 = null;
	
	function selectDate(day) {
	  selectedDay = new Date(day);
	  const formattedDate = formatDateLocal(day);
	  let sto_id = $('#sto_id').val();
	  let post_id = $('#posts_select').val();
	  slotsHeader.textContent = `Доступное время: ${formattedDate}`;
	  slotsList.innerHTML = '<div>Загрузка...</div>';
	  renderCalendar(); // перерисовываем с подсветкой выбранного дня

	  postAjax(
		{ action: 'get_slots_by_date', sto_id: sto_id, post_id : post_id, date: formattedDate },  
		false,
		'renderSlots'
	  );
	}
	
	function renderSlots(answer) {
	  const slots = answer.data.slots;
	  slotsList.innerHTML = '';
	  if (slots.length === 0) {
		slotsList.innerHTML = '<div>Нет свободных слотов</div>';
	  } else {
		slots.forEach(slot => {
		  const btn = document.createElement('button');
		  btn.className = 'btn-slot';
		  btn.textContent = slot;
		  btn.addEventListener('click', () => {
			selectedDateTime = `${slotsHeader.textContent.replace('Доступное время: ','')} ${slot}`;
			display.textContent = selectedDateTime;
			setTimeFromOfSlot(selectedDateTime);
			closeCalendar();
			//Автосохранение
			save_record();
		  });
		  slotsList.appendChild(btn);
		});
	  }
	}
	
	function goToRecordPage_2() {
		let record_id = document.querySelector('#record_id').value;
		if (record_id > 0) {
			location = "<?=$DP_Config->domain_path;?>cp/car_service/sto_record?id=" + record_id;
		}
		else {
			alert('Укажите данные записи.');
		}
	}
	
	function setTimeFromOfSlot(slot_text_value) {
		console.log(slot_text_value); 
		
		// Преобразуем строку в объект Date
		const dateObj = new Date(slot_text_value.replace(' ', 'T'));
		
		// Устанавливаем значение напрямую через datetimepicker
		jQuery('#time_from').datetimepicker({
			value: dateObj,
			onGenerate: function() {
				// Форматируем дату для отображения
				const formattedDate = dateObj.getDate() + "." + 
									 (dateObj.getMonth() + 1) + "." + 
									 dateObj.getFullYear() + " " + 
									 dateObj.getHours() + ":" + 
									 (dateObj.getMinutes() < 10 ? '0' : '') + dateObj.getMinutes();
				
				document.getElementById('time_from_show').value = formattedDate;
				calculateTimeTo();
			}    
		});
			
	}
	
	
	function calculateTimeTo() {
		if (!canChangeTimeTo)
			return;
		const timeFromValue = document.getElementById('time_from').value;
		const totalQnt = parseFloat(document.getElementById('total_quantity').textContent);
		const totalHours = parseFloat(document.getElementById('total_hourly_rate').textContent) * totalQnt;
		
		if (timeFromValue && totalHours) {
			// Преобразуем Unix timestamp в объект Date
			const timeFrom = new Date(parseInt(timeFromValue) * 1000);
			
			// Добавляем часы
			const timeTo = new Date(timeFrom.getTime() + (totalHours * 60 * 60 * 1000));
			
			// Преобразуем в Unix timestamp
			const unixTimestamp = Math.floor(timeTo.getTime() / 1000);
			
			// Устанавливаем значение в скрытое поле
			document.getElementById('time_to').value = unixTimestamp;
			
			// Форматируем для отображения
			const formattedDate = timeTo.getDate() + "." + 
								(timeTo.getMonth() + 1) + "." + 
								timeTo.getFullYear() + " " + 
								timeTo.getHours() + ":" + 
								(timeTo.getMinutes() < 10 ? '0' : '') + timeTo.getMinutes();
			
			document.getElementById('time_to_show').value = formattedDate;
			
			// Обновляем datetimepicker
			jQuery('#time_to').datetimepicker('update');
			save_record();
			return true;
		}
		
		return false;
	}
	
	// Функция для изменения времени динамически
	function updateTimeRange(id, newStartTime, newEndTime) {
		console.log(id);
		console.log('update_time_range');
		jQuery("#" + id).datetimepicker('destroy');
		jQuery("#" + id).datetimepicker({
			lang:"ru",
			closeOnDateSelect:true,
			closeOnTimeSelect:false,
			dayOfWeekStart:1,
			format:'unixtime',
			step: 30,
			startTime: '09:00',
			endTime: newEndTime,
			onClose:function(current_time, input)
			{
				var time_string = "";
				var date_ob = new Date(current_time);
				time_string += date_ob.getDate()+".";
				time_string += (date_ob.getMonth() + 1)+".";
				time_string += date_ob.getFullYear()+" ";
				time_string += date_ob.getHours()+":"+date_ob.getMinutes();
				document.getElementById(id + "_show").value = time_string;
			},
			onGenerate:function(current_time, input)
			{
				var time_string = "";
				var date_ob = new Date(current_time);
				time_string += date_ob.getDate()+".";
				time_string += (date_ob.getMonth() + 1)+".";
				time_string += date_ob.getFullYear()+" ";
				time_string += date_ob.getHours()+":"+date_ob.getMinutes();
				document.getElementById(id + "_show").value = time_string;
			}
		});
	}
	
	function openCalendar() {
	  overlay.style.display = 'flex';
	  renderCalendar();
	}

	function closeCalendar() {
	  overlay.style.display = 'none';
	}
	
	function reload_page() {
		console.log(123);
		location = location;
	}
	
	var orig_pay_value = 0;
	
	function add_sum_to_pay_value (sum) {
		
		if (document.querySelector('#pay_value') != null) {
			document.querySelector('#pay_value').value = parseFloat(sum) + parseFloat(orig_pay_value);
			document.querySelector('#order_sum_text').textContent = parseFloat(sum) + parseFloat(orig_pay_value);
			document.querySelector('#order_paid_left_text').textContent = (parseFloat(sum) + parseFloat(orig_pay_value)) - document.querySelector('#order_paid_sum_text').textContent;
		}
		else if (!canAddServices) {
			let debtElement = document.querySelector('#span_total_debt');
			if (debtElement) {
				// Убираем все пробелы из текста перед парсингом
				let currentDebt = parseFloat(debtElement.textContent.replace(/\s/g, ''));
				let newDebt = currentDebt + parseFloat(sum);
				
				// Форматируем с пробелами для тысяч (опционально)
				//debtElement.textContent = newDebt.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
			}
		}
	}
	
	// Находим все нужные элементы
	var hourlyRateInputs = null;//document.querySelectorAll('input.hourly_rate[name^="service_hourly_rate"]');
	var quantityInputs = null;//document.querySelectorAll('input.quantity[name^="service_quantity"]');
	var checkboxes = null;//document.querySelectorAll('input.service_one_check');
	let isAlertShown = false;
	
	window.onload = function () {
		
		// Находим все нужные элементы
		hourlyRateInputs = document.querySelectorAll('input.hourly_rate[name^="service_hourly_rate"]');
		quantityInputs = document.querySelectorAll('input.quantity[name^="service_quantity"]');
		checkboxes = document.querySelectorAll('input.service_one_check');
		
		addEventListenersToElements(hourlyRateInputs);
		addEventListenersToElements(quantityInputs);
		addEventListenersToElements(checkboxes);  
		
		if (document.querySelector('#pay_value') != null)
			orig_pay_value = document.querySelector('#pay_value').value;
		else
			orig_pay_value = 0;
				
		$(document).on('change', '#services_select', function(event) {
			generate_table_by_services_select();
		});
		
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
		
		document.querySelector('#user_car').addEventListener('change', () => {
			console.log(is_programm_select_change);
			render_car_inputs();
		});
		
		get_cars_info_by_user_id();
		
		document.querySelector('#check_all_services').addEventListener('change', function() {
			const checked_val = this.checked;
			console.log(checked_val);
			document.querySelectorAll('.service_one_check').forEach(el => {
				el.checked = checked_val;
			});
		});
		
		updateTotals();
	}
	
	
	
	
</script>
