<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use Amp\Loop;
use function Amp\GreenThread\async;
use function Amp\GreenThread\await;

Loop::run(function () {
    // Note that the closure declares int as a return type, not Promise or Generator.
    $result = yield async(function (): int {
        return await(new Delayed(1000, 42));
    });

    var_dump($result);
});
