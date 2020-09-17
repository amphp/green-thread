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
 * @throws \Throwable Promise failure reason.
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
        throw createTypeError([Promise::class, ReactPromise::class, 'array'], $promise);
    }

    return \Fiber::suspend(new Internal\Future($promise));
}

/**
 * Run the event loop until it is either stopped explicitly, no referenced watchers exist anymore, or an
 * exception is thrown that cannot be handled.
 *
 * Exceptions that cannot be handled are exceptions thrown from an error handler or exceptions that would be passed to
 * an error handler but none exists to handle them.
 */
function awaitPending(): void
{
    if (\Fiber::inFiber()) {
        throw new \Error(__FUNCTION__ . " may only be called from the root context, not from within a fiber.");
    }

    if (Loop::getInfo()['running']) {
        throw new \Error(__FUNCTION__ . " can't be used inside event loop callbacks. Tip: Wrap your callback with asyncCallable.");
    }

    Loop::run();
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

    Loop::defer(static function () use ($deferred, $callback, $args): void {
        \Fiber::run(static function () use ($deferred, $callback, $args): void {
            try {
                $deferred->resolve($callback(...$args));
            } catch (\Throwable $e) {
                $deferred->fail($e);
            }
        }, ...$args);
    });

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
    return static function (...$args) use ($callback): Promise {
        return async($callback, ...$args);
    };
}
