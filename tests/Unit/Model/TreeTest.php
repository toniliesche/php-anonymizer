<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Model;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\ChildNodeNotFoundException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\NodeConflictException;
use PhpAnonymizer\Anonymizer\Model\Node;
use PhpAnonymizer\Anonymizer\Model\Tree;
use PHPUnit\Framework\TestCase;

final class TreeTest extends TestCase
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

        self::assertTrue($tree->hasChildNode('bar'));
        self::assertFalse($tree->hasChildNode('foo'));
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
        self::assertSame($node, $bar);

        $this->expectException(ChildNodeNotFoundException::class);
        $tree->getChildNode('baz');
    }

    public function testWillFailOnInvalidChildNodeInConstruct(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Tree(
            // @phpstan-ignore-next-line
            childNodes: [
                'test',
            ],
        );
    }

    public function testWillFailOnConflictingChildNodesInConstruct(): void
    {
        $this->expectException(NodeConflictException::class);
        new Tree(
            childNodes: [
                new Node(
                    name: 'foo',
                    dataAccess: DataAccess::DEFAULT->value,
                    nodeType: NodeType::NODE,
                    valueType: null,
                    isArray: false,
                ),
                new Node(
                    name: 'foo',
                    dataAccess: DataAccess::DEFAULT->value,
                    nodeType: NodeType::NODE,
                    valueType: null,
                    isArray: false,
                ),
            ],
        );
    }

    public function testCanAddChildNode(): void
    {
        $this->expectNotToPerformAssertions();
        $tree = new Tree();
        $tree->addChildNode(
            node: new Node(
                name: 'foo',
                dataAccess: DataAccess::DEFAULT->value,
                nodeType: NodeType::NODE,
                valueType: null,
                isArray: false,
            ),
        );
    }

    public function testCanAddSecondChildNode(): void
    {
        $this->expectNotToPerformAssertions();
        $tree = new Tree();

        $tree->addChildNode(
            node: new Node(
                name: 'foo',
                dataAccess: DataAccess::DEFAULT->value,
                nodeType: NodeType::NODE,
                valueType: null,
                isArray: false,
            ),
        );

        $tree->addChildNode(
            node: new Node(
                name: 'foo2',
                dataAccess: DataAccess::DEFAULT->value,
                nodeType: NodeType::NODE,
                valueType: null,
                isArray: false,
            ),
        );
    }

    public function testWillFailOnConflictingChildNode(): void
    {
        $tree = new Tree();

        $tree->addChildNode(
            node: new Node(
                name: 'foo',
                dataAccess: DataAccess::DEFAULT->value,
                nodeType: NodeType::NODE,
                valueType: null,
                isArray: false,
            ),
        );

        $this->expectException(NodeConflictException::class);
        $tree->addChildNode(
            node: new Node(
                name: 'foo',
                dataAccess: DataAccess::DEFAULT->value,
                nodeType: NodeType::NODE,
                valueType: null,
                isArray: false,
            ),
        );
    }
}
