<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Commands;

use Illuminate\Console\Command;

final class DockerizerBuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dockerizer:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the docker configuration for your application.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
