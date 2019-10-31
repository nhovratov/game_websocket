<?php

use NH\Game;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require 'config.php';

$app = new HttpServer(
    new WsServer(
        new Game()
    )
);

$loop = Factory::create();

$secure_websockets = new Server('0.0.0.0:8081', $loop);
$secure_websockets = new SecureServer($secure_websockets, $loop, [
    'local_cert' => $config['local_cert'],
    'local_pk' => $config['local_pk'],
    'verify_peer' => false,
    'allow_self_signed' => true
]);
$secure_websockets_server = new IoServer($app, $secure_websockets, $loop);
$secure_websockets_server->run();
