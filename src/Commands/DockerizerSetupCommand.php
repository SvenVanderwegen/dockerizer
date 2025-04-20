<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class DockerizerSetupCommand extends Command
{
    protected $signature = 'dockerizer:setup';

    protected $description = 'Setup Dockerizer configuration (choose container registry, repository)';

    public function handle(): int
    {
        $this->info('Dockerizer setup command executed.');

        $registry = $this->choice(
            'Which container registry do you want to use?',
            [
                'dockerhub' => 'DockerHub',
                'ghcr' => 'GitHub Container Registry',
                'gitlab' => 'GitLab Container Registry',
                'custom' => 'Custom/private registry',
            ],
            'dockerhub'
        );

        $repository = $this->ask('Enter your repository path (e.g., myusername/myapp)');

        $customRegistryUrl = null;
        if ($registry === 'custom') {
            $customRegistryUrl = $this->ask('Enter your full registry URL (e.g., registry.example.com)');
        }

        $config = [
            'registry' => $registry,
            'repository' => $repository,
        ];

        if ($customRegistryUrl) {
            $config['custom_url'] = $customRegistryUrl;
        }

        if (! File::exists(base_path('.dockerizer'))) {
            File::makeDirectory(base_path('.dockerizer'));
        }

        File::put(base_path('.dockerizer/config.json'), json_encode($config, JSON_PRETTY_PRINT));

        $this->info('✅ Dockerizer configuration saved successfully!');
        $this->line('  - Registry: '.$registry);
        $this->line('  - Repository: '.$repository);

        if ($customRegistryUrl) {
            $this->line('  - Custom URL: '.$customRegistryUrl);
        }

        $this->newLine();
        $this->info('Next steps:');
        $this->line('👉 Use this config for your CI/CD pipelines to push Docker images.');
        $this->line('👉 Your production docker-compose can now pull images cleanly.');

        return Command::SUCCESS;
    }
}
