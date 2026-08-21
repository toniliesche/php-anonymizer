<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Transformation;

use InvalidArgumentException;
use Override;

/**
 * @codeCoverageIgnore
 */
final class PartialMaskTransformation implements ValueTransformationInterface
{
    #[Override]
    public function id(): string
    {
        return 'partial_mask';
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

    /**
     * @param array<string,mixed> $options
     */ #[Override]
    public function validateOptions(array $options): void
    {
        foreach (['prefix', 'suffix'] as $key) {
            if (isset($options[$key]) && (!is_int($options[$key]) || $options[$key] < 0)) {
                throw new InvalidArgumentException(sprintf('%s must be a non-negative integer.', $key));
            }
        } if (isset($options['character']) && (!is_string($options['character']) || $options['character'] === '')) {
            throw new InvalidArgumentException('character must be a non-empty string.');
        }
    }

    /**
     * @param array<string,mixed> $options
     */ #[Override]
    public function transform(mixed $value, array $options): mixed
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('partial_mask requires a string.');
        } $prefix = is_int($options['prefix'] ?? null) ? $options['prefix'] : 0;
        $suffix = is_int($options['suffix'] ?? null) ? $options['suffix'] : 4;
        $character = is_string($options['character'] ?? null) ? $options['character'] : '*';
        $length = mb_strlen($value, 'UTF-8');
        $visible = min($length, $prefix + $suffix);
        if ($visible === $length) {
            return $value;
        }

return mb_substr($value, 0, $prefix, 'UTF-8') . str_repeat($character, $length - $visible) . ($suffix > 0 ? mb_substr($value, -$suffix, null, 'UTF-8') : '');
    }
}
