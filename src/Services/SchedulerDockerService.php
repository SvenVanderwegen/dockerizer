<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;

final readonly class SchedulerDockerService implements DockerServiceModule
{
    public function getServiceName(): string
    {
        return 'scheduler';
    }

    public function getServiceImage(): string
    {
        return (new AppDockerService)->getServiceImage();
    }

    public function getService(): DockerService
    {
        return new DockerService(
            image: $this->getServiceImage(),
            command: 'php artisan schedule:work',
            restart: 'unless-stopped',
            networks: ['internal'],
            working_dir: '/var/www/html',
            env_file: ['stack.env'],
        );
    }
}
