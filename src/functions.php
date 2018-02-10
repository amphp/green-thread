<?php

namespace Amp\GreenThread;

use Amp\Promise;
use function Amp\call;

/**
 * This function must be used within the Closure passed to Amp\GreenThread\execute() to pause the green thread.
 * Using \Fiber::yield() directly will throw an exception within the green thread.
 *
 * @param \Amp\Promise|\React\Promise\PromiseInterface|array $promise
 *
 * @return mixed Promise resolution value.
 *
 * @throws \Throwable Promise failure reason.
 */
function await($promise) {
    return \Fiber::yield(new Internal\Awaited($promise));
}

/**
 * Creates a green thread using the given closure and argument list.
 *
 * @param \Closure $closure
 * @param mixed ...$args
 *
 * @return \Amp\Promise
 */
function execute(\Closure $closure, ...$args): Promise {
    return call(function () use ($closure, $args) {
        $fiber = new \Fiber($closure);

        $yielded = $fiber->resume(...$args);

        while ($fiber->status() === \Fiber::STATUS_SUSPENDED) {
            if (!$yielded instanceof Internal\Awaited) {
                throw new InvalidAwaitError($yielded, "Must use Amp\GreenThread\await() to pause a green thread");
            }

            try {
                $result = yield $yielded;
            } catch (\Throwable $exception) {
                $yielded = $fiber->throw($exception);
                continue;
            }

            $yielded = $fiber->resume($result);
        }

        return $yielded;
    });
}
