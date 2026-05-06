<?php

/**

 * Страница управления одним складом (создание / редактирование)

 * 

 * 

*/

defined('_ASTEXE_') or die('No access');

?>



<?php

if(!empty($_POST["save_action"]))

{

	// -------------------------------------------------------------------------------

	//Защита от CSRF-атак

	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");

	// -------------------------------------------------------------------------------

	

	

    $id = $_POST["storage_id"];

    $name = htmlentities(trim($_POST["name"]));

	$short_name = htmlentities(trim($_POST["short_name"]));

	$currency = $_POST["currency"];

    $interface_type = $_POST["interface_type"];

    $users = $_POST["users"];

    $connection_options = $_POST["connection_options"];



    $short_text = trim($_POST["short_text"]);

    $short_text = str_replace(array("\t", "\n", "\r", "'", '"'), '', $short_text);



	$hidden = (int) $_POST["hidden"];

	$bg_line_color = (int) $_POST["bg_line_color"];

    

	

	//Обрабатываем настройки склада

	$connection_options = json_decode($connection_options, true);

	foreach( $connection_options AS $key => $object )

	{

		//Из строк удаляем пробелы по краям

		if( ! is_array($object) )

		{

			$connection_options[$key] = trim($object);

		}

		

		//Вероятность доставки - часто ставят знак процента

		if( $key == "probability" )

		{

			$object = str_replace( array(' ', '%') , '', $object);

			$connection_options[$key] = $object;

		}

		

		//Поддомен для API ABCP

		if($_POST["handler_folder"] == 'abcp')

		{

			if( $key == 'subdomain' )

			{

				$object = strtolower($object);

				$object = str_replace( array('http://', 'https://', '.public.api.abcp.ru') , '', $object);

				$object = str_replace( array('/') , '', $object);

				$connection_options[$key] = $object;

			}

		}

	}

	$connection_options = json_encode($connection_options);

	

	

    if($_POST["save_action"] == "create")

    {
        if( $db_link->prepare("INSERT INTO `shop_storages` (`name`, `interface_type`, `users`, `connection_options`, `currency`, `short_name`, `short_text`, `hidden`, `bg_line_color`) VALUES (?,?,?,?,?,?,?,?,?);")->execute( array($name, $interface_type, $users, $connection_options, $currency, $short_name, $short_text, $hidden, $bg_line_color) ) != true)

        {

            $error_message = "Не удалось создать учетную запись склада";

            ?>

            <script>

                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages/storage?error_message=<?php echo $error_message; ?>";

            </script>

            <?php

            exit;

        }

		else//Успешное создание склада

		{

			$id = $db_link->lastInsertId();//ID созданного склада

			

			$success_message = "Учетная запись склада создана";

            ?>

            <script>

                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages/storage?success_message=<?php echo $success_message; ?>&id=<?php echo $id; ?>";

            </script>

            <?php

            exit;

		}

    }//~if($_POST["save_action"] == "create")

    else if($_POST["save_action"] == "edit")

    {		

        if( $db_link->prepare("UPDATE `shop_storages` SET `name` = ?, `interface_type` = ?, `users` = ?, `connection_options` = ?, `currency` = ?, `short_name` = ?, `short_text` = ?, `hidden` = ?, `bg_line_color` = ? WHERE `id` = ?;")->execute( array($name, $interface_type, $users, $connection_options, $currency, $short_name, $short_text, $hidden, $bg_line_color, $id) ) != true)

        {

            $error_message = "Не удалось обновить учетную запись склада";

            ?>

            <script>

                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages/storage?id=<?php echo $id; ?>&error_message=<?php echo $error_message; ?>";

            </script>

            <?php

            exit;

        }

		else//Обновление успешно

		{

			$success_message = "Склад успешно отредактирован";

            ?>

            <script>

                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages/storage?id=<?php echo $id; ?>&success_message=<?php echo $success_message; ?>";

            </script>

            <?php

            exit;

		}

    }//~else if($_POST["save_action"] == "edit")

}//~if(!empty($_POST["save_action"]))

else//Вывод страницы

