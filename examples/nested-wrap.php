<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Loop;
use function Amp\asyncCallable;
use function Amp\await;
use function Amp\delay;

Loop::defer(asyncCallable(function () {
    await(delay(100));
    echo 'inner', PHP_EOL;
}));

await(delay(10));
echo 'outer', PHP_EOL;
