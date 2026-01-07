<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DependencyInjection\Compiler;

use PhpAnonymizer\Anonymizer\DependencyInjection\Compiler\SerializerCompilerPass;
use PhpAnonymizer\Anonymizer\Exception\AnonymizerConfigException;
use PhpAnonymizer\Anonymizer\Exception\ContainerException;
use PhpAnonymizer\Anonymizer\Exception\RuleDefinitionException;
use PhpAnonymizer\Anonymizer\Serializer\SerializerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class SerializerCompilerPassTest extends TestCase
{
    public function testProcessSkipsWhenOptionsMissing(): void
    {
        $container = new ContainerBuilder();

        (new SerializerCompilerPass())->process($container);

        self::assertFalse($container->hasAlias('anonymizer.serializer'));
    }

    public function testProcessSkipsWhenDisabled(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('anonymizer.serializer_options', [
            'enabled' => false,
            'mode' => 'autowire',
            'custom_serializer' => null,
        ]);

        (new SerializerCompilerPass())->process($container);

        self::assertFalse($container->hasAlias('anonymizer.serializer'));
    }

    public function testUsesAutowiringWhenEnabled(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('anonymizer.serializer_options', [
            'enabled' => true,
            'mode' => 'autowire',
            'custom_serializer' => null,
        ]);
        $container->register(SerializerInterface::class);

        (new SerializerCompilerPass())->process($container);

        self::assertTrue($container->hasAlias('anonymizer.serializer'));
        $alias = $container->getAlias('anonymizer.serializer');
        self::assertSame(SerializerInterface::class, (string) $alias);
        self::assertTrue($alias->isPublic());
    }

    public function testAutowiringThrowsWhenSerializerServiceMissing(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('anonymizer.serializer_options', [
            'enabled' => true,
            'mode' => 'autowire',
            'custom_serializer' => null,
        ]);

        $this->expectException(ContainerException::class);
        (new SerializerCompilerPass())->process($container);
    }

    public function testUsesCustomServiceWhenConfigured(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('anonymizer.serializer_options', [
            'enabled' => true,
            'mode' => 'custom',
            'custom_serializer' => CustomSerializer::class,
        ]);
        $container->register(CustomSerializer::class);

        (new SerializerCompilerPass())->process($container);

        self::assertTrue($container->hasAlias('anonymizer.serializer'));
        $alias = $container->getAlias('anonymizer.serializer');
        self::assertSame(CustomSerializer::class, (string) $alias);
        self::assertTrue($alias->isPublic());
    }

    public function testCustomServiceThrowsWhenCustomSerializerMissing(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('anonymizer.serializer_options', [
            'enabled' => true,
            'mode' => 'custom',
            'custom_serializer' => null,
        ]);

        $this->expectException(AnonymizerConfigException::class);
        (new SerializerCompilerPass())->process($container);
    }

    public function testCustomServiceThrowsWhenServiceDoesNotExist(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('anonymizer.serializer_options', [
            'enabled' => true,
            'mode' => 'custom',
            'custom_serializer' => CustomSerializer::class,
        ]);

        $this->expectException(ContainerException::class);
        (new SerializerCompilerPass())->process($container);
    }

    public function testUsesInternalFactoryWhenConfigured(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('anonymizer.serializer_options', [
            'enabled' => true,
            'mode' => 'internal',
            'custom_serializer' => null,
        ]);
        $container->register(SerializerFactory::class);

        (new SerializerCompilerPass())->process($container);

        self::assertTrue($container->hasDefinition('anonymizer.serializer'));
        $definition = $container->getDefinition('anonymizer.serializer');
        self::assertSame(Serializer::class, $definition->getClass());
        self::assertEquals([new Reference(SerializerFactory::class), 'create'], $definition->getFactory());
    }

    public function testProcessThrowsOnInvalidMode(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('anonymizer.serializer_options', [
            'enabled' => true,
            'mode' => 'unsupported',
            'custom_serializer' => null,
        ]);

        $this->expectException(RuleDefinitionException::class);
        (new SerializerCompilerPass())->process($container);
    }
}

final class CustomSerializer implements SerializerInterface
{
    public function serialize(mixed $data, string $format, array $context = []): string
    {
        return '';
    }

    public function deserialize(
        mixed $data,
        string $type,
        string $format,
        array $context = [],
    ): mixed {
        return null;
    }
}
