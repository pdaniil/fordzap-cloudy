<script>
	//Построение дерева по массиву объектов
	function buildTree(items, parent) {
		parent = parent || null;
		let result = [];
		items.forEach((item) => {
			if (item.parent === parent) {
				result.push(item);
				item.data = buildTree(items, item.id);

				if (!item.data.length) {
					delete item.data;
				}
			}
		});
		
		return result;
	}
	//END Построение дерева по массиву объектов
	
	//Подготовка массива для корректнгой загрузки в webix
	function getValidArrWebix(arr) {
		let result = [];
		arr.forEach(el => {
			let elem = el;
			elem.parent = parseInt(el.parent);
			elem.id = parseInt(el.id);
			elem.open = false;
			if (elem.parent == 0)
				elem.parent = null;
			result.push(elem);
		});
		
		return result;
	}
	//END Подготовка массива для корректнгой загрузки в webix
	
	//Фильтрация дерева
	function filter_tree(name_webix, compare_text){
		if (compare_text.length < 2)
			window[name_webix].filter();
		else
			window[name_webix].filter("#value#", compare_text);
	};
	//END Фильтрация дерева
	
	function set_tree_webix_latest_selected(field, value) {
		tree_webix_latest_selected[field] = value;
		if (tree_webix_latest_selected.tree_service != null)
		{
			let obj_c = tree_service_webix.getItem(tree_webix_latest_selected.tree_service);
			getElement('#price').value = obj_c.hourly_rate * hourly_rate_price;
			getElement('#hourly_rate').value = obj_c.hourly_rate;
			getElement('#caption').textContent = obj_c.caption;
			getElement('#fix_price').value = obj_c.fix_price;
			  
			let stickersVal = obj_c.stickers;
			if (typeof stickersVal === 'string' && stickersVal.trim() !== '') {
				try {
					stickersVal = JSON.parse(stickersVal);
				} catch (e) {
					stickersVal = [];
				}
			}

			$('#stickers').multipleSelect('setSelects', stickersVal);
			
			getElement('#recommendation_block').checked = obj_c.recommendation_block == 1 ? true : false;
			getElement('#worker_price').checked = obj_c.worker_price == 1 ? true : false;
			getElement('#active').checked = obj_c.active == 1 ? true : false;
			
		}	
		else {
			getElement('#price').value = 0;
			getElement('#hourly_rate').value = 0;
		}
	}
	
	//Различные модификации событий
	function prepareEvent(tree_webix) {
		
		//Выделение только для конечных узлов, снятие выделения, если клик по последнему выделенному
		tree_webix.attachEvent("onBeforeSelect", function(id, e, node){
			var item = this.getItem(id);
			if (item.$count > 0 || tree_webix_latest_selected[tree_webix.config.id] == id)
			{
				//tree_webix_latest_selected[tree_webix.config.id] = null;
				set_tree_webix_latest_selected(tree_webix.config.id, null);
				return false;
			}
				
			//tree_webix_latest_selected[tree_webix.config.id] = id;
			set_tree_webix_latest_selected(tree_webix.config.id, id);
		});
		
		//END Выделение только для конечных узлов, снятие выделения, если клик по последнему выделенному
		
		//Снятие выделения при выставлении чекбокса
		tree_webix.attachEvent("onItemCheck", function(){
			tree_webix.unselectAll();
			set_tree_webix_latest_selected(tree_webix.config.id, null);
		});
		//END Снятие выделения при выставлении чекбокса
		
		//Снятие чекбокса при выделении элемента
		tree_webix.attachEvent("onItemClick", function(id, e, node){
			if (tree_webix.getItem(id).$count < 1)
			{
				//Снимаем чекбоксы
				tree_webix.getChecked().forEach(id => {
					tree_webix.uncheckItem(id);
				});
				
				getElement('#service_info').classList.remove('hidden');
				
				//Cнятие выделение, если клик по тому же элементу
				if (tree_webix.getSelectedId() == id) {
					getElement('#service_info').classList.add('hidden');
					tree_webix.unselectAll();
				}
					
			}
		});
		//END Снятие чекбокса при выделении элемента
	}
	
	function getById(id_tree_cars_list, id_tree_services_list) {
		let json_data = JSON.stringify({
				id_tree_services_list: id_tree_services_list,
				sto_id: sto_id
		});
		
		postAjax({
				action: "get_by_id",
				data: json_data
			}, 
			false, 
			'afterGetById'
		);
	}
	
	function afterGetById(answer) {
		if (answer.data)
		{
			console.log(123);
			getElement('#hourly_rate').value = answer.data.hourly_rate;
			getElement('#price').value = answer.data.hourly_rate * hourly_rate_price;
			getElement('#fix_price').value = answer.data.fix_price;
			console.log(answer);
			getElement('#caption').textContent = answer.data.caption;
			
			let stickersVal = obj_c.stickers;
			if (typeof stickersVal === 'string' && stickersVal.trim() !== '') {
				try {
					stickersVal = JSON.parse(stickersVal);
				} catch (e) {
					stickersVal = [];
				}
			}

			$('#stickers').multipleSelect('setSelects', stickersVal);
			
			getElement('#recommendation_block').checked = answer.data.recommendation_block == 1 ? true : false;
			getElement('#worker_price').checked = answer.data.worker_price == 1 ? true : false;
			getElement('#active').checked = answer.data.active == 1 ? true : false;
		}
		else
		{
			getElement('#price').value = 0;
			getElement('#hourly_rate').value = 0;
		}
	}
	
	function setWebixPrice(price) {
		tree_service_webix.getItem(tree_webix_latest_selected.tree_service).price = price;
	}
	
	function setWebixHourlyRate(hourly_rate) {
		tree_service_webix.getItem(tree_webix_latest_selected.tree_service).hourly_rate = hourly_rate;
		getElement('#price').value = hourly_rate * hourly_rate_price;
	}
	
	function setWebixCaption(caption) {
		tree_service_webix.getItem(tree_webix_latest_selected.tree_service).caption = caption;
	}
	
	function setWebixStickers() {
		// Берём массив выбранных ID из multipleSelect
		const selected = $("#stickers").multipleSelect('getSelects');

		// Храним массив, а не JSON-строку
		tree_service_webix.getItem(tree_webix_latest_selected.tree_service).stickers = selected;
	}
	
	function setWebixRecommendation() {
		tree_service_webix.getItem(tree_webix_latest_selected.tree_service).recommendation_block = getElement('#recommendation_block').checked ? 1 : 0;
	}
	
	function setWebixWorkerPrice() {
		tree_service_webix.getItem(tree_webix_latest_selected.tree_service).worker_price = getElement('#worker_price').checked ? 1 : 0;
	}
	
	function setWebixActive() {
		tree_service_webix.getItem(tree_webix_latest_selected.tree_service).active = getElement('#active').checked ? 1 : 0;
	}
	
	function setWebixFixPrice(fix_price) {
		tree_service_webix.getItem(tree_webix_latest_selected.tree_service).fix_price = fix_price;
	}
	
	function setData() {
		let query_arr = [];

		// Проходим по всем конечным узлам дерева
		tree_service_webix.data.each(function (obj) {
			if (obj.$count === 0) {
				query_arr.push({
					id_tree_services_list: obj.id,
					sto_id: sto_id,
					hourly_rate: obj.hourly_rate,
					caption: obj.caption,

					// Преобразуем массив в JSON-строку один раз
					stickers: JSON.stringify(obj.stickers || []),

					recommendation_block: obj.recommendation_block,
					worker_price: obj.worker_price,
					fix_price: obj.fix_price,
					active: obj.active
				});
			}
		});

		// Готовим JSON для отправки
		let json_data = JSON.stringify(query_arr);

		// Отправляем
		postAjax({
			action: "set_data",
			data: json_data
		}, true, 'afterSet');
	}
	
	function afterSet(answer) {
		//getElement('#loader_service').classList.add('hidden');
		//getElement('#set_data_button').classList.remove('hidden');
	}
	<?php 
	
	
		$service_tree_json = json_decode($service_tree_json, true); // Декодируем в массив
		$json_for_js = json_encode($service_tree_json, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	
	
	?>
	var service_tree_json = <?= $json_for_js ?>;
	
	//Получаем валидные для webix массивы
	var service_tree_arr = getValidArrWebix(service_tree_json);
	
	//Получаем дерево
	const tree_service = buildTree(service_tree_arr);
	
	var tree_service_webix = null; // Объект webix услуг
	
	var tree_webix_latest_selected = {
		tree_service : null
	}; // последний выделенный элемент
	
	//Инициализация после загрузки страницы
	window.onload = function () {
		getElement('#service_info').classList.add('hidden');
		tree_service_webix = webix.ui({
			view:"tree",
			select:true,
			data: {data: {id:"root", value:"Услуги", open: true, data: tree_service}},
			editable:false,//редактируемое
			container:"tree_service",//id блока div для дерева
			view:"tree",
			select:true,//можно выделять элементы
			drag:false,//можно переносить
			template:"{common.icon()} {common.folder()} <span>#value#</span>",
			threeState: true,  
			id : "tree_service",
			filterMode:{
				showSubItems:true
			}
		});
		
		//END Инициализация и вывод дереа в контейнер
		
		//Модификация событий
		prepareEvent(tree_service_webix);
		//END Модификация событий
		
		
		$('#stickers').multipleSelect({
			placeholder: "Нажмите для выбора...", width:"100%", 
			onClick: function(view) {
				setWebixStickers();
			}
		});
		
	}
	
</script>