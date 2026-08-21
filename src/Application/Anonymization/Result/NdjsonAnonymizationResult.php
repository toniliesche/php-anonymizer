<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

use Closure;

final readonly class NdjsonAnonymizationResult
{
    /**
     * @param iterable<string> $lines
     * @param Closure(): NdjsonAuditResult $auditFactory
     */
    public function __construct(private iterable $lines, private Closure $auditFactory)
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
        return ($this->auditFactory)();
    }
}
