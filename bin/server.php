<?php

use MyApp\Game;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Session\SessionProvider;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\HttpFoundation\Session\Storage\Handler;

require dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new SessionProvider(
            new WsServer(
                new Game()
            ),
            new Handler\MemcachedSessionHandler(new Memcached())
        )
    ),
    8080
);

$server->run();
