<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess\Provider;

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
use PHPUnit\Framework\TestCase;

final class DefaultDataAccessProviderTest extends TestCase
{
    public function testCanVerifySupportOfDataAccessTypes(): void
    {
        $provider = new DefaultDataAccessProvider();

        self::assertTrue($provider->supports(DataAccess::ARRAY->value));
        self::assertTrue($provider->supports(DataAccess::AUTODETECT->value));
        self::assertTrue($provider->supports(DataAccess::PROPERTY->value));
        self::assertTrue($provider->supports(DataAccess::SETTER->value));
        self::assertTrue($provider->supports(DataAccess::REFLECTION->value));
        self::assertFalse($provider->supports(DataAccess::DEFAULT->value));
        self::assertFalse($provider->supports('foobar'));
    }

    public function testCanProvideAllDefaultDataAccesses(): void
    {
        $provider = new DefaultDataAccessProvider();

        self::assertInstanceOf(ArrayDataAccess::class, $provider->provideDataAccess(DataAccess::ARRAY->value));
        self::assertInstanceOf(AutoDetectDataAccess::class, $provider->provideDataAccess(DataAccess::AUTODETECT->value));
        self::assertInstanceOf(PropertyDataAccess::class, $provider->provideDataAccess(DataAccess::PROPERTY->value));
        self::assertInstanceOf(ReflectionDataAccess::class, $provider->provideDataAccess(DataAccess::REFLECTION->value));
        self::assertInstanceOf(SetterDataAccess::class, $provider->provideDataAccess(DataAccess::SETTER->value));
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

        self::assertTrue($provider->supports('foobar'));

        $resolvedDataAccess = $provider->provideDataAccess('foobar');
        self::assertSame($dataAccess, $resolvedDataAccess);

        $this->expectException(DataAccessExistsException::class);
        $provider->registerCustomDataAccess('foobar', $dataAccess);
    }
}
