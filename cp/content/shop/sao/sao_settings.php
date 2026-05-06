<?php

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);


defined('_ASTEXE_') or die('No access');
?>


   
    
    <?php
    
        function getFirstKey($arr) {
            if(!empty($arr)) {
            	$keys = array_keys($arr);
            	return $keys[0];
            }
            return null;
        }

        $ex_order_status = array();
        $ex_order_status_ids = array();
        $ex_order_item_status = array();
        $ex_order_item_status_ids = array();
				$ex_storages = array();
				$ex_storages_ids = array();
				$ex_user_groups = array();
				$ex_user_groups_ids = array();
        $robot_settings_id = null;
        $actions_enable = null;
        $enable_service = null;
        $write_log = null;
        $positive_balance = null;
        
        $robot_query = $db_link->prepare("SELECT * FROM `shop_sao_settings`;");
    		$robot_query->execute();
        $robot_record = $robot_query->fetch();

				// echo "<pre>";
				// print_r($robot_record);
				// echo "</pre>";
        
        if(!empty($robot_record)) {
            $ex_order_status = json_decode($robot_record["ex_order_status"], true);
            $ex_order_item_status = json_decode($robot_record["ex_order_item_status"], true);
            $robot_settings_id = $robot_record["id"];
            $actions_enable = json_decode($robot_record["actions_enable"], true);
            $enable_service = $robot_record["enable"];
            $write_log = $robot_record["write_log"];
            $positive_balance = $robot_record["positive_balance"];
						$ex_storages = json_decode($robot_record["ex_storages"], true);
            $ex_user_groups = json_decode($robot_record["ex_user_groups"], true);
        }
            
    ?>


    <div class="col-lg-12">
    	<div class="hpanel">
    		<div class="panel-heading hbuilt">
    			Действия
    		</div>
    		<div class="panel-body">
    			
    			<a class="panel_a" href="javascript:void(0);" onclick="update_states_data_save();">
    				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
    				<div class="panel_a_caption">Сохранить</div>
    			</a>
    			
    			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/sao_settings_logs">
					<div class="panel_a_img" style="background-color: #f1c40f;width:96px;height:96px;display:table-cell;vertical-align:middle;"><i class="fas fa-bug" style="color:#FFF;font-size:45px"></i></div>
					<div class="panel_a_caption">Логи</div>
				</a>
    			
    			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
    				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
    				<div class="panel_a_caption">Выход</div>
    			</a>
    			
    		
    		</div>
    	</div>
    </div>
    
    <div class="col-lg-12">
    	<div class="hpanel">
    		<div class="panel-heading hbuilt">
    			Включить Автозаказ
    		</div>
    		<div class="panel-body">
    			
    			<input style="width: 34px;" type="checkbox" class="form-control" name="enable_service" id="enable_service" <?= ($enable_service == true) ? 'checked' : '' ;?> />
    		</div>
    	</div>
    </div>
    
    <div class="col-lg-12">
    	<div class="hpanel">
    		<div class="panel-heading hbuilt">
    			Включить запись логов для Автозаказа
    		</div>
    		<div class="panel-body">
    			
    			<input style="width: 34px;" type="checkbox" class="form-control" name="write_log" id="write_log" <?= ($write_log == true) ? 'checked' : '' ;?> />
    		</div>
    	</div>
    </div>
    
    <div class="col-lg-12">
    	<div class="hpanel">
    		<div class="panel-heading hbuilt">
    			Разрешить только при положительном балансе
    		</div>
    		<div class="panel-body">
    			
    			<input style="width: 34px;" type="checkbox" class="form-control" name="positive_balance" id="positive_balance" <?= ($positive_balance == true) ? 'checked' : '' ;?> />
    		</div>
    	</div>
    </div>
    
    <div class="col-lg-12">
    	<div class="hpanel">
    		<div class="panel-heading hbuilt">
    			Выберите исключения статусов заказа
    		</div>
    		<div class="panel-body">
    			
    			<div>
    				<div style="padding:0 0 10px 0;">
    				<button onclick="statuses_order_tree.checkAll();" class="btn w-xs btn-success">Отметить все</button>
    				<button onclick="statuses_order_tree.uncheckAll();" class="btn w-xs btn-primary2">Снять все</button>
    				</div>
    			</div>
    			<div id="container_A" style="height:250px;"></div>
    		</div>
    	</div>
    </div>
    
    <div class="col-lg-12">
    	<div class="hpanel">
    		<div class="panel-heading hbuilt">
    			Выберите исключения статусов позиций заказа
    		</div>
    		<div class="panel-body">
    			
    			<div>
    				<div style="padding:0 0 10px 0;">
    				<button onclick="statuses_order_item_tree.checkAll();" class="btn w-xs btn-success">Отметить все</button>
    				<button onclick="statuses_order_item_tree.uncheckAll();" class="btn w-xs btn-primary2">Снять все</button>
    				</div>
    			</div>
    			<div id="container_B" style="height:250px;"></div>
    		</div>
    	</div>
    </div>

		<div class="col-lg-12">
    	<div class="hpanel">
    		<div class="panel-heading hbuilt">
    			Выберите исключения для складов
    		</div>
    		<div class="panel-body">
    			
    			<div>
    				<div style="padding:0 0 10px 0;">
    				<button onclick="storages_tree.checkAll();" class="btn w-xs btn-success">Отметить все</button>
    				<button onclick="storages_tree.uncheckAll();" class="btn w-xs btn-primary2">Снять все</button>
    				</div>
    			</div>
    			<div id="container_C" style="height:250px;"></div>
    		</div>
    	</div>
    </div>

		<div class="col-lg-12">
    	<div class="hpanel">
    		<div class="panel-heading hbuilt">
    			Выберите исключения для групп пользователей
    		</div>
    		<div class="panel-body">
    			
    			<div>
    				<div style="padding:0 0 10px 0;">
    				<button onclick="user_groups_tree.checkAll();" class="btn w-xs btn-success">Отметить все</button>
    				<button onclick="user_groups_tree.uncheckAll();" class="btn w-xs btn-primary2">Снять все</button>
    				</div>
    			</div>
    			<div id="container_D" style="height:250px;"></div>
    		</div>
    	</div>
    </div>

    <div class="col-lg-12">
    	<div class="hpanel">
    		<div class="panel-heading hbuilt">
    			Редактор связи
    		</div>
    		<div class="panel-body">
    			<form name="save_form" method="POST">
    				<input type="hidden" name="save_action" value="save_action" />
    				<input type="hidden" name="robot_settings_id" id="robot_settings_id" value="<?= $robot_settings_id ;?>" />
    			
    				<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
    					<thead>
    						<tr>
    							<th>Действие SAO</th>
    							<th>Выполнить</th>
    						</tr>
    					</thead>
    					<tbody>
    						<?php
                    //Получаем статусы заказа
                    $orders_statuses = array();
                    $orders_item_statuses = array();
                    $orders_statuses_tree = array();
                    $orders_item_statuses_tree = array();
										$storages = array();
										$storages_tree = array();
										$user_groups = array();
										$user_groups_tree = array();
                    
                    $orders_statuses_query = $db_link->prepare("SELECT * FROM `shop_orders_statuses_ref` ORDER BY `order`;");
                    $orders_statuses_query->execute();
                    while( $order_status = $orders_statuses_query->fetch() )
                    {
												$checked = false;
												if(!empty($ex_order_status)) {
													foreach($ex_order_status as $ex_order_status_one) {
														if($ex_order_status_one['id'] == $order_status["id"]) {
															if($ex_order_status_one['checked'] == 1) $checked = true;
															break;
														}
													}
												}
                        $orders_statuses[$order_status["id"]] = $order_status["name"];
                        $order_item_data = array(
                            "id" => $order_status["id"],
                            "value" => $order_status["name"],
                            "checked" => $checked,
                            );
                        $orders_statuses_tree[] = $order_item_data;
                    }
                    
										//Получаем возможные склады
										$sao_state = "( SELECT IFNULL( (SELECT `state_id` FROM `shop_sao_states_types_link` WHERE `is_start` = 1 AND `interface_type_id` = `shop_storages`.`interface_type`) ,0))";
										$storages_query = $db_link->prepare("SELECT *, $sao_state AS `sao_state` FROM `shop_storages` ORDER BY `id`;");
                    $storages_query->execute();

                    while( $storage = $storages_query->fetch() )
                    {
												if($storage["sao_state"] < 1) continue;

												$checked = false;
												if(!empty($ex_storages)) {
													foreach($ex_storages as $ex_storages_one) {
														if($ex_storages_one['id'] == $storage["id"]) {
															if($ex_storages_one['checked'] == 1) $checked = true;
															break;
														}
													}
												}
                        $storage_item_data = array(
                            "id" => $storage["id"],
                            "value" => "ID " . $storage["id"] . " " . $storage["name"],
                            "checked" => $checked,
                            );
                        $storages_tree[] = $storage_item_data;
                    }

										//Получаем возможные группы
										$user_groups_query = $db_link->prepare("SELECT * FROM `groups` ORDER BY `order`;");
										$user_groups_query->execute();
										while( $user_group = $user_groups_query->fetch() )
										{
												$checked = false;
												if(!empty($ex_user_groups)) {
													foreach($ex_user_groups as $ex_user_groups_one) {
														if($ex_user_groups_one['id'] == $user_group["id"]) {
															if($ex_user_groups_one['checked'] == 1) $checked = true;
															break;
														}
													}
												}
												$user_group_item_data = array(
														"id" => $user_group["id"],
														"value" => $user_group["value"],
														"checked" => $checked,
														);
												$user_groups_tree[] = $user_group_item_data;
										}

                    //Получаем возможные статусы позиции
                    $orders_items_statuses_query = $db_link->prepare("SELECT * FROM `shop_orders_items_statuses_ref` ORDER BY `order`;");
                    $orders_items_statuses_query->execute();
                    while( $orders_item_status = $orders_items_statuses_query->fetch() )
                    {
											$checked = false;
											if(!empty($ex_order_item_status)) {
												foreach($ex_order_item_status as $ex_order_item_status_one) {
													if($ex_order_item_status_one['id'] == $orders_item_status["id"]) {
														if($ex_order_item_status_one['checked'] == 1) $checked = true;
														break;
													}
												}
											}
                      $order_item_data = array(
                          "id" => $orders_item_status["id"],
                          "value" => $orders_item_status["name"],
                          "checked" => $checked,
                          );
                      $orders_item_statuses_tree[] = $order_item_data;
                        
                    }

                    //Получаем возможные действия SAO
                    $sao_actions_statuses_query = $db_link->prepare("SELECT * FROM `shop_sao_actions`;");
                    $sao_actions_statuses_query->execute();
                    while( $sao_action = $sao_actions_statuses_query->fetch() )
                    {
                        $order_item_data = array(
                            "id" => $sao_action["id"],
                            "value" => $sao_action["name"]
                            );
                        $sao_actions__tree[] = $order_item_data;
                        
                    }

    						?>

                  <?php if(!empty($sao_actions__tree)) {

                          foreach($sao_actions__tree as $sao_action) : ?>

                              <tr class="table-relation__row">
                                  <td><?php echo $sao_action["value"]; ?></td>
                                  <td>
                                      <?php
                                          $auto_enable = '';
                                          if(!empty($actions_enable)) {

                                             $sao_action_id = $sao_action["id"];
                                             if(isset($actions_enable[$sao_action_id]) && $actions_enable[$sao_action_id] == 1) $auto_enable = 'checked';

                                          }
                                      ?> 
                                      <input class="table_row_action_enable" data-action-id="<?= $sao_action["id"] ;?>" type="checkbox" <?= $auto_enable ;?> />
                                  </td>
                              </tr>

                          <?php endforeach;

                  }
                  ?>

    					</tbody>
    				</table>
    			</form>
    		</div>
    	</div>
    </div>
   
       
    <style>
        .disabled {
            opacity: 0.55;
            pointer-events: none;
        }
    </style>
    
    <script>
        
        let statuses_order_tree = new webix.ui({
            editable:false,//не редактируемое
            container:"container_A",//id блока div для дерева
            view:"tree",
        	select:false,//можно выделять элементы
        	drag:true,//можно переносить
        	//Шаблон элемента дерева
        	template:function(obj, common)//Шаблон узла дерева
            	{
            	    let value_text = "<span style='margin:4px 0 4px 10px;display:inline-block;'>" + obj.value + "</span>";//Вывод текста
            	    let checkbox = common.checkbox(obj, common);
                    return checkbox + value_text;
            	},//~template
        });
        webix.event(window, "resize", function(){ 
            statuses_order_tree.adjust();
        });
        
				
        let saved_catalogue_1 = <?= json_encode($orders_statuses_tree) ;?>
        
        statuses_order_tree.parse(saved_catalogue_1);
        statuses_order_tree.openAll();
        
        
        let statuses_order_item_tree = new webix.ui({
            editable:false,//не редактируемое
            container:"container_B",//id блока div для дерева
            view:"tree",
	        	select:false,//можно выделять элементы
	        	drag:true,//можно переносить
	        	//Шаблон элемента дерева
	        	template:function(obj, common)//Шаблон узла дерева
          	{
          	    let value_text = "<span style='margin:4px 0 4px 10px;display:inline-block;'>" + obj.value + "</span>";//Вывод текста
          	    let checkbox = common.checkbox(obj, common);
                  return checkbox + value_text;
          	},//~template
        });
        webix.event(window, "resize", function(){ 
            statuses_order_item_tree.adjust();
        });
        
        let saved_catalogue_2 = <?= json_encode($orders_item_statuses_tree) ;?>

        statuses_order_item_tree.parse(saved_catalogue_2);
        statuses_order_item_tree.openAll();


				let storages_tree = new webix.ui({
            editable:false,//не редактируемое
            container:"container_C",//id блока div для дерева
            view:"tree",
	        	select:false,//можно выделять элементы
	        	drag:true,//можно переносить
	        	//Шаблон элемента дерева
	        	template:function(obj, common)//Шаблон узла дерева
          	{
          	    let value_text = "<span style='margin:4px 0 4px 10px;display:inline-block;'>" + obj.value + "</span>";//Вывод текста
          	    let checkbox = common.checkbox(obj, common);
                  return checkbox + value_text;
          	},//~template
        });
        webix.event(window, "resize", function(){ 
					storages_tree.adjust();
        });
        
        let saved_catalogue_3 = <?= json_encode($storages_tree) ;?>

        storages_tree.parse(saved_catalogue_3);
        storages_tree.openAll();


				let user_groups_tree = new webix.ui({
            editable:false,//не редактируемое
            container:"container_D",//id блока div для дерева
            view:"tree",
	        	select:false,//можно выделять элементы
	        	drag:true,//можно переносить
	        	//Шаблон элемента дерева
	        	template:function(obj, common)//Шаблон узла дерева
          	{
          	    let value_text = "<span style='margin:4px 0 4px 10px;display:inline-block;'>" + obj.value + "</span>";//Вывод текста
          	    let checkbox = common.checkbox(obj, common);
                  return checkbox + value_text;
          	},//~template
        });
        webix.event(window, "resize", function(){ 
            user_groups_tree.adjust();
        });
        
        let saved_catalogue_4 = <?= json_encode($user_groups_tree) ;?>

        user_groups_tree.parse(saved_catalogue_4);
        user_groups_tree.openAll();
        
        // statuses_order_item_tree.attachEvent("onItemCheck", function(newv, oldv){
        //     let statuses_order_item_ids = statuses_order_item_tree.getChecked();
        //     jQuery('.table-relation__row').removeClass('disabled');
        //     jQuery('.status-ids-list').each(function() {
        //         let _this = jQuery(this);
        //         let statusOrderItem = _this.attr('data-status-id');
        //         if(statuses_order_item_ids.indexOf(statusOrderItem) > -1) {
        //             _this.closest('tr').addClass('disabled');
        //         }
        //     });
        // });
        
        // jQuery(document).ready(function() {
        //     jQuery(window).on('load', function() {
        //         let statuses_order_item_ids = statuses_order_item_tree.getChecked();
        //         jQuery('.table-relation__row').removeClass('disabled');
        //         jQuery('.status-ids-list').each(function() {
        //             let _this = jQuery(this);
        //             let statusOrderItem = _this.attr('data-status-id');
        //             if(statuses_order_item_ids.indexOf(statusOrderItem) > -1) {
        //                 _this.closest('tr').addClass('disabled');
        //             }
        //         });
        //     })
        // });
        

        function update_states_data_save() {
            
            //Данные, которые мы будем загружать в БД
            let statuses_order_ids = [];
            let statuses_order_item_ids = [];
            let statuses_order_data = null;
            let statuses_order_item_data = null;
            let actions_enable = {};
						let storages_data = null;
						let user_groups_data = null;

            statuses_order_ids = statuses_order_tree.getChecked();
            statuses_order_item_ids = statuses_order_item_tree.getChecked();
            statuses_order_data = statuses_order_tree.serialize();
            statuses_order_item_data = statuses_order_item_tree.serialize();
						storages_data = storages_tree.serialize();
						storages_data_ids = storages_tree.getChecked();
						user_groups_data = user_groups_tree.serialize();
						user_groups_data_ids = user_groups_tree.getChecked();
            
            let robot_id = document.getElementById("robot_settings_id").value;
            let enable_service = document.getElementById("enable_service").checked ? 1 : 0;
            let write_log = document.getElementById("write_log").checked ? 1 : 0;
            let positive_balance = document.getElementById("positive_balance").checked ? 1 : 0;
            
            
            jQuery('.table_row_action_enable').each(function() {
                let _this = jQuery(this);
                let actionId = _this.attr('data-action-id');

                let actionEnable = _this.is(':checked') ? 1 : 0;
                
                // status_item_id = 'action_id_' + actionId;
                actions_enable[actionId] = actionEnable;
                
                // if(statuses_order_item_ids.indexOf(statusOrderItem) == -1) {
                //     status_item_id = 'status_item_id_' + statusOrderItem;
                //     statuses_relation[status_item_id] = statusOrder;
                // }
            });
            
           
            let request_object = new Object;
            request_object.action = 'save_settings_data';
		        request_object.statuses_order_ids = statuses_order_ids;
		        request_object.statuses_order_item_ids = statuses_order_item_ids;
		        request_object.statuses_order_data = statuses_order_data;
		        request_object.statuses_order_item_data = statuses_order_item_data;
		        request_object.robot_id = robot_id;
		        request_object.actions_enable = actions_enable;
		        request_object.enable_service = enable_service;
		        request_object.write_log = write_log;
		        request_object.positive_balance = positive_balance;
		        request_object.storages_data = storages_data;
		        request_object.storages_data_ids = storages_data_ids;
		        request_object.user_groups_data = user_groups_data;
		        request_object.user_groups_data_ids = user_groups_data_ids;
	        

	        jQuery.ajax({
        		type: "POST",
        		async: false,
        		url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/sao/ajax_sao_settings.php",
        		dataType: "json",//Тип возвращаемого значения
        		data: "request_object="+encodeURI(JSON.stringify(request_object)),
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
        
            
        }
        
        
    </script>