<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\DataAccess;

use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;
use PhpAnonymizer\Anonymizer\Model\Data\Tree;
use RuntimeException;

final class CustomDataAccess implements DataAccessInterface
{
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        // @phpstan-ignore-next-line
        return is_object($parent) && isset($parent->{$name});
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        // @phpstan-ignore-next-line
        return $parent->{$name};
    }

    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        // @phpstan-ignore-next-line
        $parent->{$name} = $newValue;
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
