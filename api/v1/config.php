<?php
// config.php

// Путь к корню сайта (для включения существующих файлов)
define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

// Подключение к БД (используем существующий конфиг, если есть)
// Подключение класса конфигурации сайта
require_once DOCUMENT_ROOT . '/config.php';
$DP_Config = new DP_Config();

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

// Подключение пользовательских классов (для работы с группами и наценками)
require_once DOCUMENT_ROOT . '/content/users/dp_user.php';

// Настройки API
define('API_VERSION', 'v1');
define('DEFAULT_GEO_ID', 1); // если geo_id не передан

// Таймаут для запросов к поставщикам (в секундах)
define('STORAGE_TIMEOUT', 30);

// Логирование ошибок (можно писать в файл)
define('LOG_PATH', DOCUMENT_ROOT . '/logs/api.log');

// Режим отладки (выводить ошибки)
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}