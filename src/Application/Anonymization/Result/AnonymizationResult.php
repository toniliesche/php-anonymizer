<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

use Override;

final readonly class AnonymizationResult implements AnonymizationResultInterface
{
    /**
     * @param array<string, mixed> $data
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    public function __construct(
        private array $data,
        private array $anonymizedPaths,
        private array $missingPaths,
    ) {}

    /** @return array<string, mixed> */
    #[Override]
    public function data(): array
    {
        return $this->data;
    }

    /** @return list<string> */
    #[Override]
    public function anonymizedPaths(): array
    {
        return $this->anonymizedPaths;
    }

    /** @return list<string> */
    #[Override]
    public function missingPaths(): array
    {
        return $this->missingPaths;
    }
}
