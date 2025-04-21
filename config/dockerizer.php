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
    'php_extensions' => ['pdo', 'pdo_mysql', 'mbstring', 'exif', 'pcntl', 'bcmath', 'gd'],

    /*
     | --------------------------------------------------------------------------
     | Registry
     | --------------------------------------------------------------------------
     |
     | The container registry to use for the Docker images.
     | You can change this to any registry you want.
     |
     | The default value is 'docker.io'.
     */

    'registry' => [
        'type' => env('DOCKER_REGISTRY_TYPE', 'dockerhub'),
        'url' => env('DOCKER_REGISTRY_URL', 'docker.io'),
        'username' => env('DOCKER_REGISTRY_USERNAME'),
        'password' => env('DOCKER_REGISTRY_PASSWORD'),
    ],

    /*
     | --------------------------------------------------------------------------
     | Database
     | --------------------------------------------------------------------------
     |
     | The database to use for the Docker images.
     | You can change this to any database you want.
     |
     | The default value is 'mysql'.
     */

    'database' => [
        'type' => env('DOCKER_DATABASE_TYPE', 'mysql'),
        'username' => env('DOCKER_DATABASE_USERNAME', 'laravel'),
        'password' => env('DOCKER_DATABASE_PASSWORD', 'secret'),
        'host' => env('DOCKER_DATABASE_HOST', 'db'),
        'port' => env('DOCKER_DATABASE_PORT', 3306),
        'database' => env('DOCKER_DATABASE_NAME', 'laravel'),
    ],

    /*
     | --------------------------------------------------------------------------
     | Service
     | --------------------------------------------------------------------------
     |
     | Extra services to enable for the Docker images.
     |
     */

    'services' => [
        SvenVanderwegen\Dockerizer\Services\RedisDockerService::class => true,
        SvenVanderwegen\Dockerizer\Services\QueueWorkerDockerService::class => true,
    ],

];
