<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use Amp\Loop;
use Amp\Promise;

class Continuation implements Future
{
    private $promise;
    private static $scheduler;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;

        if (self::$scheduler === null) {
            self::$scheduler = Scheduler::create(function (): void {
                Loop::run();
                throw new \Error('This should never be reached');
            });
        }
    }

    public function schedule(Fiber $fiber): Scheduler
    {
        $this->promise->onResolve(function (?\Throwable $exception, mixed $value) use ($fiber): void {
            if ($exception) {
                $fiber->throw($exception);
                return;
            }

            $fiber->resume($value);
        });

        return self::$scheduler;
    }
}

$fiber = Fiber::run(function (): void {
    echo "Inner\n";
    $fiber1 = Fiber::run(function () {
        var_dump(Fiber::suspend(new Continuation(new Delayed(1000, 1))));
    });

    $fiber2 = Fiber::run(function () {
        var_dump(Fiber::suspend(new Continuation(new Delayed(2000, 2))));
    });

    var_dump(Fiber::suspend(new Continuation(new Delayed(1000, 3))));
});

echo "outer\n";

var_dump(Fiber::suspend(new Continuation(new Delayed(1000, 4))));
