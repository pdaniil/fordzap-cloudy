<?php
//Скрипт для получения истории обновления прайс-листа
// -------------------------------------------------------------------------------
if( !isset($DP_Config) )
{
	//Конфигурация CMS
	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
	$DP_Config = new DP_Config;
}
// -------------------------------------------------------------------------------
if( !isset($db_link) )
{
	//Подключение к БД
	try
	{
		$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	}
	catch (PDOException $e) 
	{
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = 'No DB Connect';
		exit(json_encode($answer));
	}
	$db_link->query("SET NAMES utf8;");
}
// -------------------------------------------------------------------------------
//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
// -------------------------------------------------------------------------------
//Получаем ID прайс-листа
if( isset($element_record['id']) )
{
	//Если этот скрипт встроен в страницу менеджера прайс-листов
	$price_id = $element_record['id'];
	
	//Остальные проверки не нужны
}
else
{
	//Вызывается через AJAX
	$price_id = $_POST['price_id'];
	
	//Защита от CSRF-атак
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
	
	
	//Проверка привелегий (пользователь должен иметь доступ к следующим страницам)
	$pages_to_check = array();
	$pages_to_check[] = array('url'=>'shop/prices', 'is_frontend' => 0);
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/check_user_access.php");
}
// -------------------------------------------------------------------------------
//Сначала получаем количество заданий для данного прайс-листа (которые уже завершены)
$LIMIT_tasks = 100;//Показываем не более 100 записей. Больше - не нужно. (ВНИМАНИЕ! Подставляется в SQL, поэтому если будет доработка с пагинацией - нужно учитывать)
$tasks_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_docpart_pyprices_tasks` WHERE `price_id` = ? AND (SELECT `passed` FROM `shop_docpart_pyprices_launches` WHERE `id` = `shop_docpart_pyprices_tasks`.`pyprices_launche_id` LIMIT 1 ) = ? LIMIT ".$LIMIT_tasks.";");
$tasks_query->execute( array($price_id, 1) );
if( $tasks_query->fetchColumn() == 0 && !isset($element_record['id']) )
{
	?>
	За последнее время данный прайс-лист не обновлялся
	<?php
	exit;
}
// -------------------------------------------------------------------------------
//Если нет $element_record['id'], то, скрипт выводит таблицу истории заданий на обновление данного прайс-листа. А, если есть $element_record['id'], то, выводится только индикация результата его последнего задания
if( !isset($element_record['id']) )
{
	?>
	<p>Показано не более <?php echo $LIMIT_tasks; ?> последних обновлений</p>
	
	<div class="table-responsive">
		<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
			<thead>
				<tr>
					<th>№ п/п</th>
					<th>ID задания</th>
					<th>ID запуска<br>модуля</th>
					<th>Время<br>старта</th>
					<th>Время<br>завершения</th>
					<th>Корректное<br>завершение</th>
					<th>Кол-во прайсов<br>за сессию</th>
					<th>Детализация</th>
				</tr>
			</thead>
			<tbody>
	<?php
}
?>

		<?php
		$counter = 0;
		//Получаем все задания по данному прайс-листу в обратном порядке
		$tasks_query = $db_link->prepare("SELECT *, (SELECT `name` FROM `shop_docpart_prices` WHERE `id` = `shop_docpart_pyprices_tasks`.`price_id`) AS `price_name` FROM `shop_docpart_pyprices_tasks` WHERE `price_id` = ? AND (SELECT `passed` FROM `shop_docpart_pyprices_launches` WHERE `id` = `shop_docpart_pyprices_tasks`.`pyprices_launche_id` LIMIT 1 ) = ? ORDER BY `id` DESC LIMIT ".$LIMIT_tasks.";");
		$tasks_query->execute( array($price_id, 1) );
		while( $task = $tasks_query->fetch() )
		{
			//Получаем данные по выполнению pyprices для данного задания по колонке pyprices_launche_id
			$pyprices_launche_query = $db_link->prepare("SELECT * FROM `shop_docpart_pyprices_launches` WHERE `id` = ?;");
			$pyprices_launche_query->execute( array($task['pyprices_launche_id']) );
			$pyprices_launche = $pyprices_launche_query->fetch();
			if( !$pyprices_launche )
			{
				continue;
			}
			
			$counter++;
			
			if( !isset($element_record['id']) )
			{
			?>
			<tr>
				<td><?php echo $counter; ?></td>
				<td><?php echo $task['id']; ?></td>
				<td><?php echo $pyprices_launche['id']; ?></td>
				<td>
					<?php echo date("d.m.Y", $task['time_created']); ?><br><?php echo date("H:i:s", $task['time_created']); ?>
				</td>
				<td>
					<?php
					//Время завершения показываем, если модуль доработал штатно
					if( $pyprices_launche['is_normal_exit'] == true )
					{
						?>
						<?php echo date("d.m.Y", $pyprices_launche['time_end']); ?><br><?php echo date("H:i:s", $pyprices_launche['time_end']); ?>
						<?php
					}
					else
					{
						echo "-";
					}
					?>
				</td>
				<td>
					<?php
					//Было корректное завершение
					if( $pyprices_launche['is_normal_exit'] == true )
					{
						?>
						<i class="fas fa-check-circle" style="color:#62cb31"></i>
						<?php
					}
					else
					{
						?>
						<i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i>
						<?php
					}
					?>
				</td>
				
				<td>
				<?php
				//Пробуем распарсить список заданий, который был передан в pyprices
				$list_to_handle = json_decode($pyprices_launche['list_to_handle'], true);
				if( is_array($list_to_handle) )
				{
					echo count($list_to_handle);
				}
				else
				{
					//Не удалось распарсить список исходных заданий
					?>
					-
					<?php
				}
				?>
				</td>
				
				
				<td>
				<?php
			}
				//Объект индикации для единого окна индикации - начинаем его формировать
				$indicator = array();
				$indicator['price_id'] = $price_id;
				$indicator['price_name'] = $task['price_name'];
				
				/*
				У нас есть из таблицы shop_docpart_pyprices_launches такие колонки:
				- list_to_handle (исходные объекты заданий - которые пришли в pyprices)
				- answer (объект ответа от pyprices, содержит статус, объекты заданий с результатами выполнения и т.д.)
				- normal_exit_status (если завершение pyprices было штатным, то, здесь можно посмотреть статус завершения)
				*/
				//Пробуем распарсить объект ответа от pyprices
				$answer = json_decode($pyprices_launche['answer'], true);
				
				

				if( !$answer )
				{
					//По какой-то причине не удается распарсить JSON ответа. Результат обработки прочитать не можем. Поэтому просто заполняем поле parsing_error и можем еще попробовать добавить само задание, которое приходило на pyprices
					$indicator['parsing_error'] = "Не удается распарсить объект ответа pyprices";
					
					if( !$list_to_handle )
					{
						$indicator['parsing_error'] .= "<br>Объект задания также не удалось распарсить";
					}
					else
					{
						//Ищем объект задания
						$indicator['parsing_error'] .= "<br>Объект задания: ";
						for( $i = 0 ; $i < count($list_to_handle) ; $i++)
						{
							if( $list_to_handle[$i]['client_task_id'] == $task['id'] )
							{
								$indicator['parsing_error'] .= "<pre>".print_r($list_to_handle[$i], true)."</pre>";
								break;
							}
						}
					}
				}
				else
				{
					//Ответ от pyprices распарсили. Ищем в нем объект задания (их может быть несколько в одном запуске - нам нужен конкретный). Заполнять объект индикации будем по аналогии с обработкой результата мультизадания на странице Менеджера прайс-листов (когда обрабатываем текущие завершаемые обновления)
					
					$task_in_answer = null;//Для объекта задания из ответа pyprices
					
					//Ищем объект среди прошедщих валидацию
					for( $i = 0 ; $i < count( $answer['list_to_handle'] ) ; $i++ )
					{
						if( $answer['list_to_handle'][$i]['client_task_id'] == $task['id'] )
						{
							$task_in_answer = $answer['list_to_handle'][$i];
						}
					}
					
					//Если не нашли - ищем среди непрошедших валидацию
					if( !$task_in_answer )
					{
						for( $i = 0 ; $i < count( $answer['list_to_handle_incorrect'] ) ; $i++ )
						{
							if( $answer['list_to_handle_incorrect'][$i]['client_task_id'] == $task['id'] )
							{
								$task_in_answer = $answer['list_to_handle_incorrect'][$i];
							}
						}
					}
					
					//Если нашли объект
					if($task_in_answer)
					{
						//Заполняем объект индикации
						$indicator['validation_messages'] = $task_in_answer['validation_messages'];
						$indicator['error_messages'] = $task_in_answer['error_messages'];
						$indicator['other_messages'] = $task_in_answer['other_messages'];
						
						
						foreach($indicator['other_messages'] AS $key=>$value)
						{
							$indicator['other_messages'][$key] = str_replace(array('"', "'"), '',$value);
						}
						foreach($indicator['error_messages'] AS $key=>$value)
						{
							$indicator['error_messages'][$key] = str_replace(array('"', "'"), '',$value);
						}
						foreach($indicator['validation_messages'] AS $key=>$value)
						{
							$indicator['validation_messages'][$key] = str_replace(array('"', "'"), '',$value);
						}
						
						
						//Добавим здесь еще одно сообщение по количеству строк, прочитанных из файлов
						$indicator['other_messages'][] = "Количество строк (товарных позиций), прочитанных из обработанных файлов: ".$task_in_answer['records_handled'];
						
						//last_updated и records_handled отдельно заполнять не нужно, т.к. это история обновления, и на данный момент уже эти данные могут быть не актуальны
						
						//Нужно, чтобы показать зеленый кружок
						$indicator['records_handled'] = $task_in_answer['records_handled'];
					}
					else
					{
						//Объект не нашли - это странно. Выводим индикатор с ошибкой
						$indicator['other_error'] = "В ответе (answer) от pyprices не найден результат по данному прайс-листу";
					}
					
					//Следующие сообщения из pyprices не относятся к объекту задания, а относятся к результату работы модуля pyprices в целом. Но, заполняем их в объект индикации по данному заданию.
					if( isset($answer['errors_general_list']) )
					{
						$indicator['errors_general_list'] = $answer['errors_general_list'];
					}
					if( isset($answer['message']) )
					{
						$indicator['message'] = $answer['message'];
					}
				}
				
				
				//Здесь нужно сформировать кнопки индикации в зависимости от того, какие поля заполнены в объекте индикации (по аналогии с функцией indicate_task_result() в клиентском JavaScript на странице Менеджера прайс-листов )
				//Кнопка "Ошибки"
				if( (isset($indicator['validation_messages']) && count($indicator['validation_messages']) > 0) || ( isset($indicator['error_messages']) && count($indicator['error_messages']) > 0) || (isset($indicator['errors_general_list']) && count($indicator['errors_general_list']) > 0) || isset($indicator['parsing_error']) || isset($indicator['other_error']) )
				{
					?>
					<i class="fas fa-exclamation-triangle" onclick='show_modalTaskResult(<?php echo $price_id; ?>, "errors", <?php echo json_encode($indicator); ?>);' style="margin-left:7px;color:#e74c3c;cursor:pointer;"></i>
					<?php
				}
				//Кнопка "Инфо" (сообщения от pyprices)
				if( isset($indicator['other_messages']) && count($indicator['other_messages']) > 0 )
				{
					?>
					<i class="fas fa-info-circle" onclick='show_modalTaskResult(<?php echo $price_id; ?>, "info", <?php echo json_encode($indicator); ?>);' style="margin-left:7px;color:#3498db;cursor:pointer;"></i>
					<?
				}
				
				
				//Кнопка "Успех" (статус фактического обновления товаров)
				if( isset($indicator['records_handled']) && $indicator['records_handled'] > 0 )
				{
					?>
					<i class="fas fa-check-circle" style="margin-left:7px;color:#62cb31;cursor:pointer;" onclick="alert('Прайс-лист был успешно обновлен!')"></i>
					<?php
				}
				else
				{
					//Этот значек показываем, только если реально получили объект задания в ответе от pyprices (иначе, мы не можем быть уверенными в том, что в БД действительно нет изменений). Если в tasks[task_index] есть поля, то, он был прочтен из ответа pyprices
					if( isset($indicator['validation_messages']) && isset($indicator['error_messages']) && isset($indicator['other_messages']) )
					{
						?>
						<i class="far fa-circle" style="margin-left:7px;color:#3498db;cursor:pointer;" onclick="alert('Товары и цены в прайс-листе не изменились.')"></i>
						<?php
					}
				}
				
				
				if( !isset($element_record['id']) )
				{
				?>
				</td>
				
			</tr>
			<?php
				}
				else
				{
					break;
				}
		}
		if( !isset($element_record['id']) )
		{
		?>
		</tbody>
    </table>
</div>
<?php
}
?>