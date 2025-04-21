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
    protected $signature = 'dockerizer:setup';

    protected $description = 'Setup Dockerizer configuration (choose container registry, repository)';

    public function handle(): int
    {
        $this->info('Dockerizer setup command executed.');

        $config = $this->collectConfiguration();
        $this->saveConfiguration($config);

        return Command::SUCCESS;
    }

    private function collectConfiguration(): array
    {
        $registry = $this->choice(
            'Which container registry do you want to use?',
            RegistryOptions::choises(),
            RegistryOptions::default()->value
        );

        $repository = $this->ask('Enter your repository path (e.g., myusername/myapp)');

        if (RegistryOptions::isCustom($registry)) {
            $customRegistryUrl = $this->ask('Enter your full registry URL (e.g., registry.example.com)');
        }

        $database = $this->choice(
            'Which database do you want to use?',
            DatabaseOptions::choises(),
            DatabaseOptions::default()->value
        );

        $redis = $this->confirm(
            'Do you want to use Redis?',
            true
        );

        $worker = $this->confirm(
            'Do you want to add a queue worker?',
            true
        );

        return [
            'registry' => [
                'type' => $registry,
                'repository' => $repository,
                'url' => $customRegistryUrl ?? null,
            ],
            'database' => [
                'type' => $database,
                'username' => 'laravel',
                'password' => Str::password(),
            ],
            'services' => [
                'redis' => $redis,
                'workers' => $worker,
            ],
        ];
    }

    private function saveConfiguration(array $config): void
    {
        $configPath = base_path(config()->string('dockerizer.directory', '.dockerizer'));

        if (! File::exists($configPath)) {
            File::makeDirectory($configPath);
        }

        File::put(
            $configPath.'/config.json',
            json_encode($config, JSON_PRETTY_PRINT)
        );
    }
}
