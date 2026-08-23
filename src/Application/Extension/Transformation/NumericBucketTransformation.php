<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Transformation;

use InvalidArgumentException;
use Override;

/**
 * @codeCoverageIgnore
 */
// @internal
final class NumericBucketTransformation implements ValueTransformationInterface
{
    #[Override]
    public function id(): string
    {
        return 'numeric_bucket';
    }

    #[Override]
    public function supportedTypes(): array
    {
        return ['int', 'float'];
    }

    #[Override]
    public function outputType(): string
    {
        return 'same';
    }

    /**
     * @param array<string,mixed> $options
     */ #[Override]
    public function validateOptions(array $options): void
    {
        if ((!is_int($options['size'] ?? null) && !is_float($options['size'] ?? null)) || $options['size'] <= 0) {
            throw new InvalidArgumentException('numeric_bucket size must be positive.');
        } if (isset($options['origin']) && !is_int($options['origin']) && !is_float($options['origin'])) {
            throw new InvalidArgumentException('numeric_bucket origin must be numeric.');
        }
    }

    /**
     * @param array<string,mixed> $options
     */ #[Override]
    public function transform(mixed $value, array $options): mixed
    {
        if (!is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException('numeric_bucket requires a number.');
        } $size = $options['size'];
        $origin = $options['origin'] ?? 0;
        if ((!is_int($size) && !is_float($size)) || (!is_int($origin) && !is_float($origin))) {
            throw new InvalidArgumentException('numeric_bucket options are invalid.');
        }$bucket = floor(($value - $origin) / $size) * $size + $origin;

        return is_int($value) ? (int) $bucket : $bucket;
    }
}
