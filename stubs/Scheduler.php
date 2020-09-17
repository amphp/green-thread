<?php

final class Scheduler
{
    /**
     * Create a scheduler from the given callback.
     *
     * @param callable():void $callback
     *
     * @return Scheduler
     */
    public static function create(callable $callback): Scheduler { }

    /**
     * Pause the scheduler if it has no more events to process.
     */
    public static function pause(): void { }

    public static function inScheduler(): bool { }
}
