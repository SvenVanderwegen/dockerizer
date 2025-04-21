<?php

declare(strict_types=1);

return [

    /*
     | --------------------------------------------------------------------------
     | Dockerizer directory
     | --------------------------------------------------------------------------
     |
     | The directory where Dockerizer will store its files.
     | You can change this to any directory you want.
     |
     | The default value is '.dockerizer'.
     */

    'directory' => '.dockerizer',

    /*
     | --------------------------------------------------------------------------
     | PHP Configuration
     | --------------------------------------------------------------------------
     |
     | The PHP version to use for the Docker images.
     | You can change this to any version you want.
     |
     */

    'php_version' => env('DOCKER_PHP_VERSION', '8.4'),

    /*
     | --------------------------------------------------------------------------
     | CI/CD Configuration
     | --------------------------------------------------------------------------
     |
     |
     */

    'branch' => env('DOCKER_BRANCH', 'main'),

    /*
     | --------------------------------------------------------------------------
     | Dockerfile configuration
     | --------------------------------------------------------------------------
     |
     |
     */

    'app' => [
        'dockerfile' => 'app/Dockerfile',
        'stub' => 'app.dockerfile.stub',
        'entrypoint' => 'app/entrypoint.sh',
    ],

    'nginx' => [
        'dockerfile' => 'nginx/Dockerfile',
        'stub' => 'nginx.dockerfile.stub',
    ],
];
