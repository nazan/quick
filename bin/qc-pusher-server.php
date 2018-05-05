#!/usr/bin/php
<?php

use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use ColorAnomaly\Quick\Application\QueueChangePusher;

require_once dirname(__DIR__) . '/public/bootstrap.php';

$config = nestConfig(cascadeConfig(APPLICATION_ENV, parse_ini_file(APPLICATION_PATH . '/config/application.ini', true)));

$zmqGateway = $config['app']['websocket_server']['zmq_gateway'];
$wsListen = $config['app']['websocket_server']['listen_to'];

$loop = React\EventLoop\Factory::create();
$pusher = new QueueChangePusher();

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind("{$zmqGateway['protocol']}://0.0.0.0:{$zmqGateway['port']}"); // Binding to 127.0.0.1 means the only client that can connect is itself
$pull->on('message', array($pusher, 'onQueueChange'));

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new React\Socket\Server($loop);
$webSock->listen($wsListen['port'], $wsListen['host']); // Binding to 0.0.0.0 means remotes can connect
$webServer = new IoServer(new WsServer(new WampServer($pusher)), $webSock);

$loop->run();
