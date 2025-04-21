<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Contracts;

use SvenVanderwegen\Dockerizer\Objects\DockerService;

interface DockerServiceModule
{
    public function getServiceName(): string;

    public function getService(): DockerService;
}
