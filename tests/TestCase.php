<?php

namespace Bvtterfly\Replay\Tests;

use Bvtterfly\Replay\ReplayServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ReplayServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
    }
}
