<?php

namespace Amp\Internal;

use Amp\Loop;
use Amp\Promise;

final class Future implements \Future
{
    private Promise $promise;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    public function schedule(\Fiber $fiber): \FiberScheduler
    {
        $this->promise->onResolve(function (?\Throwable $exception, $value) use ($fiber): void {
            if ($exception) {
                Loop::defer(static fn () => $fiber->throw($exception));
            } else {
                Loop::defer(static fn () => $fiber->resume($value));
            }
        });

        return Scheduler::get();
    }
}
