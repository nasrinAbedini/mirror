<?php


// use Dotenv\Dotenv;

// echo getenv('DATABASE_HOST');

// $dotenv = Dotenv::createImmutable(BASE_PATH, '/'); 
// $dotenv->load();
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

return new \Phalcon\Config\Config([
    'database' => [
        'adapter'     => 'Mysql',
        'host'        => 'mariadb',
        'port'        => '3307',
        'username'    => 'root',
        'password'    => 'mirror',
        'dbname'      => 'mirror',
        'charset'     => 'utf8',
    ],
    'application' => [
        'appDir'         => APP_PATH . '/',
        'controllersDir' => APP_PATH . '/controllers/',
        'modelsDir'      => APP_PATH . '/models/',
        'servicesDir'    => APP_PATH . '/services/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'viewsDir'       => APP_PATH . '/views/',
        'pluginsDir'     => APP_PATH . '/plugins/',
        'libraryDir'     => APP_PATH . '/library/',
        'cacheDir'       => BASE_PATH . '/cache/',
        'baseUri'        => '/',
    ],
    'rabbitmq' => [
        'host'     => 'rabbitmq', 
        'port'     => 5672,
        'username' => 'guest',
        'password' => 'guest',
        'vhost'    => '/', 
        'queue'    => [
            'eventBatch' => 'event_batch_queue',
        ],
    ],
]);