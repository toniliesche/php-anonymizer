<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile;

use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

interface ProfileProviderInterface
{
    /** @return iterable<ProfileDefinition> */
    public function profiles(): iterable;
}
