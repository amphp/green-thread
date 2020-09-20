<?php

namespace Amp\Internal;

use Amp\Loop;
use Amp\Promise;

final class Future implements \Awaitable
{
    private Promise $promise;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    public function onResolve(callable $onResolve): void
    {
        $this->promise->onResolve(fn (?\Throwable $e, mixed $v) => Loop::defer(fn () => $onResolve($e, $v)));
    }
}
