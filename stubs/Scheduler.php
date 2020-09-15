<?php

final class Scheduler
{
    public static function create(callable $callback): Scheduler { }

    public static function inScheduler(): bool { }
}
