<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use function array_key_exists;
use function array_slice;
use function is_array;

class ArrayDataAccess implements DataAccessInterface
{
    /**
     * @param array<string,mixed> $parent
     */
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnArray(array_slice($path, 0, -1));
        }

        return array_key_exists($name, $parent);
    }

    /**
     * @param array<string,mixed> $parent
     */
    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnArray(array_slice($path, 0, -1));
        }

        return $parent[$name] ?? throw FieldDoesNotExistException::fromPath($path);
    }

    /**
     * @param array<string,mixed> $parent
     */
    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnArray(array_slice($path, 0, -1));
        }

        if (!array_key_exists($name, $parent)) {
            throw FieldDoesNotExistException::fromPath($path);
        }

        $parent[$name] = $newValue;
    }

    public function supports(mixed $parent): bool
    {
        return is_array($parent);
    }
}
