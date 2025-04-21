<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Contracts;

use SvenVanderwegen\Dockerizer\Services\DockerService;

interface DockerServiceModule
{
    public function getServiceName(): string;

    public function getServiceImage(): string;

    public function getService(): DockerService;
}
