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
 * @param callable $fiber
 * @param mixed ...$args
 *
 * @return \Amp\Promise
 */
function execute(callable $fiber, ...$args): Promise {
    return call(function () use ($fiber, $args) {
        $fiber = new \Fiber($fiber);

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
