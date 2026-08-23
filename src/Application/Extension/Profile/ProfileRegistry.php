<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use Webmozart\Assert\Assert;

// @internal
final readonly class ProfileRegistry implements ProfileProviderInterface
{
    /** @var array<string, ProfileDefinition> */
    private array $profiles;

    /**
     * @param iterable<mixed> $profiles
     */
    public function __construct(iterable $profiles)
    {
        $registered = [];
        foreach ($profiles as $profile) {
            Assert::isInstanceOf($profile, ProfileDefinition::class, sprintf('Profile registry entries must implement %s.', ProfileDefinition::class));
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
