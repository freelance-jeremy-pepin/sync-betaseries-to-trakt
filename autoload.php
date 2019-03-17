<?php

require_once __DIR__ . '/helpers.php';
require_once base_path().'/vendor/autoload.php';

foreach (glob(base_path().'/app/Repositories/*.php') as $filename)
{
    require_once $filename;
}