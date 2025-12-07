<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;

final readonly class AutoDetectDataAccess implements DataAccessInterface
{
    /**
     * @param DataAccessInterface[] $dataAccesses
     */
    public function __construct(
        private array $dataAccesses,
    ) {
        foreach ($this->dataAccesses as $dataAccess) {
            if (!$dataAccess instanceof DataAccessInterface) {
                throw new InvalidArgumentException('All data accesses must implement DataAccessInterface');
            }
        }
    }

    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        foreach ($this->dataAccesses as $dataAccess) {
            if ($dataAccess->supports($parent) && $dataAccess->hasChild($path, $parent, $name)) {
                return true;
            }
        }

        return false;
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        foreach ($this->dataAccesses as $dataAccess) {
            if ($dataAccess->supports($parent) && $dataAccess->hasChild($path, $parent, $name)) {
                try {
                    return $dataAccess->getChild($path, $parent, $name);
                    // @codeCoverageIgnoreStart
                } catch (FieldDoesNotExistException) {
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
    }

    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        foreach ($this->dataAccesses as $dataAccess) {
            if ($dataAccess->supports($parent) && $dataAccess->hasChild($path, $parent, $name)) {
                try {
                    $dataAccess->setChildValue($path, $parent, $name, $newValue);

                    return;
                    // @codeCoverageIgnoreStart
                } catch (FieldDoesNotExistException) {
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
    }

    public function supports(mixed $parent): bool
    {
        foreach ($this->dataAccesses as $dataAccess) {
            if ($dataAccess->supports($parent)) {
                return true;
            }
        }

        return false;
    }
}
