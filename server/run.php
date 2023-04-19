<?php
// this is the gameserver
// run in terminal with command php run.php 8080

require __DIR__ . '/vendor/autoload.php';

echo "Start server\n";

require "src/Networking/Server.php";

