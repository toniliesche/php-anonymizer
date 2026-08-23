<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

use Closure;
use LogicException;

final readonly class NdjsonAnonymizationResult
{
    /**
     * @param iterable<string> $lines
     * @param null|Closure(): mixed $auditFactory
     */
    public function __construct(private iterable $lines, private NdjsonAuditProviderInterface $auditProvider, private ?Closure $auditFactory = null)
    {
    }

    /**
     * @param iterable<string> $lines
     * @param Closure(): mixed $auditFactory
     */
    public static function fromLines(iterable $lines, Closure $auditFactory): self
    {
        return new self($lines, new DeferredNdjsonAuditProvider(), $auditFactory);
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
        if ($this->auditFactory instanceof Closure) {
            $audit = ($this->auditFactory)();
            if (!$audit instanceof NdjsonAuditResult) {
                throw new LogicException('The NDJSON audit factory must return an NdjsonAuditResult.');
            }

            return $audit;
        }

        return $this->auditProvider->audit();
    }
}
