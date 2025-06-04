<?php

use SvenVanderwegen\Dockerizer\Commands\DockerizerBuildCommand;
use SvenVanderwegen\Dockerizer\Services\PostgresDockerService;
use SvenVanderwegen\Dockerizer\Support\ConfigurationService;
use SvenVanderwegen\Dockerizer\Support\ConfigHelper;

it('includes postgres service when database.type is postgresql', function () {
    config()->set('dockerizer.directory', 'temp');

    $service = new ConfigurationService();
    $service->saveConfiguration([
        'database' => ['type' => 'postgresql'],
        'services' => [
            'redis' => false,
            'workers' => false,
            'scheduler' => false,
        ],
        'registry' => [
            'type' => 'dockerhub',
            'repository' => 'demo',
            'url' => null,
        ],
    ]);

    $helper = new ConfigHelper($service);
    app()->instance(ConfigHelper::class, $helper);

    $command = new DockerizerBuildCommand();
    $ref = new ReflectionMethod($command, 'collectDockerServices');
    $ref->setAccessible(true);

    $services = $ref->invoke($command);

    expect($services)->toContain(PostgresDockerService::class);
});
