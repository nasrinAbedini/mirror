<?php

declare(strict_types=1);

use Phalcon\Di\FactoryDefault;


error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app/');

try {
    $di = new FactoryDefault();

    $config = require APP_PATH . 'config/config.php';
    $di->setShared('config', $config);

    include APP_PATH . '/config/services.php';

    include APP_PATH . '/config/loader.php';

    require_once BASE_PATH . '/vendor/autoload.php';

    $config = $di->getConfig();




} catch (\Exception $e) {
    echo $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}