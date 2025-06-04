<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use SvenVanderwegen\Dockerizer\Enums\DatabaseOptions;
use SvenVanderwegen\Dockerizer\Enums\RegistryOptions;
use Symfony\Component\Console\Command\Command;

it('creates configuration file', function () {
    $basePath = $this->basePath;

    $this->artisan('dockerizer:setup')
        ->expectsChoice(
            'Which container registry do you want to use?',
            'dockerhub',
            RegistryOptions::choices()
        )
        ->expectsQuestion('Enter your repository path (e.g., myusername/myapp)', 'user/app')
        ->expectsChoice(
            'Which database do you want to use?',
            DatabaseOptions::SQLITE->value,
            DatabaseOptions::choices()
        )
        ->expectsConfirmation('Do you want to use Redis?', 'yes')
        ->expectsConfirmation('Do you want to add a queue worker?', 'yes')
        ->expectsConfirmation('Do you want to add a scheduler container?', 'no')
        ->assertExitCode(Command::SUCCESS);

    $configFile = $basePath.'/.dockerizer/config.json';
    expect(File::exists($configFile))->toBeTrue();

    $config = json_decode(File::get($configFile), true);

    expect($config['registry']['repository'])->toBe('user/app')
        ->and($config['services']['redis'])->toBeTrue();
});
