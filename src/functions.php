<?php

namespace Amp;

use React\Promise\PromiseInterface as ReactPromise;

/**
 * Await the resolution of the given promise. The function does not return until the promise has been
 * resolved. The promise resolution value is returned or the promise failure reason is thrown.
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
function await(Promise|ReactPromise|array $promise): mixed
{
    if (!$promise instanceof Promise) {
        if (\is_array($promise)) {
            $promise = Promise\all($promise);
        } elseif ($promise instanceof ReactPromise) {
            $promise = Promise\adapt($promise);
        }
    }

    return \Fiber::await(new Internal\Future($promise), Internal\Scheduler::get());
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
function async(callable $callback, mixed ...$args): Promise
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
    return static fn (mixed ...$args): Promise => async($callback, ...$args);
}
