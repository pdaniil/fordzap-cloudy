<?php
/**
 * Скрипт для обработки различных операций над таблицей на странице Фильтрация проценки
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
    if ($first > 1) $html .= "<li class='paginate_button'><a onclick='go_to_page({$y})'>1</a></li>";
    // Если текущая страница далеко от 1-й (>10), то часть предыдущих страниц
    // скрываем троеточием
    // Если текущая страница имеет номер до 10, то выводим все номера
    // перед заданным диапазоном без скрытия
	// $prev
    $y = $first - 1;
    if ($first > $prev) {
        $html .= "<li class='paginate_button'><a onclick='go_to_page({$y})'>...</a></li>";
    } else {
        for($i = 2;$i < $first;$i++){
            $html .=  "<li class='paginate_button'><a onclick='go_to_page({$y})'>$i</a></li>";
        }
    }
    // отображаем заданный диапазон: текущая страница +-$prev
    for($i = $first;$i < $last + 1;$i++){
        // если выводится текущая страница, то ей назначается особый стиль css
        if($i == $curr_link) {
			$html .= '<li class="paginate_button '.$curr_css.'"><a>'. $i .'</a></li>';
        } else {
            $alink = "<li class='paginate_button'><a onclick='go_to_page(";
            if($i != 1) $alink .= "{$i}";
            $alink .= ")'>$i</a></li>";
            $html .= $alink;
        }
    }
    $y = $last + 1;
    // часть страниц скрываем троеточием
    if ($last < ceil($all / $lim) && ceil($all / $lim) - $last > 2) $html .=  "<li class='paginate_button'><a onclick='go_to_page({$y})'>...</a></li>";
    // выводим последнюю страницу
    $e = ceil($all / $lim);
    if ($last < ceil($all / $lim)) $html .=  "<li class='paginate_button'><a onclick='go_to_page({$e})'>$e</a></li>";
	
	return $html;
}











//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
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






// -------------------------------------------------------------------------------
//Защита от CSRF-атак
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
// -------------------------------------------------------------------------------






//Проверяем право менеджера
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
if( ! DP_User::isAdmin())
{
	$answer = array('status'=>false);
	exit(json_encode($answer));
}








$answer = array('status'=>false);
$request_object = json_decode($_POST['request_object'], true);





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
		if(empty($page)){
			$page = 1;
		}
		
		$kol = 50;  //количество записей для вывода
		$art = ($page * $kol) - $kol;//с какой записи выводить
		
		// Определяем количество записей в таблице
		$res = $db_link->prepare("SELECT COUNT(*) AS `count` FROM `shop_docpart_filter`;");
		$res->execute();
		$row = $res->fetch();
		$total = (int)$row['count']; // всего записей	
		
		// Количество страниц для пагинации
		$str_pag = ceil($total / $kol);
		
		
		// Наименования складов
		$storages = array();
		$storages_query = $db_link->prepare("SELECT * FROM `shop_storages`;");
		$storages_query->execute();
		while($storage = $storages_query->fetch())
		{
			$storages[$storage['id']] = $storage['name'];
		}
		
		
		$sql = "SELECT * FROM `shop_docpart_filter` ORDER BY `manufacturer` LIMIT $art, $kol;";
		$query = $db_link->prepare($sql);
		$query->execute();
		
		
		$html = '';
		while($rov = $query->fetch())
		{
			$list_storages_str = '';
			$list_storages = json_decode(trim($rov['list_storages']), true);
			if(!empty($list_storages)){
				foreach($list_storages as $id_storage){
					$list_storages_str .= $storages[$id_storage] .' ('.$id_storage.')<br/>';
				}
			}
			
			$ranges_str = '';
			
			if($rov['min_price'] > 0){
				$ranges_str .= 'Мин. цена = '. $rov['min_price'] .'<br>';
			}
			if($rov['max_price'] > 0){
				$ranges_str .= 'Макс. цена = '. $rov['max_price'] .'<br>';
			}
			if($rov['min_time'] > 0){
				$ranges_str .= 'Мин. срок, дн. = '. $rov['min_time'] .'<br>';
			}
			if($rov['max_time'] > 0){
				$ranges_str .= 'Макс. срок, дн. = '. $rov['max_time'] .'<br>';
			}
			
			if( (int) $rov['active'] === 1 ){
				$checked = 'checked';
			}else{
				$checked = '';
			}
			
			$html .= '
			<tr id="show_line_'. $rov['id'] .'">
				<td><input id="active_'. $rov['id'] .'" title="Применять созданный фильтр к странице проценки" class="form-control" type="checkbox" '. $checked .' onChange="active('. $rov['id'] .');" /></td>
				<td>'. $rov['manufacturer'] .'</td>
				<td>'. $rov['article'] .'</td>
				<td>'. $rov['name'] .'</td>
				<td><div style="max-height: 70px; overflow: auto;">'. $list_storages_str .'</div></td>
				<td>'. $ranges_str .'</td>
				<td style="text-align:right;">
					<a href="/'. $DP_Config->backend_dir .'/shop/filter/setting?id='. $rov['id'] .'" class="btn btn-sm btn-info" title="Настроить фильтр"><i class="fa fa-filter"></i> Настроить фильтр</a>
					<a onclick="edit('. $rov['id'] .');" class="btn btn-sm btn-success" title="Редактировать"><i class="far fa-edit"></i></a>
					<a onclick="del('. $rov['id'] .');" class="btn btn-sm btn-danger" title="Удалить"><i class="fa fa-times"></i></a>
				</td>
			</tr>
			
			<tr class="hidden" id="edit_line_'. $rov['id'] .'">
				<td></td>
				<td>
					<input class="form-control" type="text" id="manufacturer_edit_'. $rov['id'] .'" value="'. $rov['manufacturer'] .'"/>
				</td>
				<td>
					<input class="form-control" type="text" id="article_edit_'. $rov['id'] .'" value="'. $rov['article'] .'"/>
				</td>
				<td>
					<input class="form-control" type="text" id="name_edit_'. $rov['id'] .'" value="'. $rov['name'] .'"/>
				</td>
				<td>
					
				</td>
				<td>
					
				</td>
				<td style="text-align:right;">
					<a href="/'. $DP_Config->backend_dir .'/shop/filter/setting?id='. $rov['id'] .'" class="btn btn-sm btn-info" title="Настроить фильтр"><i class="fa fa-filter"></i> Настроить фильтр</a>
					<a onclick="edit_save('. $rov['id'] .');" class="btn btn-sm btn-success"><i class="fa fa-floppy-o"></i> Сохранить</a>
					<a onclick="edit_otmena('. $rov['id'] .');" class="btn btn-sm btn-danger"><i class="fa fa-chevron-left"></i> Отмена</a>
				</td>
			</tr>
			';
		}
		
		if($html != ''){
			$html = '<table class="table"><tr><th></th><th>Производитель</th><th>Артикул</th><th>Подстрока в наименовании</th><th>Запрещенные склады</th><th>Диапазоны</th><th style="text-align:right;">Действие</th></tr>'. $html .'</table>';
			
			// формируем пагинацию
			$pagination = pagination($total, $kol, 3, $page, 'paginate_button active', '');
			if($pagination != '<li class="paginate_button paginate_button active"><a>1</a></li>'){
				$pagination = '<div class="text-center"><div class="pagination">'.$pagination.'</div></div>';
			}else{
				$pagination = '';
			}
			
			$html = '<div class="panel-body" style="overflow: auto;">'.$html.'</div>'.$pagination;
			$html .= '<br/><div class="panel-heading hbuilt" style="position:relative;">Действия над фильтрами</div><div class="panel-body"><div class="row"><div class="col-lg-12"><a onclick="active_all(1);" class="btn btn-xs btn-success"><i class="fas fa-check-square" aria-hidden="true"></i> Активировать все</a> <a onclick="active_all(0);" class="btn btn-xs btn-danger"><i class="fas fa-square" aria-hidden="true"></i> Отключить все</a></div></div></div>';
		}else{
			$html = '<div class="panel-body">Ничего не найдено</div>';
		}
		
		exit($html);
		break;
	case 'add':
		$article = mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $request_object['article']), "UTF-8");
		$manufacturer = htmlentities(mb_strtoupper(trim($request_object['manufacturer']), "UTF-8"), ENT_QUOTES, "UTF-8");
		$name = trim($request_object['name']);
		
		$sql = "INSERT INTO `shop_docpart_filter` (`manufacturer`,`article`,`name`) VALUES (?,?,?);";
		if( $db_link->prepare($sql)->execute(array($manufacturer, $article, $name)) == true )
		{
			$answer['status'] = true;
		}
		break;
	case 'save':
		$id = (int)$request_object['id'];
		$article = mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $request_object['article']), "UTF-8");
		$manufacturer = htmlentities(mb_strtoupper(trim($request_object['manufacturer']), "UTF-8"), ENT_QUOTES, "UTF-8");
		$name = trim($request_object['name']);
		
		$sql = "UPDATE `shop_docpart_filter` SET `manufacturer` = ?, `article` = ?, `name` = ? WHERE `id` = ?;";
		if( $db_link->prepare($sql)->execute(array($manufacturer, $article, $name, $id)) == true )
		{
			$answer['status'] = true;
		}
		break;
	case 'del':
		$id = (int)$request_object['id'];
		$sql = "DELETE FROM `shop_docpart_filter` WHERE `id` = ?;";
		if( $db_link->prepare($sql)->execute(array($id)) == true )
		{
			$answer['status'] = true;
		}
		break;
	case 'active':
		$id = (int) $request_object['id'];
		$flag = (int) $request_object['flag'];
		$sql = "UPDATE `shop_docpart_filter` SET `active` = ? WHERE `id` = ?;";
		if( $db_link->prepare($sql)->execute(array($flag, $id)) == true )
		{
			$answer['status'] = true;
		}
		break;
	case 'active_all':
		$flag = (int) $request_object['flag'];
		$sql = "UPDATE `shop_docpart_filter` SET `active` = ? WHERE `id` > 0;";
		if( $db_link->prepare($sql)->execute(array($flag)) == true )
		{
			$answer['status'] = true;
		}
		break;
	case 'save_storages':
		$id = (int) $request_object['id'];
		$storages_list_json = trim($request_object['storages_list_json']);
		
		$min_price = 'null';
		$max_price = 'null';
		
		$min_time = 'null';
		$max_time = 'null';
		
		if($request_object['min_price'] > 0 || $request_object['max_price'] > 0){
			$min_price = (float) $request_object['min_price'];
			$max_price = (float) $request_object['max_price'];
			
			if($min_price > $max_price){
				$max_price = $min_price;
			}
		}
		
		if($request_object['min_time'] > 0 || $request_object['max_time'] > 0){
			$min_time = (int) $request_object['min_time'];
			$max_time = (int) $request_object['max_time'];
			
			if($min_time > $max_time){
				$max_time = $min_time;
			}
		}
		
		$sql = "UPDATE `shop_docpart_filter` SET `list_storages` = ?, `min_price` = ?, `max_price` = ?, `min_time` = ?, `max_time` = ? WHERE `id` = ?;";
		if( $db_link->prepare($sql)->execute(array($storages_list_json, $min_price, $max_price, $min_time, $max_time, $id)) == true )
		{
			$answer['status'] = true;
		}
		break;
}
exit(json_encode($answer));
?>