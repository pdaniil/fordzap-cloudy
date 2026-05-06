<script>
	var url_request = '<?=AJAX_REQUEST_URL;?>';
	var sto_id = '<?=isset($_GET["id"]) && $_GET["id"] > 0 ? $_GET["id"] : 'null'; ?>';
	const url = '<?=REDIRECT_URL;?>';
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

    async function fetchAjax(obj) {
        let response = await fetch(url_request, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json;charset=utf-8'
            },
            body: JSON.stringify(obj)
        });

        let result = await response.json();
        return result;
    }

	function getElement(selector) {
		return document.querySelector(selector);
	}
	
	function getElById(id) {
		return document.querySelector('#' + id);
	}
	
</script>