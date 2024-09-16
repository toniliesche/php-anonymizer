<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\DataGeneration;

use PhpAnonymizer\Anonymizer\DataGeneration\DataGeneratorInterface;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use Random\RandomException;

/**
 * @template-implements DataGeneratorInterface<int>
 */
class RandomIntGenerator implements DataGeneratorInterface
{
    public function supports(mixed $value, ?string $valueType): bool
    {
        return is_int($value);
    }

    /**
     * @param string[] $path
     *
     * @throws RandomException
     */
    public function generate(array $path, mixed $oldValue, ?string $valueType): mixed
    {
        if (!is_int($oldValue)) {
            throw InvalidObjectTypeException::notAnInteger($path);
        }

        return random_int(0, 100);
    }
}
