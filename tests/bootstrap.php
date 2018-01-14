<?php

spl_autoload_register(function(string $class_name) {
    $class_name = str_replace('\\', '/', $class_name);
    $class_name = str_replace('OCA/Passwords/', __DIR__.'/../src/lib/', $class_name).'.php';

    if(is_file($class_name)) {
        require_once $class_name;
        return true;
    }

    return false;
});

foreach (glob(__DIR__.'/Classes/*.php') as $filename) {
    include $filename;
}