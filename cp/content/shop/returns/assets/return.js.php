function change_status_return() {
	let obj = new URLSearchParams({
			action: "update_return_status",
			return_id: return_id,
			status_id: document.querySelector("#reason_id").value,
			tech_key: tech_key,
			csrf_guard_key: csrf_guard_key,
			status_name: document.querySelector("#" + "status_" + document.querySelector("#reason_id").value).textContent
	});
	obj = obj.toString();
	jQuery.ajax({
			type: "POST",
			async: true, //Запрос синхронный
			url: main_url + "/content/shop/returns/ajax/ajax_statuses.php",
			dataType: "json",//Тип возвращаемого значения
			data: obj,
			success: function (answer) {
			}
	});
}
function resolveReturn(id) {
	let obj = new URLSearchParams({
			action: "resolve",
			id: id,
			tech_key: tech_key,
			csrf_guard_key: csrf_guard_key
	});
	obj = obj.toString();
	jQuery.ajax({
			type: "POST",
			async: true, //Запрос синхронный
			url: main_url + "/content/shop/returns/ajax/ajax_return.php",
			dataType: "json",//Тип возвращаемого значения
			data: obj,
			success: function (answer) {
					location = location;
			}
	});
}
function rejectReturn(id) {
	let obj = new URLSearchParams({
			action: "reject",
			id: id,
			tech_key: tech_key,
			csrf_guard_key: csrf_guard_key
	});
	obj = obj.toString();
	jQuery.ajax({
			type: "POST",
			async: true, //Запрос синхронный
			url: main_url + "/content/shop/returns/ajax/ajax_return.php",
			dataType: "json",//Тип возвращаемого значения
			data: obj,
			success: function (answer) {
					location = location;
			}
	});
}
function refund(direct_refund)
{
	if( !confirm('Отразить возврат в системе учета платежей по заказу?') ) return;

	let cashless_payment = document.getElementById('cashless_payment').checked ? '2' : '1';

	jQuery.ajax({
			type: "POST",
			async: false, //Запрос синхронный
			url: main_url + "/content/shop/order_process/ajax_order_pay_refund.php",
			dataType: "text",//Тип возвращаемого значения
			data: "cashless_payment="+cashless_payment+"&direct_refund="+direct_refund+"&order_id=" + order_id +"&csrf_guard_key=" + csrf_guard_key + "&paid_sum=" + price_sum_total,
			success: function(answer)
			{
					var answer_ob = JSON.parse(answer);
					//Если некорректный парсинг ответа
					if( typeof answer_ob.status === "undefined" ) alert("Неизвестная ошибка");
					else
							//Корректный парсинг ответа
							if(answer_ob.status == true)
									closeReturn(1);
							else
									alert(answer_ob.message);
			}
	});
}
function closeReturn(from_refund = null) {
	if (from_refund == null)
			from_refund = 0;
	let obj = new URLSearchParams({
			action: "close",
			return_id: return_id,
			tech_key: tech_key,
			csrf_guard_key: csrf_guard_key,
			from_refund: from_refund
	});
	obj = obj.toString();
	jQuery.ajax({
			type: "POST",
			async: true, //Запрос синхронный
			url: main_url + "/content/shop/returns/ajax/ajax_return.php",
			dataType: "json",//Тип возвращаемого значения
			data: obj,
			success: function (answer) {
					if (answer.status) {
							//Обновляем страницу
							location= location + '&success_message='+encodeURI('Возврат успешно отражен в системе учета платежей');
					}
					else
						 console.log(answer);
			}
	});
}
function openImage(image)
{
	let w = window.open("");
	w.document.write(image.outerHTML);
}