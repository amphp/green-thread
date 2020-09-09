<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Delayed;
use function Amp\await;

// Any function can call await(), not only closures.
function foobar(int $id): int
{
    $value = await(new Delayed(1000, $id)); // Wait 1 second, simulating async IO.

    if ($value & 1) {
        throw new Exception("Something went wrong! Value: " . $value);
    }

    return $value;
}

$result = foobar(2); // Call a subroutine, taking 1 second to return.
\var_dump($result);

try {
    $result = foobar(3); // Call subroutine again, which now throws an exception after 1 second.
    \var_dump($result);
} catch (Exception $exception) { // Exceptions thrown from async subroutines can be caught like any other.
    \var_dump("Caught exception: " . $exception->getMessage());
}

$result = foobar(5); // Make another async subroutine call that will now throw.
\var_dump($result);
