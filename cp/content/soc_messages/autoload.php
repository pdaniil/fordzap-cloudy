<?php
spl_autoload_register(function($className){
    $fileName = dirname(__FILE__) . str_replace("\\", DIRECTORY_SEPARATOR, "/". $className) . '.php';
    if(file_exists($fileName)){
        require_once $fileName;
    }
});