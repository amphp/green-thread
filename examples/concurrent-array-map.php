<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use function Amp\async;
use function Amp\await;
use function Amp\delay;

// array_map() takes 2 seconds to execute as the calls are not concurrent, but this shows that fibers are
// supported by internal callbacks.
$array = [5, 6, 7, 8, 9];

$result = await([
    async(fn() => \array_map(fn ($i) => await(new Delayed(1, $i)), $array)),
    async(fn() => \array_map(fn ($i) => await(new Delayed(2, $i)), $array)),
]);

var_dump($result);