<?php

namespace Amp\GreenThread\Internal;

use Amp\GreenThread\InvalidAwaitError;
use Amp\Promise;
use React\Promise\PromiseInterface as ReactPromise;

class Awaited implements Promise {
    /** @var \Amp\Promise */
    private $promise;

    public function __construct($promise) {
        if (!$promise instanceof Promise) {
            try {
                if (\is_array($promise)) {
                    $this->promise = Promise\all($promise);
                    return;
                }

                if ($promise instanceof ReactPromise) {
                    $this->promise = Promise\adapt($promise);
                    return;
                }

                // No match, continue to throwing below.
            } catch (\Throwable $exception) {
                // Conversion to promise failed, fall-through to throwing below.
            }

            throw new InvalidAwaitError(
                $promise,
                \sprintf(
                    "Unexpected value awaited; Expected an instance of %s or %s or an array of such instances",
                    Promise::class,
                    ReactPromise::class
                ),
                $exception ?? null
            );
        }

        $this->promise = $promise;
    }

    public function onResolve(callable $onResolved) {
        $this->promise->onResolve($onResolved);
    }
}
