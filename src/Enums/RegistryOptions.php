<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Enums;

enum RegistryOptions: string
{
    case DOCKERHUB = 'dockerhub';
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
    case CUSTOM = 'custom';

    public static function default(): self
    {
        return self::DOCKERHUB;
    }

    /**
     * Get the list of options for the registry.
     *
     * @return array<string, string>
     */
    public static function choises(): array
    {
        $cases = self::cases();

        $options = [];

        foreach ($cases as $case) {
            $options[$case->value] = $case->getDisplayName();
        }

        return $options;
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::DOCKERHUB => 'DockerHub',
            self::GITHUB => 'GitHub Container Registry',
            self::GITLAB => 'GitLab Container Registry',
            self::CUSTOM => 'Custom/private registry',
        };
    }

    public function isCustom(): bool
    {
        return $this === self::CUSTOM;
    }
}
