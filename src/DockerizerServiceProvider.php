<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * @internal
 */
final class DockerizerServiceProvider extends BaseServiceProvider
{
    /**
     * The list of commands.
     *
     * @var list<class-string<Command>>
     */
    private array $commands = [
        Commands\DockerizerSetupCommand::class,
        Commands\DockerizerBuildCommand::class,
    ];

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }


    }
}
