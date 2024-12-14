<?php
declare(strict_types=1);

use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Config;

error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/html/app');

try {
    $di = new CliDI();
    $config = require APP_PATH . '/config/config.php';
    $di->setShared('config', $config);
    include APP_PATH . '/config/services.php';
    
    include APP_PATH . '/config/loader.php';

    require_once BASE_PATH . '/html/vendor/autoload.php';

    $console = new ConsoleApp($di);

    $di->setShared('dispatcher', function () {
        $dispatcher = new \Phalcon\Cli\Dispatcher();
        $dispatcher->setDefaultNamespace('App\Tasks');
        return $dispatcher;
    });

    require_once BASE_PATH . '/html/tasks/WorkerTask.php';

    $workerTask = new \App\Tasks\WorkerTask(); 
    $workerTask->mainAction();

} catch (\Exception $e) {
    echo $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}