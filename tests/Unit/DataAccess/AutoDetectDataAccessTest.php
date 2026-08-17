<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess;

use PhpAnonymizer\Anonymizer\DataAccess\ArrayDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\AutoDetectDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\PropertyDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\ReflectionDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\SetterDataAccess;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Test\Helper\DataAccess\CustomDataAccess;
use PhpAnonymizer\Anonymizer\Test\Helper\DataAccess\NoChildAccess;
use PhpAnonymizer\Anonymizer\Test\Helper\DataAccess\NonNodeValueAccess;
use PhpAnonymizer\Anonymizer\Test\Helper\DataAccess\ThrowingGetChildAccess;
use PhpAnonymizer\Anonymizer\Test\Helper\DataAccess\ThrowingHasChildAccess;
use PhpAnonymizer\Anonymizer\Test\Helper\DataAccess\ThrowingInvalidObjectAccess;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\AutoDetectArrayListOfMapsFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\AutoDetectFieldFilterFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\AutoDetectFieldNamesFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\AutoDetectMergeLeafNodeFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\AutoDetectMergeNodeFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Fixtures\AutoDetectMetaMapFixture;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Foobar;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\MixedFooParent;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\PublicAddress;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\PublicFoobar;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
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

        $data = new AutoDetectArrayListOfMapsFixture();

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

    public function testCanParseArrayContainingScalarList(): void
    {
        $access = $this->getAccess();
        $data = [
            'tags' => ['foo', 'bar'],
        ];

        $tree = $access->parseDataTree($data);

        self::assertTrue($tree->hasChildNode('tags'));
        $tagsNode = $tree->getChildNode('tags');
        self::assertSame(NodeType::LEAF, $tagsNode->nodeType);
        self::assertTrue($tagsNode->isList);
    }

    public function testCanParseArrayContainingObjectValue(): void
    {
        $access = $this->getAccess();
        $data = [
            'item' => (object) ['foo' => 'bar'],
        ];

        $tree = $access->parseDataTree($data);

        self::assertTrue($tree->hasChildNode('item'));
        $itemNode = $tree->getChildNode('item');
        self::assertSame(NodeType::NODE, $itemNode->nodeType);
        self::assertTrue($itemNode->hasChildNode('foo'));
    }

    public function testParseArrayTreeSkipsUnsupportedScalarNodes(): void
    {
        $access = $this->getAccess();
        $data = [
            'name' => 'Jane',
            'age' => 42,
        ];

        $tree = $access->parseDataTree($data);

        self::assertTrue($tree->hasChildNode('name'));
        self::assertFalse($tree->hasChildNode('age'));
    }

    public function testParseDataTreeWillFailOnScalarInput(): void
    {
        $access = $this->getAccess();

        $this->expectException(InvalidObjectTypeException::class);
        $access->parseDataTree('not-an-object');
    }

    public function testParseDataTreeWillFailWhenNoAccessSupportsObject(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new ArrayDataAccess(),
            ],
        );

        $this->expectException(InvalidObjectTypeException::class);
        $access->parseDataTree(new stdClass());
    }

    public function testParseDataTreeWillFailOnMixedArrayList(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new ArrayDataAccess(),
            ],
        );

        $data = [
            'items' => [
                ['name' => 'John'],
                5,
            ],
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->parseDataTree($data);
    }

    public function testParseDataTreeWillFailOnInconsistentArrayListTypes(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new ArrayDataAccess(),
            ],
        );

        $data = [
            'items' => [
                ['name' => 'John'],
                ['Jane'],
            ],
        ];

        $this->expectException(RuntimeException::class);
        $access->parseDataTree($data);
    }

    public function testParseDataTreeCollectsFieldsFromReflectionAndAccessors(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new PropertyDataAccess(),
                new SetterDataAccess(),
                new ReflectionDataAccess(),
            ],
        );

        $data = new AutoDetectFieldNamesFixture();

        $tree = $access->parseDataTree($data);

        self::assertTrue($tree->hasChildNode('hidden'));
        self::assertTrue($tree->hasChildNode('magic'));
        self::assertFalse($tree->hasChildNode('readonlyValue'));
    }

    public function testCollectObjectFieldNamesSkipsNullUninitializedAndAccessorOnly(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new PropertyDataAccess(),
                new SetterDataAccess(),
                new ReflectionDataAccess(),
            ],
        );

        $tree = $access->parseDataTree(new AutoDetectFieldFilterFixture());

        self::assertTrue($tree->hasChildNode('fancy'));
        self::assertFalse($tree->hasChildNode('nullable'));
        self::assertFalse($tree->hasChildNode('unset'));
        self::assertFalse($tree->hasChildNode('readonlyValue'));
        self::assertFalse($tree->hasChildNode('only'));
    }

    public function testParseObjectTreeSkipsChildWhenHasChildThrows(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new ThrowingHasChildAccess(),
            ],
        );

        $tree = $access->parseDataTree((object) ['value' => 'ignore']);

        self::assertFalse($tree->hasChildNode('value'));
    }

    public function testParseObjectTreeSkipsChildWhenGetChildThrows(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new ThrowingGetChildAccess(),
            ],
        );

        $tree = $access->parseDataTree((object) ['value' => 'ignore']);

        self::assertFalse($tree->hasChildNode('value'));
    }

    public function testParseObjectTreeSkipsUnsupportedChildValue(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new NonNodeValueAccess(),
            ],
        );

        $tree = $access->parseDataTree((object) ['value' => 'ignore']);

        self::assertFalse($tree->hasChildNode('value'));
    }

    public function testParseObjectTreeSkipsChildWhenHasChildReturnsFalse(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new NoChildAccess(),
            ],
        );

        $tree = $access->parseDataTree((object) ['value' => 'ignore']);

        self::assertFalse($tree->hasChildNode('value'));
    }

    public function testParseObjectTreeSkipsChildWhenGetChildThrowsInvalidObjectType(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new ThrowingInvalidObjectAccess(),
            ],
        );

        $tree = $access->parseDataTree((object) ['value' => 'ignore']);

        self::assertFalse($tree->hasChildNode('value'));
    }

    public function testParseObjectTreeBuildsArrayChildNodes(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new PropertyDataAccess(),
            ],
        );

        $data = new AutoDetectMetaMapFixture();

        $tree = $access->parseDataTree($data);

        self::assertTrue($tree->hasChildNode('meta'));
        $metaNode = $tree->getChildNode('meta');
        self::assertTrue($metaNode->hasChildNode('foo'));
    }

    public function testParseArrayNodeReturnsNullForUnsupportedType(): void
    {
        $access = $this->getAccess();

        $method = new ReflectionMethod($access, 'parseArrayNode');

        $result = $method->invoke($access, 'value', 123, ['value']);

        self::assertNull($result);
    }

    public function testParseArrayListNodeCreatesScalarListNode(): void
    {
        $access = $this->getAccess();

        $method = new ReflectionMethod($access, 'parseArrayListNode');

        $node = $method->invoke($access, 'tags', ['foo', 'bar'], ['tags']);

        self::assertSame(NodeType::LEAF, $node->nodeType);
        self::assertTrue($node->isList);
    }

    public function testParseArrayObjectNodeBuildsChildNodes(): void
    {
        $access = $this->getAccess();

        $method = new ReflectionMethod($access, 'parseArrayObjectNode');

        $node = $method->invoke(
            $access,
            'item',
            (object) ['name' => 'John'],
            ['item'],
        );

        self::assertSame(NodeType::NODE, $node->nodeType);
        self::assertTrue($node->hasChildNode('name'));
    }

    public function testResolveDataAccessNameFallsBackToAutoDetect(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new CustomDataAccess(),
            ],
        );

        $tree = $access->parseDataTree((object) ['value' => 'foo']);

        self::assertTrue($tree->hasChildNode('value'));
        $node = $tree->getChildNode('value');
        self::assertSame(DataAccess::AUTODETECT->value, $node->dataAccess);
    }

    public function testParseObjectChildNodeReturnsNullForUnsupportedValue(): void
    {
        $access = $this->getAccess();

        $method = new ReflectionMethod($access, 'parseObjectChildNode');

        $node = $method->invoke($access, 'value', 123, 'array', ['value']);

        self::assertNull($node);
    }

    public function testMergeChildNodePromotesLeafToNode(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new PropertyDataAccess(),
                new SetterDataAccess(),
            ],
        );

        $tree = $access->parseDataTree(new AutoDetectMergeLeafNodeFixture());

        $itemNode = $tree->getChildNode('item');
        self::assertSame(NodeType::NODE, $itemNode->nodeType);
        self::assertTrue($itemNode->hasChildNode('name'));
    }

    public function testMergeChildNodeCombinesNodes(): void
    {
        $access = new AutoDetectDataAccess(
            [
                new PropertyDataAccess(),
                new SetterDataAccess(),
            ],
        );

        $tree = $access->parseDataTree(new AutoDetectMergeNodeFixture());

        $dataNode = $tree->getChildNode('data');
        self::assertSame(NodeType::NODE, $dataNode->nodeType);
        self::assertTrue($dataNode->hasChildNode('foo'));
        self::assertTrue($dataNode->hasChildNode('bar'));
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
