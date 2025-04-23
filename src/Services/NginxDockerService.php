<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;

final class NginxDockerService implements DockerServiceModule
{
    public function getServiceName(): string
    {
        return 'nginx';
    }

    public function getServiceImage(): string
    {
        $url = dconfig()->string('registry.url');
        $repo = $this->getRepo();

        return $url !== '' && $url !== '0'
            ? $url.'/'.$repo.'-nginx:latest'
            : $repo.'-nginx:latest';
    }

    public function getService(): DockerService
    {
        $repo = $this->getRepo();

        return new DockerService(
            image: $this->getServiceImage(),
            restart: 'unless-stopped',
            networks: ['internal', 'proxy'],
            labels: [
                'traefik.enable=true',
                "traefik.http.routers.$repo.rule=Host(`app.example.com`)",
                "traefik.http.routers.$repo.entrypoints=websecure",
                "traefik.http.routers.$repo.tls.certresolver=le",
                "traefik.http.services.$repo.loadbalancer.server.port=80",
            ],
        );
    }

    private function getRepo(): string
    {
        $repo = dconfig()->string('registry.repository');

        if (str_contains($repo, '/')) {
            return explode('/', $repo)[1];
        }

        return $repo;
    }
}
