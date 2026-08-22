<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

final readonly class NdjsonAnonymizationResult
{
    /**
     * @param iterable<string> $lines
     */
    public function __construct(private iterable $lines, private NdjsonAuditProviderInterface $auditProvider)
    {
    }

    /**
     * @return iterable<string>
     */
    public function lines(): iterable
    {
        return $this->lines;
    }

    public function audit(): NdjsonAuditResult
    {
        return $this->auditProvider->audit();
    }
}