{

	//Для работы с пользователем

	require_once( $_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php" );

	$user_session = DP_User::getAdminSession();

	

	

    //Исходные данные

    $action_type = "create";//Тип действия при сохранении

    $page_caption = "Создание склада";//Название страницы

    

    $id = 0;//ID склада

    $name = "";//Название склада

	$short_name = "";//Короткое название

    $short_text = "";

	$currency = $DP_Config->shop_currency;//По умолчанию ставим валюту магазина

    $interface_type = 1;//Тип интерфейса

    $users = array();//Список кладовщиков

    $connection_options = array();//Настройки подключения

	$hidden = "";

	$bg_line_color = "";

    if(!empty($_GET["id"]))

    {

        $id = $_GET["id"];//ID склада

        

        $action_type = "edit";//Тип действия при сохранении

        

		$storage_query = $db_link->prepare("SELECT * FROM `shop_storages` WHERE `id`=?;");

		$storage_query->execute( array($id) );

        $storage_record = $storage_query->fetch();

        

        $name = $storage_record["name"];//Название склада

		$short_name = $storage_record["short_name"];

        $short_text = $storage_record["short_text"];

		$page_caption = "Редактирование склада <b>$name</b>";//Название страницы

        $interface_type = $storage_record["interface_type"];//Тип интерфейса

		$currency = $storage_record["currency"];//Валюта склада

        $users = $storage_record["users"];//Список кладовщиков (JSON)

        $connection_options = $storage_record["connection_options"];//Настройки подключения (JSON)

		$hidden = (int) $storage_record["hidden"];

		$bg_line_color = (int) $storage_record["bg_line_color"];

    }

    ?>

    

    

    <?php

        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий

    ?>

    

    <!--Форма для отправки-->

    <form name="form_to_save" method="post" style="display:none">

        <input name="save_action" id="save_action" type="text" value="<?php echo $action_type; ?>" style="display:none"/>

        

        <!-- Настройки склада -->

        <input type="text" name="storage_id" id="storage_id" value="<?php echo $id; ?>" />

        <input type="text" name="name" id="name" value="" />

		<input type="text" name="short_name" id="short_name" value="" />

		<input type="text" name="currency" id="currency" value="" />

        <input type="text" name="interface_type" id="interface_type" value="" />

        <input type="text" name="users" id="users" value="" />

        <input type="text" name="connection_options" id="connection_options" value="" />

        <input type="text" name="handler_folder" id="handler_folder" value="" />

        <input type="text" name="short_text" id="short_text" value="" />

		<input type="text" name="hidden" id="hidden" value="" />

		<input type="text" name="bg_line_color" id="bg_line_color" value="" />

		<input type="hidden" name="csrf_guard_key" value="<?php echo $user_session["csrf_guard_key"]; ?>" />

    </form>

    <!--Форма для отправки-->

    

    

	

	

	<div class="col-lg-12">

		<div class="hpanel">

			<div class="panel-heading hbuilt">

				Действия

			</div>

			<div class="panel-body">

				<a class="panel_a" href="javascript:void(0);" onclick="save_action();">

					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>

					<div class="panel_a_caption">Сохранить</div>

				</a>

				

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/storages">

					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/storage.png') 0 0 no-repeat;"></div>

					<div class="panel_a_caption">Менеджер складов</div>

				</a>

				

				<?php

				if( (int)$id > 0 && (int)$DP_Config->suppliers_api_debug == 1 )

				{

					$id = (int)$id;

					

					if( file_exists($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/suppliers_api_log/".$id.".php") )

					{		

						print_backend_button( array("caption"=>"Отладка", "url"=>"/".$DP_Config->backend_dir."/shop/logistics/storages/storage/api_debug?storage_id=".$id, "background_color"=>"#f1c40f", "fontawesome_class"=>"fas fa-bug", "target"=>"_blank", "show_anyway"=>true) );

					}

				}

				?>

				



				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">

					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>

					<div class="panel_a_caption">Выход</div>

				</a>

			</div>

		</div>

	</div>

	

	



    

    

    

    

    

    

    <script>

    //Объект описания технических интерфейсов

    var interfaces_types = new Array();

    </script>

	

	

	<div class="col-lg-12">

		<div class="hpanel">

			<div class="panel-heading hbuilt">

				Общие настройки склада

			</div>

			<div class="panel-body">

				<div class="form-group">

					<label for="" class="col-lg-6 control-label">

						Название склада

					</label>

					<div class="col-lg-6">

						<input type="text" name="name_input" id="name_input" value="<?php echo $name; ?>" class="form-control" />

					</div>

				</div>

				

				

				<div class="hr-line-dashed col-lg-12"></div>

				<div class="form-group">

					<label for="" class="col-lg-6 control-label">

						Короткое название для покупателей

					</label>

					<div class="col-lg-6">

						<input type="text" name="short_name_input" id="short_name_input" value="<?php echo $short_name; ?>" class="form-control" />

					</div>

				</div>

				

				

				<div class="hr-line-dashed col-lg-12"></div>

				<div class="form-group">

					<label for="" class="col-lg-6 control-label">Не отображать склад в проценке</label>

					<div class="col-lg-6"><input <?=($hidden)?'checked':'';?> class="form-control" type="checkbox" id="hidden_input"></div>

				</div>

				

				<div id="bg_line_color_box">

					<div class="hr-line-dashed col-lg-12"></div>

					<div class="form-group">

						<label for="" class="col-lg-6 control-label">Залить фоном строку товара в проценке</label>

						<div class="col-lg-6"><input <?=($bg_line_color)?'checked':'';?> class="form-control" type="checkbox" id="bg_line_color_input"></div>

					</div>

				</div>





                <div class="hr-line-dashed col-lg-12"></div>

                <div class="form-group">

                    <label for="" class="col-lg-6 control-label">Короткий текст срока поставки со склада наличия

                        <br><small>Короткий текст который будет отображаться в колонке "Срок" на страницы проценки по артикулу если позиция имете срок 0 дн.</small>

                        <br><small>Например: 1-3 часа</small>

                    </label>

                    <div class="col-lg-6"><input type="text" class="form-control" id="short_text_input" value="<?=$short_text;?>" /></div>

                </div>





                <div class="hr-line-dashed col-lg-12"></div>

				<div class="form-group">

					<label for="" class="col-lg-6 control-label">

						Валюта склада

					</label>

					<div class="col-lg-6">

						<select name="currency_select" id="currency_select" class="form-control">

						<?php

						$currencies_query = $db_link->prepare("SELECT * FROM `shop_currencies` WHERE `available` = 1 ORDER BY `order`;");

						$currencies_query->execute();

						while( $currency_record = $currencies_query->fetch() )

						{

							?>

							<option value="<?php echo $currency_record["iso_code"]; ?>"><?php echo $currency_record["iso_name"]; ?></option>

							<?php

						}

						?>

						</select>

						<script>

						document.getElementById("currency_select").value = <?php echo $currency; ?>;

						</script>

					</div>

				</div>

				

				

				<div class="hr-line-dashed col-lg-12"></div>

				<div class="form-group">

					<label for="" class="col-lg-6 control-label">

						Тип технического интерфейса

					</label>

					<div class="col-lg-6">

						<select name="interface_type_select" id="interface_type_select" onchange="on_interface_changed();" class="form-control">

    	                    <?php

    	                        //Запрашиваем ВСЕ типы технических интерфейсов - для инициализации виджетов настройки

								$SQL_SELECT_interfaces_types = "SELECT *, 

								Replace(Replace(Replace(Replace(Replace(`name`, 'Веб-сервис', 'API'), 'Веб сервис', 'API') , '(API)', ''), '(Web-Сервис)',''), 'Форум-Авто (forum-auto.ru)', 'API Форум-Авто (forum-auto.ru)') AS `name` 

								FROM `shop_storages_interfaces_types` ORDER BY `name`;";

								

								$storages_interfaces_types_query = $db_link->prepare($SQL_SELECT_interfaces_types);

								$storages_interfaces_types_query->execute();

								

    	                        while( $interface = $storages_interfaces_types_query->fetch() )

    	                        {

    	                            //Инициализация опций соединения со складом для типа "select"

    	                            $connection_options_of_interface = json_decode($interface["connection_options"], true);

    	                            for($i=0; $i < count($connection_options_of_interface); $i++)

    	                            {

    	                                if($connection_options_of_interface[$i]["type"] == "select")

    	                                {

    	                                    if( isset($connection_options_of_interface[$i]["options_way"]) && $connection_options_of_interface[$i]["options_way"] == "sql")

    	                                    {

    	                                        //Делаем запрос элементов списка

    	                                        $SQL_SELECT_OPTIONS = $connection_options_of_interface[$i]["options"];

    	                                        $options = array();//Сюда запишем полученные свойства через SQL

    	                                        

												$select_items_query = $db_link->prepare($SQL_SELECT_OPTIONS);

												$select_items_query->execute();

    	                                        while( $options_record = $select_items_query->fetch() )

                    	                        {

                    	                            array_push($options, array("caption"=>$options_record["caption"], "value"=>$options_record["value"]));

                    	                        }

                    	                        $connection_options_of_interface[$i]["options"] = $options;//Заменяем строку SQL-запроса на массив свойств

    	                                    }

    	                                }

    	                            }

    	                            

    	                            ?>

    	                            <script>

    	                            interfaces_types[<?php echo $interface["id"]; ?>] = new Object;//Добавляем описание интерфейса в объект

    	                            interfaces_types[<?php echo $interface["id"]; ?>].connection_options = JSON.parse('<?php echo json_encode($connection_options_of_interface); ?>');

    	                            interfaces_types[<?php echo $interface["id"]; ?>].product_type = <?php echo $interface["product_type"]; ?>;

									interfaces_types[<?php echo $interface["id"]; ?>].handler_folder = '<?php echo $interface["handler_folder"]; ?>';

									interfaces_types[<?php echo $interface["id"]; ?>].description = '<?php echo $interface["description"]; ?>';

									

    	                            </script>

    	                            <option value="<?php echo $interface["id"]; ?>"><?php echo $interface["name"]; ?></option>

    	                            <?php

    	                        }

    	                    ?>

    	                </select>

						<style>

						#interface_type_select,

						#interface_type_select option

						{

							text-transform: capitalize;

						}

						</style>

					</div>

				</div>

				

				

				<div class="hr-line-dashed col-lg-12" id="type_description_hr" style="display:none;"></div>

				<div class="form-group" id="type_description_form" style="display:none;">

					<label for="" class="col-lg-6 control-label">

						Описание выбранного типа

					</label>

					<div class="col-lg-6" id="type_description_text">

					</div>

				</div>

				

				

			</div>

		</div>

	</div>

	



    

    <script>

    //Обработка смены типа интерфейса

    function on_interface_changed()

    {

        var current_interface_type = document.getElementById("interface_type_select").value;

		

		console.log(current_interface_type);

		if(current_interface_type == 1){

			document.getElementById("bg_line_color_box").setAttribute("style", "display:none;");

		}else{

			document.getElementById("bg_line_color_box").setAttribute("style", "display:block;");

		}

		

        var mysql_options_div_fields = document.getElementById("mysql_options_div_fields");

        

        var html = "";

        

		if(interfaces_types[current_interface_type].connection_options.length == 0)

		{

			document.getElementById("connection_options_div").setAttribute("style", "display:none;");

		}

		else

		{

			document.getElementById("connection_options_div").setAttribute("style", "");

		}

		

        for(var i=0; i < interfaces_types[current_interface_type].connection_options.length; i++)

        {

			if( interfaces_types[current_interface_type].connection_options[i].type == "hidden" )

			{

				continue;

			}

			

			if( i > 0)

			{

				html += "<div class=\"hr-line-dashed col-lg-12\"></div>";

			}

			

            //В зависимости от типа свойства - выводим виджет для настроки

			html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">"+interfaces_types[current_interface_type].connection_options[i].caption+"</label><div class=\"col-lg-6\">";

            

            if(interfaces_types[current_interface_type].connection_options[i].type == "text" || 

            interfaces_types[current_interface_type].connection_options[i].type == "number" || 

            interfaces_types[current_interface_type].connection_options[i].type == "color" || 

            interfaces_types[current_interface_type].connection_options[i].type == "password" ||

            interfaces_types[current_interface_type].connection_options[i].type == "checkbox")

            {

				var value_default = "";

				if(interfaces_types[current_interface_type].connection_options[i].type == "color")value_default = "#FFFFFF";

                html += "<input class=\"form-control\" type=\""+interfaces_types[current_interface_type].connection_options[i].type+"\" id=\""+interfaces_types[current_interface_type].connection_options[i].name+"\" value=\""+value_default+"\" />";

            }

            else if(interfaces_types[current_interface_type].connection_options[i].type == "select")

            {

                html += "<select class=\"form-control\" id=\""+interfaces_types[current_interface_type].connection_options[i].name+"\">";

                    for(var o=0; o < interfaces_types[current_interface_type].connection_options[i].options.length; o++)

                    {

                        html += "<option value=\""+interfaces_types[current_interface_type].connection_options[i].options[o].value+"\">"+interfaces_types[current_interface_type].connection_options[i].options[o].caption+"</option>";

                    }

                html += "</select>";

            }

			

			html += "</div></div>";

        }

        

        mysql_options_div_fields.innerHTML = html;

        

		if(interfaces_types[current_interface_type].handler_folder == "autoeuro") {

			<?php if($action_type == "create") : ?>

			document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

				'<div class="panel-heading hbuilt">Настройки Автоевро Личный кабинет</div>' +

				'<div class="panel-body"><div class=\"alert alert-warning\">Заполните поле API ключ и обновите страницу.</div></div>' +

				'</div>';

			<?php endif; ?>

        }



		if(interfaces_types[current_interface_type].handler_folder == "rossko") {

            <?php if($action_type == "create") : ?>

            document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

                '<div class="panel-heading hbuilt">Настройки Rossko Личный кабинет</div>' +

            		'<div class="panel-body"><div class=\"alert alert-warning\">Заполните поля KEY1 и KEY2 и обновите страницу.</div></div>' +

            	'</div>';

            <?php endif; ?>

        }



		if(interfaces_types[current_interface_type].handler_folder == "atrast") {

        <?php if($action_type == "create") : ?>

        document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

            '<div class="panel-heading hbuilt">Настройки Атраст Личный кабинет</div>' +

        		'<div class="panel-body"><div class=\"alert alert-warning\">Заполните поле API-ключ и обновите страницу.</div></div>' +

        	'</div>';

        <?php endif; ?>

    }



		if(interfaces_types[current_interface_type].handler_folder == "v_avto") {

          <?php if($action_type == "create") : ?>

          document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

              '<div class="panel-heading hbuilt">Настройки Восход Личный кабинет</div>' +

          		'<div class="panel-body"><div class=\"alert alert-warning\">Заполните API ключ и обновите страницу.</div></div>' +

          	'</div>';

          <?php endif; ?>

      }



		if(interfaces_types[current_interface_type].handler_folder == "abcp") {

          <?php if($action_type == "create") : ?>

          document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

              '<div class="panel-heading hbuilt">Настройки ABCP Личный кабинет</div>' +

          		'<div class="panel-body"><div class=\"alert alert-warning\">Заполните основные настройки и обновите страницу.</div></div>' +

          	'</div>';

          <?php endif; ?>

    }



		if(interfaces_types[current_interface_type].handler_folder == "tmparts") {

          <?php if($action_type == "create") : ?>

          document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

              '<div class="panel-heading hbuilt">Настройки TMParts Личный кабинет</div>' +

          		'<div class="panel-body"><div class=\"alert alert-warning\">Укажите Хеш авторизации и обновите страницу.</div></div>' +

          	'</div>';

          <?php endif; ?>

    }



		if(interfaces_types[current_interface_type].handler_folder == "mparts") {

            <?php if($action_type == "create") : ?>

            document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

                '<div class="panel-heading hbuilt">Настройки Mparts Личный кабинет</div>' +

            		'<div class="panel-body"><div class=\"alert alert-warning\">Заполните поля Логин и Пароль и обновите страницу.</div></div>' +

            	'</div>';

            <?php endif; ?>

    }



		if(interfaces_types[current_interface_type].handler_folder == "armtek") {

        <?php if($action_type == "create") : ?>

        document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

            '<div class="panel-heading hbuilt">Настройки Армтек Личный кабинет</div>' +

        		'<div class="panel-body"><div class=\"alert alert-warning\">Заполните основные настройки и обновите страницу.</div></div>' +

        	'</div>';

        <?php endif; ?>

    }



		if(interfaces_types[current_interface_type].handler_folder == "berg") {

        <?php if($action_type == "create") : ?>

        document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

            '<div class="panel-heading hbuilt">Настройки Berg Личный кабинет</div>' +

        		'<div class="panel-body"><div class=\"alert alert-warning\">Заполните полe Ключ API.</div></div>' +

        	'</div>';

        <?php endif; ?>

    }

	

	if(interfaces_types[current_interface_type].handler_folder == "shate_m") {

		<?php if($action_type == "create") : ?>

		document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

			'<div class="panel-heading hbuilt">Настройки Шате-М Личный кабинет</div>' +

			'<div class="panel-body"><div class=\"alert alert-warning\">Заполните основные поля и обновите страницу.</div></div>' +

			'</div>';

		<?php endif; ?>

	}



    if(interfaces_types[current_interface_type].handler_folder == "uniqom") {

        <?php if($action_type == "create") : ?>

        document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

            '<div class="panel-heading hbuilt">Настройки Uniqom Личный кабинет</div>' +

        		'<div class="panel-body"><div class=\"alert alert-warning\">Заполните полe Логин или пароль.</div></div>' +

        	'</div>';

        <?php endif; ?>

    }



		if(interfaces_types[current_interface_type].handler_folder == "tehnomir") {

			<?php if($action_type == "create") : ?>

			document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +

				'<div class="panel-heading hbuilt">Настройки Автоевро Личный кабинет</div>' +

				'<div class="panel-body"><div class=\"alert alert-warning\">Заполните поле API ключ и обновите страницу.</div></div>' +

				'</div>';

			<?php endif; ?>

    }

		

		//Указываем тип технического интерфейса в форму (может потребоваться при сохранении настроек склада)

		document.getElementById("handler_folder").value = interfaces_types[current_interface_type].handler_folder;

		

		

		//Описание типа:

		if( interfaces_types[current_interface_type].description != "" )

		{

			document.getElementById("type_description_text").innerHTML = interfaces_types[current_interface_type].description;

			document.getElementById("type_description_hr").setAttribute("style", "display:block;");

			document.getElementById("type_description_form").setAttribute("style", "display:block;");

		}

		else

		{

			document.getElementById("type_description_text").innerHTML = "";

			document.getElementById("type_description_hr").setAttribute("style", "display:none;");

			document.getElementById("type_description_form").setAttribute("style", "display:none;");

		}

    }

    </script>

    

    

	<div class="col-lg-12">

         <div id="hidden_connection_options_div"></div>

    </div>

    

    

	<div class="col-lg-12" id="connection_options_div">

		<div class="hpanel">

			<div class="panel-heading hbuilt">

				Настройки подключения

			</div>

			<div class="panel-body" id="mysql_options_div_fields">

			</div>

		</div>

	</div>

	

	

    
<!-- =======================================================================-->
	 <!-- =========== Синхронизация статусов между поставщиком и магазином ======-->
	 
	<?php $interfaces_access_check = array(3, 13, 36, 82); ?>
	<?php if(in_array($interface_type, $interfaces_access_check)) : ?>
	
    	<div class="col-lg-12">
    		<div class="hpanel">
    			<div class="panel-heading hbuilt">
    				Синхронизация статусов
    			</div>
    			
    			<?php
    			
        			//Получаем возможные статусы позиции
        			$orders_items_statuses_query = $db_link->prepare("SELECT * FROM `shop_orders_items_statuses_ref` ORDER BY `order`;");
        			$orders_items_statuses_query->execute();
        			$orders_item_statuses = array();
        			while( $orders_item_status = $orders_items_statuses_query->fetch() )
        			{
        			    $order_item_data = array(
        				    "id" => $orders_item_status["id"],
        				    "value" => $orders_item_status["name"],
        				    "checked" => false,
        				    );
        				$orders_item_statuses[$orders_item_status["id"]] = $orders_item_status["name"];
        			}
        			
        			//Находим текущие настройки для этого склада
        			$query_storage_relationship = $db_link->prepare("SELECT `relationship` FROM `shop_storages_statuses_relationship` WHERE `storage_id` = ?;");
                	$query_storage_relationship->execute(array($id));
                    $storage_relationship_json = $query_storage_relationship->fetchColumn();
                    
    			?>
    			
    			<div class="panel-body">
    				<div class="form-group">
    				    <form id="relationship_form">
    				    	<table class="table" id="table_status_relations">
        						<thead>
        							<tr>
        								<th>Код статуса со стороны поставщика</th>
        								<th>Статус магазина</th>
        								<th>Описание</th>
        							</tr>
        						</thead>
        						<tbody>
        						    <?php if(!empty($storage_relationship_json)) : ?>
        						    
        						        <?php $storage_relationship = json_decode($storage_relationship_json, true); ?>
        						    
        						        <?php foreach($storage_relationship as $key => $storage_relationship_one) : ?>
        						        
        						            <tr row_key="<?= $key ;?>">
                								<td><input type="text" name="relationship[<?= $key ;?>][status_supplier]" id="" value="<?= $storage_relationship_one['status_supplier'] ;?>" class="form-control"></td>
                								<td>
                							       	<select class="form-control" name="relationship[<?= $key ;?>][status_store]">
                										<option value="0">Нет (статус не меняется)</option>
                										<?php
                										foreach($orders_item_statuses AS $orders_item_statuses_id => $orders_item_statuses_name )
                										{
                											$selected = "";
                											if($storage_relationship_one['status_store'] == $orders_item_statuses_id)  $selected = "selected";
                											?>
                											<option <?php echo $selected; ?> value="<?php echo $orders_item_statuses_id; ?>"><?php echo $orders_item_statuses_name; ?></option>
                											<?php
                										}
                										?>
                									</select>
                								</td>
                								<td><input type="text" name="relationship[<?= $key ;?>][description]" id="" value="<?= $storage_relationship_one['description'] ;?>" class="form-control"></td>
                								<td><a id="delete_status_relations" title="Удалить связь статуса" class="btn btn-sm btn-primary"><i class="fa fa-times"></i></a></td>
                					       </tr>
        						        
        						        <?php endforeach; ?>
        						    
        						    <?php endif; ?>
        						    
    				    		</tbody>
    				    	</table>
    					</form>
    					
    					<input type="hidden" name="storage_id" id="input_storage_id" value="<?= $id ;?>" />
    					
    					<button class="btn w-xs btn-info" style="margin-bottom: 30px; margin-left: 8px;" title="Добавить связь статуса" id="add_new_status_relations">Добавить связь статуса</button>
    					<button class="btn w-xs btn-success" style="margin-bottom: 30px; margin-right: 8px; float:right;" id="save_status_relations" title="Сохранить данные">Сохранить данные</button>
    					
    				</div>
    			</div>
    			
    			<div id="hidden_statuses_options_div"></div>
    		</div>
    		
    	</div>
    	
    	<script>
            jQuery(document).ready(function() {
            
                jQuery(document).on('click', '#save_status_relations', function(e) {
                    e.preventDefault();
                    
                    let dataFrom = jQuery('#relationship_form').serialize();
    
                    let storageId = jQuery('#input_storage_id').val(); 
                    
         	        jQuery.ajax({
                		type: "POST",
                		async: false,
                		url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/logistics/ajax_statuses_relationship_operations.php",
                		dataType: "json",//Тип возвращаемого значения
                		data: dataFrom  + '&action=save_relationship_data&storage_id=' + encodeURIComponent(storageId),
                		success: function(answer)
                		{
                			console.log(answer);
                			
                			if(answer.status != true)
                			{
                				alert(answer.message);
                			}
                			else
                			{
                				alert("Настройки сохранены успешно.");
                			}
                		}
                	});
    
                });
            
	        		jQuery(document).on('click', '#add_new_status_relations', function(e) {
	        			e.preventDefault();
	        
	        			let last_index = jQuery('#table_status_relations tbody tr').last().attr('row_key') * 1;

								if(isNaN(last_index)) last_index = 0;
	    
	        			let baseRowOptions = "";
	        			<?php
	        			    
	        				foreach($orders_item_statuses AS $orders_item_statuses_id => $orders_item_statuses_name )
	        				{
	        					?>
	        					
	        					baseRowOptions += `<option value="<?php echo $orders_item_statuses_id; ?>"><?php echo $orders_item_statuses_name; ?></option>`; 
	        					
	        					<?php
	        				}
	        				
	    				?>
	        
	        			let baseRow =  `<tr row_key="${last_index + 1}">
	                                        <td><input type="text" name="relationship[${last_index + 1}][status_supplier]" class="form-control" /></td>
	                                        <td>
	                                            <select class="form-control" name="relationship[${last_index + 1}][status_store]">
	                                                <option value="0">Нет (статус не меняется)</option>
	                                                ${baseRowOptions}
	                                            </select>
	                                        </td>
	                                        <td><input type="text" name="relationship[${last_index + 1}][description]" class="form-control" /></td>
	                                        <td><a id="delete_status_relations" title="Удалить связь статуса" class="btn btn-sm btn-primary"><i class="fa fa-times"></i></a></td>
	                                    </tr>`;
	    
	        			let newRow = jQuery(baseRow).appendTo("#table_status_relations tbody");
	        		});
        		
	        		jQuery(document).on('click', '#delete_status_relations', function(e) {
	        			e.preventDefault();
	        			
	        			jQuery(this).closest('tr').remove();
	        		});
    
            
            });
        </script>
        
        <style>
            
            #table_status_relations.table > thead > tr > th,
            #table_status_relations.table > tbody > tr > th,
            #table_status_relations.table > tfoot > tr > th,
            #table_status_relations.table > thead > tr > td,
            #table_status_relations.table > tbody > tr > td,
            #table_status_relations.table > tfoot > tr > td {
                border-top: 1px solid transparent;
            }
        </style>
    
    <?php endif; ?>
    
    <!-- =======================================================================-->
	
	
	<?php $interfaces_access_check = array(13); ?>
<?php if(in_array($interface_type, $interfaces_access_check)) : ?>

    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                Дополнительное время ожидания (часы)
            </div>
            
            <?php
            
                //Получаем список магазинов
                $offices_query = $db_link->prepare("SELECT * FROM `shop_offices`;");
                $offices_query->execute();
                $offices_array = array();
                while( $office_info = $offices_query->fetch() )
                {
                    $offices_array[$office_info["id"]] = $office_info["caption"];
                }
                
                //Находим текущие настройки для этого склада
                $storages_offices_extra_time_query = $db_link->prepare("SELECT `relationship` FROM `storages_offices_extra_time` WHERE `storage_id` = ?;");
                $storages_offices_extra_time_query->execute(array($id));
                $storages_offices_extra_time_json = $storages_offices_extra_time_query->fetchColumn();
                
            ?>
            
            <div class="panel-body">
                <div class="form-group">
                    <form id="extra_time_form">
                        <table class="table" id="table_extra_time">
                            <thead>
                                <tr>
                                    <th>Офис</th>
                                    <th>Доп время, ч.</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($storages_offices_extra_time_json)) : ?>
                                
                                    <?php $storages_offices_extra_time = json_decode($storages_offices_extra_time_json, true); ?>
                                    
                                    <?php if(is_array($storages_offices_extra_time)) : ?>
                                        <?php foreach($storages_offices_extra_time as $key => $extra_time_one) : ?>
                                        
                                            <tr row_key="<?= $key ;?>">
                                                <td>
                                                    <select class="form-control" name="extra_time[<?= $key ;?>][office_id]">
                                                        <option value="0">Выберите офис</option>
                                                        <?php
                                                        foreach($offices_array AS $office_id => $office_name )
                                                        {
                                                            $selected = "";
                                                            if($extra_time_one['office_id'] == $office_id)  $selected = "selected";
                                                            ?>
                                                            <option <?php echo $selected; ?> value="<?php echo $office_id; ?>"><?php echo $office_name; ?></option>
                                                            <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </td>
                                                <td><input type="number" step="0.5" min="0" name="extra_time[<?= $key ;?>][hours]" value="<?= $extra_time_one['hours'] ?? 0 ;?>" class="form-control"></td>
                                                <td><a id="delete_extra_time" title="Удалить" class="btn btn-sm btn-primary"><i class="fa fa-times"></i></a></td>
                                           </tr>
                                        
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                
                                <?php endif; ?>
                                
                            </tbody>
                        </table>
                    </form>
                    
                    <input type="hidden" name="storage_id" id="input_storage_id_extra_time" value="<?= $id ;?>" />
                    
                    <button class="btn w-xs btn-info" style="margin-bottom: 30px; margin-left: 8px;" title="Добавить время для офиса" id="add_new_extra_time">Добавить время для офиса</button>
                    <button class="btn w-xs btn-success" style="margin-bottom: 30px; margin-right: 8px; float:right;" id="save_extra_time" title="Сохранить данные">Сохранить данные</button>
                    
                </div>
            </div>
            
            <div id="hidden_offices_options_div"></div>
        </div>
        
    </div>
    
    <script>
        jQuery(document).ready(function() {
        
            jQuery(document).on('click', '#save_extra_time', function(e) {
                e.preventDefault();
                
                let dataFrom = jQuery('#extra_time_form').serialize();

                let storageId = jQuery('#input_storage_id_extra_time').val(); 
                
                jQuery.ajax({
                    type: "POST",
                    async: false,
                    url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/logistics/ajax_extra_time_operations.php", // Нужен другой обработчик
                    dataType: "json",
                    data: dataFrom + '&action=save_extra_time_data&storage_id=' + encodeURIComponent(storageId),
                    success: function(answer)
                    {
                        console.log(answer);
                        
                        if(answer.status != true)
                        {
                            alert(answer.message);
                        }
                        else
                        {
                            alert("Настройки сохранены успешно.");
                        }
                    }
                });

            });
        
                jQuery(document).on('click', '#add_new_extra_time', function(e) {
                    e.preventDefault();
    
                    let last_index = jQuery('#table_extra_time tbody tr').last().attr('row_key') * 1;

                    if(isNaN(last_index)) last_index = 0;

                    let baseRowOptions = "";
                    <?php
                        
                        foreach($offices_array AS $office_id => $office_name )
                        {
                            ?>
                            
                            baseRowOptions += `<option value="<?php echo $office_id; ?>"><?php echo $office_name; ?></option>`; 
                            
                            <?php
                        }
                        
                    ?>

                    let baseRow =  `<tr row_key="${last_index + 1}">
                                        <td>
                                            <select class="form-control" name="extra_time[${last_index + 1}][office_id]">
                                                <option value="0">Выберите офис</option>
                                                ${baseRowOptions}
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.5" min="0" name="extra_time[${last_index + 1}][hours]" value="0" class="form-control" /></td>
                                        <td><a id="delete_extra_time" title="Удалить" class="btn btn-sm btn-primary"><i class="fa fa-times"></i></a></td>
                                    </tr>`;

                    let newRow = jQuery(baseRow).appendTo("#table_extra_time tbody");
                });
            
                jQuery(document).on('click', '#delete_extra_time', function(e) {
                    e.preventDefault();
                    
                    jQuery(this).closest('tr').remove();
                });

        
        });
    </script>
    
    <style>
        
        #table_extra_time.table > thead > tr > th,
        #table_extra_time.table > tbody > tr > th,
        #table_extra_time.table > tfoot > tr > th,
        #table_extra_time.table > thead > tr > td,
        #table_extra_time.table > tbody > tr > td,
        #table_extra_time.table > tfoot > tr > td {
            border-top: 1px solid transparent;
        }
    </style>

<?php endif; ?>   
	
    <div class="col-lg-12">

		<div class="hpanel">

			<div class="panel-heading hbuilt">

				Кладовщики

			</div>

			<div class="panel-body">

				<div class="form-group">

					<label for="" class="col-lg-6 control-label">

						Выберите кладовщиков из списка

					</label>

					<div class="col-lg-6">

						<?php

						//Получить список групп для бэкенда:

						require_once("content/users/helper.php");//Скрипт со вспомогательными возможностями пакета "Пользователи"

						

						$root_backend_group_query = $db_link->prepare("SELECT * FROM `groups` WHERE `for_backend` = 1;");

						$root_backend_group_query->execute();

						$root_backend_group_record = $root_backend_group_query->fetch();

						$root_backend_group = $root_backend_group_record["id"];//ID корневой группы для бэкэнда

						//Далее по инструкции для функции getInsertedGroups($group) (получение групп с единым корнем)

						$one_root_groups = array();//0

						array_push($one_root_groups, $root_backend_group);//1

						getInsertedGroups($root_backend_group);//2

						//Теперь получаем список пользователей, которые допущены в бэкенд

						$SQL_SELECT_ADMINS = "SELECT DISTINCT(`user_id`) FROM `users_groups_bind` WHERE";

						$binding_values = array();

						for($i=0; $i < count($one_root_groups); $i++)

						{

							if($i > 0) $SQL_SELECT_ADMINS .= " OR";

							$SQL_SELECT_ADMINS .= " `group_id` = ?";

							

							array_push($binding_values, $one_root_groups[$i]);

						}

						

						?>

						<select multiple="multiple" id="users_selector">

						<?php

						$user_query = $db_link->prepare($SQL_SELECT_ADMINS);

						$user_query->execute($binding_values);

						while( $user_id_record = $user_query->fetch() )

						{

							$user_id = $user_id_record["user_id"];

							

							//Запрашиваем подробные данные по пользователю: (<id>)<Фамилия> <Имя> <email phone>

							$general_user_data_query = $db_link->prepare("SELECT `email`, `phone` FROM `users` WHERE `user_id` = ?;");

							$general_user_data_query->execute( array($user_id) );

							$general_user_data_record = $general_user_data_query->fetch();

							$email_phone = '';

							if( !empty( $general_user_data_record["email"] ) )

							{

								$email_phone = 'E-mail: '.$general_user_data_record["email"];

							}

							if( !empty( $general_user_data_record["phone"] ) )

							{

								if( !empty($email_phone) )

								{

									$email_phone = $email_phone . ', ';

								}

								$email_phone = $email_phone.'Телефон: '.$general_user_data_record["phone"];

							}

							//Запрашиваем фамилию:

							$surname_query = $db_link->prepare("SELECT `data_value` FROM `users_profiles` WHERE `user_id` = ? AND `data_key` = 'surname';");

							$surname_query->execute( array($user_id) );

							$surname_record = $surname_query->fetch();

							$surname = $surname_record["data_value"];

							//Запрашиваем имя:

							$name_query = $db_link->prepare("SELECT `data_value` FROM `users_profiles` WHERE `user_id` = ? AND `data_key` = 'name';");

							$name_query->execute( array($user_id) );

							$name_record = $name_query->fetch();

							$name = $name_record["data_value"];

							?>

							<option value="<?php echo $user_id; ?>"><?php echo "($user_id) $surname $name $email_phone"; ?></option>

							<?php

						}

						?>

						</select>

						<script>

							//Делаем из селектора виджет с чекбоками

							$('#users_selector').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});

						</script>

					</div>

				</div>

			</div>

		</div>

	</div>

	

	

	

    

    <script>

    //Функция сохранения

    function save_action()

    {

        //1. Название склада:

        if(document.getElementById("name_input").value == "")

        {

            alert("Заполните название");

            return;

        }

        document.getElementById("name").value = document.getElementById("name_input").value;

        

		

		//1.05 Короткое название

		if(document.getElementById("short_name_input").value == "")

        {

            alert("Заполните короткое название");

            return;

        }

        document.getElementById("short_name").value = document.getElementById("short_name_input").value;



        document.getElementById("short_text").value = document.getElementById("short_text_input").value;

		

		

		//1.1 Валюта склада

		document.getElementById("currency").value = document.getElementById("currency_select").value;

		

		

		// Включение/отключение склада в проценке

		if(document.getElementById("hidden_input").checked)

        {

            document.getElementById("hidden").value = 1;

        }else{

			document.getElementById("hidden").value = 0;

		}

		

		

		// Фон строки товара

		if(document.getElementById("bg_line_color_input").checked)

        {

            document.getElementById("bg_line_color").value = 1;

        }else{

			document.getElementById("bg_line_color").value = 0;

		}

		

		

        //2. Тип интерфеса

        var interface_type = document.getElementById("interface_type_select").value;

        document.getElementById("interface_type").value = interface_type;

        

        //3. Кладовщики

        var users_array = [].concat( $("#users_selector").multipleSelect('getSelects') );

        document.getElementById("users").value = JSON.stringify(users_array);

        

        //3. Настройки подключения к интерфейсу

        var connection_options = new Object;//Объект настроек

        for(var i=0; i < interfaces_types[interface_type].connection_options.length; i++)

        {

			if( interfaces_types[interface_type].connection_options[i].type == "hidden" )

			{

				continue;

			}

			

			

            if(interfaces_types[interface_type].connection_options[i].type == "checkbox")//Запись значений для чекбокса

            {

                if(document.getElementById(interfaces_types[interface_type].connection_options[i].name).checked)

                {

                    connection_options[interfaces_types[interface_type].connection_options[i].name] = 1;

                }

                else

                {

                    connection_options[interfaces_types[interface_type].connection_options[i].name] = 0;

                }

            }

            else//Запись значений для строковых типов (text, password) и для списков (select)

            {

                connection_options[interfaces_types[interface_type].connection_options[i].name] = document.getElementById(interfaces_types[interface_type].connection_options[i].name).value;

            }

        }



			

			if(interfaces_types[interface_type].handler_folder == "shate_m") {

					let privateConnection = [

						'AgreementCode',

						'AgreementCodeGroup',

						'delivery',

						'DeliveryAddressCode',

						'DeliveryType',

						'phone',

						'fio'

					];



					privateConnection.forEach(function(item, i, arr)

					{

						let input = document.getElementById(item);

						if( input != null && input.value !== '')

						{

							connection_options[item] = input.value;

						}

					});

			}

			



			if(interfaces_types[interface_type].handler_folder == "autoeuro") {

					let privateConnection = [

						'delivery_key',

						'payer_key'

					];



					privateConnection.forEach(function(item, i, arr)

					{

						let input = document.getElementById(item);

						if( input != null && input.value !== '')

						{

							connection_options[item] = input.value;

						}

					});

		    }

			

			

			

			

			if(interfaces_types[interface_type].handler_folder == "rossko") {

				let privateConnection = [

                    'requisite_id',

                    'delivery_id',

                    'address_id',

                    'payment_id',

                    'delivery_name',

                    'delivery_phone',

                    'delivery_comment',

                    'delivery_parts'

                ];

                

            privateConnection.forEach(function(item, i, arr) {

                let input = document.getElementById(item);

                if(item == 'delivery_parts') {

                    if(input !== null) {

                        let value = input.checked ? 1 : 0;

                        connection_options[item] = value;

                    }

                } else {

                    if(input !== null && input.value !== '') {

                        connection_options[item] = input.value;

                    }

                }

            });

        }





				if(interfaces_types[interface_type].handler_folder == "atrast") {

            let privateConnection = [

                    'contract_guid',

                    'supplier_id',

                    'address_guid',

                    'warehouse_guid'

                ];

                

            privateConnection.forEach(function(item, i, arr) {

                let input = document.getElementById(item);

             

								if(input !== null && input.value !== '') {

									connection_options[item] = input.value;

								}

                

            });

        }



				if(interfaces_types[interface_type].handler_folder == "v_avto") {

            let privateConnection = [

                    'delivery_type',

                    'delivery_address',

                ];

                

            privateConnection.forEach(function(item, i, arr) {

                let input = document.getElementById(item);

             

								if(input !== null && input.value !== '') {

									connection_options[item] = input.value;

								}

                

            });

        }



				if(interfaces_types[interface_type].handler_folder == "abcp") {

            let privateConnection = [

                    'payment_id',

                    'shipment_id',

                    'office_id',

                    'address_id',

                    'date_id',

                ];

                

            privateConnection.forEach(function(item, i, arr) {

                let input = document.getElementById(item);

            

								if(input !== null && input.value !== '') {

									connection_options[item] = input.value;

								}

                

            });

        }



				if(interfaces_types[interface_type].handler_folder == "tmparts") {

            let privateConnection = [

                    'ShipCode',

                    'ContractCode',

                ];

                

            privateConnection.forEach(function(item, i, arr) {

                let input = document.getElementById(item);

              

								if(input !== null && input.value !== '') 

									connection_options[item] = input.value;

                

            });

        }





				if(interfaces_types[interface_type].handler_folder == "mparts") {

            let privateConnection = [

                    'paymentMethod',

                    'shipmentMethod',

                    'shipmentAddress',

                    'shipmentOffice',

                ];

                

            privateConnection.forEach(function(item, i, arr) {

                let input = document.getElementById(item);

             

								if(input !== null && input.value !== '') {

									connection_options[item] = input.value;

								}

                

            });

       }





			if(interfaces_types[interface_type].handler_folder == "armtek") {

            let privateConnection = [

                    'vkorg',

                    'kunrg',

                    'kunwe',

                    'kunza_0',

                    'kunza_1',

                    'vbeln',

                    'parnr',

                    'incoterms'

                ];

                

            privateConnection.forEach(function(item, i, arr) {

                let input = document.getElementById(item);

								if (typeof(input) != 'undefined' && input != null) {

									if(item == 'incoterms') {

                    if(input !== null) {

                        let value = input.checked ? 1 : 0;

                        connection_options[item] = value;

                    }

	                } else {

	                    if(input !== null && input.value !== '') {

	                        connection_options[item] = input.value;

	                    } else if(item == 'vkorg' || item == 'kunrg') {

												alert('Поле "Организация" и "Покупатель" являются обязательными.');

											}

	                }

								}

            });

      }



			if(interfaces_types[interface_type].handler_folder == "berg") {

            let privateConnection = [

                    'shipment_address_id',

                    'is_test',

                    'payment_type',

                    'dispatch_type',

                    'dispatch_at',

                    'dispatch_time',

                    'person',

                    'phone',

                    'comment'

                ];

                

            privateConnection.forEach(function(item, i, arr) {

                let input = document.getElementById(item);

				

                if(item == 'is_test') {

                    if(input !== null) {

                        let value = input.checked ? 1 : 0;

                        connection_options[item] = value;

                    }

                } else {

                    if(input !== null && input.value !== '') {

                        connection_options[item] = input.value;

                    }

                }

            });

        }



				if(interfaces_types[interface_type].handler_folder == "uniqom") {

            let privateConnection = [

                    'company_id',

                    'store_id',

                    'delivery_id',

                    'is_cash'

                ];

                

            privateConnection.forEach(function(item, i, arr) {

                let input = document.getElementById(item);

             

								if(input !== null && input.value !== '') {

									connection_options[item] = input.value;

								}

                

            });

        }





        document.getElementById("connection_options").value = JSON.stringify(connection_options);

        

        console.log(document.getElementById("connection_options").value);

        

        //alert("Ok");

        //return;

        

        document.forms["form_to_save"].submit();

    }//~function save_action()

    

    

    <?php

    //ДЕЙСТВИЕ ПРИ ЗАГРУЗКЕ СТРАНИЦЫ (ИНИЦИАЛИЗАЦИЯ ЗНАЧЕНИЙ)

    //Если тип действия - редактирование, то инициализируем страницу текущими данными

    if($action_type == "edit")

    {

        ?>

        //Тип интерфейса

        var saved_interface_type = parseInt(<?php echo $interface_type; ?>);

        document.getElementById("interface_type_select").value = saved_interface_type;

        on_interface_changed();//Обработка текущего выбора типа интерфейса (для отображения полей ввода)

        

        

        //Кладовщики

        $('#users_selector').multipleSelect('setSelects', <?php echo $users; ?>);

        

        //Настройки соединения

        var connection_options = JSON.parse('<?php echo $connection_options; ?>');

        for(var i=0; i < interfaces_types[saved_interface_type].connection_options.length; i++)

        {

			if( interfaces_types[saved_interface_type].connection_options[i].type == "hidden" )

			{

				continue;

			}

			

            if(interfaces_types[saved_interface_type].connection_options[i].type == "checkbox")//Инициализация значений для чекбокса

            {

                document.getElementById(interfaces_types[saved_interface_type].connection_options[i].name).checked = parseInt(connection_options[interfaces_types[saved_interface_type].connection_options[i].name]);

            }

            else//Инициализация значений для строковых типов (text, password) и для списков (select)

            {

                document.getElementById(interfaces_types[saved_interface_type].connection_options[i].name).value = connection_options[interfaces_types[saved_interface_type].connection_options[i].name];

            }

        }



      		if(interfaces_types[saved_interface_type].handler_folder == "autoeuro") {



						//Объект для запроса

						var request_object = new Object;

						request_object.action = 'get_data';

						request_object.api_key = document.getElementById("api_key").value || null;

						request_object.connection_options = connection_options;



						jQuery.ajax({

							type: "POST",

							async: false,

							url: "/content/shop/docpart/suppliers_handlers/autoeuro/storage_options.php",

							dataType: "json",//Тип возвращаемого значения

							data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",

							success: function(answer)

							{

								console.log(answer);



								if (answer.html != "") {

									document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

								}

							}

						});

					}

					if(interfaces_types[saved_interface_type].handler_folder == "rossko") {

			            

						//Объект для запроса

						var request_object = new Object;

						request_object.action = 'get_data';

						request_object.key1 = document.getElementById("key1").value || null;

						request_object.key2 = document.getElementById("key2").value || null;

						request_object.connection_options = connection_options;

						

						   jQuery.ajax({

							type: "POST",

							async: false,

							url: "/content/shop/docpart/suppliers_handlers/rossko/storage_options.php",

							dataType: "json",//Тип возвращаемого значения

							data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",

							success: function(answer)

							{

								console.log(answer);

								

								if (answer.html != "") {

									document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

								}

							}

						}); 

				}
				
				if(interfaces_types[saved_interface_type].handler_folder == "rossko") {
            
            			//Объект для запроса
            			var request_object = new Object;
            			request_object.action = 'get_statuses';
            			request_object.key1 = document.getElementById("key1").value || null;
            			request_object.key2 = document.getElementById("key2").value || null;
            			request_object.connection_options = connection_options;
            			
            			   jQuery.ajax({
            				type: "POST",
            				async: false,
            				url: "/content/shop/docpart/suppliers_handlers/rossko/storage_options.php",
            				dataType: "json",//Тип возвращаемого значения
            				data: "request_object="+encodeURI(JSON.stringify(request_object)),
            				success: function(answer)
            				{
            					console.log(answer);
            					
            					if (answer.html != "") {
            						document.getElementById("hidden_statuses_options_div").innerHTML = answer.html;
            					}
            				}
            			}); 
            	}

				if(interfaces_types[saved_interface_type].handler_folder == "atrast") {

          

					//Объект для запроса

					var request_object = new Object;

					request_object.action = 'get_data';

					request_object.api_key = document.getElementById("api_key").value || null;

					request_object.connection_options = connection_options;

					

						 jQuery.ajax({

						type: "POST",

						async: false,

						url: "/content/shop/docpart/suppliers_handlers/atrast/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object)),

						success: function(answer)

						{

							console.log(answer);

							

							if (answer.html != "") {

								document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

							}

						}

					}); 

				}



				if(interfaces_types[saved_interface_type].handler_folder == "v_avto") {

          

					//Объект для запроса

					var request_object = new Object;

					request_object.action = 'get_data';

					request_object.api_key = document.getElementById("api_key").value || null;

					

					request_object.connection_options = connection_options;

					

						 jQuery.ajax({

						type: "POST",

						async: false,

						url: "/content/shop/docpart/suppliers_handlers/v_avto/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",

						success: function(answer)

						{

							console.log(answer);

							

							if (answer.html != "") {

								document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

							}

						}

					}); 

				}



				if(interfaces_types[saved_interface_type].handler_folder == "abcp") {

          

					//Объект для запроса

					var request_object = new Object;

					request_object.action = 'get_data';

					request_object.login = document.getElementById("login").value || null;

					request_object.password = document.getElementById("password").value || null;

					request_object.subdomain = document.getElementById("subdomain").value || null;

					request_object.connection_options = connection_options;

					

						 jQuery.ajax({

						type: "POST",

						async: false,

						url: "/content/shop/docpart/suppliers_handlers/abcp/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",

						success: function(answer)

						{

							console.log(answer);

							

							if (answer.html != "") {

								document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

							}

						}

					}); 

				}

                if(interfaces_types[saved_interface_type].handler_folder == "abcp") {
                
                					//Объект для запроса
                					var request_object = new Object;
                					request_object.action = 'get_statuses';
                					
                					jQuery.ajax({
                						type: "POST",
                						async: false,
                						url: "/content/shop/docpart/suppliers_handlers/abcp/storage_options.php",
                						dataType: "json",//Тип возвращаемого значения
                						data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
                						success: function(answer_status)
                						{
                							console.log(answer_status);
                
                							if (answer_status.html != "") {
                								document.getElementById("hidden_statuses_options_div").innerHTML = answer_status.html;
                							}
                						}
                					});
                					}

				if(interfaces_types[saved_interface_type].handler_folder == "tmparts") {

            

						//Объект для запроса

						var request_object = new Object;

						request_object.action = 'get_data';

						request_object.authorization = document.getElementById("authorization").value || null;

						request_object.connection_options = connection_options;

						

							 jQuery.ajax({

							type: "POST",

							async: false,

							url: "/content/shop/docpart/suppliers_handlers/tmparts/storage_options.php",

							dataType: "json",//Тип возвращаемого значения

							data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",

							success: function(answer)

							{

								console.log(answer);

								

								if (answer.html != "") {

									document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

								}

							}

						}); 

				}



				if(interfaces_types[saved_interface_type].handler_folder == "mparts") {

            

						//Объект для запроса

						var request_object = new Object;

						request_object.action = 'get_data';

						request_object.login = document.getElementById("login").value || null;

						request_object.password = document.getElementById("password").value || null;

						request_object.connection_options = connection_options;

						

							 jQuery.ajax({

							type: "POST",

							async: false,

							url: "/content/shop/docpart/suppliers_handlers/mparts/storage_options.php",

							dataType: "json",//Тип возвращаемого значения

							data: "request_object="+encodeURI(JSON.stringify(request_object)),

							success: function(answer)

							{

								console.log(answer);

								

								if (answer.html != "") {

									document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

								}

							}

						}); 

				}



				if(interfaces_types[saved_interface_type].handler_folder == "armtek") {

          

					//Объект для запроса

					var request_object = new Object;

					request_object.action = 'get_data';

					request_object.login = document.getElementById("login").value || null;

					request_object.password = document.getElementById("password").value || null;

					request_object.connection_options = connection_options;

					

					jQuery.ajax({

						type: "POST",

						async: false,

						url: "/content/shop/docpart/suppliers_handlers/armtek/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object)),

						success: function(answer)

						{

							console.log(answer);

							

							if (answer.html != "") {

								document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

							}

						}

					}); 
					
					request_object.action = 'get_statuses';

					jQuery.ajax({

						type: "POST",

						async: false,

						url: "/content/shop/docpart/suppliers_handlers/armtek/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object)),

						success: function(answer)

						{

							console.log(answer);

							

							if (answer.html != "") {

								document.getElementById("hidden_statuses_options_div").innerHTML = answer.html;

							}

						}

					}); 

				}
				
				if(interfaces_types[saved_interface_type].handler_folder == "froza") {

          

					//Объект для запроса

					var request_object = new Object;

					request_object.action = 'get_statuses';

					request_object.login = document.getElementById("login").value || null;

					request_object.password = document.getElementById("password").value || null;

					request_object.connection_options = connection_options;

					jQuery.ajax({

						type: "POST",

						async: false,

						url: "/content/shop/docpart/suppliers_handlers/froza/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object)),

						success: function(answer)

						{

							console.log(answer);

							

							if (answer.html != "") {

								document.getElementById("hidden_statuses_options_div").innerHTML = answer.html;

							}

						}

					}); 

				}

				

				

				

				if(interfaces_types[saved_interface_type].handler_folder == "shate_m") {



					//Объект для запроса

					var request_object = new Object;

					request_object.action = 'get_data';

					request_object.api_key = document.getElementById("api_key").value || null;

					request_object.login = document.getElementById("login").value || null;

					request_object.password = document.getElementById("password").value || null;	

					request_object.connection_options = connection_options;



					jQuery.ajax({

						type: "POST",

						async: false,

						url: "/content/shop/docpart/suppliers_handlers/shate_m/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",

						success: function(answer_status)

						{

							console.log(answer_status);



							if (answer_status.html != "") {

								document.getElementById("hidden_connection_options_div").innerHTML = answer_status.html;

							}

						}

					});

					

					request_object.action = 'get_statuses';

					

					jQuery.ajax({

						type: "POST",

						async: false,

						url: "/content/shop/docpart/suppliers_handlers/shate_m/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object))+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",

						success: function(answer_status)

						{

							console.log(answer_status);



							if (answer_status.html != "") {

								document.getElementById("hidden_statuses_options_div").innerHTML = answer_status.html;

							}

						}

					});

					

					

				}

				

				

				

				if(interfaces_types[saved_interface_type].handler_folder == "berg") {

            

						//Объект для запроса

						var request_object = new Object;

						request_object.action = 'get_data';

						request_object.key = document.getElementById("key").value || null;

						request_object.connection_options = connection_options;

						

							 jQuery.ajax({

							type: "POST",

							async: false,

							url: "/content/shop/docpart/suppliers_handlers/berg/storage_options.php",

							dataType: "json",//Тип возвращаемого значения

							data: "request_object="+encodeURI(JSON.stringify(request_object)),

							success: function(answer)

							{

								console.log(answer);

								

								if (answer.html != "") {

									document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

								}

							}

						}); 

					}





				if(interfaces_types[saved_interface_type].handler_folder == "uniqom") {

            

					//Объект для запроса

					var request_object = new Object;

					request_object.action = 'get_data';

					request_object.login = document.getElementById("login").value || null;

					request_object.password = document.getElementById("password").value || null;

					request_object.connection_options = connection_options;

			

			   jQuery.ajax({

						type: "POST",

						async: false,

						url: "/content/shop/docpart/suppliers_handlers/uniqom/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object)),

						success: function(answer)

						{

							console.log(answer);

							

							if (answer.html != "") {

								document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

							}

						}

					}); 

				}

        <?php

    }

    else//Открыли страницу для создания нового склада

    {

        ?>

        on_interface_changed();//Обработка текущего выбора типа интерфейса

		

        <?php

    }

    ?>



			if(interfaces_types[saved_interface_type].handler_folder == "armtek") {

				jQuery(document).on('change', '.dynamic_add_input', function(e){



					jQuery("#hidden_connection_options_div").addClass("disabled");



					let vkorg_element = jQuery(this).attr('vkorg');



					//Объект для запроса

					let request_object = new Object;

					request_object.action = 'get_data';

					request_object.login = document.getElementById("login").value || null;

					request_object.password = document.getElementById("password").value || null;

					request_object.vkorg = document.getElementById("vkorg").value || null;



					let kunrg_element =  document.getElementById('kunrg');

					if (typeof(kunrg_element) != 'undefined' && kunrg_element != null)

					{

					  request_object.kunrg = document.getElementById("kunrg").value || null;

					}



					if(vkorg_element) request_object.vkorg_change = true;

					

					jQuery.ajax({

						type: "POST",

						async: true,

						url: "/content/shop/docpart/suppliers_handlers/armtek/storage_options.php",

						dataType: "json",//Тип возвращаемого значения

						data: "request_object="+encodeURI(JSON.stringify(request_object)),

						success: function(answer)

						{

							

							if (answer.html != "") {

								document.getElementById("hidden_connection_options_div").innerHTML = answer.html;

							}



							jQuery("#hidden_connection_options_div").removeClass("disabled");

						}

					});

				});

			}

			

			    

		if(interfaces_types[saved_interface_type].handler_folder == "shate_m") {

			jQuery(document).on('change', '#AgreementCode', function(e){



				let AgreementCodeGroup = e.target.options[e.target.selectedIndex].dataset.codeGroup;

				

				jQuery("#AgreementCodeGroup").val(AgreementCodeGroup);

				

			});

		}

			

    </script>

    

    <?php

}//~else//Вывод страницы

?>