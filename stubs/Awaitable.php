<?php

interface Awaitable
{
    /**
     * @param callable(?\Throwable $exception, mixed $value):void $when
     */
    public function when(callable $when): void;
}
