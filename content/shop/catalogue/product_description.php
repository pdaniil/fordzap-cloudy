<?php
$text_content_query = $db_link->prepare('SELECT `content` FROM `shop_products_text` WHERE `product_id` = ? LIMIT 1;');
$text_content_query->execute(array($product_id));
$text_content_record = $text_content_query->fetch();
if(!empty($text_content_record["content"])){
	echo $text_content_record["content"];
}else{
	echo 'Описание отсутствует';
}
?>