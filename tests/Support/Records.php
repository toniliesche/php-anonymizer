<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support;

use IteratorAggregate;
use Override;
use Traversable;

/**
 * @implements IteratorAggregate<int, array<string, string>>
 */
final readonly class Records implements IteratorAggregate
{
    /**
     * @param list<array<string, string>> $records
     */
    public function __construct(private array $records)
    {
    }

    /**
     * @return Traversable<int, array<string, string>>
     */
    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->records;
    }
}
