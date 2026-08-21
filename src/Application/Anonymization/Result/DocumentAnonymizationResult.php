<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

final readonly class DocumentAnonymizationResult
{
    /**
     * @param array<mixed, mixed> $data
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     * @param list<array{path: string, action: string, handler: ?string}> $events
     */
    public function __construct(private array $data, private array $anonymizedPaths = [], private array $missingPaths = [], private array $events = [])
    {
    }

    /**
     * @return array<mixed, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @return list<string>
     */
    public function anonymizedPaths(): array
    {
        return $this->anonymizedPaths;
    }

    /**
     * @return list<string>
     */
    public function missingPaths(): array
    {
        return $this->missingPaths;
    }

    /**
     * @return list<array{path: string, action: string, handler: ?string}>
     */
    public function events(): array
    {
        return $this->events;
    }
}
