<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Command\Command;

it('generates docker files', function () {
    $basePath = $this->basePath;
    File::ensureDirectoryExists($basePath.'/.dockerizer');

    $config = [
        'registry' => [
            'type' => 'dockerhub',
            'repository' => 'user/app',
            'url' => null,
        ],
        'database' => [
            'type' => 'mysql',
            'username' => 'laravel',
            'password' => 'secret',
        ],
        'services' => [
            'redis' => true,
            'workers' => true,
            'scheduler' => false,
        ],
    ];

    File::put($basePath.'/.dockerizer/config.json', json_encode($config, JSON_PRETTY_PRINT));

    $this->artisan('dockerizer:build')->assertExitCode(Command::SUCCESS);

    expect(File::exists($basePath.'/.dockerizer/app/Dockerfile'))->toBeTrue()
        ->and(File::exists($basePath.'/.dockerizer/app/entrypoint.sh'))->toBeTrue()
        ->and(File::exists($basePath.'/.dockerizer/nginx/Dockerfile'))->toBeTrue()
        ->and(File::exists($basePath.'/.dockerizer/nginx/default.conf'))->toBeTrue()
        ->and(File::exists($basePath.'/docker-compose.yml'))->toBeTrue();
});
