<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    ->addPathToScan(__DIR__ . '/.php-cs-fixer.dist.php', isDev: true)
    ->addPathToScan(__DIR__ . '/rector.php', isDev: true)
    ->ignoreErrorsOnExtension(
        'ext-yaml',
        [
            ErrorType::DEV_DEPENDENCY_IN_PROD,
        ],
    )
    ->ignoreErrorsOnPackages(
        [
            'fakerphp/faker',
            'symfony/config',
            'symfony/dependency-injection',
            'symfony/http-kernel',
            'symfony/property-access',
            'symfony/property-info',
            'symfony/serializer',
        ],
        [
            ErrorType::DEV_DEPENDENCY_IN_PROD,
        ],
    );
