<?php

namespace Amp\Internal;

final class Future
{
    /** @var \Fiber */
    private $fiber;

    /** @var bool */
    private $resolved = false;

    /** @var \Throwable|null */
    private $exception;

    /** @var mixed */
    private $value;

    public function __construct(\Fiber $fiber)
    {
        $this->fiber = $fiber;
    }

    public function __invoke(?\Throwable $exception, $value): void
    {
        $this->resolved = true;

        $this->exception = $exception;
        $this->value = $value;

        if ($this->fiber->isSuspended()) {
            $this->fiber->resume();
        }
    }

    public function await()
    {
        if (!$this->resolved) {
            try {
                \Fiber::suspend();
            } catch (\Throwable $e) {
                // An exception is thrown if the fiber is resumed outside the function set in Promise::onResolve() or if
                // the fiber cannot be suspended.
                throw new \Error('Exception unexpectedly thrown from Fiber::suspend()', 0, $e);
            }

            if (!$this->resolved) {
                // $resolved should only be false if the function set in Promise::onResolve() did not resume the fiber.
                throw new \Error('Fiber resumed before promise was resolved');
            }
        }

        if ($this->exception) {
            throw $this->exception;
        }

        return $this->value;
    }
}
