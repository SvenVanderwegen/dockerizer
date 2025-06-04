<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use SvenVanderwegen\Dockerizer\DockerizerServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [DockerizerServiceProvider::class];
    }
}
