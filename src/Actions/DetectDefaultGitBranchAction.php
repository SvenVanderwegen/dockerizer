<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Actions;

use Illuminate\Support\Facades\File;

final class DetectDefaultGitBranchAction
{
    /**
     * Detect the default Git branch (main or master).
     */
    public function __invoke(): string
    {
        if (File::exists(base_path('.git/refs/heads/main'))) {
            return 'main';
        }
        if (File::exists(base_path('.git/refs/heads/master'))) {
            return 'master';
        }

        return 'main';
    }
}
