<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DependencyInjection\Compiler;

use PhpAnonymizer\Anonymizer\Exception\AnonymizerConfigException;
use PhpAnonymizer\Anonymizer\Exception\ContainerException;
use PhpAnonymizer\Anonymizer\Exception\RuleDefinitionException;
use PhpAnonymizer\Anonymizer\Serializer\SerializerFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class SerializerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('anonymizer.serializer_options')) {
            return;
        }

        /**
         * @var array{
         *    enabled: bool,
         *    mode: string,
         *    custom_serializer: ?string,
         *  } $serializerOptions
         */
        $serializerOptions = $container->getParameter('anonymizer.serializer_options');
        if (!$serializerOptions['enabled']) {
            return;
        }

        match ($serializerOptions['mode']) {
            'autowire' => $this->useAutowiring($container),
            'custom' => $this->useCustomService(
                container: $container,
                class: $serializerOptions['custom_serializer'] ?? throw AnonymizerConfigException::missingCustomSerializer(),
            ),
            'internal' => $this->useInternalFactory($container),
            default => throw new RuleDefinitionException(''),
        };
    }

    private function useAutowiring(ContainerBuilder $container): void
    {
        if (!$container->has(SerializerInterface::class)) {
            throw new ContainerException(
                sprintf('Serializer service "%s" does not exist in container.', SerializerInterface::class),
            );
        }

        $container->setAlias(
            alias: 'anonymizer.serializer',
            id: SerializerInterface::class,
        )->setPublic(true);
    }

    private function useCustomService(ContainerBuilder $container, string $class): void
    {
        if (!$container->has($class)) {
            throw new ContainerException(
                sprintf('Serializer service "%s" does not exist in container.', $class),
            );
        }

        $container->setAlias(
            alias: 'anonymizer.serializer',
            id: $class,
        )->setPublic(true);
    }

    private function useInternalFactory(ContainerBuilder $container): void
    {
        $container
            ->register('anonymizer.serializer', Serializer::class)
            ->setFactory([
                new Reference(SerializerFactory::class),
                'create',
            ]);
    }
}
