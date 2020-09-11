<?php

final class Fiber implements Awaitable
{
    /**
     * @param callable $callback Function to invoke when starting the Fiber.
     * @param mixed ...$args Function arguments.
     *
     * @return Awaitable
     */
    public static function run(callable $callback, mixed ...$args): Awaitable { }

    /**
     * Private constructor to force use of {@see run()}.
     */
    private function __construct() { }

    /**
     * @inheritDoc
     */
    public function when(callable $when): void { }

    /**
     * Suspend execution of the fiber until the given awaitable resolves.
     *
     * @param Awaitable $awaitable
     *
     * @return mixed Awaitable resolution value.
     *
     * @throws \Throwable Awaitable failure reason.
     * @throws FiberError Thrown if not within a fiber context.
     */
    public static function await(Awaitable $awaitable): mixed { }

    /**
     * Returns the current Fiber context or null if not within a fiber.
     *
     * @return bool True if currently executing within a fiber context, false if in root context.
     */
    public static function inFiber(): bool { }
}
