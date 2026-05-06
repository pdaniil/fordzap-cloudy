<?php
    $sto_data = $db_link->prepare("SELECT `caption` FROM `car_services` WHERE `id` = ?;");
    $sto_data->execute([$_GET['id']]);

    $sto_data_res = $sto_data->fetch(2);