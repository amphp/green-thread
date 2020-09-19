<?php

namespace Amp\Internal;

use Amp\Loop;
use Amp\Promise;

final class Future implements \Future
{
    private static Scheduler $scheduler;

    private Promise $promise;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    public function schedule(\Fiber $fiber): \Scheduler
    {
        $this->promise->onResolve(function (?\Throwable $exception, $value) use ($fiber): void {
            if ($exception) {
                Loop::defer(fn () => $fiber->throw($exception));
                return;
            }

            Loop::defer(fn () => $fiber->resume($value));
        });

        return self::$scheduler;
    }
}

(static fn () => self::$scheduler = new Scheduler)->bindTo(null, Future::class)();
