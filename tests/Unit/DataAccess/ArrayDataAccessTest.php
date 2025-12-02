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

        $this->assertTrue($access->hasChild(['test'], $data, 'foo'));
        $this->assertFalse($access->hasChild(['test'], $data, 'bar'));
        $this->assertFalse($access->hasChild(['test'], $data, 'FOO'));
    }

    public function testWillFailOnCheckForChildPropertyOfNonArray(): void
    {
        $access = new ArrayDataAccess();

        $data = new stdClass();

        $this->expectException(InvalidObjectTypeException::class);

        /** @phpstan-ignore-next-line */
        $access->hasChild(['test'], $data, 'foo');
    }

    public function testCanRetrieveValueOfChildProperty(): void
    {
        $access = new ArrayDataAccess();

        $data = [
            'foo' => 'bar',
        ];

        $this->assertSame('bar', $access->getChild(['test'], $data, 'foo'));
    }

    public function testWillFailOnRetrieveValueOfChildPropertyOnNonArray(): void
    {
        $access = new ArrayDataAccess();

        $data = new stdClass();

        $this->expectException(InvalidObjectTypeException::class);

        /** @phpstan-ignore-next-line */
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
        $this->assertSame('baz', $data['foo']);
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
        // @phpstan-ignore-next-line
        $access->setChildValue(['test'], $data, 'foo', '123');
    }

    public function testCanVerifySupportOfDataTypes(): void
    {
        $access = new ArrayDataAccess();

        $this->assertTrue($access->supports([]));
        $this->assertTrue($access->supports(['foo' => 'bar']));
        $this->assertFalse($access->supports(new ArrayObject()));
        $this->assertFalse($access->supports('foo'));
    }
}
