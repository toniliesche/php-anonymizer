<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Replacement;

use Override;

// @internal
final class StarMaskStrategy implements ReplacementStrategyInterface
{
    #[Override]
    public function id(): string
    {
        return 'star_mask';
    }

    #[Override]
    public function replace(string $value): string
    {
        return str_repeat('*', mb_strlen($value, 'UTF-8'));
    }
}
