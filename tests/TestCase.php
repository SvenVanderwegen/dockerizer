<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use SvenVanderwegen\Dockerizer\DockerizerServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir().'/dockerizer-test-'.uniqid();
        mkdir($this->basePath.'/bootstrap/cache', 0777, true);
        $_ENV['APP_BASE_PATH'] = $this->basePath;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->basePath)) {
            passthru('rm -rf '.escapeshellarg($this->basePath));
        }

        parent::tearDown();
    }
    protected function getPackageProviders($app)
    {
        return [DockerizerServiceProvider::class];
    }
}
