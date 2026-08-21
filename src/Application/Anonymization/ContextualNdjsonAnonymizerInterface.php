<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;

interface ContextualNdjsonAnonymizerInterface
{
    /**
     * @param iterable<mixed> $lines
     */
    public function anonymizeNdjsonWithContext(string $profile, iterable $lines, AnonymizationContext $context): NdjsonAnonymizationResult;
}
