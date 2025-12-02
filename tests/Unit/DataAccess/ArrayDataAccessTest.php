<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess;

use ArrayObject;
use PhpAnonymizer\Anonymizer\DataAccess\ArrayDataAccess;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ArrayDataAccessTest extends TestCase
{
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
