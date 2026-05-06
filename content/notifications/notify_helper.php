<?php
//Скрипт с определениями вспомогательных функций для уведомлений

// -------------------------------------------------------------------------------------------------------
// Функция отправки уведомления
function send_notify($notify_name, $notify_vars, $persons, $to_wait=true, $files=array())
{
	global $DP_Config;
	
	//Отправка уведомления через общий интерфейс
	$postdata = http_build_query(
		array(
			'check' => $DP_Config->secret_succession,
			'name' => $notify_name,
			'vars' => json_encode($notify_vars),
			'persons' => json_encode($persons),
			'files' => json_encode($files)
		)
	);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path."content/notifications/send_notify.php");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	
	//Не ждем ответа, для ускарения работы скриптов
	if($to_wait == false){
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 1);
	}
	
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	$curl_result = curl_exec($curl);
	curl_close($curl);
	return json_decode($curl_result, true);
}
// -------------------------------------------------------------------------------------------------------
?>