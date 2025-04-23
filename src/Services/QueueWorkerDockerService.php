<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;

final readonly class QueueWorkerDockerService implements DockerServiceModule
{
    public function getServiceName(): string
    {
        return 'worker';
    }

    public function getServiceImage(): string
    {
        return (new AppDockerService)->getServiceImage();
    }

    public function getService(): DockerService
    {
        return new DockerService(
            image: $this->getServiceImage(),
            restart: 'unless-stopped',
            networks: [
                'internal',
            ],
        );
    }
}
