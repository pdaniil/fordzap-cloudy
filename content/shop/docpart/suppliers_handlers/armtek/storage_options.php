<?php
/**
 * Скрипт для обработки данных в личном кабинете Росско
*/

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


$result = array(
    'status'  => true,
    'message' => '',
    'html'    => '',
    'data'    => array()
);

//Получаем данные
$request_object = json_decode($_POST['request_object'], true);

switch($request_object['action']) {
    case 'get_data':
        
        try
        {
            if(empty($request_object["login"])) {
                throw new Exception("Отсутствует Логин для подключения к Армтек.");
            }
            if(empty($request_object["password"])) {
                throw new Exception("Отсутствует Пароль для подключения к Армтек.");
            }

						$login = $request_object["login"];
						$password = $request_object["password"];
            
						// Сначала получаем список сбытовых органзиаций
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_user/getUserVkorgList?format=json");
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
						$curl_result = curl_exec($ch);
						curl_close($ch);
						$curl_result = json_decode($curl_result, true);

						if($curl_result["STATUS"] != 200)
						{
							throw new Exception("Не удалось получить список сбытовых организаций от Армтек.");
						}

						/*
						{
							"STATUS": 200,
							"MESSAGES": [],
							"RESP": [
								{
									"VKORG": "4000",
									"PROGRAM_NAME": " Россия"
								}
							]
						}
						*/

						$result['data']['company'] = $curl_result['RESP'];

						// Если есть организация в запросе
						$vkorg = "";
						$kunrg = "";

						$connection_options = isset($request_object["connection_options"]) ? $request_object["connection_options"] : array();
						if(!empty($connection_options) && isset($connection_options['vkorg'])) $vkorg = $connection_options['vkorg'];
						if(!empty($request_object["vkorg"])) {
							$vkorg = $request_object["vkorg"];
						}

						if(!empty($connection_options) && isset($connection_options['kunrg'])) $kunrg = $connection_options['kunrg'];
						if(!empty($request_object["kunrg"])) {
							$kunrg = $request_object["kunrg"];
						}

						if(!empty($vkorg)) {

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_user/getUserInfo");
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_POST, true);
							curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
							curl_setopt($ch, CURLOPT_POSTFIELDS, "format=json&STRUCTURE=1&VKORG=".$vkorg);
							$curl_result = curl_exec($ch);
							$curl_result = json_decode($curl_result, true);

							curl_close($ch);
	
							if($curl_result["STATUS"] != 200)
							{
								throw new Exception("Не удалось получить список покупателей для текущей организации от Армтек.");
							}
	
							if(isset($curl_result["RESP"]["STRUCTURE"]["RG_TAB"]) && !empty($curl_result["RESP"]["STRUCTURE"]["RG_TAB"])) {
	
								$result['data']['customer'] = $curl_result["RESP"]["STRUCTURE"]["RG_TAB"];
	
							}	else {
								throw new Exception("Неверная структура данных покупателей для текущей организации от Армтек.");
							}

							if(!empty($kunrg)) {
								foreach($result['data']['customer'] as $customer_key => $customer_item) {

									if($kunrg == $customer_item['KUNNR']) {
										$result['data']['customer_options'] = $result['data']['customer'][$customer_key];
									}
								}
							}
						}
        	
        }
        catch (Exception $e)//Не можем создать клиент SOAP
        {
          $result['status'] = false;
        	$result['message'] = "Ошибка: " . $e->getMessage();
        }
        
        //Формируем ответ для вывода
        if($result['status']) {
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Армтек Личный кабинет
        		</div>
        		<div class=\"panel-body\">";

						
            if(!empty($result['data'])) {
                
                //Список компаний
                if(!empty($result['data']['company'])) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Организация</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control dynamic_add_input\" id=\"vkorg\" vkorg=\"true\">";

																$result['html'] .= "<option value=\"\">Не выбрана</option>";
                		            
                		            foreach($result['data']['company'] as $company_item) {
                    		              $is_selected = false;
                    		              if(!empty($vkorg) && $vkorg == $company_item['VKORG']) $is_selected = true;

                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $company_item['VKORG'] . "\" " . $selected . ">" . $company_item['PROGRAM_NAME'] . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                }

								//Список покупателей
								if(!empty($result['data']['customer'])) {
									$result['html'] .= "<div class=\"form-group\">
															<label class=\"col-lg-6 control-label\">Покупатель</label>
													<div class=\"col-lg-6\">
															<select class=\"form-control dynamic_add_input\" id=\"kunrg\">";

															$result['html'] .= "<option value=\"\">Не выбран</option>";
															
															foreach($result['data']['customer'] as $customer_item) {
																		$is_selected = false;
																		if(!empty($kunrg) && $kunrg == $customer_item['KUNNR']) $is_selected = true;
																		$selected = $is_selected ? 'selected' : '' ;
																		$result['html'] .= "<option value=\"" . $customer_item['KUNNR'] . "\" " . $selected . ">" . $customer_item['SNAME'] . " (" . $customer_item['ADRESS'] . ")</option>";
															}
															
								 $result['html'] .= "</select>
											</div>
									</div>
									<div class=\"hr-line-dashed col-lg-12\"></div>"; 
								}
								
								//Список параметров покупателя
								if(!empty($result['data']['customer_options'])) {

									//Таблица доступных грузополучателей WE_TAB
									if(!empty($result['data']['customer_options']['WE_TAB'])) {

											$result['html'] .= "<div class=\"form-group\">
											<label class=\"col-lg-6 control-label\">Список грузополучателей</label>
											<div class=\"col-lg-6\">
											<select class=\"form-control\" id=\"kunwe\">";

											$result['html'] .= "<option value=\"\">Не выбран</option>";
											
											foreach($result['data']['customer_options']['WE_TAB'] as $customer_wetab) {
														$is_selected = false;
														if(!empty($connection_options) && isset($connection_options['kunwe']) && $connection_options['kunwe'] == $customer_wetab['KUNNR']) $is_selected = true;
														$selected = $is_selected ? 'selected' : '' ;
														$result['html'] .= "<option value=\"" . $customer_wetab['KUNNR'] . "\" " . $selected . ">" . $customer_wetab['ADRESS'] . " (" . $customer_wetab['SNAME'] . ")</option>";
											}
																
									 $result['html'] .= "</select>
												</div>
										</div>
										<div class=\"hr-line-dashed col-lg-12\"></div>"; 
									}

									//Таблица доступных адресов доставки ZA_TAB
									//Признак самовывоза.
									//1 - самовывоз. В поле KUNNR_ZA необходимо отправлять значения из таблицы RG_TAB->EXW_TAB-ID.
									//0 - доставка. В поле KUNNR_ZA необходимо отправлять значения из таблицы RG_TAB->ZA_TAB-KUNNR
									if(!empty($result['data']['customer_options']['ZA_TAB'])) {

											$result['html'] .= "<div class=\"form-group\">
											<label class=\"col-lg-6 control-label\">Список адресов доставки</label>
											<div class=\"col-lg-6\">
											<select class=\"form-control\" id=\"kunza_0\">";

											$result['html'] .= "<option value=\"\">Не выбран</option>";
											
											foreach($result['data']['customer_options']['ZA_TAB'] as $customer_zatab) {
														$is_selected = false;
														if(!empty($connection_options) && isset($connection_options['kunza_0']) && $connection_options['kunza_0'] == $customer_zatab['KUNNR']) $is_selected = true;
														$selected = $is_selected ? 'selected' : '' ;
														$result['html'] .= "<option value=\"" . $customer_zatab['KUNNR'] . "\" " . $selected . ">" . $customer_zatab['ADRESS'] . " (" . $customer_zatab['SNAME'] . ")</option>";
											}
																
										 	$result['html'] .= "</select>
													</div>
											</div>
											<div class=\"hr-line-dashed col-lg-12\"></div>"; 
									}

									//Таблица доступных адресов самовывоза EXW_TAB
									if(!empty($result['data']['customer_options']['EXW_TAB'])) {

										$result['html'] .= "<div class=\"form-group\">
										<label class=\"col-lg-6 control-label\">Список адресов самовывоза</label>
										<div class=\"col-lg-6\">
												<select class=\"form-control\" id=\"kunza_1\">";

												$result['html'] .= "<option value=\"\">Не выбран</option>";
												
												foreach($result['data']['customer_options']['EXW_TAB'] as $customer_exwtab) {
															$is_selected = false;
															if(!empty($connection_options) && isset($connection_options['kunza_1']) && $connection_options['kunza_1'] == $customer_exwtab['ID']) $is_selected = true;
															$selected = $is_selected ? 'selected' : '' ;
															$result['html'] .= "<option value=\"" . $customer_exwtab['ID'] . "\" " . $selected . ">" . $customer_exwtab['NAME'] . "</option>";
												}
																	
										 $result['html'] .= "</select>
													</div>
											</div>
											<div class=\"hr-line-dashed col-lg-12\"></div>"; 
									}

									//Таблица доступных договоров DOGOVOR_TAB
									if(!empty($result['data']['customer_options']['DOGOVOR_TAB'])) {

										$result['html'] .= "<div class=\"form-group\">
										<label class=\"col-lg-6 control-label\">Список доступных договоров</label>
										<div class=\"col-lg-6\">
												<select class=\"form-control\" id=\"vbeln\">";

												$result['html'] .= "<option value=\"\">Не выбран</option>";
												
												foreach($result['data']['customer_options']['DOGOVOR_TAB'] as $customer_dogovortab) {
															$is_selected = false;
															if(!empty($connection_options) && isset($connection_options['vbeln']) && $connection_options['vbeln'] == $customer_dogovortab['VBELN']) $is_selected = true;
															$selected = $is_selected ? 'selected' : '' ;
															$result['html'] .= "<option value=\"" . $customer_dogovortab['VBELN'] . "\" " . $selected . ">" . $customer_dogovortab['BSTKD'] . "</option>";
												}
																	
										 $result['html'] .= "</select>
													</div>
											</div>
											<div class=\"hr-line-dashed col-lg-12\"></div>"; 
									}

									//Таблица доступных контактных лиц CONTACT_TAB
									if(!empty($result['data']['customer_options']['CONTACT_TAB'])) {

										$result['html'] .= "<div class=\"form-group\">
										<label class=\"col-lg-6 control-label\">Список контактных лиц</label>
										<div class=\"col-lg-6\">
												<select class=\"form-control\" id=\"parnr\">";

												$result['html'] .= "<option value=\"\">Не выбран</option>";
												
												foreach($result['data']['customer_options']['CONTACT_TAB'] as $customer_contacttab) {
															$is_selected = false;
															if(!empty($connection_options) && isset($connection_options['parnr']) && $connection_options['parnr'] == $customer_contacttab['PARNR']) $is_selected = true;
															$selected = $is_selected ? 'selected' : '' ;
															$result['html'] .= "<option value=\"" . $customer_contacttab['PARNR'] . "\" " . $selected . ">" . $customer_contacttab['FNAME'] . " " .$customer_contacttab['LNAME']. "</option>";
												}
																	
										 $result['html'] .= "</select>
													</div>
											</div>
											<div class=\"hr-line-dashed col-lg-12\"></div>"; 
									}
								}

                //Доставлять заказ по частям или нет
                $incoterms = false;
                if(!empty($connection_options) && isset($connection_options['incoterms'])) $incoterms = $connection_options['incoterms'];

                $checked = $incoterms ? "checked" : "";
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Cамовывоз</label>
                		        <div class=\"col-lg-6\">
                		            <input class=\"form-control\" type=\"checkbox\" id=\"incoterms\" $checked />
                		        </div>
                		    </div>
            		        <div class=\"hr-line-dashed col-lg-12\"></div>";
            }
						
        		        
					$result['html'] .= "<div>После любых изменений дополнительных опций - сохраните настройки склады.</div>";
        	$result['html'] .= "</div></div>";

					$result['html'] .= "<style>.disabled {opacity: 0.75; pointer-events: none;}</style>";

        } else {
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Армтек Личный кабинет
        		</div>
        		<div class=\"panel-body\"><div class=\"alert alert-danger\">" . $result['message'] . "</div></div>
        	</div>";
        	
        }
    
    break;
    
    case 'get_statuses':
                        
        $supplier_statuses = array(
            1 => "Создан",
            2 => "В работе",
            3 => "Закрыт",
            5 => "Отклонен"
        );


        //Формируем ответ для вывода
        if(!empty($supplier_statuses)) {
            
            $result['html'] = "<div class=\"hpanel panel-collapse\">
    		<div class=\"panel-heading hbuilt\">
    			Статусы поставщика Armtek
    			<div class=\"panel-tools\"><a class=\"showhide\"><i class=\"fa fa-chevron-down\" style=\"color:#000;\"></i></a></div>
    		</div>
    		<div class=\"panel-body\" style=\"display: none;\">
    		    <table class=\"table\" style=\"width: 600px;\"><tbody>
    		        <tr>
    		            <td style=\"font-weight: 600;\">Наименование статуса</td>
    		            <td style=\"font-weight: 600;\">Код статуса</td>
    		        </tr>";
    		

            foreach($supplier_statuses as $status_one_code => $status_one_text) {
                
                $result['html'] .= "<tr>
                    		            <td style=\"\">" .$status_one_text. "</td>
                    		            <td style=\"\">" .$status_one_code. "</td>
                    		        </tr>";
            }

    	    $result['html'] .= "</tbody></table></div></div>";
        		

        } else {
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Статусы Веб-сервис Armtek
        		</div>
        		<div class=\"panel-body\"><div class=\"alert alert-danger\">" . $result['message'] . "</div></div>
        	</div>";
        	
        }
    
    break;
    
    default:
        $result['status'] = false;
        $result['message'] = 'Неизвестное действие';
}




exit(json_encode($result));