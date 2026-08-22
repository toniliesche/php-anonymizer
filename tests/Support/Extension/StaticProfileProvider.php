<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use Override;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

final readonly class StaticProfileProvider implements ProfileProviderInterface
{
    /**
     * @param list<ProfileDefinition> $profiles
     */
    public function __construct(private array $profiles)
    {
    }

    #[Override]
    public function profiles(): iterable
    {
        yield from $this->profiles;
    }
}
