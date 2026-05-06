<?php ?>
<script>  
	function save_record() {
		const id = "<?= $_GET['id'] > 0 ? (int)$_GET['id'] : '' ?>";

		// ✅ Выбранный автосервис (radio)
		const sto_id = document.querySelector('input[name="car_service"]:checked')?.value || '';

		// ✅ Выбранное время
		const time = document.querySelector('#time-record-display').textContent.trim();
		const time_from = document.querySelector('#time_from')?.value || '';
		const time_to = document.querySelector('#time_to')?.value || '';

		// ✅ Данные пользователя
		const user_json = {
			user_id: document.querySelector('#user_id')?.value || '',
			email: document.querySelector('#email')?.value.trim() || '',
			phone: document.querySelector('#phone')?.value.trim() || '',
			fio: document.querySelector('#fio')?.value.trim() || ''
		};

		// ✅ Данные авто
		const car_json = {
			car_id: document.querySelector('#car_id')?.value || '',
			caption: document.querySelector('#car_id option:checked')?.dataset.caption || '',
			model: document.querySelector('#car_model')?.value.trim() || '',
			gos_num: document.querySelector('#car_gos_num')?.value || '',
			vin: document.querySelector('#car_vin')?.value || '',
			year: document.querySelector('#car_year')?.value || '',
			mileage: document.querySelector('#car_mileage')?.value || ''
		};

		// ✅ Выбранные услуги
		const services_arr = getSelectedServices();

		// === ✅ ВАЛИДАЦИЯ ===
		let errors = [];

		// Проверка времени
		if (!time || time === 'Выберите дату и время') {
			errors.push('Выберите дату и время записи.');
		}

		// Проверка выбранных услуг
		if (services_arr.length === 0) {
			errors.push('Выберите хотя бы одну услугу.');
		}

		// Проверка данных пользователя (достаточно ФИО + один контакт)
		if (!user_json.fio) {
			errors.push('Укажите ФИО.');
		}
		if (!user_json.phone && !user_json.email) {
			errors.push('Укажите хотя бы телефон или email.');
		}

		// Проверка модели автомобиля
		if (!car_json.model) {
			errors.push('Укажите модель автомобиля.');
		}

		// Если есть ошибки — показываем и выходим
		if (errors.length > 0) {
			alert(errors.join('\n'));
			return;
		}

		// ✅ Формируем объект для отправки
		const obj = {
			tables: {
				car_service_records: {
					id: id,
					sto_id: sto_id,
					time: time,
					time_from: time_from,
					time_to: time_to,
					user_json: JSON.stringify(user_json),
					car_json: JSON.stringify(car_json),
					status_id: 1
				},
				car_service_record_services: services_arr
			}
		};

		// ✅ Отправка
		postAjax({ action: 'save_record', json: JSON.stringify(obj) }, false, 'go_to_record_page');
	}

	
	  
	function getSelectedServices() {
		const data = [];

		document.querySelectorAll('.selected-table tbody tr').forEach(row => {
			// если есть чекбокс и он не checked — пропускаем
			const checkbox = row.querySelector('input.selected-check');
			if (checkbox && !checkbox.checked) return;

			// id и wp из data- атрибутов строки (fallback на checkbox.dataset.id)
			const id = parseInt(row.dataset.id || checkbox?.dataset.id || '', 10) || null;
			const wp = parseInt(row.dataset.wp || '0', 10) || 0;

			// цена: предпочтительно из data-price, иначе из input.price, иначе из текстовой ячейки
			let price = 0;
			if (row.dataset.price) {
				price = parseInt(String(row.dataset.price).replace(/[^\d\-]/g, ''), 10) || 0;
			} else if (row.querySelector('input.price')) {
				price = parseFloat(row.querySelector('input.price').value) || 0;
			} else {
				const priceText = row.querySelector('td:nth-child(4)')?.textContent || '';
				// удаляем всё кроме цифр и точки/минуса (поддержка тысячных разделителей и NBSP)
				const cleaned = priceText.replace(/[^\d.-]/g, '');
				price = cleaned ? parseFloat(cleaned) : 0;
			}

			// caption: берём из <strong>, чтобы не тянуть текст стикеров
			const caption = row.querySelector('strong')?.textContent.trim()
						  || row.querySelector('td:nth-child(2)')?.textContent.trim() || '';

			const description = row.querySelector('td:nth-child(3)')?.textContent.trim() || '';

			// count: из input.quantity, либо data-count, иначе 1
			const count = parseInt(row.querySelector('input.quantity')?.value
						 || row.dataset.count || '1', 10) || 1;

			data.push({
				caption: caption,
				price: price,
				count: count
			});
		});

		return data;
	}

	
	
	function go_to_record_page(answer) {
		if (answer.status != 200) {
			alert(answer.message);
			return false;
			
		}
		else {
			location = location + "?success_message=Запись успешно создана!";
		}
	}
	
	function getTableData() {
		let data = [];

		$('#table_services_wrapper table tbody tr').each(function () {
			let caption = $(this).find('td:nth-child(1)').text().trim();
			let price = parseFloat($(this).find('input.price').val()) || 0;
			let count = parseInt($(this).find('input.quantity').val()) || 0;

			data.push({
				caption: caption,
				price: price,
				count: count
			});
		});

		return data;
	}
	
	
	function get_data_by_sto_id() {
		const sto_id = $('#sto_id').val();
		postAjax({action: 'get_data_by_sto_id', id : sto_id}, false, 'render_data_by_sto_id');
	}
	
	function get_cars_info_by_user_id() {
		
		document.getElementById('user_car_caption').value = '';
		document.getElementById('user_car_model').value = '';
		document.getElementById('user_car_gos_num').value = '';
		document.getElementById('user_car_vin').value =  '';
		document.getElementById('user_car_year').value = '';
		document.getElementById('user_car_mileage').value = '';
		
		let user_id = $('#users_select option:selected').data('id');
		postAjax({action: 'get_cars_info_by_user_id', user_id : user_id}, false, 'render_data_by_user_id');
	}
	
	function render_data_by_user_id(answer) {
		const cars_arr = answer.data.cars_info;
		const select = document.getElementById('user_car');

		// Очистка select
		select.innerHTML = '';

		// Добавляем option по данным
		cars_arr.forEach((el, index) => {
			const option = document.createElement('option'); 
			option.value = el.id;
			option.textContent = `${el.caption} (${el.model})`;
			option.dataset.index = index; // сохраняем индекс для быстрого доступа
			select.appendChild(option);
		});

		// При изменении выбранного авто – заполняем поля
		select.addEventListener('change', function () {
			const selectedIndex = this.options[this.selectedIndex].dataset.index;
			const car = cars_arr[selectedIndex];

			if (car) {
				document.getElementById('user_car_caption').value = car.caption || '';
				document.getElementById('user_car_model').value = car.model || '';
				document.getElementById('user_car_gos_num').value = car.gos_num || '';
				document.getElementById('user_car_vin').value = (car.vin || car.frame) || '';
				document.getElementById('user_car_year').value = car.year || '';
				document.getElementById('user_car_mileage').value = car.mileage || '';
			}
		});
		
		// Автозаполнение первым авто (если нужно)
		if (cars_arr.length > 0) {
			select.selectedIndex = 0;
			select.dispatchEvent(new Event('change'));
		}
		
	}
	
	function render_data_by_sto_id(answer) {
		
		const tree_lists_services_arr = answer.data.tree_lists_services_arr;
		
		let $services = $('#services_select');
		$services.empty();
		$services.append(new Option("Добавьте в таблицу", "-1", false, false));
		tree_lists_services_arr.forEach(item => {
			$services.append(new Option(item.value, item.id, false, false));
		});
		$services.trigger('change');
		
	}
	
	function fill_options_by_selected_user() {
		
		let email = $('#users_select option:selected').data('email');
		let phone = $('#users_select option:selected').data('phone');
		let name = $('#users_select option:selected').data('name');
		let surname = $('#users_select option:selected').data('surname');
		
		getElement('#user_email').value = email;
		getElement('#user_phone').value = phone;
		getElement('#user_fio').value = surname + ' ' + name;
		
		get_cars_info_by_user_id();
		
	}
	  
	function generate_table_by_services_select() {
		let id = $('#services_select').val();
		let sto_id = $('input[name="car_service"]:checked').data('id');
		
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
	
	// Удаление строки
	$(document).on('click', '.btn-remove-row', function () {
		$(this).closest('tr').remove();
		updateTotals();
	});

	// Пересчёт "Итого" для строки при изменении цены или количества
	$(document).on('input', '.quantity, .price', function () {
		const row = $(this).closest('tr');
		const price = parseFloat(row.find('.price').val()) || 0;
		const quantity = parseInt(row.find('.quantity').val()) || 0;
		row.find('.total').val((price * quantity).toFixed(2));
		updateTotals();
	});

	// Обновление общей суммы и количества
	function updateTotals() {
		let totalQuantity = 0;
		let totalSum = 0;

		$('#table_services_wrapper table tbody tr').each(function () {
			const qty = parseInt($(this).find('.quantity').val()) || 0;
			const sum = parseFloat($(this).find('.total').val()) || 0;
			totalQuantity += qty;
			totalSum += sum;
		});

		$('#total_quantity').text(totalQuantity);
		$('#total_sum').text(totalSum.toFixed(2));
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

	function renderCalendar() {
	  const monthName = currentDate.toLocaleString('ru-RU', { month: 'long', year: 'numeric' });
	  document.getElementById('calendar-month').textContent = monthName;

	  const grid = document.querySelector('.calendar-grid');
	  grid.innerHTML = '';

	  const year = currentDate.getFullYear();
	  const month = currentDate.getMonth();

	  const start = getCalendarStart(year, month);
	  const today = new Date(); today.setHours(0,0,0,0);

	  for (let i = 0; i < 42; i++) {
		const cellDate = new Date(start);
		cellDate.setDate(start.getDate() + i);

		const div = document.createElement('div');
		div.textContent = cellDate.getDate();

		if (cellDate.getMonth() !== month) {
		  div.classList.add('disabled');
		} else {
		  const dow = cellDate.getDay();
		  const isWeekend = (dow === 0 || dow === 6);

		  if (cellDate.getTime() === today.getTime()) {
			div.classList.add('today');
		  }

		  if (selectedDay && cellDate.getTime() === selectedDay.getTime()) {
			div.classList.add('selected');
		  }

		  if (cellDate.getTime() > today.getTime() && !isWeekend) {
			div.classList.add('available');
			const dayCopy = new Date(cellDate);
			div.addEventListener('click', () => selectDate(dayCopy));
		  } else {
			div.classList.add('disabled');
		  }
		}
		grid.appendChild(div);
	  }
	}
	
	function selectDate(day) {
	  selectedDay = new Date(day);
	  const formattedDate = formatDateLocal(day);
	  let sto_id = $('input[name="car_service"]:checked').data('id');
	  slotsHeader.textContent = `Доступное время: ${formattedDate}`;
	  slotsList.innerHTML = '<div>Загрузка...</div>';
	  renderCalendar(); // перерисовываем с подсветкой выбранного дня

	  postAjax(
		{ action: 'get_slots_by_date', sto_id: sto_id, date: formattedDate },
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
			closeCalendar();
		  });
		  slotsList.appendChild(btn);
		});
	  }
	}

	function openCalendar() {
	  overlay.style.display = 'flex';
	  renderCalendar();
	}

	function closeCalendar() {
	  overlay.style.display = 'none';
	}
	
	
	function cancel_pay() {
		postAjax(
		{ 
			action: 'cancel_pay', record_id: "<?=$_GET["id"];?>"},
			false,
			'reload_page'
		);
	} 
	
	function add_pay() {
		const sum = document.querySelector("#total_sum").textContent.trim();
		
		if (!(sum > 0)) {
			alert("Сумма позиций ошибочна, операция отменена.");
			return false;
		}
		
		postAjax(
		{   
			action: 'add_pay', record_id: "<?=$_GET["id"];?>", sum: sum},
			false,
			'reload_page'
		);
	}
	
	function reload_page() {
		location = location;
	}
	
	const url_request = '<?=$DP_Config->domain_path . $DP_Config->backend_dir;?>/content/car_service/ajax/ajax_sto_record_functions.php';
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
						alert(answer.message);
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
	
</script>