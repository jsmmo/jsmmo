<?php
// this is the gameserver
// run in terminal with command php run.php 8080

require __DIR__ . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$staticFileDeliveryHelper = new \APPNAME\Helper\StaticFileDeliveryHelper();
$errorPageHelper = new \APPNAME\Helper\ErrorPageHelper();
$sseConnectionHelper = new \APPNAME\Helper\SSEConnectionHelper();
$broadcastStream = new \React\Stream\ThroughStream(function ($data) {
    return $data;
});

$server = new \React\Http\Server(function (\Psr\Http\Message\ServerRequestInterface $request) use ($broadcastStream, $loop,$staticFileDeliveryHelper, $errorPageHelper, $sseConnectionHelper) {
    // normal http requests
    // here we deliver the gameclient to the browser
    if ($staticFileDeliveryHelper->isStaticFile($request)) {
        return $staticFileDeliveryHelper->deliverStaticFile($request);
    }

    // receive data from client
    if ($sseConnectionHelper->isSSEDataRequest($request)) {
        return $sseConnectionHelper->handleIncommingData($request);
    }

    // filter non sse connections
    if (!$sseConnectionHelper->isSSEConnectionRequest($request)) {
        return $errorPageHelper->return404Page($request);
    }

    // this is the game event connection
    return $sseConnectionHelper->handleIncomingConnection($request, $broadcastStream);
});

$loop->addPeriodicTimer(2.0, function () use ($broadcastStream) {
    $broadcastStream->write(array(
        'event' => 'HELLO',
        'data' => '1',
    ));
});

$port = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 0;
$socket = new React\Socket\Server($port, $loop);

$server->listen($socket);
$server->on('error', function (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

$loop->run();

