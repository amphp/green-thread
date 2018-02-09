<?php

namespace Amp\GreenThread\Internal;

class Failed {
    /** @var \Throwable */
    private $exception;

    public function __construct(\Throwable $exception) {
        $this->exception = $exception;
    }

    public function throw() {
        throw $this->exception;
    }
}