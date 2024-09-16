<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess;

use PHPUnit\Framework\TestCase;
use stdClass;
use PhpAnonymizer\Anonymizer\DataAccess\PropertyDataAccess;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Foobar;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\ReadonlyFoobar;

class PropertyDataAccessTest extends TestCase
{
    public function testCanCheckIfChildPropertyExists(): void
    {
        $access = new PropertyDataAccess();

        $data = new stdClass();
        $data->foo = 'bar';

        $this->assertTrue($access->hasChild(['test'], $data, 'foo'));
        $this->assertFalse($access->hasChild(['test'], $data, 'bar'));
        $this->assertFalse($access->hasChild(['test'], $data, 'FOO'));
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

        $this->assertSame('bar', $access->getChild(['test'], $data, 'foo'));
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

        $this->assertObjectHasProperty('bar', $data);
        $this->assertSame('baz', $data->bar);
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

        $this->assertTrue($access->supports(new stdClass()));
        $this->assertTrue($access->supports(new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        )));
        $this->assertFalse($access->supports([]));
        $this->assertFalse($access->supports('foobar'));
    }
}
