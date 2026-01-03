<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess;

use PhpAnonymizer\Anonymizer\DataAccess\PropertyDataAccess;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\FieldIsNotInitializedException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Barfoo;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Foobar;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\PublicAddress;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\PublicFoobar;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\PublicFooParent;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\ReadonlyFoobar;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PropertyDataAccessTest extends TestCase
{
    public function testCanParseSimpleDataTree(): void
    {
        $access = new PropertyDataAccess();

        $data = new PublicFoobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $tree = $access->parseDataTree($data);

        self::assertCount(1, $tree->childNodes);
        $node = $tree->childNodes[0];

        self::assertSame('baz', $node->name);
        self::assertSame(NodeType::LEAF, $node->nodeType);
        self::assertSame(DataAccess::PROPERTY->value, $node->dataAccess);
        self::assertFalse($node->isList);
        self::assertEmpty($node->childNodes);
    }

    public function testCanParseDataTreeWithChildren(): void
    {
        $access = new PropertyDataAccess();

        $data = new PublicFooParent(
            foobar: new PublicFoobar(
                foo: 'foo',
                bar: 'bar',
                baz: 'baz',
            ),
            array: [
                'foo',
                'bar',
                'baz',
            ],
        );

        $tree = $access->parseDataTree($data);

        self::assertCount(2, $tree->childNodes);
        $foobarNode = $tree->childNodes[0];

        self::assertSame('foobar', $foobarNode->name);
        self::assertSame(NodeType::NODE, $foobarNode->nodeType);
        self::assertSame(DataAccess::PROPERTY->value, $foobarNode->dataAccess);
        self::assertFalse($foobarNode->isList);
        self::assertNotEmpty($foobarNode->childNodes);

        $arrayNode = $tree->childNodes[1];

        self::assertSame('array', $arrayNode->name);
        self::assertSame(NodeType::LEAF, $arrayNode->nodeType);
        self::assertSame(DataAccess::PROPERTY->value, $arrayNode->dataAccess);
        self::assertTrue($arrayNode->isList);
        self::assertEmpty($arrayNode->childNodes);
    }

    public function testCanParseDataTreeWithObjectArray(): void
    {
        $access = new PropertyDataAccess();

        $data = new PublicFooParent(
            foobar: new PublicFoobar(
                foo: 'foo',
                bar: 'bar',
                baz: 'baz',
            ),
            array: [
                new PublicFoobar(
                    foo: 'foo',
                    bar: 'bar',
                    baz: 'baz',
                ),
                new PublicAddress(
                    name: 'John Doe',
                    city: 'New York',
                ),
            ],
        );

        $tree = $access->parseDataTree($data);

        self::assertCount(2, $tree->childNodes);
        $foobarNode = $tree->childNodes[0];

        self::assertSame('foobar', $foobarNode->name);
        self::assertSame(NodeType::NODE, $foobarNode->nodeType);
        self::assertSame(DataAccess::PROPERTY->value, $foobarNode->dataAccess);
        self::assertFalse($foobarNode->isList);
        self::assertNotEmpty($foobarNode->childNodes);

        $arrayNode = $tree->childNodes[1];

        self::assertSame('array', $arrayNode->name);
        self::assertSame(NodeType::NODE, $arrayNode->nodeType);
        self::assertSame(DataAccess::PROPERTY->value, $arrayNode->dataAccess);
        self::assertTrue($arrayNode->isList);
        self::assertCount(3, $arrayNode->childNodes);

        foreach ($arrayNode->childNodes as $childNode) {
            self::assertSame(NodeType::LEAF, $childNode->nodeType);
            self::assertSame(DataAccess::PROPERTY->value, $childNode->dataAccess);
            self::assertFalse($childNode->isList);
            self::assertEmpty($childNode->childNodes);
        }
    }

    public function testCanCheckIfChildPropertyExists(): void
    {
        $access = new PropertyDataAccess();

        $data = new stdClass();
        $data->foo = 'bar';

        self::assertTrue($access->hasChild(['test'], $data, 'foo'));
        self::assertFalse($access->hasChild(['test'], $data, 'bar'));
        self::assertFalse($access->hasChild(['test'], $data, 'FOO'));
    }

    public function testCanCheckIfUnitializedChildPropertyDoesNotExist(): void
    {
        $access = new PropertyDataAccess();

        $data = new Barfoo();
        self::assertFalse($access->hasChild(['test'], $data, 'foo'));
    }

    public function testWillFailOnCheckForChildPropertyOfNonObject(): void
    {
        $access = new PropertyDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->hasChild(['test'], $data, 'foo');
    }

    public function testCanRetrieveValueOfChildProperty(): void
    {
        $access = new PropertyDataAccess();

        $data = new stdClass();
        $data->foo = 'bar';

        self::assertSame('bar', $access->getChild(['test'], $data, 'foo'));
    }

    public function testWillFailOnRetrieveValueOfChildPropertyOnNonObject(): void
    {
        $access = new PropertyDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->getChild(['test'], $data, 'foo');
    }

    public function testWillFailOnRetrieveValueOfNonExistantChildProperty(): void
    {
        $access = new PropertyDataAccess();

        $data = new stdClass();
        $data->foo = 'bar';

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'bar');
    }

    public function testWillFailOnRetrieveValueOfUninitializedChildProperty(): void
    {
        $access = new PropertyDataAccess();

        $data = new Barfoo();

        $this->expectException(FieldIsNotInitializedException::class);
        $access->getChild(['test'], $data, 'foo');
    }

    public function testWillFailOnRetrieveValueOfPrivateChildProperty(): void
    {
        $access = new PropertyDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'foo');
    }

    public function testCanSetValueOfChildProperty(): void
    {
        $access = new PropertyDataAccess();

        $data = new stdClass();
        $data->foo = 'bar';

        $access->setChildValue(['test'], $data, 'bar', 'baz');

        self::assertObjectHasProperty('bar', $data);
        self::assertSame('baz', $data->bar);
    }

    public function testWillFailOnSetValueOfChildPropertyOfInvalidType(): void
    {
        $access = new PropertyDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->setChildValue(['test'], $data, 'foo', 'bar');
    }

    public function testWillFailOnSetValueOfNonExistantChildProperty(): void
    {
        $access = new PropertyDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->setChildValue(['test'], $data, 'foo', 'baz');
    }

    public function testWillFailOnSetValueOfReadonlyChildProperty(): void
    {
        $access = new PropertyDataAccess();

        $data = new ReadonlyFoobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->setChildValue(['test'], $data, 'foo', 'baz');
    }

    public function testCanVerifySupportOfDataTypes(): void
    {
        $access = new PropertyDataAccess();

        self::assertTrue($access->supports(new stdClass()));
        self::assertTrue($access->supports(new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        )));
        self::assertFalse($access->supports([]));
        self::assertFalse($access->supports('foobar'));
    }
}
