#!/usr/bin/php
<?php

use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use ColorAnomaly\Quick\Application\QueueChangeSock;

require dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(new WsServer(new QueueChangeSock()), 8090);

$server->run();
