<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Model;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\ChildNodeNotFoundException;
use PhpAnonymizer\Anonymizer\Model\Data\Node;
use PhpAnonymizer\Anonymizer\Model\Data\Tree;
use PHPUnit\Framework\TestCase;

final class DataTreeTest extends TestCase
{
    public function testWillFailOnMissingChildNode(): void
    {
        $tree = new Tree([
            new Node(
                name: 'foo',
                dataAccess: DataAccess::ARRAY->value,
                nodeType: NodeType::LEAF,
                isList: false,
            ),
        ]);

        $this->expectException(ChildNodeNotFoundException::class);
        $tree->getChildNode('bar');
    }
}
