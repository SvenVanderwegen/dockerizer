<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;

final class RedisDockerService implements DockerServiceModule
{
    public function getServiceName(): string
    {
        return 'cache';
    }

    public function getServiceImage(): string
    {
        return 'redis:latest';
    }

    public function getService(): DockerService
    {
        return new DockerService(
            image: $this->getServiceImage(),
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
