<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Model\Data\Node as DataNode;
use PhpAnonymizer\Anonymizer\Model\Data\Tree as DataTree;

final class MissingChildAccess implements DataAccessInterface
{
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        return false;
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
        return true;
    }

    public function parseDataTree(mixed $data, array $path = []): DataTree
    {
        return new DataTree([
            new DataNode(
                name: 'name',
                dataAccess: DataAccess::ARRAY->value,
                nodeType: NodeType::LEAF,
                isList: false,
            ),
        ]);
    }
}
