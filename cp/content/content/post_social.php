<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
function request_curl($url, $params = array(), $headers = array())
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    if(count($headers)){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return @json_decode($result, true);
}
function vkapi($method, array $params = array())
{
    global $DP_Config;
    $params['access_token'] = $DP_Config->vk_token;
    $params['v'] = '5.199'; // Версия VK API
    $result = request_curl('https://api.vk.com/method/'. $method, $params);
    #file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/vk.api.log', json_encode($result) . "\n", FILE_APPEND);
    if(is_array($result) && isset($result['response'])){
        return $result['response'];
    }
    return $result;
}
function post_vk($content, $images = array())
{
    global $DP_Config;
    if(!mb_strlen($DP_Config->vk_token)){
        return false;
    }
    if(!mb_strlen($DP_Config->vk_group_id)){
        return false;
    }
    $photos = array();
    if(count($images)){
        foreach($images as $image){
            $result = vkapi('photos.getWallUploadServer');
            if($result && isset($result['upload_url'])){
                $tmpFileName = sys_get_temp_dir() . '/vk.png';
                if(file_exists($tmpFileName)){
                    @unlink($tmpFileName);
                }
                $tmpFile = @file_get_contents($image);
                if($tmpFile){
                    file_put_contents($tmpFileName, $tmpFile);
                    if(file_exists($tmpFileName)){
                        $imageResult = request_curl($result['upload_url'], array(
                            'photo' => new CurlFile($tmpFileName)
                        ), array(
                            'Content-Type: multipart/form-data'
                        ));
                        if($imageResult && isset($imageResult['photo']) && $imageResult['photo']){
                            $imageSaveResult = vkapi('photos.saveWallPhoto', $imageResult);
                            if($imageSaveResult && count($imageSaveResult)){
                                $photos[] = 'photo' . $imageSaveResult[0]['owner_id'] . '_' . $imageSaveResult[0]['id'];
                            }
                        }
                    }
                }
            }
        }
    }
    $params = array(
        'message' => $content
    );
    if(count($photos)){
        $photos = array_slice($photos, 0, 10); // Ограничение в 10 фотографий для поста в ВКонтакте
        $params['attachments'] = implode(",",  $photos);
    }
    if(count($photos)){
        $photos = array_slice($photos, 0, 10); // Ограничение в 10 фотографий для поста в ВКонтакте
        $params['attachments'] = implode(",",  $photos);
    }
    if($DP_Config->vk_post_user){
        // Если включена публикация на стене пользователя
        $result = vkapi('wall.post', $params); // Публикация на стене пользователя
    }
    $params['owner_id'] = '-' . str_replace("-", "", $DP_Config->vk_group_id);
    $params['from_group'] = 1;
    $result = vkapi('wall.post', $params); // Публикация в сообществе
    return $result && isset($result['post_id']) ? (int)$result['post_id'] : $result;
}
function post_social($content_id)
{
    global $DP_Config, $db_link;
    $content_query = $db_link->prepare("SELECT * FROM `content` WHERE `id` = ?;");
    $content_query->execute( array($content_id) );
    $content_record = $content_query->fetch();

    $parent_query = $db_link->prepare("SELECT * FROM content WHERE id=?");
    $parent_record = $parent_query->execute(array($content_record ? $content_record['parent'] : 0)) ? $parent_query->fetch(PDO::FETCH_ASSOC) : false;
    $isNews = $parent_record && mb_strtolower($parent_record['value']) == 'новости'; // Проверка родителя записей новостей
    if($isNews && $content_record['content_type'] == 'text'){
        $content = $content_record['content'];
        preg_match_all('/src=\"(.*?)\"/mui', $content, $contentImages);
        $content = strip_tags($content);
        $images = array();
        if(count($contentImages) == 2){
            foreach($contentImages[1] as $image){
                if(mb_strpos($image, '.png') !== false || mb_strpos($image, '.jpg') !== false || mb_strpos($image, '.jpeg') !== false){
                    $images[] =  str_replace("../../", $DP_Config->domain_path, $image);
                }
            }
        }
        $postImages = array();
        if(count($images)){
            $postImages[] = $images[0]; // Публикуем только одно изображение если есть
        }
        if(mb_strlen($content)){
            // Если содержимое непустое, то можем попробовать опубликовать
            $result = post_vk($content, $postImages);
            if(is_numeric($result)){
                $db_link->prepare("UPDATE content SET post=1 WHERE id=?")->execute(array($content_id));
                return true;
            }else{
                return isset($result['error']) && $result['error']['error_code'] == 5 ? 'Ошибка авторизации' : 'Не удалось создать пост';
            }
        }
    }
}
function post_social_product($product_id, $checkPost = false)
{
    global $DP_Config, $db_link;

    $group_id = 2; // ID группы для определения цены
    $products_ids_str = $product_id;
    $customer_offices = array();
    $offices_query = $db_link->prepare('SELECT `office_id` FROM `shop_offices_geo_map`');
    $offices_query->execute(array());
    while($office = $offices_query->fetch())
    {
        array_push($customer_offices, $office["office_id"]);
    }
    require($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_all.php");
    require($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_show.php");
    $stmt = $db_link->prepare($SQL);
    $stmt->execute($sql_args_array);
    
    $product_record = $stmt->fetch();

    $product_query= $db_link->prepare("SELECT *, (SELECT `file_name` FROM `shop_products_images` WHERE `product_id` = `shop_catalogue_products`.`id` LIMIT 1) AS `file_name` FROM shop_catalogue_products WHERE id=?");
    $product = $product_query->execute(array($product_id)) ? $product_query->fetch(PDO::FETCH_ASSOC) : false;

    $property_types_tables = array("1"=>"int", "2"=>"float", "3"=>"text", "4"=>"bool", "5"=>"list", "6"=>"tree_list");

    if($product && $product_record && $product_record['customer_price'] > 0 && $product_record['exist'] > 0){

        //Указываем общие параметры товара

        $caption = $product_record["caption"];
        
        $product_url = $DP_Config->domain_path . $product_record['category_url'] . '/' . ($DP_Config->product_url == "id" ? $product_record['id'] : $product_record['alias']);

        $product_text_query = $db_link->prepare("SELECT `content` FROM `shop_products_text` WHERE `product_id` = ?;");

		$product_text_query->execute( array($product_id) );

		$product_text_record = $product_text_query->fetch();

        $product_text = '';

        if( $product_text_record != false )

        {

            $product_text = $product_text_record["content"];

        }

        $product_text = str_replace("</", "\n</", $product_text);
        $product_text = str_replace("&nbsp;", "\n", $product_text);

        $product_text = strip_tags($product_text);

        while(true){
            if(mb_strpos($product_text, "\n\n") !== false){
                $product_text = str_replace("\n\n", "\n", $product_text);
            }else{
                break;
            }
        }
        $product_text = htmlspecialchars_decode($product_text);

        $images = array();
        if($product['file_name']){
            $images[] = $DP_Config->domain_path . 'content/files/images/products_images/' . $product['file_name'];
        }

        $properties_query = $db_link->prepare('SELECT * FROM `shop_categories_properties_map` WHERE `category_id` = :category_id ORDER BY `order` ASC;');
        $properties_query->bindValue(':category_id', $product_record['category_id']);
        $properties_query->execute();
        $properties = array();
        while($property_record = $properties_query->fetch())
        {
            $property_id = $property_record["id"];
            $list_id = $property_record["list_id"];//ID списка - если свойство списковое
            $property_name = $property_record["value"];
            if(!in_array($property_name, array(
                /* Нужные свойства */
                "Артикул",
                "Производитель",
                "Состояние"
            ))){
                continue; // Пропускаем ненужные свойства
            }
            //Получаем значение данного свойства для товара:
            $table_postfix = $property_types_tables[(string)$property_record["property_type_id"]];//Постфикс таблицы
            $property_value_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_properties_values_'.$table_postfix.'` WHERE `product_id` = :product_id AND `property_id` = :property_id;');
            $property_value_query->bindValue(':product_id', $product_id);
            $property_value_query->bindValue(':property_id', $property_id);
            $property_value_query->execute();
            $property_value_query_count_rows = $property_value_query->fetchColumn();

            if($property_value_query_count_rows > 0)
            {
                $property_value_query = $db_link->prepare('SELECT `id`, `value` FROM `shop_properties_values_'.$table_postfix.'` WHERE `product_id` = :product_id AND `property_id` = :property_id;');
                $property_value_query->bindValue(':product_id', $product_id);
                $property_value_query->bindValue(':property_id', $property_id);
                $property_value_query->execute();
                
                //Задаем значение
                $property_value = '';
                switch($property_record["property_type_id"])
                {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                        $property_value_record = $property_value_query->fetch();
                        $property_value = $property_value_record["value"];
                        break;
                    case 5:
                        //Свойство списковое - значений может быть несколько
                        $list_property_items = array();
                        while($property_value_record = $property_value_query->fetch())
                        {
                            array_push($list_property_items, (integer)$property_value_record["value"]);
                        }
                        //Теперь получаем названия значений свойств из линейных списков
                        $line_list_items = array();
                        
                        $line_list_items_query = $db_link->prepare('SELECT `id`, `value` FROM `shop_line_lists_items` WHERE `line_list_id` = :line_list_id ORDER BY `order`;');
                        $line_list_items_query->bindValue(':line_list_id', $list_id);
                        $line_list_items_query->execute();
                        while( $list_item = $line_list_items_query->fetch() )
                        {
                            array_push($line_list_items, array("id"=>$list_item["id"], "value"=>$list_item["value"]) );
                        }
                        $line_list_values_text = "";//Текстовая строка для вывода значений линейного списка
                        for($L=0; $L < count($line_list_items); $L++)
                        {
                            if(array_search((integer)$line_list_items[$L]["id"], $list_property_items) !== false)
                            {
                                if($line_list_values_text != "") $line_list_values_text .= ", ";
                                $line_list_values_text .= $line_list_items[$L]["value"];
                            }
                        }
                        $property_value = $line_list_values_text;
                        break;
                    case 6:
                        //Свойство типа "Древовидный список" - значений может быть несколько
                        $list_property_items = array();
                        while($property_value_record = $property_value_query->fetch())
                        {
                            array_push($list_property_items, (int)$property_value_record["value"]);
                        }
                        //Переводим в строку:
                        $list_property_items = json_encode($list_property_items);
                        $list_property_items = str_replace( array("[", "]") , "", $list_property_items);
                        
                        //Теперь формируем строку для отображения значения данного свойства
                        //Данное свойство отображаем в виде цепочек для каждой ветви
                        $property_variants = array();//Массив для всех цепочек данного свойства
                        
                        $tree_items_query = $db_link->prepare('SELECT `id`, `value`, `level`, `count`, `parent` FROM `shop_tree_lists_items` WHERE `id` IN ('.$list_property_items.') ORDER BY `level` DESC, `order` ASC;');
                        $tree_items_query->execute();
                        while( $tree_item = $tree_items_query->fetch() )
                        {
                            $has_added = false;
                            for( $pv = 0 ; $pv < count($property_variants); $pv++)
                            {
                                if($property_variants[$pv][ 0 ]["parent"] == $tree_item["id"])
                                {
                                    array_unshift($property_variants[$pv], array("value"=>$tree_item["value"], "level"=>$tree_item["level"], "count"=>$tree_item["count"], "parent"=>$tree_item["parent"], "id"=>$tree_item["id"]) );
                                    
                                    $has_added = true;
                                }
                            }
                            if( ! $has_added )
                            {
                                array_unshift($property_variants, array());
                                
                                array_unshift($property_variants[ 0 ], array("value"=>$tree_item["value"], "level"=>$tree_item["level"], "count"=>$tree_item["count"], "parent"=>$tree_item["parent"], "id"=>$tree_item["id"]) );
                            }
                        }
                        $property_value_tmp = '';
                        //Если есть цепочки
                        for( $pv = 0 ; $pv < count($property_variants); $pv++)
                        {
                            if($pv > 0)
                            {
                                $propery_value_tmp .= "\n";
                            }
                            for( $pv_i = 0 ; $pv_i < count($property_variants[$pv]); $pv_i++)
                            {
                                if($pv_i > 0)
                                {
                                    $property_value_tmp .= " ";
                                }
                                $property_value_tmp .= $property_variants[$pv][$pv_i]["value"];
                            }
                        }
                        break;
                    $property_value = $property_value_tmp;
                }
                if($property_value){
                    $properties[] = $property_name . ": " . $property_value;
                }
            }//~if() - есть значение свойства
        }
        $properties[] = "Цена: " . ceil($product_record["customer_price"]) .' ₽';
        $content = "Название: " . $caption . "\n". implode("\n", $properties) . "\n\nПодробнее: " . $product_url;
        if($checkPost && $product['post_social']){
            // Пропускаем отправку, если ранее уже публиковали товар. Действует только если передавать вторым параметром true.
            return false;
        }
        $result = post_vk($content, $images);
        if(is_numeric($result)){
            $db_link->prepare("UPDATE shop_catalogue_products SET post_social=1 WHERE id=?")->execute(array($product_id)); // Помечаем, чтоб продукт уже публиковался. И в дальнейшем можно только через кнопку публиковать
            return true;
        }
        return isset($result['error']) && $result['error']['error_code'] == 5 ? 'Ошибка авторизации' : 'Не удалось создать пост';
    }
    return false;
}