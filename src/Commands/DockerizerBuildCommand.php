<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dockerizer:build {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the docker configuration for your application.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🐳 Dockerizer: Generating Docker configuration files...');

        // Create directory structure
        $this->createDirectories(directories: [
            base_path(config()->string('dockerizer.directory', '.dockerizer')),
            base_path('.github/workflows'),
        ]);

        foreach (GeneratedStubFiles::cases() as $file) {
            try {
                (new GenerateFileFromStubAction)->handle(
                    path: $this->getPath($file->getDestinationPath()),
                    stubPath: $file->getStubFilePath(),
                    force: $this->isForced(),
                    contentProcessor: $file->getContentProcessor());
            } catch (FileNotFoundException|FileAlreadyExistsException $e) {
                $this->error('Failed to generate Dockerfile: '.$e->getMessage());
            }
        }

        $this->generateDockerComposeFile();

        $this->info('✅ Docker configuration successfully generated!');

        return Command::SUCCESS;
    }

    private function isForced(): bool
    {
        return (bool) $this->option('force');
    }

    private function getPath(string $path): string
    {
        return base_path(config()->string('dockerizer.directory', '.dockerizer')."/$path");
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

    /**
     * Generate docker-compose.yml file.
     */
    private function generateDockerComposeFile(): void
    {
        $filePath = base_path('docker-compose.yml');
        $config = File::json(base_path(config()->string('dockerizer.directory', '.dockerizer').'/config.json'));

        $services = [];

        $services[] = DatabaseOptions::from($config['database']['type'])->getDockerService();

        if ($config['services']['redis']) {
            $services[] = RedisDockerService::class;
        }

        if ($config['services']['workers']) {
            $services[] = QueueWorkerDockerService::class;
        }

        $compose = [];

        foreach ($services as $service) {
            /* @var DockerServiceModule $serviceInstance */
            $serviceInstance = new $service();
            $compose['services'][$serviceInstance->getServiceName()] = $serviceInstance->getService()->toArray();
        }

        // Iterate through the services and compile networks and volumes
        $networks = [];
        $volumes = [];

        foreach ($compose['services'] as $service) {
            if (isset($service['networks'])) {
                foreach ($service['networks'] as $network) {
                    $networks[$network] = [];
                }
            }

            if (isset($service['volumes'])) {
                foreach ($service['volumes'] as $volume) {
                    // Split the volume name if it contains a colon
                    if (str_contains((string) $volume, ':')) {
                        $volume = explode(':', (string) $volume)[0];
                    }

                    $volumes[$volume] = [];
                }
            }
        }

        // Add networks and volumes to the compose file
        $compose['networks'] = $networks;
        $compose['volumes'] = $volumes;

        // Use Symfony YAML to generate clean output
        $yamlContent = Yaml::dump($compose, 6, 2);

        File::put($filePath, $yamlContent);
        $this->line('Generated: <info>docker-compose.yml</info>');
    }
}
