<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataAccess\Provider\Factory;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DefaultDataAccessProviderFactory;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\DataAccessProviderExistsException;
use PhpAnonymizer\Anonymizer\Exception\InvalidDataAccessProviderDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\UnknownDataAccessProviderException;
use PHPUnit\Framework\TestCase;
use stdClass;

class DefaultDataAccessProviderFactoryTest extends TestCase
{
    public function testCanProvideDefaultDataAccessProvider(): void
    {
        $factory = new DefaultDataAccessProviderFactory();
        $provider = $factory->getDataAccessProvider(DataAccess::DEFAULT->value);

        $this->assertInstanceOf(DefaultDataAccessProvider::class, $provider);
    }

    public function testCanProvideNull(): void
    {
        $factory = new DefaultDataAccessProviderFactory();
        $provider = $factory->getDataAccessProvider(null);

        $this->assertNull($provider);
    }

    public function testWillFailOnProvideUnknownDataAccessProvider(): void
    {
        $factory = new DefaultDataAccessProviderFactory();

        $this->expectException(UnknownDataAccessProviderException::class);
        $factory->getDataAccessProvider('unknown');
    }

    public function testCanRegisterAndProvideCustomDataAccessProviderWithCallable(): void
    {
        $callable = fn () => $this->createMock(DataAccessProviderInterface::class);
        $factory = new DefaultDataAccessProviderFactory();
        $factory->registerCustomDataAccessProvider('custom', $callable);

        $provider = $factory->getDataAccessProvider('custom');
        $this->assertInstanceOf(DataAccessProviderInterface::class, $provider);
    }

    public function testCanRegisterAndProvideCustomDataAccessProviderWithInstance(): void
    {
        $provider = $this->createMock(DataAccessProviderInterface::class);
        $factory = new DefaultDataAccessProviderFactory();
        $factory->registerCustomDataAccessProvider('custom', $provider);

        $resolvedProvider = $factory->getDataAccessProvider('custom');
        $this->assertInstanceOf(DataAccessProviderInterface::class, $resolvedProvider);
    }

    public function testWillFailOnRegisteringCustomDataAccessProviderOnNameConflict(): void
    {
        $callable = fn () => $this->createMock(DataAccessProviderInterface::class);
        $factory = new DefaultDataAccessProviderFactory();

        $this->expectException(DataAccessProviderExistsException::class);
        $factory->registerCustomDataAccessProvider(DataAccess::DEFAULT->value, $callable);
    }

    public function testWillFailOnRegisteringCustomDataAccessProviderWithNonCallableDefinition(): void
    {
        $factory = new DefaultDataAccessProviderFactory();

        $this->expectException(InvalidDataAccessProviderDefinitionException::class);

        /** @phpstan-ignore-next-line */
        $factory->registerCustomDataAccessProvider('custom', 'not a callable');
    }

    public function testWillFailOnRegisteringCustomDataAccessProviderWhenNotImplementingInterface(): void
    {
        $callable = fn () => new stdClass();
        $factory = new DefaultDataAccessProviderFactory();

        $this->expectException(InvalidDataAccessProviderDefinitionException::class);
        $factory->registerCustomDataAccessProvider('custom', $callable);
        $factory->getDataAccessProvider('custom');
    }
}
