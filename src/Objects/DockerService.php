<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Objects;

final readonly class DockerService
{
    public function __construct(
        public ?string $image = null,
        public ?array $build = null,
        public ?string $command = null,
        public ?string $container_name = null,
        public ?string $restart = null,
        public ?array $environment = null,
        public ?array $volumes = null,
        public ?array $depends_on = null,
        public ?array $networks = null,
        public ?array $ports = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'image' => $this->image,
            'build' => $this->build,
            'command' => $this->command,
            'environment' => $this->environment,
            'volumes' => $this->volumes,
            'depends_on' => $this->depends_on,
            'networks' => $this->networks,
            'ports' => $this->ports,
        ]);
    }
}
