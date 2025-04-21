<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Actions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use SvenVanderwegen\Dockerizer\Exceptions\FileAlreadyExistsException;

final class GenerateAppDockerfileAction
{
    /**
     * Generate a Dockerfile for the application.
     *
     * @param  array<string>  $extensions
     *
     * @throws FileAlreadyExistsException|FileNotFoundException
     */
    public function handle(string $path, bool $force = false, array $extensions = []): void
    {
        if (! $force && File::exists($path)) {
            throw new FileAlreadyExistsException($path);
        }

        $template = File::get(__DIR__.'/../../stubs/app.dockerfile.stub');

        $commands = [];

        foreach ($extensions as $extension) {
            switch ($extension) {
                case 'pdo_mysql':
                    $commands[] = 'RUN docker-php-ext-install pdo_mysql';
                    break;
                case 'gd':
                    $commands[] = 'RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp';
                    $commands[] = 'RUN docker-php-ext-install -j$(nproc) gd';
                    break;
                default:
                    $commands[] = "RUN docker-php-ext-install $extension";
            }
        }

        $template = str_replace('# [DOCKERIZER_PLACEHOLDER_EXTENSIONS]', mb_trim(implode("\n", $commands)), $template);

        File::put($path, $template);
    }
}
