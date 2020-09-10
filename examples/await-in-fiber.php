<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use function Amp\await;
use function Amp\delay;

\Fiber::run(function () {
    print 'before';
    await(delay(10));
    print 'after';
});