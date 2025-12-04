<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use Error;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\FieldIsNotInitializedException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use function array_slice;
use function method_exists;
use function ucfirst;

final class SetterDataAccess extends AbstractObjectDataAccess
{
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $getter = 'get' . ucfirst($name);
        $setter = 'set' . ucfirst($name);

        try {
            return method_exists($parent, $getter)
                // @phpstan-ignore-next-line
                && $parent->{$getter}() !== null
                && method_exists($parent, $setter);
        } catch (Error) {
            return false;
        }
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

        try {
            // @phpstan-ignore-next-line
            return $parent->{$getter}();
        } catch (Error) {
            throw FieldIsNotInitializedException::fromPath($path);
        }
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

        // @phpstan-ignore-next-line
        $parent->{$setter}($newValue);
    }
}
