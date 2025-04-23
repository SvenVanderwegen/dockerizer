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
            working_dir: '/var/www/html',
            command: 'php artisan schedule:work',
            restart: 'unless-stopped',
            env_file: ['stack.env'],
            networks: ['internal'],
        );
    }
}
