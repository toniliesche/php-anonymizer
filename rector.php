<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/.*.php',
    ])
    ->withRootFiles()
    ->withPhpSets(
        php82: true,
    )
    ->withAttributesSets(
        symfony: true,
        phpunit: true,
    )
    ->withComposerBased(
        phpunit: true,
        symfony: true,
    )
    ->withSets(
        [
            SetList::DEAD_CODE,
            SetList::CODE_QUALITY,
            SetList::PRIVATIZATION,
            SetList::TYPE_DECLARATION,
            SetList::EARLY_RETURN,
            PHPUnitSetList::PHPUNIT_110,
            PHPUnitSetList::PHPUNIT_CODE_QUALITY,
            PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
            __DIR__ . '/vendor/thecodingmachine/safe/rector-migrate.php',
        ],
    )
    ->withConfiguredRule(
        AddOverrideAttributeToOverriddenMethodsRector::class,
        [
            'allow_override_empty_method' => true,
        ],
    )
    ->withImportNames()
    ->withRules(
        [
            Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitSelfCallRector::class,
        ],
    );
