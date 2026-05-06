<?php
/**
 * Вывести страницу о продукте
 * Используется для вывода страниц для покупателей и кладовщиков через require_once
*/
defined('_ASTEXE_') or die('No access');


//Постфиксы таблиц значений свойств - зависят от типа свойства
$property_types_tables = array("1"=>"int", "2"=>"float", "3"=>"text", "4"=>"bool", "5"=>"list", "6"=>"tree_list");


if($product_id == 0)
{
    $product_id = $_REQUEST["product_id"];
}


$products_images_dir = "/content/files/images/products_images/";


$product_query = $db_link->prepare('SELECT `caption`, `category_id` FROM `shop_catalogue_products` WHERE `id` = :id;');
$product_query->bindValue(':id', $product_id);
$product_query->execute();
$product_record = $product_query->fetch();
$category_id = $product_record["category_id"];


if( $isFrontMode )
{
?>
	<link href="/lib/Lightbox/css/lightbox.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="/lib/Lightbox/js/lightbox.js"></script>
	<div class="col-md-5">
		<?php
		$images_list = array();
		$images_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_products_images` WHERE `product_id` = :product_id;');
		$images_query->bindValue(':product_id', $product_id);
		$images_query->execute();
		$count_rows = $images_query->fetchColumn();

		if($count_rows > 0)//Есть изображение
		{
			$images_query = $db_link->prepare('SELECT `id`,`file_name` FROM `shop_products_images` WHERE `product_id` = :product_id;');
			$images_query->bindValue(':product_id', $product_id);
			$images_query->execute();
			
			while( $image = $images_query->fetch() )
			{
				$src = '/content/shop/catalogue/watermark.php?image_id='.$image["id"];
				array_push($images_list, $src);
			}
		}
		else//Изображений нет
		{
			array_push($images_list, "/content/files/images/no_image.png");
		}
		?>
		
		<div class="div_product_img_big">
			<a href="<?=$images_list[0];?>" rel="lightbox-product"><img src="<?php echo $images_list[0];?>"/></a>
		</div>
		
		<p style="text-align:center;"><small style="color:#999; font-size: 75%;">Кликните на картинку для увеличения</small></p>
		
		<div class="div_product_img_small">
			<?php
			for($i=1; $i < count($images_list); $i++)
			{
				?>
				<a href="<?=$images_list[$i];?>" rel="lightbox-product" style="background:url('<?=$images_list[$i];?>') #fff;"></a>
				<?php
			}
			?>
		</div>
		
	</div>
	<div class="col-md-7">
		<div class="div_product_price">
		<?php
			//Подключение построение запроса
			$products_ids_str = $product_id;
			require($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_all.php");
			require($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_show.php");
			$stmt = $db_link->prepare($SQL);
			$stmt->execute($sql_args_array);
			
			$product_offers = $stmt->fetch();
			
			if(1){
				$min_order = 1;
				$price = $product_offers["customer_price"];
				$price_crossed_out = $product_offers["price_crossed_out"];
				$exist = $product_offers["exist"];
				$office_id = $product_offers["office_id"];
				$storage_id = $product_offers["storage_id"];
				$storage_record_id = $product_offers["storage_record_id"];
				
				$article = mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $product_offers["article"]), "UTF-8");
				$manufacturer = mb_strtoupper(trim($product_offers["manufacturer"]), "UTF-8");
                $condition = mb_strtoupper(trim($product_offers["cond"]), "UTF-8");

				$div_id = $office_id."_".$storage_id."_".$storage_record_id;
				
				//ОБРАБАТЫВАЕМ СРОК ПОСТАВКИ
				$additional_time = (int) $product_offers["additional_time"];//Дополнительный срок поставки склада в часах
				if(time() < $product_offers["arrival_time"]){
					$time_to_exe = (int)((($product_offers["arrival_time"] + ($additional_time * 3600)) - time()) / 86400);
				}else{
					if($product_offers["time_to_exe"] > 0){
						$time_to_exe = $product_offers["time_to_exe"] + ((int)($additional_time / 24));
					}else{
						$time_to_exe = ((int)($additional_time / 24));
					}
				}
				$product_offers["time_to_exe"] = $time_to_exe;
				
				//ОБРАБАТЫВАЕМ ОКРУГЛЕНИЕ ЦЕН
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
				
				//Кнопка проценки
				$article_button = '';
				if(!empty($article)){
					if($DP_Config->chpu_search_config["chpu_search_on"] === true){
						if(!empty($manufacturer)){
							$url = '/parts/'. urlencode($manufacturer) .'/'. urlencode($article);
						}else{
							$url = '/parts/brands/'. urlencode($products_objects[$product_record["id"]]["article"]);
						}
					}else{
						if(!empty($manufacturer)){
							$url = '/shop/part_search?brend='. urlencode($manufacturer) .'&article='. urlencode($article);
						}else{
							$url = '/shop/part_search?article='. urlencode($article);
						}
					}
					$article_button = '<a target="_blank" title="Посмотреть все предложения поставщиков по данному товару" href="'.$url.'">Все цены <i class="fa fa-search"></i></a>';
				}
				
				?>
				<div class="price_div">
					<div class="price_div_header">Цена:</div>
					<div class="price_div_text">
						<?php 
						if($price > 0){
							$price_string = $price;
							if( $price_string == (int)$price_string )
							{
								$price_string = number_format($price_string, 0, ',', ' ');
							}else{
								$price_string = number_format($price_string, 2, ',', ' ');
							}
							
							$price_crossed_out_string = $price_crossed_out;
							if( $price_crossed_out_string == (int)$price_crossed_out_string )
							{
								$price_crossed_out_string = number_format($price_crossed_out_string, 0, ',', ' ');
							}else{
								$price_crossed_out_string = number_format($price_crossed_out_string, 2, ',', ' ');
							}
							
							if($DP_Config->currency_show_mode == "sign_before")
							{
								echo $currency_indicator .' '. $price_string;
								
								if(!empty($price_crossed_out) && $price < $price_crossed_out){
									echo '<span class="product_div_tile"><span class="product_div_price_crossed_out">'. $currency_indicator .' '. $price_crossed_out_string .'</span></span>';
								}
							}
							else
							{
								echo number_format($price, 2, '.', ' ') .' '. $currency_indicator;
								
								if(!empty($price_crossed_out) && $price < $price_crossed_out){
									echo '<span class="product_div_tile"><span class="product_div_price_crossed_out">'. $price_crossed_out_string .' '. $currency_indicator .'</span></span>';
								}
							}
						}else{
							echo 'Уточните<span class="hidden-xs"> у менеджера</span>';
						}
						?>
					</div>
				</div>
				<?php
				$properties_query = $db_link->prepare('SELECT * FROM `shop_categories_properties_map` WHERE `value`="Магазин" AND `category_id` = :category_id ORDER BY `order` ASC;');
				$properties_query->bindValue(':category_id', $category_id);
				$properties_query->execute();
				$properties_list = array();
				$propertyStore = '';
				$property_record = $properties_query->fetch();
				if($property_record){
					$table_postfix = $property_types_tables[(string)$property_record["property_type_id"]];//Постфикс таблицы
					$property_value_query = $db_link->prepare('SELECT * FROM `shop_properties_values_'.$table_postfix.'` WHERE `product_id` = :product_id AND `property_id` = :property_id;');
					$property_value_query->bindValue(':product_id', $product_id);
					$property_value_query->bindValue(':property_id', $property_record['id']);
					$property_value = $property_value_query->execute() ? $property_value_query->fetch() : false;
					if($property_value && $property_record['property_type_id'] == 6){
						$tree_items_query = $db_link->prepare('SELECT `id`, `value`, `level`, `count`, `parent` FROM `shop_tree_lists_items` WHERE `id` = ? LIMIT 1');
						if($tree_items_query->execute(array($property_value['value']))){
							$tree_record = $tree_items_query->fetch();
							if($tree_record){
								$propertyStore = $tree_record['value'];
							}
						}
					}
				}
				?>
				<?if($propertyStore):?>
					<div class="office_info_div">
						<div class="ooffice_info_div_header">Магазин:</div>
						<div class="office_info_div_text">
							<?php
							/*
							if(empty($product_offers["office_id"])){
								$product_offers["office_id"] = $customer_offices[0];
							}
							$office_query = $db_link->prepare('SELECT * FROM `shop_offices` WHERE `id` = ?;');
							$office_query->execute(array($product_offers["office_id"]));
							$office_info = $office_query->fetch(PDO::FETCH_ASSOC);
							?>
							<span><?php echo trim($office_info['caption']) .'<br/><small>'. trim($office_info['city']) .', '. trim($office_info['address']) .'</small>'; ?></span>
							*/
							?>
							<span><?=$propertyStore?></span>
						</div>
					</div>
				<?endif;?>
				
				<div class="product_div_manufacturer">
					<?php echo $manufacturer; ?>
				</div>
				
				<div class="product_div_article">
					<?php echo $article; ?>
				</div>

                <div>
                    <b>Состояние: </b><?php echo $condition; ?>
                </div>

                <div class="product_div_article_button">
					<?php echo $article_button; ?>
				</div>
				
				<div class="product_div_tile">
					<div class="product_div_exist_info">
						<?php
						
						?> 
						
						<?php
						if($exist > 0){
							if($product_offers["time_to_exe"] == 0){
								echo '<span class="green">В наличии</span>';
							}else{
								echo '<span class="orange">Срок '. $product_offers["time_to_exe"] . ' дн.</span>';
							}
							echo '<span class="exist">'. $exist .' шт.</span>';
						}else{
							if($product_offers["reserved"] > 0){
								echo '<span class="blue">В резерве</span>';
							}else{
								echo '<span class="red">Под заказ</span>';
							}
						}
						?>
					</div>
				</div>
				
				<div class="btn_cart_div">
					<div class="btn_cart_div_header"></div>
					<div class="btn_cart_div_text">
						<?php
						if($exist > 0){
							?>
							<div class="btn-ar btn-primary cart_btn_purchase_action">
								<table><tr><td><div class="product_div_count_need"><a class="count_need_minus" href="javascript:void(0);" onclick="minusCountNeed('<?php echo $div_id; ?>', <?php echo $exist; ?>, <?php echo $min_order; ?>);">-</a><input class="count_need_input count_need_<?php echo $div_id; ?>" type="text" value="<?php echo $min_order; ?>" onchange="onKeyUpCountNeed('<?php echo $div_id; ?>', <?php echo $exist; ?>, <?php echo $min_order; ?>);"/><a class="count_need_plus" href="javascript:void(0);" onclick="plusCountNeed('<?php echo $div_id; ?>', <?php echo $exist; ?>, <?php echo $min_order; ?>);">+</a></div></td><td><a href="javascript:void(0);" onclick="purchase_action('<?php echo $div_id; ?>');">В корзину</a></td></tr></table>
							</div>
							<?php
						}else{
							?>
							<div class="btn-ar btn-primary cart_btn_purchase_action">
								<table><tr><td><div class="product_div_count_need"></div></td><td><a href="/zapros-prodavczu" target="_blank">Запрос продавцу</a></td></tr></table>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
			
			<?php
			//Для работы с пользователем
			require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );
			$user_session = DP_User::getUserSession();
			?>
			
			
			<div id="evaluations_general_mark_home"></div>
			<script>
			//Оценка
			function getGeneralMark1()
			{
				console.log('getGeneralMark1');
				jQuery.ajax({
					type: "POST",
					async: true,
					url: "/content/shop/catalogue/evaluations/ajax_get_product_general_mark.php",
					dataType: "json",
					data: "product_id=<?php echo $product_id; ?>"+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
					success: function(answer)
					{
						if(answer.status == true)
						{
							var general_mark_html = "";
							general_mark_html += "<div class=\"evaluations_mark\">";
								for(var i=0; i < 5; i++)
								{
									if( answer.general_mark < i+1 )
									{
										general_mark_html += "<i class=\"fa fa-star-o em-primary \"></i> ";
									}
									else
									{
										general_mark_html += "<i class=\"fa fa-star em-primary \"></i> ";
									}
								}
							general_mark_html += "</div>";
							if(document.getElementById("evaluations_general_mark_home")){
								document.getElementById("evaluations_general_mark_home").innerHTML = general_mark_html;
							}
						}
					}
				});
			}
			getGeneralMark1();
			</script>
			
			
			
			<?php
			if(DP_User::isAdmin()){
				?>
				<div class="product_div_admin">
					<a href="<?=$DP_Config->domain_path . $DP_Config->backend_dir;?>/shop/catalogue/products/product?category_id=<?=$category_id;?>&product_id=<?=$product_id;?>" target="_blank"><i class="fa fa-external-link"></i></a>
				</div>
				<?php
			}
			?>
			
			
			
			<?php
			//Закладки
			//Получаем закладки
			$in_bookmarks = false;//Флаг - данный товар добавлен в закладки
			$bookmarks = NULL;
			if( isset($_COOKIE["bookmarks"]) )
			{
				$bookmarks = json_decode($_COOKIE["bookmarks"], true);
			}
			if($bookmarks != NULL)//Есть закладки. Определяем - находится ли данный товар в закладках
			{
				if( array_search($product["id"], $bookmarks) !== false )
				{
					$in_bookmarks = true;
				}
			}
			if($in_bookmarks)//Этот товар в закладках
			{
				if($product["product_block_type"] == 6)//Страница закладок
				{
					?>
					<div class="product_div_bookmark">
						<a href="javascript:void(0);" onclick="removeBookmark(<?php echo $product["id"]; ?>, this);" title="Удалить из закладок">
							<i class="fa fa-remove"></i>
							<span>
								Удалить
							</span>
						</a>
					</div>
					<?php
				}
				else//Для остальных страниц - переход на страницу закладок
				{
					?>
					<div class="product_div_bookmark">
						<a href="javascript:void(0);" onclick="location = '/shop/zakladki';" title="Перейти в закладки">
							<i class="fa fa-bookmark"></i>
							<span>
								В закладках
							</span>
						</a>
					</div>
					<?php
				}
			}
			else//Этого товара нет в закладках
			{
				?>
				<div class="product_div_bookmark">
					<a href="javascript:void(0);" onclick="addToBookmarks(<?php echo $product_id; ?>, this);" title="Добавить в закладки">
						<i class="fa fa-bookmark-o"></i>
						<span>
							В закладки
						</span>
					</a>
				</div>
				<?php
			}
			?>
			
			
			
			<?php
			//Ссылка "К сравнению"
			//Получаем товары в сравнениях
			$in_compare = false;//Флаг - данный товар добавлен в сравнения
			$compare = NULL;
			if( isset($_COOKIE["compare"]) )
			{
				$compare = json_decode($_COOKIE["compare"], true);
			}
			if($compare != NULL)//Есть сравнения. Определяем - находится ли данный товар в сравнениях
			{
				if( array_search($product["id"], $compare) !== false )
				{
					$in_compare = true;
				}
			}
			if($in_compare)//Этот товар в сравнениях
			{
				if($product["product_block_type"] == 7)//Страница сравнений
				{
					?>
					<div class="product_div_compare">
						<a href="javascript:void(0);" onclick="removeCompare(<?php echo $product_id; ?>, this);" title="Удалить из сравнений">
							<i class="fa fa-remove"></i>
							<span>
								Удалить
							</span>
						</a>
					</div>
					<?php
				}
				else//Для остальных страниц - переход на страницу сравнений
				{
					?>
					<div class="product_div_compare">
						<a href="javascript:void(0);" onclick="location = '/shop/sravneniya'; " title="На страницу сравненения товаров">
							<i class="glyphicon glyphicon-duplicate"></i>
							<span>
								В сравнениях
							</span>
						</a>
					</div>
					<?php
				}
			}
			else
			{
				?>
				<div class="product_div_compare">
					<a href="javascript:void(0);" onclick="addToCompare(<?php echo $product_id; ?>, this);" title="Добавить к сравнению">
						<i class="fa fa-copy fa-flip-horizontal"></i>
						<span>
							К сравнению
						</span>
					</a>
				</div>
				<?php
			}
			?>
			
		</div>
		
		
		<div style="color:#999; font-size: 75%; text-align:center; line-height: 1.3em; margin-top: 8px; margin-bottom: 5px;">
			Цена действительна только для интернет-магазина и может отличаться от цен в розничных магазинах
		</div>
		
	</div>
	<?php
}
else
{
	?>
	
	<?php
	//После вывовода страницы - динамически меняем высоту блока, т.к. он position:relative - Используется для BACKEND
	?>
	<script>
	function setBlocksHeight()
	{
		//Получаем высоты:
		var product_genaral_info_HEIGHT = jQuery("#product_genaral_info_div").height();
		var product_galery_HEIGHT = jQuery("#main_image").height() + jQuery("#all_product_images_div").height();
		
		if(product_galery_HEIGHT > product_genaral_info_HEIGHT)
		{
			jQuery("#product_info_wrap_div").height(parseInt(product_galery_HEIGHT));
		}
		else
		{
			jQuery("#product_info_wrap_div").height(parseInt(product_genaral_info_HEIGHT));
		}
	}

	</script>
	
	<div class="product_info_wrap" id="product_info_wrap_div">

		<div class="product_galery" id="product_galery_div">
			<div class="main_image" id="main_image_div">
				<?php
				$images_query = $db_link->prepare('SELECT `id`,`file_name` FROM `shop_products_images` WHERE `product_id` = :product_id;');
				$images_query->bindValue(':product_id', $product_id);
				$images_query->execute();
				$image_main = $images_query->fetch();
				if($image_main != false)//Есть изображение
				{
					$src = $products_images_dir.$image_main["file_name"];
					$current_image_id = $image_main["id"];//Для текущего изображения
					//Указатель в ноль:
					$images_query->execute();
					$image_main = $images_query->fetch();
				}
				else//Изображений нет
				{
					$src = "/content/files/images/no_image.png";
					$current_image_id = 0;//Для текущего изображения
				}
				?>
				<img onload="setBlocksHeight();" id="main_image" src="<?php echo $src; ?>" />
			</div>
			
			<div class="all_product_images" id="all_product_images_div">
				<?php
				while($image = $images_query->fetch())
				{
					$sub_class = "other_image";
					if($i==0) $sub_class = "current_image";
					?>
					<img onload="setBlocksHeight();" onclick="selectImage(<?php echo $image["id"]; ?>,'<?php echo $products_images_dir.$image["file_name"]; ?>');" id="product_image_select_<?php echo $image["id"]; ?>" class="product_image_select <?php echo $sub_class; ?>" src="<?php echo $products_images_dir.$image["file_name"]; ?>" />
					<?php
				}
				?>
			</div>
		</div>
		
		
		<div class="product_genaral_info" id="product_genaral_info_div">
			<?php
			$text_content = "Текстовое описание";
			$text_content_query = $db_link->prepare('SELECT `content` FROM `shop_products_text` WHERE `product_id` = :product_id;');
			$text_content_query->bindValue(':product_id', $product_id);
			$text_content_query->execute();
			$text_content_record = $text_content_query->fetch();
			if($text_content_record != false)
			{
				$text_content = $text_content_record["content"];
			}
			
			
			//ВЫВОДИМ СВОЙСТВА ТОВАРА
			?>
			<table>
			<?php
			//Получаем основные свойства товара по category_id
			$properties_query = $db_link->prepare('SELECT * FROM `shop_categories_properties_map` WHERE `category_id` = :category_id ORDER BY `order` ASC;');
			$properties_query->bindValue(':category_id', $category_id);
			$properties_query->execute();
			while($property_record = $properties_query->fetch())
			{
				$property_id = $property_record["id"];
				$list_id = $property_record["list_id"];//ID списка - если свойство списковое
				?>
				<tr>
					<td style="vertical-align:top;"><font style="font-weight:bold; margin-right:10px;"><?php echo $property_record["value"]; ?></font></td>
					<td style="vertical-align:top;">
					<?php
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
						switch($property_record["property_type_id"])
						{
							case 1:
							case 2:
							case 3:
							case 4:
								$property_value_record = $property_value_query->fetch();
								echo $property_value_record["value"];
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
								echo $line_list_values_text;
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
								//Если есть цепочки
								if( count(property_variants) > 0 )
								{
									echo "<br>";
								}
								for( $pv = 0 ; $pv < count($property_variants); $pv++)
								{
									if($pv > 0)
									{
										echo "<br>";
									}
									for( $pv_i = 0 ; $pv_i < count($property_variants[$pv]); $pv_i++)
									{
										if($pv_i > 0)
										{
											echo " ";
										}
										echo $property_variants[$pv][$pv_i]["value"];
									}
								}
								break;
						}
					}//~if() - есть значение свойства
					?>
					</td>
				</tr>
				<?php
			}
			?>
			</table>
			<?php
			
			echo $text_content;
			?>
		</div>
	</div>
	<script>
	var current_image_id = <?php echo $current_image_id; ?>;
	function selectImage(id, image_url)
	{
		if(current_image_id == id)
		{
			return;
		}
		
		//Предыдущий возвращаем в нормальное состояние
		document.getElementById("product_image_select_"+current_image_id).setAttribute("class", "product_image_select other_image");
		
		//Ставим текущий новый
		document.getElementById("product_image_select_"+id).setAttribute("class", "product_image_select current_image");
		
		//Запоминаем текущее изображение
		current_image_id = id;
		
		//Обновляем выбранное изображение
		document.getElementById("main_image").setAttribute("src", image_url);
	}
	</script>
	<?php
}
?>