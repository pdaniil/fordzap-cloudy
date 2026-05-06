<?php

defined('_ASTEXE_') or die('No access');
// Для SSL
$ssl_state = 0;//Индикатор для SMS-уведомлений. Состояние индикатора: SSL есть, настройки есть 11, SSL есть, настроек нет 12,SSL нет, настройки есть 21, SSL нет, настроек нет 22 

$url = $DP_Config->domain_path;
$orignal_parse = parse_url($url, PHP_URL_HOST);
$context = stream_context_create(
    array(
        'ssl' => array(
            'capture_peer_cert' => true,
            'verify_peer'       => false, // Т.к. промежуточный сертификат может отсутствовать,
            'verify_peer_name'  => false  // отключение его проверки.
        )
    )
);


//На случай возникновения warning - устанавливаем обработчик
set_error_handler(function() {});
//Вызов функции, где может возникнуть warning
$read = stream_socket_client("ssl://".$orignal_parse.":443", $err_no, $err_str, 30, STREAM_CLIENT_CONNECT, $context);
//После вызова функции stream_socket_client, в которой мог возникнуть warning, возвращаем старый обработчик ошибок
restore_error_handler();

if($read)
{
	$cert = stream_context_get_params($read);
}


if (empty($err_no)) {
    // $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']); Просмотр содержимого
    $ssl_state = 1;
}
else $ssl_state = 2;

if (strripos(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/.htaccess"), "RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]") !== false && strripos($DP_Config->domain_path, "https") !== false)
{
    $ssl_state .= 1;
}
else $ssl_state .= 2;


switch($ssl_state)
{
    case 11:
        $ssl_status_text = "SSl сертификат установлен.<br>Редирект настроен.";
        $ssl_a_style = "";
        $ssl_sign_after = '';
        break;
    case 12:
        $ssl_status_text = "SSl сертификат установлен.<br>Редирект не настроен.";
        $ssl_a_style = "background-color:#f3fa37;color:#000;";
        $ssl_sign_after = '<i class="pe-7s-info"></i>';
        break;
    case 21:
        $ssl_status_text = "Некорректный SSl сертификат.<br>Редирект настроен.";
        $ssl_a_style = "background-color:#f3fa37;color:#000;";
        $ssl_sign_after = '<i class="pe-7s-info"></i>';
        break;
    case 22:
        $ssl_status_text = "SSl сертификат не установлен.<br>Редирект не настроен.";
        $ssl_a_style = "background-color:#F00;color:#FFF;";
        $ssl_sign_after = '<i class="pe-7s-attention"></i>';
        break;
}
?>
    <li class="dropdown hidden-sm hidden-xs hidden-md" style="pointer-events: none;">
        <a href="#" style="<?php echo $ssl_a_style; ?>border-bottom:1px solid #e4e5e7;">
            <i class="pe-7s-lock"></i> <div style="font-size:0.5em;display:inline-block;line-height:1em;"><?php echo $ssl_status_text; ?></div><?echo " ".$ssl_sign_after; ?>
        </a>
    </li>