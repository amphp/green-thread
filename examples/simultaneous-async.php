<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use Amp\Loop;
use Amp\Promise;
use function Amp\GreenThread\async;
use function Amp\GreenThread\await;

// Note that the closure declares int as a return type, not Promise or Generator.
$callback = async(function (int $id): int {
    return await(new Delayed(1000, $id)); // Await promise resolution.
});

Loop::run(async(function () use ($callback): void {
    // Invoking $callback returns an instance of Amp\Promise.
    $result = await($callback(1)); // Runs a green thread, awaiting its resolution.
    var_dump($result);

    $result = await(Promise\all([$callback(2), $callback(3)])); // Simultaneously runs two green threads.
    var_dump($result);
}));
