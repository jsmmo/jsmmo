<?php
srand(\APPNAME\Service\RandomService::getSeed() + time());

$loop = React\EventLoop\Factory::create();

$staticFileDeliveryHelper = new \APPNAME\Service\StaticFileDeliveryService();
$errorPageHelper = new \APPNAME\Service\ErrorPageService();
$sseConnectionHelper = new \APPNAME\Networking\SSEConnectionHelper();
$broadcastStream = new \React\Stream\ThroughStream(function ($data) {
    return $data;
});

$server = new \React\Http\Server(function (\Psr\Http\Message\ServerRequestInterface $request) use ($broadcastStream, $loop,$staticFileDeliveryHelper, $errorPageHelper, $sseConnectionHelper) {
    // normal http requests
    // here we deliver the game client to the browser
    if ($staticFileDeliveryHelper->isStaticFile($request)) {
        return $staticFileDeliveryHelper->deliverStaticFile($request);
    }

    // receive data from client
    if ($sseConnectionHelper->isSSEDataRequest($request)) {
        return $sseConnectionHelper->handleIncommingData($request);
    }

    // filter non sse connections
    if (!$sseConnectionHelper->isSSEConnectionRequest($request)) {
        return $errorPageHelper->return404Page();
    }

    // this is the game event connection
    return $sseConnectionHelper->handleIncomingConnection($request, $broadcastStream);
});

$loop->addPeriodicTimer(2.0, function () use ($sseConnectionHelper) {
    $connections = $sseConnectionHelper->getConnections();

    /** @var \APPNAME\Networking\Connection $connection */
    foreach($connections as $connection) {
        $connection->getStream()->write(array(
            'event' => 'HELLO',
            'data' => '1',
        ));
    }
});


$loop->addPeriodicTimer(10.0, function () use ($sseConnectionHelper) {
    $connections = $sseConnectionHelper->getConnections();

    /** @var \APPNAME\Networking\Connection $connection */
    foreach($connections as $key => $connection) {
        if ($connection->getLastKeepAlive() < time() - 15) {
            $sseConnectionHelper->removeConnection($key);
            echo "Remove client connection: ".$key;
        }
    }
});


$port = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 0;
$socket = new React\Socket\Server($port, $loop);

$server->listen($socket);
$server->on('error', function (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

$loop->run();