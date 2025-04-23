<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SvenVanderwegen\Dockerizer\Enums\DatabaseOptions;
use SvenVanderwegen\Dockerizer\Enums\RegistryOptions;

final class DockerizerSetupCommand extends Command
{
    private const string DEFAULT_DB_USERNAME = 'laravel';

    private const string CONFIG_FILENAME = 'config.json';

    protected $signature = 'dockerizer:setup';

    protected $description = 'Setup Dockerizer configuration (choose container registry, repository)';

    public function handle(): int
    {
        $this->info('Dockerizer setup command executed.');

        $config = $this->collectConfiguration();

        if ($config === []) {
            return Command::FAILURE;
        }

        $this->saveConfiguration($config);
        $this->info('Configuration saved successfully!');

        return Command::SUCCESS;
    }

    /**
     * Collect configuration from user input.
     *
     * @return array<string, mixed>
     */
    private function collectConfiguration(): array
    {
        $registryConfig = $this->collectRegistryConfiguration();

        if ($registryConfig === []) {
            return [];
        }

        $databaseConfig = $this->collectDatabaseConfiguration();
        $servicesConfig = $this->collectServicesConfiguration();

        return [
            'registry' => $registryConfig,
            'database' => $databaseConfig,
            'services' => $servicesConfig,
        ];
    }

    /**
     * Collect registry configuration.
     *
     * @return array<string, mixed>
     */
    private function collectRegistryConfiguration(): array
    {
        $registry = $this->choice(
            'Which container registry do you want to use?',
            RegistryOptions::choices(),
            RegistryOptions::default()->value
        );

        if (is_array($registry)) {
            $this->error('Invalid registry option selected.');

            return [];
        }

        $repository = $this->ask('Enter your repository path (e.g., myusername/myapp)');
        $customRegistryUrl = null;

        if (RegistryOptions::isCustom($registry)) {
            $customRegistryUrl = $this->ask('Enter your full registry URL (e.g., registry.example.com)');
        }

        return [
            'type' => $registry,
            'repository' => $repository,
            'url' => $customRegistryUrl,
        ];
    }

    /**
     * Collect database configuration.
     *
     * @return array<string, mixed>
     */
    private function collectDatabaseConfiguration(): array
    {
        $database = $this->choice(
            'Which database do you want to use?',
            DatabaseOptions::choices(),
            DatabaseOptions::default()->value
        );

        return [
            'type' => $database,
            'username' => self::DEFAULT_DB_USERNAME,
            'password' => Str::password(),
        ];
    }

    /**
     * Collect services configuration.
     *
     * @return array<string, bool>
     */
    private function collectServicesConfiguration(): array
    {
        $redis = $this->confirm('Do you want to use Redis?', true);
        $worker = $this->confirm('Do you want to add a queue worker?', true);

        return [
            'redis' => $redis,
            'workers' => $worker,
        ];
    }

    /**
     * Get the configuration directory path.
     */
    private function getConfigPath(): string
    {
        return base_path(config()->string('dockerizer.directory', '.dockerizer'));
    }

    /**
     * Save the configuration to a JSON file.
     *
     * @param  array<string, mixed>  $config
     */
    private function saveConfiguration(array $config): void
    {
        $configPath = $this->getConfigPath();

        if (! File::exists($configPath)) {
            File::makeDirectory($configPath, 0755, true);
        }

        $content = json_encode($config, JSON_PRETTY_PRINT);

        if ($content === false) {
            $this->error('Failed to encode configuration to JSON.');

            return;
        }

        File::put(
            $configPath.'/'.self::CONFIG_FILENAME,
            $content
        );
    }
}
