<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess;

use PHPUnit\Framework\TestCase;
use stdClass;
use PhpAnonymizer\Anonymizer\DataAccess\SetterDataAccess;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Foobar;

class SetterDataAccessTest extends TestCase
{
    public function testCanCheckIfChildPropertyExists(): void
    {
        $access = new SetterDataAccess();

        $data = new Foobar(
            foo: 'foo',
            bar: 'bar',
            baz: 'baz',
        );

        $this->assertTrue($access->hasChild(['test'], $data, 'baz'));
        $this->assertFalse($access->hasChild(['test'], $data, 'foo'));
        $this->assertFalse($access->hasChild(['test'], $data, 'bar'));
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

        $this->assertSame('baz', $access->getChild(['test'], $data, 'baz'));
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
        $this->assertSame('new baz', $data->getBaz());
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
