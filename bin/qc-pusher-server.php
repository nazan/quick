#!/usr/bin/php
<?php

use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use ColorAnomaly\Quick\Application\QueueChangePusher;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$pusher = new QueueChangePusher();

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
$pull->on('message', array($pusher, 'onQueueChange'));

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new React\Socket\Server($loop);
$webSock->listen(8090, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
$webServer = new IoServer(new WsServer(new WampServer($pusher)), $webSock);

$loop->run();
