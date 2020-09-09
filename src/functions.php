<?php

namespace Amp;

use React\Promise\PromiseInterface as ReactPromise;
use function Amp\Internal\createTypeError;

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
 */
function await($promise)
{
    if (\is_array($promise)) {
        $promise = Promise\all($promise);
    } elseif ($promise instanceof ReactPromise) {
        $promise = Promise\adapt($promise);
    } elseif (!$promise instanceof Promise) {
        throw createTypeError([Promise::class, ReactPromise::class], $promise);
    }

    $fiber = \Fiber::getCurrent();

    if ($fiber === null) {
        return Promise\wait($promise);
    }

    $future = new Internal\Future($fiber);

    $promise->onResolve($future);

    return $future->await();
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

    \Fiber::create(static function () use ($deferred, $callback, $args): void {
        try {
            $deferred->resolve($callback(...$args));
        } catch (\Throwable $e) {
            $deferred->fail($e);
        }
    }, ...$args);

    return $deferred->promise();
}

/**
 * Returns a callable that when invoked creates a new green thread using the given callable using {@see async()},
 * passing any arguments to the function as the argument list to async() and returning promise created by async().
 *
 * @param callable $callback Green thread to create each time the function returned is invoked.
 *
 * @return callable(mixed ...$args):Promise Creates a new green thread each time the returned function is invoked. The
 *     arguments given to the returned function are passed through to the callable.
 */
function asyncCallable(callable $callback): callable
{
    return function (...$args) use ($callback): Promise {
        return async($callback, ...$args);
    };
}
