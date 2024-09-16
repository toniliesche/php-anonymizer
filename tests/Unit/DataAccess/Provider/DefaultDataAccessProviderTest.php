<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess\Provider;

use PHPUnit\Framework\TestCase;
use PhpAnonymizer\Anonymizer\DataAccess\ArrayDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\AutoDetectDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;
use PhpAnonymizer\Anonymizer\DataAccess\PropertyDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataAccess\ReflectionDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\SetterDataAccess;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\DataAccessExistsException;
use PhpAnonymizer\Anonymizer\Exception\UnknownDataAccessException;

class DefaultDataAccessProviderTest extends TestCase
{
    public function testCanVerifySupportOfDataAccessTypes(): void
    {
        $provider = new DefaultDataAccessProvider();

        $this->assertTrue($provider->supports(DataAccess::ARRAY->value));
        $this->assertTrue($provider->supports(DataAccess::AUTODETECT->value));
        $this->assertTrue($provider->supports(DataAccess::PROPERTY->value));
        $this->assertTrue($provider->supports(DataAccess::SETTER->value));
        $this->assertTrue($provider->supports(DataAccess::REFLECTION->value));
        $this->assertFalse($provider->supports(DataAccess::DEFAULT->value));
        $this->assertFalse($provider->supports('foobar'));
    }

    public function testCanProvideAllDefaultDataAccesses(): void
    {
        $provider = new DefaultDataAccessProvider();

        $this->assertInstanceOf(ArrayDataAccess::class, $provider->provideDataAccess(DataAccess::ARRAY->value));
        $this->assertInstanceOf(AutoDetectDataAccess::class, $provider->provideDataAccess(DataAccess::AUTODETECT->value));
        $this->assertInstanceOf(PropertyDataAccess::class, $provider->provideDataAccess(DataAccess::PROPERTY->value));
        $this->assertInstanceOf(ReflectionDataAccess::class, $provider->provideDataAccess(DataAccess::REFLECTION->value));
        $this->assertInstanceOf(SetterDataAccess::class, $provider->provideDataAccess(DataAccess::SETTER->value));
    }

    public function testWillFailOnProvidingDefaultDataAccess(): void
    {
        $provider = new DefaultDataAccessProvider();

        $this->expectException(UnknownDataAccessException::class);
        $provider->provideDataAccess(DataAccess::DEFAULT->value);
    }

    public function testWillFailOnProvidingUnknownDataAccess(): void
    {
        $provider = new DefaultDataAccessProvider();

        $this->expectException(UnknownDataAccessException::class);
        $provider->provideDataAccess('foobar');
    }

    public function testCanRegisterAndProvideCustomDataAccess(): void
    {
        $provider = new DefaultDataAccessProvider();

        $dataAccess = $this->createMock(DataAccessInterface::class);
        $provider->registerCustomDataAccess('foobar', $dataAccess);

        $this->assertTrue($provider->supports('foobar'));

        $resolvedDataAccess = $provider->provideDataAccess('foobar');
        $this->assertSame($dataAccess, $resolvedDataAccess);

        $this->expectException(DataAccessExistsException::class);
        $provider->registerCustomDataAccess('foobar', $dataAccess);
    }
}
