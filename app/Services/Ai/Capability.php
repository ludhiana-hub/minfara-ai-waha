<?php

namespace App\Services\Ai;

enum Capability
{
    case JsonMode;
    case Tools;
    case Streaming;
}
