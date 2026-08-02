<?php

namespace App\Services\Ai\Support;

interface SleeperContract
{
    public function sleep(int $milliseconds): void;
}
