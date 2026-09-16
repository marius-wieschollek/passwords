<?php
require_once __DIR__.'/../../vendor/autoload.php';

foreach (glob(__DIR__.'/Classes/*.php') as $filename) {
    include $filename;
}