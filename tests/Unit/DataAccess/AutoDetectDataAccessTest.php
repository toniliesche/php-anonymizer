<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess;

use PhpAnonymizer\Anonymizer\DataAccess\ArrayDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\AutoDetectDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\PropertyDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\ReflectionDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\SetterDataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Foobar;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\MixedFooParent;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\PublicAddress;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\PublicFoobar;
use PHPUnit\Framework\TestCase;
use stdClass;

final class AutoDetectDataAccessTest extends TestCase
{
    public function testWillFailOnInitializationWithInvalidDefinition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutoDetectDataAccess(
            /** @phpstan-ignore-next-line  */
            [
                'hello',
            ],
        );
    }

    public function testCanCheckIfChildPropertyExistsOnArray(): void
    {
        $access = $this->getAccess();
        $data = [
            'foo' => 'bar',
        ];

        self::assertTrue($access->hasChild(['test'], $data, 'foo'));
        self::assertFalse($access->hasChild(['test'], $data, 'bar'));
    }

    public function testCanRetrieveValueOfChildPropertyOnArray(): void
    {
        $access = $this->getAccess();
        $data = [
            'foo' => 'bar',
        ];

        self::assertSame('bar', $access->getChild(['test'], $data, 'foo'));
    }

    public function testWillFailOnRetrieveValueOfNonExistantChildPropertyOnArray(): void
    {
        $access = $this->getAccess();
        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'bar');
    }

    public function testCanSetValueOfChildPropertyOnArray(): void
    {
        $access = $this->getAccess();
        $data = [
            'foo' => 'bar',
        ];

        $access->setChildValue(['test'], $data, 'foo', 'baz');
        self::assertSame('baz', $data['foo']);
    }

    public function testCanCheckIfChildPropertyExistsOnObjectViaProperty(): void
    {
        $access = $this->getAccess();

        $data = new stdClass();
        $data->foo = 'bar';

        self::assertTrue($access->hasChild(['test'], $data, 'foo'));
        self::assertFalse($access->hasChild(['test'], $data, 'bar'));
    }

    public function testCanRetrieveValueOfChildPropertyOnObjectViaProperty(): void
    {
        $access = $this->getAccess();

        $data = new stdClass();
        $data->foo = 'bar';

        self::assertSame('bar', $access->getChild(['test'], $data, 'foo'));
    }

    public function testWillFailOnRetrieveValueOfNonExistantChildPropertyOnObjectViaProperty(): void
    {
        $access = $this->getAccess();

        $data = new stdClass();
        $data->foo = 'bar';

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'bar');
    }

    public function testCanSetValueOfChildPropertyOnObjectViaProperty(): void
    {
        $access = $this->getAccess();

        $data = new stdClass();
        $data->foo = 'bar';

        $access->setChildValue(['test'], $data, 'foo', 'baz');
        self::assertSame('baz', $data->foo);
    }

    public function testCanCheckIfChildPropertyExistsOnObjectViaGetter(): void
    {
        $access = $this->getAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        self::assertTrue($access->hasChild(['test'], $data, 'baz'));
        self::assertFalse($access->hasChild(['test'], $data, 'foo'));
        self::assertFalse($access->hasChild(['test'], $data, 'bar'));
    }

    public function testCanRetrieveValueOfChildPropertyOnObjectViaGetter(): void
    {
        $access = $this->getAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        self::assertSame('baz', $access->getChild(['test'], $data, 'baz'));
    }

    public function testWillFailOnRetrieveValueOfNonExistantChildPropertyOnObjectViaGetter(): void
    {
        $access = $this->getAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'foo2');
    }

    public function testWillFailOnRetrieveValueOfWriteOnlyChildPropertyOnObjectViaGetter(): void
    {
        $access = $this->getAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'bar');
    }

    public function testCanSetValueOfChildPropertyOnObjectViaSetter(): void
    {
        $access = $this->getAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $access->setChildValue(['test'], $data, 'baz', 'new baz');
        self::assertSame('new baz', $data->getBaz());
    }

    public function testWillFailOnSetValueOfReadonlyChildPropertyOnObjectViaSetter(): void
    {
        $access = $this->getAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->expectException(FieldDoesNotExistException::class);
        $access->setChildValue(['test'], $data, 'foo', 'new foo');
    }

    public function testWillFailOnSetValueOfNonExistantChildPropertyOnObjectViaSetter(): void
    {
        $access = $this->getAccess();

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
        $access = $this->getAccess();

        self::assertTrue($access->supports([]));
        self::assertTrue($access->supports(new stdClass()));
        self::assertTrue($access->supports(
            new Foobar(
                foo: 'foo',
                bar: 'bar',
                baz: 'baz',
            ),
        ));
        self::assertFalse($access->supports('foobar'));
    }

    public function testCanParseDataTreeWithMixedAccessStrategies(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new ArrayDataAccess(),
                new PropertyDataAccess(),
                new SetterDataAccess(),
                new ReflectionDataAccess(),
            ],
        );

        $data = new MixedFooParent(
            publicFoo: new PublicFoobar(
                foo: 'foo',
                bar: 'bar',
                baz: 'baz',
            ),
            privateFoo: new Foobar(
                foo: 'foo',
                bar: 'bar',
                baz: 'baz',
            ),
        );

        $tree = $access->parseDataTree($data);

        self::assertTrue($tree->hasChildNode('publicFoo'));
        self::assertTrue($tree->hasChildNode('privateFoo'));

        $publicNode = $tree->getChildNode('publicFoo');
        self::assertSame(NodeType::NODE, $publicNode->nodeType);
        self::assertTrue($publicNode->hasChildNode('bar'));
        self::assertTrue($publicNode->hasChildNode('baz'));

        $privateNode = $tree->getChildNode('privateFoo');
        self::assertSame(NodeType::NODE, $privateNode->nodeType);
        self::assertTrue($privateNode->hasChildNode('bar'));
        self::assertTrue($privateNode->hasChildNode('baz'));
    }

    public function testCanParseObjectContainingArrayListOfMaps(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new ArrayDataAccess(),
                new PropertyDataAccess(),
                new SetterDataAccess(),
                new ReflectionDataAccess(),
            ],
        );

        $data = new class () {
            /** @var array<int, array<string, string>> */
            public array $items = [
                ['name' => 'John', 'city' => 'New York'],
                ['name' => 'Jane', 'city' => 'Los Angeles'],
            ];
        };

        $tree = $access->parseDataTree($data);

        self::assertTrue($tree->hasChildNode('items'));
        $itemsNode = $tree->getChildNode('items');
        self::assertSame(NodeType::NODE, $itemsNode->nodeType);
        self::assertTrue($itemsNode->isList);
        self::assertTrue($itemsNode->hasChildNode('name'));
        self::assertTrue($itemsNode->hasChildNode('city'));
    }

    public function testCanParseArrayContainingObjectList(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new ArrayDataAccess(),
                new PropertyDataAccess(),
                new SetterDataAccess(),
                new ReflectionDataAccess(),
            ],
        );

        $data = [
            'items' => [
                new PublicFoobar(foo: 'foo', bar: 'bar', baz: 'baz'),
                new PublicAddress(name: 'John Doe', city: 'New York'),
            ],
        ];

        $tree = $access->parseDataTree($data);

        self::assertTrue($tree->hasChildNode('items'));
        $itemsNode = $tree->getChildNode('items');
        self::assertSame(NodeType::NODE, $itemsNode->nodeType);
        self::assertTrue($itemsNode->isList);
        self::assertTrue($itemsNode->hasChildNode('bar'));
        self::assertTrue($itemsNode->hasChildNode('baz'));
        self::assertTrue($itemsNode->hasChildNode('name'));
        self::assertTrue($itemsNode->hasChildNode('city'));
    }

    private function getAccess(): AutoDetectDataAccess
    {
        return new AutoDetectDataAccess(
            [
                new ArrayDataAccess(),
                new PropertyDataAccess(),
                new SetterDataAccess(),
            ],
        );
    }
}
