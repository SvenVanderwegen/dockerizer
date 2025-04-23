<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use SvenVanderwegen\Dockerizer\Actions\GenerateFileFromStubAction;
use SvenVanderwegen\Dockerizer\Contracts\DockerServiceModule;
use SvenVanderwegen\Dockerizer\Enums\DatabaseOptions;
use SvenVanderwegen\Dockerizer\Enums\GeneratedStubFiles;
use SvenVanderwegen\Dockerizer\Exceptions\FileAlreadyExistsException;
use SvenVanderwegen\Dockerizer\Services\QueueWorkerDockerService;
use SvenVanderwegen\Dockerizer\Services\RedisDockerService;
use Symfony\Component\Yaml\Yaml;

final class DockerizerBuildCommand extends Command
{
    private const string DEFAULT_DOCKERIZER_DIR = '.dockerizer';

    private const string DOCKER_COMPOSE_FILENAME = 'docker-compose.yml';

    private const string CONFIG_FILENAME = 'config.json';

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dockerizer:build {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Build the docker configuration for your application.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🐳 Dockerizer: Generating Docker configuration files...');

        $this->createDirectories([
            $this->getDockerizeDirectory(),
            base_path('.github/workflows'),
        ]);

        $this->generateStubFiles();
        $this->generateDockerComposeFile();

        $this->info('✅ Docker configuration successfully generated!');

        return Command::SUCCESS;
    }

    /**
     * Generate files from stubs.
     */
    private function generateStubFiles(): void
    {
        $generator = new GenerateFileFromStubAction();

        foreach (GeneratedStubFiles::cases() as $file) {
            try {
                $generator->handle(
                    path: $this->getPath($file->getDestinationPath()),
                    stubPath: $file->getStubFilePath(),
                    force: $this->isForced(),
                    contentProcessor: $file->getContentProcessor()
                );
            } catch (FileNotFoundException|FileAlreadyExistsException $e) {
                $this->error('Failed to generate file: '.$e->getMessage());
            }
        }
    }

    /**
     * Check if the force option is enabled.
     */
    private function isForced(): bool
    {
        return (bool) $this->option('force');
    }

    /**
     * Get the full path for a file in the dockerizer directory.
     */
    private function getPath(string $path): string
    {
        return $this->getDockerizeDirectory()."/$path";
    }

    /**
     * Get the dockerizer directory path.
     */
    private function getDockerizeDirectory(): string
    {
        return base_path(config()->string('dockerizer.directory', self::DEFAULT_DOCKERIZER_DIR));
    }

    /**
     * Create the necessary directories if they don't exist.
     *
     * @param  array<string>  $directories
     */
    private function createDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line("Created directory: <info>{$directory}</info>");
            }
        }
    }

    private function generateDockerComposeFile(): void
    {
        $filePath = base_path(self::DOCKER_COMPOSE_FILENAME);
        $configPath = $this->getDockerizeDirectory().'/'.self::CONFIG_FILENAME;

        try {
            $config = $this->loadAndValidateConfig($configPath);
            $services = $this->collectDockerServices($config);
            $compose = $this->buildComposeConfiguration($services);

            // Use Symfony YAML to generate clean output
            $yamlContent = Yaml::dump($compose, 6, 2);
            File::put($filePath, $yamlContent);

            $this->line('Generated: <info>'.self::DOCKER_COMPOSE_FILENAME.'</info>');
        } catch (FileNotFoundException) {
            $this->error("Config file not found: {$configPath}");
        } catch (InvalidArgumentException $e) {
            $this->error("Error generating docker-compose.yml: {$e->getMessage()}");
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws FileNotFoundException|InvalidArgumentException
     */
    private function loadAndValidateConfig(string $configPath): array
    {
        $config = File::json($configPath);

        if (! is_array($config)) {
            throw new InvalidArgumentException('Invalid configuration: Configuration must be an array');
        }

        if (! isset($config['database']) || ! is_array($config['database']) || ! isset($config['database']['type'])) {
            throw new InvalidArgumentException("Invalid configuration: 'database.type' is missing");
        }

        if (! isset($config['services']) || ! is_array($config['services'])) {
            throw new InvalidArgumentException("Invalid configuration: 'services' section is missing");
        }

        if (! isset($config['services']['redis'])) {
            throw new InvalidArgumentException("Invalid configuration: 'services.redis' is missing");
        }

        if (! isset($config['services']['workers'])) {
            throw new InvalidArgumentException("Invalid configuration: 'services.workers' is missing");
        }

        return $config;
    }

    /**
     * Collect Docker service classes based on configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<class-string<DockerServiceModule>>
     */
    private function collectDockerServices(array $config): array
    {
        $services = [];

        $databaseConfig = $config['database'] ?? [];
        $databaseType = '';

        if (is_array($databaseConfig) && isset($databaseConfig['type']) && is_string($databaseConfig['type'])) {
            $databaseType = $databaseConfig['type'];
        }

        if ($databaseType !== '') {
            $database = DatabaseOptions::from($databaseType)->getDockerService();

            if (
                is_string($database) &&
                class_exists($database) &&
                is_subclass_of($database, DockerServiceModule::class)
            ) {
                $services[] = $database;
            }
        }

        $servicesConfig = $config['services'] ?? [];

        if (
            is_array($servicesConfig) &&
            isset($servicesConfig['redis']) &&
            $servicesConfig['redis'] === true
        ) {
            $services[] = RedisDockerService::class;
        }

        if (
            is_array($servicesConfig) &&
            isset($servicesConfig['workers']) &&
            $servicesConfig['workers'] === true
        ) {
            $services[] = QueueWorkerDockerService::class;
        }

        /** @var array<class-string<DockerServiceModule>> $services */
        return $services;
    }

    /**
     * @param  array<class-string<DockerServiceModule>>  $serviceClasses
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    private function buildComposeConfiguration(array $serviceClasses): array
    {
        $compose = ['services' => []];

        foreach ($serviceClasses as $serviceClass) {
            if (! is_subclass_of($serviceClass, DockerServiceModule::class)) {
                throw new InvalidArgumentException(
                    sprintf('Service %s must implement %s', $serviceClass, DockerServiceModule::class)
                );
            }

            $serviceInstance = new $serviceClass();
            $compose['services'][$serviceInstance->getServiceName()] = $serviceInstance->getService()->toArray();
        }

        [$networks, $volumes] = $this->extractNetworksAndVolumes($compose['services']);

        $compose['networks'] = $networks;
        $compose['volumes'] = $volumes;

        return $compose;
    }

    /**
     * Extract networks and volumes from services.
     *
     * @param  array<string, mixed>  $services
     * @return array{0: array<string, array<mixed>>, 1: array<string, array<mixed>>}
     */
    private function extractNetworksAndVolumes(array $services): array
    {
        $networks = [];
        $volumes = [];

        foreach ($services as $service) {
            if (! is_array($service)) {
                continue;
            }

            if (isset($service['networks']) && is_array($service['networks'])) {
                foreach ($service['networks'] as $network) {
                    if (is_string($network)) {
                        $networks[$network] = [];
                    }
                }
            }

            if (isset($service['volumes']) && is_array($service['volumes'])) {
                foreach ($service['volumes'] as $volume) {
                    if (is_string($volume) && str_contains($volume, ':')) {
                        $volume = explode(':', $volume)[0];
                    }

                    if (is_string($volume)) {
                        $volumes[$volume] = [];
                    }
                }
            }
        }

        return [$networks, $volumes];
    }
}
