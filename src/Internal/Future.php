<?php

namespace Amp\Internal;

use Amp\Promise;

final class Future implements \Awaitable
{
    /** @var Promise */
    private $promise;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    public function when(callable $when): void
    {
        $this->promise->onResolve($when);
    }
}
