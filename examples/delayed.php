<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use function Amp\GreenThread\await;
use function Amp\GreenThread\execute;

// Note that the closure declares void as a return type, not Promise or Generator.
execute(function (): void {
    $result = await(new Delayed(1000, 42));
    \var_dump($result);
});
