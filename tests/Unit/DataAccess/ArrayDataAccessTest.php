<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess;

use ArrayObject;
use PhpAnonymizer\Anonymizer\DataAccess\ArrayDataAccess;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ArrayDataAccessTest extends TestCase
{
    public function testCanParseSimpleDataTree(): void
    {
        $access = new ArrayDataAccess();

        $data = [
            'baz' => 'baz',
        ];

        $tree = $access->parseDataTree($data);

        self::assertCount(1, $tree->childNodes);
        $node = $tree->childNodes[0];

        self::assertSame('baz', $node->name);
        self::assertSame(NodeType::LEAF, $node->nodeType);
        self::assertSame(DataAccess::ARRAY->value, $node->dataAccess);
        self::assertFalse($node->isList);
        self::assertEmpty($node->childNodes);
    }

    public function testCanParseDataTreeWithChildren(): void
    {
        $access = new ArrayDataAccess();

        $data = [
            'foobar' => [
                'baz' => 'baz',
            ],
            'array' => [
                'foo',
                'bar',
                'baz',
            ],
        ];

        $tree = $access->parseDataTree($data);

        self::assertCount(2, $tree->childNodes);
        $foobarNode = $tree->childNodes[0];

        self::assertSame('foobar', $foobarNode->name);
        self::assertSame(NodeType::NODE, $foobarNode->nodeType);
        self::assertSame(DataAccess::ARRAY->value, $foobarNode->dataAccess);
        self::assertFalse($foobarNode->isList);
        self::assertNotEmpty($foobarNode->childNodes);

        $arrayNode = $tree->childNodes[1];

        self::assertSame('array', $arrayNode->name);
        self::assertSame(NodeType::LEAF, $arrayNode->nodeType);
        self::assertSame(DataAccess::ARRAY->value, $arrayNode->dataAccess);
        self::assertTrue($arrayNode->isList);
        self::assertEmpty($arrayNode->childNodes);
    }

    public function testCanParseDataTreeWithObjectArray(): void
    {
        $access = new ArrayDataAccess();

        $data = [
            'foobar' => [
                'baz' => 'baz',
            ],
            'array' => [
                [
                    'baz' => 'baz',
                ],
                [
                    'name' => 'John Doe',
                    'city' => 'New York',
                ],
            ],
        ];

        $tree = $access->parseDataTree($data);

        self::assertCount(2, $tree->childNodes);
        $foobarNode = $tree->childNodes[0];

        self::assertSame('foobar', $foobarNode->name);
        self::assertSame(NodeType::NODE, $foobarNode->nodeType);
        self::assertSame(DataAccess::ARRAY->value, $foobarNode->dataAccess);
        self::assertFalse($foobarNode->isList);
        self::assertNotEmpty($foobarNode->childNodes);

        $arrayNode = $tree->childNodes[1];

        self::assertSame('array', $arrayNode->name);
        self::assertSame(NodeType::NODE, $arrayNode->nodeType);
        self::assertSame(DataAccess::ARRAY->value, $arrayNode->dataAccess);
        self::assertTrue($arrayNode->isList);
        self::assertCount(3, $arrayNode->childNodes);

        foreach ($arrayNode->childNodes as $childNode) {
            self::assertSame(NodeType::LEAF, $childNode->nodeType);
            self::assertSame(DataAccess::ARRAY->value, $childNode->dataAccess);
            self::assertFalse($childNode->isList);
            self::assertEmpty($childNode->childNodes);
        }
    }

    public function testCanCheckIfChildPropertyExists(): void
    {
        $access = new ArrayDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        self::assertTrue($access->hasChild(['test'], $data, 'foo'));
        self::assertFalse($access->hasChild(['test'], $data, 'bar'));
        self::assertFalse($access->hasChild(['test'], $data, 'FOO'));
    }

    public function testWillFailOnCheckForChildPropertyOfNonArray(): void
    {
        $access = new ArrayDataAccess();

        $data = new stdClass();

        $this->expectException(InvalidObjectTypeException::class);

        $access->hasChild(['test'], $data, 'foo');
    }

    public function testCanRetrieveValueOfChildProperty(): void
    {
        $access = new ArrayDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        self::assertSame('bar', $access->getChild(['test'], $data, 'foo'));
    }

    public function testWillFailOnRetrieveValueOfChildPropertyOnNonArray(): void
    {
        $access = new ArrayDataAccess();

        $data = new stdClass();

        $this->expectException(InvalidObjectTypeException::class);

        $access->getChild(['test'], $data, 'foo');
    }

    public function testWillFailOnRetrieveValueOfNonExistantChildProperty(): void
    {
        $access = new ArrayDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'bar');
    }

    public function testCanSetValueOfChildProperty(): void
    {
        $access = new ArrayDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $access->setChildValue(['test'], $data, 'foo', 'baz');
        self::assertSame('baz', $data['foo']);
    }

    public function testWillFailOnSetValueOfNonExistantChildProperty(): void
    {
        $access = new ArrayDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(FieldDoesNotExistException::class);
        $access->setChildValue(['test'], $data, 'bar', 'baz');
    }

    public function testWillFailOnSetValueOfChildPropertyOfInvalidType(): void
    {
        $access = new ArrayDataAccess();

        $data = new stdClass();

        $this->expectException(InvalidObjectTypeException::class);
        $access->setChildValue(['test'], $data, 'foo', '123');
    }

    public function testCanVerifySupportOfDataTypes(): void
    {
        $access = new ArrayDataAccess();

        self::assertTrue($access->supports([]));
        self::assertTrue($access->supports(['foo' => 'bar']));
        self::assertFalse($access->supports(new ArrayObject()));
        self::assertFalse($access->supports('foo'));
    }
}
