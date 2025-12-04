<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(
        php82: true,
    )
    ->withComposerBased(
        phpunit: true,
        symfony: true,
    )
    ->withTypeCoverageLevel(0);
