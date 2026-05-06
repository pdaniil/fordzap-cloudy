<?php
/**
 * Скрипт для обработки различных операций над таблицей
*/



// формируем пагинацию
// $all 		= количество постов в категории (определяем количество постов в базе данных)
// $lim 		= количество постов, размещаемых на одной странице
// $prev 		= количество отображаемых ссылок до и после номера текущей страницы
// $curr_link 	= номер текущей страницы (получаем из URL)
// $curr_css 	= css-стиль для ссылки на "текущую (активную)" страницу
// $link 		= часть адреса, используемый для формирования линков на другие страницы
function pagination($all, $lim, $prev, $curr_link, $curr_css, $link)
{
    $html = '';
	// осуществляем проверку, чтобы выводимые первая и последняя страницы
    // не вышли за границы нумерации
    $first = $curr_link - $prev;
    if ($first < 1) $first = 1;
    $last = $curr_link + $prev;
    if ($last > ceil($all/$lim)) $last = ceil($all/$lim);
 
    // начало вывода нумерации
    // выводим первую страницу
    $y = 1;
    if ($first > 1) $html .= "<a onclick='go_to_page({$y})'>1</a>";
    // Если текущая страница далеко от 1-й (>10), то часть предыдущих страниц
    // скрываем троеточием
    // Если текущая страница имеет номер до 10, то выводим все номера
    // перед заданным диапазоном без скрытия
	// $prev
    $y = $first - 1;
    if ($first > $prev) {
        $html .= "<a onclick='go_to_page({$y})'>...</a>";
    } else {
        for($i = 2;$i < $first;$i++){
            $html .=  "<a onclick='go_to_page({$y})'>$i</a>";
        }
    }
    // отображаем заданный диапазон: текущая страница +-$prev
    for($i = $first;$i < $last + 1;$i++){
        // если выводится текущая страница, то ей назначается особый стиль css
        if($i == $curr_link) {
			$html .= '<a class="'.$curr_css.'">'. $i .'</a>';
        } else {
            $alink = "<a onclick='go_to_page(";
            if($i != 1) $alink .= "{$i}";
            $alink .= ")'>$i</a>";
            $html .= $alink;
        }
    }
    $y = $last + 1;
    // часть страниц скрываем троеточием
    if ($last < ceil($all / $lim) && ceil($all / $lim) - $last > 2) $html .=  "<a onclick='go_to_page({$y})'>...</a>";
    // выводим последнюю страницу
    $e = ceil($all / $lim);
    if ($last < ceil($all / $lim)) $html .=  "<a onclick='go_to_page({$e})'>$e</a>";
	
	return $html;
}

