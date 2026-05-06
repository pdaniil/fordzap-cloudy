<?php
    $dir = APP_PATH . 'icons/';
    $handler = opendir($dir);

    //Данные, которые выводим на страницу
    $post_data = [];

    $post_data_select = $db_link->prepare("SELECT * FROM `car_service_posts` WHERE `id` = ?;");
    $post_data_select->execute([$_GET["id"]]);
    $post_data = $post_data_select->fetch(PDO::FETCH_ASSOC);

    $arr_images = [];

    while ($image = readdir($handler))
    {
        if(preg_match("#\.(jpg|gif|png)$#i", $image))
            $arr_images[] = $DP_Config->domain_path . $DP_Config->backend_dir . '/content/car_service/icons/' . $image;
    }
    closedir($handler);
?>