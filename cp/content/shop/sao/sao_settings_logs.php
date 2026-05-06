<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


/*Страничный скрипт для отображения логов*/
defined('_ASTEXE_') or die('No access');


$debug_dir = $_SERVER["DOCUMENT_ROOT"]."/modules/debug/tmp";

function scan_dir_v2($dir) {
    $ignored = array('.', '..');

    $files = array();    
    foreach (scandir($dir) as $file) {
        if (in_array($file, $ignored)) continue;
        $files[$file] = filemtime($dir . '/' . $file);
    }

    arsort($files);
    $files = array_keys($files);

    return ($files) ? $files : false;
}

function remove_tmp_files() {
    global $debug_dir;
    global $DP_Config;
    
    $files = glob($debug_dir . '/*');
    print_r($files);
    foreach($files as $file){
      if(is_file($file)) unlink($file);
    }
    $link = "/{$DP_Config->backend_dir}/shop/orders/sao_settings_logs"; 
    echo '<script>window.location.href = "'.$link.'";</script>';
}

$files_update = scan_dir_v2($debug_dir);


if(isset($_GET["r"]) && $_GET["r"] == "tmp") {
    remove_tmp_files();
}
    


?>


<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Действия
        </div>
        <div class="panel-body">
            <a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>/shop/orders/sao_settings">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/in.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Вернуться</div>
            </a>
             <a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>/shop/orders/sao_settings_logs?r=tmp">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/delete.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Удалить временные файлы</div>
            </a> 
            <a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Выход</div>
            </a>
        </div>
    </div>
</div>



<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Выберите файл для загрузки
        </div>
        <div class="panel-body">

            <?php
                if(isset($files_update) && !empty($files_update)) {
                    foreach($files_update as $file) {

                        echo "<a class=\"link\" href=\"/" . $DP_Config->backend_dir . "/shop/orders/sao_settings_logs?file=" . $file . "\">" . $file . "</a>";

                    }
                }
            ?>

        </div>
    </div>
</div>


<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Содержимое файла
        </div>
        <div class="panel-body">

                <?php

                    if(isset($_GET['file']) && !empty($_GET['file']))
                        require_once($_SERVER["DOCUMENT_ROOT"]."/modules/debug/tmp/" . $_GET['file']);

                ?>

        </div>
    </div>
</div>






<style>
    .link {
        display: inline-block;
        margin: 2px 5px;
    }
    .link:hover {
        font-weight: 700;
    }
</style>