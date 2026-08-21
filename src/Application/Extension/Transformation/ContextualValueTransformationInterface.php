<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Transformation;

use PhpAnonymizer\Anonymizer\Application\Anonymization\ValueTransformationContext;

interface ContextualValueTransformationInterface extends ValueTransformationInterface
{
    /**
     * @param array<string,mixed> $options
     */
    public function transformWithContext(mixed $value, array $options, ValueTransformationContext $context): mixed;
}
