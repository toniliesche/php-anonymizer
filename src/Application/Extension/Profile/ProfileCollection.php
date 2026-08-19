<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

/** Immutable profile collection used by application-level construction APIs. */
final class ProfileCollection implements ProfileProviderInterface
{
    /** @var array<string, ProfileDefinition> */
    private array $profiles = [];

    /** @param list<ProfileDefinition> $profiles */
    public function __construct(array $profiles)
    {
        foreach ($profiles as $profile) {
            if (isset($this->profiles[$profile->name()])) {
                throw new InvalidArgumentException(sprintf('Profile "%s" is registered more than once.', $profile->name()));
            }
            $this->profiles[$profile->name()] = $profile;
        }
    }

    #[Override]
    public function profiles(): iterable
    {
        yield from $this->profiles;
    }
}
