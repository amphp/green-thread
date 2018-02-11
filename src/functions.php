<?php

namespace Amp\GreenThread;

use Amp\Promise;
use function Amp\call;

/**
 * Alias of \Fiber::yield().
 *
 * @param \Amp\Promise|\React\Promise\PromiseInterface|array $promise
 *
 * @return mixed Promise resolution value.
 *
 * @throws \Throwable Promise failure reason.
 */
function await($promise) {
    return \Fiber::yield($promise);
}

/**
 * Creates a green thread using the given callable and argument list.
 *
 * @param callable $callback
 * @param mixed ...$args
 *
 * @return \Amp\Promise
 */
function execute(callable $callback, ...$args): Promise {
    return call(function () use ($callback, $args) {
        $fiber = new \Fiber($callback);

        $yielded = $fiber->resume(...$args);

        while ($fiber->status() === \Fiber::STATUS_SUSPENDED) {
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

/**
 * @param callable $callback Green thread to create each time the function returned is invoked.
 *
 * @return callable Creates a new green thread each time the returned function is invoked. The arguments given to
 *    the returned function are passed through to the callable.
 */
function async(callable $callback): callable {
    return function (...$args) use ($callback): Promise {
        return execute($callback, ...$args);
    };
}
