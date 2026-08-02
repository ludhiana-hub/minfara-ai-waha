<?php

namespace App\Services\Ai\Support;

final class Sleeper implements SleeperContract
{
    public function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
