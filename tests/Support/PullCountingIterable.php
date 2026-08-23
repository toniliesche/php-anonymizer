<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support;

use IteratorAggregate;
use Override;
use Traversable;

/**
 * @implements IteratorAggregate<int, mixed>
 */
final class PullCountingIterable implements IteratorAggregate
{
    private int $pulls = 0;

    /**
     * @param list<mixed> $values
     */
    public function __construct(private readonly array $values)
    {
    }

    public function pulls(): int
    {
        return $this->pulls;
    }

    /**
     * @return Traversable<int, mixed>
     */
    #[Override]
    public function getIterator(): Traversable
    {
        ++$this->pulls;

        yield from $this->values;
    }
}
