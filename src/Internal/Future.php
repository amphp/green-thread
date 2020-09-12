<?php

namespace Amp\Internal;

use Amp\Promise;

final class Future implements \Future
{
    /** @var Promise */
    private $promise;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    public function schedule(\Fiber $fiber): void
    {
        $this->promise->onResolve(function (?\Throwable $exception, $value) use ($fiber): void {
            \assert($fiber->isSuspended(), "Fiber resumed outside of future!");

            if ($exception) {
                $fiber->throw($exception);
                return;
            }

            $fiber->resume($value);
        });
    }
}
