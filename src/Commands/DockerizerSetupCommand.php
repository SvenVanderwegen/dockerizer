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
        // Check if the configuration file already exists (dockerizer.php), if not publish it.
        if (!File::exists(config_path('dockerizer.php'))) {
            $this->callSilent('vendor:publish', [
                '--tag' => 'dockerizer-config',
                '--force' => true,
            ]);
        }

        $config = $this->collectConfiguration();
        $this->saveConfiguration($config);
        $this->displaySummary($config);
        $this->displayNextSteps();

        return Command::SUCCESS;
    }

    private function collectConfiguration(): array
    {
        $registry = RegistryOptions::from($this->choice(
            'Which container registry do you want to use?',
            RegistryOptions::choises(),
            RegistryOptions::default()->value
        ));

        $repository = $this->ask('Enter your repository path (e.g., myusername/myapp)');

        if ($registry->isCustom()) {
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

        $queues = $this->confirm(
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
            'redis' => [
                'enabled' => $redis,
                'password' => Str::password(),
            ],
            'queues' => [
                'enabled' => $queues,
                'connection' => 'redis',
                'queue' => 'default',
            ],
        ];
    }

    private function saveConfiguration(array $config): void
    {
        $configPath = config_path('dockerizer.php');

        // Load the existing configuration file
        $existingConfig = File::get($configPath);

        // Replace the placeholder with the new configuration
        $newConfig = preg_replace(
            '/return\s+\[.*?];/s',
            'return '.var_export($config, true).';',
            $existingConfig
        );

        // Save the updated configuration back to the file
        File::put($configPath, $newConfig);
    }

    private function displayNextSteps(): void
    {
        $this->info('Next steps:');
        $this->line('👉 Use this config for your CI/CD pipelines to push Docker images.');
        $this->line('👉 Your production docker-compose can now pull images cleanly.');
    }
}
