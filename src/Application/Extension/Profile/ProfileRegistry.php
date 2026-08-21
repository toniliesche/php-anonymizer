<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

final readonly class ProfileRegistry implements ProfileProviderInterface
{
    /** @var array<string, ProfileDefinition> */
    private array $profiles;

    /**
     * @param iterable<ProfileDefinition> $profiles
     */
    public function __construct(iterable $profiles)
    {
        $registered = [];
        foreach ($profiles as $profile) {
            $name = $profile->name();
            if (isset($registered[$name])) {
                throw new InvalidArgumentException(sprintf('Profile "%s" is registered more than once.', $name));
            }

            $registered[$name] = $profile;
        }

        $this->profiles = $registered;
    }

    public function get(string $name): ?ProfileDefinition
    {
        return $this->profiles[$name] ?? null;
    }

    /**
     * @return iterable<ProfileDefinition>
     */
    #[Override]
    public function profiles(): iterable
    {
        yield from $this->profiles;
    }
}
