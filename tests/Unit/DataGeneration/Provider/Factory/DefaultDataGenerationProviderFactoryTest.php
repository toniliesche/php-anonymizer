<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataGeneration\Provider\Factory;

use PHPUnit\Framework\TestCase;
use stdClass;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DefaultDataGenerationProviderFactory;
use PhpAnonymizer\Anonymizer\Enum\DataGenerationProvider;
use PhpAnonymizer\Anonymizer\Exception\DataGenerationProviderExistsException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\InvalidDataGenerationProviderDefinitionException;

class DefaultDataGenerationProviderFactoryTest extends TestCase
{
    public function testCanProvideDefaultDataGenerationProvider(): void
    {
        $factory = new DefaultDataGenerationProviderFactory();
        $provider = $factory->getDataGenerationProvider(DataGenerationProvider::DEFAULT->value);

        $this->assertInstanceOf(DefaultDataGeneratorProvider::class, $provider);
    }

    public function testCanProvideNull(): void
    {
        $factory = new DefaultDataGenerationProviderFactory();
        $provider = $factory->getDataGenerationProvider(null);

        $this->assertNull($provider);
    }

    public function testWillFailOnProvideUnknownDataGenerationProvider(): void
    {
        $factory = new DefaultDataGenerationProviderFactory();

        $this->expectException(InvalidArgumentException::class);
        $factory->getDataGenerationProvider('unknown');
    }

    public function testCanRegisterAndProvideCustomDataGenerationProviderWithCallable(): void
    {
        $callable = fn () => $this->createMock(DataGenerationProviderInterface::class);
        $factory = new DefaultDataGenerationProviderFactory();
        $factory->registerCustomDataGenerationProvider('custom', $callable);

        $provider = $factory->getDataGenerationProvider('custom');
        $this->assertInstanceOf(DataGenerationProviderInterface::class, $provider);
    }

    public function testCanRegisterAndProvideCustomDataGenerationProviderWithInstance(): void
    {
        $provider = $this->createMock(DataGenerationProviderInterface::class);
        $factory = new DefaultDataGenerationProviderFactory();
        $factory->registerCustomDataGenerationProvider('custom', $provider);

        $provider = $factory->getDataGenerationProvider('custom');
        $this->assertSame($provider, $provider);
    }

    public function testWillFailOnRegisteringCustomDataGenerationProviderOnNameConflict(): void
    {
        $callable = fn () => $this->createMock(DataGenerationProviderInterface::class);
        $factory = new DefaultDataGenerationProviderFactory();

        $this->expectException(DataGenerationProviderExistsException::class);
        $factory->registerCustomDataGenerationProvider(DataGenerationProvider::DEFAULT->value, $callable);
    }

    public function testWillFailOnRegisteringCustomDataGenerationProviderWithNonCallableDefinition(): void
    {
        $factory = new DefaultDataGenerationProviderFactory();

        $this->expectException(InvalidDataGenerationProviderDefinitionException::class);

        /** @phpstan-ignore-next-line */
        $factory->registerCustomDataGenerationProvider('custom', 'not a callable');
    }

    public function testWillFailOnRegisteringCustomDataGenerationProviderWhenNotImplementingInterface(): void
    {
        $callable = fn () => new stdClass();
        $factory = new DefaultDataGenerationProviderFactory();

        $this->expectException(InvalidDataGenerationProviderDefinitionException::class);
        $factory->registerCustomDataGenerationProvider('custom', $callable);
        $factory->getDataGenerationProvider('custom');
    }
}
