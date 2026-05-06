<?php

/*

* Единый скрипт для генерации объектов товаров на основе унифицированных SQL-запросов

* Данный скрипт подключается после запроса в БД и перед вызовом универсальной функции печати объектов товаров

*/







/*

//ДАЛЕЕ ДЛЯ ОТЛАДКИ

//Функция замены первого вхождения строки

if(!function_exists('str_replace_once')){

	function str_replace_once($search, $replace, $text)

	{

	   $pos = strpos($text, $search);

	   return $pos!==false ? substr_replace($text, $replace, $pos, strlen($search)) : $text;

	}

}

//Боевой SQL-запрос присваем в $SQL_bebug, чтобы боевой остался без изменений, т.к. он будет далее использоваться в скрипте

$SQL_limit_bebug = $SQL_limit;

$SQL_bebug = $SQL;

//Цикл по массиву значений, которые нужно биндить

for( $i=0 ; $i < count($sql_args_array) ; $i++ )

{

	$SQL_limit_bebug = str_replace_once('?', $sql_args_array[$i], $SQL_limit_bebug);

	$SQL_bebug = str_replace_once('?', $sql_args_array[$i], $SQL_bebug);

}



//echo $SQL_limit_bebug;

//echo $SQL_bebug;



$log = fopen($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/sql_limit.log", "w");

fwrite($log, $SQL_limit_bebug);

fclose($log);



$log = fopen($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/sql.log", "w");

fwrite($log, $SQL_bebug);

fclose($log);

*/











//Делаем запрос на получение товаров и формируем массив объектов товаров

$products_objects = array();

$stmt = $db_link->prepare($SQL);

$stmt->execute($sql_args_array);

while( $product_record = $stmt->fetch() )

