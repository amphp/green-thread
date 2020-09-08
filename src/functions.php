<?php

namespace Amp\GreenThread;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use React\Promise\PromiseInterface as ReactPromise;

/**
 * Execute the given callback within a new green thread.
 *
 * @param callable $callback
 * @param mixed    ...$args
 */
function execute(callable $callback, ...$args): void
{
    Loop::run(function () use ($callback, $args): Promise {
        return async($callback, ...$args);
    });
}

/**
 * Await a promise within an async function created by Amp\GreenThread\async(). Can only be called within a
 * green thread started with {@see execute()} or {@see async()}.
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
 *
 * @throws InvalidAwaitError If the given parameter does not match one of the expected types.
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

    $fiber = \Fiber::getCurrent();

    if ($fiber === null) {
        throw new \Error('Cannot await outside a green thread; create a green thread using execute() or async() first');
    }

    $resolved = false;
    $error = null;
    $value = null;

    $promise->onResolve(function (?\Throwable $e, $v) use (&$resolved, &$error, &$value, $fiber): void {
        $resolved = true;
        $error = $e;
        $value = $v;

        if ($fiber->getStatus() == \Fiber::STATUS_SUSPENDED) {
            $fiber->resume();
        }
    });

    if (!$resolved) {
        try {
            \Fiber::suspend();
        } catch (\Throwable $e) {
            // An exception is thrown if the fiber is resumed outside the function set in Promise::onResolve() or if
            // the fiber cannot be suspended.
            throw new \Error('Exception unexpectedly thrown from Fiber::suspend()', 0, $e);
        }

        if (!$resolved) {
            // $resolved should only be false if the function set in Promise::onResolve() did not resume the fiber.
            throw new \Error('Fiber resumed before promise was resolved', 0, $e ?? null);
        }
    }

    if ($error) {
        throw $error;
    }

    return $value;
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

    $fiber = new \Fiber(function () use ($deferred, $callback, $args): void {
        try {
            $deferred->resolve($callback(...$args));
        } catch (\Throwable $e) {
            $deferred->fail($e);
        }
    });

    $fiber->start();

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
function asyncCallable(callable $callback): callable
{
    return function (...$args) use ($callback): Promise {
        return async($callback, ...$args);
    };
}
