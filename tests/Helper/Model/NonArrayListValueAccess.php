<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Model\Data\Node as DataNode;
use PhpAnonymizer\Anonymizer\Model\Data\Tree as DataTree;

final class NonArrayListValueAccess implements DataAccessInterface
{
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        return true;
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        return 'oops';
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
                name: 'items',
                dataAccess: DataAccess::ARRAY->value,
                nodeType: NodeType::NODE,
                isList: true,
            ),
        ]);
    }
}
