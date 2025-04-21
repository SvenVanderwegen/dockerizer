<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Actions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;

final class DetectPhpExtensionsAction
{
    /**
     * Detect PHP extensions required from composer.json.
     * @throws FileNotFoundException
     *
     * @return array<string>
     */
    public function __invoke(): array
    {
        $composerJsonPath = base_path('composer.json');
        if (! File::exists($composerJsonPath)) {
            return [];
        }

        $composerJson = File::json($composerJsonPath);
        $extensions = [
            'ctype',
            'curl',
            'dom',
            'fileinfo',
            'filter',
            'hash',
            'mbstring',
            'openssl',
            'pcre',
            'pdo',
            'session',
            'tokenizer',
            'xml'
        ];

        if (isset($composerJson['require'])) {
            foreach ($composerJson['require'] as $requirement => $version) {
                if (str_starts_with($requirement, 'ext-')) {
                    $extensions[] = str_replace('ext-', '', $requirement);
                }
            }
        }

        return array_filter($extensions);
    }
}
