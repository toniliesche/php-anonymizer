<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

interface BatchAnonymizerInterface
{
    /**
     * @param iterable<array<string, mixed>> $records
     *
     * @return iterable<array<string, mixed>>
     */
    public function anonymizeRecords(string $profile, iterable $records): iterable;
}
