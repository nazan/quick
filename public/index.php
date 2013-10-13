<?php

use ColorAnomaly\Quick\Domain\QueueException;
use ColorAnomaly\Quick\Domain\QueueEmptyException;
use ColorAnomaly\Quick\Domain\QueueOverflowException;
use ColorAnomaly\Quick\Domain\QueueInappropriateReadException;

require_once 'bootstrap.php';

$config = nestConfig(cascadeConfig(APPLICATION_ENV, parse_ini_file(APPLICATION_PATH . '/config/application.ini', true)));

// Prepare app
$app = new ColorAnomaly\Quick\Application\SlimApp(array(
    'templates.path' => '../templates',
        ), $config, APPLICATION_PATH, APPLICATION_ENV);

$app->add(new \ColorAnomaly\Quick\Application\UseCaseBasedRedirectMiddleware());

// Create monolog logger and store logger in container as singleton 
// (Singleton resources retrieve the same log resource definition each time)
$app->container->singleton('log', function () {
    $log = new \Monolog\Logger('slim-skeleton');
    $log->pushHandler(new \Monolog\Handler\StreamHandler('../logs/app.log', \Psr\Log\LogLevel::DEBUG));
    return $log;
});

// Prepare view
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());

// Define routes
$app->get('/', function () use ($app) {
    // Sample log message
    $app->log->info("Slim-Skeleton '/' route");
    // Render index view
    $app->render('index.html');
});

$app->map('/register', function () use ($app) {
    $queues = $app->getQueueService()->getQueueNames();
    $msg = '';
    if ($app->request->isPost()) {
        $posted = $app->request->post();
        $uc = $app->getUserContext();

        try {
            if ($app->getQueueService()->registerActor($posted['queue'], $uc['id'], $posted['role'], $posted['counter'])) {
                $app->redirect('/');
            } else {
                $msg = 'Failed';
            }
        } catch (QueueException $excp) {
            $msg = $excp->getMessage();
        }
    }

    $app->render('register.html', array('msg' => $msg, 'queues' => $queues));
})->via('GET', 'POST');

$app->get('/unregister', function () use ($app) {
    $uc = $app->getUserContext();
    
    $qs = $app->getQueueService();
    
    $qs->unregisterActor($uc['id']);
    
    if(isset($uc['role']) && $uc['role'] == '/dequeue') {
        $queueId = $uc['queue']->getName();
        
        try {
            $queue = $qs->getQueue($queueId);

            $context = new ZMQContext();
            $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'queue change pusher');
            $socket->connect("tcp://localhost:5555");

            $socket->send(json_encode($queue->getDisplayInfo()));
        } catch (QueueInappropriateReadException $irExcp) {
            echo "Queue $queueId display data could not be retrieved." . PHP_EOL;
        } catch (\Exception $sockExcp) {
            echo "Socket comm failed." . PHP_EOL;
        }
    }
    
    $app->redirect('/register');
});

$app->map('/enqueue', function () use ($app) {
    $uc = $app->getUserContext();
    $queueId = $uc['queue']->getName();
    $qs = $app->getQueueService();
    $msg = '';

    try {
        if ($app->request->isPost()) {
            try {
                $token = $qs->enqueueToken($queueId);
                $msg = "$token";
            } catch (QueueOverflowException $ofExcp) {
                $lastToken = $qs->getLastTokenDispensed($queueId);
                $msg = "No more tokens left. Last token dispensed is $lastToken";
            }
        } else { // GET request
            try {
                $lastToken = $qs->getLastTokenDispensed($queueId);
                $msg = "Last token dispensed $lastToken";
            } catch (QueueInappropriateReadException $irExcp) {
                $startingToken = $uc['queue']->getLowerBound();
                $msg = "No tokens dispensed yet. Starting token is $startingToken";
            }
        }
    } catch (QueueException $excp) {
        $qs->unregisterActor($uc['id']);
        $app->redirect('/register');
    }

    $app->render('enqueue.html', array('queueId' => $queueId, 'msg' => $msg));
})->via('GET', 'POST');

$app->map('/dequeue', function () use ($app) {
    $uc = $app->getUserContext();
    $queueId = $uc['queue']->getName();
    $actorId = $uc['id'];
    $qs = $app->getQueueService();
    $msg = '';

    try {
        if ($app->request->isPost()) {
            try {
                $token = $qs->dequeueToken($queueId, $actorId);
                $msg = "$token";

                try {
                    $queue = $qs->getQueue($queueId);

                    $context = new ZMQContext();
                    $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'queue change pusher');
                    $socket->connect("tcp://localhost:5555");

                    $socket->send(json_encode($queue->getDisplayInfo()));
                } catch (QueueInappropriateReadException $irExcp) {
                    echo "Queue $queueId display data could not be retrieved." . PHP_EOL;
                } catch (\Exception $sockExcp) {
                    echo "Socket comm failed." . PHP_EOL;
                }
            } catch (QueueEmptyException $eExcp) {
                $msg = "No one in line.";
            }
        } else {
            try {
                $currentToken = $qs->getCurrentTokenServed($actorId);
                $msg = "You are currently serving $currentToken";
            } catch (QueueInappropriateReadException $irExcp) {
                $msg = "You have not served any token yet.";
            }
        }
    } catch (QueueException $excp) {
        $qs->unregisterActor($uc['id']);
        $app->redirect('/register');
    }

    $app->render('dequeue.html', array('queueId' => $queueId, 'msg' => $msg));
})->via('GET', 'POST');

$app->get('/display', function () use ($app) {
    $uc = $app->getUserContext();

    $queue = $uc['queue'];

    $displayData = $queue->getDisplayInfo();

    $app->render('display.html', $displayData);
});

$app->get('/display-all', function () use ($app) {
    $app->render('display-all.html');
});

$app->get('/queue(/:queueId)', function ($queueId = null) use ($app) {
    $qs = $app->getQueueService();

    if (is_null($queueId)) {
        $queues = array();
        foreach($qs->getQueue() as $q) {
            $queues[] = $q->getDisplayInfo();
        }
    } else {
        try {
            $queues = array($qs->getQueue($queueId)->getDisplayInfo());
        } catch(QueueInappropriateReadException $irExcp) {
            $queues = array();
        }
    }

    $resp = $app->response();

    $resp['Content-Type'] = 'application/json';
    $resp['X-Powered-By'] = 'Slim';

    echo json_encode($queues);
});

$app->get('/add-queue/:name(/:lower(/:upper))', function($name, $lower = 1000, $upper = 1100) use ($app) {
    $service = $app->getQueueService();

    $service->addQueue($name, $lower, $upper);

    $app->render('action-complete.html', array('msg' => "$name queue added successfully."));
});

// Run app
$app->run();
