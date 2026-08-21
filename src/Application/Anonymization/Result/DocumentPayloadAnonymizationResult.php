<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

final readonly class DocumentPayloadAnonymizationResult
{
    public function __construct(private string $payload, private DocumentAnonymizationResult $anonymizationResult)
    {
    }

    public function payload(): string
    {
        return $this->payload;
    }

    public function anonymizationResult(): DocumentAnonymizationResult
    {
        return $this->anonymizationResult;
    }
}
