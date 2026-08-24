<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ValueTransformationContext;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ContextualValueTransformationInterface;

final readonly class StaticContextualValueTransformation implements ContextualValueTransformationInterface
{
    public function __construct(private string $transformationId = 'contextual_transform')
    {
    }

    #[Override]
    public function id(): string
    {
        return $this->transformationId;
    }

    #[Override]
    public function supportedTypes(): array
    {
        return ['string'];
    }

    #[Override]
    public function outputType(): string
    {
        return 'string';
    }

    #[Override]
    public function validateOptions(array $options): void
    {
    }

    #[Override]
    public function transform(mixed $value, array $options): mixed
    {
        return $value;
    }

    #[Override]
    public function transformWithContext(mixed $value, array $options, ValueTransformationContext $context): mixed
    {
        $value = is_scalar($value) || $value === null ? (string) $value : get_debug_type($value);

        return sprintf('%s:%s:%s', $value, $context->profile(), $context->pathString());
    }
}
