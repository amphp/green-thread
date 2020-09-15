<?php

namespace Amp\Internal;

use Amp\Loop;
use Amp\Promise;

final class Future implements \Future
{
    /** @var \Scheduler */
    private static $scheduler;

    /** @var Promise */
    private $promise;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    public function schedule(\Fiber $fiber): \Scheduler
    {
        $this->promise->onResolve(function (?\Throwable $exception, $value) use ($fiber): void {
            \assert($fiber->isSuspended(), "Fiber resumed outside of future!");

            if ($exception) {
                $fiber->throw($exception);
                return;
            }

            $fiber->resume($value);
        });

        return self::$scheduler;
    }
}

(static function (): void {
    self::$scheduler = \Scheduler::create(static function (): void {
        Loop::run();
    });
})->bindTo(null, Future::class)();
