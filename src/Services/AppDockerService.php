<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;

final class AppDockerService implements DockerServiceModule
{
    public function getServiceName(): string
    {
        return 'app';
    }

    public function getServiceImage(): string
    {
        $url = dconfig()->string('registry.url');
        $repository = dconfig()->string('registry.repository');

        if ($url !== '' && $url !== '0') {
            return $url.'/'.$repository.':latest';
        }

        return $repository.':latest';
    }

    public function getService(): DockerService
    {
        return new DockerService(
            image: $this->getServiceImage(),
            working_dir: '/var/www/html',
            env_file: [
                'stack.env',
            ],
            restart: 'unless-stopped',
            networks: [
                'internal',
            ],
        );
    }
}
