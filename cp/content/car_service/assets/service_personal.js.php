<script>
	//Проверяем данные и возвращаем объект для сохранения/текст ошибки
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
		console.log(result);
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
	
	
	function createPersonal() {
		let result = validateFields('create_personal');
		if (!result.success)
		{
			alert(result.error_message);
			return false;
		}
		
		postAjax(result.body, false, 'reloadPageAfterCreate');
		//
	}
	
	function updatePersonal(id) {
		let result = validateFields('update_personal', {id : id});
		if (!result.success)
		{
			alert(result.error_message);
			return false;
		}
		
		postAjax(result.body, true);
		//
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


