<?php

namespace Amp\GreenThread;

final class InvalidAwaitError extends \Error
{
    /**
     * @param mixed $awaited
     * @param string $prefix
     * @param \Throwable|null $previous
     */
    public function __construct($awaited, string $prefix, \Throwable $previous = null)
    {
        parent::__construct($prefix . \sprintf(
            "; %s awaited",
            \is_object($awaited) ? \get_class($awaited) : \gettype($awaited)
        ), 0, $previous);
    }
}
