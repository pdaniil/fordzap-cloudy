<?php

ini_set('display_errors', 0);

//Конфигурация
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Соединение с основной БД
$DP_Config = new DP_Config;//Конфигурация CMS

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

// Получаем настройки
$settings_catalog = array();
$settings_catalog['watermark'] = $DP_Config->watermark;//Отображать водяной знак (1\0)
$settings_catalog['watermark_images'] = $DP_Config->watermark_images;//Файл картинки водяного знака из папки Файлы -> images (watermark.png)
$settings_catalog['watermark_display'] = $DP_Config->watermark_display;//Расположение водяного знака на картинке товара: вверху слева (1), вверху справа (2), внизу слева (3), внизу справа (4)

// получаем имя изображения через GET
if(isset($_GET['image_id'])){
	$query = $db_link->prepare('SELECT `file_name` FROM `shop_products_images` WHERE `id` = ?');
	$query->execute(array($_GET['image_id']));
	$row = $query->fetch();
	if(!empty($row)){
		$images_list = array();
		if(!empty($row['file_name'])){
			if(strpos($row['file_name'], ',') !== false){
				$images_list_tmp = explode(',', $row['file_name']);
			}else{
				$images_list_tmp = explode(' ', $row['file_name']);
			}
			foreach($images_list_tmp as $item_img){
				$item_img = trim($item_img);
				$item_img = $DP_Config->domain_path."content/files/images/products_images/".$item_img;
				if(strpos($item_img,'http')===false){
					continue;
				}else{
					$images_list[] = $item_img;
				}
			}
		}
		if(!empty($images_list[(int)$_GET['image_n']-1]))
		{
			$image = $images_list[(int)$_GET['image_n']-1];
		}else{
			if(!empty($images_list[0])){
				$image = $images_list[0];
			}
		}
	}
}

if($image == null){
	$image = $DP_Config->domain_path."content/files/images/no_image.png";
}

$image_file_name = end(explode('/',$image));
$extension = pathinfo($image_file_name, PATHINFO_EXTENSION);

/*
var_dump($extension);
var_dump($images_list);
exit;
*/

$image_path = $image;

switch($extension){
	case 'png' :
		header('content-type: image/png');
		$image = imagecreatefrompng($image_path);
		imageAlphaBlending($image, true);
		imageSaveAlpha($image, true);
	break;
	case 'gif' :
		header('content-type: image/gif');
		$image = imagecreatefromgif($image_path);
	break;
	default :
		header('content-type: image/jpeg');
		$image = imagecreatefromjpeg($image_path);
	break;
}

//если что-то пойдёт не так
if ($image === false) {
	return false;
}

// Если включено наложение водяного знака
if((int)$settings_catalog['watermark'] === 1 && file_exists($_SERVER["DOCUMENT_ROOT"].'/content/files/images/'.$settings_catalog['watermark_images'])){
		
	// создаём водяной знак
	$watermark = $_SERVER["DOCUMENT_ROOT"].'/content/files/images/'.$settings_catalog['watermark_images'];
	$image_file_name_watermark = end(explode('/',$watermark));
	$extension_watermark = pathinfo($image_file_name_watermark, PATHINFO_EXTENSION);
	switch($extension_watermark){
	case 'png' :
		$watermark = imagecreatefrompng($watermark);
	break;
	case 'gif' :
		$watermark = imagecreatefromgif($watermark);
	break;
	default :
		$watermark = imagecreatefromjpeg($watermark);
	break;
}
	
	// получаем значения высоты и ширины водяного знака
	$watermark_width = imagesx($watermark);
	$watermark_height = imagesy($watermark);  
	
	// получаем значения высоты и ширины картинки
	$image_width = imagesx($image);
	$image_height = imagesy($image);  
	
	$size = getimagesize($image_path);

	switch((int)$settings_catalog['watermark_display']){
		case 1 :
			// помещаем водяной знак на изображение вверху слева
			$dest_x = 5;
			$dest_y = 5;
		break;
		case 2 :
			// помещаем водяной знак на изображение вверху справа
			$dest_x = $size[0] - $watermark_width - 5;
			$dest_y = 5;
		break;
		case 3 :
			// помещаем водяной знак на изображение внизу слева
			$dest_x = 5;
			$dest_y = $size[1] - $watermark_height - 5;
		break;
		case 4 :
			// помещаем водяной знак на изображение внизу справа
			$dest_x = $size[0] - $watermark_width - 5;
			$dest_y = $size[1] - $watermark_height - 5;
		break;
	}

	imagealphablending($image, true);
	imagealphablending($watermark, true);

	// создаём новое изображение
	imagecopy($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);
	/*
	// создаём новое изображение с автозаполнение
	do{
		imagecopy($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);
		$dest_x += $watermark_width + 80;
		if($image_width < $dest_x ){
			$dest_y += $watermark_height + 80;
			$dest_x = 5;
			
		}
	}while($image_width > $dest_x && $image_height > $dest_y);
	*/
	
	/*
	// Наложение водяного знака по центру картинки
	imagecopyresampled ( $image, $watermark,
	   ( int ) ceil ( ( $image_width - $watermark_width ) / 2 ),
	   ( int ) ceil ( ( $image_height - $watermark_height ) / 2 ),
	   0, 0,
	   imagesx ( $watermark ), imagesx ( $watermark ),
	   imagesx ( $watermark ), imagesy ( $watermark )
	);
	*/
	
	// освобождаем память
	imagedestroy($watermark); 
}

// Отображаем картинку
switch($extension){
	case 'png' :
		imagepng($image, null);
	break;
	case 'gif' :
		imagegif($image);
	break;
	default :
		imagejpeg($image, null, 100);
	break;
}
?>