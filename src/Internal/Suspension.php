<?php

namespace Amp\GreenThread\Internal;

use Amp\Promise;

/** @internal */
final class Suspension
{
    private $promise;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    public function promise(): Promise
    {
        return $this->promise;
    }
}
