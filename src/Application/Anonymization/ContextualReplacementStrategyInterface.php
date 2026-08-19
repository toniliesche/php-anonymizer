<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;

interface ContextualReplacementStrategyInterface extends ReplacementStrategyInterface
{
    public function replaceWithContext(string $value, ReplacementContext $context): string;
}
