<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Exceptions;

use Exception;

final class FileAlreadyExistsException extends Exception
{
    public function __construct(string $filePath)
    {
        parent::__construct("The file at path {$filePath} already exists.");
    }
}
