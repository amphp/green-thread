<?php

namespace Amp\GreenThread;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use React\Promise\PromiseInterface as ReactPromise;

/**
 * Await a promise within an async function created by Amp\GreenThread\async().
 *
 * @template TValue
 *
 * @param Promise|ReactPromise|array<Promise|ReactPromise> $promise
 *
 * @psalm-param Promise<TValue>|ReactPromise|array<Promise<TValue>|ReactPromise> $promise
 *
 * @return mixed Promise resolution value.
 *
 * @psalm-return TValue|array<TValue>
 */
function await($promise)
{
    while (!$promise instanceof Promise) {
        try {
            if (\is_array($promise)) {
                $promise = Promise\all($promise);
                break;
            }

            /** @psalm-suppress RedundantConditionGivenDocblockType */
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
                "Unexpected await; Expected an instance of %s or %s or an array of such instances",
                Promise::class,
                ReactPromise::class
            ),
            $exception ?? null
        );
    }

    return \Fiber::suspend(new Internal\Suspension($promise));
}

/**
 * Creates a green thread using the given callable and argument list.
 *
 * @template TValue
 *
 * @param callable(mixed ...$args):TValue $callback
 * @param mixed ...$args
 *
 * @return Promise
 *
 * @psalm-return Promise<TValue>
 */
function async(callable $callback, ...$args): Promise
{
    $deferred = new Deferred;

    try {
        $fiber = new \Fiber($callback);

        $awaited = $fiber->start(...$args);

        if (!$awaited instanceof Internal\Suspension) {
            if ($fiber->getStatus() !== \Fiber::STATUS_SUSPENDED) {
                $deferred->resolve($fiber->getReturn());
                return $deferred->promise();
            }

            throw new InvalidAwaitError($awaited, "Use Amp\GreenThread\await() to await promises");
        }

        /** @psalm-suppress MissingClosureParamType */
        $onResolve = function (?\Throwable $exception, $value) use (&$onResolve, $fiber, $deferred): void {
            static $thrown, $result, $immediate = true;

            $thrown = $exception;
            $result = $value;

            if (!$immediate) {
                $immediate = true;
                return;
            }

            try {
                try {
                    do {
                        if ($thrown) {
                            // Throw exception at last await.
                            $awaited = $fiber->throw($thrown);
                        } else {
                            // Send the new value and execute to next await.
                            $awaited = $fiber->resume($result);
                        }

                        if (!$awaited instanceof Internal\Suspension) {
                            if ($fiber->getStatus() !== \Fiber::STATUS_SUSPENDED) {
                                $onResolve = null;
                                $deferred->resolve($fiber->getReturn());
                                return;
                            }

                            throw new InvalidAwaitError($awaited, "Use Amp\GreenThread\await() to await promises");
                        }

                        $immediate = false;
                        $awaited->promise()->onResolve($onResolve);
                    } while ($immediate);

                    $immediate = true;
                } catch (\Throwable $exception) {
                    $deferred->fail($exception);
                    $onResolve = null;
                } finally {
                    $thrown = null;
                    $result = null;
                }
            } catch (\Throwable $exception) {
                Loop::defer(static function () use ($exception) {
                    throw $exception;
                });
            }
        };

        $awaited->promise()->onResolve($onResolve);
    } catch (\Throwable $exception) {
        $deferred->fail($exception);
    }

    return $deferred->promise();
}

/**
 * Returns a callable that when invoked creates a new green thread using the given callable using {@see async()},
 * passing any arguments to the function as the argument list to async() and returning promise created by async().
 *
 * @param callable $callback Green thread to create each time the function returned is invoked.
 *
 * @return callable(mixed ...$args):Promise Creates a new green thread each time the returned function is invoked. The arguments given to
 *    the returned function are passed through to the callable.
 */
function continuation(callable $callback): callable
{
    return function (...$args) use ($callback): Promise {
        return async($callback, ...$args);
    };
}

/**
 * Returns a callable that when invoked creates a new green thread using the given callable using {@see async()} similar
 * to {@see coroutine()}, however unlike coroutine(), the promise is not returned, rather {@see Promise\rethrow()} is
 * called, forwarding any unhandled exceptions to the loop exception handler.
 *
 * Use this function to create a coroutine-aware callable for a non-promise-aware callback caller.
 *
 * @param callable(mixed ...$args):mixed $callback
 *
 * @return callable(mixed ...$args):void
 *
 * @see coroutine()
 */
function asyncContinuation(callable $callback): callable
{
    return function (...$args) use ($callback): void {
        Promise\rethrow(async($callback, ...$args));
    };
}
