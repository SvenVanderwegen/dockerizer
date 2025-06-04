<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;

final class PostgresDockerService implements DockerServiceModule
{
    public function getServiceName(): string
    {
        return 'db';
    }

    public function getServiceImage(): string
    {
        return 'postgres:16';
    }

    public function getService(): DockerService
    {
        return new DockerService(
            image: $this->getServiceImage(),
            restart: 'unless-stopped',
            environment: [
                'POSTGRES_DB' => 'laravel',
                'POSTGRES_USER' => 'laravel',
                'POSTGRES_PASSWORD' => 'secret',
            ],
            volumes: [
                'db-data:/var/lib/postgresql/data',
            ],
            networks: [
                'internal',
            ],
        );
    }
}
