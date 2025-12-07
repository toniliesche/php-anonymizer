<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Mapper\Node\Factory;

use PhpAnonymizer\Anonymizer\Enum\DataGenerationProvider;
use PhpAnonymizer\Anonymizer\Enum\NodeMapper;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeMapperDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\NodeMapperExistsException;
use PhpAnonymizer\Anonymizer\Exception\UnknownNodeMapperException;
use PhpAnonymizer\Anonymizer\Mapper\Node\DefaultNodeMapper;
use PhpAnonymizer\Anonymizer\Mapper\Node\Factory\DefaultNodeMapperFactory;
use PhpAnonymizer\Anonymizer\Mapper\Node\NodeMapperInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DefaultNodeMapperFactoryTest extends TestCase
{
    public function testCanProvideDefaultDataGenerationProvider(): void
    {
        $factory = new DefaultNodeMapperFactory();
        $mapper = $factory->getNodeMapper(NodeMapper::DEFAULT->value);

        self::assertInstanceOf(DefaultNodeMapper::class, $mapper);
    }

    public function testCanProvideNull(): void
    {
        $factory = new DefaultNodeMapperFactory();
        $mapper = $factory->getNodeMapper(null);

        self::assertNull($mapper);
    }

    public function testWillFailOnProvideUnknownDataGenerationProvider(): void
    {
        $factory = new DefaultNodeMapperFactory();

        $this->expectException(UnknownNodeMapperException::class);
        $factory->getNodeMapper('unknown');
    }

    public function testCanRegisterAndProvideCustomDataGenerationProviderWithCallable(): void
    {
        $callable = fn () => $this->createMock(NodeMapperInterface::class);
        $factory = new DefaultNodeMapperFactory();
        $factory->registerCustomNodeMapper('custom', $callable);

        $provider = $factory->getNodeMapper('custom');
        self::assertInstanceOf(NodeMapperInterface::class, $provider);
    }

    public function testCanRegisterAndProvideCustomDataGenerationProviderWithInstance(): void
    {
        $provider = $this->createMock(NodeMapperInterface::class);
        $factory = new DefaultNodeMapperFactory();
        $factory->registerCustomNodeMapper('custom', $provider);

        $generatedProvider = $factory->getNodeMapper('custom');
        self::assertSame($provider, $generatedProvider);
    }

    public function testWillFailOnRegisteringCustomDataGenerationProviderOnNameConflict(): void
    {
        $callable = fn () => $this->createMock(NodeMapperInterface::class);
        $factory = new DefaultNodeMapperFactory();

        $this->expectException(NodeMapperExistsException::class);
        $factory->registerCustomNodeMapper(DataGenerationProvider::DEFAULT->value, $callable);
    }

    public function testWillFailOnRegisteringCustomDataGenerationProviderWithNonCallableDefinition(): void
    {
        $factory = new DefaultNodeMapperFactory();

        $this->expectException(InvalidNodeMapperDefinitionException::class);

        /** @phpstan-ignore-next-line */
        $factory->registerCustomNodeMapper('custom', 'not a callable');
    }

    public function testWillFailOnRegisteringCustomDataGenerationProviderWhenNotImplementingInterface(): void
    {
        $callable = fn () => new stdClass();
        $factory = new DefaultNodeMapperFactory();

        $this->expectException(InvalidNodeMapperDefinitionException::class);
        $factory->registerCustomNodeMapper('custom', $callable);
        $factory->getNodeMapper('custom');
    }
}
