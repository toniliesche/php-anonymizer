<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Model;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\ChildNodeNotFoundException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Model\Node;
use PHPUnit\Framework\TestCase;

class NodeTest extends TestCase
{
    public function testWillFailOnInitializationWithInvalidChildNodes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Node(
            name: 'foo',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
            /** @phpstan-ignore-next-line */
            childNodes: ['123'],
        );
    }

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

        self::assertTrue($node->hasChildNode('foo'));
        self::assertFalse($node->hasChildNode('baz'));
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

        $foo = $node->getChildNode('foo');
        self::assertSame($childNode, $foo);

        $this->expectException(ChildNodeNotFoundException::class);
        $node->getChildNode('baz');
    }
}
