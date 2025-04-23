<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Support;

use Exception;
use Illuminate\Support\Facades\File;
use SvenVanderwegen\Dockerizer\Exceptions\ConfigurationException;

final class ConfigurationService
{
    private const string CONFIG_FILENAME = 'config.json';

    /**
     * @var array<string, mixed>
     */
    private array $cachedConfig = [];

    private bool $configLoaded = false;

    /**
     * Get the configuration directory path.
     */
    public function getConfigPath(): string
    {
        return base_path(config()->string('dockerizer.directory', '.dockerizer'));
    }

    /**
     * Get the full path to the configuration file.
     */
    public function getConfigFilePath(): string
    {
        return $this->getConfigPath().'/'.self::CONFIG_FILENAME;
    }

    /**
     * Check if the configuration file exists.
     */
    public function configurationExists(): bool
    {
        return File::exists($this->getConfigFilePath());
    }

    /**
     * Load the configuration from the JSON file.
     *
     * @return array<string, mixed>
     *
     * @throws ConfigurationException If the configuration file doesn't exist or is invalid
     */
    public function loadConfiguration(): array
    {
        if (! $this->configLoaded) {
            if (! $this->configurationExists()) {
                throw new ConfigurationException('Configuration file does not exist. Run dockerizer:setup first.');
            }

            try {
                $this->cachedConfig = File::json($this->getConfigFilePath());
                $this->configLoaded = true;
            } catch (Exception $e) {
                throw new ConfigurationException('Invalid configuration file: '.$e->getMessage());
            }
        }

        return $this->cachedConfig;
    }

    /**
     * Save the configuration to a JSON file.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws ConfigurationException If saving the configuration fails
     */
    public function saveConfiguration(array $config): void
    {
        $configPath = $this->getConfigPath();

        if (! File::exists($configPath)) {
            File::makeDirectory($configPath, 0755, true);
        }

        $content = json_encode($config, JSON_PRETTY_PRINT);

        if ($content === false) {
            throw new ConfigurationException('Failed to encode configuration to JSON.');
        }

        File::put($this->getConfigFilePath(), $content);

        // Update cache
        $this->cachedConfig = $config;
        $this->configLoaded = true;
    }

    /**
     * Get a string value from the configuration.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  string|null  $default  Default value if key doesn't exist
     * @return string The configuration value
     *
     * @throws Exception If the configuration has not been loaded
     */
    public function string(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Get a boolean value from the configuration.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  bool|null  $default  Default value if key doesn't exist
     * @return bool The configuration value
     *
     * @throws Exception If the configuration has not been loaded
     */
    public function boolean(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            return false;
        }

        return (bool) $value;
    }

    /**
     * Get an integer value from the configuration.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  int|null  $default  Default value if key doesn't exist
     * @return int The configuration value
     *
     * @throws Exception If the configuration has not been loaded
     */
    public function integer(string $key, ?int $default = null): int
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            return 0;
        }

        return is_scalar($value) ? (int) $value : 0;
    }

    /**
     * Get an array value from the configuration.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  array<string, mixed>|null  $default  Default value if key doesn't exist
     * @return array<string, mixed> The configuration value
     *
     * @throws Exception If the configuration has not been loaded
     */
    public function array(string $key, ?array $default = null): array
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            // Indien de waarde geen array is, verpakken we het in een array met een generieke key
            return ['value' => $value];
        }

        return $value;
    }

    /**
     * Get a value from the configuration using dot notation.
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  mixed  $default  Default value if key doesn't exist
     * @return mixed The configuration value
     *
     * @throws Exception If the configuration has not been loaded
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureConfigLoaded();

        $segments = explode('.', $key);
        $config = $this->cachedConfig;

        foreach ($segments as $segment) {
            if (! is_array($config) || ! array_key_exists($segment, $config)) {
                return $default;
            }

            $config = $config[$segment];
        }

        return $config;
    }

    /**
     * Ensure the configuration is loaded.
     *
     * @throws Exception If the configuration file doesn't exist or is invalid
     */
    private function ensureConfigLoaded(): void
    {
        if (! $this->configLoaded) {
            $this->loadConfiguration();
        }
    }
}
