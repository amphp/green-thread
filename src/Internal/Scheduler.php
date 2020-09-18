<?php

namespace Amp\Internal;

use Amp\Loop;

final class Scheduler implements \Scheduler
{
    public function run(): void
    {
        Loop::run();
    }
}
