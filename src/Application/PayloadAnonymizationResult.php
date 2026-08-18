<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

final readonly class PayloadAnonymizationResult
{
    public function __construct(
        private string $payload,
        private AnonymizationResult $anonymizationResult,
    ) {}

    public function payload(): string
    {
        return $this->payload;
    }

    public function anonymizationResult(): AnonymizationResult
    {
        return $this->anonymizationResult;
    }
}
