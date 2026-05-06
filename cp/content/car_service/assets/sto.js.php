<script>
	//Проверяем данные и возвращаем объект для сохранения/текст ошибки
	function validateFields(action, extra_fields = null) {
		let result = {
			success: true,
			error_message: '',
			body : {
				id_tree_list_services : getElement('#sto_id_tree_list_services').value,
				caption : getElement('#sto_caption').value,
				address : getElement('#sto_address').value,
				operating_mode : getElement('#sto_operating_mode').value,
                time_from : getElement('#time_from').value,
                time_to : getElement('#time_to').value,
                organization : getElement('#organization').value,
                inn : getElement('#inn').value,
                phone : getElement('#phone_org').value,
				action: action,
				record_step : getElement('#record_step').value,
				coords : getElement('#coords').value,
				hourly_rate : getElement('#hourly_rate').value
			}
		};

        let days_array = [].concat( $("#sto_operating_mode_select").multipleSelect('getSelects') );

        if(days_array.length == 0)
        {
            result.success = false;
            result.error_message = 'Укажите режим работы.';
            return result;
        }

        document.getElementById("sto_operating_mode").value = JSON.stringify(days_array);
        result.body.operating_mode = document.getElementById("sto_operating_mode").value;
		if (result.body.caption.trim() == '')
		{
			result.success = false;
			result.error_message = 'Укажите название.';
			return result;
		}
		
		if (result.body.id_tree_list_cars == result.body.id_tree_list_services )
		{
			result.success = false;
			result.error_message = 'Список автомобилей и список услуг не могут ссылаться на один и тот же список.';
			return result;
		}

        if (Math.round(result.body.time_from) >= Math.round(result.body.time_to) )
        {
            result.success = false;
            result.error_message = 'Укажите корректное время работы.';
            return result;
        }
		
		if (!(result.body.record_step > 0)) {
			result.success = false;
            result.error_message = 'Укажите интервал записи в минутах. Пример - 30 (каждые пол часа).';
            return result;
		}
		
		if (extra_fields != null)
			result.body = Object.assign(result.body, extra_fields);
		
		return result;
	}
	
	
	function createSto() {
		let result = validateFields('create_sto');
		if (!result.success)
		{
			alert(result.error_message);
			return false;
		}
		
		postAjax(result.body, false, 'reloadPageAfterCreate');
		//
	}
	
	function updateSto(id) {
		let result = validateFields('update_sto', {id : id});
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
		location = '<?=$DP_Config->domain_path . $DP_Config->backend_dir . '/car_service/sto_list'?>';
	}
	
	function deleteStoById(id) {
		if (confirm('Подтвердите удаление.'))
			postAjax({
				action: 'delete_by_id',
				id : id
			}, false, 'redirectAfterDelete');
	}

	window.onload = function () {
        $('#sto_operating_mode_select').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});
        $('#sto_operating_mode_select').multipleSelect('setSelects', <?php echo json_encode(json_decode($sto_data["operating_mode"], true)); ?>);
    }

</script>


