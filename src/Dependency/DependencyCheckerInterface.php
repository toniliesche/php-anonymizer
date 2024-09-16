<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Dependency;

interface DependencyCheckerInterface
{
    public function extensionIsLoaded(string $extension): bool;

    public function libraryIsInstalled(string $library): bool;
}
