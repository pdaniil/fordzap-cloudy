window.onload = function () {
	jQuery("#datetimepickerTo").datetimepicker({
		lang:"ru",
		closeOnDateSelect:true,
		closeOnTimeSelect:false,
		dayOfWeekStart:1,
		format:'unixtime',
		onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
		{
			var time_string = "";
			var date_ob = new Date(current_time);
			time_string += date_ob.getDate()+".";
			time_string += (date_ob.getMonth() + 1)+".";
			time_string += date_ob.getFullYear()+" ";
			time_string += date_ob.getHours()+":"+date_ob.getMinutes();
			document.getElementById("datetimepickerTo_show").value = time_string;//Показываем время в понятном виде
		}
	});
	jQuery("#datetimepickerFrom").datetimepicker({
		lang:"ru",
		closeOnDateSelect:true,
		closeOnTimeSelect:false,
		dayOfWeekStart:1,
		format:'unixtime',
		onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
		{
			var time_string = "";
			var date_ob = new Date(current_time);
			time_string += date_ob.getDate()+".";
			time_string += (date_ob.getMonth() + 1)+".";
			time_string += date_ob.getFullYear()+" ";
			time_string += date_ob.getHours()+":"+date_ob.getMinutes();
			document.getElementById("datetimepickerFrom_show").value = time_string;//Показываем время в понятном виде
		}
	});
	$('#nameInput').multipleSelect({ placeholder: "Все", width: "100%", filter: true });
	$('#managerInput').multipleSelect({ placeholder: "Все", width: "100%", filter: true });
	$('#paymentInput').multipleSelect({ placeholder: "Все", width: "100%", filter: true });
	displayInitialPage();
	resetFilters();
}
let currentPage = 1;
const itemsPerPage = 25; // Меняем на 20
let allRows = []; // Для хранения всех строк таблицы
var filteredRows = [];
function displayInitialPage() {
    const table = document.getElementById("operationsTable");
    const rows = table.getElementsByTagName("tr");
    allRows = Array.from(rows).slice(1); // Сохраняем все строки, исключая заголовок
    const totalPages = Math.ceil(allRows.length / itemsPerPage);
    displayPage(currentPage, allRows, totalPages);
}
function getTimeStamp(dateString) {
	const [day, month, yearAndTime] = dateString.split('.');
	const [year, time] = yearAndTime.split(' ');
	const [hours, minutes] = time.split(':');
	// Создаем объект Date
	const date = new Date(year, month - 1, day, hours, minutes);
	// Получаем метку времени (в миллисекундах)
	return parseInt(date.getTime());
}
function filterTable(handle = false) {
    const idInput = document.getElementById("idInput").value.toLowerCase();
	//Менеджеры
    let managerInput = '';
	if ($('#managerInput').multipleSelect('getSelects', "value").length == 0) { managerInput = ''; }
	else managerInput = $('#managerInput').multipleSelect('getSelects', "value");
	if ($('#nameInput').multipleSelect('getSelects', "value").length == 0) { nameInput = ''; }
	else nameInput = $('#nameInput').multipleSelect('getSelects', "value");
	if ($('#paymentInput').multipleSelect('getSelects', "value").length == 0) { paymentInput = ''; }
	else paymentInput = $('#paymentInput').multipleSelect('getSelects', "value");
    const amountInputFrom = document.getElementById("amountInputFrom").value;
    const amountInputTo = document.getElementById("amountInputTo").value;
    const commentInput = document.getElementById("commentInput").value;
		const dateFromInput = document.getElementById("datetimepickerFrom").value * 1000;
		const dateToInput = document.getElementById("datetimepickerTo").value * 1000;
    const table = document.getElementById("operationsTable");
    const rows = table.getElementsByTagName("tr");
    filteredRows = []; // Сбросить отфильтрованные строки
		//const dateFromInput = document.getElementById("dateFromInput").value.toLowerCase();
    //const dateToInput = document.getElementById("dateToInput").value.toLowerCase();
		
		if(handle) {
			let dateFromValue = document.getElementById("datetimepickerFrom").value;
			let dateToValue = document.getElementById("datetimepickerTo").value;
			getCashStatModule(dateFromValue, dateToValue);
		}


		var total_sum = 0;
    for (let i = 1; i < rows.length; i++) {
        const idCell = rows[i].getElementsByClassName("id")[0].textContent.toLowerCase();
        const managerCell = rows[i].getElementsByClassName("manager")[0].textContent;
        const nameCell = rows[i].getElementsByClassName("name")[0].textContent;
        const amountCell = parseInt(rows[i].getElementsByClassName("amount")[0].textContent);
        const income = rows[i].getElementsByClassName("amount")[0].getAttribute('data-income');
        const paymentCell = rows[i].getElementsByClassName("payment")[0].textContent;
        const commentCell = rows[i].getElementsByClassName("comment")[0].textContent.toLowerCase();
        const idMatch = idInput === "" || idCell.includes(idInput);
		let managerMatch = true;
		if (managerInput.length > 0 && !managerInput.includes(managerCell))
			managerMatch = false;
        let nameMatch = true;
		if (nameInput.length > 0 && !nameInput.includes(nameCell))
			nameMatch = false;
		let paymentMatch = true;
		if (paymentInput.length > 0 && !paymentInput.includes(paymentCell))
			paymentMatch = false;
		let amountMatchFrom = true;
		if (amountCell < amountInputFrom && amountInputFrom > 0)
			amountMatchFrom = false;
		let amountMatchTo = true;
		if (amountCell > amountInputTo && amountInputTo > 0)
			amountMatchTo = false;
        const commentMatch = commentCell.includes(commentInput);
		const dateCell = getTimeStamp(rows[i].getElementsByClassName("date")[0].textContent);
		let dateFrom = true;
		if (dateCell < dateFromInput && dateFromInput > 0)
			dateFrom = false;
		let dateTo = true;
		if (dateCell > dateToInput && dateToInput > 0)
			dateTo = false;
		//
		//const dateMatch = (dateCell.includes(dateFromInput) || dateCell.includes(dateToInput));
        //if (idMatch && managerMatch && nameMatch && amountMatch && paymentMatch && commentMatch) {
		if (idMatch && managerMatch && nameMatch && paymentMatch && amountMatchFrom && amountMatchTo && commentMatch && dateTo && dateFrom) {
            filteredRows.push(rows[i]);
			if (income == '1')
				total_sum += parseInt(amountCell);
			else
				total_sum -= parseInt(amountCell);
        }
    }
    // Обновление таблицы и отображение первой страницы после фильтрации
    const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
		document.querySelector('#total_sum').textContent = 'Итого: ' + total_sum.toLocaleString('ru-RU') + ' руб.';
    displayPage(1, filteredRows, totalPages);
}
function displayPage(page, rowsToDisplay, totalPages) {
    currentPage = page;
    const table = document.getElementById("operationsTable");
    const rows = table.getElementsByTagName("tr");
    // Скрываем все строки
    for (let i = 1; i < rows.length; i++) {
        rows[i].style.display = "none";
    }
    // Показываем только отфильтрованные строки на текущей странице
    const start = (page - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    for (let i = start; i < end && i < rowsToDisplay.length; i++) {
        rowsToDisplay[i].style.display = ""; // показываем строки
    }
    updatePagination(totalPages);
}
function updatePagination(totalPages) {
    const paginationContainer = document.getElementById("pagination");
    paginationContainer.innerHTML = "";
    for (let i = 1; i <= totalPages; i++) {
        const pageLink = document.createElement("a");
        pageLink.href = "#";
        pageLink.textContent = i;
        pageLink.className = (i === currentPage) ? "active" : "";
        pageLink.onclick = function (event) {
            event.preventDefault(); // предотвращаем переход по ссылке
            displayPage(i, filteredRows.length > 0 ? filteredRows : allRows, totalPages);
        };
        paginationContainer.appendChild(pageLink);
    }
}
function resetFilters() {
    document.getElementById("idInput").value = "";
	$('#managerInput').multipleSelect('setSelects', []);
	$('#nameInput').multipleSelect('setSelects', []);
	$('#paymentInput').multipleSelect('setSelects', []);
	document.getElementById("amountInputFrom").value = "";
	document.getElementById("amountInputTo").value = "";
	document.getElementById("commentInput").value = "";
	$('#datetimepickerFrom').datetimepicker('reset');
	$('#datetimepickerFrom_show').val('');
	$('#datetimepickerTo').datetimepicker('reset');
	$('#datetimepickerTo_show').val('');
  filterTable(); // обновить таблицу после сброса фильтров
}
function getCashStatModule(dateFrom = 0, dateTo = 0) {
	let office_id = jQuery("#cash_stat_module").data("officeId");
	let date = {
		'dateFrom':dateFrom,
		'dateTo':dateTo
	};
	jQuery.ajax({
		type: "POST",
		async: false, //Запрос синхронный
		url: "/cp/content/shop/logistics/cash_assets/cash_stat_module.php",
		dataType: "text",//Тип возвращаемого значения
		data: "date="+JSON.stringify(date)+"&office_id="+office_id+"&isUpdate=1",
		success: function(answer)
		{
			html = answer;
			jQuery("#cash_stat_module").html(html);
		},
		error: function()
		{
			alert('Ошибка загрузки статистики, обратитесь к специалисту техподдержки.');
		}
	});
}
