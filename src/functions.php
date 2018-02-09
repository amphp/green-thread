<?php

namespace Amp\GreenThread;

use Amp\Promise;
use React\Promise\PromiseInterface as ReactPromise;
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
    while (!$promise instanceof Promise) {
        try {
            if (\is_array($promise)) {
                $promise = Promise\all($promise);
                break;
            }

            if ($promise instanceof ReactPromise) {
                $promise = Promise\adapt($promise);
                break;
            }

            // No match, continue to throwing below.
        } catch (\Throwable $exception) {
            // Conversion to promise failed, fall-through to throwing below.
        }

        throw new InvalidAwaitError(
            $promise,
            \sprintf(
                "Unexpected value awaited; Expected an instance of %s or %s or an array of such instances",
                Promise::class,
                ReactPromise::class
            ),
            $exception ?? null
        );
    }

    $value = \Fiber::yield(new Internal\AwaitedPromise($promise));

    if ($value instanceof Internal\Failed) {
        $value->throw();
    }

    return $value;
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
            if (!$yielded instanceof Internal\AwaitedPromise) {
                throw new InvalidAwaitError($yielded, "Must use Amp\GreenThread\await() to pause a green thread");
            }

            try {
                $result = yield $yielded;
            } catch (\Throwable $exception) {
                $yielded = $fiber->resume(new Internal\Failed($exception));
                continue;
            }

            $yielded = $fiber->resume($result);
        }

        return $yielded;
    });
}
