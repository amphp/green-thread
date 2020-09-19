<?php

namespace Amp\Internal;

use Amp\Loop;

final class Scheduler implements \Scheduler
{
    /** @var self */
    private static self $instance;

    public static function get(): self
    {
        return self::$instance;
    }

    public function run(): void
    {
        Loop::run();
    }
}

(static fn () => self::$instance = new Scheduler)->bindTo(null, Scheduler::class)();