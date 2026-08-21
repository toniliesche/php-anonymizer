<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

final readonly class AuditAnonymizationResult
{
    /** @var list<AuditEntry> */
    private array $entries;

    /**
     * @param array<string, mixed> $data
     * @param list<AuditEntry> $entries
     */
    public function __construct(private array $data, array $entries)
    {
        /** @var list<AuditEntry> $normalized */
        $normalized = $entries;
        $this->entries = $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @return list<AuditEntry>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
