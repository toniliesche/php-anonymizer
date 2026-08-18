<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;

final class AnonymizationReport
{
    /** @var list<string> */
    private array $anonymizedPaths = [];

    /** @var list<string> */
    private array $missingPaths = [];

    public function anonymized(JsonPointer $path): void
    {
        $this->anonymizedPaths[] = (string) $path;
    }

    public function missing(JsonPointer $path): void
    {
        $this->missingPaths[] = (string) $path;
    }

    public function merge(AnonymizationResult $result, JsonPointer $base): void
    {
        foreach ($result->anonymizedPaths() as $path) {
            $this->anonymizedPaths[] = (string) $base->prefix(JsonPointer::fromString($path));
        }
        foreach ($result->missingPaths() as $path) {
            $this->missingPaths[] = (string) $base->prefix(JsonPointer::fromString($path));
        }
    }

    /** @return list<string> */
    public function anonymizedPaths(): array
    {
        return $this->anonymizedPaths;
    }

    /** @return list<string> */
    public function missingPaths(): array
    {
        return $this->missingPaths;
    }

}
