<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use Amp\Loop;
use function Amp\GreenThread\await;
use function Amp\GreenThread\execute;

Loop::run(function () {
    $result = yield execute(function () {
        return await(new Delayed(1000, 42));
    });

    var_dump($result);
});
