<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Enums;

use SvenVanderwegen\Dockerizer\Services\MySQLDockerService;

enum DatabaseOptions: string
{
    case MYSQL = 'mysql';
    case POSTGRESQL = 'postgresql';
    case SQLITE = 'sqlite';

    public static function default(): self
    {
        return self::SQLITE;
    }

    /**
     * Get the list of options for the registry.
     *
     * @return array<string, string>
     */
    public static function choices(): array
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
            self::MYSQL => 'MySQL',
            self::POSTGRESQL => 'PostgreSQL',
            self::SQLITE => 'SQLite',
        };
    }

    public function getDockerService(): ?string
    {
        return match ($this) {
            self::MYSQL => MySQLDockerService::class,
            self::POSTGRESQL => null, // TODO: implement PostgreSQL docker service
            self::SQLITE => null,
        };
    }
}
