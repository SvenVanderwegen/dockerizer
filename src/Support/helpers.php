<?php

declare(strict_types=1);

use SvenVanderwegen\Dockerizer\Support\ConfigHelper;

if (! function_exists('dockerizer_config')) {
    /**
     * Get the Dockerizer configuration instance or a specific value.
     *
     * @template T
     *
     * @param  T  $default
     * @return (
     *     $key is null
     *     ? ConfigHelper
     *     : ($default is null ? mixed : T)
     * )
     */
    function dockerizer_config(?string $key = null, mixed $default = null): mixed
    {
        /** @var ConfigHelper $instance */
        $instance = app(ConfigHelper::class);

        if (is_null($key)) {
            return $instance;
        }

        return $instance->get($key, $default);
    }
}

if (! function_exists('dconfig')) {
    /**
     * Get the Dockerizer configuration instance or a specific value.
     *
     * @template T
     *
     * @param  T  $default
     * @return (
     *     $key is null
     *     ? ConfigHelper
     *     : ($default is null ? mixed : T)
     * )
     */
    function dconfig(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            /** @var ConfigHelper */
            return app(ConfigHelper::class);
        }

        return dockerizer_config($key, $default);
    }
}
