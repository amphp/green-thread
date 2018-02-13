<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use Amp\Loop;
use function Amp\GreenThread\await;
use function Amp\GreenThread\coroutine;

// Any function can call await(), not only closures. Calling this function outside a green thread with throw an Error.
function doAsyncTask(int $id): int {
    $value = await(new Delayed(1000, $id)); // Wait 1 second, simulating async IO.

    if ($value & 1) {
        throw new Exception("Something went wrong! Value: " . $value);
    }

    return $value;
};

Loop::run(coroutine(function () use ($callback): void {
    // Invoking $callback returns an int, but is executed asynchronously.
    $result = doAsyncTask(2); // Call a subroutine within this green thread, taking 1 second to return.
    var_dump($result);

    try {
        $result = doAsyncTask(3); // Call subroutine again, which now throws an exception after 1 second.
        var_dump($result);
    } catch (Exception $exception) { // Exceptions thrown from async subroutines can be caught like any other.
        var_dump("Caught exception: " . $exception->getMessage());
    }

    $result = doAsyncTask(5); // Make another async subroutine call that will now throw from green thread.
    var_dump($result);
}));