// Функция формирования WHERE строки для запроса. Используется при отображении таблицы и удалении позиций с учетом фильтра
function get_where($where_object)
{
	global $binding_values;
	
	$where = '';
	
	if(!empty($where_object))
	{
		$search_text = trim(urldecode($where_object['search_text']));
		$price_id = (int) $where_object['price_id'];
		$article = trim($where_object['article']);
		$manufacturer = trim($where_object['manufacturer']);
		$no_article = (int) $where_object['no_article'];
		$no_manufacturer = (int) $where_object['no_manufacturer'];
		
		$article = mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $article), "UTF-8");
		$manufacturer = htmlentities(mb_strtoupper(trim($manufacturer), "UTF-8"), ENT_QUOTES, "UTF-8");
		
		if(!empty($price_id))
		{
			if($where != ''){$where .= ' AND ';}
			$where .= "(`price_id` IN(?))";
			array_push($binding_values, $price_id);
		}
		
		if($article !== '')
		{
			if($where != ''){$where .= ' AND ';}
			$where .= "(`article` LIKE ?)";
			array_push($binding_values, $article);
		}
		
		if($manufacturer !== '')
		{
			if($where != ''){$where .= ' AND ';}
			$where .= "(`manufacturer` LIKE ?)";
			array_push($binding_values, $manufacturer);
		}
		
		if(!empty($no_article))
		{
			if($where != ''){$where .= ' AND ';}
			$where .= "(`article` LIKE ?)";
			array_push($binding_values, '');
		}
		
		if(!empty($no_manufacturer))
		{
			if($where != ''){$where .= ' AND ';}
			$where .= "(`manufacturer` LIKE ?)";
			array_push($binding_values, '');
		}
		
		////////////////////////////////////////////////////////////////////////////////////
		
		if(!empty($search_text))
		{
			$search_text_arr = explode(' ', $search_text);
			$where_tmp = '';
			
			
			$where_tmp_2 = '';
			if($where_tmp != ''){$where_tmp .= ' OR ';}
			foreach($search_text_arr as $search_text_item){
				if(mb_strlen($search_text_item, 'UTF-8') >= 3){
					if($where_tmp_2 != ''){$where_tmp_2 .= ' AND ';}
					$where_tmp_2 .= "(`article` LIKE ?)";
					array_push($binding_values, '%'.$search_text_item.'%');
				}
			}
			$where_tmp .= $where_tmp_2;
			if($where_tmp != ''){$where_tmp = '('. $where_tmp .')';}
			
			
			$where_tmp_2 = '';
			if($where_tmp != ''){$where_tmp .= ' OR ';}
			foreach($search_text_arr as $search_text_item){
				if(mb_strlen($search_text_item, 'UTF-8') >= 3){
					if($where_tmp_2 != ''){$where_tmp_2 .= ' AND ';}
					$where_tmp_2 .= "(`manufacturer` LIKE ?)";
					array_push($binding_values, '%'.$search_text_item.'%');
				}
			}
			$where_tmp .= $where_tmp_2;
			if($where_tmp != ''){$where_tmp = '('. $where_tmp .')';}
			
			
			$where_tmp_2 = '';
			if($where_tmp != ''){$where_tmp .= ' OR ';}
			foreach($search_text_arr as $search_text_item){
				if(mb_strlen($search_text_item, 'UTF-8') >= 3){
					if($where_tmp_2 != ''){$where_tmp_2 .= ' AND ';}
					$where_tmp_2 .= "(`name` LIKE ?)";
					array_push($binding_values, '%'.$search_text_item.'%');
				}
			}
			$where_tmp .= $where_tmp_2;
			if($where_tmp != ''){$where_tmp = '('. $where_tmp .')';}
			
			
			if($where_tmp != ''){
				if($where != ''){$where .= ' AND ';}
				$where .= '('. $where_tmp .')';
			}
		}
		
		////////////////////////////////////////////////////////////////////////////////////
		
		if(!empty($where)){
			$where = 'WHERE ' . $where;
		}
	}
	
	return $where;
}




//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");





//Проверяем право менеджера
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
if( ! DP_User::isAdmin())
{
	$answer = array('status'=>false);
	exit(json_encode($answer));
}

$answer = array('status'=>false);
$request_object = json_decode($_POST['request_object'], true);

$sweep = array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t");

// -------------------------------------------------------------------------------
//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
// -------------------------------------------------------------------------------





