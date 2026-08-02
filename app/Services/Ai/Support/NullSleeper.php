<?php

namespace App\Services\Ai\Support;

/** Bound in the testing environment so retry-delay logic doesn't slow down the test suite. */
final class NullSleeper implements SleeperContract
{
    public function sleep(int $milliseconds): void
    {
        // no-op
    }
}
