<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\DataAccess;

use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Model\Data\Tree;
use RuntimeException;

final class ThrowingGetChildAccess implements DataAccessInterface
{
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        return true;
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        throw FieldDoesNotExistException::fromPath($path);
    }

    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
    }

    public function supports(mixed $parent): bool
    {
        return is_object($parent);
    }

    public function parseDataTree(mixed $data, array $path = []): Tree
    {
        throw new RuntimeException();
    }
}
