<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;

final readonly class StaticValueTransformation implements ValueTransformationInterface
{
    /**
     * @param list<string> $types
     */
    public function __construct(private string $transformationId, private array $types = ['string'], private string $resultType = 'string', private string $prefix = '')
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
        return $this->types;
    }

    #[Override]
    public function outputType(): string
    {
        return $this->resultType;
    }

    #[Override]
    public function validateOptions(array $options): void
    {
    }

    #[Override]
    public function transform(mixed $value, array $options): mixed
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected a string.');
        }

        return $this->prefix . $value;
    }
}
