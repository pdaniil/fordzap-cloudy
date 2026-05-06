<?php ?>
<script>

function set_selected_data_cars() {
	
	const select = document.querySelector('#car_id');
	console.log(select);
	const selectedOption = select.options[select.selectedIndex];

	document.querySelector('#car_model').value = selectedOption.dataset.model;
	document.querySelector('#car_year').value = selectedOption.dataset.year;
	document.querySelector('#car_vin').value = selectedOption.dataset.vin;
	
}

</script> 