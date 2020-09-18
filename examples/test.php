<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use Amp\Loop;
use Amp\Promise;

class Manager implements Scheduler
{
    public function run(): void
    {
        Loop::run();
    }
}

class Continuation implements Future
{
    private $promise;
    private static $scheduler;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;

        if (self::$scheduler === null) {
            self::$scheduler = new Manager;
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
    $promise1 = new Delayed(1000, 1);
    $promise2 = new Delayed(2000, 2);

    var_dump(Fiber::suspend(new Continuation($promise1)));
    var_dump(Fiber::suspend(new Continuation($promise2)));
//
//    $promise3 = new Delayed(1000, 3);
//
//    var_dump(Fiber::suspend(new Continuation($promise3)));
});