<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;

interface ProfileProviderInterface
{
    /** @return iterable<ProfileDefinition> */
    public function profiles(): iterable;
}
