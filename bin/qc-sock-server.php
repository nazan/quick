#!/usr/bin/php
<?php

use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use ColorAnomaly\Quick\Application\QueueChangeSock;

require_once dirname(__DIR__) . '/public/bootstrap.php';

$config = nestConfig(cascadeConfig(APPLICATION_ENV, parse_ini_file(APPLICATION_PATH . '/config/application.ini', true)));

$wsListen = $config['app']['websocket_server']['listen_to'];

$server = IoServer::factory(new WsServer(new QueueChangeSock()), $wsListen['port']);

$server->run();
