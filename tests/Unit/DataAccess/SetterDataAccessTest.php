<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess;

use PhpAnonymizer\Anonymizer\DataAccess\SetterDataAccess;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\FieldIsNotInitializedException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\SetterMapArrayFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\SetterMixedListFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\SetterScalarFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\SetterThrowingGetterFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Address;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Barfoo;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Foobar;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\FooParent;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

final class SetterDataAccessTest extends TestCase
{
    public function testCanParseSimpleDataTree(): void
    {
        $access = new SetterDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $tree = $access->parseDataTree($data);

        self::assertCount(1, $tree->childNodes);
        $node = $tree->childNodes[0];

        self::assertSame('baz', $node->name);
        self::assertSame(NodeType::LEAF, $node->nodeType);
        self::assertSame(DataAccess::SETTER->value, $node->dataAccess);
        self::assertFalse($node->isList);
        self::assertEmpty($node->childNodes);
    }

    public function testCanParseDataTreeWithChildren(): void
    {
        $access = new SetterDataAccess();

        $data = new FooParent(
            foobar: new Foobar(
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
        self::assertSame(DataAccess::SETTER->value, $foobarNode->dataAccess);
        self::assertFalse($foobarNode->isList);
        self::assertNotEmpty($foobarNode->childNodes);

        $arrayNode = $tree->childNodes[1];

        self::assertSame('array', $arrayNode->name);
        self::assertSame(NodeType::LEAF, $arrayNode->nodeType);
        self::assertSame(DataAccess::SETTER->value, $arrayNode->dataAccess);
        self::assertTrue($arrayNode->isList);
        self::assertEmpty($arrayNode->childNodes);
    }

    public function testCanParseDataTreeWithObjectArray(): void
    {
        $access = new SetterDataAccess();

        $data = new FooParent(
            foobar: new Foobar(
                foo: 'foo',
                bar: 'bar',
                baz: 'baz',
            ),
            array: [
                new Foobar(
                    foo: 'foo',
                    bar: 'bar',
                    baz: 'baz',
                ),
                new Address(
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
        self::assertSame(DataAccess::SETTER->value, $foobarNode->dataAccess);
        self::assertFalse($foobarNode->isList);
        self::assertNotEmpty($foobarNode->childNodes);

        $arrayNode = $tree->childNodes[1];

        self::assertSame('array', $arrayNode->name);
        self::assertSame(NodeType::NODE, $arrayNode->nodeType);
        self::assertSame(DataAccess::SETTER->value, $arrayNode->dataAccess);
        self::assertTrue($arrayNode->isList);
        self::assertCount(3, $arrayNode->childNodes);

        foreach ($arrayNode->childNodes as $childNode) {
            self::assertSame(NodeType::LEAF, $childNode->nodeType);
            self::assertSame(DataAccess::SETTER->value, $childNode->dataAccess);
            self::assertFalse($childNode->isList);
            self::assertEmpty($childNode->childNodes);
        }
    }

    public function testWillFailOnParseDataTreeWithNonObject(): void
    {
        $access = new SetterDataAccess();

        $this->expectException(InvalidObjectTypeException::class);
        $access->parseDataTree(['foo' => 'bar']);
    }

    public function testParseNodeReturnsNullForUnsupportedType(): void
    {
        $access = new SetterDataAccess();

        $reflection = new ReflectionMethod($access, 'parseNode');

        $result = $reflection->invoke(
            $access,
            'value',
            'getValue',
            new SetterScalarFixture(),
            ['value'],
        );

        self::assertNull($result);
    }

    public function testWillFailOnParseDataTreeWithNonListArray(): void
    {
        $access = new SetterDataAccess();

        $this->expectException(InvalidObjectTypeException::class);
        $access->parseDataTree(new SetterMapArrayFixture());
    }

    public function testWillFailOnParseDataTreeWithListOfNonStrings(): void
    {
        $access = new SetterDataAccess();

        $this->expectException(InvalidObjectTypeException::class);
        $access->parseDataTree(new SetterMixedListFixture());
    }

    public function testCanCheckIfChildPropertyExists(): void
    {
        $access = new SetterDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        self::assertTrue($access->hasChild(['test'], $data, 'baz'));
        self::assertFalse($access->hasChild(['test'], $data, 'foo'));
        self::assertFalse($access->hasChild(['test'], $data, 'bar'));
    }

    public function testHasChildReturnsFalseWhenGetterThrows(): void
    {
        $access = new SetterDataAccess();

        self::assertFalse($access->hasChild(['test'], new SetterThrowingGetterFixture(), 'value'));
    }

    public function testCanCheckIfUnitializedChildPropertyDoesNotExist(): void
    {
        $access = new SetterDataAccess();

        $data = new Barfoo();
        self::assertFalse($access->hasChild(['test'], $data, 'foo'));
    }

    public function testWillFailOnCheckForChildPropertyOfNonObject(): void
    {
        $access = new SetterDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->hasChild(['test'], $data, 'foo');
    }

    public function testCanRetrieveValueOfChildProperty(): void
    {
        $access = new SetterDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        self::assertSame('baz', $access->getChild(['test'], $data, 'baz'));
    }

    public function testWillFailOnRetrieveValueOfChildPropertyOnNonObject(): void
    {
        $access = new SetterDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->getChild(['test'], $data, 'foo');
    }

    public function testWillFailOnRetrieveValueOfNonExistantChildProperty(): void
    {
        $access = new SetterDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'foo2');
    }

    public function testWillFailOnRetrieveValueOfUninitializedChildProperty(): void
    {
        $access = new SetterDataAccess();

        $data = new Barfoo();

        $this->expectException(FieldIsNotInitializedException::class);
        $access->getChild(['test'], $data, 'foo');
    }

    public function testWillFailOnRetrieveValueOfWriteonlyChildProperty(): void
    {
        $access = new SetterDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'bar');
    }

    public function testCanSetValueOfChildProperty(): void
    {
        $access = new SetterDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $access->setChildValue(['test'], $data, 'baz', 'new baz');
        self::assertSame('new baz', $data->getBaz());
    }

    public function testWillFailOnSetValueOfChildPropertyOfInvalidType(): void
    {
        $access = new SetterDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->setChildValue(['test'], $data, 'foo', 'bar');
    }

    public function testWillFailOnSetValueOfReadonlyChildProperty(): void
    {
        $access = new SetterDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->setChildValue(['test'], $data, 'foo', 'new foo');
    }

    public function testWillFailOnSetValueOfNonExistantChildProperty(): void
    {
        $access = new SetterDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->setChildValue(['test'], $data, 'foo2', 'new foo');
    }

    public function testCanVerifySupportOfDataTypes(): void
    {
        $access = new SetterDataAccess();

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
