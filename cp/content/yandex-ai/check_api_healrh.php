<?php 

$ch = curl_init('https://api.fordzap.com/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$error = curl_error($ch);
var_dump($response, $error);


?>