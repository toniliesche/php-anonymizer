<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ContextualReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ReplacementContext;

final readonly class StaticContextualReplacementStrategy implements ContextualReplacementStrategyInterface
{
    public function __construct(private string $strategyId = 'contextual', private string $mode = 'value', private string $fixed = '')
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
        return $value;
    }

    #[Override]
    public function replaceWithContext(string $value, ReplacementContext $context): string
    {
        return match ($this->mode) {
            'describe' => $context->profile() . ':' . $context->path() . ':' . $context->keyId() . ':' . $context->attributes()['tenant'],
            'key' => $this->fixed . $context->keyId(),
            'fixed' => $this->fixed,
            default => $value,
        };
    }
}
