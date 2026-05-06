<?php 	
	//Подключаем панель навигации
	include(APP_PATH . 'php_functions/prepare_navbar.php');
	include(APP_PATH . 'blades/navbar.blade.php');	

	//Объявляем константу, используется в качестве url запроса в js для ajax
	define("AJAX_REQUEST_URL", $DP_Config->domain_path . $DP_Config->backend_dir . '/content/car_service/ajax/ajax_' . URL_PATH . '_functions.php');
	
	//
	include(APP_PATH . 'php_functions/prepare_' . URL_PATH . '.php');
	
	//Подключаем скрипты и стили
	include(APP_PATH . 'assets/main.js.php');
	include(APP_PATH . 'assets/main.css.php');
	
	include(APP_PATH . 'assets/'. URL_PATH . '.css.php');
	include(APP_PATH . 'assets/'. URL_PATH . '.js.php');
	
	//Подключаем скрипты страницы
	include(APP_PATH . 'blades/'. URL_PATH . '.blade.php');	
	
?>