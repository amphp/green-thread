<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Loop;
use function Amp\asyncCallable;
use function Amp\await;
use function Amp\awaitPending;
use function Amp\delay;

Loop::defer(asyncCallable(function () {
    await(delay(100));
    print 'inner';
}));

await(delay(10));
print 'outer';

awaitPending();
