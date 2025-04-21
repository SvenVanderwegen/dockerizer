<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Actions;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use SvenVanderwegen\Dockerizer\Exceptions\FileAlreadyExistsException;

final class GenerateFileFromStubAction
{
    private const STUB_PATH_BASE = __DIR__.'/../../stubs/';

    /**
     * Generate a file from a stub template.
     *
     * @throws FileAlreadyExistsException|FileNotFoundException
     */
    public function handle(
        string $path,
        string $stubName,
        bool $force = false,
        ?Closure $contentProcessor = null
    ): void {

        if (! $force && File::exists($path)) {
            throw new FileAlreadyExistsException($path);
        }

        $stubPath = self::STUB_PATH_BASE.$stubName;
        $template = File::get($stubPath);

        if ($contentProcessor !== null) {
            $template = $contentProcessor($template);
        }

        File::put($path, $template);
    }
}
