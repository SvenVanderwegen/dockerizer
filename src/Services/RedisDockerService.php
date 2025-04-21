<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;
use SvenVanderwegen\Dockerizer\Objects\DockerService;

final class RedisDockerService implements DockerServiceModule
{
    public function getServiceName(): string
    {
        return 'cache';
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
