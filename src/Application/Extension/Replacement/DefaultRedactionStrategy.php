<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Replacement;

use Override;

final class DefaultRedactionStrategy implements ReplacementStrategyInterface
{
    #[Override]
    public function id(): string
    {
        return 'redact';
    }

    #[Override]
    public function replace(string $value): string
    {
        return '[REDACTED]';
    }
}
