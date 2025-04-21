<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

use Illuminate\Support\Facades\File;
use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;

final class QueueWorkerDockerService implements DockerServiceModule
{
    public function getServiceName(): string
    {
        return 'worker';
    }

    public function getServiceImage(): string
    {
        $config = File::json(base_path(config()->string('dockerizer.directory', '.dockerizer').'/config.json'));

        return $config['registry']['repository'].'-app';
    }

    public function getService(): DockerService
    {
        return new DockerService(
            image: 'redis:latest',
            restart: 'unless-stopped',
            volumes: [
                'redis_data:/data',
            ],
            networks: [
                'internal',
            ],
        );
    }
}
