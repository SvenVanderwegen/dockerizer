<?php

declare(strict_types=1);

namespace SvenVanderwegen\Dockerizer\Enums;

use Closure;
use SvenVanderwegen\Dockerizer\Actions\DetectPhpExtensionsAction;

enum GeneratedStubFiles: string
{
    case APP_DOCKERFILE = 'app.dockerfile';
    case APP_ENTRYPOINT = 'app.entrypoint';
    case NGINX_DOCKERFILE = 'nginx.dockerfile';
    case NGINX_CONFIG = 'nginx.default';

    public function getStubFilePath(): string
    {
        return match ($this) {
            self::APP_DOCKERFILE => $this->getStubFolder().'app.dockerfile.stub',
            self::APP_ENTRYPOINT => $this->getStubFolder().'app.entrypoint.stub',
            self::NGINX_DOCKERFILE => $this->getStubFolder().'nginx.dockerfile.stub',
            self::NGINX_CONFIG => $this->getStubFolder().'nginx.default.stub',
        };
    }

    public function getDestinationPath(): string
    {
        return match ($this) {
            self::APP_DOCKERFILE => config()->string('dockerizer.app.dockerfile', 'app/Dockerfile'),
            self::APP_ENTRYPOINT => config()->string('dockerizer.app.entrypoint', 'app/entrypoint.sh'),
            self::NGINX_DOCKERFILE => config()->string('dockerizer.nginx.dockerfile', 'nginx/Dockerfile'),
            self::NGINX_CONFIG => config()->string('dockerizer.nginx.config', 'nginx/default.conf'),
        };
    }

    public function getContentProcessor(): ?Closure
    {
        return match ($this) {
            self::APP_DOCKERFILE => function (string $content): string|array {
                $commands = [];
                $extensions = (new DetectPhpExtensionsAction())();

                foreach ($extensions as $extension) {
                    switch ($extension) {
                        case 'pdo_mysql':
                            $commands[] = 'RUN docker-php-ext-install pdo_mysql';
                            break;
                        case 'gd':
                            $commands[] = 'RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp';
                            $commands[] = 'RUN docker-php-ext-install -j$(nproc) gd';
                            break;
                        default:
                            $commands[] = "RUN docker-php-ext-install $extension";
                    }
                }

                $phpVersion = config()->string('dockerizer.php.version', '8.4');
                $content = str_replace('php:8.4-fpm-alpine', "php:$phpVersion-fpm-alpine", $content);

                return str_replace('# [DOCKERIZER_PLACEHOLDER_EXTENSIONS]', mb_trim(implode("\n", $commands)), $content);
            },
            self::APP_ENTRYPOINT => null,
            self::NGINX_DOCKERFILE => null,
            self::NGINX_CONFIG => null,
        };
    }

    private function getStubFolder(): string
    {
        return __DIR__.'/../../stubs/';
    }
}
