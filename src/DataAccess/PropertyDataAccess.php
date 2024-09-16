<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use Error;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use ReflectionClass;
use ReflectionException;
use stdClass;

class PropertyDataAccess extends AbstractObjectDataAccess
{
    /**
     * @throws ReflectionException
     */
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(\array_slice($path, 0, -1));
        }

        if ($parent instanceof stdClass) {
            return \property_exists($parent, $name);
        }

        $reflection = new ReflectionClass($parent);

        return $reflection->hasProperty($name) && $reflection->getProperty($name)->isPublic();
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(\array_slice($path, 0, -1));
        }

        return $parent->{$name} ?? throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
    }

    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(\array_slice($path, 0, -1));
        }

        try {
            $parent->{$name} = $newValue;
        } catch (Error) {
            throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
        }
    }
}
