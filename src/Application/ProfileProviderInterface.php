<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;

interface ProfileProviderInterface
{
    public function get(string $name): ?ProfileDefinition;
}
