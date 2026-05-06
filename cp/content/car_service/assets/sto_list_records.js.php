<script>
	var filter_var = {
		time_from : -1,
		time_to : -1,
		auto_str : "",
		worker_id : -1,
		post_id : -1,
		customer : "",
		is_paid : -1,
		is_view : -1,
		id : -1,
		sto_id : -1,
		is_archive : 0,
	};
	
	var current_main_checkbox = false;
	
	window.onload = function () {
		setHtmlTable();
	}
	
	function setHtmlTable() {
		getHtmlRecordsTable();
		getElement('#main_checkbox').addEventListener('click', toggleAllCheckboxes);
	}
	
	function toggleAllCheckboxes() {
		current_main_checkbox = !current_main_checkbox;
		document.querySelectorAll('.checkbox_for_records').forEach((el) => {
			if (current_main_checkbox) {
				el.checked = true;
			}
			else {
				el.checked = false;
			}
		});
	}
	
	function get_selected_records() {
		let arr_records = [];
		document.querySelectorAll('.checkbox_for_records').forEach((el) => {
			if (el.checked) {
				arr_records.push(el.dataset.id); 
			}
		});
		
		if (arr_records.length > 0) {
			return arr_records.join(',');
		}
		else {
			return false;
		}
			
	}

	
	function archive_select_records() {
		let str_records = get_selected_records();
		if (!str_records) {
			alert('Выберите записи.');
			return false;
		}
		else if (confirm('Архивировать записи?')){
			postAjax({action: 'archive_by_ids', ids : str_records}, false, 'setHtmlTable');
		}
	}
	
	function unarchive_select_records() {
		let str_records = get_selected_records();
		if (!str_records) {
			alert('Выберите записи.');
			return false;
		}
		else if (confirm('Разархивировать записи?')){
			postAjax({action: 'unarchive_by_ids', ids : str_records}, false, 'setHtmlTable');
		}
	}
	
	function delete_select_records() {
		let str_records = get_selected_records();
		if (!str_records) {
			alert('Выберите записи.');
			return false;
		}
		else if (confirm('Удалить записи?')){
			postAjax({action: 'delete_by_ids', ids : str_records}, false, 'setHtmlTable');
		}
	}
	
	function getHtmlRecordsTable() {
		postAjax({action: 'get_records_list_html', filter_obj : JSON.stringify(filter_var)}, false, 'setRecorsdHtml');
		getElement('#main_checkbox').checked = false;
		current_main_checkbox = false;
	}
	
	function setRecorsdHtml(answer) {
		document.querySelector('#main_wrapper_records').innerHTML = answer.html;
	}
	 
	function unset_filter_var () {
		filter_var = {
			time_from : -1,
			time_to : -1,
			auto_str : "",
			worker_id : -1,
			post_id : -1,
			customer : "",
			is_paid : -1,
			is_view : -1,
			id : -1,
			sto_id : -1,
			is_archive : 0,
		};

		// Сбрасываем значения элементов формы
		getElement('#time_from').value = "";
		getElement('#time_to').value = "";
		getElement('#auto_str').value = "";
		getElement('#worker_id').selectedIndex = 0;
		getElement('#post_id').selectedIndex = 0;
		getElement('#customer').value = "";
		getElement('#paid').selectedIndex = 0;
		getElement('#viewed').selectedIndex = 0;
		getElement('#order_id').value = "";
		getElement('#sto_id').selectedIndex = 0;
		getElement('#is_archive').checked = false;

		getElement('#time_from_show').value = "";
		getElement('#time_to_show').value = "";

		getHtmlRecordsTable();
	}

	
	function set_filter_records() {
		
		filter_var = {
			time_from : getElement('#time_from').value * 1000,
			time_to : getElement('#time_to').value * 1000,
			auto_str : getElement('#auto_str').value,
			worker_id : getElement('#worker_id').selectedIndex,
			post_id : getElement('#post_id').selectedIndex,
			customer : getElement('#customer').value,
			is_paid : getElement('#paid').selectedIndex,
			is_view : getElement('#viewed').selectedIndex,
			id : getElement('#order_id').value,
			sto_id : getElement('#sto_id').selectedIndex,
			is_archive : getElement('#is_archive').checked ? 1 : 0,
		};
		
		getHtmlRecordsTable();
	}
</script>