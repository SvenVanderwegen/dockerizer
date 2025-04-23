<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Support;

use Exception;

final readonly class ConfigHelper
{
    public function __construct(
        private ConfigurationService $configService
    ) {
        try {
            if ($this->configService->configurationExists()) {
                $this->configService->loadConfiguration();
            }
        } catch (Exception) {
        }
    }

    /**
     * Get a string value from the configuration.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  string|null  $default  Default value if the key doesn't exist
     * @return string The configuration value
     */
    public function string(string $key, ?string $default = null): string
    {
        try {
            return $this->configService->string($key, $default);
        } catch (Exception) {
            return $default ?? '';
        }
    }

    /**
     * Get a boolean value from the configuration.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  bool|null  $default  Default value if the key doesn't exist
     * @return bool The configuration value
     */
    public function boolean(string $key, ?bool $default = null): bool
    {
        try {
            return $this->configService->boolean($key, $default);
        } catch (Exception) {
            return $default ?? false;
        }
    }

    /**
     * Get an integer value from the configuration.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  int|null  $default  Default value if the key doesn't exist
     * @return int The configuration value
     */
    public function integer(string $key, ?int $default = null): int
    {
        try {
            return $this->configService->integer($key, $default);
        } catch (Exception) {
            return $default ?? 0;
        }
    }

    /**
     * Get an array value from the configuration.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  array<string, mixed>|null  $default  Default value if the key doesn't exist
     * @return array<string, mixed> The configuration value
     */
    public function array(string $key, ?array $default = null): array
    {
        try {
            return $this->configService->array($key, $default);
        } catch (Exception) {
            return $default ?? [];
        }
    }

    /**
     * Get a mixed value from the configuration.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  mixed  $default  Default value if key doesn't exist
     * @return mixed The configuration value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            return $this->configService->get($key, $default);
        } catch (Exception) {
            return $default;
        }
    }
}
