<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use function array_slice;
use function method_exists;
use function ucfirst;

class SetterDataAccess extends AbstractObjectDataAccess
{
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $getter = 'get' . ucfirst($name);
        $setter = 'set' . ucfirst($name);

        return method_exists($parent, $getter) && method_exists($parent, $setter);
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $getter = 'get' . ucfirst($name);

        if (!method_exists($parent, $getter)) {
            throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
        }

        return $parent->{$getter}();
    }

    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $setter = 'set' . ucfirst($name);

        if (!method_exists($parent, $setter)) {
            throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
        }

        $parent->{$setter}($newValue);
    }
}
