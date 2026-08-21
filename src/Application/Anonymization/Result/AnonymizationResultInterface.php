<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

interface AnonymizationResultInterface
{
    /**
     * @return array<string, mixed>|object
     */
    public function data(): array|object;

    /**
     * @return list<string>
     */
    public function anonymizedPaths(): array;

    /**
     * @return list<string>
     */
    public function missingPaths(): array;
}
