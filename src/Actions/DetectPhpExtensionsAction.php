<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Actions;

use Illuminate\Support\Facades\File;

final class DetectPhpExtensionsAction
{
    /**
     * Detect PHP extensions required from composer.json.
     */
    public function __invoke(): array
    {
        $composerJsonPath = base_path('composer.json');
        if (! File::exists($composerJsonPath)) {
            return [];
        }

        $composerJson = json_decode(File::get($composerJsonPath), true);
        $extensions = [];

        if (isset($composerJson['require'])) {
            foreach ($composerJson['require'] as $requirement => $version) {
                if (str_starts_with($requirement, 'ext-')) {
                    $extensions[] = str_replace('ext-', '', $requirement);
                }
            }
        }

        return $extensions;
    }
}
