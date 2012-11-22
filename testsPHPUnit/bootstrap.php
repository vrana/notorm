<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('APP_LIBRARY', __DIR__ . '/../');
set_include_path(implode(PATH_SEPARATOR, array(
    APP_LIBRARY,
    get_include_path()
   ))    
);
//Simple Autoloader
spl_autoload_register(function($className) {
        $fileParts = explode('\\', ltrim($className, '\\'));

        if (false !== strpos(end($fileParts), '_'))
            array_splice($fileParts, -1, 1, explode('_', current($fileParts)));

        $fileName = implode(DIRECTORY_SEPARATOR, $fileParts) . '.php';
     
        if (stream_resolve_include_path($fileName))
            require $fileName;
    }
);

