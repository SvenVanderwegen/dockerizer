<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Actions;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use SvenVanderwegen\Dockerizer\Exceptions\FileAlreadyExistsException;

final class GenerateFileFromStubAction
{
    /**
     * Generate a file from a stub template.
     *
     * @throws FileAlreadyExistsException|FileNotFoundException
     */
    public function handle(
        string $path,
        string $stubPath,
        bool $force = false,
        ?Closure $contentProcessor = null
    ): void {

        if (! $force && File::exists($path)) {
            throw new FileAlreadyExistsException($path);
        }

        $template = File::get($stubPath);

        if ($contentProcessor !== null) {
            $template = $contentProcessor($template);
        }

        $directory = dirname($path);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, recursive: true);
        }

        File::put($path, $template);
    }
}
