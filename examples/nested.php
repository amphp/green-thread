<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Loop;
use function Amp\await;
use function Amp\delay;

Loop::defer(function () {
    await(delay(100)); // direct await inside event loop callbacks is not allowed
    print 'inner';
});

await(delay(10));
print 'outer';
