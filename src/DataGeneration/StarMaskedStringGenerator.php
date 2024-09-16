<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataGeneration;

use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;

/**
 * @template-implements DataGeneratorInterface<string>
 */
class StarMaskedStringGenerator implements DataGeneratorInterface
{
    public function supports(mixed $value, ?string $valueType): bool
    {
        return \is_string($value);
    }

    public function generate(array $path, mixed $oldValue, ?string $valueType): string
    {
        if (!\is_string($oldValue)) {
            throw InvalidObjectTypeException::notAString($path);
        }

        return \str_repeat('*', \strlen($oldValue));
    }
}
