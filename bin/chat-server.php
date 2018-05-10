<?php

use MyApp\Chat;
use Ratchet\Server\IoServer;

require dirname(__DIR__) . '/vendor/autoload.php';
$server = IoServer::factory(
    new Chat(),
    8080
);

$server->run();
