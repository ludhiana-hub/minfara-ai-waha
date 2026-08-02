<?php

namespace Tests;

use App\Models\BotConfig;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // BotConfig::$requestMemo is a static array meant to live for one HTTP request or
        // one queue job (cleared via JobProcessing/JobProcessed listeners in
        // AppServiceProvider). PHPUnit runs every test method in the same long-lived PHP
        // process, so without this it would leak one test's memoized config values into
        // every test that runs after it — RefreshDatabase resets the DB but not this.
        BotConfig::clearRequestMemo();
    }
}
