<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Commands;

use Illuminate\Console\Command;

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
            0
        );

        return Command::SUCCESS;
    }

}
