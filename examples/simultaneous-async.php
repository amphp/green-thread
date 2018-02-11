<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use Amp\Loop;
use function Amp\GreenThread\async;
use function Amp\GreenThread\asyncify;
use function Amp\GreenThread\await;

// Note that the closure declares int as a return type, not Promise or Generator, but executes like a coroutine.
$callback = function (int $id): int {
    return await(new Delayed(1000, $id)); // Await promise resolution.
};

Loop::run(asyncify(function () use ($callback): void {
    // Invoking $callback returns an int, but is executed asynchronously.
    $result = $callback(1); // Call a subroutine within this green thread.
    var_dump($result); // Executed after 1 second.

    // Simultaneously runs two new green threads, await their resolution in this green thread.
    $result = await([
        async($callback, 2),
        async($callback, 3), // Executed simultaneously, only 1 second will elapse during this await.
    ]);
    var_dump($result); // Executed after 2 seconds.

    $result = $callback(4);
    var_dump($result); // Executed after 3 seconds.
}));
