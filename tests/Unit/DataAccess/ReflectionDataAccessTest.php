<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess;

use PhpAnonymizer\Anonymizer\DataAccess\ReflectionDataAccess;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\FieldIsNotInitializedException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Barfoo;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Foobar;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\ReadonlyFoobar;
use PHPUnit\Framework\TestCase;
use stdClass;

class ReflectionDataAccessTest extends TestCase
{
    public function testCanCheckIfChildPropertyExists(): void
    {
        $access = new ReflectionDataAccess();

        $data = (new class () {
            public string $foo = 'bar';
        });

        self::assertTrue($access->hasChild(['test'], $data, 'foo'));
        self::assertFalse($access->hasChild(['test'], $data, 'bar'));
        self::assertFalse($access->hasChild(['test'], $data, 'FOO'));
    }

    public function testCanCheckIfUnitializedChildPropertyDoesNotExist(): void
    {
        $access = new ReflectionDataAccess();

        $data = new Barfoo();
        self::assertFalse($access->hasChild(['test'], $data, 'foo'));
    }

    public function testWillFailOnCheckForChildPropertyOfNonObject(): void
    {
        $access = new ReflectionDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->hasChild(['test'], $data, 'foo');
    }

    public function testCanRetrieveValueOfChildProperty(): void
    {
        $access = new ReflectionDataAccess();

        $data = (new class () {
            public string $foo = 'bar';
        });

        self::assertSame('bar', $access->getChild(['test'], $data, 'foo'));
    }

    public function testWillFailOnRetrieveValueOfChildPropertyOnNonObject(): void
    {
        $access = new ReflectionDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->getChild(['test'], $data, 'foo');
    }

    public function testWillFailOnRetrieveValueOfNonExistantChildProperty(): void
    {
        $access = new ReflectionDataAccess();

        $data = (new class () {
            public string $foo = 'bar';
        });

        $this->expectException(FieldDoesNotExistException::class);
        $access->getChild(['test'], $data, 'bar');
    }

    public function testWillFailOnRetrieveValueOfUninitializedChildProperty(): void
    {
        $access = new ReflectionDataAccess();

        $data = new Barfoo();

        $this->expectException(FieldIsNotInitializedException::class);
        $access->getChild(['test'], $data, 'foo');
    }

    public function testCanRetrieveValueOfPrivateChildProperty(): void
    {
        $access = new ReflectionDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $value = $access->getChild(['test'], $data, 'foo');
        self::assertSame('foo', $value);
    }

    public function testCanSetValueOfChildProperty(): void
    {
        $access = new ReflectionDataAccess();

        $data = (new class () {
            public string $bar = 'bar';
        });

        $access->setChildValue(['test'], $data, 'bar', 'baz');

        self::assertObjectHasProperty('bar', $data);
        self::assertSame('baz', $data->bar);
    }

    public function testWillFailOnSetValueOfChildPropertyOfInvalidType(): void
    {
        $access = new ReflectionDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->expectException(InvalidObjectTypeException::class);
        $access->setChildValue(['test'], $data, 'foo', 'bar');
    }

    public function testWillFailOnSetValueOfNonExistantChildProperty(): void
    {
        $access = new ReflectionDataAccess();

        $data = (new class () {
            public string $bar = 'bar';
        });

        $this->expectException(FieldDoesNotExistException::class);
        $access->setChildValue(['test'], $data, 'foo', 'baz');
    }

    public function testWillFailOnSetValueOfReadonlyChildProperty(): void
    {
        $access = new ReflectionDataAccess();

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
        $access = new ReflectionDataAccess();

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
