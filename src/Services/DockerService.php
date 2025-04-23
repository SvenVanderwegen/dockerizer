<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Services;

final readonly class DockerService
{
    /**
     * @param  array<string>|null  $build
     * @param  array<string>|null  $environment
     * @param  array<string>|null  $volumes
     * @param  array<string>|null  $depends_on
     * @param  array<string>|null  $networks
     * @param  array<string>|null  $ports
     */
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

    /**
     * Convert the Docker service to an array.
     *
     * @return array<string, array<string>|string|null>
     */
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
