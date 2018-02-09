<?php

namespace Amp\GreenThread;

class InvalidAwaitError extends \Error {
    /**
     * @param mixed $yielded
     * @param string $prefix
     * @param \Throwable|null $previous
     */
    public function __construct($yielded, string $prefix, \Throwable $previous = null) {
        parent::__construct($prefix .= \sprintf(
            "; %s yielded",
            \is_object($yielded) ? \get_class($yielded) : \gettype($yielded)
        ));
    }
}
