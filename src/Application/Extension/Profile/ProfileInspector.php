<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile;

use Override;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

final readonly class ProfileInspector implements ProfileInspectorInterface
{
    private ProfileRegistry $registry;

    public function __construct(ProfileProviderInterface $provider)
    {
        $this->registry = new ProfileRegistry($provider->profiles());
    }

    #[Override]
    public function inspect(string $name): ?ProfileInspection
    {
        $profile = $this->registry->get($name);
        if (!$profile instanceof ProfileDefinition) {
            return null;
        }

        return $this->inspection($profile);
    }

    #[Override]
    public function profiles(): iterable
    {
        foreach ($this->registry->profiles() as $profile) {
            yield $this->inspection($profile);
        }
    }

    private function inspection(ProfileDefinition $profile): ProfileInspection
    {
        return new ProfileInspection($profile->name(), $profile->metadata(), $profile->fingerprint(), $profile->fragmentNames());
    }
}
