<?php
defined('_ASTEXE_') or die('No access');

    //Подключение одной из страниц в зависимости от get параметра
    $page = "";
    if (isset($_GET["page"]))
        $page = $_GET["page"];

    if ($page == 'reasons_statuses')
        require_once  $_SERVER["DOCUMENT_ROOT"] . "/". $DP_Config->backend_dir . "/content/shop/returns/reasons_statuses.php";
    else if (isset($_GET["return_id"]))
        require_once  $_SERVER["DOCUMENT_ROOT"] . "/". $DP_Config->backend_dir . "/content/shop/returns/return.php";
    else
        require_once  $_SERVER["DOCUMENT_ROOT"] . "/". $DP_Config->backend_dir . "/content/shop/returns/returns_page.php";
?>