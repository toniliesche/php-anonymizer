<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Replacement;

interface ReplacementStrategyInterface
{
    public function id(): string;

    public function replace(string $value): string;
}
