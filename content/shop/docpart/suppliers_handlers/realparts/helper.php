<?php 
	function getToken($login, $password) {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://whls.real.parts/api/auth',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => 'login='.$login.'&password=' . $password,
		  CURLOPT_HTTPHEADER => array(
			'Accept: application/json',
			'Content-Type: application/x-www-form-urlencoded'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return $response;
	}
	
	function getManufacturers($article, $token) {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://whls.real.parts/api/products/manufacturers/codes?code=' . $article,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_HTTPHEADER => array(
			'Accept: application/json',
			'Authorization: Bearer ' . $token
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return $response;
	}
	
	function getProducts($manufacturer_id, $article, $replacements, $token) {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://whls.real.parts/api/offers/products/manufacturers/'.$manufacturer_id.'/new?code=' . $article . '&replacements=' . $replacements,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_HTTPHEADER => array(
			'Accept: application/json',
			'Authorization: Bearer ' . $token
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return $response;
	}
?>