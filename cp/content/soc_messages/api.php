<?php
//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Функции пользователя
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
//Основной класс API
require_once(dirname(__FILE__)."/autoload.php");
mb_internal_encoding("UTF-8");
ini_set("display_errors", 0);
error_reporting(E_ALL);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/error.log');
global $DP_Config;
$DP_Config = new DP_Config;
$db_link = false;

try{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->exec("set names utf8mb4");
} catch (PDOException $e){
    $db_link = $e->getMessage();
}
function pre($v)
{
    print '<pre>' . print_r($v, true) . '</pre>';
}
$socialsChatsOptions = array(
    'api_url' => $DP_Config->domain_path . $DP_Config->backend_dir . '/content/soc_messages/api.php',
    'rights' => DP_User::isAdmin() || $DP_Config->tech_key === (isset($_REQUEST['tech_key']) ? $_REQUEST['tech_key'] : ''),
    'socials_config' => array(
        'VK' => array(
            'userId' => $DP_Config->soc_messages_vk_uid,
            'token' => $DP_Config->soc_messages_vk_token
        ),
        'VKGroup' => array(
            'userId' => $DP_Config->soc_messages_vk_group_id,
            'token' => $DP_Config->soc_messages_vk_group_token
        ),
        'Avito' => array(
            'clientId' => $DP_Config->soc_messages_avito_client_id,
            'clientSecret' => $DP_Config->soc_messages_avito_client_secret,
            'userId' => $DP_Config->soc_messages_avito_user_id,
            'noAvatar' => $DP_Config->domain_path . $DP_Config->backend_dir . '/content/soc_messages/img/avatar.png'
        )
    ),
    'vk_group_callback' => array(
        'confirmation' => $DP_Config->soc_messages_vk_group_confirmation,
        'secret' => $DP_Config->soc_messages_vk_group_secret
    )
);
$socialsChatsApi = new SocialsChats\Api($db_link, true, $socialsChatsOptions);
$socialsChatsApi->JSApi($DP_Config->domain_path . $DP_Config->backend_dir . '/content/soc_messages/api.php');