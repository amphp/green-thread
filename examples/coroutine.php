<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use Amp\Loop;
use function Amp\GreenThread\await;
use function Amp\GreenThread\coroutine;

// Note that the closure declares void as a return type, not Promise or Generator.
Loop::run(coroutine(function () use ($callback): void {
    $result = await(new Delayed(1000, 42));
    var_dump($result);
}));
