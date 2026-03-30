<?php

namespace Langsys\ApiKit\Tests;

use Langsys\ApiKit\ApiKitServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ApiKitServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('api-kit.resource_driver', 'config');
    }
}
