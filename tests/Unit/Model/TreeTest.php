<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Model;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\ChildNodeNotFoundException;
use PhpAnonymizer\Anonymizer\Model\Node;
use PhpAnonymizer\Anonymizer\Model\Tree;
use PHPUnit\Framework\TestCase;

class TreeTest extends TestCase
{
    public function testCanCheckIfChildNodesExist(): void
    {
        $childNode = new Node(
            name: 'foo',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $node = new Node(
            name: 'bar',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::NODE,
            valueType: null,
            isArray: false,
            childNodes: [
                $childNode,
            ],
        );

        $tree = new Tree(
            childNodes: [
                $node,
            ],
        );

        $this->assertTrue($tree->hasChildNode('bar'));
        $this->assertFalse($tree->hasChildNode('foo'));
    }

    public function testCanGetChildNodes(): void
    {
        $childNode = new Node(
            name: 'foo',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $node = new Node(
            name: 'bar',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::NODE,
            valueType: null,
            isArray: false,
            childNodes: [
                $childNode,
            ],
        );

        $tree = new Tree(
            childNodes: [
                $node,
            ],
        );

        $bar = $tree->getChildNode('bar');
        $this->assertSame($node, $bar);

        $this->expectException(ChildNodeNotFoundException::class);
        $tree->getChildNode('baz');
    }
}
