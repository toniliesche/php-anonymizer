<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use IteratorAggregate;
use Override;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use Traversable;

/**
 * @implements IteratorAggregate<int, ProfileProviderInterface>
 */
final readonly class ProfileProviderSequence implements IteratorAggregate
{
    /**
     * @param list<ProfileProviderInterface> $providers
     */
    public function __construct(private array $providers)
    {
    }

    /**
     * @return Traversable<int, ProfileProviderInterface>
     */
    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->providers;
    }
}
