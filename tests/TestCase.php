<?php

namespace SmartDato\MondialRelay\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SmartDato\MondialRelay\MondialRelayServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            MondialRelayServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('mondial-relay-sdk.v1.enseigne', 'TESTTEST');
        config()->set('mondial-relay-sdk.v1.private_key', 'PrivateK');
    }
}
