<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use Override;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;

final readonly class StaticReplacementStrategy implements ReplacementStrategyInterface
{
    public function __construct(private string $strategyId, private ?string $replacement = null, private string $suffix = '')
    {
    }

    #[Override]
    public function id(): string
    {
        return $this->strategyId;
    }

    #[Override]
    public function replace(string $value): string
    {
        return $this->replacement ?? $value . $this->suffix;
    }
}
