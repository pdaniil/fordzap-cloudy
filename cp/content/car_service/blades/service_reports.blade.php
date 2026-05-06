<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Период
        </div>
        <div class="panel-body">
            <div class="form-group col-lg-2">
                <label for="" class="col-lg-4 control-label">
                    Дата с
                </label>
                <div class="col-lg-8">
                    <div style="position:relative;height:34px;">
                        <input style="position:absolute; z-index:2; opacity:0;" type="text"  id="time_from" value="<?php echo $time_from; ?>" class="form-control" />
                        <input style="position:absolute; z-index:1; <?=(!empty($time_from))?'background:#b9fcab;':'';?>" type="text" id="time_from_show" class="form-control" />
                        <script>
                            //Инициализируем datetimepicker
                            jQuery("#time_from").datetimepicker({
                                lang:"ru",
                                closeOnDateSelect:true,
                                closeOnTimeSelect:false,
                                dayOfWeekStart:1,
                                format:'unixtime',
                                onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
                                {
                                    var time_string = "";
                                    var date_ob = new Date(current_time);
                                    time_string += date_ob.getDate()+".";
                                    time_string += (date_ob.getMonth() + 1)+".";
                                    time_string += date_ob.getFullYear()+" ";
                                    time_string += date_ob.getHours()+":"+date_ob.getMinutes();
                                    document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
                                }
                                <?php
                                if($time_from != "")
                                {
                                ?>
                                ,
                                onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
                                {
                                    var time_string = "";
                                    var date_ob = new Date(current_time);
                                    time_string += date_ob.getDate()+".";
                                    time_string += (date_ob.getMonth() + 1)+".";
                                    time_string += date_ob.getFullYear()+" ";
                                    time_string += date_ob.getHours()+":"+date_ob.getMinutes();
                                    document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
                                }
                                <?php
                                }
                                ?>
                            });
                        </script>
                    </div>
                </div>
            </div>
            <div class="form-group col-lg-2">
                <label for="" class="col-lg-4 control-label">
                    Дата по
                </label>
                <div class="col-lg-8">
                    <div style="position:relative;height:34px;">
                        <input style="position:absolute; z-index:2; opacity:0;" type="text"  id="time_to" value="<?php echo $time_to; ?>" class="form-control" />
                        <input style="position:absolute; z-index:1; <?=(!empty($time_to))?'background:#b9fcab;':'';?>" type="text" id="time_to_show" class="form-control" />
                        <script>
                            //Инициализируем datetimepicker
                            jQuery("#time_to").datetimepicker({
                                lang:"ru",
                                closeOnDateSelect:true,
                                closeOnTimeSelect:false,
                                dayOfWeekStart:1,
                                format:'unixtime',
                                onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
                                {
                                    var time_string = "";
                                    var date_ob = new Date(current_time);
                                    time_string += date_ob.getDate()+".";
                                    time_string += (date_ob.getMonth() + 1)+".";
                                    time_string += date_ob.getFullYear()+" ";
                                    time_string += date_ob.getHours()+":"+date_ob.getMinutes();
                                    document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
                                }
                                <?php
                                if($time_to != "")
                                {
                                ?>
                                ,
                                onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
                                {
                                    var time_string = "";
                                    var date_ob = new Date(current_time);
                                    time_string += date_ob.getDate()+".";
                                    time_string += (date_ob.getMonth() + 1)+".";
                                    time_string += date_ob.getFullYear()+" ";
                                    time_string += date_ob.getHours()+":"+date_ob.getMinutes();
                                    document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
                                }
                                <?php
                                }
                                ?>
                            });
                        </script>
                    </div>
                </div>
            </div>
            <div class="form-group col-lg-4">
                <div class="col-lg-6">
                    <button class="btn btn-success" onclick="loadReports()">Загрузить</button>
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
        </div>
    </div>
</div>
<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            СТО
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-condensed table-striped">
                    <thead>
                        <th>ID</th>
                        <th>Наименование</th>
                        <th>Всего записей</th>
                        <th>Всего валидных записей</th>
                        <th>Общая ценность записей</th>
                    </thead>
                    <tbody>
                        <?php foreach ($arr_sto_data as $sto) : ?>
                            <tr>
                                <td><?=$sto['sto_id'];?></td>
                                <td><?=$sto['sto_caption'];?></td>
                                <td><?=$sto['count_records'];?></td>
                                <td><?=$sto['valid_records'];?></td>
                                <td><?=print_price($sto['sum_price_records']);?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Сотрудники
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-condensed table-striped">
                    <thead>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Ставка</th>
                        <th>Процент</th>
                        <th>Кол-во записей</th>
                        <th>Сумма стоимости услуг</th>
                        <th>З/п по сменам</th>
                        <th>С процента от услуг</th>
                        <th>Общая з/п</th> 
                    </thead>
                    <tbody>
                        <?php foreach ($arr_workers_data as $worker) : ?>
                            <tr>
                                <td><?=$worker['id'];?></td>
                                <td><?=$worker['fio'];?></td>
                                <td><?=$worker['standart_hourly_rate'];?></td>
                                <td><?=$worker['service_percent'];?></td>
                                <td><?=$worker['record_count'];?></td>
                                <td><?=print_price($worker['total_service_price']);?></td>
                                <td><?=print_price($worker['salary']);?></td>
                                <td><?=print_price($worker['percent']);?></td>
                                <td><?=print_price($worker['salary'] + $worker['percent']);?></td>
                            </tr>
                        <? endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