switch($request_object['action'])
{
	case 'get_table':
		/*
		$kol - количество записей для вывода
		$art - с какой записи выводить
		$total - всего записей
		$page - текущая страница
		$str_pag - количество страниц для пагинации
		*/
		// Текущая страница
		$page = (int)$request_object['page'];
		if(empty($page))
		{
			$page = 1;
		}
		
		$kol = 20;  //количество записей для вывода
		$art = ($page * $kol) - $kol;//с какой записи выводить
		
		
		$binding_values = array();
		$where = get_where($request_object['where_object']);
		
		
		// Определяем количество записей в таблице
		$res = $db_link->prepare("SELECT COUNT(*) AS `count` FROM `shop_docpart_prices_data` $where");
		$res->execute($binding_values);
		$row = $res->fetch();
		$total = (int)$row['count']; // всего записей	
		
		// Количество страниц для пагинации
		$str_pag = ceil($total / $kol);
		
		
		$sql = "SELECT * FROM `shop_docpart_prices_data` $where ORDER BY `article` LIMIT $art, $kol;";		
		$query = $db_link->prepare($sql);
		$query->execute($binding_values);
		
		$html = '';
		while($rov = $query->fetch() )
		{
			$html .= '
			<tr id="show_line_'. $rov['id'] .'">
				<td>'. $rov['price_id'] .'</td>
				<td class="bgtd">'. $rov['article'] .'</td>
				<td>'. $rov['manufacturer'] .'</td>
				<td class="hidden">'. $rov['article_show'] .'</td>
				<td>'. $rov['name'] .'</td>
				<td class="center">'. $rov['exist'] .'</td>
				<td class="bgtd right">'. $rov['price'] .'</td>
				<td class="center">'. $rov['time_to_exe'] .'</td>
				<td class="hidden">'. $rov['storage'] .'</td>
				<td class="center">'. $rov['min_order'] .'</td>
				<td style="white-space: nowrap;">
					<a onclick="edit('. $rov['id'] .');" class="btn btn-sm btn-primary" title="Редактировать"><i class="fa fas fa-pencil-alt"></i></a>
					<a onclick="del('. $rov['id'] .');" class="btn btn-sm btn-primary" title="Удалить"><i class="fa fa-times"></i></a>
				</td>
			</tr>
			
			<tr class="hidden" id="edit_line_'. $rov['id'] .'">
				<td>
					<input class="form-control" type="text" id="price_id_edit_'. $rov['id'] .'" value="'. $rov['price_id'] .'"/>
				</td>
				<td>
					<input class="form-control" type="text" id="article_edit_'. $rov['id'] .'" value="'. $rov['article'] .'"/>
				</td>
				<td>
					<input class="form-control" type="text" id="manufacturer_edit_'. $rov['id'] .'" value="'. $rov['manufacturer'] .'"/>
				</td>
				<td class="hidden">
					<input class="form-control" type="text" id="article_show_edit_'. $rov['id'] .'" value="'. $rov['article_show'] .'"/>
				</td>
				<td>
					<input class="form-control" type="text" id="name_edit_'. $rov['id'] .'" value="'. $rov['name'] .'"/>
				</td>
				<td>
					<input class="form-control" type="number" id="exist_edit_'. $rov['id'] .'" value="'. $rov['exist'] .'"/>
				</td>
				<td>
					<input class="form-control" type="number" id="price_edit_'. $rov['id'] .'" value="'. $rov['price'] .'"/>
				</td>
				<td>
					<input class="form-control" type="number" id="time_to_exe_edit_'. $rov['id'] .'" value="'. $rov['time_to_exe'] .'"/>
				</td>
				<td class="hidden">
					<input class="form-control" type="text" id="storage_edit_'. $rov['id'] .'" value="'. $rov['storage'] .'"/>
				</td>
				<td>
					<input class="form-control" type="number" id="min_order_edit_'. $rov['id'] .'" value="'. $rov['min_order'] .'"/>
				</td>
				<td style="white-space: nowrap;">
					<a onclick="edit_save('. $rov['id'] .');" class="btn btn-sm btn-primary"><i class="fa fa-floppy-o"></i> Сохранить</a>
					<a onclick="edit_otmena('. $rov['id'] .');" class="btn btn-sm btn-primary"><i class="fa fa-chevron-left"></i> Отмена</a>
				</td>
			</tr>
			';
		}
		
		if($html != ''){
			$html = '<table class="table table"><tr>
						<th>ID прайс-листа</th>
						<th>Артикул</th>
						<th>Производитель</th>
						<th class="hidden">Артикул без обработки</th>
						<th>Наименование</th>
						<th class="center">Количество</th>
						<th class="right">Цена</th>
						<th class="center">Срок</th>
						<th class="hidden">Внутренний склад</th>
						<th class="center">Мин. партия</th>
						<th></th>
					</tr>'. $html .'</table>';
			
			// формируем пагинацию
			$pagination = pagination($total, $kol, 3, $page, 'pagination_active', '');
			if($pagination != '<a class="pagination_active">1</a>'){
				$pagination = '<div class="panel-footer"><div class="pagination_box">'.$pagination.'</div></div>';
			}else{
				$pagination = '';
			}
			
			$html = '<div class="panel-body">'.$html.'</div>'.$pagination;
		}else{
			$html = '<div class="panel-body">Ничего не найдено</div>';
		}
		
		//$html = $sql .'<hr>'. json_encode($binding_values) .'<hr>'. $html;
		
		exit($html);
		break;
	case 'add':
		$price_id 				= (int) $request_object['price_id'];
		$article 				= strip_tags(mb_strtoupper(trim(urldecode($request_object['article'])), 'UTF-8'));
		$manufacturer 			= strip_tags(mb_strtoupper(trim(urldecode($request_object['manufacturer'])), 'UTF-8'));
		$article_show 			= strip_tags(trim(urldecode($request_object['article_show'])));
		$name				 	= strip_tags(trim(urldecode($request_object['name'])));
		$exist				 	= (int) preg_replace("/[^0-9]+/", "", $request_object['exist']);
		$price				 	= number_format( (float) trim($request_object['price']), 2, '.', '');
		$time_to_exe		 	= (int) trim($request_object['time_to_exe']);
		$storage			 	= htmlentities(strip_tags(trim(urldecode($request_object['storage']))), ENT_QUOTES, "UTF-8");
		$min_order			 	= (int) trim($request_object['min_order']);
		
		$article = mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $article), "UTF-8");
		$manufacturer = htmlentities(mb_strtoupper(trim($manufacturer), "UTF-8"), ENT_QUOTES, "UTF-8");
		$article_show = htmlentities($article, ENT_QUOTES, "UTF-8");
		$name = htmlentities(str_replace(array("\"", "\\", "'", "\n", "\r", "\t"), "", $name), ENT_QUOTES, "UTF-8");
		
		if(empty($article_show)){$article_show = $article;}
		
		if(($article !== '') && ($manufacturer !== '') && !empty($price))
		{
			$sql = "INSERT INTO `shop_docpart_prices_data`(`price_id`, `manufacturer`, `article`, `article_show`, `name`, `exist`, `price`, `time_to_exe`, `storage`, `min_order`) VALUES (?,?,?,?,?,?,?,?,?,?)";
			if($db_link->prepare($sql)->execute( array($price_id, $manufacturer, $article, $article_show, $name, $exist, $price, $time_to_exe, $storage, $min_order) ))
			{
				$answer['status'] = true;
			}
		}
		break;
	case 'save':
		$id 					= (int) $request_object['id'];
		$price_id 				= (int) $request_object['price_id'];
		$article 				= strip_tags(mb_strtoupper(trim(urldecode($request_object['article'])), 'UTF-8'));
		$manufacturer 			= strip_tags(mb_strtoupper(trim(urldecode($request_object['manufacturer'])), 'UTF-8'));
		$article_show 			= strip_tags(trim(urldecode($request_object['article_show'])));
		$name				 	= strip_tags(trim(urldecode($request_object['name'])));
		$exist				 	= (int) preg_replace("/[^0-9]+/", "", $request_object['exist']);
		$price				 	= number_format( (float) trim($request_object['price']), 2, '.', '');
		$time_to_exe		 	= (int) trim($request_object['time_to_exe']);
		$storage			 	= htmlentities(strip_tags(trim(urldecode($request_object['storage']))), ENT_QUOTES, "UTF-8");
		$min_order			 	= (int) trim($request_object['min_order']);
		
		$article = mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $article), "UTF-8");
		$manufacturer = htmlentities(mb_strtoupper(trim($manufacturer), "UTF-8"), ENT_QUOTES, "UTF-8");
		$article_show = htmlentities($article, ENT_QUOTES, "UTF-8");
		$name = htmlentities(str_replace(array("\"", "\\", "'", "\n", "\r", "\t"), "", $name), ENT_QUOTES, "UTF-8");
		
		if(empty($article_show)){$article_show = $article;}
		
		if(($article !== '') && ($manufacturer !== '') && !empty($price_id))
		{
			$sql = "UPDATE `shop_docpart_prices_data` SET `price_id`=?,`manufacturer`=?,`article`=?,`article_show`=?,`name`=?,`exist`=?,`price`=?,`time_to_exe`=?,`storage`=?,`min_order`=? WHERE `id` = ?;";

			if($db_link->prepare($sql)->execute( array($price_id, $manufacturer, $article, $article_show, $name, $exist, $price, $time_to_exe, $storage, $min_order, $id) ))
			{
				$answer['status'] = true;
			}
		}
		break;
	case 'del':
		$id = (int) $request_object['id'];
		$sql = "DELETE FROM `shop_docpart_prices_data` WHERE `id` = ? LIMIT 1;";
		if($db_link->prepare($sql)->execute( array($id) ))
		{
			$answer['status'] = true;
		}
		break;
	case 'del_search':
		
		$binding_values = array();
		$where = get_where($request_object['where_object']);
		
		$sql = "DELETE FROM `shop_docpart_prices_data` $where LIMIT 10000;";

		do{
			$query = $db_link->prepare($sql);
			$query->execute($binding_values);
		}while($query->fetchColumn() > 0);
		
		$answer['status'] = true;
		
		break;
}
exit(json_encode($answer));
?>