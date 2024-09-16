<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Dependency;

use Composer\InstalledVersions;

class DefaultDependencyChecker implements DependencyCheckerInterface
{
    public function libraryIsInstalled(string $library): bool
    {
        return InstalledVersions::isInstalled($library);
    }

    public function extensionIsLoaded(string $extension): bool
    {
        return \extension_loaded($extension);
    }
}
