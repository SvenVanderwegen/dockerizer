<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;

final class MySQLDockerService implements DockerServiceModule
{
    public function getServiceName(): string
    {
        return 'db';
    }

    public function getServiceImage(): string
    {
        return 'mysql:8.0';
    }

    public function getService(): DockerService
    {
        return new DockerService(
            image: $this->getServiceImage(),
            restart: 'unless-stopped',
            environment: [
                'MYSQL_DATABASE' => 'laravel',
                'MYSQL_USER' => 'laravel',
                'MYSQL_PASSWORD' => 'secret',
                'MYSQL_ROOT_PASSWORD' => 'secret',
            ],
            volumes: [
                'db-data:/var/lib/mysql',
            ],
            networks: [
                'internal',
            ],
        );
    }
}
