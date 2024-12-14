<?php

use Phalcon\Mvc\Router;

$router = new Router();


$router->addPost(
    '/event/send',
    [
        'controller' => 'event',
        'action'     => 'send',
    ]
);

return $router;