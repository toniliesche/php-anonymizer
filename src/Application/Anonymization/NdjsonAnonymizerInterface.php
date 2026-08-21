<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;

interface NdjsonAnonymizerInterface
{
    /**
     * @param iterable<mixed> $lines
     */
    public function anonymizeNdjson(string $profile, iterable $lines): NdjsonAnonymizationResult;
}
