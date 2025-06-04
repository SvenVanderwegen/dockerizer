<?php

use SvenVanderwegen\Dockerizer\Enums\DatabaseOptions;
use SvenVanderwegen\Dockerizer\Services\PostgresDockerService;

it('returns Postgres service class for POSTGRESQL option', function () {
    expect(DatabaseOptions::POSTGRESQL->getDockerService())
        ->toBe(PostgresDockerService::class);
});
