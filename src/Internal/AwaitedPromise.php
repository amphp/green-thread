<?php

namespace Amp\GreenThread\Internal;

use Amp\Promise;

class AwaitedPromise implements Promise {
    /** @var \Amp\Promise */
    private $promise;

    public function __construct(Promise $promise) {
        $this->promise = $promise;
    }

    public function onResolve(callable $onResolved) {
        $this->promise->onResolve($onResolved);
    }
}