{

    //Если такого товара еще не было - создаем

    if( ! isset($products_objects[$product_record["id"]]) )

    {

        //Прямые поля

        $products_objects[$product_record["id"]] = array();

        $products_objects[$product_record["id"]]["id"] = $product_record["id"];

        $products_objects[$product_record["id"]]["caption"] = $product_record["caption"];

        $products_objects[$product_record["id"]]["alias"] = $product_record["alias"];



        $products_objects[$product_record["id"]]["image"] = "/content/files/images/products_images/".$product_record["file_name"];



        $products_objects[$product_record["id"]]["category_id"] = $product_record["category_id"];

        $products_objects[$product_record["id"]]["category_url"] = $product_record["category_url"];



        $products_objects[$product_record["id"]]["article"] = mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $product_record["article"]), "UTF-8");

        $products_objects[$product_record["id"]]["manufacturer"] = mb_strtoupper(trim($product_record["manufacturer"]), "UTF-8");



        //URL товара

        if($DP_Config->product_url == "id")

        {

            $products_objects[$product_record["id"]]["product_url"] = "/".$products_objects[$product_record["id"]]["category_url"]."/".$product_record["id"];

        }

        else

        {

            $products_objects[$product_record["id"]]["product_url"] = "/".$products_objects[$product_record["id"]]["category_url"]."/".$product_record["alias"];

        }



        //Стиль для HTML-блока

        $products_objects[$product_record["id"]]["main_class_of_block"] = $main_class_of_block;//!!!!!!!!!!!!!!!!

        //Тип блока (1,2,3,4)

        $products_objects[$product_record["id"]]["product_block_type"] = $product_block_type;//!!!!!!!!!!!!!!!!



        //Массивы

        $products_objects[$product_record["id"]]["storage_data"] = array();

        $products_objects[$product_record["id"]]["stickers"] = array();



        //ВСПОМОГАТЕЛЬНЫЕ ПОЛЯ ДЛЯ ВЫВОДА ПО 1, 4, 5, 6, 7

        $products_objects[$product_record["id"]]["min_price"] = 0;

        $products_objects[$product_record["id"]]["max_price"] = 0;

        $products_objects[$product_record["id"]]["prioritet1"] = NULL;

        $products_objects[$product_record["id"]]["prioritet2"] = NULL;

        $products_objects[$product_record["id"]]["prioritet3"] = NULL;

        $products_objects[$product_record["id"]]["prioritet4"] = NULL;



        //Оценки товара:

        $products_objects[$product_record["id"]]["mark"] = $product_record["mark"];//Средня оценка

        $products_objects[$product_record["id"]]["marks_count"] = $product_record["marks_count"];//Общее количество оценок

        $products_objects[$product_record["id"]]["mark_1"] = $product_record["mark_1"];//Количество ценок 1

        $products_objects[$product_record["id"]]["mark_2"] = $product_record["mark_2"];//Количество ценок 2

        $products_objects[$product_record["id"]]["mark_3"] = $product_record["mark_3"];//Количество ценок 3

        $products_objects[$product_record["id"]]["mark_4"] = $product_record["mark_4"];//Количество ценок 4

        $products_objects[$product_record["id"]]["mark_5"] = $product_record["mark_5"];//Количество ценок 5

    }







    //Заполняем массив складских записей

    if( isset($product_record["storage_id"]) )//Если есть складская запись. Т.Е. ЭТОТ БЛОК РАБОТАЕТ ТОЛЬКО ДЛЯ 1 и 4

    {

        if( !isset( $products_objects[$product_record["id"]]["storage_data"][$product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"]] ) )//Если такую запись еще не добавляли

        {

            //ОБРАБАТЫВАЕМ ОКРУГЛЕНИЕ ЦЕН

            $price = $product_record["customer_price"];

            if($DP_Config->price_rounding == '1')//Без копеечной части

            {

                if( $price != (int)$price )

                {

                    $price = (int)$price + 1;

                }

                else

                {

                    $price = (int)$price;

                }

            }

            else if($DP_Config->price_rounding == '2')//До 5 руб

            {

                $price = (integer)$price;

                $price_str = (string)$price;

                $price_str_last_char = (integer)$price_str[strlen($price_str)-1];

                if($price_str_last_char > 0 && $price_str_last_char < 5)

                {

                    $price = $price + (5 - $price_str_last_char);

                }

                else if($price_str_last_char > 5 && $price_str_last_char <= 9)

                {

                    $price = $price + (10 - $price_str_last_char);

                }

            }

            else if($DP_Config->price_rounding == '3')//До 10 руб

            {

                $price = (integer)$price;

                $price_str = (string)$price;

                $price_str_last_char = (integer)$price_str[strlen($price_str)-1];

                if($price_str_last_char != 0)

                {

                    $price = $price + (10 - $price_str_last_char);

                }

            }

            $product_record["customer_price"] = $price;







            //ОБРАБАТЫВАЕМ СРОК ПОСТАВКИ

            $additional_time = (int) $product_record["additional_time"];//Дополнительный срок поставки склада в часах

            if(time() < $product_record["arrival_time"]){

                $time_to_exe = (int)((($product_record["arrival_time"] + ($additional_time * 3600)) - time()) / 86400);

            }else{

                if($product_record["time_to_exe"] > 0){

                    $time_to_exe = $product_record["time_to_exe"] + ((int)($additional_time / 24));

                }else{

                    $time_to_exe = ((int)($additional_time / 24));

                }

            }

            $product_record["time_to_exe"] = $time_to_exe;







            //ВНОСИМ САМУ ЗАПИСЬ МАГАЗИН-СКЛАД-ПОСТАВКА

            $products_objects[$product_record["id"]]["storage_data"][$product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"]] = array("office_id" =>$product_record["office_id"], "storage_id"=>$product_record["storage_id"], "record_id"=>$product_record["storage_record_id"], "customer_price"=>$product_record["customer_price"], "price"=>$product_record["price"], "price_crossed_out"=>$product_record["price_crossed_out"], "price_purchase"=>$product_record["price_purchase"], "arrival_time"=>$product_record["arrival_time"], "time_to_exe"=>$product_record["time_to_exe"], "exist"=>$product_record["exist"], "reserved"=>$product_record["reserved"], "issued"=>$product_record["issued"], "additional_time"=>$product_record["additional_time"]);







            //ОБРАБОТКА ВСПОМОГАТЕЛЬНЫХ ПОЛЕЙ

            //Минимальная цена

            if($products_objects[$product_record["id"]]["min_price"] == 0)

            {

                $products_objects[$product_record["id"]]["min_price"] = $product_record["customer_price"];

            }

            else if($product_record["customer_price"] < $products_objects[$product_record["id"]]["min_price"])

            {

                $products_objects[$product_record["id"]]["min_price"] = $product_record["customer_price"];

            }

            //Максимальная цена

            if($products_objects[$product_record["id"]]["max_price"] == 0)

            {

                $products_objects[$product_record["id"]]["max_price"] = $product_record["customer_price"];

            }

            else if($product_record["customer_price"] > $products_objects[$product_record["id"]]["max_price"])

            {

                $products_objects[$product_record["id"]]["max_price"] = $product_record["customer_price"];

            }







            //ОБРАБОТКА ПРИОРИТЕТОВ. ПРЯМАЯ КНОПКА КУПИТЬ ВОЗМОЖНА ТОЛЬКО ПРИ ВСЕХ ОДИНАКОВЫХ ЦЕНАХ

            if($products_objects[$product_record["id"]]["min_price"] == $products_objects[$product_record["id"]]["max_price"])

            {

                if($product_record["exist"] > 0)//Есть наличие

                {

                    if( $product_record["time_to_exe"] == 0 )//Поставка пришла. т.е. уже в магазине

                    {

                        //ДОБАВЛЯЕМ ПЕРВЫЙ ПРИОРИТЕТ (ЕСЛИ ЕЩЕ НЕ ДОБАВИЛИ)

                        if($products_objects[$product_record["id"]]["prioritet1"] == NULL)

                        {

                            $products_objects[$product_record["id"]]["prioritet1"] = $product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"];

                        }

                    }

                    else//Поставка не пришла

                    {

                        //ДОБАВЛЯЕМ ВТОРОЙ ПРИОРИТЕТ (ЕСЛИ ЕЩЕ НЕ ДОБАВИЛИ)

                        if($products_objects[$product_record["id"]]["prioritet2"] == NULL)

                        {

                            $products_objects[$product_record["id"]]["prioritet2"] = $product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"];

                        }

                    }

                }

                else//Наличия нет

                {

                    if( $product_record["reserved"] > 0 )//Есть зарезервированный товар

                    {

                        //ДОБАВЛЯЕМ ТРЕТИЙ ПРИОРИТЕТ (ЕСЛИ ЕЩЕ НЕ ДОБАВИЛИ)

                        if($products_objects[$product_record["id"]]["prioritet3"] == NULL)

                        {

                            $products_objects[$product_record["id"]]["prioritet3"] = $product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"];

                        }

                    }

                    else//Нет даже зарезервированного товара

                    {

                        //ДОБАВЛЯЕМ ТРЕТИЙ ЧЕТВЕРТЫЙ (ЕСЛИ ЕЩЕ НЕ ДОБАВИЛИ)

                        if($products_objects[$product_record["id"]]["prioritet4"] == NULL)

                        {

                            $products_objects[$product_record["id"]]["prioritet4"] = $product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"];

                        }

                    }

                }

            }

        }

    }







    //НАЛИЧИЕ НЕСКОЛЬКИХ ДОСТУПНЫХ СКЛАДСКИХ ЗАПИСЕЙ

    $products_objects[$product_record["id"]]["variants"] = false;

    if(count($products_objects[$product_record["id"]]["storage_data"]) > 1){

        $products_objects[$product_record["id"]]["variants"] = true;

    }







    //ЗАПОЛНЯЕМ СТИКЕРЫ

    if($product_record["sticker_id"] != NULL)

    {

        if( ! isset($products_objects[$product_record["id"]]["stickers"]["s_".$product_record["sticker_id"]])  )

        {

            $products_objects[$product_record["id"]]["stickers"]["s_".$product_record["sticker_id"]] = array("id"=>$product_record["sticker_id"], "value"=>$product_record["sticker_value"], "color_text"=>$product_record["sticker_color_text"], "color_background"=>$product_record["sticker_color_background"], "href"=>$product_record["sticker_href"], "class_css"=>$product_record["sticker_class_css"], "description"=>$product_record["sticker_description"]);

        }

    }







    //КНОПКА ПРОЦЕНКИ

    $products_objects[$product_record["id"]]["article_button"] = '';

    if(!empty($products_objects[$product_record["id"]]["article"])){

        if($DP_Config->chpu_search_config["chpu_search_on"] === true){

            if(!empty($products_objects[$product_record["id"]]["manufacturer"])){

                $url = '/parts/'. urlencode($products_objects[$product_record["id"]]["manufacturer"]) .'/'. urlencode($products_objects[$product_record["id"]]["article"]);

            }else{

                $url = '/parts/brands/'. urlencode($products_objects[$product_record["id"]]["article"]);

            }

        }else{

            if(!empty($products_objects[$product_record["id"]]["manufacturer"])){

                $url = '/shop/part_search?brend='. urlencode($products_objects[$product_record["id"]]["manufacturer"]) .'&article='. urlencode($products_objects[$product_record["id"]]["article"]);

            }else{

                $url = '/shop/part_search?article='. urlencode($products_objects[$product_record["id"]]["article"]);

            }

        }

        $products_objects[$product_record["id"]]["article_button"] = '<a target="_blank" title="Посмотреть все предложения поставщиков по данному товару" href="'.$url.'">Все цены <i class="fa fa-search"></i></a>';

    }







    //КНОПКА ДЛЯ АДМИНИСТРАТОРА И КЛАДОВЩИКА. А ДЛЯ ПОКУПАТЕЛЯ - ДАЛЬШЕ

    switch($product_block_type)

    {

        case 2:

            $products_objects[$product_record["id"]]["button"] = "
    <div style=\"display: flex; flex-direction: column; gap: 20px;\">
            <a href=\"/".$DP_Config->backend_dir."/content/label/generate-label.php?product_id=". $product_record["id"]. "\" class=\"btn btn-ar btn-success\" target='_blank' title='Печать этикетки' style='position: absolute; right: 180px;'><i class=\"fas fa-print\"></i></a>

        <a class=\"btn btn-ar btn-primary\" href=\"/".$DP_Config->backend_dir."/shop/catalogue/products/product?category_id=".$product_record["category_id"]."&product_id=".$product_record["id"]."\">Редактировать</a>
        <a class=\"btn btn-ar btn-primary\" onclick=\"post_social(this, {$product_record["id"]})\">Опубликовать в ВК</a>
    </div>

			<script>
				function post_social(self, product_id)
				{
					$(self).text('Идет публикация...');
					$.post('".$DP_Config->domain_path . "/" . $DP_Config->backend_dir ."/content/content/ajax_post_social.php', {
						product_id: product_id,
						code: '".$DP_Config->secret_succession."'
					}, function(res){
						$(self).text('Опубликовать в ВК');
						alert(res.message);
					}).fail(function(res){
						$(self).text('Опубликовать в ВК');
						alert('Ошибка выполнения запроса');
					});
				}
			</script>
			";

            break;

        case 3:

            $products_objects[$product_record["id"]]["button"] = "<a class=\" btn btn-ar btn-primary\" href=\"/".$DP_Config->backend_dir."/shop/logistics/stock/product?product_id=".$product_record["id"]."\">Управление</a>";

            break;

    }

}











//После получения всей информации из БД - определяем отображение для покупателя

if( $product_block_type == 1 || $product_block_type == 4 || $product_block_type == 5 || $product_block_type == 6 || $product_block_type == 7 )

{

    foreach( $products_objects AS $product_id => $product )

    {

        $storage_data = $product["storage_data"];



        if( $product["prioritet1"] != NULL )//ПРИОРИТЕТ 1

        {

            $div_id = $product["prioritet1"];

            $exist = (int) $product["storage_data"][$div_id]["exist"];

            $time_to_exe = (int) $product["storage_data"][$div_id]["time_to_exe"];

            $min_order = 1;



            $products_objects[$product_id]["cart_suggestion"] = $product["prioritet1"];//Объект для добавления в корзину. Т.е. ключ в списке всех предложений

            $products_objects[$product_id]["exist_info_variant"] = '<span class="green">В наличии</span> <span class="exist">'.$exist.' шт.</span>';//Пометка о наличии



            $products_objects[$product_id]["button"] = '<div class="btn-ar btn-primary cart_btn_purchase_action">

				<table><tr><td><div class="product_div_count_need"><a class="count_need_minus" href="javascript:void(0);" onclick="minusCountNeed(\''.$div_id.'\', '.$exist.', '.$min_order.');">-</a><input class="count_need_input count_need_'.$div_id.'" type="text" value="'.$min_order.'" onchange="onKeyUpCountNeed(\''.$div_id.'\', '.$exist.', '.$min_order.');"/><a class="count_need_plus" href="javascript:void(0);" onclick="plusCountNeed(\''.$div_id.'\', '.$exist.', '.$min_order.');">+</a></div></td><td><a href="javascript:void(0);" onclick="purchase_action(\''.$div_id.'\');">В корзину</a></td></tr></table>

			</div>';

        }

        else if( $product["prioritet2"] != NULL )//ПРИОРИТЕТ 2

        {

            $div_id = $product["prioritet2"];

            $exist = (int) $product["storage_data"][$div_id]["exist"];

            $time_to_exe = (int) $product["storage_data"][$div_id]["time_to_exe"];

            $min_order = 1;



            $products_objects[$product_id]["cart_suggestion"] = $product["prioritet2"];//Объект для добавления в корзину

            $products_objects[$product_id]["exist_info_variant"] = '<span class="orange">Срок '. $time_to_exe .' дн.</span> <span class="exist">'.$exist.' шт.</span>';//Пометка о наличии



            $products_objects[$product_id]["button"] = '<div class="btn-ar btn-primary cart_btn_purchase_action">

				<table><tr><td><div class="product_div_count_need"><a class="count_need_minus" href="javascript:void(0);" onclick="minusCountNeed(\''.$div_id.'\', '.$exist.', '.$min_order.');">-</a><input class="count_need_input count_need_'.$div_id.'" type="text" value="'.$min_order.'" onchange="onKeyUpCountNeed(\''.$div_id.'\', '.$exist.', '.$min_order.');"/><a class="count_need_plus" href="javascript:void(0);" onclick="plusCountNeed(\''.$div_id.'\', '.$exist.', '.$min_order.');">+</a></div></td><td><a href="javascript:void(0);" onclick="purchase_action(\''.$div_id.'\');">В корзину</a></td></tr></table>

			</div>';

        }

        else if( $product["prioritet3"] != NULL )//ПРИОРИТЕТ 3

        {

            $div_id = $product["prioritet2"];

            $exist = (int) $product["storage_data"][$div_id]["exist"];

            $time_to_exe = (int) $product["storage_data"][$div_id]["time_to_exe"];

            $min_order = 1;



            $products_objects[$product_id]["cart_suggestion"] = $product["prioritet3"];//Хотя, в корзину добавлять нечего

            $products_objects[$product_id]["exist_info_variant"] = '<span class="blue">В резерве</span>';//Пометка о наличии



            $products_objects[$product_id]["button"] = '

			<div class="btn-ar btn-primary">

				<table>

					<tr>

						<td>

							<a href="'.$products_objects[$product_id]["product_url"].'" target="_blank">Подробнее</a>

						</td>

					</tr>

				</table>

			</div>';

        }

        else//ПРИОРИТЕТ 4

        {

            $products_objects[$product_id]["cart_suggestion"] = $product["prioritet4"];//Хотя, в корзину добавлять нечего

            $products_objects[$product_id]["exist_info_variant"] = '<span class="red">Под заказ</span>';//Пометка о наличии



            $products_objects[$product_id]["button"] = '

			<div class="btn-ar btn-primary">

				<table>

					<tr>

						<td>

							<a href="'.$products_objects[$product_id]["product_url"].'" target="_blank">Подробнее</a>

						</td>

					</tr>

				</table>

			</div>';



            /*

            //Если есть кнопка проценки

            if(!empty($products_objects[$product_id]["article_button"])){

                $products_objects[$product_id]["button"] = '

                <div class="btn-ar btn-primary">

                    <table>

                        <tr>

                            <td>

                                '. $products_objects[$product_id]["article_button"] .'

                            </td>

                        </tr>

                    </table>

                </div>';

                $products_objects[$product_id]["article_button"] = '';//Убираем что бы не выводить повторно

            }

            */

        }







        //Далее то, что не зависит от приоритетов:

        //Цена

        if(!empty($storage_data[$products_objects[$product_id]["cart_suggestion"]]["customer_price"])){

            $price_string = $storage_data[$products_objects[$product_id]["cart_suggestion"]]["customer_price"];

            if( $price_string == (int)$price_string )

            {

                $price_string = number_format($price_string, 0, ',', ' ');

            }else{

                $price_string = number_format($price_string, 2, ',', ' ');

            }

            $products_objects[$product_id]["price"] = "<font class=\"price\">".$price_string."</font>";

        }

        //Цена зачеркнутая

        $products_objects[$product_id]["price_crossed_out"] = "";

        if($storage_data[$products_objects[$product_id]["cart_suggestion"]]["price_crossed_out"] > 0)

        {

            $price_string = $storage_data[$products_objects[$product_id]["cart_suggestion"]]["price_crossed_out"];

            if( $price_string == (int)$price_string )

            {

                $price_string = number_format($price_string, 0, ',', ' ');

            }else{

                $price_string = number_format($price_string, 2, ',', ' ');

            }

            $products_objects[$product_id]["price_crossed_out"] = '<font class="price">'.$price_string.'</font>';

        }

        //Указатель валюты

        if(!empty($storage_data[$products_objects[$product_id]["cart_suggestion"]]["customer_price"])){

            if($DP_Config->currency_show_mode == "sign_before")

            {

                $products_objects[$product_id]["price"] = "<font class=\"currency\">$currency_indicator</font> ".$products_objects[$product_id]["price"];



                if($products_objects[$product_id]["price_crossed_out"] != "")

                {

                    $products_objects[$product_id]["price_crossed_out"] = "<font class=\"currency\">$currency_indicator</font> ".$products_objects[$product_id]["price_crossed_out"];

                }

            }

            else

            {

                $products_objects[$product_id]["price"] = $products_objects[$product_id]["price"]." <font class=\"currency\">$currency_indicator</font>";



                if($products_objects[$product_id]["price_crossed_out"] != "")

                {

                    $products_objects[$product_id]["price_crossed_out"] = $products_objects[$product_id]["price_crossed_out"]." <font class=\"currency\">$currency_indicator</font>";

                }

            }

        }







        //Если несколько складских записей

        if( $product["variants"] == true )

        {

            $products_objects[$product_id]["price"] = "от ". $products_objects[$product_id]["price"];

            $products_objects[$product_id]["price_crossed_out"] = "";//Цена зачеркнутая

            $products_objects[$product_id]["exist_info_variant"] = '<span class="green">Несколько вариантов</span>';//Пометка о наличии

            $products_objects[$product_id]["button"] = '

			<div class="btn-ar btn-primary">

				<table>

					<tr>

						<td>

							<a href="'.$products_objects[$product_id]["product_url"].'" target="_blank">Подробнее</a>

						</td>

					</tr>

				</table>

			</div>';

        }







        //Указываем проверочный хеш для предотвращения подмены данных злоумышленниками через Javascript

        $products_objects[$product_id]["storage_data"][$products_objects[$product_id]["cart_suggestion"]]["check_hash"] = md5($product_id.$storage_data[$products_objects[$product_id]["cart_suggestion"]]["office_id"].$storage_data[$products_objects[$product_id]["cart_suggestion"]]["storage_id"].$storage_data[$products_objects[$product_id]["cart_suggestion"]]["record_id"].$storage_data[$products_objects[$product_id]["cart_suggestion"]]["customer_price"].$DP_Config->tech_key);



    }

}

?>