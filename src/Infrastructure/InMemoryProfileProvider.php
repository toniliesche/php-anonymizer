<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Infrastructure;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;

final class InMemoryProfileProvider implements ProfileProviderInterface
{
    /** @var array<string, ProfileDefinition> */
    private array $profiles = [];

    /** @param list<ProfileDefinition> $profiles */
    public function __construct(array $profiles)
    {
        foreach ($profiles as $profile) {
            $name = $profile->name();
            if (isset($this->profiles[$name])) {
                throw new InvalidArgumentException(sprintf('Profile "%s" is registered more than once.', $name));
            }

            $this->profiles[$name] = $profile;
        }
    }

    #[\Override]
    public function get(string $name): ?ProfileDefinition
    {
        return $this->profiles[$name] ?? null;
    }
}
