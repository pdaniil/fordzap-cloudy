<script>
	// URL ajax-обработчика. Если используется ЧПУ-роут — замените значение.
	var PERSONAL_AJAX_URL = '<?=$DP_Config->domain_path;?>cp/content/car_service/ajax/ajax_service_personal_functions.php';

	/* ===== Работа с изображением ===== */

	function previewPersonalImage(input) {
		if (input.files && input.files[0]) {
			var reader = new FileReader();
			reader.onload = function (e) {
				document.getElementById('personal_image_preview').src = e.target.result;
				document.getElementById('personal_image_preview_wrap').style.display = 'block';
			};
			reader.readAsDataURL(input.files[0]);
			// выбрали новый файл — сбрасываем флаг удаления
			document.getElementById('personal_image_delete').value = '0';
		}
	}

	function removePersonalImage() {
		document.getElementById('personal_image_file').value = '';
		document.getElementById('personal_image_preview').src = '';
		document.getElementById('personal_image_preview_wrap').style.display = 'none';
		document.getElementById('personal_image_delete').value = '1';
	}

	/* ===== Валидация ===== */

	function validateFields(action, extra_fields = null) {
		let result = {
			success: true,
			error_message: '',
			body : {
				fio : getElement('#personal_fio').value,
				phone : getElement('#personal_phone').value,
				standart_hourly_rate : getElement('#personal_standart_hourly_rate').value,
				service_percent : getElement('#personal_service_percent').value,
				user_id : getElement('#personal_user_id').value,
				show_price_info : getElement('#show_price_info').checked ? 1 : 0,
				action: action
			}
		};

		if (result.body.fio.trim() == '')
		{
			result.success = false;
			result.error_message = 'Укажите ФИО.';
			return result;
		}

		if (!result.body.standart_hourly_rate > 0)
		{
			result.success = false;
			result.error_message = 'Укажите ставку нормо-часа';
			return result;
		}

		if (extra_fields != null)
			result.body = Object.assign(result.body, extra_fields);

		return result;
	}

	/* ===== Сборка FormData (поля + файл + флаг удаления) ===== */

	function buildPersonalFormData(body) {
		let fd = new FormData();
		for (let key in body) {
			if (Object.prototype.hasOwnProperty.call(body, key)) {
				fd.append(key, body[key]);
			}
		}

		let delFlag = getElement('#personal_image_delete');
		fd.append('image_delete', delFlag ? delFlag.value : '0');

		let fileInput = getElement('#personal_image_file');
		if (fileInput && fileInput.files && fileInput.files[0]) {
			fd.append('image', fileInput.files[0]);
		}
		return fd;
	}

	// Аналог postAjax, но с multipart/form-data (для передачи файлов)
	function postAjaxMultipart(body, callback) {
		$.ajax({
			url: PERSONAL_AJAX_URL,
			type: 'POST',
			data: buildPersonalFormData(body),
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function (answer) {
				if (typeof callback === 'function') {
					callback(answer);
				} else if (typeof callback === 'string' && typeof window[callback] === 'function') {
					window[callback](answer);
				}
			},
			error: function () {
				alert('Ошибка запроса');
			}
		});
	}

	/* ===== Действия ===== */

	function createPersonal() {
		let result = validateFields('create_personal');
		if (!result.success)
		{
			alert(result.error_message);
			return false;
		}

		postAjaxMultipart(result.body, 'reloadPageAfterCreate');
	}

	function updatePersonal(id) {
		let result = validateFields('update_personal', {id : id});
		if (!result.success)
		{
			alert(result.error_message);
			return false;
		}

		postAjaxMultipart(result.body, function (answer) {
			if (answer && answer.status === 200) {
				location.reload();
			} else {
				alert(answer && answer.message ? answer.message : 'Ошибка сохранения');
			}
		});
	}

	function reloadPageAfterCreate(answer) {
		if (answer.id > 0)
			location = location.origin + location.pathname + '?id=' + answer.id;
	}

	function redirectAfterDelete() {
		location = '<?=$DP_Config->domain_path . $DP_Config->backend_dir . '/car_service/service_personal_list'?>';
	}

	function deletePersonalById(id) {
		if (confirm('Подтвердите удаление.'))
			postAjax({
				action: 'delete_by_id',
				id : id
			}, false, 'redirectAfterDelete');
	}
</script>