<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Transformation;

use InvalidArgumentException;
use Override;

/**
 * @codeCoverageIgnore
 */
final class NullifyTransformation implements ValueTransformationInterface
{
    #[Override]
    public function id(): string
    {
        return 'nullify';
    }

    #[Override]
    public function supportedTypes(): array
    {
        return ['scalar', 'temporal'];
    }

    #[Override]
    public function outputType(): string
    {
        return 'null';
    }

    /**
     * @param array<string,mixed> $options
     */ #[Override]
    public function validateOptions(array $options): void
    {
        if ($options !== []) {
            throw new InvalidArgumentException('nullify does not accept options.');
        }
    }

    /**
     * @param array<string,mixed> $options
     */ #[Override]
    public function transform(mixed $value, array $options): mixed
    {
        return null;
    }
}
