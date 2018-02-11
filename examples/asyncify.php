<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use Amp\Loop;
use function Amp\GreenThread\asyncify;
use function Amp\GreenThread\await;

// Note that the closure declares void as a return type, not Promise or Generator.
Loop::run(asyncify(function () use ($callback): void {
    $result = await(new Delayed(1000, 42));
    var_dump($result);
}));
