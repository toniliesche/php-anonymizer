<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Dependency;

use Composer\InstalledVersions;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use function class_exists;
use function extension_loaded;

final class DefaultDependencyChecker implements DependencyCheckerInterface
{
    public function __construct()
    {
        if (!class_exists(InstalledVersions::class)) {
            throw new MissingPlatformRequirementsException('Composer is required to check for library dependencies');
        }
    }

    public function libraryIsInstalled(string $library): bool
    {
        return InstalledVersions::isInstalled($library);
    }

    public function extensionIsLoaded(string $extension): bool
    {
        return extension_loaded($extension);
    }
}
