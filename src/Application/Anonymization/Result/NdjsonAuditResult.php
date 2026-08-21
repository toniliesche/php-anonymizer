<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

final readonly class NdjsonAuditResult
{
    /**
     * @param list<AuditEntry> $entries
     */
    public function __construct(private array $entries)
    {
    }

    /**
     * @return list<AuditEntry>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
